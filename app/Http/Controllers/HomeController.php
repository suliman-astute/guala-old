<?php

namespace App\Http\Controllers;

use App\Models\ActiveApp;
use App\Models\Cart;
use App\Models\Mail;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }


    public function redirect($code)
    {
        $app = ActiveApp::where("code", $code)->first();
        if($app && Auth::user()->active_apps->contains($app->id))
            return view('app.'.$code.'.index');
        else
            return view('home');
    }

}

