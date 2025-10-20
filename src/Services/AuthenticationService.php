<?php

namespace UntitledDevelopers\KockatoosAdminCore\Services;

use Illuminate\Support\Facades\Hash;
use UntitledDevelopers\KockatoosAdminCore\Exceptions\AccountLockedException;
use UntitledDevelopers\KockatoosAdminCore\Facades\Auth;
use UntitledDevelopers\KockatoosAdminCore\Models\Admin;

class AuthenticationService
{
    protected MfaService $mfaService;

    public function __construct(MfaService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    /**
     * @param array{identifier: string, password: string, remember:boolean, mfa_code?: string} $loginData Login credentials
     * @return boolean
     * @throws AccountLockedException
     */
    public function login(array $loginData): bool
    {
        $identifierField = config('login.identifier');

        $user = Admin::where($identifierField, $loginData['identifier'])->first();

        if (!$user || !Hash::check($loginData['password'], $user->password)) {
            return false;
        }

        if ($user->is_locked) {
            throw new AccountLockedException();
        }

        if ($this->mfaService->hasMfa($user)) {
            $mfaCode = $loginData['mfa_code'] ?? null;

            if (!$mfaCode || !$this->mfaService->verifyCode($user, $mfaCode)) {
                return false;
            }
        }

        //TODO test the remember me functionality
        Auth::login($user, $loginData['remember'] ?? false);

        $user->last_login_at = now();
        $user->save();

        $accessToken = $user->createToken(config('app.key'))->plainTextToken;
        $user = $user->toArray();
        $user['access_token'] = $accessToken;

        return true;
    }


}
