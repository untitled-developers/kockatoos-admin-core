<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;

use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\Core\BaseAuthenticationController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use UntitledDevelopers\KockatoosAdminCore\Facades\Auth;
use Illuminate\Support\Facades\Log;
use function response;

class AuthenticationController extends BaseAuthenticationController
{
    /**
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);


        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }


//        $user = User::wherePhone($data['phone'])->first();
//        if ($user != null) {
//            Auth::setUser($user);
//            $user = Auth::user();
//            $user->save();
//            return $this->sendLoginResponse($request);
//        }

        if (Auth::guard()->attempt($data, $request->has('remember'))) {
            $this->clearLoginAttempts($request);

            $user = Auth::user();
            $user->last_login_at = now();
            $user->save();

            if ($user->is_locked)
                abort(401, 'Account locked, please contact our support or try again later.');

            $accessToken = $user->createToken(config('app.key'))->plainTextToken;

            $user = $user->toArray();
            $user['access_token'] = $accessToken;
            return redirect('/');
        }

        $this->incrementLoginAttempts($request);

        abort(401, 'Login Failed');

    }

    public static function getLoggedInUserModel()
    {
        $user = Auth::user();
        if ($user == null)
            abort(response(['message' => 'Unauthenticated'], 401));

        $user->makeHidden([
            'is_locked',
            'deleted_at'
        ]);

        $user->append(['role_name', 'role_display_name']);


        return $user;
    }

    public static function getLoggedInUser(): JsonResponse
    {
        return response()->json(self::getLoggedInUserModel());
    }

    public static function user()
    {
        return json_encode(self::getLoggedInUserModel());
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        return response()->json(['message' => 'Logged out successfully!']);
    }

    protected function sendLoginResponse(Request $request): JsonResponse
    {
//        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        $user = \UntitledDevelopers\KockatoosAdminCore\Facades\Auth::user();

        if ($user->is_locked)
            abort(response(['message' => 'Account locked, please contact our support or try again later.'], 401));

        $accessToken = $user->createToken(config('app.name'))->plainTextToken;
        $token = [
            'access_token' => $accessToken
        ];
        Log::info("Authenticated login request for user #$user->id : $user->name");

        return response()->json($token);
    }


}
