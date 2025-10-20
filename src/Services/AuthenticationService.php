<?php

namespace UntitledDevelopers\KockatoosAdminCore\Services;

use UntitledDevelopers\KockatoosAdminCore\Exceptions\AccountLockedException;
use UntitledDevelopers\KockatoosAdminCore\Facades\Auth;

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

        //TODO test the remember me functionality
        if (Auth::attempt([$identifierField => $loginData['identifier'], 'password' => $loginData['password']], $loginData['remember'] ?? false)) {
            $user = Auth::user();

            if ($user->is_locked) {
                Auth::guard('web')->logout();
                throw new \UntitledDevelopers\KockatoosAdminCore\Exceptions\AccountLockedException();
            }
            if ($this->mfaService->hasMfa($user)) {
                $mfaCode = $loginData['mfa_code'] ?? null;

                if (!$mfaCode || !$this->mfaService->verifyCode($user, $mfaCode)) {
                    Auth::guard('web')->logout();
                    return false;
                }
            }

            $user->last_login_at = now();
            $user->save();

            $accessToken = $user->createToken(config('app.key'))->plainTextToken;
            $user = $user->toArray();
            $user['access_token'] = $accessToken;
            return true;
        }
        return false;
    }


}
