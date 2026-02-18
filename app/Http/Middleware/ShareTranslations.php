<?php

// app/Http/Middleware/ShareTranslations.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\View;

class ShareTranslations
{
    public function handle($request, Closure $next)
    {
        $lang = auth()->check() ? (auth()->user()->lang ?? 'IT') : 'IT';

        $trad_stampaggio = \DB::table('dictionary_table')
            ->where('table_name', 'stampaggio_view')
            ->pluck($lang, 'column_name')
            ->toArray();

        $trad_assemblaggio = \DB::table('dictionary_table')
            ->where('table_name', 'assemblaggio_view')
            ->pluck($lang, 'column_name')
            ->toArray();

        $users = \DB::table('dictionary_table')
            ->where('table_name', 'users')
            ->pluck($lang, 'column_name')
            ->toArray();

        $default = \DB::table('dictionary_table')
            ->where('table_name', 'users')
            ->pluck($lang, 'column_name')
            ->toArray();

        View::share('trad_stampaggio', $trad_stampaggio);
        View::share('trad_assemblaggio', $trad_assemblaggio);
        View::share('users', $users);
        View::share('default', $default);

        return $next($request);
    }
}

