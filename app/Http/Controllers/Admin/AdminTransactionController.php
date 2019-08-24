<?php

namespace App\Http\Controllers\Admin;
use DB;
use App\Models\User;
use App\Models\Configurations;
use App\Models\Transactions;
use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AdminTransactionController extends Controller
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
	
    public function index()
    {
        $user = Auth::user();				
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		
		$dataForView = array();
		$is_export_accessible = '1';//User::check_page_access('admin.exportusers',Auth::user()->custom_role);
		$dataForView['is_export_accessible'] = $is_export_accessible;
        if(is_null($user->OTP))
        {
			
			
			$get_user_stats = $this->getTransactionStats('','');			
			$total_record = $get_user_stats['user_total'];
			
			$dataForView['total_btc'] = CommonHelper::format_float_balance(Transactions::get_total_transaction_sum("BTC",''), config("constants.DEFAULT_PRECISION"));
			$dataForView['total_eth'] = CommonHelper::format_float_balance(Transactions::get_total_transaction_sum("ETH",''), config("constants.DEFAULT_PRECISION"));
			$dataForView['total_eur'] = CommonHelper::format_float_balance(Transactions::get_total_transaction_sum("EUR",''), config("constants.DEFAULT_PRECISION"));
			$dataForView['total_elt'] = CommonHelper::format_float_balance(Transactions::get_total_transaction_sum("ELT",''), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_btc_today'] = CommonHelper::format_float_balance(Transactions::get_total_transaction_sum("BTC",date("Y-m-d")), config("constants.DEFAULT_PRECISION"));
			$dataForView['total_eth_today'] = CommonHelper::format_float_balance(Transactions::get_total_transaction_sum("ETH",date("Y-m-d")), config("constants.DEFAULT_PRECISION"));
			$dataForView['total_eur_today'] = CommonHelper::format_float_balance(Transactions::get_total_transaction_sum("EUR",date("Y-m-d")), config("constants.DEFAULT_PRECISION"));
			$dataForView['total_elt_today'] = CommonHelper::format_float_balance(Transactions::get_total_transaction_sum("ELT",date("Y-m-d")), config("constants.DEFAULT_PRECISION"));
						

            return view('admin.transaction', $dataForView);
        }
        else
        {
            return redirect()
                    ->route('admin.twoSetpVarification')
                        ->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	private function getTransactionStats($start,$end)
	{
		return $this->get_transaction_stats($start, $end);
	}
	
	public  function get_transaction_stats($start='', $end='')
	{
		$userStatsData = array();
		$userStatsData['user_total'] = 0;
		
		if(!empty($start) && !empty($end))
		{
			$start_date_array = explode("/",$start);
			$end_date_array = explode("/",$end);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			$start_datetime = $start_date.' 00:00:00';
			$end_datetime = $end_date.' 23:59:59';
			$user_total = DB::select( DB::raw( "SELECT count(*) AS transaction_total FROM transactions WHERE created_at>='".$start_datetime."' AND created_at<='".$end_datetime."'" ) );
			$userStatsData['transactions_total'] = $transactions_total[0]->transaction_total;
			
		}
		else
		{
			$transactions_total = DB::select( DB::raw("SELECT count(*) AS transaction_total FROM users WHERE role=2") );
			$userStatsData['transactions_total'] = $transactions_total[0]->transaction_total;
		
		}
		
		return $userStatsData;	
	}
	
	public function cointransactions()
    {
        $user = Auth::user();				
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		
		$dataForView = array();
		
        if(is_null($user->OTP))
        {
			$get_all_coin_transactions = Transactions::get_all_coin_transactions();
						
			$total_nexo = 0;
			$total_salt = 0;
			$total_orme = 0;
			
			$today_nexo = 0;
			$today_salt = 0;
			$today_orme = 0;
			
			$today_date = date("Y-m-d");
			
			foreach($get_all_coin_transactions as $transaction_row)
			{
				$transaction_row = (array)$transaction_row;
				if($transaction_row['term_currency'] == 'NEXO')
				{
					$total_nexo = $total_nexo + $transaction_row['term_amount'];
				}
				elseif($transaction_row['term_currency'] == 'SALT')
				{
					$total_salt = $total_salt + $transaction_row['term_amount'];
				}
				elseif($transaction_row['term_currency'] == 'ORME')
				{
					$total_orme = $total_orme + $transaction_row['term_amount'];
				}
				
				if($transaction_row['term_currency'] == 'NEXO' && $transaction_row['today_date'] == $today_date)
				{
					$today_nexo = $today_nexo + $transaction_row['term_amount'];
				}
				elseif($transaction_row['term_currency'] == 'SALT' && $transaction_row['today_date'] == $today_date)
				{
					$today_salt = $today_salt + $transaction_row['term_amount'];
				}
				elseif($transaction_row['term_currency'] == 'ORME' && $transaction_row['today_date'] == $today_date)
				{
					$today_orme = $today_orme + $transaction_row['term_amount'];
				}				
			}
			
			$dataForView['total_nexo'] = CommonHelper::format_float_balance($total_nexo, config("constants.DEFAULT_PRECISION"));
			$dataForView['total_salt'] = CommonHelper::format_float_balance($total_salt, config("constants.DEFAULT_PRECISION"));
			$dataForView['total_orme'] = CommonHelper::format_float_balance($total_orme, config("constants.DEFAULT_PRECISION"));
			$dataForView['today_nexo'] = CommonHelper::format_float_balance($today_nexo, config("constants.DEFAULT_PRECISION"));
			$dataForView['today_salt'] = CommonHelper::format_float_balance($today_salt, config("constants.DEFAULT_PRECISION"));
			$dataForView['today_orme'] = CommonHelper::format_float_balance($today_orme, config("constants.DEFAULT_PRECISION"));
			
            return view('admin.cointransactions', $dataForView);
        }
        else
        {
            return redirect()
                    ->route('admin.twoSetpVarification')
                        ->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function allpayments()
    {		
        $user = Auth::user();				
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		
		$dataForView = array();
        if(is_null($user->OTP))
        {
			$dataForView['total_btc'] = CommonHelper::format_float_balance(Transactions::get_total_payments("BTC",''), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_btc_today'] = CommonHelper::format_float_balance(Transactions::get_total_payments("BTC",date("Y-m-d")), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_eth'] = CommonHelper::format_float_balance(Transactions::get_total_payments("ETH",''), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_eth_today'] = CommonHelper::format_float_balance(Transactions::get_total_payments("ETH",date("Y-m-d")), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_eur'] = CommonHelper::format_float_balance(Transactions::get_total_payments("EUR",''), config("constants.EUR_PRECISION"));
			
			$dataForView['total_eur_today'] = CommonHelper::format_float_balance(Transactions::get_total_payments("EUR",date("Y-m-d")), config("constants.EUR_PRECISION"));
			
			$dataForView['total_ltc'] = CommonHelper::format_float_balance(Transactions::get_total_payments("LTC",''), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_ltc_today'] = CommonHelper::format_float_balance(Transactions::get_total_payments("LTC",date("Y-m-d")), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_bch'] = CommonHelper::format_float_balance(Transactions::get_total_payments("BCH",''), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_bch_today'] = CommonHelper::format_float_balance(Transactions::get_total_payments("BCH",date("Y-m-d")), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_xrp'] = CommonHelper::format_float_balance(Transactions::get_total_payments("XRP",''), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_xrp_today'] = CommonHelper::format_float_balance(Transactions::get_total_payments("XRP",date("Y-m-d")), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_dash'] = CommonHelper::format_float_balance(Transactions::get_total_payments("DASH",''), config("constants.DEFAULT_PRECISION"));
			
			$dataForView['total_dash_today'] = CommonHelper::format_float_balance(Transactions::get_total_payments("DASH",date("Y-m-d")), config("constants.DEFAULT_PRECISION"));
			
            return view('admin.allpayments', $dataForView);
        }
        else
        {
            return redirect()
                    ->route('admin.twoSetpVarification')
                        ->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	

    public function accounting()
    {
        $user = Auth::user();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		$dataForView = array();
        if(is_null($user->OTP))
        {
			if(isset($_GET['tabname'])){
				$tabname = $_GET['tabname'];
			}
			else{
				$tabname = 'sales';
			}
			$dataForView['tabname'] = $tabname;	
			$current_week_date_range = CommonHelper::current_week_date_range();
			$dataForView['week_start_date'] = date("Y-m-d",strtotime($current_week_date_range['start_week']));
			$dataForView['week_end_date'] = date("Y-m-d",strtotime($current_week_date_range['end_week']));
			$dataForView['month_start_date'] = date('Y-m-01');
			$dataForView['month_end_date']  = date('Y-m-t');
			$dataForView['year_start_date'] = date('Y-01-01');
			$dataForView['year_end_date']  = date('Y-12-31');
			$currencyLoopData = array();			
			$currencyList = array('BTC','ETH','EUR','BCH','LTC','XRP','DASH');
			$elt_euro = Transactions::get_current_elt_euro_rate('elt_euro');
			$total_elt_sales = Transactions::get_user_elt_worth_in_euro_reset_date(array(),date("Y-m-d"),date("Y-m-d"));			
			if($tabname == 'sales')
			{
				$currencyLoopData["ELT"] = array
				(
					"live_rate_in_euro"=>$elt_euro,
					"total_sales"=>$total_elt_sales[0]->elt_worth_total,
					"at_purchase"=>$total_elt_sales[0]->euro_worth_total,
					"elt_amount"=>$total_elt_sales[0]->elt_worth_total
				);
			}			
			$get_periodic_data = Transactions::get_periodic_table_data('today','','',$currencyList,$tabname);
			$dataForView["get_periodic_data"] = $get_periodic_data;
			$total_sum = array("total_sales"=>0,"at_purchase"=>0,"live_rate_in_euro"=>0,"elt_amount"=>0);
			foreach($currencyList as $currencyName)
			{
				$rowData = array();
				$rowData["live_rate_in_euro"] =  CommonHelper::format_float_balance(Transactions::get_currency_to_euro_rate($currencyName),config("constants.EUR_PRECISION"));
				if(isset($get_periodic_data[$currencyName]))
				{
					$rowData["total_sales"] = CommonHelper::format_float_balance($get_periodic_data[$currencyName]->total_sales,config("constants.DEFAULT_PRECISION"));
					$rowData["at_purchase"] = CommonHelper::format_float_balance($get_periodic_data[$currencyName]->total_sale_in_euro, config("constants.EUR_PRECISION"));
					$rowData["elt_amount"] = CommonHelper::format_float_balance($get_periodic_data[$currencyName]->total_elt,config("constants.DEFAULT_PRECISION"));
				}
				else
				{
					$rowData["total_sales"] = 0;
					$rowData["at_purchase"] = 0;
					$rowData["elt_amount"] = 0;
				}
				$total_sum["total_sales"]+=$rowData["total_sales"];
				$total_sum["at_purchase"]+=$rowData["at_purchase"];
				$total_sum["live_rate_in_euro"]+=$rowData["live_rate_in_euro"];
				$total_sum["elt_amount"]+=$rowData["elt_amount"];
				$currencyLoopData[$currencyName] = $rowData;	
			}		
			
			$total_sum['at_purchase'] = CommonHelper::format_float_balance($total_sum['at_purchase'], config("constants.EUR_PRECISION"));
			
			$total_sum['live_rate_in_euro'] = CommonHelper::format_float_balance($total_sum['live_rate_in_euro'], config("constants.EUR_PRECISION"));
			
			$currencyLoopData["Total"] =  $total_sum;
			$dataForView['currencyLoopData'] = $currencyLoopData;
			$dataForView['total_sum'] = $total_sum;
			
			//echo "<pre>";print_r($dataForView);die;
			
            return view('admin.accounting',$dataForView);
        }
        else
        {
            return redirect()
                    ->route('admin.twoSetpVarification')
                        ->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
}
