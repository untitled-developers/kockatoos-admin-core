<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;

use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\Core\BaseAuthenticationController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use UntitledDevelopers\KockatoosAdminCore\Facades\Auth;
use Illuminate\Support\Facades\Log;
use UntitledDevelopers\KockatoosAdminCore\Http\Requests\LoginRequest;
use UntitledDevelopers\KockatoosAdminCore\Services\AuthenticationService;
use UntitledDevelopers\KockatoosAdminCore\Services\LoginRateLimiter;
use function response;

class AuthenticationController
{
    protected LoginRateLimiter $loginRateLimiter;
    protected AuthenticationService $authenticationService;


    public function __construct(LoginRateLimiter $loginRateLimiter, AuthenticationService $authenticationService)
    {
        $this->loginRateLimiter = $loginRateLimiter;
        $this->authenticationService = $authenticationService;
    }

    public function login(LoginRequest $request)
    {
        $loginData = $request->validated();

        if ($this->loginRateLimiter->hasTooManyLoginAttempts($request)) {
            $this->loginRateLimiter->fireLockoutEvent($request);
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . $this->loginRateLimiter->getAvailableIn($request) . ' seconds.'
            ], 429);
        }

        $didLogin = $this->authenticationService->login($loginData);
        if (!$didLogin) {
            $this->loginRateLimiter->incrementLoginAttempts($request);
            return response()->json(['message' => 'Login failed. Please check your credentials and try again.'], 401);
        }

        $this->loginRateLimiter->clearLoginAttempts($request);

        return response()->json([
            'message' => 'Login successfully!',
        ]);

    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        return response()->json(['message' => 'Logged out successfully!']);
    }

    public function me(Request $request)
    {
        $user = Auth::user();
        if ($user == null) {
            abort(response(['message' => 'Unauthenticated'], 401));
        }

        $user->append(['role_name', 'role_display_name']);
        return response()->json($user);
    }


}
