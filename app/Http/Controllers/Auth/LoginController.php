<?php

namespace App\Http\Controllers\Auth;

use DB;
use App;
use Cache;

use App\Helpers\LoggerHelper;
use App\Http\Controllers\Controller;

use App\Models\Activation;
use App\Models\User;
use App\Models\Transactions;

use App\Notifications\UserLoginNotify;
use App\Notifications\SendWhiteUserActivationEmail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use jeremykenedy\LaravelRoles\Models\Role;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectUserAfterLogin = '/home';
	
	protected $redirectToWebWallet = '/home/setwalletpin.html';

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectAfterLogout = '/login';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm(Request $request)
    {		
        $user = Auth::check() ? Auth::user() : false;
        if ($user) {
            return redirect('/home');
        }
        return view('auth.login');
    }

    /**
     * Handle a login request
     *
     * @return Response
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $credentials = array('email' => $request->email, 'password' => $request->password);
        if (Auth::validate(['email' => $request->email, 'password' => $request->password, 'status' => 0, 'role' => 2])) {
            $role = Role::where('slug', '=', 'unverified')->first();
            if (Auth::attempt($credentials, $request->has('remember'))) {
                Auth::user()->attachRole($role);
                return redirect()->route('activate');
            } else {
                return redirect()
                    ->to('/login')
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors(['email' => trans('auth.login_incorrect')]);
            }
        } 
		else if (Auth::validate(['email' => $request->email, 'password' => $request->password, 'status' => 2, 'role' => 2])) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => trans('auth.account_delete')]);
        } 
		else if (Auth::validate(['email' => $request->email, 'password' => $request->password, 'status' => 1, 'role' => 2])) 
		{
			//echo "Success";echo Auth::user()->email;die;
			
            $role = Role::where('slug', '=', 'user')->first();
            if (Auth::attempt($credentials, $request->has('remember'))) 
			{
				$ip_address = \Request::getClientIp(true);
				
                App::setLocale(Auth::user()->language);
                $record = [
                    'message' => $request->email.' Login with IP address : '.$ip_address,
                    'level' => 'INFO',
                    'context' => 'Login'
                ];
                LoggerHelper::writeDB($record);	
				
                $UserInfo = User::find(Auth::user()->id);
                $UserInfo->online = 1;
				$UserInfo->save();
				
				//self::sendLoginEmailNotification($UserInfo, 'website', $ip_address, date("d/M/Y h:i A"));
				
				if(is_null($UserInfo->app_pin))
				{
					//return redirect()->to($this->redirectToWebWallet);
				}
				
				if(isset($request->_previous_url) && strpos($request->_previous_url, '/videos') !== false)
				{
					return redirect()->to($request->_previous_url);
				}
				else
				{
					return redirect()->to($this->redirectUserAfterLogin);
				}
            }
			else 
			{
                return redirect()
                    ->to('/login')
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors(['email' => trans('auth.login_incorrect')]);
            }
        } else {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => trans('auth.login_incorrect')]);
        }
    }

    /**
     * Logout, Clear Session, and Return.
     *
     * @return void
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            $record = [
                'message' => Auth::user()->email,
                'level' => 'INFO',
                'context' => 'Logout'
            ];
            LoggerHelper::writeDB($record);
            DB::table('users')->where('id',Auth::user()->id)->update(['device_token'=>NULL,'online'=>0]);
        }
        $request->session()->flush();
        Session::flush();
        Auth::logout();
        Cache::flush();
        return redirect(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/');
    }
	
	public function logoutlendo(Request $request)
    {
        if (Auth::check()) 
		{
            $record = [
                'message' => Auth::user()->email,
                'level' => 'INFO',
                'context' => 'Logout'
            ];
            LoggerHelper::writeDB($record);
            DB::table('users')->where('id',Auth::user()->id)->update(['device_token'=>NULL,'online'=>0]);
			$request->session()->flush();
			Session::flush();
			Auth::logout();
			Cache::flush();
        }
		
		if(isset($_GET['platform']) && $_GET['platform'] == 1){
			return redirect("register");
		}
		else
		{
			return redirect(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/');
		}
    }

    public function activateuser($token)
    {
        if (isset($token)) 
		{
            $activation = Activation::where('token', $token)->orderBy('created_at', 'desc')->get()->first();
            if (!empty($activation) && $activation->user_id) 
			{
                $user = User::find($activation->user_id);
				
				$distribute_bonus = 0;
				if(!$user->status)
				{
					$distribute_bonus = 1;
				}
				
                $role = Role::where('slug', '=', 'user')->first();
                $user->status = true;
				
                $user->detachAllRoles();
                $user->attachRole($role);
                $user->last_update_ip = \Request::getClientIp(true);
				
				if($user->save() && $distribute_bonus == 1)
				{				
					$referrer_user_id = $user->referrer_user_id;
					$user_e = $user->email;
					$value = '5';
					$type  = '4';
					$description = "Got referral bonus of ".$value.'ELT on community registration of email :'.$user_e;
					$this->give_bonus($referrer_user_id,$value,$description,$type);
					
			
					// give 50 ELT bonus to new user - 25 for loan + 25 for card
					if($user->reserve_card > 0)
					{
						$value = '25';
						$type  = '3';
						$description = "Got community registration bonus for card priority of ".$value.'ELT';
						$this->give_bonus($user->id,$value,$description,$type);		
					}
					
					if($user->loan_amount_requested > 0)
					{
						$value = '25';
						$type  = '2';
						$description = "Got community registration bonus for loan priority of ".$value.'ELT';
						$this->give_bonus($user->id,$value,$description,$type);		
					}
				}
		
                $allActivations = Activation::where('user_id', $user->id)->get();
                foreach ($allActivations as $anActivation) {
                    $anActivation->delete();
                }
                return redirect()->route('login')->with('success', trans('auth.successActivated'));
            }
        }
        return redirect()->route('login')->with('error', trans('lendo.InvalidTokenText'));
    }
	
	private function give_bonus($user_id,$value,$description,$type)
	{
		$user = User::where('id', '=', $user_id)->first();
		$ledger = 'ELT';
        $transaction_id = uniqid();
		$phaseId = NULL;
        $description = $description .' Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
        $Transaction = Transactions::createTransaction($user_id, $ledger, $value, $description, 1, $transaction_id, $phaseId, NULL, $type);
        $user->addValue('ELT_balance', $value);
        return $user->save();
	}
	

    public function sendUserActivationEmail(User $user, $token)
    {
        $user->notify(new SendWhiteUserActivationEmail($token));
    }

    public function whitelistsignup(Request $request)
    {
        error_reporting(0);
        $response = array();
        $response['status'] = false;
        $response['code'] = 401;
        $response['message'] = 'unauthorized access';
        $name = $_REQUEST['name'];
        $email = $_REQUEST['email'];
        $phone = $_REQUEST['phone'];
        $token = $_REQUEST['token'];
        
        if ($token == 'XaescigdFvkdmfdfndgDdsjvX') {
            if (empty($name) || empty($email)) {
                $response['code'] = 406;
                $response['message'] = 'Invalid parameters';
            } else {
                $user = User::where('email', '=', $email)->first();
                if ($user === null) {
                    $check_response = User::check_whitelist_email($email);
                    if ($check_response == 0) {
                        $email_token = str_random(64);
                        $ip_address = \Request::getClientIp(true);
                        $white_list_data = array
                        (
                            "name" => $name,
                            "email" => $email,
                            "phone" => $phone,
                            "status" => "0",
                            'token' => $email_token,
                            "ip_address" => $ip_address,
                            "created_at" => date("Y-m-d H:i:s")
                        );
                        $add_whitelist_user = User::add_whitelist_user($white_list_data);
                        if ($add_whitelist_user) {
                            $response['code'] = 200;
                            $response['status'] = true;
                            $response['message'] = 'user added as whitelist';
                            $user = User::find(1);
                            $user->email = $email;
                            self::sendUserActivationEmail($user, $email_token);
                        } else {
                            $response['code'] = 407;
                            $response['message'] = 'some error occurred';
                        }
                    } else {
                        $response['code'] = 408;
                        $response['message'] = 'user already registered';
                    }
                } else {
                    $response['code'] = 408;
                    $response['message'] = 'user already registered';
                }
            }
        }
        echo json_encode($response);
        exit;
    }

    public function verifywhitelist($token)
    {
        if (isset($token)) {
            $response = User::get_whitelist_user_by_token($token);
            if (!empty($response) && $response->id) {
                User::update_whitelist_user($response->id, array("status" => 1));
                $refkey = 'VwymG';
                setcookie('lendo_ref', $refkey, time() + ((365 * 60 * 60 * 24) - 5), "/");
                return redirect()->route('signup')->with('success', trans('lendo.WhiteListUserVerifiedSuccess'));
            }
        }
        return redirect()->route('login')->with('error', trans('lendo.InvalidWhiteListToken'));
    }

    public function whitelistpackage($token, $amount)
    {
        if (isset($token) && isset($amount)) {
            $response = User::get_whitelist_user_by_token($token);
            if (!empty($response) && $response->id) {
                User::update_whitelist_user($response->id, array("amount" => $amount, "token" => ''));
                return redirect()->route('login')->with('success', trans('lendo.WhitelistUserPackageUpdatedSuccess'));
            }
        }
        return redirect()->route('login')->with('error', trans('lendo.InvalidWhiteListToken'));
    }
	
	public function sendLoginEmailNotification(User $user, $platform, $ip_address, $date_time)
    {
        $user->notify(new UserLoginNotify($platform, $ip_address, $date_time));
    }
	
	
	
}
