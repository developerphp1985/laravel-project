<?php

namespace App\Http\Controllers;
use App\Models\Logs;
use App\Models\User;
use App\Models\Phases;
use App\Models\Country;
use App\Models\Withdrawal;
use App\Models\Blockchain;
use App\Models\ParentChild;
use App\Models\Transactions;
use App\Models\ChangeRequests;
use App\Models\Configurations;
use App\Models\FileAttachments;
use App\Helpers\MCrypt;
use App\Helpers\PhaseHelper;
use App\Helpers\CommonHelper;
use App\Helpers\LoggerHelper;
use App\Notifications\SendChangeBTCWalletAddress;
use App\Notifications\SendChangeCryptoWalletAddress;
use App\Notifications\SendWithDrawalRequest;
use App\Notifications\SendWithDrawalConfirmation;
use App\Notifications\SendChangeEmailRequest;
use App\Notifications\SendChangeETHWalletAddress;
use App\Notifications\SendChangeIBanNumber;
use App\Notifications\SendChangeNotification;
use App\Notifications\AuthenticateUserViaOTP;
use App\Rules\BTCValidation;
use App\Rules\ETHValidation;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use PragmaRX\Google2FA\Google2FA;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['confirmChange']]);
    }

    /**
     *
     */
    public function SendChangeNotification(User $user, $emailData)
    {
        $user->notify(new SendChangeNotification($emailData));
    }

    /**
     *
     */
    public function sendChangeEmailRequest(User $user, $emailData)
    {
        $user->notify(new SendChangeEmailRequest($emailData));
    }

    /**
     *
     */
    public function sendChangeBTCWalletAddress(User $user, $emailData)
    {
        $user->notify(new SendChangeBTCWalletAddress($emailData));
    }
	
	/**
     *
     */
    public function SendChangeCryptoWalletAddress(User $user, $emailData)
    {
        $user->notify(new SendChangeCryptoWalletAddress($emailData));
    }
	
	public function SendWithDrawalRequest(User $user, $emailData)
	{
		$user->notify(new SendWithDrawalRequest($emailData));
	}
	
	public function SendWithDrawalConfirmation(User $user, $emailData)
	{
		$user->notify(new SendWithDrawalConfirmation($emailData));
	}
	

    /**
     *
     */
    public function sendChangeETHWalletAddress(User $user, $emailData)
    {
        $user->notify(new SendChangeETHWalletAddress($emailData));
    }

    /**
     *
     */
    public function SendChangeIBanNumber(User $user, $emailData)
    {
        $user->notify(new SendChangeIBanNumber($emailData));
    }
	
	/**
     *
     */
	public function sendEmailToUserWithOTP(User $user, $OTP)
    {
        $user->notify(new AuthenticateUserViaOTP($OTP));
    }
	

    /**
     * @param $view_name
     * @param $kyc_status
     * @param $phases
     * @param $Conversion_EUR_ELT
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    private function showView($view_name, $kyc_status, $phases, $Conversion_EUR_ELT)
    {
        return view($view_name,
            [
                'kyc_status' => $kyc_status,
                'phases' => $phases,
                'Conversion_EUR_ELT' => $Conversion_EUR_ELT
            ]
        );
    }

    /**
     * @param $view_name
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showViewDependingOnUser($view_name)
    {
        $user = Auth::user();
        error_reporting(0);
        $kyc_status = FileAttachments::fetch_kyc_status(Auth::user()->id);
        $phases = PhaseHelper::getPhases();
        $Conversion_EUR_ELT = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Conversion-EUR-ELT']])->get();
        return $user->hasRole('user') ? $this->showView($view_name, $kyc_status, $phases, $Conversion_EUR_ELT) : $this->showSpecialUserView($user);
    }

    /**
     * @param $user
     * @return \Illuminate\Http\RedirectResponse
     */
    private function showSpecialUserView($user): \Illuminate\Http\RedirectResponse
    {
        if ($user->hasRole('unverified')) {
            return redirect()->route('activate');
        } else if ($user->hasRole('admin')) {
            return redirect()->to('/');
        } else {
            return redirect()->to('/');
        }
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
		
		
        return $this->showViewDependingOnUser('home');
	}

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function homeDashboard()
    {
        return $this->showViewDependingOnUser('homeDashboard');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function icowallet()
    {
		$dataForView = array();		
		error_reporting(0);		
		$user = User::find(Auth::user()->id);
		$dataForView['userInfo'] = $user;
		$dataForView['kycStatus'] = FileAttachments::getKYCStatus($user->id);
		$dataForView['kyc_status'] = $dataForView['kycStatus'];
        
		$all_referrals_list = User::where('referrer_user_id',$user->id)->where('status', 1)->orderBy('created_at', 'desc')->get();
		
		$dataForView['all_referrals_list'] = $all_referrals_list;
		$level_detail = Transactions::get_user_elt_worth_in_euro($user->id);
		$euro_worth = $level_detail[0]->euro_worth_total;
		$dataForView['euro_worth'] = $euro_worth;
		
		$current_level_array = Transactions::get_current_user_level($user->id);
		$dataForView['current_level'] = $current_level_array['current_level'];
		$dataForView['euro_worth_for_next_level'] = $current_level_array['euro_worth_for_next_level'];
		
		$dataForView['levelwise_data'] = Transactions::get_levelwise_user_elt_euro_worth($user->id);
		
		$currencyList["ELT"] = array("code"=>"ELT","name"=>"Ethereum Lendo Token","wallet_balance"=>Auth::user()->ELT_balance, "wallet_address"=>Auth::user()->ELT_wallet_address);

		$currencyList["BTC"] = array("code"=>"BTC","name"=>"Bitcoin","wallet_balance"=>Auth::user()->BTC_balance, "wallet_address"=>Auth::user()->BTC_wallet_address);

		$currencyList["EUR"] = array("code"=>"EUR","name"=>"Euro","wallet_balance"=>Auth::user()->EUR_balance, "wallet_address"=>Auth::user()->IBAN_number);

		$currencyList["ETH"] = array("code"=>"ETH","name"=>"Ethereum","wallet_balance"=>Auth::user()->ETH_balance, "wallet_address"=>Auth::user()->ETH_wallet_address);

		$currencyList["BCH"] = array("code"=>"BCH","name"=>"Bitcoin Cash","wallet_balance"=>Auth::user()->BCH_balance, "wallet_address"=>Auth::user()->BCH_wallet_address);

		$currencyList["LTC"] = array("code"=>"LTC","name"=>"Litcoin Cash","wallet_balance"=>Auth::user()->LTC_balance, "wallet_address"=>Auth::user()->LTC_wallet_address);

		$currencyList["XRP"] = array("code"=>"XRP","name"=>"Ripple","wallet_balance"=>Auth::user()->XRP_balance, "wallet_address"=>Auth::user()->XRP_wallet_address);

		$currencyList["DASH"] = array("code"=>"DASH","name"=>"Dash","wallet_balance"=>Auth::user()->DASH_balance, "wallet_address"=>Auth::user()->DASH_wallet_address);
		
		$withdraw_settings = Configurations::where([['valid_to', '9999-12-31']])->where('name','LIKE',"Withdrawal-Setting-%")->get();
		
		$withdraw_data = array();
		if (!$withdraw_settings->isEmpty()) {
			foreach ($withdraw_settings as $key => $rates_minimume_values) {
				$withdraw_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
				$withdraw_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
			}
		}
		
		$withdraw_data['Withdrawal-Setting-BTC'][] = Transactions::get_wallet_change_status($user->id,1);
		$withdraw_data['Withdrawal-Setting-ETH'][] = Transactions::get_wallet_change_status($user->id,2);
		$withdraw_data['Withdrawal-Setting-LTC'][] = Transactions::get_wallet_change_status($user->id,7);
		$withdraw_data['Withdrawal-Setting-BCH'][] = Transactions::get_wallet_change_status($user->id,8);
		$withdraw_data['Withdrawal-Setting-XRP'][] = Transactions::get_wallet_change_status($user->id,9);
		$withdraw_data['Withdrawal-Setting-DASH'][] = Transactions::get_wallet_change_status($user->id,10);
		$withdraw_data['Withdrawal-Setting-ELT'][] = Transactions::get_wallet_change_status($user->id,11);
		$withdraw_data['Withdrawal-Setting-EUR'][] = $withdraw_data['Withdrawal-Setting-EUR'][0];
		
		$dataForView['withdraw_settings'] = $withdraw_data;
		
		list($all_transactions_list,
            $btc_transactions_list,
            $eth_transactions_list,
            $elt_transactions_list,
            $eur_transactions_list,
            $bch_transactions_list,
            $ltc_transactions_list,
            $xrp_transactions_list,
            $dash_transactions_list) = $this->get_transactions($user->id);
		
		$dataForView['all_transactions_list'] = $all_transactions_list;
		$dataForView['btc_transactions_list'] = $btc_transactions_list;
		$dataForView['eth_transactions_list'] = $eth_transactions_list;
		$dataForView['elt_transactions_list'] = $elt_transactions_list;
		$dataForView['eur_transactions_list'] = $eur_transactions_list;
		$dataForView['bch_transactions_list'] = $bch_transactions_list;
		$dataForView['ltc_transactions_list'] = $ltc_transactions_list;
		$dataForView['xrp_transactions_list'] = $xrp_transactions_list;
		$dataForView['dash_transactions_list'] = $dash_transactions_list;
		$dataForView["currencyList"] = $currencyList;
		//echo "<pre>";print_r($dataForView);die;
		return view('icowallet', $dataForView);
        //return $this->showViewDependingOnUser('icowallet');
    }

    /**
     * Get transactions for user
     * @return array
     */
    private function get_transactions($user_id): array
    {
        return array(
            $this->get_currency_transactions($user_id),
            $this->get_currency_transactions($user_id, 'BTC'),
            $this->get_currency_transactions($user_id, 'ETH'),
            $this->get_currency_transactions($user_id, 'ELT'),
            $this->get_currency_transactions($user_id, 'EUR'),
            $this->get_currency_transactions($user_id, 'BCH'),
            $this->get_currency_transactions($user_id, 'LTC'),
            $this->get_currency_transactions($user_id, 'XRP'),
            $this->get_currency_transactions($user_id, 'DASH')
        );
    }

    /**
     * @param $user_id
     * @param $currency
     * @return mixed
     */
    private function get_currency_transactions($user_id, $currency = null)
    {
        if ($currency == null) {
            return Transactions::where([['user_id', $user_id], ['show_to_user', 1]])
                ->orderBy('created_at', 'DESC')
                ->paginate(50)
                ->withPath('/public/transaction-load-date-ajax/all');
        } else {
            return Transactions::where([['user_id', $user_id], ['ledger', $currency], ['show_to_user', 1]])
                ->orderBy('created_at', 'DESC')
                ->paginate(50)
                ->withPath('/public/transaction-load-date-ajax/' . $currency);
        }
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function transactions()
    {
        $user = Auth::user();
        $kyc_status = FileAttachments::fetch_kyc_status($user->id);
        if ($user->hasRole('user')) 
		{
            list(
                $all_transactions_list,
                $btc_transactions_list,
                $eth_transactions_list,
                $elt_transactions_list,
                $eur_transactions_list,
                $bch_transactions_list,
                $ltc_transactions_list,
                $xrp_transactions_list,
                $dash_transactions_list) = $this->get_transactions($user->id);
				return view('transactions', [
                'all_transactions_list' => $all_transactions_list,
                'btc_transactions_list' => $btc_transactions_list,
                'eth_transactions_list' => $eth_transactions_list,
                'elt_transactions_list' => $elt_transactions_list,
                'eur_transactions_list' => $eur_transactions_list,
                'bch_transactions_list' => $bch_transactions_list,
                'ltc_transactions_list' => $ltc_transactions_list,
                'xrp_transactions_list' => $xrp_transactions_list,
                'dash_transactions_list' => $dash_transactions_list,
                'kyc_status' => $kyc_status,
            ]);
        } else return $this->showSpecialUserView($user);
    }

	
    /**
     * Ajax callback for tabs in the transaction list
     */
    public function transactionLoadDateAjax($data, Request $request)
    {
        $user = Auth::user();
        $currency = ($data == 'all') ? "All" : $data;
        $table_id = "table" . $currency;
        if ($user->hasRole('user') && isset($data) && !empty($data)) {
            $this->jsonEncodeTransactions($data == 'all' ? $this->get_currency_transactions($user->id) : $this->get_currency_transactions($user->id, $data), $table_id);
        } else if ($user->hasRole('unverified')) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'unverified user'
            ));
        } else if ($user->hasRole('admin')) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'admin user'
            ));
        } else {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'something went wrong'
            ));
        }
    }
	
    public function getChangeRequestStatus($user_id, $type)
	{
		$status = '1';
		$data = ChangeRequests::where([['user_id', $user_id], ['is_delete', 0], ['type', $type]])->first();
		if(!empty($data))
		{
			$status = '0';
		}		
		return $status;
	}
	
    /**
     *Show the user Preferences
     */
    public function preferences(Request $request)
    {
		
		error_reporting(0);
        $country_without_postal = CommonHelper::countries_without_postal();
        $all_languages = User::get_languages();
        $user = User::find(Auth::user()->id);
		$kyc_status = FileAttachments::getKYCStatus($user->id);		
		$btc_change_status = $this->getChangeRequestStatus($user->id, 1);
		$eth_change_status = $this->getChangeRequestStatus($user->id, 2);
		$email_status = $this->getChangeRequestStatus($user->id, 3);
		$ltc_change_status = $this->getChangeRequestStatus($user->id, 7);
		$bch_change_status = $this->getChangeRequestStatus($user->id, 8);
		$xrp_change_status = $this->getChangeRequestStatus($user->id, 9);
		$dash_change_status = $this->getChangeRequestStatus($user->id, 10);
		$elt_change_status = $this->getChangeRequestStatus($user->id, 11);
		$kyc_dir_path = asset("/kyc") . "/";
        if ($user->hasRole('user')) 
		{
			/* Contact Details */ 
            if($request->get('update_contact'))
			{
                $validator = Validator::make($request->all(), [
                    'first_name' => 'required|regex:/^[a-zA-Z-,]+(\s{0,1}[a-zA-Z-, ])*$/',
                    'last_name' => 'required|regex:/^[a-zA-Z-,]+(\s{0,1}[a-zA-Z-, ])*$/',
                    'language' => 'required|alpha|min:2|max:2',
                ], [
                    'first_name.required' => trans('auth.fNameRequired'),
                    'last_name.required' => trans('auth.lNameRequired'),
                    'first_name.regex' => trans('auth.alphabets_space_hypen_allowed'),
                    'last_name.regex' => trans('auth.alphabets_space_hypen_allowed'),                    
                ]);
                if($validator->fails()){
                    return redirect()
                        ->to('/home/preferences')
                        ->withErrors($validator)
                        ->withInput($request->all())->with('tabname', 'Profile')->with('enableContactFrm', 'Yes');
                }
				else{
                    $user = User::find(Auth::user()->id);
                    $telegram_id = $request->get('telegram_id');
                    $telegram_id = str_replace(' ', '', $telegram_id);
                    if(!empty($telegram_id) && isset($telegram_id)) {
                        $telegramUser = User::select('id')->where('telegram_id', '=', $telegram_id)->where('id', '!=', Auth::user()->id)->first();
                        if ($telegramUser->id) {
                            return redirect()->to('/home/preferences')->with('error', __('message.telegram_id_already_exist'))->with('tabname', 'Profile');
                        }
                    } else {
                        $telegram_id = NULL;
                    }
                    $user->first_name = $request->get('first_name');
                    $user->last_name = $request->get('last_name');
					$user->display_name = $request->get('display_name');
                    $user->mobile_number = $request->get('mobile_number');
					$user->mobile_code = $request->get('mobile_code');
                    $user->telegram_id = $telegram_id;
                    $user->language = $request->get('language');
                    $user->save();
                    return redirect()->route('home.preferences')->with('success', trans("message.contact_update_success"))->with('tabname', 'Profile');
                }
            }
			elseif ($request->get('update_email'))   /* Contact Details */
			{
                $validator = Validator::make($request->all(), [
                    'email' => 'unique:users,email,' . $user->id . '|required|email|max:255',
                ], [                    
                    'email.required' => trans('auth.emailRequired'),
                    'email.email' => trans('auth.emailInvalid')
                ]);
                if ($validator->fails()) {
                    return redirect()
                        ->to('/home/preferences')
                        ->withErrors($validator)
                        ->withInput($request->all())->with('tabname', 'Profile')->with('enableContactFrm', 'Yes');
                } else {
                    $user = User::find(Auth::user()->id);
                    if ($request->get('email') != $user->email) {
						
                        //generate unique_confirmation_key
                        $unique_confirmation_key = uniqid(bin2hex(openssl_random_pseudo_bytes(10)), true);
								
						$changeRequests = ChangeRequests::create([
                            'old_value' => $user->email,
                            'new_value' => $request->get('email'),
                            'unique_confirmation_key' => $unique_confirmation_key,
                            'type' => 3,
                            'user_id' => $user->id,
                            'is_delete' => 0
                        ]);
						
                        if ($changeRequests->id) {
                            //mail data
                            $emailData = array(
                                'old_value' => $user->email,
                                'new_value' => $request->get('email'),
                                'unique_confirmation_key' => $unique_confirmation_key,
                                'id' => $changeRequests->id,
                                'type' => 3
                            );
                            // Send activation email notification
                            self::sendChangeEmailRequest($user, $emailData);
                            return redirect()->to('/home/preferences')->with('success', trans("message.email_change_request"))->with('tabname', 'ChangePassword');
                        }
                    }
					return redirect()->route('home.preferences')->with('success', trans("message.email_update_success"))->with('tabname', 'Profile');
                }
            }
			elseif ($request->get('update_address')) 
			{
                if (in_array($request->get('country_code'), $country_without_postal['codeList'])) {
                    $validator = Validator::make($request->all(), [
                        'address1' => 'required|alpha_spaces_addrr',
						'address2' => 'alpha_spaces_addrr',
                        'country_code' => 'required',
                        'city' => 'required|alpha_spaces_city',
                    ], [
                        'address1.required' => trans('auth.address1_is_required'),
						'address1.alpha_spaces_addrr'=>trans("auth.address_is_invalid"),
						'address2.alpha_spaces_addrr'=>trans("auth.address_is_invalid"),
                        'city.required' => trans('auth.city_is_required'),
                        'city.alpha_spaces_city' => trans('auth.alphabets_space_hypen_allowed'),
                    ]);
                } else {
                    $validator = Validator::make($request->all(), [
                        'address1' => 'required|alpha_spaces_addrr',
						'address2' => 'alpha_spaces_addrr',
                        'postal_code' => 'required|valid_postal_code',
                        'country_code' => 'required',
                        'city' => 'required|alpha_spaces_city',
                    ], [
                        'address1.required' => trans('auth.address1_is_required'),
						'address1.alpha_spaces_addrr'=>trans("auth.address_is_invalid"),
						'address2.alpha_spaces_addrr'=>trans("auth.address_is_invalid"),
                        'postal_code.required' => trans('lendo.postal_code_required'),
                        'postal_code.valid_postal_code' => trans('auth.alphabets_with_space_dash_allowed'),
                        'city.required' => trans('auth.city_is_required'),
                        'city.alpha_spaces_city' => trans('auth.alphabets_space_hypen_allowed'),
                    ]);
                }
                if ($validator->fails()) {
                    return redirect()
                        ->route('home.preferences')
                        ->withErrors($validator)
                        ->withInput($request->all())->with('tabname', 'Profile')->with('enableAddressFrm', 'Yes');
                } 
				else {
                    $user = User::find(Auth::user()->id);
                    $user->address1 = $request->get('address1');
                    $user->address2 = $request->get('address2');
                    $user->postal_code = $request->get('postal_code');
                    $user->city = $request->get('city');
                    $user->country_code = $request->get('country_code');
                    $user->save();
                    return redirect()->route('home.preferences')->with('success', trans("message.address_update_success"))->with('tabname', 'Profile');
                }
            } 
			elseif ($request->get('update_password')) {
                $validator = Validator::make($request->all(), [
                    'password' => 'required|min:6|max:20',
                    'new_password' => 'required|min:6|max:20|confirmed',
                    'new_password_confirmation' => 'required|same:new_password',
                ], [
                    'password.required' => trans('auth.passwordRequired'),
                    'password.min' => trans('auth.PasswordMin'),
                    'password.max' => trans('auth.PasswordMax'),
                    'new_password.required' => trans('auth.NewPasswordRequired'),
                    'new_password.min' => trans('auth.PasswordMin'),
                    'new_password.max' => trans('auth.PasswordMax'),
                ]);
                if ($validator->fails()) {
                    return redirect()
                        ->route('home.preferences')
                        ->withErrors($validator)
                        ->withInput($request->all())->with('tabname', 'Profile')->with('enablePasswordFrm', 'Yes');
                } else {
                    $user = User::find(Auth::user()->id);
                    if ($request->get('password') != '' && !Hash::check($request->get('password'), $user->password)) {
                        return redirect()
                            ->route('home.preferences')
                            ->withErrors(['password' => trans("message.preference_password_incorect")])
                            ->withInput($request->all())->with('tabname', 'Profile')->with('enablePasswordFrm', 'Yes');
                    }
                    if (isset($_POST['new_password']) && $_POST['new_password'] != '') {
                        $user->password = bcrypt($_POST['new_password']);
                        //mail data
                        $emailData = array(
                            'ChangeEntity' => 'Password'
                        );
                        // Send activation email notification
                        self::SendChangeNotification($user, $emailData);
                    }
                    $user->save();
                    return redirect()->route('home.preferences')->with('success', trans("message.password_change_success"))->with('tabname', 'Profile');
                }
            }
			elseif ($request->get('update_new_BTC_wallet_address')){
				$validator = Validator::make($request->all(), [
                    'BTC_wallet_address'   =>  ['required', new BTCValidation]
                ],[
                    'BTC_wallet_address.required'   => trans('auth.currencyWalletAddressRequired',['unit'=>'BTC']),
                ]);
				
				if($validator->fails()){
					return redirect()
                            ->route('home.preferences')
                            ->withErrors($validator)
                            ->withInput($request->all())->with('tabname','Wallet');
				}
				else 
				{
					$this->regsiterChangeWalletRequest(Auth::user()->BTC_wallet_address, $request->get('BTC_wallet_address'), Auth::user()->id, 1, 'BTC');
					return  redirect()->route('home.preferences')->with('success', trans("message.BTC_change_request"))->with('tabname','Wallet');	
                }
			}
			elseif ($request->get('update_new_ETH_wallet_address')){
				$validator = Validator::make($request->all(), [
                    'ETH_wallet_address'   =>  ['required', new ETHValidation]
                ],[
                    'ETH_wallet_address.required'   => trans('auth.currencyWalletAddressRequired',['unit'=>'ETH']),
                ]);
				
				if($validator->fails()){
					return redirect()
                            ->route('home.preferences')
                            ->withErrors($validator)
                            ->withInput($request->all())->with('tabname','Wallet');
				}
				else 
				{
					$this->regsiterChangeWalletRequest(Auth::user()->ETH_wallet_address, $request->get('ETH_wallet_address'), Auth::user()->id, 2, 'ETH');
					return redirect()->route('home.preferences')->with('success', trans("message.ETH_change_request"))->with('tabname','Wallet');	
                }
			}
			elseif ($request->get('update_new_LTC_wallet_address')){
				$validator = Validator::make($request->all(), [
                    'LTC_wallet_address'   =>  ['required']
                ],[
                    'LTC_wallet_address.required'   => trans('auth.currencyWalletAddressRequired',['unit'=>'BCH']),
                ]);
				
				if($validator->fails()){
					return redirect()
                            ->route('home.preferences')
                                ->withErrors($validator)
                                    ->withInput($request->all())->with('tabname','Wallet');
				}
				else 
				{
					$this->regsiterChangeWalletRequest(Auth::user()->LTC_wallet_address, $request->get('LTC_wallet_address'), Auth::user()->id, 7, 'LTC');
					return  redirect()->route('home.preferences')->with('success', trans("message.LTC_change_request"))->with('tabname','Wallet');	
                }
			}
			elseif ($request->get('update_new_BCH_wallet_address')){
				$validator = Validator::make($request->all(), [
                    'BCH_wallet_address'   =>  ['required']
                ],[
                    'BCH_wallet_address.required'   => trans('auth.currencyWalletAddressRequired',['unit'=>'BCH']),
                ]);
				
				if($validator->fails()){
					return redirect()
                            ->route('home.preferences')
                                ->withErrors($validator)
                                    ->withInput($request->all())->with('tabname','Wallet');
				}
				else 
				{
					$this->regsiterChangeWalletRequest(Auth::user()->BCH_wallet_address, $request->get('BCH_wallet_address'), Auth::user()->id, 8, 'BCH');
					return  redirect()->route('home.preferences')->with('success', trans("message.BCH_change_request"))->with('tabname','Wallet');	
                }
			}
			elseif ($request->get('update_new_XRP_wallet_address')){
				$validator = Validator::make($request->all(), [
                    'XRP_wallet_address'   =>  ['required']
                ],[
                    'XRP_wallet_address.required'   => trans('auth.currencyWalletAddressRequired',['unit'=>'XRP']),
                ]);
				
				if($validator->fails()){
					return redirect()
                            ->route('home.preferences')
                                ->withErrors($validator)
                                    ->withInput($request->all())->with('tabname','Wallet');
				}
				else 
				{
					$this->regsiterChangeWalletRequest(Auth::user()->XRP_wallet_address, $request->get('XRP_wallet_address'), Auth::user()->id, 9, 'XRP');
					return  redirect()->route('home.preferences')->with('success', trans("message.XRP_change_request"))->with('tabname','Wallet');	
                }
			}
			elseif ($request->get('update_new_DASH_wallet_address')){
				$validator = Validator::make($request->all(), [
                    'DASH_wallet_address'   =>  ['required']
                ],[
                    'DASH_wallet_address.required'   => trans('auth.currencyWalletAddressRequired',['unit'=>'DASH']),
                ]);
				
				if($validator->fails()){
					return redirect()
                            ->route('home.preferences')
                                ->withErrors($validator)
                                    ->withInput($request->all())->with('tabname','Wallet');
				}
				else 
				{
					$this->regsiterChangeWalletRequest(Auth::user()->DASH_wallet_address, $request->get('DASH_wallet_address'), Auth::user()->id, 10, 'DASH');
					return  redirect()->route('home.preferences')->with('success', trans("message.DASH_change_request"))->with('tabname','Wallet');	
                }
			}
			elseif ($request->get('update_new_ELT_wallet_address')){
				$validator = Validator::make($request->all(), [
                    'ELT_wallet_address'   =>  ['required']
                ],[
                    'ELT_wallet_address.required'   => trans('auth.currencyWalletAddressRequired',['unit'=>'ELT']),
                ]);
				
				if($validator->fails()){
					return redirect()
                            ->route('home.preferences')
                            ->withErrors($validator)
                            ->withInput($request->all())->with('tabname','Wallet');
				}
				else 
				{
					$valid_elt_address = array("address" => $request->get('ELT_wallet_address'));
					$address_response = CommonHelper::call_eth_api('is_valid_address', $valid_elt_address);	
					$address_response = json_decode($address_response,true);
					if(isset($address_response['is_address']) && $address_response['is_address'] == 1)
					{
						$this->regsiterChangeWalletRequest(Auth::user()->ELT_wallet_address, $request->get('ELT_wallet_address'), Auth::user()->id, 11, 'ELT');
						return  redirect()->route('home.preferences')->with('success', trans("message.ELT_change_request"))->with('tabname','Wallet');
					}
					else
					{
						$errors = new MessageBag();
						$errors->add('ELT_wallet_address','Please enter valid ELT wallet address');
						return redirect()
                            ->route('home.preferences')
                                ->withErrors($errors)
                                    ->withInput($request->all)->with('tabname','Wallet');
					}						
                }
			}
			else if($request->get('save_register_new_bank_info'))
			{				
				$validator = Validator::make($request->all(), [
                    'register_new_IBAN_number'   => 'required',
					'register_new_Swift_code'   => 'required'
                ],[
                    'register_new_IBAN_number.required'   => trans('auth.IBan_Number'),
					'register_new_Swift_code.required'   => trans('auth.Swift_Code')
                ]);
				
				if ($validator->fails()) {
                    return redirect()
                            ->route('home.preferences')
                            ->withErrors($validator)
                            ->withInput($request->all)->with('tabname','Bank');
                }
				
				else
				{
					
					$new_Beneficiary_name = $request->get('new_Beneficiary_name');
					$first_name = Auth::user()->first_name;
					$last_name = Auth::user()->last_name;
					if(isset($new_Beneficiary_name) && $new_Beneficiary_name!=$first_name.' '.$last_name)
					{
						$errors = new MessageBag();
						$errors->add('new_Beneficiary_name', __('message.invalid_beneficiary_message'));
						return redirect()
                            ->route('home.preferences')
                                ->withErrors($errors)
                                    ->withInput($request->all)->with('tabname','Bank');

					}
					
					/* generate unique_confirmation_key */					
					$unique_confirmation_key = uniqid(bin2hex(openssl_random_pseudo_bytes(10)), true);

					$old_value_array = array();
					$new_value_array = array();

					$new_value_array['iban_number'] = $request->get('register_new_IBAN_number');
					$new_value_array['Swift_code'] = $request->get('register_new_Swift_code');
					$old_value_array['iban_number'] = Auth::user()->IBAN_number;
					$old_value_array['Swift_code'] = Auth::user()->Swift_code;
					
					if(isset($new_Beneficiary_name))
					{
						$new_value_array['Beneficiary_name'] = $new_Beneficiary_name; 
						$old_value_array['Beneficiary_name'] = Auth::user()->Beneficiary_name; 
					}
					
					$new_Bank_name = $request->get('new_Bank_name');
					if(isset($new_Bank_name))
					{
						$new_value_array['Bank_name'] = $new_Bank_name;
						$old_value_array['Bank_name'] = Auth::user()->Bank_name;
					}
					
					$new_Bank_address = $request->get('new_Bank_address');
					if(isset($new_Bank_address))
					{
						$new_value_array['Bank_address'] = $new_Bank_address;
						$old_value_array['Bank_address'] = Auth::user()->Bank_address;
					}
					
					$new_Bank_street_name = $request->get('new_Bank_street_name');
					if(isset($new_Bank_street_name))
					{
						$new_value_array['Bank_street_name'] = $new_Bank_street_name;
						$old_value_array['Bank_street_name'] = Auth::user()->Bank_street_name;
					}
					
					$new_Bank_city_name = $request->get('new_Bank_city_name');
					if(isset($new_Bank_city_name))
					{
						$new_value_array['Bank_city_name'] = $new_Bank_city_name;
						$old_value_array['Bank_city_name'] = Auth::user()->Bank_city_name; 
					}
					
					$new_Bank_city_name = $request->get('new_Bank_postal_code');
					if(isset($new_Bank_city_name))
					{
						$new_value_array['Bank_postal_code'] = $new_Bank_city_name; 
						$old_value_array['Bank_postal_code'] = Auth::user()->Bank_postal_code;
					}
					
					$new_Bank_country = $request->get('new_Bank_country');
					if(isset($new_Bank_country))
					{
						$new_value_array['Bank_country'] = $new_Bank_country;
						$old_value_array['Bank_country'] = Auth::user()->Bank_country; 
					}

					$changeRequests = ChangeRequests::create([
						'old_value'              => serialize($old_value_array),
						'new_value'              => serialize($new_value_array),
						'unique_confirmation_key'=> $unique_confirmation_key,
						'type'                   => 4,
						'user_id'                => Auth::user()->id,
						'is_delete'              => 0
					]);
					if($changeRequests->id)
					{
						$emailData = array(
							'old_value'        			=> $old_value_array,
							'new_value'        			=> $new_value_array,
							'unique_confirmation_key'   => $unique_confirmation_key,
							'id'                        => $changeRequests->id,
							'type'                      => 4
						);

						$user = User::find(Auth::user()->id);
						self::SendChangeIBanNumber($user, $emailData);
						$msg = trans("message.IBan_change_request");
						return  redirect()->route('home.preferences')->with('success', $msg)->with('tabname','Bank');
					}
					else
					{
						return redirect()
							->route('home.preferences')
								->withErrors(['register_new_IBAN_number' => ''])
									->withInput($request->only('register_new_IBAN_number'))->with('tabname','Bank');

					}
				}
			}
			elseif ($request->get('submit_kyc')) 
			{
				$check_kyc = DB::table('users')
					->select('users.first_name', 'users.last_name', 'users.address1', 'users.city', 'users.postal_code', 'users.country_code')
					->where('users.id', Auth::user()->id)
					->first();
				
				if (isset($check_kyc->first_name) && isset($check_kyc->last_name) && isset($check_kyc->address1) && isset($check_kyc->city) && isset($check_kyc->postal_code) && isset($check_kyc->country_code)) 
				{
					$validator1 = Validator::make($request->all(), [
						'kyc_document_dlf' => 'required|mimes:jpeg,png,jpg,pdf|max:4096',
					]);				
					if ($validator1->fails() && $request->file('kyc_document_dlf')) {
						return redirect()->route('home.preferences')->withErrors($validator1)->withInput($request->all())->with('tabname', 'Verification');
					}				
					$validator2 = Validator::make($request->all(), [
						'kyc_document_dlb' => 'required|mimes:jpeg,png,jpg,pdf|max:4096',
					]);				
					if ($validator2->fails() && $request->file('kyc_document_dlb')) {
						return redirect()->route('home.preferences')->withErrors($validator2)->withInput($request->all())->with('tabname', 'Verification');
					}					
					$validator3 = Validator::make($request->all(), [
						'kyc_document_poa' => 'required|mimes:jpeg,png,jpg,pdf|max:4096',
					]);					
					if ($validator3->fails() && $request->file('kyc_document_poa')) 
					{
						return redirect()->route('home.preferences')->withErrors($validator3)->withInput($request->all())->with('tabname', 'Verification');
					}				
					$destinationPath = public_path('/kyc');					
					$type = NULL;					
					$s3 = \Storage::disk('s3');
             		$s3update1 = $s3update2 = $s3update3 = '';
					if($request->file('kyc_document_dlf')) 
					{					
						$kyc_document_dlf = $request->file('kyc_document_dlf');
						$kyc_document_dlf_filename = 'dlf-' . time().'-'.uniqid().'.' . $kyc_document_dlf->getClientOriginalExtension();						
						$kyc_document_dlf_type = 'DLF';
						$awsFilePath = '/kyc/' . $kyc_document_dlf_filename;
						$s3update1 = $s3->put($awsFilePath, file_get_contents($kyc_document_dlf), 'private');
					}				
					if($request->file('kyc_document_dlb')) 
					{
						$kyc_document_dlb = $request->file('kyc_document_dlb');
						$kyc_document_dlb_filename = 'dlb-' . time() . '-'. uniqid().'.' . $kyc_document_dlb->getClientOriginalExtension();
						$kyc_document_dlb_type = 'DLB';
						$awsFilePath = '/kyc/' . $kyc_document_dlb_filename;
						$s3update2 = $s3->put($awsFilePath, file_get_contents($kyc_document_dlb), 'private');					
					}
				
					if ($request->file('kyc_document_poa')) 
					{
						$kyc_document_poa = $request->file('kyc_document_poa');						
						$kyc_document_poa_filename = 'poa-' . time() . '-'.uniqid().'.' . $kyc_document_poa->getClientOriginalExtension();						
						$kyc_document_poa_type = 'POA';
						$awsFilePath = '/kyc/' . $kyc_document_poa_filename;
						$s3update3 = $s3->put($awsFilePath, file_get_contents($kyc_document_poa), 'private');
					}
				
					if($s3update1) 
					{
						$check_kyc = array();
						$check_kyc = DB::table('file_attachments')
							->select('file_attachments.id','file_attachments.filename')
							->where('file_attachments.user_id', Auth::user()->id)
							->where('file_attachments.type', $kyc_document_dlf_type)
							->first();
							
						if(isset($check_kyc->id)) 
						{
							$FileAttachments = FileAttachments::find($check_kyc->id);
							$deletePath = '/kyc/'.$FileAttachments->filename;							
							if(\Storage::disk('s3')->exists($deletePath)) 
							{
								\Storage::disk('s3')->delete($deletePath);
							}							
							$FileAttachments->filename = $kyc_document_dlf_filename;
							$FileAttachments->updated_at = date("Y-m-d H:i:s");
							$FileAttachments->status = 0;
							$FileAttachments->save();
						} 
						else 
						{
							$FileAttachments = new FileAttachments();
							$FileAttachments->user_id = Auth::user()->id;
							$FileAttachments->filename = $kyc_document_dlf_filename;
							$FileAttachments->type = $kyc_document_dlf_type;
							$FileAttachments->save();
						}
					}
					if($s3update2) 
					{
						$check_kyc = array();
						$check_kyc = DB::table('file_attachments')
							->select('file_attachments.id','file_attachments.filename')
							->where('file_attachments.user_id', Auth::user()->id)
							->where('file_attachments.type', $kyc_document_dlb_type)
							->first();
						if (isset($check_kyc->id)) 
						{
							$FileAttachments = FileAttachments::find($check_kyc->id);
							$deletePath = '/kyc/'.$FileAttachments->filename;
							if(\Storage::disk('s3')->exists($deletePath)) 
							{
								\Storage::disk('s3')->delete($deletePath);
							}						
							
							$FileAttachments->filename = $kyc_document_dlb_filename;
							$FileAttachments->updated_at = date("Y-m-d H:i:s");
							$FileAttachments->status = 0;
							$FileAttachments->save();
						} 
						else 
						{
							$FileAttachments = new FileAttachments();
							$FileAttachments->user_id = Auth::user()->id;
							$FileAttachments->filename = $kyc_document_dlb_filename;
							$FileAttachments->type = $kyc_document_dlb_type;
							$FileAttachments->save();
						}
					}
					if($s3update3) 
					{
						$check_kyc = array();
						$check_kyc = DB::table('file_attachments')
							->select('file_attachments.id','file_attachments.filename')
							->where('file_attachments.user_id', Auth::user()->id)
							->where('file_attachments.type', $kyc_document_poa_type)
							->first();
							
						if(isset($check_kyc->id)) 
						{
							$FileAttachments = FileAttachments::find($check_kyc->id);
							$deletePath = '/kyc/'.$FileAttachments->filename;
							if(\Storage::disk('s3')->exists($deletePath)) 
							{
								\Storage::disk('s3')->delete($deletePath);
							}							
							$FileAttachments->filename = $kyc_document_poa_filename;
							$FileAttachments->updated_at = date("Y-m-d H:i:s");
							$FileAttachments->status = 0;
							$FileAttachments->save();
						} 
						else 
						{
							$FileAttachments = new FileAttachments();
							$FileAttachments->user_id = Auth::user()->id;
							$FileAttachments->filename = $kyc_document_poa_filename;
							$FileAttachments->type = $kyc_document_poa_type;
							$FileAttachments->save();
						}
					}
					if($s3update1 || $s3update2 || $s3update3) 
					{
						$narration = Auth::user()->email . " has updated a new KYC documents";
						$loggerRecord = [
							'userId' => Auth::user()->id,
							'message' => $narration,
							'level' => 'INFO',
							'context' => 'KYC_UPLOAD',
						];
						LoggerHelper::writeDB($loggerRecord);
						return redirect()->route('home.preferences')->with('success', trans("message.kyc_upload_success"))->with('tabname', 'Verification');
					} 
					else 
					{
						return redirect()->route('home.preferences')->with('error', trans('message.error'))->with('tabname', 'Verification');
					}
				}
				else 
				{
					$kyc_data = __('message.kyc_preference_validation');
				}
			}
			else
			{
				$kyc_dir_path = asset("/kyc") . "/";
				
				$DLF_list_file = FileAttachments::where([['user_id', Auth::user()->id], ['type', 'DLF']])->orderBy('created_at', 'DESC')->first();
				
				$DLB_list_file = FileAttachments::where([['user_id', Auth::user()->id], ['type', 'DLB']])->orderBy('created_at', 'DESC')->first();
				
				$POA_list_file = FileAttachments::where([['user_id', Auth::user()->id], ['type', 'POA']])->orderBy('created_at', 'DESC')->first();
				
				$check_kyc = DB::table('users')
                ->select('users.first_name', 'users.last_name', 'users.address1', 'users.city', 'users.postal_code', 'users.country_code')
                ->where('users.id', Auth::user()->id)
                ->first();
				
				if ($check_kyc->first_name && $check_kyc->last_name && $check_kyc->address1 && $check_kyc->city && $check_kyc->postal_code && $check_kyc->country_code) 
				{
					$kyc_data = '';
				} 
				else 
				{
					$kyc_data = __('message.kyc_preference_validation');
				}
			
                $Countries = Country::all();
                return view('preferences',
                    [
                        'Countries' => $Countries,
						'kyc_status'=>$kyc_status,
                        'country_without_postal' => $country_without_postal['allList'],
                        'all_languages' => $all_languages,
						'email_status'=>$email_status,
						'btc_change_status'=>$btc_change_status,
						'eth_change_status'=>$eth_change_status,
						'ltc_change_status'=>$ltc_change_status,
						'bch_change_status'=>$bch_change_status,
						'xrp_change_status'=>$xrp_change_status,
						'dash_change_status'=>$dash_change_status,
						'elt_change_status'=>$elt_change_status,						
						'DLF_list_file' => $DLF_list_file,
						'DLB_list_file' => $DLB_list_file,
						'POA_list_file' => $POA_list_file,
						'kyc_data' => $kyc_data,
						'kyc_dir_path' => $kyc_dir_path					
					]
                );
            }
        } else
            return $this->showSpecialUserView($user);
    }

	
	
    /**
     *Show the user Verification
     */
    public function verification(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $kyc_dir_path = asset("/kyc") . "/";
        error_reporting(0);
        if ($user->hasRole('user')) {
            $DLF_list_file = FileAttachments::where([['user_id', Auth::user()->id], ['type', 'DLF']])->orderBy('created_at', 'DESC')->first();
            $DLB_list_file = FileAttachments::where([['user_id', Auth::user()->id], ['type', 'DLB']])->orderBy('created_at', 'DESC')->first();
            $POA_list_file = FileAttachments::where([['user_id', Auth::user()->id], ['type', 'POA']])->orderBy('created_at', 'DESC')->first();
            $kyc_status = -1; // KYC not uploaded yet
            if (isset($DLF_list_file->status) && $DLF_list_file->status == 0 && isset($DLB_list_file->status) && $DLB_list_file->status == 0 && isset($POA_list_file->status) && $POA_list_file->status == 0) {
                $kyc_status = 0;
            } else if (isset($DLF_list_file->status) && $DLF_list_file->status == 1 && isset($DLB_list_file->status) && $DLB_list_file->status == 1 && isset($POA_list_file->status) && $POA_list_file->status == 1) {
                $kyc_status = 1;
            } else if (isset($DLF_list_file->status) && $DLF_list_file->status == 2 && isset($DLB_list_file->status) && $DLB_list_file->status == 2 && isset($POA_list_file->status) && $POA_list_file->status == 2) {
                $kyc_status = 2;
            }
            $check_kyc = DB::table('users')
                ->select('users.first_name', 'users.last_name', 'users.address1', 'users.city', 'users.postal_code', 'users.country_code')
                ->where('users.id', Auth::user()->id)
                ->first();
            if ($check_kyc->first_name && $check_kyc->last_name && $check_kyc->address1 && $check_kyc->city && $check_kyc->postal_code && $check_kyc->country_code) {
                $kyc_data = '';
            } else {
                $kyc_data = __('message.kyc_preference_validation');
            }
            return view('verification',
                [
                    'DLF_list_file' => $DLF_list_file,
                    'DLB_list_file' => $DLB_list_file,
                    'POA_list_file' => $POA_list_file,
                    'kyc_data' => $kyc_data,
                    'kyc_status' => $kyc_status,
                    'kyc_dir_path' => $kyc_dir_path
                ]
            );
        } else return $this->showSpecialUserView($user);
    }

    /**
     *Show the user Whitepaper
     */
    public function whitepaper(Request $request)
    {
        return view('whitepapers');
    }

    /**
     *Show the user faq
     */
    public function faq(Request $request)
    {
        return view('faq');
    }
	
	  /**
     *Show the user faq
     */
    public function videos(Request $request)
    {
        return view('videos');
    }
	
		  /**
     *Show the user Video Tutorial
     */
    public function videostutorial(Request $request)
    {
        return view('videos_tutorial');
    } 
	
		  /**
     *Show the user Presentations Tutorial
     */
    public function presentations(Request $request)
    {
        return view('presentations');
    } 
	


    /**
     *Show the user faq
     */
    public function mediakit(Request $request)
    {
        return view('mediakit');
    }
	
	/**
     *Show the user faq
     */
    public function referrals_detail()
    {
        return view('referrals_detail');
    }

    /**
     * Load referrals list
     */
    public function referrals(Request $request)
    {		
		$dataForView = array();		
		error_reporting(0);		
		$user = User::find(Auth::user()->id);
		$dataForView['userInfo'] = $user;
		$dataForView['kycStatus'] = FileAttachments::getKYCStatus($user->id);
        $all_referrals_list = User::where('referrer_user_id',$user->id)->orderBy('created_at', 'desc')->get();
		$dataForView['all_referrals_list'] = $all_referrals_list;
		$level_detail = Transactions::get_user_elt_worth_in_euro($user->id);
		$euro_worth = $level_detail[0]->euro_worth_total;
		$dataForView['euro_worth'] = $euro_worth;
		$current_level_array = Transactions::get_current_user_level($user->id);
		$current_level = $current_level_array['current_level'];
		$dataForView['current_level'] = $current_level;
		$euro_worth_for_next_level = $current_level_array['euro_worth_for_next_level'];
		$dataForView['euro_worth_for_next_level'] = $euro_worth_for_next_level;
		$dataForView['levelwise_data'] = Transactions::get_levelwise_user_elt_euro_worth($user->id);
		//echo "<pre>";print_r($dataForView);die;
        return view('referrals', $dataForView);
    }
	
	/**
     * Load referrals new list
     */
    public function referralsnew(Request $request)
    {
		$dataForView = array();
		error_reporting(0);
        $user = User::find(Auth::user()->id);
		$dataForView['userInfo'] = $user;
		$dataForView['kycStatus'] = FileAttachments::getKYCStatus($user->id);
        $all_referrals_list = User::where('referrer_user_id',$user->id)->orderBy('created_at', 'desc')->get();
		$dataForView['all_referrals_list'] = $all_referrals_list;
		$level_detail = Transactions::get_user_elt_worth_in_euro($user->id);
		$euro_worth = $level_detail[0]->euro_worth_total;
		$dataForView['euro_worth'] = $euro_worth;
		$current_level_array = Transactions::get_current_user_level($user->id);
		$current_level = $current_level_array['current_level'];
		$dataForView['current_level'] = $current_level;
		$euro_worth_for_next_level = $current_level_array['euro_worth_for_next_level'];
		$dataForView['euro_worth_for_next_level'] = $euro_worth_for_next_level;
		//$dataForView['levelwise_data'] = Transactions::get_levelwise_user_elt_euro_worth($user->id,1);
		
		$currentMonth = date("m");
        $currentYear = date("Y");
        $top_referrals = array();
        $top_referrals_list = User::get_top_referrals_in_months($currentMonth, $currentYear);
        foreach($top_referrals_list as $top_referrals_row){
            if(FileAttachments::getKYCStatus($top_referrals_row->id) == 1){
                $dataForView['top_referrals'][] = $top_referrals_row;
            }
        }            
		$pre_rank_array = array();
		foreach($dataForView['top_referrals'] as $top_referral){
			$pre_rank_array[$top_referral->id] = $top_referral->this_month;
		}
		$dataForView['pre_rank_array'] = $pre_rank_array;
		$dataForView['current_user_referral'] = User::get_user_referrals_in_months($user->id, $currentMonth, $currentYear);
		$pre_rank_array[$user->id] = $dataForView['current_user_referral'];
		$post_rank_array = CommonHelper::set_ranking_order($pre_rank_array);
		$dataForView['post_rank_array'] = $post_rank_array;
		$dataForView['pre_rank_array'] = $pre_rank_array;
		$top_referrals_final = array();
		$referral_limit = 50;
		$counter = 1;
		foreach($dataForView['top_referrals'] as $top_referral){
			if($counter<=$referral_limit){
				$top_referral->rank = $post_rank_array[$top_referral->id];
				$top_referrals_final[] = $top_referral;
			}
			$counter++;
		}
		$dataForView['top_referrals'] = $top_referrals_final;
		//echo "<pre>";print_r($dataForView);die;
        return view('referralsnew', $dataForView);
    }
	
	
	/**
     * Load referrals terms and condition
     */
    public function referralstc(Request $request)
    {
        return view('referralstc', []);
    }

	
	/**
     * Load referrals member list
     */
    public function referralmember(Request $request)
    {
		$dataForView = array();
		
		$sorting_desc = 'desc';
		$dataForView['sorting_desc'] = $sorting_desc;
        
        $currentMonth = date("m");
        $currentYear = date("Y");
        $top_referrals = array();
        $top_referrals_list = User::get_top_referrals_in_months($currentMonth, $currentYear);
        foreach($top_referrals_list as $top_referrals_row){
            if(FileAttachments::getKYCStatus($top_referrals_row->id) == 1){
                $dataForView['top_referrals'][] = $top_referrals_row;
            }
        }            
		$pre_rank_array = array();
		foreach($dataForView['top_referrals'] as $top_referral){
			$pre_rank_array[$top_referral->id] = $top_referral->this_month;
		}
		$dataForView['pre_rank_array'] = $pre_rank_array;
		$dataForView['current_user_referral'] = User::get_user_referrals_in_months(Auth::user()->id, $currentMonth, $currentYear);
		$pre_rank_array[Auth::user()->id] = $dataForView['current_user_referral'];
		$post_rank_array = CommonHelper::set_ranking_order($pre_rank_array);
		$dataForView['post_rank_array'] = $post_rank_array;
		$dataForView['pre_rank_array'] = $pre_rank_array;
		$top_referrals_final = array();
		$referral_limit = config("constants.REFERRAL_LEADERBOARD_LIMIT");
		$counter = 1;
		foreach($dataForView['top_referrals'] as $top_referral)
		{
			if($counter<=$referral_limit)
			{
				$top_referral->rank = $post_rank_array[$top_referral->id];
				$top_referrals_final[] = $top_referral;
			}
			$counter++;
		}	
		$dataForView['top_referrals'] = $top_referrals_final;
		//echo "<pre>";print_r($dataForView);die;
		return view('referralmember', $dataForView);
    }
	
	/**
     * Load sales top listing with ranking
     */
    public function salesleaderboard(Request $request)
    {
		$dataForView = array();
		$top_sales_list = array();
		$top_sales_list = ParentChild::get_all_downline_sales_data();
		foreach($top_sales_list as $top_sales_row){				
			if(FileAttachments::getKYCStatus($top_sales_row->parent_id) == 1){
				$top_sales_row->fakeSalesAmount = ParentChild::get_user_sales_fake_Data($top_sales_row->parent_id);
				$top_sales_with_kycverified[] = $top_sales_row;
			}
		}		
		$top_sales_with_kycverified = $top_sales_list;
		$pre_rank_array = array();
		
		foreach($top_sales_with_kycverified as $top_sales){
			if($top_sales->fakeSalesAmount > 0){
				$pre_rank_array[$top_sales->parent_id] = $top_sales->fakeSalesAmount;
			}
			else{
				$pre_rank_array[$top_sales->parent_id] = $top_sales->euro_worth_total;
			}
		}
		
		$dataForView['pre_rank_array'] = $pre_rank_array;
		$post_rank_array = CommonHelper::set_ranking_order($pre_rank_array);
		$dataForView['post_rank_array'] = $post_rank_array;
		$top_sales_final = array();
		$referral_limit = config("constants.SALES_LEADERBOARD_LIMIT");
		$counter = 1;
		
		foreach($post_rank_array as $postRankKey=>$postRankValue)
		{	
			foreach($top_sales_with_kycverified as $top_referral)
			{
				if($counter<=$referral_limit)
				{
					if($top_referral->parent_id == $postRankKey){
						$top_referral->rank = $postRankValue;
						if(isset($top_referral->fakeSalesAmount) && $top_referral->fakeSalesAmount > 0)
						{
							$top_referral->euro_worth_total = $top_referral->fakeSalesAmount;
						}
						$top_sales_final[] = $top_referral;
						$counter++;
					}
				}
			}
		}
		
		/*
		foreach($top_sales_with_kycverified as $top_referral){
			if($counter<=$referral_limit){
				$top_referral->rank = $post_rank_array[$top_referral->parent_id];
				$top_sales_final[] = $top_referral;
			}
			$counter++;
		}
		*/
		
		$dataForView['top_sales'] = $top_sales_final;
		return view('salesleaderboard', $dataForView);
    }
	
	/**
     *Show the user Withdraw section
     */
    public function withdrawamount(Request $request)
    {
		$dataForView = array();
		$userInfo = User::find(Auth::user()->id);
		$dataForView['userInfo'] = $userInfo;		
		$kycStatus = FileAttachments::getKYCStatus($userInfo->id);
		$type = isset($_GET['type'])?$_GET['type']:'BTC';		
		$dataForView['type'] = $type;		
		$dataForView['wallet_balance'] = CommonHelper::getAppropriateWalletBalance($userInfo, $type);
		$dataForView['wallet_address'] = CommonHelper::getAppropriateWalletAddress($userInfo, $type);		
		if($kycStatus!=1){
			return redirect()->route('home.icowallet')->with('error', trans('validation.kyc_verification_required_for_withdraw'))->with('tabname', 'withdraw');
		}		
		if(empty($dataForView['wallet_address'])){
			return redirect()->route('home.preferences')->with('error','Please enter your '.$type.' wallet address')->with('tabname', 'Wallet');
		}	
		$conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
		$conversion_rate_data = array();
		if (!$conversion_rates->isEmpty()) {
			foreach ($conversion_rates as $key => $rates_minimume_values) {
				$conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
				$conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
			}
		}		
		$dataForView['conversion_rate_data'] = $conversion_rate_data;
		if($request->isMethod('post'))
		{
			$ledger         = $request->type;			
			$validator = Validator::make($request->all(), [
				'withdraw_amount'=> 'required',
			]);				
			if($validator->fails())
			{
                return redirect()->route('withdrawamount',['type'=>$request->type])->with('error', "Please enter withdraw amount")->withInput($request->only('withdraw_amount'));				
            }			
			$wallet_address = CommonHelper::getAppropriateWalletAddress($userInfo, $request->type);
			if(!empty($wallet_address))
			{
				$min_withdraw = $conversion_rate_data['Minimum-Withdraw-'.$request->type][0];				
				$max_withdraw = $conversion_rate_data['Maximum-Withdraw-'.$request->type][0];				
				$withdraw_fee = $conversion_rate_data['Withdrawal-Fee-'.$request->type][0];				
				$withdraw_fee_amount = $conversion_rate_data['Withdrawal-Fee-Amount-'.$request->type][0];
				if($request->withdraw_amount >= $min_withdraw && $request->withdraw_amount <= $max_withdraw)
				{
					$user_id        = $userInfo->id;
					$ledger         = $request->type;
					$value          = '-'.$request->withdraw_amount;
					$transaction_id = uniqid();					
					$current_wallet_balance = CommonHelper::getAppropriateWalletBalance($userInfo,$ledger);
					if($current_wallet_balance < $request->withdraw_amount)
					{
						return redirect()->route('withdrawamount',['type'=>$ledger])->with('error', "Insufficient balance")->withInput($request->only('withdraw_amount'));
					}					
					if($withdraw_fee > 0)
					{
						$fee_amount = User::calculateCommision($request->withdraw_amount,$withdraw_fee);
					}
					elseif($withdraw_fee_amount > 0)
					{						
						$fee_amount = $withdraw_fee_amount;
					}					
					$transfer_amount = $request->withdraw_amount;
					if($fee_amount > 0)
					{
						$transfer_amount = $request->withdraw_amount - $fee_amount;
					}					
					$unique_confirmation_key = uniqid(bin2hex(openssl_random_pseudo_bytes(10)), true);
					$new_value_array = array();
					$new_value_array['transaction_id'] = $transaction_id;
					$new_value_array['user_id'] = $user_id;
					$new_value_array['wallet_address'] = $wallet_address;
					$new_value_array['amount'] = $request->withdraw_amount;
					$new_value_array['currency_name'] = $ledger;
					$changeRequests = ChangeRequests::create([
						'old_value'              => serialize(array()),
						'new_value'              => serialize($new_value_array),
						'unique_confirmation_key'=> $unique_confirmation_key,
						'type'                   => 12,
						'user_id'                => Auth::user()->id,
						'is_delete'              => 0
					]);
					if($changeRequests->id) 
					{
						$emailData = array(
							'currency_name'        		=> $ledger,
							'amount'        			=> $request->withdraw_amount,
							'wallet_address'			=> $wallet_address,
							'unique_confirmation_key'   => $unique_confirmation_key,
							'id'                        => $changeRequests->id,
							'type'                      => 12
						);
						self::SendWithDrawalRequest($userInfo, $emailData);
					}						
					Withdrawal::create([
						'transaction_id'    => $transaction_id,
						'ledger'            => $ledger,
						'amount'            => $request->withdraw_amount,
						'fees'              => $fee_amount,
						'transfer_amount'   => $transfer_amount,
						'status'            => 0,
						'remarks'           => '',
						'ip_address'        => CommonHelper::get_client_ip()!=''?CommonHelper::get_client_ip():\Request::getClientIp(true),
						'user_id'           =>$user_id
					]);					
					$record = [
						'message'   => 'Username '.$userInfo->email.' request withdraw '.$ledger.' has created '.$description.' Amount : '.$request->withdraw_amount.' user id : '.$userInfo->id,
						'level'     => 'INFO',
						'context'   => 'withdraw'.$ledger
					];
                    LoggerHelper::writeDB($record);					
					return redirect()->route('withdrawamount',['type'=>$ledger])->with('success',trans('lendo.withdraw_request_success'))->withInput($request->only('withdraw_amount'));
				}
				else
				{
					return redirect()->route('withdrawamount',['type'=>$ledger])->with('error',trans('lendo.CurrencyAmountGreaterValidation',['unit'=>$request->type,'amount'=>$min_withdraw]))->withInput($request->only('withdraw_amount'));
				}
			}
			else
			{
				return redirect()->route('withdrawamount',['type'=>$ledger])->with('error', trans('lendo.EnterCryptoWalletAddress',['unit'=>$request->type]))->withInput($request->only('withdraw_amount'));
			}
		}		
		$ChangeRequests = ChangeRequests::get_change_request_list($userInfo->id,12);		
		$linkExpireTimeInMins = config('constants.LINK_EXPIRE_TIME_IN_MINS');		
		$currentTime = time();		
		$pending_withdraw = 0;
		foreach($ChangeRequests as $ChangeRequest){			
			$expire_time = strtotime($ChangeRequest->created_at) + $linkExpireTimeInMins * 60;
			if($currentTime < $expire_time){
				$dataRow = unserialize($ChangeRequest->new_value);
				if($dataRow['currency_name'] == $type){
					$pending_withdraw = 1;
					break;
				}
			}
			else{
				ChangeRequests::where('id', $ChangeRequest->id)->update(['is_delete' => '1']);
			}
		}
		$dataForView['pending_withdraw'] = $pending_withdraw;		
		//echo "<pre>";print_r($dataForView);die;
        return view('withdraw_amount',$dataForView);
    }
	
	/**
     * Load ELT invoices list
     */
    public function invoices(Request $request)
    {
		$dataForView = array();
		$user_elt_invoices = DB::table('elt_invoices')->select('elt_invoices.*')->where('elt_invoices.user_id',Auth::user()->id)->orderBy('id', 'DESC')->get();
		$dataForView['user_elt_invoices'] = $user_elt_invoices;
		return view('invoices', $dataForView);
    }
	
	
	/**
     * Load invoice detail
     */
    public function invoice_detail($id, Request $request)
    {
		$dataForView = array();
		$exchange_value_in_euro='';
		$invoice_detail = Transactions::get_invoice_detail($id);
		if($invoice_detail->currency != 'EUR'){
			$coin_base_rate_data = CommonHelper::get_coinbase_currency($invoice_detail->currency);
			$exchange_value_in_euro = $coin_base_rate_data['data']['rates']['EUR'] * $invoice_detail->currency_amount;
		}
		$dataForView['invoice_detail'] = $invoice_detail;
		$dataForView['exchange_value_in_euro'] = $exchange_value_in_euro;
		$dataForView['invoice_bonus_row'] = Transactions::get_invoice_bonus($invoice_detail->ref_transaction_id,$invoice_detail->user_id);
		return view('invoice_detail', $dataForView);
    }
	

    /**
     * Save the bitcoin wallet
     */
    public function confirmChange($token)
    {
        $msg = $error = '';
		
        if(isset($token) && !empty($token)) 
		{
            $dataToUpdate = ChangeRequests::where('unique_confirmation_key',$token)->where('is_delete',0)->first();
			
            if($dataToUpdate) 
			{				
				$expire_at = strtotime($dataToUpdate->created_at) + config('constants.LINK_EXPIRE_TIME_IN_MINS') * 60;
				if(time() > $expire_at)
				{
					$error = trans('withdraw.token_expired_make_new_request');
					ChangeRequests::where('id', $dataToUpdate->id)->update(['is_delete' => '1']);
					if($dataToUpdate->type == 12)
					{
						$dataRow = array();
						$dataRow = unserialize($dataToUpdate->new_value);
						if(isset($dataRow['transaction_id']))
						{							
							Withdrawal::where('transaction_id',$dataRow['transaction_id'])->update(['status' => '3']);
						}
					}
				}
				else
				{
					$user = User::find($dataToUpdate->user_id);
					switch ($dataToUpdate->type) 
					{
						case '1':
							$msg = $this->updateAndNotify($dataToUpdate, $user, 'BTC Wallet Address', 'BTC_wallet_address', "message.BTC_update");
							break;
						case '2':
							$msg = $this->updateAndNotify($dataToUpdate, $user, 'ETH Wallet Address', 'ETH_wallet_address', "message.ETH_update");
							break;
						case '3':
							$msg = $this->updateAndNotify($dataToUpdate, $user, 'Email address', 'email', "message.preference_update");
							break;
						case '4':
							$new_value_array = unserialize($dataToUpdate->new_value);
							$updateData = array();
							$updateData['IBAN_number'] = $new_value_array['iban_number'];
							$updateData['Swift_code'] = $new_value_array['Swift_code'];
							if (isset($new_value_array['Beneficiary_name'])) {
								$updateData['Beneficiary_name'] = $new_value_array['Beneficiary_name'];
							}
							if (isset($new_value_array['Bank_name'])) {
								$updateData['Bank_name'] = $new_value_array['Bank_name'];
							}
							if (isset($new_value_array['Bank_address'])) {
								$updateData['Bank_address'] = $new_value_array['Bank_address'];
							}
							if (isset($new_value_array['Bank_street_name'])) {
								$updateData['Bank_street_name'] = $new_value_array['Bank_street_name'];
							}
							if (isset($new_value_array['Bank_city_name'])) {
								$updateData['Bank_city_name'] = $new_value_array['Bank_city_name'];
							}
							if (isset($new_value_array['Bank_postal_code'])) {
								$updateData['Bank_postal_code'] = $new_value_array['Bank_postal_code'];
							}
							if (isset($new_value_array['Bank_country'])) {
								$updateData['Bank_country'] = $new_value_array['Bank_country'];
							}
							User::where('id', $dataToUpdate->user_id)->update($updateData);
							$this->flagAsUpdatedAndNotify($dataToUpdate, $user, __('layouts.bank_info'), "message.preference_update");
							break;
						case '7':
							$msg = $this->updateAndNotify($dataToUpdate, $user, 'LTC Wallet Address', 'LTC_wallet_address', "message.LTC_update");
							break;
						case '8':
							$msg = $this->updateAndNotify($dataToUpdate, $user, 'BCH Wallet Address', 'BCH_wallet_address', "message.BCH_update");
							break;
						case '9':
							$msg = $this->updateAndNotify($dataToUpdate, $user, 'XRP Wallet Address', 'XRP_wallet_address', "message.XRP_update");
							break;
						case '10':
							$msg = $this->updateAndNotify($dataToUpdate, $user, 'DASH Wallet Address', 'DASH_wallet_address', "message.DASH_update");
							break;
						case '11':
							$msg = $this->updateAndNotify($dataToUpdate, $user, 'ELT Wallet Address', 'ELT_wallet_address', "message.ELT_update");
							break;
						case '12':						
							$new_value_array = unserialize($dataToUpdate->new_value);
							if(isset($new_value_array['transaction_id']))
							{
								$transaction_id = $new_value_array['transaction_id'];
							}
							$withdrawalRow = Withdrawal::where('transaction_id',$transaction_id)->first();
							if($withdrawalRow->id)
							{
								$current_wallet_balance = CommonHelper::getAppropriateWalletBalance($user,$withdrawalRow->ledger);
					
								if($current_wallet_balance < $withdrawalRow->amount)
								{
									return redirect()->route('withdrawamount',['type'=>$withdrawalRow->ledger])->with('error', "You do not have sufficient balance to make withdraw request");
								}
								
								$user_id        = $withdrawalRow->user_id;
								$ledger         = $withdrawalRow->ledger;
								$value          = '-'.$withdrawalRow->amount;
								$transaction_id = $withdrawalRow->transaction_id;
								$wallet_address = $new_value_array['wallet_address'];								
								$description    = 'Withdraw unit '.$ledger.' Payment id : '.@$transaction_id.' To address: '.$wallet_address.' Time created at: '.date("m/d/Y H:i:s");
								
								$Transaction = Transactions::createTransaction($user_id, $ledger, $value, $description, 1, $transaction_id, NULL, NULL, 8,NULL, 'withdrawal', 0);
								$user->subtractValue($ledger.'_balance',$withdrawalRow->amount);
								$user->save();
							}
							
							if(isset($new_value_array['transaction_id']) && isset($new_value_array['user_id']))
							{
								Withdrawal::where('transaction_id','=',$new_value_array['transaction_id'])->where('user_id','=',$new_value_array['user_id'])->update(['status'=>2]);
								self::SendWithDrawalConfirmation($user, $new_value_array);
							}
							
							$find_all_pending_request = ChangeRequests::where([['type', $dataToUpdate->type],['is_delete', '0'],['user_id',$dataToUpdate->user_id]])->get();
							if(!empty($find_all_pending_request))
							{
								foreach($find_all_pending_request as $single_request)
								{
									ChangeRequests::where('id', $single_request->id)->update(['is_delete' => '1']);
								}
							}							
							break;
					}
				}
			}
			else 
			{
                $error = trans("message.token_error");
            }
			
			$tabname='Profile';
			if(isset($dataToUpdate->type) && in_array($dataToUpdate->type,array(1,2,7,8,9,10,11))){
				$tabname='Wallet';
			}
			elseif(isset($dataToUpdate->type) && $dataToUpdate->type == 4){
				$tabname='Bank';
			}
			elseif(isset($dataToUpdate->type) && $dataToUpdate->type == 12){
				return redirect()->route('withdrawamount',['type'=>$new_value_array['currency_name']])->with(['success' => 'Withdraw request has been confirmed']);
			}
            return redirect()->to('/home/preferences')->with(['success' => $msg, 'error' => $error])->with('tabname', $tabname);
        }
    }

    /**
     * @param $Transaction
     * @return string
     */
    private function getTransactionStatus($Transaction): string
    {
        switch ($Transaction->status) {
            case 0:
                $status = trans('lendo.Failed');
                break;
            case 1:
                $status = trans('lendo.Success');
                break;
            case 2:
                $status = trans('lendo.Pending');
                break;
            default:
                $status = '-';
        }
        return $status;
    }

    /**
     * @param $transactions_list
     * @param $table_id
     */
    private function jsonEncodeTransactions($transactions_list, $table_id)
    {
        if($transactions_list->isEmpty()){
            echo json_encode(array(
                'status' => 'error',
                'message' => 'empty result',
                'table_id' => $table_id,
            ));
        } 
		else{
            $i = 0;
            $output = array();
            foreach ($transactions_list as $Transaction) 
			{				
				if(strpos($Transaction->value, '-') !== false)
				{					
                    $OUT = '';					
					$IN = $Transaction->ledger == 'ELT' ? round(abs($Transaction->value), config('constants.ELT_PRECISION')) : abs($Transaction->value);
                } 
				else 
				{										
					$OUT = $Transaction->ledger == 'ELT' ? round(abs($Transaction->value), config('constants.ELT_PRECISION')) : abs($Transaction->value);
                    $IN = '';
                }
				
                $status = $this->getTransactionStatus($Transaction);
                $output[$i++] = array
				(
                    $Transaction->id,
                    $Transaction->created_at->format('d/m/Y'),
					'<div class="ellipsis"><p class="showShortDesc_'.$Transaction->transaction_id.'">'.CommonHelper::filter_trans_from_desc($Transaction).'</p><p class="showFullDesc_'.$Transaction->transaction_id.'" style="display:none">'.$Transaction->description.'</p>	<button class="btnToggle" paymentid="'.$Transaction->transaction_id.'">+</button></div>',
					$Transaction->ledger,					 
                    $IN,
                    $OUT,
					$Transaction->unpaid_bonus,
                    $status
                );
            }
            echo json_encode(array(
                'status' => 'success',
                'next_url' => $transactions_list->nextPageUrl(),
                'table_id' => $table_id,
                'data' => $output
            ));
        }
    }

    /**
     * @param $dataToUpdate
     * @param $user
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    private function updateAndNotify($dataToUpdate, $user, $update_message, $field, $msg_key)
    {
		if($dataToUpdate->type != 11){
			User::where('id', $dataToUpdate->user_id)->update([$field => $dataToUpdate->new_value]);
		}
        return $this->flagAsUpdatedAndNotify($dataToUpdate, $user, $update_message, $msg_key);
    }

    /**
     * @param $dataToUpdate
     * @param $user
     * @param $update_message
     * @param $msg_key
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    private function flagAsUpdatedAndNotify($dataToUpdate, $user, $update_message, $msg_key)
    {
		$find_all_pending_request = ChangeRequests::where([['type', $dataToUpdate->type],['is_delete', '0'],['user_id',$dataToUpdate->user_id]])->get();
		
		if(!empty($find_all_pending_request))
		{
			foreach($find_all_pending_request as $single_request)
			{
				ChangeRequests::where('id', $single_request->id)->update(['is_delete' => '1']);
			}
        }
		$msg = trans($msg_key);
        // Send activation email notification
        self::SendChangeNotification($user, array('ChangeEntity' => $update_message));
        return $msg;
    }
	
	
	public function transfer_elt(Request $request)
    {
		$response = array();		
		$response['status'] = "0"; 
		$response['confirmation_key'] = '';		
		parse_str($_POST['data'], $postarray);
		
		if(!empty($postarray['to_address']) && !empty($postarray['elt_amount']) && !empty($postarray['wallet_pin']) && !empty($postarray['type']))
		{			
			$wallet_pin_encoded = $postarray['wallet_pin'];		
			$userid = Auth::user()->id;			
			$UserInfo = User::find($userid);
			
			if(!User::matchWalletPin($wallet_pin_encoded, $userid))
			{
				$response['msg'] = trans('lendo.wallet_pin_incorrect');
				echo json_encode($response);exit;
			}
			
			$bc_elt_balance = CommonHelper::balance_format($UserInfo->ELT_bc_balance,6);
			$bc_eth_balance = CommonHelper::balance_format($UserInfo->ETH_bc_balance,6);
			
			$uc_elt_balance = User::get_nonconfirm_balance($UserInfo->Custom_ETH_Address,'ELT');
			$uc_eth_balance = User::get_nonconfirm_balance($UserInfo->Custom_ETH_Address,'ETH');
	
			if($uc_elt_balance > 0){
				$bc_elt_balance = $bc_elt_balance - $uc_elt_balance;
			}
			if($uc_eth_balance > 0){
				$bc_eth_balance = $bc_eth_balance - $uc_eth_balance;
			}
			
			if($postarray['type'] == 'ELT' && $bc_elt_balance < $postarray['elt_amount'])
			{
				$message = "You have insufficient ELT account balance";
				$response['message'] = $message;	
				echo json_encode($response);exit;
			}
			if($postarray['type'] == 'ETH' && $bc_eth_balance < $postarray['elt_amount'])
			{
				$message = "You have insufficient ETH account balance";
				$response['message'] = $message;	
				echo json_encode($response);exit;
			}
			
			$unique_confirmation_key = uniqid(bin2hex(openssl_random_pseudo_bytes(10)), true);
			$changeRequests = ChangeRequests::create([
				'old_value' => 'transfer_elt',
				'new_value' => serialize($postarray),
				'unique_confirmation_key' => $unique_confirmation_key,
				'type' => 6,
				'user_id' => $userid,
				'is_delete' => 0
			]);
			
			if($changeRequests->id) 
			{
				$google2fa = new Google2FA();
				
				$OTP = $google2fa->generateSecretKey();
				
				self::sendEmailToUserWithOTP($UserInfo, $OTP);
				
				DB::table("users")->where("id",$userid)->update(['app_otp'=>$OTP]);
				
				$response['status'] = "1";
				
				$response['msg'] = "success";
				
				$response['confirmation_key'] = $unique_confirmation_key;
				
				$logRecord = 
				[
					'message'   => "User#$UserInfo->email initiated ".$postarray['type']." transfer of amount:".$postarray['elt_amount'],
					'level'     => 'INFO',
					'context'   => 'web user initiated '.$postarray['type'].' transfer'
				];
				LoggerHelper::writeDB($logRecord);				
			}
			else
			{
				$response['msg'] = "Opps! Something went wrong";
			}
		}
		else
		{
			$response['msg'] =  trans('lendo.pleaseEnterAllFields');exit;
		}
		echo json_encode($response);exit;
	}
	
	public function execute_transfer_elt(Request $request)
    {
		$response = array();
		
		$response['status'] = "0"; 
				
		parse_str($_POST['data'], $postarray);
						
		if(!empty($postarray['confirmation_key']) && !empty($postarray['app_otp']) )
		{			
			$userInfo = User::find(Auth::user()->id);		

			if($userInfo->app_otp == $postarray['app_otp'])
			{
				$dataToUpdate = ChangeRequests::where('unique_confirmation_key',$postarray['confirmation_key'])->where('type','6')->where('is_delete',0)->first();
				
				if(isset($dataToUpdate->id))
				{
					$newTransferData = array();
					$newTransferData = unserialize($dataToUpdate->new_value);
					$bc_elt_balance = CommonHelper::balance_format($userInfo->ELT_bc_balance,6);
					$bc_eth_balance = CommonHelper::balance_format($userInfo->ETH_bc_balance,6);
					$uc_elt_balance = User::get_nonconfirm_balance($userInfo->Custom_ETH_Address,'ELT');
					$uc_eth_balance = User::get_nonconfirm_balance($userInfo->Custom_ETH_Address,'ETH');
					if($uc_elt_balance > 0){
						$bc_elt_balance = $bc_elt_balance - $uc_elt_balance;
					}
					if($uc_eth_balance > 0){
						$bc_eth_balance = $bc_eth_balance - $uc_eth_balance;
					}
					$transferType = ($newTransferData['type'] == 'ETH')?2:1;
					if($newTransferData['type'] == 'ELT' && $bc_elt_balance < $newTransferData['elt_amount'])
					{
						$message = "You have insufficient ELT account balance";
						$response['message'] = $message;	
						echo json_encode($response);exit;
					}
					
					if($newTransferData['type'] == 'ETH' && $bc_eth_balance < $newTransferData['elt_amount'])
					{
						$message = "You have insufficient ETH account balance";
						$response['message'] = $message;	
						echo json_encode($response);exit;
					}
								
					$transfer_response = CommonHelper::transfer_elt(Auth::user()->Custom_ETH_Address,$newTransferData['to_address'],$newTransferData['elt_amount'], $transferType);							
					
					if(isset($transfer_response['status']) && $transfer_response['status'] == 1 && $transfer_response['httpcode'] == 200)
					{
						$fees = isset($transfer_response['fee'])?$transfer_response['fee']:0;
						
						$fees = $fees / 1000000000000000000;
						
						$insertData = 
						[
							"txid"=>$transfer_response['txid'],
							"blockNumber"=>0,
							"fees"=>$fees,
							"from_address"=>Auth::user()->Custom_ETH_Address,
							"to_address"=>$newTransferData['to_address'],
							"amount"=>$newTransferData['elt_amount'],
							"confirmations"=>0,
							"type"=>$newTransferData['type'],
							"time_stamp"=>date("Y-m-d H:i:s")
						];
						
						User::insertIntoTable("bc_transations",$insertData);
						
						$pushUser = User::get_user_by_address($newTransferData['to_address']);
						
						if(isset($pushUser->device_type) && isset($pushUser->device_token) && ($pushUser->device_type == 'ios' || $pushUser->device_type == 'android'))
						{
							$pushData = array();								
							$pushData['title'] = '';
							$pushData['sub_title'] = '';
							$pushData['msg'] = "You have received ".$newTransferData['elt_amount']." ELT in your wallet";
							CommonHelper::sendPushNotification($pushUser->device_type, $pushUser->device_token, $pushData);
						}
						
						ChangeRequests::where('id', $dataToUpdate->id)->update(['is_delete' => '1']);
						
						$logRecord = 
						[
							'message'   => "User#$userInfo->email send ".$newTransferData['elt_amount']." ELT from_address:".Auth::user()->Custom_ETH_Address." to ".$pushUser->email." to_address:".$newTransferData['to_address'],
							'level'     => 'INFO',
							'context'   => 'web user transfer ELT'
						];				
						LoggerHelper::writeDB($logRecord);
						
						$response['status'] = "1"; 
						
						$response['msg'] = "You have sent ".$newTransferData['elt_amount']." ".$newTransferData['type']." successfully! <a href='".route('eltwebwallet')."?type=".$newTransferData['type']."'>Close</a>";
						
						$request->session()->flash('success', $response['msg']);
					}
					else
					{						
						$response['msg'] = isset($transfer_response['msg'])?$transfer_response['msg']:trans('lendo.somethingWentWrong');
					}
				}
				else
				{
					$response['msg'] = trans('lendo.OppsSomethingWentWrongText');
				}
			}
			else
			{
				$response['msg'] = trans("lendo.EnteredWrongOTP");
			}
		}
		else
		{
			$response['msg'] =  trans('lendo.pleaseEnterAllFields');exit;
		}

		echo json_encode($response);exit;
	}
	
	public function ajaxgenerateToken(Request $request)
	{
		$response = array();
		$response['status'] = "0";
		$response['code'] = "";
		$response['msg'] = "";
		$loginUser = Auth::user();	
		$loginUserEmail = Auth::user()->email;
		$unique_confirmation_key = uniqid(bin2hex(openssl_random_pseudo_bytes(100)), true);
		$updateResult = User::update_user_by_id(["loan_platform_token"=>$unique_confirmation_key],$loginUser->id);
		if($updateResult)
		{
			$response['status'] = "1";
			$response['code'] = $unique_confirmation_key;
		}
		else{
			$response['msg'] = "Opps! Something went wrong";
		}
		echo json_encode($response);exit;
	}
	
	/**
     * Load eltwebwallet list
    */
    public function eltwebwallet(Request $request)
    {
		$dataForView = array();	
		$dataForView['block_chain_transactions'] = array();
		$user = User::find(Auth::user()->id);		
		if(is_null($user->Custom_ETH_Address))
		{
			$action  = "generate_address";			
			$parameter = array("password"=>md5(time().uniqid()),"user"=>$user->email);
			$response = CommonHelper::call_eth_api($action,$parameter);
			$data = json_decode($response,true);
			if($data['status']=="true" && isset($data['httpcode']) && $data['httpcode'] == 200)
			{
				$address = $data['address'];				
				DB::table('users')->where('id',Auth::user()->id)->update(['Custom_ETH_Address'=>$address]);
				$user->Custom_ETH_Address = $address;				
				$logRecord = 
				[
					'message'   => "User#$user->email generated his address ".$address,
					'level'     => 'INFO',
					'context'   => 'web user generated address'
				];				
				LoggerHelper::writeDB($logRecord);
			}
		}		
		$dataForView['userInfo'] = $user;
		$type = isset($_GET['type'])?$_GET['type']:'ELT';
		$dataForView['type'] = $type;		
		$Transactions = array();		
		$Transactions = User::bc_elt_eth_transactions_by_address($user->Custom_ETH_Address,0,1000,$type);
		foreach($Transactions as $Transaction)
		{			
			$Transaction = (array)$Transaction;
			$Transaction['fees'] = CommonHelper::fees_format($Transaction['fees'],6);
			$Transaction['amount'] = CommonHelper::balance_format($Transaction['amount'],6);
			$Transaction['from_address'] = strtolower($Transaction['from_address']);
			$Transaction['to_address'] = strtolower($Transaction['to_address']);
			$dataForView['block_chain_transactions'][] = $Transaction;
		}
		$dataForView['elt_balance'] = CommonHelper::balance_format($user->ELT_bc_balance,6);
		$dataForView['eth_balance'] = CommonHelper::balance_format($user->ETH_bc_balance,6);
		return view('eltwebwallet', $dataForView);
    }
	
	
	public function serverdatetime(Request $request)
    {
		date_default_timezone_set("CET");
		$seconds = strtotime("2018-06-30 17:00:00") - strtotime(date('Y-m-d H:i:s'));

		$days    = floor($seconds / 86400);
		$hours   = floor(($seconds - ($days * 86400)) / 3600);
		$minutes = floor(($seconds - ($days * 86400) - ($hours * 3600))/60);
		$seconds = floor(($seconds - ($days * 86400) - ($hours * 3600) - ($minutes*60)));
		$response= ['day'=>$days,'hours'=>$hours,'min'=>$minutes,'seconds'=>$seconds];
		echo json_encode($response);
		//echo $days.' Days '.$hours.' Hours '.$minutes.' Minutes '.$seconds.' Seconds';
	}
	
	/**
     * Load eltwebwallet Ajax
    */
    public function eltwebwalletAjax(Request $request)
    {
		$dataForView = array();	
		$dataForView['block_chain_transactions'] = array();
		$user = User::find(Auth::user()->id);		
		if(is_null($user->Custom_ETH_Address))
		{
			$action  = "generate_address";			
			$parameter = array("password"=>md5(time().uniqid()),"user"=>$user->email);
			$response = CommonHelper::call_eth_api($action,$parameter);
			$data = json_decode($response,true);
			if($data['status']=="true" && isset($data['httpcode']) && $data['httpcode'] == 200)
			{
				$address = $data['address'];				
				DB::table('users')->where('id',Auth::user()->id)->update(['Custom_ETH_Address'=>$address]);
				$user->Custom_ETH_Address = $address;
				
				$logRecord = 
				[
					'message'   => "User#$user->email generated his address ".$address,
					'level'     => 'INFO',
					'context'   => 'web user generated address'
				];				
				LoggerHelper::writeDB($logRecord);
			}
		}
		
		$dataForView['userInfo'] = $user;
		
		$type = isset($_GET['type'])?$_GET['type']:'ELT';
		
		$dataForView['type'] = $type;
		
		$Transactions = array();
		
		$Transactions = User::bc_elt_eth_transactions_by_address($user->Custom_ETH_Address,0,1000,$type);
		
		foreach($Transactions as $Transaction)
		{			
			$Transaction = (array)$Transaction;
			
			$Transaction['fees'] = CommonHelper::fees_format($Transaction['fees'],6);
			
			$Transaction['amount'] = CommonHelper::balance_format($Transaction['amount'],6);
			
			$Transaction['from_address'] = strtolower($Transaction['from_address']);
			
			$Transaction['to_address'] = strtolower($Transaction['to_address']);
				
			$dataForView['block_chain_transactions'][] = $Transaction;
		}			

		$dataForView['elt_balance'] = CommonHelper::balance_format($user->ELT_bc_balance,6);
		$dataForView['eth_balance'] = CommonHelper::balance_format($user->ETH_bc_balance,6);
		
		return view('eltwebwalletAjax', $dataForView);
    }
	
	public function ajaxhomebctransactions(Request $request)
	{
		error_reporting(0);
		
		$Address = '0xad6Cf6c69b5e6EEb5e70a61b600fb3A328e0Cc8B';
		
		$total_count = Blockchain::get_address_transaction_total_count($Address);
		
		$filtered_count = 0;
		
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
		
		$column_order = array
		(
			"bc_transations.id",
			"bc_transations.time_stamp",
			"bc_transations.txid",
			"bc_transations.amount",
			"bc_transations.fees",
			"bc_transations.type",
			"bc_transations.blockNumber",
			"bc_transations.confirmations",
			"bc_transations.from_address",
			"bc_transations.to_address"
		);
		
		$data = array();
		
		
		
		$transaction_list = Blockchain::get_datatables_join_blockchain($column_order,$_POST, $Address);
		
		$transaction_count = Blockchain::get_datatables_join_count_blockchain($column_order,$_POST, $Address);
		
		if(!empty($_POST['search_text']) || !empty($_POST['start_date']) || !empty($Address))
		{
			$filtered_count = $transaction_count;
		}
		else
		{
			$filtered_count = $total_count;
		}
		
		if(isset($_POST['start']) && $_POST['start'] == '0')
		{
			$i = $_POST['start'] + 1;
		}
		else
		{
			$i = $_POST['start'] + 1;
		}
		
		
		foreach($transaction_list as $transaction_row)
		{
			$row = array();				
			$row[] = $transaction_row->time_stamp;	
			$row[] = $transaction_row->txid;				
			$row[] = CommonHelper::balance_format($transaction_row->amount,6);
			$row[] = CommonHelper::fees_format($transaction_row->fees,6);
			$row[] = $transaction_row->type;
			$row[] = $transaction_row->blockNumber;
			$row[] = $transaction_row->confirmations;			
			$row[] = $transaction_row->from_address;
			$row[] = $transaction_row->to_address;
			$data[] = $row;
		}
			
		$output = array(
			"draw" => $_POST['draw'],
			"recordsTotal" => $total_count,
			"recordsFiltered" =>$filtered_count,
			"data" => $data,
		);
			
		
		echo json_encode($output);exit;
		
	}
	
	public function setpinrequest(Request $request)
    {
		$response = array();
		
		$response['msg'] = '';
		
		$response['status'] = "0";
		
		$response['confirmation_key'] = "";
		
		parse_str($_POST['data'], $postarray);		
				
		if(!empty($postarray['new_pin']) && !empty($postarray['confirm_pin']) )
		{			
			if($postarray['new_pin'] == $postarray['confirm_pin'] )
			{				
				$userid = Auth::user()->id;
								
				$unique_confirmation_key = uniqid(bin2hex(openssl_random_pseudo_bytes(10)), true);
				
				$changeRequests = ChangeRequests::create([
					'old_value' => 'xxxx',
					'new_value' => $postarray['new_pin'],
					'unique_confirmation_key' => $unique_confirmation_key,
					'type' => 5,
					'user_id' => $userid,
					'is_delete' => 0
				]);
				
				if($changeRequests->id) 
				{
					$google2fa = new Google2FA();
					
					$OTP = $google2fa->generateSecretKey();
				
					$UserInfo = User::find($userid);
					
					self::sendEmailToUserWithOTP($UserInfo, $OTP);
					
					DB::table("users")->where("id",$userid)->update(['app_otp'=>$OTP]);
										
					$response['status'] = "1";
					
					$response['msg'] = "success";
					
					$response['confirmation_key'] = $unique_confirmation_key;
					
					$logRecord = 
					[
						'message'   => "User#".$UserInfo->email." initiated pin:".$postarray['new_pin']." change request",
						'level'     => 'INFO',
						'context'   => 'web user initiated new wallet pin'
					];				
					LoggerHelper::writeDB($logRecord);				
				}
				else
				{
					$response['msg'] = trans("lendo.some_error_occurred");
				}
			}
			else
			{
				$response['msg'] =  trans("lendo.newpin_confirmpin_should_be_same");
			}
		}
		else
		{
			$response['msg'] = trans('lendo.enter_newpin_and_confirmpin');
		}
		
		echo json_encode($response);exit;
	}
	
	public function setpin(Request $request)
    {
		$response = array();
		
		$response['status'] = "0";
					
		$response['msg'] = "";
					
		parse_str($_POST['data'], $postarray);
				
		if(!empty($postarray['new_pin']) && !empty($postarray['confirm_pin']) )
		{			
			if($postarray['new_pin'] == $postarray['confirm_pin'] )
			{
				$userid = Auth::user()->id;
				
				$unique_confirmation_key = uniqid(bin2hex(openssl_random_pseudo_bytes(10)), true);
				
				$changeRequests = ChangeRequests::create([
					'old_value' => 'xxxx',
					'new_value' => $postarray['new_pin'],
					'unique_confirmation_key' => $unique_confirmation_key,
					'type' => 5,
					'user_id' => $userid,
					'is_delete' => 0
				]);
				
				if($changeRequests->id) 
				{
					$google2fa = new Google2FA();
					
					$OTP = $google2fa->generateSecretKey();
				
					$UserInfo = User::find($userid);
					
					self::sendEmailToUserWithOTP($UserInfo, $OTP);
					
					DB::table("users")->where("id",$userid)->update(['app_otp'=>$OTP]);
										
					$response['status'] = "1";
					
					$response['msg'] = "success";
					
					$response['confirmation_key'] = $unique_confirmation_key;
					
					$logRecord = 
					[
						'message'   => "User#$UserInfo->email initiated pin:".$postarray['new_pin']." change request",
						'level'     => 'INFO',
						'context'   => 'web user initiated new wallet pin'
					];				
					LoggerHelper::writeDB($logRecord);				
				}
				else
				{
					$response['msg'] = trans("lendo.some_error_occurred");
				}
			}
			else
			{
				$response['msg'] = trans("lendo.newpin_confirmpin_should_be_same");
			}
		}
		else
		{
			$response['msg'] = trans('lendo.enter_newpin_and_confirmpin');
		}
		
		echo json_encode($response);exit;
	}
	
	public function savepin(Request $request)
    {

		$response = array();
		
		$response['msg'] = '';
		
		$response['status'] = "0";
				
		parse_str($_POST['data'], $postarray);		
		
		$userInfo = Auth::user();
				
		if(!empty($postarray['app_otp']) && !empty($postarray['confirmation_key']) )
		{			
			if($userInfo->app_otp == $postarray['app_otp'])
			{
				$dataToUpdate = ChangeRequests::where('unique_confirmation_key',$postarray['confirmation_key'])->where('is_delete',0)->first();
				
				if(isset($dataToUpdate->id))
				{
					$newpin = $dataToUpdate->new_value;
				
					$userid = Auth::user()->id;
					
					User::set_app_pin($newpin,$userInfo->id);

					$response['status'] = "1";
					
					$response['msg'] = "Pin set successfully <a href='".route('home')."'> Go back </a>";
					
					DB::table("users")->where("id",$userInfo->id)->update(['app_otp'=>NULL]);
					
					ChangeRequests::where('id', $dataToUpdate->id)->update(['is_delete' => '1']);
					
					$logRecord = 
					[
						'message'   => "User#$userInfo->email set wallet pin to $newpin",
						'level'     => 'INFO',
						'context'   => 'web user set new wallet pin'
					];				
					LoggerHelper::writeDB($logRecord);
				}
				else
				{
					$response['msg'] = "Opps! Something went wrong";
				}
			}
			else
			{
				$response['msg'] = "You have entered wrong OTP";
			}
		}
		else
		{
			$response['msg'] = 'Please enter OTP';
		}
		echo json_encode($response);exit;
	}
	
	
	
	/**First time set wallet pin after login
    */
    public function setwalletpin(Request $request)
    {
		$dataForView = array();		
		
		$userInfo = User::find(Auth::user()->id);
		
		if(!is_null($userInfo->app_pin))
		{
			return redirect()->route('home');
		}
		
		$dataForView['userInfo'] = $userInfo;
		
		return view('setwalletpin', $dataForView);
    }
	
	/**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function downline($id)
    {
		if(Auth::user()->downline == 0){
			return redirect()->route('home')->with('error','Unauthorized access to page');
		}
		$dataForView = array();
		
		$referred_users = array();
		
		$parent_level1_users =[];
        
		$parent_level2_users =[];
        
		$parent_level3_users =[];
		
		$parent_level4_users =[];
		
		$parent_level5_users =[];
		
		if(isset($id))
		{
			$downline_id = CommonHelper::encodeDecode($id,'d');
		}
		else
		{
			$downline_id = Auth::user()->id;
		}
		
		
		
		$dataForView['downline_id'] = $downline_id;
		
		$userInfo = User::find($downline_id);
		
		$referred_users = User::where( [ ['referrer_user_id', $downline_id] ] )->orderBy('created_at','DESC')->get();
		
		$dataForView['referred_users'] = $referred_users;
		
		$dataForView['userInfo'] = $userInfo;
		
		if($userInfo->referrer_user_id) 
		{
            $parent_level1_users = User::find($userInfo->referrer_user_id); 
			
            if(isset($parent_level1_users->id) && !empty($parent_level1_users->id)) 
			{
				$parent_level2_users = User::where( [ ['id', $parent_level1_users->referrer_user_id] ] )->first();
			}
            
			if(isset($parent_level2_users->id) && !empty($parent_level2_users->id)) 
			{
                $parent_level3_users = User::where( [ ['id', $parent_level2_users->referrer_user_id] ] )->first();  
            }
			
			if(isset($parent_level3_users->id) && !empty($parent_level3_users->id)) 
			{
                $parent_level4_users = User::where( [ ['id', $parent_level3_users->referrer_user_id] ] )->first();  
            }
			
			if(isset($parent_level4_users->id) && !empty($parent_level4_users->id)) 
			{
                $parent_level5_users = User::where( [ ['id', $parent_level4_users->referrer_user_id] ] )->first();  
            }
			
        }
		
		$Countries = Country::all();
		
		$dataForView['Countries'] = $Countries;
		
		$dataForView['parent_level1_users'] = $parent_level1_users;
		$dataForView['parent_level2_users'] = $parent_level2_users;
		$dataForView['parent_level3_users'] = $parent_level3_users;
		$dataForView['parent_level4_users'] = $parent_level4_users;
		$dataForView['parent_level5_users'] = $parent_level5_users;
		
		$all_transactions_list = Transactions::where( [ ['user_id', $downline_id] ] )->orderBy('created_at','DESC')->get();
		$dataForView['all_transactions_list'] = $all_transactions_list;
		
		$user_logs = Logs::where( [ ['user_id', $downline_id] ] )->orderBy('created_at','DESC')->get();
		$dataForView['user_logs'] = $user_logs;
		
		return view('downline', $dataForView);
		
    }
	
	public function update_bonus_opt(Request $request)
    {
		$response = array();
		$response['msg'] = '';
		$response['status'] = "0";
		$userInfo = Auth::user();
		$userid = Auth::user()->id;
		if($_POST['check_value'] == 1 || $_POST['check_value'] == 0)
		{
			if(FileAttachments::getKYCStatus($userid) == 0 && $_POST['check_value'] == 1){
				$response['on_off'] = 0;
				$response['msg'] = trans('lendo.kycVerificationForBonusSystem');
				echo json_encode($response);exit;
			}
			$check_value = $_POST['check_value'];
			$updateData = ["user_opt_bonus"=>$check_value];
			User::update_user_by_id($updateData,$userid);
			$response['status'] = "1";
			
			if($check_value == 1)
				$response['msg'] = trans("lendo.userOptedForBonusSuccess");
			else
				$response['msg'] = trans("lendo.userRemoveForBonusSuccess");
			
			if($check_value == 1)
			{
				$logRecord = 
				[
					'message'   => "User#$userInfo->email opted for bonus system",
					'level'     => 'INFO',
					'context'   => 'web user opted for bonus system'
				];
			}
			else
			{
				$logRecord = 
				[
					'message'   => "User#$userInfo->email removed himself from bonus system",
					'level'     => 'INFO',
					'context'   => 'web user removed himself from bonus system'
				];
			}	
			LoggerHelper::writeDB($logRecord);
		}
		else
		{
			$response['msg'] = trans('lendo.OppsSomethingWentWrongText');
		}
		$response['on_off'] = $check_value;
		echo json_encode($response);exit;
	}
	
	public function ajaxwithdrawalrequest(Request $request)
	{
		$response = array();
		$user = Auth::user();
		$response['status']='0';
		$response['htmlContent']='';
		if($request->isMethod('post')){
			if($request->currency_name){
				$currency_name = $request->currency_name;
				$withdrawalRequests = Withdrawal::where('user_id','=',$user->id)->where('ledger','=',$currency_name)->orderBy('created_at', 'desc')->get();
				if(count($withdrawalRequests)){
					$response['status']='1';
					foreach($withdrawalRequests as $withdrawalRequest){
						$status='';
						$status = CommonHelper::withdraw_status($withdrawalRequest->status);
						$response['htmlContent'].='<tr>';
						$response['htmlContent'].='<td>'.$withdrawalRequest->transaction_id.'</td>';
						$response['htmlContent'].='<td>'.$withdrawalRequest->created_at.'</td>';
						$response['htmlContent'].='<td>'.CommonHelper::format_float_balance($withdrawalRequest->amount,config("constants.DEFAULT_PRECISION")).'</td>';
						$response['htmlContent'].='<td>'.CommonHelper::format_float_balance($withdrawalRequest->fees,config("constants.DEFAULT_PRECISION")).'</td>';
						$response['htmlContent'].='<td>'.CommonHelper::format_float_balance($withdrawalRequest->transfer_amount,config("constants.DEFAULT_PRECISION")).'</td>';
						$response['htmlContent'].='<td>'.$status.'</td>';
						$response['htmlContent'].='<td>'.$withdrawalRequest->remarks.'</td>';
						$response['htmlContent'].='</tr>';
					}
				}
				else
				{
					$response['htmlContent'] = '<tr class="text-primary text-center"><td colspan="7"><h3 style="color:#000; padding:10px;">No record(s) found</h3></td></tr>';
				}
			}
		}
		echo json_encode($response);exit;		
	}
	
	public function ajaxupdatepricephase(Request $request)
	{
		$response = array();
		$user = Auth::user();
		$response['status']='0';
		$response['msg']='error';		
		if($request->isMethod('post')){
			if($request->data == 'updateNextPricePhase'){
				$currentPhase = Phases::get_current_phase_row();
				if($currentPhase){					
					$nextPhase = Phases::get_next_phase_row($currentPhase->phase_end_date);
					if($nextPhase){						
						/* Update current Phase */
						$data = array();
						$data['status'] = 0;
						$data['updated_at'] = date("Y-m-d H:i:s");
						Transactions::update_table_row('phases', $data, $currentPhase->id);
						
						/* Update Next Phase */
						$data = array();
						$data['status'] = 1;
						$data['updated_at'] = date("Y-m-d H:i:s");
						Transactions::update_table_row('phases', $data, $nextPhase->id);
						
						if($nextPhase->token_price > 0){
							$configuration_data = array();
							$configuration = Configurations::where('valid_to', '=', "9999-12-31")->orderBy('name', 'asc')->getQuery()->get();
							foreach($configuration as $config){
								$configuration_data[$config->name] = $config;
							}
							$configurationUpdate = Configurations::find($configuration_data['Conversion-EUR-ELT']->id);
							$configurationUpdate->valid_to = date('Y-m-d h:i:s');
							$configurationUpdate->save();
							$configurationCreate = Configurations::create([
								'name'          => 'Conversion-EUR-ELT',
								'valid_from'    => date('Y-m-d h:i:s'),
								'valid_to'      => '9999-12-31',
								'defined_value' => 1/$nextPhase->token_price,
								'defined_unit'  => 'ELT',
								'updated_by'    => '1'
							]);
						}
						$response['status'] = '1';
						$response['msg'] = 'success';
					}
				}
			}
		}
		echo json_encode($response);exit;		
	}
	
	private function regsiterChangeWalletRequest($old_address, $new_address, $userid, $type, $currency_name)
	{
		$unique_confirmation_key = uniqid(bin2hex(openssl_random_pseudo_bytes(10)), true);
		
		if($type == 11){
			ChangeRequests::where('user_id', $userid)->where('type',$type)->update(['is_delete' => '2']);
		}
			
		$changeRequests = ChangeRequests::create([
			'old_value'              => $old_address,
			'new_value'              => $new_address,
			'unique_confirmation_key'=> $unique_confirmation_key,
			'type'                   => $type,
			'user_id'                => $userid,
			'is_delete'              => 0
		]);
		if($changeRequests->id){
			$emailData = array(
				'old_value'                 => $old_address,
				'new_value'                 => $new_address,
				'unique_confirmation_key'   => $unique_confirmation_key,
				'id'                        => $changeRequests->id,
				'type'                      => $type,
				'currency_name'				=> $currency_name
			);
			$user = User::find($userid);
			self::SendChangeCryptoWalletAddress($user, $emailData);
			
		}
	}	
}
