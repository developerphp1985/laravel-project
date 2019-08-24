<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Auth;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if (Auth::check()) {
            if (strpos($request->url(), config('constants.admin_url')) !== false) {
                return $request->expectsJson()
                        ? response()->json(['message' => $exception->getMessage()], 401)
                        : redirect()->guest(route('admin.login'))->with('error', trans('message.expred_session'));
                
            } else {
                return $request->expectsJson()
                        ? response()->json(['message' => $exception->getMessage()], 401)
                        : redirect()->guest(route('login'))->with('error', trans('message.expred_session'));
            }
        // without loign access url admin
        } else if (strpos($request->url(), config('constants.admin_url')) !== false) {
            return $request->expectsJson()
                        ? response()->json(['message' => $exception->getMessage()], 401)
                        : redirect()->guest(route('admin.login'))->with('error', trans('message.unauthenticatedAccessURL'));
        // without loign access url user
        } else {
            return $request->expectsJson()
                    ? response()->json(['message' => $exception->getMessage()], 401)
                    : redirect()->guest(route('login'))->with('error',  trans('message.unauthenticatedAccessURL'));
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        /**
         *
         */
        if (
            $exception instanceof \jeremykenedy\LaravelRoles\Exceptions\RoleDeniedException ||
            $exception instanceof \jeremykenedy\LaravelRoles\Exceptions\LevelDeniedException ||
            $exception instanceof \jeremykenedy\LaravelRoles\Exceptions\PermissionDeniedException
        )
        {
            if (Auth::check()) {
                $user  = Auth::user();
                if ($user->hasRole('admin')) {
                    return redirect()->route('admin.dashboard');
                } else {
                    return redirect()->to('/home');
                }
            }
        }

        /**
         *
         */
        if ($exception instanceof TokenMismatchException){
            // Redirect to a form. Here is an example of how I handle mine
            return redirect()
                    ->back()
                        ->with('error', trans('message.TokenMismatchException'));
        }

        /**
         *
         */
        if($this->isHttpException($exception))
        {
            switch ($exception->getStatusCode())
            {
                // not found
                case 404:
                    $data = array('code' => $exception->getStatusCode(), 'message' => $exception->getMessage());
                    return response()->view('errors.error', $data, $exception->getStatusCode());
                    break;
                //
                case 403:
                    $data = array('code' => $exception->getStatusCode(), 'message' => $exception->getMessage());
                    return response()->view('errors.error', $data, $exception->getStatusCode());
                    break;
                //
                case 405:
                    $data = array('code' => $exception->getStatusCode(), 'message' => $exception->getMessage());
                    return response()->view('errors.error', $data, $exception->getStatusCode());
                    break;
                // internal server error
                case 500:
                    $data = array('code' => $exception->getStatusCode(), 'message' => $exception->getMessage());
                    return response()->view('errors.error', $data, $exception->getStatusCode());
                    break;
                //
                default:
                    return parent::render($request, $exception);
            }
        }

        //
        return parent::render($request, $exception);
    }
}
