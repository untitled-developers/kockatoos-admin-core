<?php

namespace UntitledDevelopers\KockatoosAdminCore\Services;

use UntitledDevelopers\KockatoosAdminCore\Facades\Auth;

class AuthenticationService
{

    /**
     * @param array{identifier: string, password: string, remember:boolean} $loginData Login credentials
     * @return boolean
     */
    public function login(array $loginData): bool
    {
        $identifierField = config('login.identifier');
        if (Auth::attempt([$identifierField => $loginData['identifier'], 'password' => $loginData['password']], $loginData['remember'] ?? false)) {
            $user = Auth::user();
            $user->last_login_at = now();
            $user->save();

            if ($user->is_locked) {
                abort(401, 'Account locked, please contact our support or try again later.');
            }
            $accessToken = $user->createToken(config('app.key'))->plainTextToken;
            $user = $user->toArray();
            $user['access_token'] = $accessToken;
            return true;
        }
        return false;
    }


}
