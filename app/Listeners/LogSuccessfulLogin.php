<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Spatie\Activitylog\Facades\Activity;

class LogSuccessfulLogin
{
    public function handle(Login $event)
    {
        Activity::causedBy($event->user)
            ->withProperties([
                'user_id' => $event->user->id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('login');
    }
}
