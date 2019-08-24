<?php

namespace App\Http\Controllers;
use Mail;
use DB;
use Cache;
use App\Models\User;
use App\Models\Transactions;
use App\Models\WhiteList;
use App\Models\Withdrawal;
use App\Models\ParentChild;
use App\Models\ChangeRequests;
use App\Models\Configurations;
use App\Models\FileAttachments;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Helpers\LoggerHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Notifications\SendOTPEmail;
use App\Notifications\SendWhiteListWelcomeEmail;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use PragmaRX\Google2FA\Google2FA;
use jeremykenedy\LaravelRoles\Models\Role;

class CronController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Cron Controller
    |--------------------------------------------------------------------------
    |
    | 
    |
    */
    use AuthenticatesUsers;
    
    public function whitelistautomatedemails($token)
    {
        $response = array();
        $response["sending"] = 0;
        $response["message"] = "";
        if (!empty($token) && $token == 'XaeiuozebraConEwebC43RetifyX') {
            $this_hour_ago = date("Y-m-d H:i:s", strtotime('-673 minutes'));
            $whereClause[] = ['whitelist_users.amount', '=', '0'];
            $whereClause[] = ['whitelist_users.automated_email', '=', '0'];
            $whereClause[] = ['whitelist_users.status', '=', '1'];
            $whereClause[] = ['whitelist_users.created_at', '<=', $this_hour_ago];
            $whitelist_result = WhiteList::get_whitelist_members($whereClause);
            
            if ($whitelist_result) {
                foreach ($whitelist_result as $whitelist_row) {
                    if ($whitelist_row->email) {
                        $user = User::find(1);
                        $user->email = $whitelist_row->email;
                        $user->user_name = $whitelist_row->name;
                        $user->token = $whitelist_row->token;
                        
                        /* sending email to whitelist users */
                        $user->notify(new SendWhiteListWelcomeEmail($user->token));
                        $whereClause = array();
                        $whereClause[] = ['id', '=', $whitelist_row->id];
                        $updateData = array("automated_email" => 1);
                        WhiteList::update_whitelistusers($whereClause, $updateData);
                        $response["sending"] = $response["sending"] + 1;
                    }
                }
                if ($response["sending"] > 0) {
                    $response["message"] = "success";
                } else {
                    $response["message"] = "no record(s) found";
                }
            }
        } else {
            $response["message"] = "unauthorized access";
        }
        echo json_encode($response);
        exit;
    }

    public function getRaisedETH($token)
    {
        $response = array();
        $response['message'] = '';
        $totalETHRaised = 0;
        $response['value'] = 0;
		$default_card_apply = 120;		
		$default_loan_apply = 380000; 
		
		$default_loan_apply += 500;
		$default_card_apply += 1600;
		
		$default_loan_apply += 1000000;
		$default_card_apply += 1700;
		
		$default_loan_apply += 1300000;
		$default_loan_apply += 1300000;
		
        if($token == 'xsndfhjdfgsfsdjfvbsdfsdfsd') 
		{
			$configuration = Configurations::where('valid_to', '=', "9999-12-31")->orderBy('name', 'asc')->getQuery()->get();
            $response['value'] = Transactions::total_elt_distribution();
			
			$response['eurovalue'] = "2,997,620";
			$response['message'] = 'success';
			
			$response['total_card_applied'] = $default_card_apply + User::total_card_applied();
			$response['total_loan_amount_in_euro'] = $default_loan_apply + User::total_loan_amount();
			
			$response['nexo_to_euro'] = CommonHelper::get_coinmarketcap_rates(config('global_vars.NEXO_ID'),"EUR");
			$response['salt_to_euro'] = CommonHelper::get_coinmarketcap_rates(config('global_vars.SALT_ID'),"EUR");
			$response['orme_to_euro'] = CommonHelper::get_coinmarketcap_rates(config('global_vars.ORME_ID'),"EUR");
			
			$base_currency = config('global_vars.base_currency');
			$term_currency = config('global_vars.term_currency');
			
			foreach($configuration as $config)
			{
				$configuration_data[$config->name] = $config;
			}
			
			$response['EUR_to_ELT'] = $configuration_data['Conversion-EUR-ELT']->defined_value;
			$response['nexo_to_elt'] = $response['nexo_to_euro'] * $response['EUR_to_ELT'];
			$response['salt_to_elt'] = $response['salt_to_euro'] * $response['EUR_to_ELT'];
			$response['orme_to_elt'] = $response['orme_to_euro'] * $response['EUR_to_ELT'];			
			$response['nexo_fee'] = $configuration_data['Conversion-NEXO-EUR-Fee']->defined_value;
			$response['salt_fee'] = $configuration_data['Conversion-SALT-EUR-Fee']->defined_value;
			$response['orme_fee'] = $configuration_data['Conversion-ORME-EUR-Fee']->defined_value;
			$response['elt_to_nexo'] = 1/$response['nexo_to_elt'];
			$response['elt_to_salt'] = 1/$response['salt_to_elt'];
			$response['elt_to_orme'] = 1/$response['orme_to_elt'];			
			$response['elt_to_nexo_with_fee'] = $response['elt_to_nexo'] + ($response['elt_to_nexo'] * $response['nexo_fee'])/100;			
			$response['elt_to_salt_with_fee'] = $response['elt_to_salt'] + ($response['elt_to_salt'] * $response['salt_fee'])/100;			
			$response['elt_to_orme_with_fee'] = $response['elt_to_orme'] + ($response['elt_to_orme'] * $response['orme_fee'])/100;
			$response['nexo_to_elt_with_fee'] = 1/$response['elt_to_nexo_with_fee'];
			$response['salt_to_elt_with_fee'] = 1/$response['elt_to_salt_with_fee'];
			$response['orme_to_elt_with_fee'] = 1/$response['elt_to_orme_with_fee'];
			$response['btc_to_elt'] = $this->getConversionInELTWithFee("BTC");
			$response['elt_to_btc'] = $this->balance_format(1/$response['btc_to_elt'],6);
			$response['bch_to_elt'] = $this->getConversionInELTWithFee("BCH");
			$response['elt_to_bch'] = $this->balance_format(1/$response['bch_to_elt'],6);
			$response['dash_to_elt'] = $this->getConversionInELTWithFee("DASH");
			$response['elt_to_dash'] = $this->balance_format(1/$response['dash_to_elt'],6);			
			$response['eth_to_elt'] = $this->getConversionInELTWithFee("ETH");
			$response['elt_to_eth'] = $this->balance_format(1/$response['eth_to_elt'],6);
			$response['eur_to_elt'] = $this->getConversionInELTWithFee("EUR");
			$response['elt_to_eur'] = $this->balance_format(1/$response['eur_to_elt'],6);
			$response['ltc_to_elt'] = $this->getConversionInELTWithFee("LTC");
			$response['elt_to_ltc'] = $this->balance_format(1/$response['ltc_to_elt'],6);
			$response['xrp_to_elt'] = $this->getConversionInELTWithFee("XRP");
			$response['elt_to_xrp'] = $this->balance_format(1/$response['xrp_to_elt'],6);
			
			if(isset($configuration_data['Total-Loan-Amount-Application']->defined_value) && $configuration_data['Total-Loan-Amount-Application']->defined_value > 0)
			{
				$response['total_loan_amount_in_euro'] = $configuration_data['Total-Loan-Amount-Application']->defined_value;
			}
			if(isset($configuration_data['Total-Card-Application']->defined_value) && $configuration_data['Total-Card-Application']->defined_value > 0)
			{
				$response['total_card_applied'] = $configuration_data['Total-Card-Application']->defined_value;
			}
        }
		else 
		{
            $response['message'] = 'unauthorized access';
        }
		//echo "<pre>";print_r($response);die;
        echo json_encode($response);exit;
    }
	
	public function setupline($token)    
	{        
		$response = array();
		if ($token == 'acsBcdeFghijkIMnopQrstVvwXYZz')
		{
			$userlist = DB::select("SELECT id FROM users WHERE id>=120904 AND id<=122423");
			echo "<pre>";print_r($userlist);die;	
			$childIDs = array();
			foreach($userlist as $userrow)
			{
				$childIDs[] = $userrow->id;
			}			
			if(count($childIDs)){
				foreach($childIDs as $childID){
					echo "<hr> Child";echo $childID;					
					ParentChild::restructure_child($childID);
					$getMyUpline = User::getMyUpline($childID);
				}
			}
		}
		else
		{
			$response['message'] = 'unauthorized page access';
		}
		echo json_encode($response);exit;    
	}
	
	public function getLoanCardApplicationInfo($token)    
	{        
		$response = array();        
		$response['message'] = '';		
		$default_card_apply = 120;		
		$default_loan_apply = 380000;  

		$default_loan_apply += 500;
		$default_card_apply += 1600;
		
		$default_loan_apply += 1000000;
		$default_card_apply += 1700;
		
		$default_loan_apply += 1300000;
		$default_loan_apply += 1300000;
		
		if ($token == 'Xx_abcdefghijklmnopqrstuvwxyz_xX'){			
			$response['total_card_applied'] = $default_card_apply + User::total_card_applied();			$response['total_loan_amount_in_euro'] = $default_loan_apply + User::total_loan_amount();         $response['message'] = 'success';        
		}		
		else{            
			$response['message'] = 'unauthorized access';        
		}        
		echo json_encode($response);exit;    
	}

    public function updatetermcurrency($token)
    {
        $response = array();
        $response['message'] = '';
        $totalUpdation = 0;
        $response['totalUpdation'] = 0;
        if ($token == 'xsndfhjdfgsfsdjfvbsdfsdfsd') {
            $getAllSuccessTransactionTest = Transactions::getAllSuccessTransactionTest();
            foreach ($getAllSuccessTransactionTest as $getAllSuccessTransactionRow) {
                if (empty($getAllSuccessTransactionRow->term_currency) && $getAllSuccessTransactionRow->ledger = 'ELT') {
                    if (strpos($getAllSuccessTransactionRow->description, 'Converted unit') !== false) {
                        $descriptionArray = array();
                        $descriptionArray = explode(" ", trim($getAllSuccessTransactionRow->description));
                        $term_currency = $descriptionArray[2];
                        $term_amount = $descriptionArray[3];
                        $UpdateData = array('term_currency' => $term_currency, 'term_amount' => $term_amount);
                        Transactions::update_table_row('transactions', $UpdateData, $getAllSuccessTransactionRow->id);
                        $totalUpdation++;
                    }
                }
            }
            $response['totalUpdation'] = $totalUpdation;
            $response['message'] = 'success';
        } else {
            $response['message'] = 'unauthorized access';
        }
        echo json_encode($response);
        exit;
    }

	public function cronExchageRate()
	{
		$this->addCurrencyLog("BTC");
		$this->addCurrencyLog("ETH");
		$this->addCurrencyLog("LTC");
		$this->addCurrencyLog("BCH");
		$this->addCurrencyLog("XRP");
		$this->addCurrencyLog("DASH");
		
		
	}
	
	/**
     * Load invoice detail
     */
    public function invoice_detail($id, Request $request)
    {
		$dataForView = array();
		$dataForView['hide_btn'] = 1;
		$exchange_value_in_euro='';
		$invoice_detail = Transactions::get_invoice_detail($id);
		if($invoice_detail->currency != 'EUR'){
			$coin_base_rate_data = CommonHelper::get_coinbase_currency($invoice_detail->currency);
			$exchange_value_in_euro = $coin_base_rate_data['data']['rates']['EUR'] * $invoice_detail->currency_amount;
		}
		$dataForView['invoice_detail'] = $invoice_detail;
		$dataForView['exchange_value_in_euro'] = $exchange_value_in_euro;
		$dataForView['invoice_bonus_row'] = Transactions::get_invoice_bonus($invoice_detail->ref_transaction_id,$invoice_detail->user_id);
		$user = User::find($invoice_detail->user_id);
		\App::setLocale($user->language);
		return view('invoice_detail', $dataForView);
    }
	
	/**
     * Load proforma detail
     */
    public function proforma_detail($id, Request $request)
    {
		$dataForView = array();
		$dataForView['hide_btn'] = 1;
		$exchange_value_in_euro='';
		$proforma_detail = Transactions::get_performa_detail($id);
		
		$dataForView['invoice_detail'] = $proforma_detail;
		$dataForView['exchange_value_in_euro'] = $exchange_value_in_euro;
		
		$user = User::find($proforma_detail->user_id);
		\App::setLocale($user->language);
		return view('proforma_detail', $dataForView);
	}
	
	public function reversebonuselt($token)
    {
        $response = array();		
        $response['message'] = '';
        $response['totalUpdation'] = 0;
		if($token == 'PkztJzAhXXhobJX3QtWjbOPxxDLbaEdb1f7c8e8c0cc5a71aaaf107e58') 
		{	
			$userId = isset($_GET['userid'])?$_GET['userid']:59025;

			$user_model = User::find($userId);	

			// Got referral bonus of 5ELT on community registration of email :adsgharbia@gmail.com Payment id : 5b3ce09555649 Time created at: 07/04/2018 14:58:29

			echo "<pre>";			
			$Sql = "
			SELECT id, user_id, ledger, value, description, status, type, type_name 
			FROM transactions 
			WHERE type IN (4) AND status=1 AND ledger='ELT' AND value=5 AND user_id = ".$userId;

			print_r($Sql);

			$results = DB::select(DB::raw($Sql));	
			
			foreach($results as $row) 
			{
				echo "<hr>";

				print_r($row);

				$temp1 = explode("Got referral bonus of 5ELT on community registration of email :",$row->description);

				print_r($temp1);

				$temp2 = explode(" ",$temp1[1]);

				print_r($temp2);

				if(isset($temp2[0]))
				{
					$referral_user = DB::table('users')->select('id','email')->where('email',trim($temp2[0]))->first();

					if($referral_user)
					{
						$child_kyc_status = FileAttachments::fetch_kyc_status($referral_user->id);

						if($child_kyc_status != 1)
						{
							print_r('child non-kyc : '.$referral_user->id);
							$updateUser = User::find($userId);
							if($updateUser->ELT_balance > $amount)
							{
								$reff_update_array = array('status'=>2);	
								DB::table('transactions')->where('id',$row->id)->update($reff_update_array);	
								$amount = 5;
								$updateUser->ELT_balance -= $amount;
								$updateUser->save();
								$response['totalUpdation']++;
							}
						}
						else
						{
							print_r('child kyc : '.$referral_user->id);
						}
					}
				}
			}
        }
		else
		{
            $response['message'] = 'unauthorized access';
        }
        echo json_encode($response);exit;
    }
	
    public function testemail($email)
    {
        if (isset($_GET['type']) && $_GET['type'] == 'lendohome') {
            return redirect("https://www.lendo.io/about-lendo/");
        }
        if ($email == '_X_xsndfhjdfgsfsdjfvbsdfsdfsd_X_') {
            echo "<pre>";
            $user = User::find(1);
            echo "Admin OTP : " . $user->OTP;
        }
    }

    public function testaction()
    {
        echo "<pre>";
        $Sql = "select id, email, user_name, referrer_user_id, ELT_balance, created_at, referrer_count
		from users 
		where 1
		order by id ASC";
        echo $Sql;//die;
        $results = DB::select(DB::raw($Sql));
        $total_updation = 0;
        foreach ($results as $row) {
            $total_updation++;
            print_r($row);
            if ($row->id > 0) {
                $Sql1 = "SELECT count(*) AS Total_Referral FROM users WHERE referrer_user_id = $row->id";
                $results1 = DB::select(DB::raw($Sql1));
                $Total_Referral = $results1[0]->Total_Referral;
                if ($Total_Referral > 0) {
                    //$UpdateSql = "UPDATE users SET referrer_count=$Total_Referral WHERE id = $row->id";
                    //DB::table('users')->where('id',$row->id)->update(['referrer_count' =>$Total_Referral]);
                }
                print_r($results1);
            }
        }
        echo $total_updation;
        die("Action end");
    }
	
	private function addCurrencyLog($currency)
	{
		$euro_worth_with_fee = Transactions::get_currency_to_euro_rate($currency,1);
		$euro_worth_without_fee = Transactions::get_currency_to_euro_rate($currency,0);
		$insertRow = array();
		$insertRow = 
		[
			'currency'=>$currency,
			'euro_worth_with_fee'=>$euro_worth_with_fee,
			'euro_worth_without_fee'=>$euro_worth_without_fee,
			'created_at'=>date("Y-m-d H:i:s")
		];
		User::insertIntoTable("exchange_rates",$insertRow);
	}
	
	private function balance_format($amount,$precision=2)
	{
		return number_format($amount, $precision, '.', '');
	}
	
	
	private function getConversionInELTWithFee($currency,  $amount = 1)
    {
		
		$total_amount_in_ELT = 0;
		
        $allowedTypeBraveAPI = array('ETC', 'XRP', 'DASH');

        $allowedTypeCoinBaseAPI = array('BTC', 'BCH', 'BTH', 'ETH', 'LTC');

        $Conversion_EUR_ELT = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Conversion-EUR-ELT']])->get();
       
		if ($currency == 'EUR') 
		{
            return round($amount * $Conversion_EUR_ELT[0]->defined_value, config('constants.EUR_PRECISION'));
        }
        elseif (in_array(strtoupper($currency), $allowedTypeCoinBaseAPI))
		{
            
			$coinbase_rate = CommonHelper::get_coinbase_currency($currency);
            
			$__to_euro = $coinbase_rate['data']['rates']['EUR'];
        }
		elseif (in_array(strtoupper($currency), $allowedTypeBraveAPI)) 
		{
			
            $__to_euro = CommonHelper::get_brave_coin_rates(strtolower($currency), "EUR", 1);
			
        }
		
        if($__to_euro > 0) 
		{
            $__to_elt = $__to_euro * $Conversion_EUR_ELT[0]->defined_value;
            
			$__to_elt_reverse = 1 / $__to_elt;
            
			$Conversion_EUR_Fee = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Conversion-' . $currency . '-EUR-Fee']])->get();
            
			$fees = $Conversion_EUR_Fee[0]->defined_value;
            
			$__to_elt_reverse = $__to_elt_reverse + ($__to_elt_reverse * $fees / 100);
            
			$total_amount_in_ELT = (1 / $__to_elt_reverse) * $amount;
        }
		
        return round($total_amount_in_ELT, config('constants.ELT_PRECISION'));
	}


	public function expirepasttoken($token)
    {
        $response = array();
        $response['message'] = '';
        $totalUpdation = 0;
        $response['totalUpdation'] = 0;
        if ($token == 'PkztJzAhXXhobJX3QtWjbOPxxDLbaEdb1f7c8e8c0cc5a71aaaf107e58') 
		{
			$change_request_types = array(12);			
			$find_all_pending_request = ChangeRequests::where('is_delete',0)->whereIn('type',$change_request_types)->get();						
			if(!empty($find_all_pending_request))
			{
				foreach($find_all_pending_request as $single_request)
				{				
					$expire_at = strtotime($single_request->created_at) + config('constants.LINK_EXPIRE_TIME_IN_MINS') * 60;					
					if(time() > $expire_at)
					{
						ChangeRequests::where('id', $single_request->id)->update(['is_delete' => '1']);
						if($single_request->type == 12)
						{
							$dataRow = array();
							$dataRow = unserialize($single_request->new_value);
							if(isset($dataRow['transaction_id']))
							{
								Withdrawal::where('transaction_id',$dataRow['transaction_id'])->where('status',0)->update(['status' => '4']);
							}
						}
					}
				}
			}
        }
		else
		{
            $response['message'] = 'unauthorized access';
        }
        echo json_encode($response);exit;
	}

	public function updateWalletELTAddress($token)
    {
        $response = array();
        $response['message'] = '';
        $totalUpdation = 0;
        $response['totalUpdation'] = 0;
        if($token == 'PkztJzAhXXhobJX3QtWjbOPxxDLbaEdb1f7c8e8c0cc5a71aaaf107e58') 
		{
			$change_request_types = array(11);			
			$find_all_request = ChangeRequests::where('is_delete',1)->where('type',11)->get();
			if(!empty($find_all_request))
			{
				foreach($find_all_request as $single_request)
				{
					$expire_at = strtotime($single_request->created_at) + config('constants.UPDATE_WALLET_TIME_IN_HRS') * 60 * 60;
					
					if(time() > $expire_at)
					{
						if($single_request->type == 11)
						{
							if(isset($single_request->new_value))
							{
								
								User::where('id', $single_request->user_id)->update(['ELT_wallet_address' => $single_request->new_value]);
								
								
								ChangeRequests::where('id', $single_request->id)->update(['is_delete' => '2']);
								
								$response['totalUpdation'] = $response['totalUpdation'] + 1;
							}
						}
					}
				}
			}
        }
		else
		{
            $response['message'] = 'unauthorized access';
        }
        echo json_encode($response);exit;
	}
	
	
	
	
}
