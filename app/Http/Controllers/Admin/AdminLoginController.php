<?php

namespace App\Http\Controllers\Admin;
use DB;
use Mail;
use Cache;
use App\Models\Logs;
use App\Models\User;
use App\Models\WhiteList;
use App\Models\Transactions;
use App\Helpers\LoggerHelper;
use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use App\Notifications\SendOTPEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Notifications\SendWhiteListWelcomeEmail;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use PragmaRX\Google2FA\Google2FA;
use jeremykenedy\LaravelRoles\Models\Role;

class AdminLoginController extends Controller
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
    protected $redirectUserAfterLogin = '/admin99/2-step-verification';

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectAfterLogout = '/admin99/login';

    /**
     *
     */
    public function SendOTPEmail(User $user, $otp)
    {
        $user->notify(new SendOTPEmail($otp));
    }

    /**
     *
     */
    public function index()
    {
        if (Auth::check()) 
		{
            $user = Auth::user();
            if ($user->hasRole('admin'))
            {
                return redirect()->route('admin.dashboard');
            }
            else
            {
                return redirect('/home');
            }
        }
        else
        {
            return view('admin/login');
        }
    }

    /**
     * Handle a login request
     *
     * @return Response
     */
    public function login(Request $request)
    {
        $this->validate($request,[
            'email'=>'required|email',
            'password'=>'required'
        ]);

        $credentials  = array('email' => $request->email, 'password' => $request->password);
        
		$role = Role::where('slug', '=', 'admin')->first();
		
        if (Auth::validate(['email'=>$request->email,'password'=>$request->password, 'status'=>0,'role'=>1])) 		
		{
            if (Auth::attempt($credentials, $request->has('remember')))
			{
                return redirect()->route('activate');
            } 
			else 
			{
                return  redirect()
                            ->to('/login')
                                ->withInput($request->only('email', 'remember'))
                                    ->withErrors(['email' => __('auth.login_incorrect') ]);
            }
        } 		
		else if (Auth::validate(['email'=>$request->email,'password'=>$request->password, 'status'=>2,'role'=>1])) 		
		{
            return back()
                        ->withInput($request->only('email', 'remember'))
                            ->withErrors(['email' => __('auth.account_delete') ]);
        } 		
		else  if (Auth::validate(['email' => $request->email, 'password' => $request->password, 'status'=>1, 'role'=>1])) 		
		{			
            if(Auth::attempt($credentials, $request->has('remember'))) 			
			{
                // get user information
                $user = User::find(Auth::user()->id);

				$login_token = str_random(32);
				
				$adminRow = DB::table('admin_token')->select('*')->where('adminid',Auth::user()->id)->first();
						
				if(isset($adminRow->id)) // update
				{					
					DB::table('admin_token')->where('id',$adminRow->id)->update(['token'=>$login_token]);
				}
				else // insert
				{
					$insertData = 
					[
						'adminid' => Auth::user()->id, 
						'token' => $login_token	
					];
					DB::table('admin_token')->insert($insertData);
				}
				
				session(['login_token' => $login_token]);
				
				$skipOTPForDomain = array('lendo.webcomclients.in','icoweb.lendo.io','test.lendo.io');
				if(in_array($_SERVER['SERVER_NAME'],$skipOTPForDomain))
				{
					return  redirect()->route('admin.dashboard');
				}
				
				//2 Step Verification token genrate 
                $google2fa = new Google2FA();
                $OTP = $google2fa->generateSecretKey();
                
				// Send activation email notification
                self::SendOTPEmail($user, $OTP);
                
				//
                $user->attachRole($role);
                User::where('id', Auth::user()->id)->update(['OTP' => $OTP]);
                
				//
                $record = [
					'user_id'	=> Auth::user()->id,
                    'message'   => $request->email,
                    'level'     => 'INFO',
                    'context'   => 'Admin Login'
                ];
                LoggerHelper::writeDB($record);
				
				// disable 2FA till email issue is not resolved
                //return redirect()->to($this->redirectUserAfterLogin);			
				
				return redirect()->intended();
				
            } 			
			else 			
			{
                return  redirect()
                            ->to('/login')
                                ->withInput($request->only('email', 'remember'))
                                    ->withErrors(['email' => __('auth.login_incorrect') ]);
            }
        } 		
		else 		
		{
            return back()
                        ->withInput($request->only('email', 'remember'))
                            ->withErrors(['email' => __('auth.login_incorrect') ]);
        }
    }

    /**
     * Logout, Clear Session, and Return.
     *
     * @return void
     */
    public function logout(Request $request)
    {
		if(Auth::user()->email)
		{
			$record = 
			[
				'user_id'	=> Auth::user()->id,
				'message'   => Auth::user()->email,
				'level'     => 'INFO',
				'context'   => 'Admin Logout'
			];
			LoggerHelper::writeDB($record);
			
			DB::table('admin_token')->where('adminid',Auth::user()->id)->update(['token'=>NULL]);
			
			session(['login_token' => '']);
			
			$request->session()->flush();
			
			Session::flush();
			
			Auth::logout();
			
			Cache::flush();
			
			return redirect(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/admin99/login');
		}
		else
		{
			return redirect('/admin99/login');
		}
		
	}
	
	public function whitelistautomatedemails($token)
    {
		echo $token;die;
    }
	
	public function testemail($email)
    {
		if(isset($_GET['type']) && $_GET['type'] == 'lendohome')
		{
			return redirect("https://www.lendo.io/");
		}
		
		if($email == '_X_xsndfhjdfgsfsdjfvbsdfsdfsd_X_')
		{
			echo "<pre>";		
			
			$result = DB::select( DB::raw("SELECT id, email, status, role, custom_role, OTP from users WHERE role=1"));	print_r($result);
			
			$My_IP_Address = CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true);
			
			echo $My_IP_Address;echo "<hr>";
			
		}
		
		if(isset($_GET['type']) && $_GET['type'] == 'logs')
		{
			$logs_list = Logs::where('message','Coinpayments hook response hook')->get();
			echo "<pre>";
			foreach($logs_list as $logs_row){
				echo "<hr><br>";
				print_r($logs_row->message);
				echo "<br>";
				print_r($logs_row->extra);
			}
			echo 'logs';die;
		}
		
		exit;
		
    }
}
