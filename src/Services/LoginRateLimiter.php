<?php

namespace UntitledDevelopers\KockatoosAdminCore\Services;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginRateLimiter
{
    protected int $maxAttempts = 5;

    protected int $decaySeconds = 60;


    public function hasTooManyLoginAttempts(Request $request): bool
    {
        return RateLimiter::tooManyAttempts($this->getThrottleKey($request), $this->maxAttempts);
    }

    public function incrementLoginAttempts(Request $request): int
    {
        RateLimiter::hit($this->getThrottleKey($request), $this->decaySeconds);

        return RateLimiter::attempts($this->getThrottleKey($request));
    }

    public function clearLoginAttempts(Request $request): int
    {
        RateLimiter::clear($this->getThrottleKey($request));
        return RateLimiter::attempts($this->getThrottleKey($request));
    }

    public function getAvailableIn(Request $request): int
    {
        return RateLimiter::availableIn($this->getThrottleKey($request));
    }

    public function fireLockoutEvent(Request $request): void
    {
        event(new Lockout($request));
    }

    protected function getThrottleKey(Request $request): string
    {
        return Str::lower($request->input('identifier')) . '|' . $request->ip();
    }


}
