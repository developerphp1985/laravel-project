<?php

namespace App\Http\Middleware;

use Closure;
use App;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;

class Language
{
    /**
     * The availables languages.
     *
     * @array $languages
     */
    protected $languages = ['en'];

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    public $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(Auth::user())
        {
            App::setLocale(Auth::user()->language);
        }
        else
        {
            App::setLocale($request->getPreferredLanguage($this->languages));
        }
        
        return $next($request);
    }
}