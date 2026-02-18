<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Spatie\Activitylog\Facades\Activity;

class LogSuccessfulLogout
{
    public function handle(Logout $event)
    {
        Activity::causedBy($event->user)
            ->withProperties([
                'user_id' => $event->user->id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('logout');
    }
}
