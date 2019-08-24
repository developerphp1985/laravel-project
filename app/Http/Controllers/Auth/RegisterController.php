<?php
namespace App\Http\Controllers\Auth;
use DB;
use App\Models\User;
use App\Models\FileAttachments;
use App\Models\LendoCards;
use App\Models\Activation;
use App\Models\ParentChild;
use App\Models\Transactions;
use App\Models\Configurations;
use App\Traits\GenrateKeyTrait;
use App\Traits\ActivationTrait;
use App\Helpers\LoggerHelper;
use App\Helpers\CommonHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Notifications\SendActivationEmail;
use App\Notifications\SendAirdropActivationEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use jeremykenedy\LaravelRoles\Models\Role;
use App\Logic\Activation\ActivationRepository;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Helpers\CaptchaHelper;


class RegisterController extends Controller
{
	/*
	|--------------------------------------------------------------------------
	| Register Controller
	|--------------------------------------------------------------------------
	|
	| This controller handles the registration of new users as well as their
	| validation and creation. By default this controller uses a trait to
	| provide this functionality without requiring any additional code.
	|
	*/
	use ActivationTrait;
	use RegistersUsers;
	use GenrateKeyTrait;

	/**
	 * Where to redirect users after registration.
	 *
	 * @var string
	 */
	protected $loginPath = '/login';
	protected $redirectTo = '/email-verification';
	protected $redirectAfterLogout = '/';
	protected $blockIPAddress = array('83.122.175.238','83.123.254.33','83.123.200.4','80.82.67.201');
	
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->middleware('guest');
	}

	/**
	 * Get a validator for an incoming registration request.
	 *
	 * @param  array $data
	 * @return \Illuminate\Contracts\Validation\Validator
	 */
	protected function validator(array $data)
	{
		return Validator::make($data, [
			'first_name' => 'required',
			'last_name' => 'required',
			'email' => 'required|email|max:255|unique:users',
			'password' => 'required|min:6|max:20|confirmed',
			'password_confirmation' => 'required|same:password',
			'termsConditionRegister' => 'required',
		], [
			'email.required' => trans('auth.emailRequired'),
			'email.email' => trans('auth.emailInvalid'),
			'email.max' => trans('auth.emailMax'),
			'email.unique' => trans('auth.emailUnique'),
			'password.required' => trans('auth.passwordRequired'),
			'password.min' => trans('auth.PasswordMin'),
			'password.max' => trans('auth.PasswordMax'),
			'password.confirmed' => trans('auth.PasswordConfirmed'),
			'password_confirmation.required' => trans('auth.PasswordConfirmationRequired'),
			'password_confirmation.same' => trans('auth.PasswordConrimationSame'),
			'termsConditionRegister.required' => trans('auth.termsConditionRegister'),
		]);
	}

	/**
	 * Get a validator for an incoming airdrop registration request.
	 *
	 * @param  array $data
	 * @return \Illuminate\Contracts\Validation\Validator
	 */
	protected function airdrop_validator(array $data)
	{
		return Validator::make($data, [
			'first_name' => 'required|alpha_spaces',
			'last_name' => 'required|alpha_spaces',
			'email' => 'required|email|max:255|unique:users',
			'telegram_id' => 'required',
			'password' => 'required|min:6|max:20|confirmed',
			'password_confirmation' => 'required|same:password',
			'termsConditionRegister' => 'required'
		], [
			'first_name.required' => trans('auth.fNameRequired'),
			'last_name.required' => trans('auth.lNameRequired'),
			'email.required' => trans('auth.emailRequired'),
			'email.email' => trans('auth.emailInvalid'),
			'email.max' => trans('auth.emailMax'),
			'email.unique' => trans('auth.emailUnique'),
			'password.required' => trans('auth.passwordRequired'),
			'password.min' => trans('auth.PasswordMin'),
			'password.max' => trans('auth.PasswordMax'),
			'password.confirmed' => trans('auth.PasswordConfirmed'),
			'password_confirmation.required' => trans('auth.PasswordConfirmationRequired'),
			'password_confirmation.same' => trans('auth.PasswordConrimationSame'),
			'termsConditionRegister.required' => trans('auth.termsConditionRegister')
		]);
	}

	/**
	 * Create a new user instance after a valid registration.
	 *
	 * @param  array $data
	 * @return \App\User
	 */
	protected function create(array $data)
	{
		$role = Role::where('slug', '=', 'unverified')->first();

		//check referrer key and generate new
		referrer_key:
		$referrer_key = $this->getUniqueKey(5);
		$uniqueKeyData = User::select('referrer_key')->where('referrer_key', '=', $referrer_key)->first();
		if (count($uniqueKeyData) > 0) {
			GOTO referrer_key;
		}

		//Check if it reffered by any user
		$default_referrer = Configurations::where('name', 'Default-referrer-user-id')->where('valid_to', '9999-12-31')->pluck('defined_value');
		$default_referrer = count($default_referrer) ? $default_referrer[0] : 1;
		if (isset($_COOKIE["lendo_ref"])) {
			$refkey = $_COOKIE["lendo_ref"];
			$userRef = User::where('referrer_key', '=', $refkey)->first();
			if (isset($userRef) && $userRef->id != 0) {
				$referrer_user_id = $userRef->id;
			} else {
				$referrer_user_id = $default_referrer;
			}
		} else {
			$referrer_user_id = $default_referrer;
		}

		$registration_ip = CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true);
		
		$user = User::create([
			'user_name' => $this->genrateUniqueUsername($data['email']),
			'email' => $data['email'],
			'first_name' => isset($data['first_name']) ? $data['first_name'] : NULL,
			'last_name' => isset($data['last_name']) ? $data['last_name'] : NULL,
			'password' => bcrypt($data['password']),
			'referrer_key' => $referrer_key,
			'referrer_user_id' => $referrer_user_id,
			'registration_ip' => $registration_ip,
		]);
		
		//
		$user->attachRole($role);

		if($user->id > 0){
			ParentChild::restructure_child($user->id);
		}
		
		//
		$activation = new Activation();
		$activation->user_id = $user->id;
		$activation->token = str_random(64);
		$activation->ip_address = CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true);
		$activation->save();

		/* update referral count */
		DB::statement("UPDATE users SET referrer_count=referrer_count+1 WHERE id=" . $referrer_user_id);

		// Send activation email notification
		self::sendNewActivationEmail($user, $activation->token);

		//
		$record = [
			'message' => 'User Register successfully email: ' . $data['email'],
			'level' => 'INFO',
			'context' => 'Register',
			'userId' => $user->id
		];
		LoggerHelper::writeDB($record);

		//
		return $user;
	}

	public function sendNewActivationEmail(User $user, $token)
	{
		$user->notify(new SendActivationEmail($token));
	}

	public function sendAirdropActivationEmail(User $user, $token)
	{
		$user->notify(new sendAirdropActivationEmail($token));
	}

	/**
	 * Generate Unique Username
	 * This function call if we want to genrate unique username
	 * @var $username string (email address through create username)
	 * @return $username string (unique username)
	 *
	 * user this function :
	 * Auth_controller : userJoin(), userSocialJoinLogin()
	 */
	public function genrateUniqueUsername($username)
	{
		$username = substr($username, 0, strpos($username, '@'));             //
		$username = str_replace(' ', '_', $username);                         // Replaces all spaces with hyphens.
		$username = preg_replace('/-+/', '_', $username);                     // Replaces multiple hyphens with single one.
		$name = $username = preg_replace('/[^A-Za-z0-9\_]/', '', $username);    // Removes special chars.

		$result = User::where('user_name', 'like', '%' . $username . '%')->pluck('user_name')->toArray();

		if (isset($result) && !empty($result)) {
			for ($i = 1; $i < COUNT($result) * 2; $i++) {
				if (in_array($username, $result)) {
					$username = $name . $i;
				} else {
					break;
				}
			}
		}
		return $username;
	}


	/**
	 * Check for save referral key in cookie
	 *
	 * @param  array $data
	 * @return User
	 */
	public function userReferral($refkey)
	{
		if(isset($refkey) && $refkey == 'JaLrf'){
			return redirect()->route('login');
		}

		if (isset($_COOKIE["lendo_ref"])) {
			//Do Nothing

			if($_COOKIE["lendo_ref"] == 'JaLrf'){
				return redirect()->route('login');
			}

		} else {
			if (!is_null($refkey)) {
				$user = User::where('referrer_key', '=', $refkey)->first();

				if (isset($user)) {
					setcookie('lendo_ref', $refkey, time() + (365 * 86400 - 5), "/"); // 86400 = 1 day
				}
			}
		}

		return redirect()->route('newsignup');
	}

	/**
	 * Check for save referral key in cookie
	 *
	 * @param  array $data
	 * @return User
	 */
	public function airdrop_userReferral($refkey)
	{
		if(isset($refkey) && $refkey == 'JaLrf'){
			return redirect()->route('login');
		}
		
		if (isset($_COOKIE["lendo_ref"])) {
			//Do Nothing

			if($_COOKIE["lendo_ref"] == 'JaLrf'){
				return redirect()->route('login');
			}

		} else {
			if (!is_null($refkey)) {
				$user = User::where('referrer_key', '=', $refkey)->first();

				if (isset($user)) {
					setcookie('lendo_ref', $refkey, time() + (60 * 15), "/"); // 86400 = 1 day
				}
			}
		}

		return redirect()->route('airdrop_signup');
	}
	
	public function signup()
	{
		if (isset($_COOKIE["lendo_ref"])) {
			if ($_POST) {
				$validator = $this->validator($_POST);
				$errors = $validator->errors();
				if ($validator->fails()) {
					return view('registerR', ['errors' => $errors]);
				} else {
					$userCheck = $this->create($_POST);
					$role = Role::where('slug', '=', 'unverified')->first();
					$userCheck->attachRole($role);
					$userCheck->save();
					auth()->login($userCheck, true);
					$success = trans("message.socialRegiterSuccess");
					setcookie('lendo_ref', $_COOKIE["lendo_ref"], time() - 1000, "/");
					return redirect('home')->with('success', $success);
				}
			}
			return view('registerR');
		} else {
			return redirect()->route('login');
		}
	}

	
	
	
	protected function newsignup_validator(array $data)
	{
		return Validator::make($data, [
			'email' => 'required|email|max:255|unique:users',
			'password' => 'required|min:6|max:20',
			'termsConditionRegister' => 'required',
			'notUScitizen' => 'required',
			'confirmNatureAndRisk' => 'required',
			'confirm_password' => 'min:6|required_with:password|same:password|required',
		], [
			'email.required' => trans('auth.emailRequired'),
			'email.email' => trans('auth.emailInvalid'),
			'email.max' => trans('auth.emailMax'),
			'email.unique' => trans('auth.emailUnique'),
			'password.required' => trans('auth.passwordRequired'),
			'password.min' => trans('auth.PasswordMin'),
			'password.max' => trans('auth.PasswordMax'),
			'termsConditionRegister.required' => trans('auth.termsConditionRegister'),
			'notUScitizen.required' => trans('auth.confirNotUSCitizen'),
			'confirmNatureAndRisk.required' => trans('auth.confirNatureRisk'),
			'confirm_password.same' => trans('auth.PasswordConfirmed'),
		]);
	}
	
	

	public function newsignup(Request $request)
	{
		$dataForView['postdata'] = array();
		if($_POST) 
		{			
			$validator = $this->newsignup_validator($_POST);
			$errors = $validator->errors();
			if ($validator->fails()) 
			{
				$dataForView['postdata'] = $_POST;
				$errors->add('isRegsiter', 'Yes');
				$dataForView['errors'] = $errors;
				return view('auth.register', $dataForView);
			}
			$recaptchaResponse = trim($_POST['g-recaptcha-response']);
			$userIp = CommonHelper::get_client_ip()!='' ? CommonHelper::get_client_ip():\Request::getClientIp(true);

			/*
			$secret = env('APP_DEBUG') === true ? '6LfVEFcUAAAAAClCdwp9KOyjmRFrpWMn9VwxJI2O' :
					'6LdqKFcUAAAAAAMrr_86hyyILYeayAptt8lyePgX';
					*/
			$secret = '6LetpkcUAAAAAFqgZjPxS_GIg9AOpcG7qo-jrmv-';

			$url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret . "&response=" . $recaptchaResponse . "&remoteip;=" . $userIp;
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $url);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			$buffer = curl_exec($curl_handle);
			curl_close($curl_handle);
			$bufferObject = json_decode($buffer);			
			if(!$bufferObject->success) 
			{
				$dataForView['postdata'] = $_POST;
				$errors->add('invalid_captcha', '1');
				$dataForView['postdata']['invalid_captcha'] = 1;
				$dataForView['errors'] = $errors;
				return view('auth.register', $dataForView);
			}
			$userCheck = $this->create($_POST);
			$role = Role::where('slug', '=', 'unverified')->first();
			$userCheck->attachRole($role);
			$userCheck->save();
			auth()->login($userCheck, true);
			$success = trans("message.socialRegiterSuccess");
			return redirect('home')->with('success', $success);
		}
		return redirect()->route('register');
	}

	
	public function register_demo(Request $request)
	{
		return redirect()->route('home');
		$user = Auth::user();
		if($user)
		{
			return redirect()->route('home');
		}
		
		$dataForView['postdata'] = array();		
		$dataForView['card_data'] = LendoCards::getCardData();
		
		if (isset($_COOKIE["lendo_ref"])) 
		{			
			$dataForView['postdata']['friend_telegram_id'] = $_COOKIE["lendo_ref"];
		}
		
		if($_POST) 
		{
			if(isset($_POST['termsConditionForLoan']) && $_POST['termsConditionForLoan'] == 1)
			{
				$_POST["loan_amount_requested"] = 50000;
				$_POST["loan_term"] = 12;
				$_POST["security_type"] = "BTC";
			}
			
			if(isset($_POST['termsConditionForCard']) && $_POST['termsConditionForCard'] == 1)
			{
				$_POST["reserve_card"] = "1";
			}
			
			if(isset($_POST['termsConditionForCard']) && $_POST['termsConditionForCard'] == 1)
			{
				$_POST["notUScitizen"] = "1";
				$_POST["confirmNatureAndRisk"] = "1";
			}
			
			if(isset($_POST['first_name']) && isset($_POST['last_name']) )
			{
				$_POST["name"] = $_POST['first_name'] .' '.$_POST['last_name'];
			}
			
			//echo "<pre>";print_r($_POST);die;
			/* capcha server side validation */
			$recaptchaResponse = trim($_POST['g-recaptcha-response']);
			$userIp = CommonHelper::get_client_ip()!='' ? CommonHelper::get_client_ip():\Request::getClientIp(true);		
			$secret = '6LetpkcUAAAAAFqgZjPxS_GIg9AOpcG7qo-jrmv-';
			$url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret . "&response=" . $recaptchaResponse . "&remoteip;=" . $userIp;
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $url);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			$buffer = curl_exec($curl_handle);
			curl_close($curl_handle);
			$bufferObject = json_decode($buffer);			
		
			/* capcha server side validation */
			
			$validator = $this->second_validator($_POST);
			$errors = $validator->errors();
			
			if($validator->fails() || !$bufferObject->success) 
			{
				$dataForView['postdata'] = $_POST;
				$errors->add('isRegsiter', 'Yes');
				if(!$bufferObject->success)
				{
				$dataForView['postdata']['invalid_captcha'] = 1;
				}
				$dataForView['errors'] = $errors;
				return view('registerDemo', $dataForView);
			}
			
		
			$userCheck = $this->second_create($_POST);
			$email_exist_response = User::email_already_exist($_POST['email']);
			
			if($email_exist_response['status'] === false)
		    {
				$role = Role::where('slug', '=', 'unverified')->first();
				$userCheck->attachRole($role);
				$userCheck->save();
			}
			
			if(isset($_COOKIE["lendo_ref"])) 
			{
				setcookie('lendo_ref', $_COOKIE["lendo_ref"], time() - 10000, "/");
			}
			
			auth()->login($userCheck, true);
			$success = trans("message.socialRegiterSuccess");
			return redirect('home')->with('success', $success);
		}
		return view('registerDemo',$dataForView);
		
		
		
	}
	 public function check_captcha($str) {
		
        $word = 'XXXX';
		if (isset($_COOKIE["captcha_word"])) 
		{			
			$word = $_COOKIE["captcha_word"];
		}
		
		
       if (strcmp(strtoupper($str), strtoupper($word)) == 0) {
            return true;
        } else {
            return false;
        }
    }
	
	public function refresh_capcha()
	{
	
	$random_number = substr(number_format(time() * rand(), 0, '', ''), 0, 6);
        // setting up captcha config
        $vals = array(
            'word' => $random_number,
            'img_path' => public_path().'/captcha/',
            'img_url' => url('/captcha'),
            'img_width' => 250,
            'img_height' => 50,
            'expiration' => 7200,
            'colors' => array(
                'background' => array(255, 255, 255),
                'border' => array(255, 255, 255),
                'text' => array(0, 0, 0),
                'grid' => array(255, 255, 255)
            )
        );

		$capcha_data = CaptchaHelper::create_captcha($vals);
		
		$word = $capcha_data['word'];
		$dataForView['image'] = $capcha_data['image'];
		$dataForView['captcha_image_src'] = $capcha_data['src'];
		$dataForView['word'] = $word;
		
		setcookie('captcha_word', $word, time() + (60 * 15), "/"); 
		echo  json_encode($dataForView);
       		
		
	}			
	
	public function airdrop_signup(Request $request)
	{
		$My_IP_Address = CommonHelper::get_client_ip()!='' ? CommonHelper::get_client_ip():\Request::getClientIp(true);
		if(in_array($My_IP_Address,$this->blockIPAddress)){
			return redirect()->route('login');
		}
		
		$user = Auth::user();
		if($user)
		{
			return redirect()->route('home');
		}
		
		
		$random_number = substr(number_format(time() * rand(), 0, '', ''), 0, 6);
        // setting up captcha config
        $vals = array(
            'word' => $random_number,
            'img_path' => public_path().'/captcha/',
            'img_url' => url('/captcha'),
            'img_width' => 250,
            'img_height' => 50,
            'expiration' => 7200,
            'colors' => array(
                'background' => array(255, 255, 255),
                'border' => array(255, 255, 255),
                'text' => array(0, 0, 0),
                'grid' => array(255, 255, 255)
            )
        );

        /* Generate the captcha */
        
		$dataForView['postdata'] = array();		
		$dataForView['card_data'] = LendoCards::getCardData();
		
		if(isset($_COOKIE["lendo_ref"])) 
		{
			$dataForView['postdata']['friend_telegram_id'] = $_COOKIE["lendo_ref"];
		}
		$capcha_data = CaptchaHelper::create_captcha($vals);
		//print_r($capcha_data);die;
		
		$word = $capcha_data['word'];
		$dataForView['image'] = $capcha_data['image'];
		$dataForView['captcha_image_src'] = $capcha_data['src'];
		$dataForView['word'] = $word;
		
		
		setcookie('captcha_word', $word, time() + (60 * 15), "/"); 
		
		$dataForView['capcha_invalid'] = false;
		if($_POST) 
		{            
			//echo "<pre>";print_r($_POST);die;
		
			$validator = $this->airdrop_signup_validator($_POST);
			$errors = $validator->errors();
			$capcha_response = $this->check_captcha($_POST['userCaptcha']);
			
			if($validator->fails() || !$capcha_response) 
			{
				$dataForView['postdata'] = $_POST;
				$errors->add('isRegsiter', 'Yes');
				$dataForView['errors'] = $errors;
				if(!$capcha_response)
				{
					$dataForView['capcha_invalid'] = true;
				}				
				return view('registerA', $dataForView);
			}			
		
			$userCheck = $this->airdrop_create($_POST);
			$email_exist_response = User::email_already_exist($_POST['email']);
			
			if($email_exist_response['status'] === false)
		    {
				$role = Role::where('slug', '=', 'unverified')->first();
				$userCheck->attachRole($role);
				$userCheck->save();
			}
			
			if(isset($_COOKIE["lendo_ref"])) 
			{
				setcookie('lendo_ref', $_COOKIE["lendo_ref"], time() - 10000, "/");
			}
			
			auth()->login($userCheck, true);
			$success = trans("message.socialRegiterSuccess");
			return redirect('home')->with('success', $success);
		}
		//echo '<pre>';
		//print_R($dataForView); exit;
		return view('registerA',$dataForView);
	}
	
	
	public function airdrop_create(array $data)
	{
		$now_date = date('Y-m-d H:i:s');
		$email_exist_response = User::email_already_exist($_POST['email']);
		
		if($email_exist_response['status'] === true)
		{
			$updateLoanCardData = array();	
			
			$updateLoanCardData['tc'] = isset($data['termsConditionRegister']) ? $data['termsConditionRegister'] : 0;
			
			$updateLoanCardData['notUScitizen'] = isset($data['notUScitizen']) ? $data['notUScitizen'] :0;
			
			$updateLoanCardData['read_wps'] = isset($data['confirmNatureAndRisk']) ? $data['confirmNatureAndRisk'] : 0;
			
			// If applied for Loan	
			if(isset($data['loan_amount_requested']) && $data['loan_amount_requested'] > 0)
			{
				$updateLoanCardData['loan_term'] = $data['loan_term'];
				$updateLoanCardData['loan_amount_requested'] = $data['loan_amount_requested'];
				$updateLoanCardData['security_type'] = $data['security_type'];
				$updateLoanCardData['loan_requested_on'] = $now_date;
			}
			
			/// IF apply for Card
			if(isset($data['reserve_card']) && $data['reserve_card'] > 0)
			{
				$updateLoanCardData['reserve_card'] = $data['reserve_card'];
				$updateLoanCardData['card_requested_on'] = $now_date;
			}
			
			DB::table('users')->where('email',$data['email'])->update($updateLoanCardData);
			
			$user = User::where('email',$data['email'])->first();
		
			if($user->status == '0')
			{
				$activation = new Activation();
				$activation->user_id = $user->id;
				$activation->token = str_random(64);
				$activation->ip_address = CommonHelper::get_client_ip()!='' ? CommonHelper::get_client_ip():\Request::getClientIp(true);
				$activation->save();

				// Send activation email notification
				self::sendNewActivationEmail($user, $activation->token);
			}
			else
			{	
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
	
			$record = [
				'message' => 'User Register is updated successfully email: ' . $data['email'],
				'level' => 'INFO',
				'context' => 'Register',
				'extra' => [
                    'load_card_postdata' => json_encode($_POST)
                ],
				'userId' => $user->id
			];
			LoggerHelper::writeDB($record);
		
		}
		else
		{
			
			$role = Role::where('slug', '=', 'unverified')->first();

			//check referrer key and generate new
			referrer_key:
			$referrer_key = $this->getUniqueKey(5);
			$uniqueKeyData = User::select('referrer_key')->where('referrer_key', '=', $referrer_key)->first();
			if (count($uniqueKeyData) > 0) {
				GOTO referrer_key;
			}

			//Check if it reffered by any user
			$default_referrer = Configurations::where('name', 'Default-referrer-user-id')->where('valid_to', '9999-12-31')->where('valid_to', '9999-12-31')->pluck('defined_value');
			$default_referrer = count($default_referrer) ? $default_referrer[0] : 1;
			$referrer_user_id = $default_referrer;
	
			if(isset($_POST['friend_telegram_id']) && !empty($_POST['friend_telegram_id']))
			{
				if (isset($_COOKIE["lendo_ref"])) 
				{
					$refkey = $_COOKIE["lendo_ref"];
				}
				else
				{
					$refkey = $_POST["friend_telegram_id"];
				}
				
				$userRef = User::where('telegram_id', '=', $refkey)->orWhere('referrer_key', '=', $refkey)->first();
				
				if(isset($userRef) && $userRef->id != 0) 
				{
					$referrer_user_id = $userRef->id;	
				}				
			}
		
			$insertLoanCardData = array();
			
			$insertLoanCardData['user_name'] = $this->genrateUniqueUsername($data['email']);
			$insertLoanCardData['email'] = $data['email'];
			$insertLoanCardData['first_name'] = isset($data['first_name']) ? $data['first_name'] : NULL;
			$insertLoanCardData['last_name'] = isset($data['last_name']) ? $data['last_name'] : NULL;
			$insertLoanCardData['password'] = bcrypt($data['password']);
			$insertLoanCardData['referrer_key'] =$referrer_key;
			$insertLoanCardData['referrer_user_id'] = $referrer_user_id;
			$insertLoanCardData['telegram_id'] = isset($data['telegram_id']) ? $data['telegram_id'] : NULL;
			$insertLoanCardData['friend_telegram_id'] = isset($data['friend_telegram_id']) ? $data['friend_telegram_id'] : NULL;
			$insertLoanCardData['registration_ip'] = CommonHelper::get_client_ip()!='' ? CommonHelper::get_client_ip():\Request::getClientIp(true);
			$insertLoanCardData['tc'] = isset($data['termsConditionRegister']) ? $data['termsConditionRegister'] : 0;
			$insertLoanCardData['notUScitizen'] = isset($data['notUScitizen']) ? $data['notUScitizen'] : 0;
			$insertLoanCardData['read_wps'] = isset($data['confirmNatureAndRisk']) ? $data['confirmNatureAndRisk'] : 0;
			
			// If applied for Loan	
			if(isset($data['loan_amount_requested']) && $data['loan_amount_requested'] > 0)
			{
				$insertLoanCardData['loan_term'] = $data['loan_term'];
				$insertLoanCardData['loan_amount_requested'] = $data['loan_amount_requested'];
				$insertLoanCardData['security_type'] = $data['security_type'];
				$insertLoanCardData['loan_requested_on'] = $now_date;
			}
			
			
			/// IF apply for Card
			if(isset($data['reserve_card']) && $data['reserve_card'] > 0)
			{
				$insertLoanCardData['reserve_card'] = $data['reserve_card'];
				$insertLoanCardData['card_requested_on'] = $now_date;
			}
			
			$user = User::create($insertLoanCardData);
			
			if($user->id > 0){
				ParentChild::restructure_child($user->id);
			}

			$user->attachRole($role);

			//
			$activation = new Activation();
			$activation->user_id = $user->id;
			$activation->token = str_random(64);
			$activation->ip_address = CommonHelper::get_client_ip()!='' ? CommonHelper::get_client_ip():\Request::getClientIp(true);
			$activation->save();

			/* update referral count */
			DB::statement("UPDATE users SET referrer_count=referrer_count+1 WHERE id=" . $referrer_user_id);

			// Send activation email notification
			self::sendNewActivationEmail($user, $activation->token);

			//
			$record = [
				'message' => 'User Register successfully email: ' . $data['email'],
				'level' => 'INFO',
				'context' => 'Register',
				'userId' => $user->id,
				'extra' => [
                    'load_card_postdata' => json_encode($_POST)
                ]
			];
			LoggerHelper::writeDB($record);
		   }
		//
		return $user;
	}
	
	public function second_create(array $data)
	{
		$now_date = date('Y-m-d H:i:s');
		$email_exist_response = User::email_already_exist($_POST['email']);
		
		if($email_exist_response['status'] === true)
		{
			$updateLoanCardData = array();	
			
			$updateLoanCardData['tc'] = isset($data['termsConditionRegister']) ? $data['termsConditionRegister'] : 0;
			
			$updateLoanCardData['notUScitizen'] = isset($data['notUScitizen']) ? $data['notUScitizen'] :0;
			
			$updateLoanCardData['read_wps'] = isset($data['confirmNatureAndRisk']) ? $data['confirmNatureAndRisk'] : 0;
			
			// If applied for Loan	
			if(isset($data['loan_amount_requested']) && $data['loan_amount_requested'] > 0)
			{
				$updateLoanCardData['loan_term'] = $data['loan_term'];
				$updateLoanCardData['loan_amount_requested'] = $data['loan_amount_requested'];
				$updateLoanCardData['security_type'] = $data['security_type'];
				$updateLoanCardData['loan_requested_on'] = $now_date;
			}
			
			/// IF apply for Card
			if(isset($data['reserve_card']) && $data['reserve_card'] > 0)
			{
				$updateLoanCardData['reserve_card'] = $data['reserve_card'];
				$updateLoanCardData['card_requested_on'] = $now_date;
			}
			
			DB::table('users')->where('email',$data['email'])->update($updateLoanCardData);
			
			$user = User::where('email',$data['email'])->first();
		
			if($user->status == '0')
			{
				$activation = new Activation();
				$activation->user_id = $user->id;
				$activation->token = str_random(64);
				$activation->ip_address = CommonHelper::get_client_ip()!='' ? CommonHelper::get_client_ip():\Request::getClientIp(true);
				$activation->save();

				// Send activation email notification
				self::sendNewActivationEmail($user, $activation->token);
			}
			else
			{	

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
	
			$record = [
				'message' => 'User Register is updated successfully email: ' . $data['email'],
				'level' => 'INFO',
				'context' => 'Register',
				'extra' => [
                    'load_card_postdata' => json_encode($_POST)
                ],
				'userId' => $user->id
			];
			LoggerHelper::writeDB($record);
		
		}
		else
		{
			
			$role = Role::where('slug', '=', 'unverified')->first();

			//check referrer key and generate new
			referrer_key:
			$referrer_key = $this->getUniqueKey(5);
			$uniqueKeyData = User::select('referrer_key')->where('referrer_key', '=', $referrer_key)->first();
			if (count($uniqueKeyData) > 0) {
				GOTO referrer_key;
			}

			
			$referrer_user_id = 243;
		
			$insertLoanCardData = array();
			
			$insertLoanCardData['user_name'] = $this->genrateUniqueUsername($data['email']);
			$insertLoanCardData['email'] = $data['email'];
			$insertLoanCardData['first_name'] = isset($data['first_name']) ? $data['first_name'] : NULL;
			$insertLoanCardData['last_name'] = isset($data['last_name']) ? $data['last_name'] : NULL;
			$insertLoanCardData['password'] = bcrypt($data['password']);
			$insertLoanCardData['referrer_key'] =$referrer_key;
			$insertLoanCardData['referrer_user_id'] = $referrer_user_id;
			$insertLoanCardData['telegram_id'] = isset($data['telegram_id']) ? $data['telegram_id'] : NULL;
			$insertLoanCardData['friend_telegram_id'] = isset($data['friend_telegram_id']) ? $data['friend_telegram_id'] : NULL;
			$insertLoanCardData['registration_ip'] = CommonHelper::get_client_ip()!='' ? CommonHelper::get_client_ip():\Request::getClientIp(true);
			$insertLoanCardData['tc'] = isset($data['termsConditionRegister']) ? $data['termsConditionRegister'] : 0;
			$insertLoanCardData['notUScitizen'] = isset($data['notUScitizen']) ? $data['notUScitizen'] : 0;
			$insertLoanCardData['read_wps'] = isset($data['confirmNatureAndRisk']) ? $data['confirmNatureAndRisk'] : 0;
			
			// If applied for Loan	
			if(isset($data['loan_amount_requested']) && $data['loan_amount_requested'] > 0)
			{
				$insertLoanCardData['loan_term'] = $data['loan_term'];
				$insertLoanCardData['loan_amount_requested'] = $data['loan_amount_requested'];
				$insertLoanCardData['security_type'] = $data['security_type'];
				$insertLoanCardData['loan_requested_on'] = $now_date;
			}
			
			/// IF apply for Card
			if(isset($data['reserve_card']) && $data['reserve_card'] > 0)
			{
				$insertLoanCardData['reserve_card'] = $data['reserve_card'];
				$insertLoanCardData['card_requested_on'] = $now_date;
			}
			
			$user = User::create($insertLoanCardData);
			 
			$user->attachRole($role);

			//
			$activation = new Activation();
			$activation->user_id = $user->id;
			$activation->token = str_random(64);
			$activation->ip_address = CommonHelper::get_client_ip()!='' ? CommonHelper::get_client_ip():\Request::getClientIp(true);
			$activation->save();

			/* update referral count */
			DB::statement("UPDATE users SET referrer_count=referrer_count+1 WHERE id=" . $referrer_user_id);

			// Send activation email notification
			self::sendNewActivationEmail($user, $activation->token);

			//
			$record = [
				'message' => 'User Register successfully email: ' . $data['email'],
				'level' => 'INFO',
				'context' => 'Register',
				'userId' => $user->id,
				'extra' => [
                    'load_card_postdata' => json_encode($_POST)
                ]
			];
			LoggerHelper::writeDB($record);
		   }
		//
		return $user;
	}
	
	
	protected function airdrop_signup_validator(array $data)
	{
		$user = User::where('email', '=', $_POST['email'])->first();
		$email_exist_response = User::email_already_exist($_POST['email']);
		
		$amount_loan =  $_POST['loan_amount_requested'];
		$_POST['loan_amount_requested'] = str_replace(",","",$amount_loan);
		$data['loan_amount_requested'] = str_replace(",","",$amount_loan);
		
		
		if($email_exist_response['status'] == true && $user->reserve_card == null)
		{
			return Validator::make($data, [
				'email' => 'required|email|max:255',
				'termsConditionRegister' => 'required',
				'notUScitizen' => 'required',
				'confirmNatureAndRisk' => 'required',
				'loan_term' => 'required',
				'loan_amount_requested' => 'required|numeric|min:1000|max:50000',
				'security_type' => 'required',
				'reserve_card' => 'required',
				'telegram_id' => 'unique:users',
			], [				
				'email.required' => trans('auth.emailRequired'),
				'email.email' => trans('auth.emailInvalid'),
				'email.max' => trans('auth.emailMax'),
				'termsConditionRegister.required' => trans('auth.termsConditionRegister'),
				'notUScitizen.required' => trans('auth.confirNotUSCitizen'),
				'confirmNatureAndRisk.required' => trans('auth.confirNatureRisk'),
				'loan_term' => trans('auth.loan_term'),
				'loan_amount_requested' => trans('auth.loan_amount_requested'),
				'security_type' => trans('auth.security_type'),
				'reserve_card' => trans('auth.reserve_card'),
				'telegram_id' => trans('auth.telegramUnique'),
			]);
		}
		
		return Validator::make($data, [
		    'first_name' => 'required|regex:/^[a-zA-Z-,]+(\s{0,1}[a-zA-Z-, ])*$/',
			'last_name' => 'required|regex:/^[a-zA-Z-,]+(\s{0,1}[a-zA-Z-, ])*$/',
			'email' => 'required|email|max:255|unique:users',
			'password' => 'required|min:6|max:20',
			'confirm_password' => 'min:6|required_with:password|same:password|required',
			'termsConditionRegister' => 'required',
			'notUScitizen' => 'required',
			'confirmNatureAndRisk' => 'required',
			'loan_term' => 'required',
			'loan_amount_requested' => 'required|numeric|min:1000|max:50000',
			'security_type' => 'required',
			'reserve_card' => 'required',
			'telegram_id' => 'unique:users',
		], [
		    'first_name.required' => trans('auth.fNameRequired'),
			'first_name.regex' => trans('auth.InvalidName'),
			'last_name.required' => trans('auth.lNameRequired'),
			'last_name.regex' => trans('auth.InvalidName'),
			'email.required' => trans('auth.emailRequired'),
			'email.email' => trans('auth.emailInvalid'),
			'email.max' => trans('auth.emailMax'),
			'email.unique' => trans('auth.emailUnique'),
			'password.required' => trans('auth.passwordRequired'),
			'password.min' => trans('auth.PasswordMin'),
			'password.max' => trans('auth.PasswordMax'),
			'termsConditionRegister.required' => trans('auth.termsConditionRegister'),
			'notUScitizen.required' => trans('auth.confirNotUSCitizen'),
			'confirmNatureAndRisk.required' => trans('auth.confirNatureRisk'),
			'confirm_password.same' => trans('auth.PasswordConfirmed'),
			'loan_term' => trans('auth.loan_term'),
			'loan_amount_requested' => trans('auth.loan_amount_requested'),
			'security_type' => trans('auth.security_type'),
			'reserve_card' => trans('auth.reserve_card'),
			'telegram_id' => trans('auth.telegramUnique'),
		]);
		
	}
	
	
	protected function second_validator(array $data)
	{
		$user = User::where('email', '=', $_POST['email'])->first();
		$email_exist_response = User::email_already_exist($_POST['email']);
		
		$amount_loan =  $_POST['loan_amount_requested'];
		$_POST['loan_amount_requested'] = str_replace(",","",$amount_loan);
		$data['loan_amount_requested'] = str_replace(",","",$amount_loan);
		
		
		if($email_exist_response['status'] == true && $user->reserve_card == null)
		{
			return Validator::make($data, [
				'email' => 'required|email|max:255',
				'termsConditionRegister' => 'required',
				'notUScitizen' => 'required',
				'confirmNatureAndRisk' => 'required',
				'loan_term' => 'required',
				'loan_amount_requested' => 'required|numeric|min:1000|max:50000',
				'security_type' => 'required',
				'reserve_card' => 'required',
				'telegram_id' => 'unique:users',
			], [
				
				'email.required' => trans('auth.emailRequired'),
				'email.email' => trans('auth.emailInvalid'),
				'email.max' => trans('auth.emailMax'),
				'termsConditionRegister.required' => trans('auth.termsConditionRegister'),
				'notUScitizen.required' => trans('auth.confirNotUSCitizen'),
				'confirmNatureAndRisk.required' => trans('auth.confirNatureRisk'),
				'loan_term' => trans('auth.loan_term'),
				'loan_amount_requested' => trans('auth.loan_amount_requested'),
				'security_type' => trans('auth.security_type'),
				'reserve_card' => trans('auth.reserve_card'),
				'telegram_id' => trans('auth.telegramUnique'),
			]);
		}
		
		return Validator::make($data, [
		    'first_name' => 'required|regex:/^[a-zA-Z-,]+(\s{0,1}[a-zA-Z-, ])*$/',
			'last_name' => 'required|regex:/^[a-zA-Z-,]+(\s{0,1}[a-zA-Z-, ])*$/',
			'email' => 'required|email|max:255|unique:users',
			'password' => 'required|min:6|max:20',
			'confirm_password' => 'min:6|required_with:password|same:password|required',
			'termsConditionRegister' => 'required',
			'notUScitizen' => 'required',
			'confirmNatureAndRisk' => 'required',
			'loan_term' => 'required',
			'loan_amount_requested' => 'required|numeric|min:1000|max:50000',
			'security_type' => 'required',
			'reserve_card' => 'required',
			'telegram_id' => 'unique:users',
		], [
		    'first_name' => trans('auth.fNameRequired'),
			'first_name.regex' => trans('auth.InvalidName'),
			'last_name' => trans('auth.lNameRequired'),
			'last_name.regex' => trans('auth.InvalidName'),
			'email.required' => trans('auth.emailRequired'),
			'email.email' => trans('auth.emailInvalid'),
			'email.max' => trans('auth.emailMax'),
			'email.unique' => trans('auth.emailUnique'),
			'password.required' => trans('auth.passwordRequired'),
			'password.min' => trans('auth.PasswordMin'),
			'password.max' => trans('auth.PasswordMax'),
			'termsConditionRegister.required' => trans('auth.termsConditionRegister'),
			'notUScitizen.required' => trans('auth.confirNotUSCitizen'),
			'confirmNatureAndRisk.required' => trans('auth.confirNatureRisk'),
			'confirm_password.same' => trans('auth.PasswordConfirmed'),
			'loan_term' => trans('auth.loan_term'),
			'loan_amount_requested' => trans('auth.loan_amount_requested'),
			'security_type' => trans('auth.security_type'),
			'reserve_card' => trans('auth.reserve_card'),
			'telegram_id' => trans('auth.telegramUnique'),
		]);
		
	}
	
	public function check_email_exist()
	{
		
		error_reporting(0);
		 $email = isset($_GET['email']) ? $_GET['email'] : '';
		$response = User::email_already_exist($email);
		
		
		echo json_encode($response);
		exit;
		
	}
	public function check_telegram_id_exist()
	{
		error_reporting(0);
		$response['status'] = "NOK";
		$telegram_id = isset($_GET['telegram_id']) ? $_GET['telegram_id'] : '';
		//$data = User::where('telegram_id', '=', $_GET['telegram_id'])->first();
		$data = User::where('telegram_id', '=', $_GET['telegram_id'])->orWhere('referrer_key', '=', $_GET['telegram_id'])->first();
		
		if(!empty($data))
		{
		 $response['status'] = "OK";
		}
		echo json_encode($response);
		exit;
		
	}
	
	
	public function give_bonus($user_id,$value,$description,$type)
	{
		
		$user = User::where('id', '=', $user_id)->first();
		// get user kyc status
		//$kyc_status = FileAttachments::fetch_kyc_status($user_id);
		
		$status = 2;	
		$show_to_user = 0;

		/*if($kyc_status == 1)
		{
		$status = 1;
		$show_to_user = 1; 		
		}*/
		
		$ledger = 'ELT';
        $transaction_id = uniqid();
		$phaseId = NULL;
		$description = $description .' Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
        $Transaction = Transactions::createTransaction($user_id, $ledger, $value, $description, $status, $transaction_id, $phaseId, NULL, $type,NULL,$type_name='ico-wallet',0, $show_to_user);
		
		if($status == 1)
		{
		$user->addValue('ELT_balance', $value);
		$userUpdate = $user->save();
		}
		
		
	}
	
	
	/**
	 * Check for save referral telegram key in cookie
	 *
	 * @param  array $data
	 * @return User
	 */
	public function userTelegramReferral($telegramkey)
	{
		if (isset($telegramkey)) {
			// Airdrop signup has been disabled now and sending back to old signup form
			$user1 = User::where('telegram_id', '=', $telegramkey)->first();
			if (isset($user1->referrer_key)) {
				setcookie('lendo_ref', $user1->referrer_key, time() + (365 * 86400 - 5), "/");// 1 day
			}
			return redirect()->route('signup');

		} else {
			if (!is_null($telegramkey)) {
				$user = User::where('telegram_id', '=', $telegramkey)->first();
				if (isset($user)) {
					setcookie('telegramkey', $telegramkey, time() + (365 * 86400 - 5), "/");
				} else {
					$user = User::where('user_name', '=', $telegramkey)->first();
					if (isset($user)) {
						setcookie('telegramkey', $telegramkey, time() + (365 * 86400 - 5), "/");
					} else {
						return redirect()->route('login')->with('error', __('layouts.invalid_referral_key'));
					}
				}
			} else {
				return redirect()->route('login')->with('error', __('layouts.invalid_referral_key'));
			}
		}
		return redirect()->route('airdrop');
	}

	public function airdrop()
	{
		$dataForView = array();
		$dataForView['referral_exist'] = 0;
		$dataForView['telegram_exist'] = 0;
		$dataForView['postdata'] = $_POST;
		$dataForView['referralUser'] = array();
		$total_telegram_members = User::total_telegram_singup();
		if ($total_telegram_members > 20000) {

		}
		if (isset($_COOKIE["telegramkey"])) {
			$telegramkey = $_COOKIE["telegramkey"];
			if (!is_null($telegramkey)) {
				$user1 = User::where('telegram_id', '=', $telegramkey)->first();

				if (isset($user1->referrer_key)) {
					setcookie('lendo_ref', $user1->referrer_key, time() + (365 * 86400 - 5), "/");// 1 day
				}

				return redirect()->route('signup');


			} else {
				return redirect()->route('login')->with('error', __('layouts.invalid_referral_key'));
			}
		}


		return redirect()->route('login')->with('error', __('layouts.invalid_referral_key'));

		if ($_POST) {
			$validator = $this->airdrop_validator($_POST);
			$errors = $validator->errors();
			if ($validator->fails()) {
				$dataForView['errors'] = $errors;
				return view('airdrop', $dataForView);
			} else {

				$recaptchaResponse = trim($_POST['g-recaptcha-response']);
				$userIp = CommonHelper::get_client_ip()!='' ? CommonHelper::get_client_ip():\Request::getClientIp(true);
                $secret = '6LetpkcUAAAAAFqgZjPxS_GIg9AOpcG7qo-jrmv-';

				$url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret . "&response=" . $recaptchaResponse . "&remoteip;=" . $userIp;

				$curl_handle = curl_init();
				curl_setopt($curl_handle, CURLOPT_URL, $url);
				curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
				$buffer = curl_exec($curl_handle);
				curl_close($curl_handle);

				if (empty($buffer)) {
					$dataForView['invalid_captcha'] = 1;
					return view('airdrop', $dataForView);
				} else {
					$captchaStatus = json_decode($buffer, true);
					if ($captchaStatus['success']) {

						if (isset($_POST['ref_email'])) {
							$ref_email = $_POST['ref_email'];
						} else if (isset($_POST['hidden_ref_email'])) {
							$ref_email = $_POST['hidden_ref_email'];
						} else {
							$ref_email = NULL;
						}

						if (isset($_POST['referral_telegram_id'])) {
							$referral_telegram_id = $_POST['referral_telegram_id'];
						} else if (isset($_POST['hidden_referral_telegram_id'])) {
							$referral_telegram_id = $_POST['hidden_referral_telegram_id'];
						} else {
							$referral_telegram_id = NULL;
						}

						$erc20_wallet_address = isset($_POST['erc20_wallet_address']) ? $_POST['erc20_wallet_address'] : NULL;

						/* Fetch default referral set from db */
						$default_referrer = Configurations::where('name', 'Default-referrer-user-id')->where('valid_to', '9999-12-31')->pluck('defined_value');

						if (isset($ref_email) && $ref_email != NULL) {
							$userRef = User::where('email', '=', $ref_email)->first();
							if (isset($userRef) && $userRef->id != 0) {
								$referrer_user_id = $userRef->id;
							} else {
								$dataForView['referral_exist'] = 1;
							}
						} else {
							$referrer_user_id = $default_referrer[0];
						}

						$_POST['telegram_id'] = str_replace(' ', '', $_POST['telegram_id']);
						$_POST['telegram_id'] = str_replace('https://', '', $_POST['telegram_id']);
						$_POST['telegram_id'] = str_replace('/', '-', $_POST['telegram_id']);
						$_POST['telegram_id'] = str_replace(':', '-', $_POST['telegram_id']);

						if (isset($_POST["telegram_id"])) {
							$telegram_id = $_POST["telegram_id"];
							$userTelegramRow = User::where('telegram_id', '=', $telegram_id)->first();
							if (isset($userTelegramRow) && $userTelegramRow->telegram_id != NULL) {
								$dataForView['telegram_exist'] = 1;
							}
						}

						if ($dataForView['telegram_exist'] == 1 || $dataForView['referral_exist'] == 1) {
							return view('airdrop', $dataForView);
						} else {
							/* check referrer key and generate new */
							referrer_key:
							$referrer_key = $this->getUniqueKey(5);
							$uniqueKeyData = User::select('referrer_key')->where('referrer_key', '=', $referrer_key)->first();
							if (count($uniqueKeyData) > 0) {
								GOTO referrer_key;
							}

							$dataArray = [
								'user_name' => $this->genrateUniqueUsername($_POST['email']),
								'email' => $_POST['email'],
								'first_name' => $_POST['first_name'],
								'last_name' => $_POST['last_name'],
								'password' => bcrypt($_POST['password']),
								'referrer_key' => $referrer_key,
								'ELT_balance' => 0,
								'referrer_user_id' => $referrer_user_id,
								'registration_ip' => CommonHelper::get_client_ip()!='' ? CommonHelper::get_client_ip():\Request::getClientIp(true),
								'telegram_id' => $_POST['telegram_id'],
								'friend_telegram_id' => $referral_telegram_id,
								'telegram_referral_email' => $ref_email,
								'ERC20_wallet_address' => $erc20_wallet_address,
							];

							$userCheck = User::create($dataArray);
							$role = Role::where('slug', '=', 'unverified')->first();
							$userCheck->attachRole($role);
							$activation = new Activation();
							$activation->user_id = $userCheck->id;
							$activation->token = str_random(64);
							//$activation->ip_address = \Request::getClientIp(true);
							$activation->ip_address = CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true);
							
							$activation->save();

							/* Send activation email notification */
							self::sendAirdropActivationEmail($userCheck, $activation->token);

							$role = Role::where('slug', '=', 'unverified')->first();
							$userCheck = User::find($userCheck->id);

							$userCheck->attachRole($role);

							$userCheck->save();

							$loggerRecord = [
								'userId' => $userCheck->id,
								'message' => "Member signup from telegram group with ID:" . $_POST['telegram_id'],
								'level' => 'INFO',
								'context' => 'SignupTelegram',
							];
							LoggerHelper::writeDB($loggerRecord);

							auth()->login($userCheck, true);

							$success = trans("message.socialRegiterSuccess");

							if (isset($_COOKIE["telegramkey"])) {
								setcookie('telegramkey', $_COOKIE["telegramkey"], time() - 1000, "/");
							}
							return redirect('home')->with('success', $success);
						}
					} else {
						$dataForView['invalid_captcha'] = 1;
						return view('airdrop', $dataForView);
					}
				}
			}
		}
		return view('airdrop', $dataForView);
	}
}
