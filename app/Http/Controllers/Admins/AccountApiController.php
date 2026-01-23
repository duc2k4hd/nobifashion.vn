<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountPasswordResetRequest;
use App\Http\Requests\Admin\AccountRoleUpdateRequest;
use App\Http\Requests\Admin\AccountStoreRequest;
use App\Http\Requests\Admin\AccountUpdateRequest;
use App\Http\Resources\AccountResource;
use App\Mail\AccountEmailVerificationMail;
use App\Models\Account;
use App\Services\AccountEmailVerificationService;
use App\Services\AccountLogService;
use App\Services\MailConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountApiController extends Controller
{
    public function __construct(
        protected AccountLogService $accountLogService,
        protected AccountEmailVerificationService $verificationService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Account::class);

        $filters = $this->buildFilters($request);

        $query = Account::query()->with('profile');
        $query->applyFilters($filters);

        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? $request->integer('per_page', 20))));
        $accounts = $query->orderByDesc('id')->paginate($perPage);

        return AccountResource::collection($accounts);
    }

    public function show(Account $account)
    {
        $this->authorize('view', $account);

        return new AccountResource($account->load('profile'));
    }

    public function store(AccountStoreRequest $request)
    {
        $this->authorize('create', Account::class);

        $validated = $request->validated();
        $profileData = $validated['profile'] ?? [];
        unset($validated['profile']);

        $account = DB::transaction(function () use ($validated, $profileData) {
            $account = Account::create($validated);
            $account->profile()->create($profileData);

            return $account->load('profile');
        });

        $this->accountLogService->record('account.created', $account->id, null, [
            'account' => $account->only(['name', 'email', 'role', 'is_active', 'account_status']),
            'profile' => $account->profile?->toArray(),
        ]);

        return new AccountResource($account);
    }

    public function update(AccountUpdateRequest $request, Account $account)
    {
        $this->authorize('update', $account);

        $validated = $request->validated();
        $profileData = $validated['profile'] ?? [];
        unset($validated['profile']);

        $changes = [];

        DB::transaction(function () use ($account, $validated, $profileData, &$changes) {
            if (!empty($validated)) {
                $beforeAccount = $account->getOriginal();
                $account->fill($validated);
                $dirtyAccount = $account->getDirty();

                if (!empty($dirtyAccount)) {
                    $account->save();
                    $changes['account'] = [
                        'before' => array_intersect_key($beforeAccount, $dirtyAccount),
                        'after' => $dirtyAccount,
                    ];
                }
            }

            if (!empty($profileData)) {
                $profile = $account->profile()->firstOrNew([]);
                $beforeProfile = $profile->getOriginal();
                $profile->fill($profileData);
                $dirtyProfile = $profile->getDirty();

                if (!empty($dirtyProfile)) {
                    $profile->save();
                    $changes['profile'] = [
                        'before' => array_intersect_key($beforeProfile, $dirtyProfile),
                        'after' => $dirtyProfile,
                    ];
                }
            }
        });

        if (!empty($changes)) {
            $this->accountLogService->record('account.updated', $account->id, null, $changes);
        }

        return new AccountResource($account->load('profile'));
    }

    public function destroy(Account $account)
    {
        $this->authorize('delete', $account);

        $accountId = $account->id;
        $account->delete();

        $this->accountLogService->record('account.deleted', $accountId);

        return response()->noContent();
    }

    public function toggle(Request $request, Account $account)
    {
        $this->authorize('update', $account);

        $target = $request->has('is_active')
            ? $request->boolean('is_active')
            : !$account->is_active;

        $account->forceFill([
            'is_active' => $target,
            'account_status' => $target ? Account::STATUS_ACTIVE : Account::STATUS_INACTIVE,
        ])->save();

        if (!$target) {
            $this->terminateSessions($account);
        }

        $this->accountLogService->record('account.state_toggled', $account->id, null, [
            'is_active' => $target,
            'account_status' => $account->account_status,
        ]);

        return new AccountResource($account->refresh()->load('profile'));
    }

    public function changeRole(AccountRoleUpdateRequest $request, Account $account)
    {
        $this->authorize('changeRole', $account);

        $validated = $request->validated();
        $previous = $account->role;

        $account->forceFill([
            'role' => $validated['role'],
        ])->save();

        $this->accountLogService->record('account.role_changed', $account->id, null, [
            'before' => $previous,
            'after' => $validated['role'],
            'note' => $validated['note'] ?? null,
        ]);

        return new AccountResource($account->refresh()->load('profile'));
    }

    public function resetPassword(AccountPasswordResetRequest $request, Account $account): JsonResponse
    {
        $this->authorize('resetPassword', $account);

        $data = $request->validated();
        $account->forceFill([
            'password' => $data['password'],
            'last_password_changed_at' => now(),
        ])->save();

        $sessions = 0;
        if ($request->boolean('force_logout')) {
            $sessions = $this->terminateSessions($account);
        }

        $this->accountLogService->record('account.password_reset', $account->id, null, [
            'force_logout' => $request->boolean('force_logout'),
            'sessions_terminated' => $sessions,
        ]);

        return response()->json([
            'message' => 'Password reset successfully.',
            'sessions_terminated' => $sessions,
        ]);
    }

    public function forceLogout(Account $account): JsonResponse
    {
        $this->authorize('forceLogout', $account);

        $sessions = $this->terminateSessions($account);

        $this->accountLogService->record('account.force_logout', $account->id, null, [
            'sessions_terminated' => $sessions,
        ]);

        return response()->json([
            'message' => 'Sessions terminated.',
            'sessions_terminated' => $sessions,
        ]);
    }

    public function verifyEmail(Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        if ($account->email_verified_at) {
            return response()->json([
                'message' => 'Email này đã được xác minh trước đó.',
            ]);
        }

        $token = $this->verificationService->createToken($account);
        $url = URL::temporarySignedRoute('account.email.verify', now()->addDays(3), [
            'token' => $token,
        ]);

        $emailAccountId = config('email_defaults.account_verification');
        MailConfigService::sendWithAccount($emailAccountId, function () use ($account, $url) {
            Mail::to($account->email)->send(new AccountEmailVerificationMail($account, $url, $account->email));
        });

        $this->accountLogService->record('account.verification_email_sent', $account->id, null, [
            'expires_at' => now()->addDays(3)->toDateTimeString(),
        ]);

        return response()->json([
            'message' => 'Đã gửi email xác minh tới người dùng.',
        ]);
    }

    protected function terminateSessions(Account $account): int
    {
        $driver = config('session.driver');
        $deleted = 0;

        if ($driver === 'database') {
            $deleted = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $account->id)
                ->delete();
        }

        $account->forceFill([
            'remember_token' => Str::random(60),
        ])->saveQuietly();

        return $deleted;
    }

    protected function buildFilters(Request $request): array
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(Account::roles())],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'account_status' => ['nullable', Rule::in(Account::statuses())],
            'email_verified' => ['nullable', Rule::in(['yes', 'no'])],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'location' => ['nullable', 'string', 'max:255'],
            'last_login_from' => ['nullable', 'date'],
            'last_login_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (isset($validated['status'])) {
            $validated['is_active'] = $validated['status'] === 'active';
            unset($validated['status']);
        }

        return $validated;
    }
}
 
