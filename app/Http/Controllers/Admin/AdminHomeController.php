<?php
namespace App\Http\Controllers\Admin;
use View;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Helpers\LoggerHelper;
use App\Http\Controllers\CommandController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

use PragmaRX\Google2FA\Google2FA;
use jeremykenedy\LaravelRoles\Models\Role;

class AdminHomeController extends Controller
{
	private $currentRouteName;
	public function __construct()
    {
		$this->currentRouteName = \Request::route()->getName();
		$this->middleware(function ($request, $next) {
			
			$this->user= Auth::user();
			
			$session_token = session('login_token');
			
			if(!User::is_valid_admin_session(Auth::user()->id, $session_token)) 		
			{			
				auth()->logout();			
				session(['login_token' => '']);			
				return redirect()->route('admin.login')->withErrors(['email' => trans('lendo.another_user_login_with_admin_account')]);					
			}			
			return $next($request);
			
		});
	}
	
	public function twoSetpVarification(Request $request)
	{
        $user = Auth::user();				
		if($request->all()) {
			$validator = Validator::make($request->all(), [
                'OTP'	=> 'required',
            ],[
                'OTP.required'   => 'OTP is required',
            ]);
            if ($validator->fails()) {
            	return redirect()
                        ->route('admin.twoSetpVarification')
                            ->withErrors($validator)
                                ->withInput($request->only('OTP'));
            } else {
            	$google2fa = new Google2FA();
            	$secret = $request->input('OTP');
            	$valid = $google2fa->verifyKey($user->OTP, $secret);
                if($user->OTP == $secret)
                {
                    User::where('id', Auth::user()->id)
                        ->update(['OTP' => NULL]); 
                    return  redirect()->route('admin.dashboard');
                }
                else
                {
                    return redirect()
                        ->route('admin.twoSetpVarification')
                            ->withErrors(['OTP' => 'Enter OTP is wrong']);
                }
            }
        }
		return view('admin.twoSetpVarification');
	}

	public function index()
    {
    	$user = Auth::user();
    	if(is_null($user->OTP))
    	{  
            $commandObj = new CommandController();            
            $commanddata['frontEndTestCase'] = $commandObj->composer();
            $commanddata['adminTestCase'] = $commandObj->adminTestCase();
    		return view('admin.dashboard')->with($commanddata);
    	}
    	else
    	{
    		return redirect()
    				->route('admin.twoSetpVarification')
    					->withErrors(['OTP' => 'Please enter your OTP']);
    	}
    }
}
