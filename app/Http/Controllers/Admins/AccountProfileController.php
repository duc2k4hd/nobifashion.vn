<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountAvatarUploadRequest;
use App\Http\Requests\Admin\AccountProfileUpdateRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Account;
use App\Models\Profile;
use App\Services\AccountLogService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AccountProfileController extends Controller
{
    public function __construct(protected AccountLogService $accountLogService)
    {
    }

    public function show(Account $account)
    {
        $this->authorize('view', $account);

        return new ProfileResource($this->ensureProfile($account));
    }

    public function update(AccountProfileUpdateRequest $request, Account $account)
    {
        $this->authorize('update', $account);

        $profile = $this->ensureProfile($account);
        $profile->fill($request->validated());
        $dirty = $profile->getDirty();
        $before = array_intersect_key($profile->getOriginal(), $dirty);
        $profile->save();

        if (!empty($dirty)) {
            $this->accountLogService->record('profile.updated', $account->id, null, [
                'before' => $before,
                'after' => $dirty,
            ]);
        }

        return new ProfileResource($profile->refresh());
    }

    public function toggleVisibility(Request $request, Account $account)
    {
        $this->authorize('update', $account);

        $profile = $this->ensureProfile($account);
        $nextState = $request->has('is_public')
            ? $request->boolean('is_public')
            : !$profile->is_public;

        $profile->is_public = $nextState;
        $profile->save();

        $this->accountLogService->record('profile.visibility_updated', $account->id, null, [
            'is_public' => $nextState,
        ]);

        return new ProfileResource($profile->refresh());
    }

    public function upload(AccountAvatarUploadRequest $request, Account $account)
    {
        $this->authorize('update', $account);

        $profile = $this->ensureProfile($account);
        $changes = [];

        if ($request->boolean('remove_avatar') && $profile->avatar) {
            $this->rememberHistory($profile, 'avatar', $profile->avatar);
            $profile->avatar = null;
            $changes['avatar'] = null;
        }

        if ($request->boolean('remove_sub_avatar') && $profile->sub_avatar) {
            $this->rememberHistory($profile, 'sub_avatar', $profile->sub_avatar);
            $profile->sub_avatar = null;
            $changes['sub_avatar'] = null;
        }

        if ($request->filled('history_restore') && in_array($request->history_restore, ['avatar', 'sub_avatar'], true)) {
            $field = $request->history_restore;
            $restored = $this->restoreFromHistory($profile, $field);
            if ($restored) {
                $this->rememberHistory($profile, $field, $profile->{$field});
                $profile->{$field} = $restored;
                $changes[$field] = $restored;
            }
        }

        if ($request->hasFile('avatar')) {
            $this->rememberHistory($profile, 'avatar', $profile->avatar);
            $profile->avatar = $this->storeAvatarFile($request->file('avatar'), $account, 'main');
            $changes['avatar'] = $profile->avatar;
        }

        if ($request->hasFile('sub_avatar')) {
            $this->rememberHistory($profile, 'sub_avatar', $profile->sub_avatar);
            $profile->sub_avatar = $this->storeAvatarFile($request->file('sub_avatar'), $account, 'sub');
            $changes['sub_avatar'] = $profile->sub_avatar;
        }

        if (!empty($changes)) {
            $profile->save();
            $this->accountLogService->record('profile.avatar_updated', $account->id, null, $changes);
        }

        return new ProfileResource($profile->refresh());
    }

    protected function ensureProfile(Account $account): Profile
    {
        return $account->profile()->firstOrCreate([]);
    }

    protected function storeAvatarFile(UploadedFile $file, Account $account, string $type): string
    {
        $directory = $this->avatarDirectoryPath();

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = $account->id . '_' . $type . '_' . Str::random(20) . '.' . $extension;

        $file->move($directory, $filename);

        return $filename;
    }

    protected function avatarDirectoryPath(): string
    {
        return public_path('admins/img/accounts');
    }

    protected function rememberHistory(Profile $profile, string $field, ?string $oldFilename): void
    {
        if (!$oldFilename) {
            return;
        }

        $key = $field . '_history';
        $history = $profile->{$key};
        if (!is_array($history)) {
            $history = [];
        }
        array_unshift($history, $oldFilename);
        $profile->{$key} = array_slice(array_values(array_unique(array_filter($history))), 0, 5);
    }

    protected function restoreFromHistory(Profile $profile, string $field): ?string
    {
        $key = $field . '_history';
        $history = $profile->{$key};
        if (!is_array($history) || empty($history)) {
            return null;
        }
        $next = array_shift($history);
        $profile->{$key} = $history;

        return $next;
    }
}
