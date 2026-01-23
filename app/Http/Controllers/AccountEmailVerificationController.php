<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\AccountEmailVerificationService;
use App\Services\AccountLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountEmailVerificationController extends Controller
{
    public function __construct(
        protected AccountEmailVerificationService $verificationService,
        protected AccountLogService $accountLogService
    ) {
    }

    public function verify(Request $request, string $token): View
    {
        $status = 'error';
        $message = 'Liên kết không hợp lệ hoặc đã hết hạn.';

        $record = $this->verificationService->findValidToken($token);

        if ($record && $record->account) {
            $account = $record->account;

            if (!$account->email_verified_at) {
                $account->forceFill([
                    'email_verified_at' => now(),
                    'account_status' => Account::STATUS_ACTIVE,
                    'login_attempts' => 0,
                ])->save();

                $this->accountLogService->record('account.email_verified', $account->id);
            }

            $this->verificationService->consume($record);

            $status = 'success';
            $message = 'Email của bạn đã được xác minh thành công.';
        }

        return view('clients.auth.verify-email-result', [
            'status' => $status,
            'message' => $message,
        ]);
    }
}
