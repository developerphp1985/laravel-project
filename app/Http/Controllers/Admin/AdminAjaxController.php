<?php
namespace App\Http\Controllers\Admin;
use DB;
use Mail;
use Carbon\Carbon;
use App\Models\Logs;
use App\Models\User;
use App\Models\Phases;
use App\Models\Country;
use App\Models\Proforma;
use App\Models\Blockchain;
use App\Models\Withdrawal;
use App\Models\Activation;
use App\Models\ParentChild;
use App\Models\Transactions;
use App\Models\StoredData;
use App\Models\AdminComments;
use App\Models\Configurations;
use App\Models\ChangeRequests;
use App\Helpers\LoggerHelper;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Traits\ActivationTrait;
use App\Notifications\SendNewActivationEmail;
use App\Notifications\SendChangeNotification;
use App\Notifications\SendWhiteListWelcomeEmail;
use App\Notifications\SendEmailForWithdrawApproval;
use App\Notifications\SendEmailForWithdrawRejection;
use jeremykenedy\LaravelRoles\Models\Role;

class AdminAjaxController extends Controller
{
	public function SendEmailForWithdrawApproval(User $user, $emailData)
    {
        $user->notify(new SendEmailForWithdrawApproval($emailData));
    }
	
	public function SendEmailForWithdrawRejection(User $user, $emailData)
    {
        $user->notify(new SendEmailForWithdrawRejection($emailData));
    }
	
	public function ajaxusers()
    {
        $user = Auth::user();
        $total_count = DB::table('users')->where('role', 2)->count();
        $output = array("draw" => '', "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []);
        if (is_null($user->OTP)) {
            $column_order = array
            (
                "users.created_at",
                "users.user_name",
                "users.email",
                "usr.email",
                "users.registration_ip",
                "users.city",
                "countries.name",
                "users.ELT_balance",
                "users.referrer_count",
                "users.created_at"
            );

            $users_list = User::get_datatables_users($column_order, $_POST);

            $users_count = User::get_datatables_users($column_order, $_POST, 1);

            if (!empty($_POST['search_text']) || !empty($_POST['start_date'])) {
                $filtered_count = $users_count;
            } else {
                $filtered_count = $total_count;
            }
			
            if (isset($_POST['start']) && $_POST['start'] == '0') {
                $i = $_POST['start'] + 1;
            } else {
                $i = $_POST['start'] + 1;
            }

            $data = array();

			$is_tab_accessible = User::check_page_access('user-tab-info',Auth::user()->custom_role);
			
            foreach ($users_list as $users_row) {
				
                $row = array();

                $row[] = $i;

                $row[] = $users_row->first_name . ' ' . $users_row->last_name;

				if(!CommonHelper::isUserAllowedForSubAdmin(Auth::user()->custom_role,$users_row->id))
				{
					$row[] = $users_row->email;
				}
				else
				{
					$row[] = "<a href='" . route('admin.usersdetail', ['id' => $users_row->id]) . "'>" . $users_row->email . "</a>";
				}

				if(!CommonHelper::isUserAllowedForSubAdmin(Auth::user()->custom_role,$users_row->referrelId))
				{
					$row[] = $users_row->referrelEmail;
				}
				else
				{
					$row[] = "<a href='" . route('admin.usersdetail', ['id' => $users_row->referrelId]) . "'>" . $users_row->referrelEmail . "</a>";
				}
				
				if($is_tab_accessible)
				{
					$row[] = $users_row->registration_ip;
					$row[] = $users_row->city;
					$row[] = $users_row->country_name;
					if(CommonHelper::is_float_number($users_row->ELT_balance))
					{
						$row[] = CommonHelper::format_float_balance($users_row->ELT_balance, config("constants.DEFAULT_PRECISION"));
					}
					else
					{
						$row[] = $users_row->ELT_balance;
					}
					/*
					if(CommonHelper::is_float_number($users_row->BTC_balance))
					{
						$row[] = CommonHelper::format_wallet_balance($users_row->BTC_balance, config("constants.DEFAULT_PRECISION"));
					}
					else
					{
						$row[] = $users_row->BTC_balance;
					}                
					if(CommonHelper::is_float_number($users_row->ETH_balance))
					{
						$row[] = CommonHelper::format_wallet_balance($users_row->ETH_balance, config("constants.DEFAULT_PRECISION"));
					}
					else
					{
						$row[] = $users_row->ETH_balance;
					}
					if(CommonHelper::is_float_number($users_row->EUR_balance))
					{
						$row[] = CommonHelper::format_wallet_balance($users_row->EUR_balance, config("constants.DEFAULT_PRECISION"));
					}
					else
					{
						$row[] = $users_row->EUR_balance;
					}
					*/
				}

                $row[] = $users_row->referrer_count;

                $row[] = date("m/d/Y", strtotime($users_row->created_at));

				if(!$is_tab_accessible){
					$row[] = "<a href=" . route('admin.users.transferfund', ['id' => $users_row->id]) . " title='Transfer Funds' class='btn btn-primary btn-sm'><i class='fa fa-exchange'></i></a>&nbsp;&nbsp;<a href=" . route('admin.usersdetail', ['id' => $users_row->id]) . " title='View Detail' class='btn btn-primary btn-sm'><i class='fa fa-eye'></i></a>&nbsp;&nbsp;";
				}
				else{
					$row[] = "<a href=" . route('admin.users.transferfund', ['id' => $users_row->id]) . " title='Transfer Funds' class='btn btn-primary btn-sm'><i class='fa fa-exchange'></i></a>&nbsp;&nbsp;<a href=" . route('impersonate', ['id' => $users_row->id]) . " title='Impersonate this user' class='btn btn-primary btn-sm'><i class='fa fa-user-plus'></i></a>&nbsp;&nbsp;<a href=" . route('admin.usersdetail', ['id' => $users_row->id]) . " title='View Detail' class='btn btn-primary btn-sm'><i class='fa fa-eye'></i></a>&nbsp;&nbsp;";
				}
				
                $data[] = $row;

                $i++;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $total_count,
                "recordsFiltered" => $filtered_count,
                "data" => $data,
            );
        } 
		else{
        }
        echo json_encode($output);exit;
    }
	
	public function ajaxaccounting()
	{
		error_reporting(0);
		$user = Auth::user();
		$dataForView = array();
		if(is_null($user->OTP))
		{
			if(isset($_POST['tabname'])){
				$tabname = $_POST['tabname'];
			}
			else
			{
				$tabname = 'sales';
			}
			
			$dataForView['tabname'] = $tabname;
			$currencyLoopData = array();		
			$currencyList = array('BTC','ETH','EUR','BCH','LTC','XRP','DASH');
			$type =  isset( $_POST['type'] ) ? $_POST['type'] : 'today';		
			$start_date = isset( $_POST['start_date'] ) ? $_POST['start_date'] : '';
			$end_date = isset( $_POST['end_date'] ) ? $_POST['end_date'] : '';		
			
			$elt_euro = Transactions::get_current_elt_euro_rate('elt_euro');
			if($type == 'all'){
				$total_elt_sales = Transactions::get_user_elt_worth_in_euro_reset_date(array(),'9999-12-31','2099-12-31');
			}
			elseif($type == 'today'){
				$total_elt_sales = Transactions::get_user_elt_worth_in_euro_reset_date(array(),date("Y-m-d"),date("Y-m-d"));
			}
			else{
				$total_elt_sales = Transactions::get_user_elt_worth_in_euro_reset_date(array(),$start_date,$end_date);
			}
			
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
			
			$get_periodic_data = Transactions::get_periodic_table_data($type,$start_date,$end_date,$currencyList,$tabname);
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
			
			$dataForView['currencyLoopData'] = $currencyLoopData;
			$htmlContent = "";
			foreach($currencyLoopData as $currencyName=>$currencyData){
				$htmlContent .= "<tr>";
				$htmlContent .= "<td>".$currencyName."</td>";
				$htmlContent .= "<td>".CommonHelper::format_float_balance($currencyData['total_sales'],config("constants.DEFAULT_PRECISION"))."</td>";
				$htmlContent .= "<td>".CommonHelper::format_float_balance($currencyData['at_purchase'],config("constants.EUR_PRECISION"))."</td>";
				$htmlContent .= "<td>".CommonHelper::format_float_balance($currencyData['total_sales'] * $currencyData['live_rate_in_euro'],config("constants.EUR_PRECISION"))."</td>";
				$htmlContent .= "<td>".CommonHelper::format_float_balance($currencyData['elt_amount'],config("constants.DEFAULT_PRECISION"))."</td>";
				$htmlContent .= "</tr>";
			}
			$dataForView['htmlContent'] = $htmlContent;
		}
		echo json_encode($dataForView);exit;
	}
	
	 public function ajaxstoreddata()
	{
		//error_reporting(0);
		$user = Auth::user();
		
		$dataForView = array();
		if(is_null($user->OTP))
		{
			$user_id = $_POST['user_id'];
			if(isset($_POST['type'])){
				$type = $_POST['type'];
			}
			else{
				$type = 'sales';
			}
			
			if(isset($_POST['time'])){
				$time = $_POST['time'];
			}
			else{
				$time = 'hourly';
			}
			
			
			if($type == 'users')
			{
				
			if($time == 'monthly')
			{
				$data['monthly_data'] = StoredData::get_users_monthly_data($user_id,$type);
			    $dataForView =  view('admin.partials.stored-data.users.monthly', $data);
			}
			elseif($time == 'weekly')
			{
				$data['weekly_data'] = StoredData::get_users_weekly_data($user_id,$type);
			    $dataForView =  view('admin.partials.stored-data.users.weekly', $data);	
			}
			elseif($time == 'daily')
			{
				$data['daily_data'] = StoredData::get_users_daily_data($user_id,$type);
			    $dataForView =  view('admin.partials.stored-data.users.daily', $data);	
			}
			else
			{
				
				$data['hourly_data'] = StoredData::get_users_hourly_data($user_id,$type);
				$dataForView =  view('admin.partials.stored-data.users.hourly', $data);
			}
				
				
			}
			else
			{
			if($time == 'monthly')
			{
				$data['monthly_data'] = StoredData::get_monthly_data($user_id,$type);
			    $dataForView =  view('admin.partials.stored-data.monthly', $data);
			}
			elseif($time == 'weekly')
			{
				$data['weekly_data'] = StoredData::get_weekly_data($user_id,$type);
			    $dataForView =  view('admin.partials.stored-data.weekly', $data);	
			}
			elseif($time == 'daily')
			{
				$data['daily_data'] = StoredData::get_daily_data($user_id,$type);
			    $dataForView =  view('admin.partials.stored-data.daily', $data);	
			}
			else
			{
				$data['hourly_data'] = StoredData::get_hourly_data($user_id,$type);
				$dataForView =  view('admin.partials.stored-data.hourly', $data);
			}
			}
		
		}
		echo $dataForView;exit;
	}
	
	public function ajaxcurrencyinfo()
	{
		error_reporting(0);
		$user = Auth::user();
		$dataForView = array();
		if(is_null($user->OTP))
		{
			if(isset($_POST['tabname'])){
				$tabname = $_POST['tabname'];
			}
			else{
				$tabname = 'bonus';
			}
			
			if(isset($_POST['currency'])){
				$currency = $_POST['currency'];
			}
			else{
				$currency = 'ETH';
			}
			
			
			$dataForView['tabname'] = $tabname;
			
			$dataForView['currency'] = $currency;
			
			$get_periodic_data = Transactions::call_accounting_proc($tabname,$currency);
			
			$dataForView['get_periodic_data'] = $get_periodic_data;
		}
		echo json_encode($dataForView);exit;
	}
	
	public function ajaxwithdrawstats()
	{
		error_reporting(0);
		$user = Auth::user();
		$dataForView = array();
		if(is_null($user->OTP))
		{			
			$htmlContent='';
			if(isset($_POST['thisfiltertype'])){				
				$currencyList = Withdrawal::withdraw_request_statistics($_POST['thisfiltertype']);
				foreach($currencyList as $key=>$value){
					$htmlContent.="<tr><th>".$key."</th><td>".$value['requested']."</td><td>".$value['approved']."</td><td>".$value['declined']."</td></tr>";
				}
			}
			$dataForView['htmlContent'] = $htmlContent;
		}
		echo json_encode($dataForView);exit;
	}	
	
	public function ajaxapprovewithdraw()
	{
		$user = Auth::user();
		$dataForView = array();
		$dataForView['status'] = 0;
		$dataForView['msg'] = 'error';
		if(is_null($user->OTP))
		{
			parse_str($_POST['data'], $postarray);			
			if(isset($postarray['request_id']))
			{
				$transaction_id = $postarray['request_id'];
				$remarks = $postarray['remarks'];
				Withdrawal::where('transaction_id',$transaction_id)->update(['status' =>'1', 'remarks'=>$remarks]);
				$withdrawalRow = Withdrawal::where('transaction_id',$transaction_id)->first();
				if($withdrawalRow->user_id)
				{
					$user = User::find($withdrawalRow->user_id);					
					$remarks = $postarray['remarks'];					
					$emailData = array(
						'amount' => $withdrawalRow->amount,
						'status' => 'approved',
						'transaction_id' => $withdrawalRow->transaction_id,
						'currency_name' => $withdrawalRow->ledger,
						'wallet_address' => CommonHelper::getAppropriateWalletAddress($user, $withdrawalRow->ledger),
						'first_name' => $user->first_name,
						'last_name' => $user->last_name						
					);
					Withdrawal::where('transaction_id',$transaction_id)->update(['status' =>'1', 'remarks'=>$remarks]);
					self::SendEmailForWithdrawApproval($user, $emailData);
					$dataForView['status'] = 1;
					$dataForView['msg'] = 'success';
					
					
					$record = 
					[
						'user_id'	=> Auth::user()->id,
						'message'   => 'Token buy without invoice for user: '.$userInfo->email.' elt amount: '.$elt_amount.' term amount: '.$term_amount.' against currency: '.$term_currency,
						'level'     => 'userInfo',
						'context'   => 'Admin buyTOken'
					];
					LoggerHelper::writeDB($record);

				}
			}
		}
		echo json_encode($dataForView);exit;
	}
	
	public function ajaxrejectwithdraw()
	{		
		error_reporting(0);
		$user = Auth::user();
		$dataForView = array();
		if(is_null($user->OTP))
		{
			parse_str($_POST['data'], $postarray);			
			$transaction_id = $postarray['request_id'];
			$remarks = $postarray['remarks'];
			$amonut_d = $postarray['amonut_d'];
			$ledger_t = $postarray['ledger_t'];
			$user_id_r = $postarray['user_id_r'];			
			$userId = $_POST['user_id'];
			$userInfo = User::find($user_id_r);
			$userInfo->addValue($ledger_t.'_balance',$amonut_d);
			if($userInfo->save())
			{
				$refund_transaction_id = uniqid();
				$description = 'Withdrawal request refunded PaymentID:'.$refund_transaction_id;
				Transactions::createTransaction($userInfo->id, $ledger_t, $amonut_d, $description, 1, $refund_transaction_id, NULL, NULL, 8, $transaction_id, 'withdrawal', 0, 1);
				DB::table('withdraw_request')->where('transaction_id',$transaction_id)->where('user_id',$user_id_r)->update(['status' =>'3','remarks'=>$remarks]);
				
				$withdrawalRow = Withdrawal::where('transaction_id',$transaction_id)->first();
				if($withdrawalRow->user_id)
				{
					$emailData = array(
						'amount' => $withdrawalRow->amount,
						'status' => 'declined',
						'transaction_id' => $withdrawalRow->transaction_id,
						'currency_name' => $withdrawalRow->ledger,
						'wallet_address' => CommonHelper::getAppropriateWalletAddress($userInfo, $ledger_t),
						'first_name' => $userInfo->first_name,
						'last_name' => $userInfo->last_name						
					);
					self::SendEmailForWithdrawRejection($userInfo, $emailData);
				}
				$dataForView['status'] = 1;			
				$dataForView['msg'] = 'success';
			}
		}
		echo json_encode($dataForView);exit;
	}
	
	
	public function ajaxadminreferrals()
    {		
        $user = Auth::user();
        $total_count = DB::table('users')->where('role', 2)->where('referrer_count','>',0)->count();
        $output = array("draw" => '', "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []);
        if (is_null($user->OTP)) {
            $column_order = array
            (
                "users.referrer_count",
                "users.user_name",
                "users.email",
                "users.referrer_count",
                "users.first_name",
                "users.created_at",
                "countries.name",
				"users.status",
            );
			$_POST['ajaxadminreferrals'] = 1;
            $users_list = User::get_datatables_users($column_order, $_POST);
            $users_count = User::get_datatables_users($column_order, $_POST, 1);
            if (!empty($_POST['search_text']) || !empty($_POST['start_date'])){
                $filtered_count = $users_count;
            } 
			else{
                $filtered_count = $total_count;
            }			
            if(isset($_POST['start']) && $_POST['start'] == '0'){
                $i = $_POST['start'] + 1;
            }
			else{
                $i = $_POST['start'] + 1;
            }
			
            $data = array();
			
			if(isset($_POST['admin_referral_type']) && $_POST['admin_referral_type'] == 'five'){
				$tempData = array();
				foreach ($users_list as $users_row) {
					$temp = array();
					$temp['userid'] = $users_row->id;
					$temp['user_name'] = $users_row->user_name;
					$temp['email'] = $users_row->email;
					$temp['referrer_count'] = $users_row->referrer_count;
					$temp['get_user_referral_all_five_level'] = Transactions::get_user_referral_all_five_level($users_row->id,'count');
					$temp['name'] = $users_row->first_name . ' ' . $users_row->last_name;
					$temp['created_at'] = date("m/d/Y", strtotime($users_row->created_at));
					$temp['country_name'] = $users_row->country_name;		
					$temp['status'] = $users_row->status == 1 ? 'Yes' : 'No';
					$tempData[] = $temp;
				}
				usort($tempData, function ($item1, $item2) {
					return $item2['get_user_referral_all_five_level'] <=> $item1['get_user_referral_all_five_level'];
				});
				foreach ($tempData as $key=>$value) {
					$row = array();
					$row[] = $value['userid'];
					$row[] = $value['user_name'];
					$row[] = "<a href='" . route('admin.usersdetail', ['id' => $value['userid']]) . "'>" . $value['email'] . "</a>";
					$row[] = $value['get_user_referral_all_five_level'];
					$row[] = $value['name'];
					$row[] = $value['created_at'];
					$row[] = $value['country_name'];
					$row[] = $value['status'];
					$data[] = $row;
					$i++;	
				}
			}
			else
			{
				foreach ($users_list as $users_row) {
					$row = array();
					$row[] = $users_row->id;				
					$row[] = $users_row->user_name;
					$row[] = "<a href='" . route('admin.usersdetail', ['id' => $users_row->id]) . "'>" . $users_row->email . "</a>";
					$row[] = $users_row->referrer_count;
					$row[] = $users_row->first_name . ' ' . $users_row->last_name;
					$row[] = date("m/d/Y", strtotime($users_row->created_at));
					$row[] = $users_row->country_name;				
					$row[] = $users_row->status == 1 ? 'Yes' : 'No';
					$data[] = $row;
					$i++;
				}
			}

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $total_count,
                "recordsFiltered" => $filtered_count,
                "data" => $data,
            );
        }
		else{        
		}
        echo json_encode($output);exit;
    }
	
	public function ajaxmarkedusers()
    {		
        $user = Auth::user();       
        $output = array("draw" => '', "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []);
        if (is_null($user->OTP)) {
            $column_order = array
            (
                "users.referrer_count",
                "users.user_name",
                "users.email",
                "users.referrer_count",
                "users.first_name",
                "users.created_at",
                "countries.name",
				"users.status",
            );
			$_POST['ajaxmarkedusers'] = 1;            
			$selectedConfigSetting = isset($_POST['selectedConfigSetting'])?$_POST['selectedConfigSetting']:'exclude_saleslist';
			$total_count = DB::table('users')->where('role', 2)->where($selectedConfigSetting,1)->count();		
			$users_list = User::get_datatables_users($column_order, $_POST);
            $users_count = User::get_datatables_users($column_order, $_POST, 1);
			
            if (!empty($_POST['search_text']) || !empty($_POST['start_date'])){
                $filtered_count = $users_count;
            } 
			else{
                $filtered_count = $total_count;
            }	
			
            if(isset($_POST['start']) && $_POST['start'] == '0'){
                $i = $_POST['start'] + 1;
            }
			else{
                $i = $_POST['start'] + 1;
            }
			
            $data = array();
			
			foreach ($users_list as $users_row) {
				$row = array();
				$row[] = $users_row->id;
				$row[] = $users_row->first_name . ' ' . $users_row->last_name;
				$row[] = "<a href='" . route('admin.usersdetail', ['id' => $users_row->id]) . "'>" . $users_row->email . "</a>";				
				$row[] = date("m/d/Y", strtotime($users_row->created_at));
				$row[] = $users_row->country_name;				
				$row[] = "<a href='javascript:void(0);' onclick=\"javascript:removeUserConfigSetting(".$users_row->id.",'".$selectedConfigSetting."');\">Remove</a>";
				$data[] = $row;
				$i++;
			}

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $total_count,
                "recordsFiltered" => $filtered_count,
                "data" => $data,
            );
        }
		else{        
		}
        echo json_encode($output);exit;
    }
	
	
	public function ajaxfinance()
    {		
        $user = Auth::user();
        $output = array("draw" => '', "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []);
        if (is_null($user->OTP))
		{
			if($_POST['selectedCurrency'] == 'bonus' || $_POST['selectedCurrency'] == 'unqualified')
			{
				$column_order = array();			
				$column_order[] = "transactions.value";
				$column_order[] = "users.user_name";
				$column_order[] = "users.email";
				$column_order[] = "users.referrer_count";
				$column_order[] = "users.first_name";
				$column_order[] = "users.created_at";
				$column_order[] = "countries.name";
				$column_order[] = "users.status";
				$users_list = Transactions::get_datatables_bonus($column_order, $_POST);
				$total_count = $filtered_count = Transactions::get_datatables_bonus($column_order, $_POST, 1);
				if(isset($_POST['start']) && $_POST['start'] == '0'){
					$i = $_POST['start'] + 1;
				}
				else{
					$i = $_POST['start'] + 1;
				}
				$data = array();			
				foreach ($users_list as $users_row) 
				{
					$row = array();
					$row[] = $users_row->id;	
					$row[] = $users_row->user_name;
					$row[] = "<a href='" . route('admin.usersdetail', ['id' => $users_row->id]) . "'>" . $users_row->email . "</a>";				
					$row[] = CommonHelper::format_float_balance($users_row->bonus_euro_worth,config("constants.EUR_PRECISION"));		
					$row[] = $users_row->first_name . ' ' . $users_row->last_name;
					$row[] = date("m/d/Y", strtotime($users_row->created_at));
					$row[] = $users_row->country_name;				
					$row[] = $users_row->status == 1 ? 'Yes' : 'No';
					$data[] = $row;
					$i++;
				}
			}
			else
			{
				$_POST['ajaxfinance'] = 1;
				if(isset($_POST['selectedCurrency']) && !empty($_POST['selectedCurrency'])){
					$selectedCurrency = strtoupper($_POST['selectedCurrency']);
				}
				else{
					$selectedCurrency = 'ELT';
					$_POST['selectedCurrency'] = 'ELT';
				}			
				$column_order = array();
				$column_order[] = "users.".$selectedCurrency."_balance";
				$column_order[] = "users.user_name";
				$column_order[] = "users.email";
				$column_order[] = "users.referrer_count";
				$column_order[] = "users.first_name";
				$column_order[] = "users.created_at";
				$column_order[] = "countries.name";
				$column_order[] = "users.status";
				$users_list = User::get_datatables_users($column_order, $_POST);
				$users_count = User::get_datatables_users($column_order, $_POST, 1);
				$total_count = DB::table('users')->where('role', 2)->where($selectedCurrency.'_balance','>',0)->count();
				if(!empty($_POST['search_text']) || !empty($_POST['start_date'])){
					$filtered_count = $users_count;
				}
				else{
					$filtered_count = $total_count;
				}			
				if(isset($_POST['start']) && $_POST['start'] == '0'){
					$i = $_POST['start'] + 1;
				}
				else{
					$i = $_POST['start'] + 1;
				}
				$data = array();			
				foreach ($users_list as $users_row) {
					$row = array();
					$row[] = $users_row->id;	
					$row[] = $users_row->user_name;
					$row[] = "<a href='" . route('admin.usersdetail', ['id' => $users_row->id]) . "'>" . $users_row->email . "</a>";				
					if($selectedCurrency == 'BTC'){
						$row[] = CommonHelper::format_float_balance($users_row->BTC_balance,config("constants.DEFAULT_PRECISION"));
					}
					elseif($selectedCurrency == 'ETH'){
						$row[] = CommonHelper::format_float_balance($users_row->ETH_balance,config("constants.DEFAULT_PRECISION"));
					}
					elseif($selectedCurrency == 'EUR'){
						$row[] = CommonHelper::format_float_balance($users_row->EUR_balance,config("constants.EUR_PRECISION"));
					}
					elseif($selectedCurrency == 'LTC'){
						$row[] = CommonHelper::format_float_balance($users_row->LTC_balance,config("constants.DEFAULT_PRECISION"));
					}
					elseif($selectedCurrency == 'BCH'){
						$row[] = CommonHelper::format_float_balance($users_row->BCH_balance,config("constants.DEFAULT_PRECISION"));
					}
					elseif($selectedCurrency == 'XRP'){
						$row[] = CommonHelper::format_float_balance($users_row->XRP_balance,config("constants.DEFAULT_PRECISION"));
					}
					elseif($selectedCurrency == 'dash'){
						$row[] = CommonHelper::format_float_balance($users_row->DASH_balance,config("constants.DEFAULT_PRECISION"));
					}
					else{
						$row[] = CommonHelper::format_float_balance($users_row->ELT_balance,config("constants.DEFAULT_PRECISION"));
					}				
					$row[] = $users_row->first_name . ' ' . $users_row->last_name;
					$row[] = date("m/d/Y", strtotime($users_row->created_at));
					$row[] = $users_row->country_name;				
					$row[] = $users_row->status == 1 ? 'Yes' : 'No';
					$data[] = $row;
					$i++;
				}
				
			}
			
            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $total_count,
                "recordsFiltered" => $filtered_count,
                "data" => $data,
            );
        }
        echo json_encode($output);exit;
    }
	
	public function ajaxbonuslist()
    {		
        $user = Auth::user();
        $output = array("draw" => '', "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []);
        if (is_null($user->OTP)){
			
			$_POST['selectedCurrency'] = isset($_POST['selectedCurrency'])?$_POST['selectedCurrency']:'bonus';
			
			
            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $total_count,
                "recordsFiltered" => $filtered_count,
                "data" => $data,
            );
        }
        echo json_encode($output);exit;
    }
	
	public function ajaxsalesrevenue()
    {
		error_reporting(0);
        $user = Auth::user();
        $total_count = DB::table('users')->where('role', 2)->where('ELT_balance','>',0)->count();
        $output = array("draw" => '', "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []);
        if (is_null($user->OTP)) {
            $column_order = array
            (
                "users.ELT_balance",
                "users.user_name",
                "users.email",
                "users.ELT_balance",
                "users.first_name",
                "users.created_at",
                "countries.name",
				"users.status",
            );
            $users_list = User::get_datatables_salesrevenue($column_order, $_POST);
            $users_count = User::get_datatables_salesrevenue($column_order, $_POST, 1);
            if (!empty($_POST['search_text']) || !empty($_POST['start_date'])){
                $filtered_count = $users_count;
            } 
			else{
                $filtered_count = $total_count;
            }			
            if(isset($_POST['start']) && $_POST['start'] == '0'){
                $i = $_POST['start'] + 1;
            }
			else{
                $i = $_POST['start'] + 1;
            }
            $data = array();	
            foreach ($users_list as $users_row) {
                $row = array();
                $row[] = $users_row->id;				
				$row[] = $users_row->user_name;
				$row[] = "<a href='" . route('admin.usersdetail', ['id' => $users_row->id]) . "'>" . $users_row->email . "</a>";
				$row[] = CommonHelper::format_balance_view($users_row->euro_worth);
                $row[] = $users_row->first_name . ' ' . $users_row->last_name;
				$row[] = date("m/d/Y", strtotime($users_row->created_at));
				$row[] = $users_row->country_name;				
				$row[] = $users_row->status == 1 ? 'Yes' : 'No';
                $data[] = $row;
                $i++;
            }
            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $total_count,
                "recordsFiltered" => $filtered_count,
                "data" => $data,
            );
        }
        echo json_encode($output);exit;
    }
	
	public function ajaxdemographics()
    {
		error_reporting(0);
		
        $user = Auth::user();
	
		$total_list = DB::select(DB::raw( "select id from `users` where `role` = 2 group by `country_code`" ));
		$total_count = count($total_list);
		
		$output = array("draw" => '', "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []);
        
		if (is_null($user->OTP)) 
		{
            $column_order = array("count(users.id)","countries.country_code","count(users.id)");		
            $users_list = User::get_datatables_demographics($column_order, $_POST);	
            $users_count = 1;            
			if (!empty($_POST['searchByCountry'])){
                $filtered_count = $users_count;
            } else {
                $filtered_count = $total_count;
            }			
            if (isset($_POST['start']) && $_POST['start'] == '0') {
                $i = $_POST['start'] + 1;
            } else {
                $i = $_POST['start'] + 1;
            }
            $data = array();
			$base_url = url('/');
            foreach ($users_list as $users_row) {
                $row = array();	
								
				if(isset($users_row->country_code)){
					$row[] = $users_row->country_code;
					$img_url = $base_url.'/admin/flags/1x1/'.strtolower($users_row->country_code).'.svg';
					$flag_url = '<img src="'.$img_url.'" alt="'.$users_row->country_code.'" width="20px">';
				}
				else{
					$row[] = '--';
					$img_url = $base_url.'/admin/images/admin_user_profile.png';
					$flag_url = '<img src="'.$img_url.'" alt="No country" width="20px">';
				}
				$row[] = $flag_url;
				$row[] = isset($users_row->country_name)?$users_row->country_name:'--';
				$row[] = $users_row->total_country_user;
                $data[] = $row;
                $i++;
            }
            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $total_count,
                "recordsFiltered" => $filtered_count,
                "data" => $data,
            );
        } 
		else {
		}
        echo json_encode($output);exit;
    }
	
	
	/* Ajax call for All Card Priority */
	public function ajaxcardpriority()
    {
		error_reporting(0);
		
		ini_set('memory_limit', '-1');
		
        $user = Auth::user();
		
		$search_value = '';
		
		$total_count = DB::table('users')->where("reserve_card",'>',0)->count();
		
		$filtered_count = 0;
		
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        
		if(is_null($user->OTP))
        {
			$column_order = array
			(
				"users.id",
				"users.first_name",
				"users.email",
				'lendo_cards.name',
				'lendo_cards.issue_fee',
				'lendo_cards.annual_fee',
				'lendo_cards.credit_limit',
				"users.card_requested_on"
			);
			
			$transaction_list = User::get_datatables_card_list($column_order,$_POST);
				
			$transaction_count = User::get_datatables_card_list($column_order,$_POST,1);
			
			if(!empty($_POST['search_text']) || !empty($_POST['start_date']) || $_POST['reserve_card']!=-1)
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
			
			$data = array();
			
			foreach($transaction_list as $transaction_row)
			{
				$row = array();			
				
				$row[] = $i;				
				$row[] = $transaction_row->first_name;
				if(!CommonHelper::isUserAllowedForSubAdmin(Auth::user()->custom_role,$transaction_row->user_id))
				{
					$row[] = $transaction_row->email;
				}
				else
				{
					$row[] = "<a href='" . route('admin.usersdetail', ['id' => $transaction_row->id]) . "'>" . $transaction_row->email . "</a>";
				}
				$row[] = $transaction_row->name;
				$row[] = $transaction_row->issue_fee;
				$row[] = $transaction_row->annual_fee;
				$row[] = $transaction_row->credit_limit;
				$row[] = $transaction_row->card_requested_on;
				$data[] = $row;				
				$i++;
			}
			
			$output = array(
			"draw" => $_POST['draw'],
			"recordsTotal" => $total_count,
			"recordsFiltered" =>$filtered_count,
			"data" => $data,
			);
        }
        else
        {
            
        }
		echo json_encode($output);exit;
    }
	
	/* Ajax call for All Loan Priority */
	public function ajaxloanpriority()
    {
		error_reporting(0);
		
		ini_set('memory_limit', '-1');
		
        $user = Auth::user();
		
		$search_value = '';
		
		$total_count = DB::table('users')->where("loan_amount_requested",'>',0)->count();
		
		$filtered_count = 0;
		
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        
		if(is_null($user->OTP))
        {
			$column_order = array
			(
				"users.id",
				"users.first_name",
				"users.email",
				"users.loan_amount_requested",
				"users.loan_term",
				"users.security_type",
				"users.loan_requested_on"
			);
			
			$transaction_list = User::get_datatables_loan_list($column_order,$_POST);
				
			$transaction_count = User::get_datatables_loan_list($column_order,$_POST,1);
			
			if( !empty($_POST['search_text']) || !empty($_POST['start_date']) || $_POST['loan_term']!=-1 || $_POST['security_type']!=-1 )
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
			
			$data = array();
			
			
			foreach($transaction_list as $transaction_row)
			{
				$row = array();			
				
				$row[] = $i;				
				$row[] = $transaction_row->first_name;	
				if(!CommonHelper::isUserAllowedForSubAdmin(Auth::user()->custom_role,$transaction_row->id))
				{
					$row[] = $transaction_row->email;
				}
				else
				{
					$row[] = "<a href='" . route('admin.usersdetail', ['id' => $transaction_row->id]) . "'>" . $transaction_row->email . "</a>";
				}
				
				$row[] = $transaction_row->loan_amount_requested;
				$row[] = $transaction_row->loan_term.' months';
				$row[] = $transaction_row->security_type;						
				$row[] = $transaction_row->loan_requested_on;
				$data[] = $row;				
				$i++;
			}
			
			$output = array(
			"draw" => $_POST['draw'],
			"recordsTotal" => $total_count,
			"recordsFiltered" =>$filtered_count,
			"data" => $data,
			);
        }
        else
        {
            
        }
		echo json_encode($output);exit;
    }
	
	/* Ajax call for Admin Logs */
	public function ajaxadminlogs()
    {
		//echo 'ajaxadminlogs';die;
				
        $user = Auth::user();
		
		$search_value = '';
		
		$total_count = DB::table('logs')->join('users','users.id','=','logs.user_id')->where('logs.user_id','>',0)->where('users.role',1)->count();
		
		$filtered_count = 0;
		
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        
		if(is_null($user->OTP))
        {
			$column_order = array("logs.id");
			
			$logs_list = Logs::get_datatables_logs_list($_POST, 0, 1);
			
			//print_r($logs_list);die;
			
			$logs_count = Logs::get_datatables_logs_list($_POST, 1, 1);
			
			if( !empty($_POST['search_text']) || !empty($_POST['start_date']) )
			{
				$filtered_count = $logs_count;
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
			
			$data = array();
			
			foreach($logs_list as $log_row)
			{
				$row = array();				
				$row[] = $i;
				$row[] = $log_row->first_name.' '.$log_row->last_name;	
				$row[] = "<a href='" . route('admin.usersdetail', ['id' => $log_row->user_id]) . "'>" . $log_row->email . "</a>";				
				$row[] = $log_row->context;
				$row[] = $log_row->message;
				$row[] = date("m/d/Y",strtotime($log_row->created_at));				
				$data[] = $row;
				$i++;
			}
			
			$output = array
			(
				"draw" => $_POST['draw'],
				"recordsTotal" => $total_count,
				"recordsFiltered" =>$filtered_count,
				"data" => $data,
			);
        }
        else
        {
            
        }
		echo json_encode($output);exit;
    }
	
	/* Ajax call for All transactions */
	public function ajaxtransaction()
    {
        $user = Auth::user();
		$total_count = DB::table('transactions')->count();
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        if(is_null($user->OTP))
        {
			$column_order = array("transactions.created_at","transactions.transaction_id","transactions.created_at","users.user_name","users.email","transactions.ledger","transactions.value","transactions.value","transactions.status","transactions.status","transactions.status","transactions.status");		
			
			$transaction_list = Transactions::get_datatables_join($column_order,$_POST,0);
			$transaction_count = Transactions::get_datatables_join($column_order,$_POST,1);
			
			if(!empty($_POST['search_text']) || !empty($_POST['start_date']) || !empty($_POST['currency_filter']) || !empty($_POST['status_filter']))
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
			else{
				$i = $_POST['start'] + 1;
			}
			
			//$i = 1;
			$data = array();
			foreach($transaction_list as $transaction_row)
			{
				$row = array();
			
				//SrNo
				$row[] = $i;	
				
				$child_tran_count = Transactions::count_child_transaction(
				$transaction_row->transaction_id);
				
				if($child_tran_count > 0)
				{
					//BDL
					$row[] = "<a href='#' onclick=\"javascript:showChildTransaction('".$transaction_row->transaction_id."');\" title='Child transaction'>".$child_tran_count."</a>";
				}
				else
				{
					$row[] = "<a href='#' onclick=\"javascript:showChildTransaction('".$transaction_row->transaction_id."');\" title='Child transaction'>".$child_tran_count."</a>";
				}
				
				//TransactionID
				$row[] = $transaction_row->transaction_id;
				
				//Date
				$row[] = date("m/d/Y",strtotime($transaction_row->created_at));
				
				//Email
				if(!CommonHelper::isUserAllowedForSubAdmin(Auth::user()->custom_role,$transaction_row->user_id))
				{
					$row[] = $transaction_row->email;
				}
				else{
					$row[] = "<a href='".route('admin.usersdetail',['id'=> $transaction_row->user_id ])."'>".$transaction_row->email."</a>";
				}
				//Typename
				$row[] = $transaction_row->type_name;
				
				// Currency				
				if($transaction_row->term_currency != NULL) 
				{
					$currencyName = $transaction_row->term_currency;
				}
				else
				{
					$currencyName = $transaction_row->ledger;
				}
				$row[] = $currencyName;
				
				// Amount and ELT Column
				if($transaction_row->ledger == 'ELT')
				{
					if( $transaction_row->term_amount > 0 )
					{
						if($transaction_row->term_currency == 'EUR')
						{
							$row[] = CommonHelper::format_float_balance($transaction_row->term_amount,config("constants.EUR_PRECISION"));
						}
						else
						{
							$row[] = CommonHelper::format_float_balance($transaction_row->term_amount,config("constants.DEFAULT_PRECISION"));
						}
					}
					else
					{
						$row[] = '-';
					}
					
					$row[] = CommonHelper::format_float_balance($transaction_row->value,config("constants.DEFAULT_PRECISION"));
				}
				else
				{
					if($transaction_row->type == 8)
					{
						$row[] = CommonHelper::format_float_balance(abs($transaction_row->value),config("constants.DEFAULT_PRECISION"));
					}
					else
					{
						$row[] = CommonHelper::format_float_balance($transaction_row->value,config("constants.DEFAULT_PRECISION"));
					}					
					$row[] = '-';
				}
			
				// Status
				if($transaction_row->status == 0)
				{
					$row[] = 'Failed';
				} 
				else if($transaction_row->status == 1)
				{
					$row[] = 'Success';
				}
				else if($transaction_row->status == 2)
				{
					$row[] = 'Pending';
				} 
				else
				{
					$row[] = '-';
				}
				
				$row[] = "<a href='#' onclick=\"javascript:showTransactionDescription('".$transaction_row->description."');\" title='".$transaction_row->description."'>Description</a>";
				
				$data[] = $row;
				
				$i++;
			}
			
			$output = array
			(
				"draw" => $_POST['draw'],
				"recordsTotal" => $total_count,
				"recordsFiltered" =>$filtered_count,
				"data" => $data,
			);
        }
		echo json_encode($output);exit;
    }
	
	
	/* Ajax call for All downline level transactions */
	public function ajaxdownlinetransaction()
    {
        $user = Auth::user();
		
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        
		if(is_null($user->OTP))
        {
			
			if(isset($_POST['userid']) && isset($_POST['level']))
			{
				$parentid = $_POST['userid'];
				
				$level = $_POST['level'];				
				
				$downlinelist_level_1 = Transactions::get_user_downline_list($parentid,$level,'list');
				
				$inUserIds = array();
				
				foreach($downlinelist_level_1 as $downlinelist_level_1_row){
					$inUserIds[] = $downlinelist_level_1_row->child_id;
				}
				
				$total_count = DB::table('transactions')->whereIn('user_id',$inUserIds)->whereNotIn('type', [2, 3, 4])->whereNull('ref_transaction_id')->count();
				
				$_POST['inUserIds'] = $inUserIds;
				
				$column_order = array("transactions.created_at","transactions.transaction_id","transactions.created_at","users.user_name","users.email","transactions.ledger","transactions.value","transactions.value","transactions.status","transactions.status","transactions.status","transactions.status");		
			
				$transaction_list = Transactions::get_datatables_join($column_order,$_POST,0);			
				$transaction_count = Transactions::get_datatables_join($column_order,$_POST,1);
			
				if(!empty($_POST['search_text']) || !empty($_POST['start_date']) || !empty($_POST['currency_filter']) || !empty($_POST['status_filter'])){
					$filtered_count = $transaction_count;
				}
				else{
					$filtered_count = $total_count;
				}	
				
				if(isset($_POST['start']) && $_POST['start'] == '0'){
					$i = $_POST['start'] + 1;
				}
				else{
					$i = $_POST['start'] + 1;
				}
			
				$data = array();
				foreach($transaction_list as $transaction_row)
				{
					$row = array();
				
					//SrNo
					$row[] = $i;	
					
					$child_tran_count = Transactions::count_child_transaction(
					$transaction_row->transaction_id);
					
					//TransactionID
					$row[] = $transaction_row->transaction_id;
					
					//Date
					$row[] = date("m/d/Y",strtotime($transaction_row->created_at));
					
					//Email
					if(!CommonHelper::isUserAllowedForSubAdmin(Auth::user()->custom_role,$transaction_row->user_id))
					{
						$row[] = $transaction_row->email;
					}
					else{
						$row[] = "<a href='".route('admin.usersdetail',['id'=> $transaction_row->user_id ])."'>".$transaction_row->email."</a>";
					}
					
					//Typename
					$row[] = $transaction_row->type_name;
					
					// Currency				
					if($transaction_row->term_currency != NULL) 
					{
						$currencyName = $transaction_row->term_currency;
					}
					else
					{
						$currencyName = $transaction_row->ledger;
					}
					$row[] = $currencyName;
					
					// Amount and ELT Column
					if($transaction_row->ledger == 'ELT')
					{
						if( $transaction_row->term_amount > 0 )
						{
							if($transaction_row->term_currency == 'EUR')
							{
								$row[] = CommonHelper::format_float_balance($transaction_row->term_amount,config("constants.EUR_PRECISION"));
							}
							else
							{
								$row[] = CommonHelper::format_float_balance($transaction_row->term_amount,config("constants.DEFAULT_PRECISION"));
							}
						}
						else
						{
							$row[] = '-';
						}
						
						$row[] = CommonHelper::format_float_balance($transaction_row->value,config("constants.DEFAULT_PRECISION"));
					}
					else
					{
						if($transaction_row->type == 8)
						{
							$row[] = CommonHelper::format_float_balance(abs($transaction_row->value),config("constants.DEFAULT_PRECISION"));
						}
						else
						{
							$row[] = CommonHelper::format_float_balance($transaction_row->value,config("constants.DEFAULT_PRECISION"));
						}					
						$row[] = '-';
					}
			
					// Status
					if($transaction_row->status == 0)
					{
						$row[] = 'Failed';
					} 
					else if($transaction_row->status == 1)
					{
						$row[] = 'Success';
					}
					else if($transaction_row->status == 2)
					{
						$row[] = 'Pending';
					} 
					else
					{
						$row[] = '-';
					}
				
					$data[] = $row;
				
					$i++;
				}
			}
			
			$output = array
			(
				"draw" => $_POST['draw'],
				"recordsTotal" => $total_count,
				"recordsFiltered" =>$filtered_count,
				"data" => $data,
			);
        }
		echo json_encode($output);exit;
    }
	
	/* Ajax call for All downline level transactions */
	public function ajaxadmincomments()
    {
        $user = Auth::user();
		
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        
		if(is_null($user->OTP))
        {
			if(isset($_POST['userid']))
			{
				$userid = $_POST['userid'];
				
				$total_count = DB::table('admin_comments')->where('user_id',$userid)->count();
				
				$column_order = array("admin_comments.created_at","admin_comments.comments");		
			
				$transaction_list = AdminComments::get_datatables_join($column_order,$_POST,0);			
				$transaction_count = AdminComments::get_datatables_join($column_order,$_POST,1);
			
				if(!empty($_POST['search_text']))
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
			
				$data = array();
				
				foreach($transaction_list as $transaction_row)
				{
					$row = array();
					$row[] = $i;
					$row[] = $transaction_row->transaction_id;
					$row[] = $transaction_row->currency;
					$row[] = $transaction_row->amount;
					$row[] = $transaction_row->comments;
					$row[] = $transaction_row->first_name.' '.$transaction_row->last_name;
					$row[] = date("m/d/Y H:i:s",strtotime($transaction_row->created_at));
					$data[] = $row;			
					$i++;
				}
			}
			
			$output = array
			(
				"draw" => $_POST['draw'],
				"recordsTotal" => $total_count,
				"recordsFiltered" =>$filtered_count,
				"data" => $data,
			);
        }
		echo json_encode($output);exit;
    }
	
	/* Ajax call for All transactions */
	public function ajaxcointransaction()
    {
        $user = Auth::user();
		$total_count = DB::table('transactions')->whereIn('transactions.term_currency', ['NEXO','SALT','ORME'])->count();
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        if(is_null($user->OTP))
        {
			$column_order = array("transactions.created_at","transactions.transaction_id","transactions.created_at","users.user_name","users.email","transactions.ledger","transactions.value","transactions.value","transactions.status","transactions.status","transactions.status","transactions.status");		
						
			$transaction_list = Transactions::get_datatables_join_cointrans($column_order,$_POST,0);
			$transaction_count = Transactions::get_datatables_join_cointrans($column_order,$_POST,1);
			
			$filtered_count = $transaction_count;
			
			if(isset($_POST['start']) && $_POST['start'] == '0')
			{
				$i = $_POST['start'] + 1;
			}
			else{
				$i = $_POST['start'] + 1;
			}
			
			$data = array();
			foreach($transaction_list as $transaction_row)
			{
				$row = array();
				$row[] = $i;
				$row[] = $transaction_row->transaction_id;
				$row[] = date("m/d/Y",strtotime($transaction_row->created_at));
				$row[] = "<a href='".route('admin.usersdetail',['id'=> $transaction_row->user_id ])."'>".$transaction_row->email."</a>";
				$row[] = $transaction_row->ledger;
				$row[] = round($transaction_row->value,config('constants.CURRENCY_PRECISION'));				
				if($transaction_row->ledger != $transaction_row->term_currency)
				{
					$row[] = $transaction_row->term_currency;
					$row[] = CommonHelper::format_float_balance($transaction_row->term_amount,config("constants.DEFAULT_PRECISION"));
				}
				else
				{
					$row[] = "";
					$row[] = "";
				}
				
				if($transaction_row->status == 0){					
					$row[] = 'Failed';
				} 
				else if($transaction_row->status == 1){
					$row[] = 'Success';
				}
				else if($transaction_row->status == 2){
					$row[] = 'Pending';
				} 
				else{
					$row[] = '-';
				}
				
				$ActionHTML = "";
				
				if($transaction_row->status == 2)
				{
					$ActionHTML = "<a href='#' onclick=\"javascript:depositELT('".$transaction_row->value."', '".$transaction_row->transaction_id."','".$transaction_row->user_id."');\" title='Deposit ELT'>Send ELT</a> &nbsp; <a href='#' onclick=\"javascript:cancelTransaction('".$transaction_row->transaction_id."','".$transaction_row->user_id."');\" title='Cancel Transaction'>Cancel</a>";
				}
				
				$row[] = $ActionHTML;
				
				$data[] = $row;
				
				$i++;
			}
			
			$output = array(
			"draw" => $_POST['draw'],
			"recordsTotal" => $total_count,
			"recordsFiltered" =>$filtered_count,
			"data" => $data,
			);
        }
		echo json_encode($output);exit;
    }
	
	
	/* Ajax call for All payments */
	public function ajaxpayments()
    {
        $user = Auth::user();
		
		$total_count = DB::table('transactions')
					->where("transactions.exclude_in_payments",0)
					->whereIn('transactions.type',[1])
					->whereIn('transactions.status',[1])
					->whereNotIn('transactions.type_name',['bonus'])
					->whereNull('transactions.ref_transaction_id')
					->count();
		
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
		
        if(is_null($user->OTP))
        {
			$column_order = array("transactions.created_at","transactions.transaction_id","transactions.created_at","users.user_name","users.email","transactions.ledger","transactions.value","transactions.value","transactions.status","transactions.status","transactions.status","transactions.status");		
			
			$_POST['status'] = 1;
			
			$_POST['paymentOnly'] = 1;
			
			$transaction_list = Transactions::get_datatables_join($column_order,$_POST,0);
			
			$transaction_count = Transactions::get_datatables_join($column_order,$_POST,1);
			
			if(!empty($_POST['search_text']) || !empty($_POST['start_date']) || !empty($_POST['currency_filter']) || !empty($_POST['status_filter']))
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
			else{
				$i = $_POST['start'] + 1;
			}
			
			//$i = 1;
			$data = array();
			foreach($transaction_list as $transaction_row)
			{
				$row = array();
			
				//SrNo
				$row[] = $i;	
				
				//$child_tran_data = Transactions::payment_child_transaction($transaction_row->transaction_id);
				$child_tran_count = Transactions::count_child_transaction($transaction_row->transaction_id);
				$bonus = 0;
				$unpaid_bonus = 0;
				if($child_tran_count > 0)
				{
					//BDL
					$row[] = "<a href='#' onclick=\"javascript:showChildTransaction('".$transaction_row->transaction_id."');\" title='Child transaction'>".$child_tran_count."</a>";
					//$bonus = $child_tran_data->currency_amount;
					//$unpaid_bonus = $child_tran_data->unpaid_bonus;
				}
				else{
					$row[] = $child_tran_count;
					
				}
				
				//TransactionID
				$row[] = $transaction_row->transaction_id;
				
				//Date
				$row[] = date("m/d/Y",strtotime($transaction_row->created_at));
				
				//Email
				if(!CommonHelper::isUserAllowedForSubAdmin(Auth::user()->custom_role,$transaction_row->user_id))
				{
					$row[] = $transaction_row->email;
				}
				else{
					$row[] = "<a href='".route('admin.usersdetail',['id'=> $transaction_row->user_id ])."'>".$transaction_row->email."</a>";
				}
				//Typename
				$row[] = $transaction_row->type_name;
				
				// Currency
				if($transaction_row->term_currency != NULL) 
				{
					$currencyName = $transaction_row->term_currency;
				}
				else
				{
					$currencyName = $transaction_row->ledger;
				}
				$row[] = $currencyName;
				
				// Amount and ELT Column
				if($transaction_row->ledger == 'ELT')
				{
					$row[] = $transaction_row->term_amount > 0 ? $transaction_row->term_amount : '-';
					$row[] = CommonHelper::format_float_balance($transaction_row->value,config("constants.DEFAULT_PRECISION"));
				}
				else
				{
					$row[] = CommonHelper::format_float_balance($transaction_row->value,config("constants.DEFAULT_PRECISION"));
					$row[] = '-';
				}
				
				$total_bonus = 0;
				$total_bonus_percentage = 0;
				
				$total_unpaid = 0;
				$total_unpaid_percentage = 0;
				
				$transaction_bonus_Row = Transactions::transaction_bonus_percentage($transaction_row->transaction_id);
				if($transaction_bonus_Row['total_bonus'] > 0 && $transaction_row->term_amount > 0)
				{
					$total_bonus = $transaction_bonus_Row['total_bonus'];
					$total_bonus_percentage = ($total_bonus * 100)/$transaction_row->term_amount;
				}
				$total_bonus_percentage_sum = Transactions::total_bonus_percentage_sum();
				if($transaction_bonus_Row['total_unpaid_bonus'] > 0 && $transaction_row->term_amount > 0)
				{
					$total_unpaid = $transaction_bonus_Row['total_unpaid_bonus'];
				}
				$total_unpaid_percentage = $total_bonus_percentage_sum - $total_bonus_percentage;
				
				if($currencyName == 'EUR'){
					$row[] = CommonHelper::format_balance_view($total_bonus,config("constants.EUR_PRECISION"));
				}
				else{
					$row[] = CommonHelper::format_float_balance($total_bonus,config("constants.DEFAULT_PRECISION"));
				}				
				$row[] = CommonHelper::format_float_balance($total_bonus_percentage,config("constants.EUR_PRECISION")).'%';
				
				if($currencyName == 'EUR'){
					$row[] = CommonHelper::format_balance_view($total_unpaid,config("constants.EUR_PRECISION"));
				}
				else{
					$row[] = CommonHelper::format_float_balance($total_unpaid,config("constants.DEFAULT_PRECISION"));
				}
				$row[] = CommonHelper::format_float_balance($total_unpaid_percentage,config("constants.EUR_PRECISION")).'%';
				
				$row[] = "<a href='#' totalsum='".$total_bonus_percentage_sum."' onclick=\"javascript:showTransactionDescription('".$transaction_row->description."');\" title='".$transaction_row->description."'>Description</a>";
				
				$data[] = $row;
				
				$i++;
			}
			
			$output = array
			(
				"draw" => $_POST['draw'],
				"recordsTotal" => $total_count,
				"recordsFiltered" =>$filtered_count,
				"data" => $data,
			);
        }
		echo json_encode($output);exit;
    }
	
	public function ajax_whitelistusers()
    {
		error_reporting(0);
		
        $user = Auth::user();
		$search_value = '';
		$total_count = DB::table('whitelist_users')->count();
		$filtered_count = 0;
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        if(is_null($user->OTP))
        {
			$column_order = array("whitelist_users.created_at","whitelist_users.name","whitelist_users.email","whitelist_users.phone","whitelist_users.status","whitelist_users.amount","whitelist_users.ip_address","whitelist_users.created_at");	
			
			$users_list = User::get_whitelistuser_datatables($column_order,$_POST);
			if(!empty($_POST['search_text']) || !empty($_POST['start_date']))
			{
				$filtered_count = count($users_list);
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
			
			$data = array();
			foreach($users_list as $users_row)
			{
				$row = array();
				$row[] = $i;
				$row[] = $users_row->name;
				$row[] = $users_row->email;
				$row[] = $users_row->phone;
				$row[] = ($users_row->status==1?'Yes':'No');
				$row[] = $users_row->amount;
				
				$row[] = $users_row->ip_address;
				$row[] = date("m/d/Y",strtotime($users_row->created_at));
				
				if($users_row->status==1 && $users_row->amount == 0)
				{
					$row[] = "<a href=".route('admin.send-whitelist-email',['id'=> $users_row->id ])." title='Send email' class='btn btn-primary btn-sm'>Send Email</a>";
				}
				else{
					$row[] = "--";
				}
				$data[] = $row;
				$i++;
			}
			
			$output = array(
			"draw" => $_POST['draw'],
			"recordsTotal" => $total_count,
			"recordsFiltered" =>$filtered_count,
			"data" => $data,
			);
        }
        else
        {
            
        }
		echo json_encode($output);exit;
    }
	
	public function ajaxwithdrawal()
    {
		error_reporting(0);

        $user = Auth::user();
		
		$total_count = Withdrawal::where('status','!=',0)->count();
		
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        
		if(is_null($user->OTP))
        {
			$column_order = array
			(
			"withdraw_request.created_at",
			"withdraw_request.transaction_id",
			"users.email",
			"withdraw_request.ledger",
			"withdraw_request.amount",
			"withdraw_request.fees",
			"withdraw_request.transfer_amount",
			"users.BTC_wallet_address",
			"withdraw_request.created_at",
			"withdraw_request.status"			
			);
		
									
			$withdraw_list = Withdrawal::get_datatables_join($column_order,$_POST);

		
			$withdraw_list_count = Withdrawal::get_datatables_join($column_order,$_POST,1);
						
			if(!empty($_POST['search_text']) || !empty($_POST['start_date']))
			{
				$filtered_count = $withdraw_list_count;
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
			
			$data = array();
			
			
			
			foreach($withdraw_list as $withdraw_row)
			{
				$row = array();				
				$user = User::find($withdraw_row->user_id);				
				$wallet_address = CommonHelper::getAppropriateWalletAddress($user, $withdraw_row->ledger);
				$row[] = $i;
				$row[] = $withdraw_row->transaction_id;
				$row[] = date("m/d/Y",strtotime($withdraw_row->created_at));
				$row[] = "<a href='".route('admin.usersdetail',['id'=> $withdraw_row->user_id ])."'>".$withdraw_row->email."</a>";
				$row[] = $withdraw_row->ledger;				
				$row[] = CommonHelper::format_float_balance($withdraw_row->amount,config("constants.DEFAULT_PRECISION"));
				$row[] = CommonHelper::format_float_balance($withdraw_row->fees, config("constants.DEFAULT_PRECISION"));
				$row[] = CommonHelper::format_float_balance($withdraw_row->transfer_amount,config("constants.DEFAULT_PRECISION"));				
				$row[] = $wallet_address;
				
				$row[] = '<a href="javascript:void(0)" class="btn btn-primary btn-sm CopyAddress">Copy</a>';
				
				$status = CommonHelper::withdraw_status($withdraw_row->status);
				
				$row[] = $status;
				if($withdraw_row->status == 2)
				{
					$row[] = '<a href="javascript:void(0)"
				onclick="add_cash(' . "'" . $withdraw_row->transaction_id ."'" .','."'". $withdraw_row->email."'" .','."'".  $withdraw_row->ledger . "'" . ',' . "'" . $withdraw_row->amount . "'" . ',' . "'" . $withdraw_row->fees . "'" . ','. "'" .$withdraw_row->transfer_amount. "'". ' )"  class="btn btn-primary btn-sm">Approve</a> &nbsp;<a href="javascript:void(0)" onclick="reject_request(' . "'" . $withdraw_row->transaction_id . "'" . ',' . "'" . $withdraw_row->amount . "'" . ','."'".  $withdraw_row->ledger . "'" . ','."'".  $withdraw_row->user_id . "'" . ')" class="btn btn-primary btn-sm">Decline</a>';
				} else{
					
					$row[] = $withdraw_row->remarks;
				}
				$data[] = $row;
				$i++;
			}
			
			$output = array(
			"draw" => $_POST['draw'],
			"recordsTotal" => $total_count,
			"recordsFiltered" =>$filtered_count,
			"data" => $data,
			);
        }
       
		echo json_encode($output);exit;
    }
	
	public function ajaxbctransactions()
    {
		error_reporting(0);
		
        $user = Auth::user();
		
		$search_value = '';
		
		$total_count = DB::table('bc_transations')->count();
		
		$filtered_count = 0;
		
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        
		if(is_null($user->OTP))
        {
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
			
			if(isset($_POST['fexport_check']) && $_POST['fexport_check'] == 1)
			{		
				$_POST['length'] = $this->_blockchain_export_count;		
				
				if(isset($_POST['fstart_date'])){
					$_POST['start_date'] = $_POST['fstart_date'];
				}				
				if(isset($_POST['fend_date'])){
					$_POST['end_date'] = $_POST['fend_date'];
				}
				
				if(isset($_POST['fsearch_text'])){
					$_POST['search_text'] = $_POST['fsearch_text'];
				}
				
				if(isset($_POST['fstart']) && $_POST['fstart'] != -1)
				{
					$_POST['start'] = $_POST['fstart'];
				}
				else
				{
					$_POST['start'] = 0;
					$_POST['length'] = $total_count;		
				}
		
				$transaction_list = Blockchain::get_datatables_join_blockchain($column_order,$_POST,'', 0);			
				$data = array();
								
				foreach($transaction_list as $transaction_row)
				{
					$rowExcel = array();				
					$rowExcel[] = date("d/M/Y H:i:s",strtotime($transaction_row->time_stamp));
					$rowExcel[] = $transaction_row->txid;
					$rowExcel[] = CommonHelper::balance_format($transaction_row->amount,6);
					$rowExcel[] = CommonHelper::fees_format($transaction_row->fees,6);
					$rowExcel[] = $transaction_row->type;
					$rowExcel[] = $transaction_row->blockNumber;	
					$rowExcel[] = $transaction_row->confirmations;
					$rowExcel[] = $transaction_row->from_address;
					$rowExcel[] = $transaction_row->to_address;
					$data[] = $rowExcel;				
				}
				
				$setRec 		= $data;
				$setCounter 	= count($data);
				$setMainHeader  = "";
				$setData		= "";
				$setExcelName = "Blockchain_transactions_".date("Y_m_d_H_i_s");
				$customHeader = array("DateTime","Txid","Amount","Fees","Type","BlockNumber","Confirmations","From address","To address");
				ob_start();
				$this->download_csv($setCounter,$setExcelName,$setRec,$setMainHeader,$setData,$customHeader,$data);exit;
			}
			
			$transaction_list = Blockchain::get_datatables_join_blockchain($column_order,$_POST, '', 0);
				
			$transaction_count = Blockchain::get_datatables_join_blockchain($column_order,$_POST, '', 1);
			
			if(!empty($_POST['search_text']) || !empty($_POST['start_date']))
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
			
			$data = array();
			
			foreach($transaction_list as $transaction_row)
			{
				$row = array();				
				$row[] = $i;				
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
				$i++;
			}
			
			$output = array(
			"draw" => $_POST['draw'],
			"recordsTotal" => $total_count,
			"recordsFiltered" =>$filtered_count,
			"data" => $data,
			);
        }
        else
        {
            
        }
		echo json_encode($output);exit;
    }
	
	
	public function update_kyc_ajax(Request $request)
	{
		$id = $_POST['id'];
		$status = $_POST['status'];
		$type = $_POST['type'];
		$response = array();
		$response['status'] = false;
		$response['message'] = '';
		if($id > 0 && $status!='' && $type!='')
		{
			if($type == 'dlf')
			{
				$responseStatus = DB::table('file_attachments')->where('user_id',$id)->where('type','DLF')->orWhere('type','DLB')->update(['status' =>$status]);
			}
			else if($type == 'poa')
			{
				$responseStatus =  DB::table('file_attachments')->where('user_id',$id)->where('type','poa')->update(['status' =>$status]);
			}
			
			if($responseStatus)
			{
				$response['status'] = true;
			}
			
			if($status == 0){
				$status_msg = "in pending";				
			}
			elseif($status == 1){
				$status_msg = "approved";				
			}
			elseif($status == 2){
				$status_msg = "declined";	
			}
			
			if($type == 'poa'){
				$message = "Proof of address has been $status_msg successfully";
			}
			else if($type == 'dlf'){
				$message = "National ID has been $status_msg successfully";
			}			
			$response['message'] = $message;
		}
		echo json_encode($response,true);exit;
	}
	
	public function updateuserconfig(Request $request)
	{
		parse_str($_POST['data'], $postarray);		
		$response = array();
		$response['status'] = "0";
		$response['message'] = 'error';
		$updateArray = array();	
		$userConfigId = $postarray['userConfigId'];
		if($userConfigId > 0)
		{
			if(isset($postarray['exclude_saleslist']) && $postarray['exclude_saleslist'] == 1){
				$updateArray['exclude_saleslist'] = 1;
			}
			else{
				$updateArray['exclude_saleslist'] = 0;
			}
		
			if(isset($postarray['exclude_salestoplist']) && $postarray['exclude_salestoplist'] == 1){
				$updateArray['exclude_salestoplist'] = 1;
			}
			else{
				$updateArray['exclude_salestoplist'] = 0;
			}
			
			if(isset($postarray['make_user_invisible']) && $postarray['make_user_invisible'] == 1){
				$updateArray['make_user_invisible'] = 1;
			}
			else{
				$updateArray['make_user_invisible'] = 0;
			}
		
			if(isset($postarray['admin_opt_bonus']) && $postarray['admin_opt_bonus'] == 1){
				$updateArray['admin_opt_bonus'] = 1;
			}
			else{
				$updateArray['admin_opt_bonus'] = 0;
			}
		
			if(isset($postarray['downline']) && $postarray['downline'] == 1){
				$updateArray['downline'] = 1;
			}
			else{
				$updateArray['downline'] = 0;
			}
			
			if(isset($postarray['exclude_toplist']) && $postarray['exclude_toplist'] == 1){
				$updateArray['exclude_toplist'] = 1;
			}
			else{
				$updateArray['exclude_toplist'] = 0;
			}
			
			if(isset($postarray['bonus_level'])){
				$updateArray['bonus_level'] = $postarray['bonus_level'];
			}
			else{
				$updateArray['bonus_level'] = 0;
			}
			if(isset($postarray['exclude_payment_transaction']) && $postarray['exclude_payment_transaction'] == 1){
				$updateArray['exclude_payment_transaction'] = 1;
			}
			else{
				$updateArray['exclude_payment_transaction'] = 0;
			}
			
			$userInfo = User::find($userConfigId);
			
			$record = 
			[
				'user_id'	=> Auth::user()->id,
				'message'   => 'Made configuration changes for user: '.$userInfo->email,
				'level'     => 'INFO',
				'context'   => 'User configuration',
				'extra' => [
					'userConfiguration' => json_encode($postarray)
				]
			];
			LoggerHelper::writeDB($record);

			User::where('id',$userConfigId)->update($updateArray);
			$response['status'] = "1";
			$response['message'] = 'success';
		}
		echo json_encode($response,true);exit;
	}
	
	public function removeuserconfig(Request $request)
	{
		$response = array();
		$response['status'] = "0";
		$response['message'] = 'error';
		$updateArray = array();	
		$userConfigId = $_POST['userConfigId'];
		$removeType = $_POST['removeType'];
		if($userConfigId > 0 && isset($removeType))
		{
			$updateArray[$removeType] = 0;
			//print_r($updateArray);die;
			User::where('id',$userConfigId)->update($updateArray);
			$response['status'] = "1";
			$response['message'] = 'success';
		}
		echo json_encode($response,true);exit;
	}
	
	public function updateuserfakesales(Request $request)
	{
		parse_str($_POST['data'], $postarray);		
		$response = array();
		$response['status'] = "0";
		$response['message'] = 'error';
		$updateArray = array();			
		$userConfigId = $postarray['fakeSaleUserId'];
		if($userConfigId > 0)
		{
			if(isset($postarray['sales_euro_level_1']) && $postarray['sales_euro_level_1'] > 0){
				$updateArray['sales_euro_level_1'] = $postarray['sales_euro_level_1'];
			}			
			if(isset($postarray['sales_euro_level_2']) && $postarray['sales_euro_level_2'] > 0){
				$updateArray['sales_euro_level_2'] = $postarray['sales_euro_level_2'];
			}			
			if(isset($postarray['sales_euro_level_3']) && $postarray['sales_euro_level_3'] > 0){
				$updateArray['sales_euro_level_3'] = $postarray['sales_euro_level_3'];
			}			
			if(isset($postarray['sales_euro_level_4']) && $postarray['sales_euro_level_4'] > 0){
				$updateArray['sales_euro_level_4'] = $postarray['sales_euro_level_4'];
			}			
			if(isset($postarray['sales_euro_level_5']) && $postarray['sales_euro_level_5'] > 0){
				$updateArray['sales_euro_level_5'] = $postarray['sales_euro_level_5'];
			}			
			User::where('id',$userConfigId)->update($updateArray);
			$response['userInfo'] = User::find($userConfigId);
			$response['status'] = "1";
			$response['message'] = 'success';
		}
		echo json_encode($response,true);exit;
	}
	
	public function addusercomment(Request $request)
	{
		$user = Auth::user();
		parse_str($_POST['data'], $postarray);		
		$response = array();
		$response['status'] = "0";
		$response['message'] = 'error';
		$updateArray = array();	
		$admin_comment_user_id = $postarray['admin_comment_user_id'];
		$admin_comment = $postarray['admin_comment_messsage'];
		if($admin_comment_user_id > 0 && isset($admin_comment) && !empty($admin_comment))
		{			
			$addCommentData = array();			
			$addCommentData['transaction_id'] = NULL;
			$addCommentData['currency'] = NULL;
			$addCommentData['amount'] = 0;
			$addCommentData['user_id'] = $admin_comment_user_id;
			$addCommentData['comments'] = $admin_comment;
			$addCommentData['created_at'] = date("Y-m-d H:i:s");
			$addCommentData['comment_by'] = $user->id;
			User::insertIntoTable("admin_comments",$addCommentData);
			$response['status'] = "1";		
			$response['message'] = 'success';
		}
		echo json_encode($response,true);exit;
	}
	
	public function update_wallet_balance(Request $request)
	{
		error_reporting(0);
		parse_str($_POST['data'], $postarray);
		$response = array();
		$status = false;
		if(isset($postarray['userId']))
		{
			// For multiple phases calculations - Select active phases data
            $activePhaseData = Phases::where('status','1')->first();
            if(!isset($activePhaseData)) {
                $user= Auth::user();
                $record = [
                    'message'   => 'Username '.$user->email.' Phase not activated ',
                    'level'     => 'ERROR',
                    'context'   => 'Transfer Fund'
                ];
                LoggerHelper::writeDB($record);
				$response['status'] = false;
				$response['message'] = 'The ELT tokens are not available, please contact admin for further detail';
				echo json_encode($response,true);exit;
            }
			
			if(isset($activePhaseData) && !empty($activePhaseData->id))
            {
                $phaseId = $activePhaseData->id;
            }
            else
            {
                $phaseId = NULL;
            }
			
			$userId = $postarray['userId'];
			$userInfo = User::find($userId);
			
			$user_log_activity = array();
			
			if($postarray['add_deduct_type_btc'] == 'credit')
			{
				$userInfo->addValue('BTC_balance',$postarray['add_deduct_amount_btc']);	
				$amount = $postarray['add_deduct_amount_btc'];
				Transactions::createTransaction($userId,'BTC',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
				
				$user_log_activity[] = "BTC updation:".$amount;
			}
			elseif($postarray['add_deduct_type_btc'] == 'debit')
			{
				if($postarray['add_deduct_amount_btc'] <= $userInfo->BTC_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_btc']);
					
					$userInfo->subtractValue('BTC_balance',abs($postarray['add_deduct_amount_btc']));
					
					Transactions::createTransaction($userId,'BTC',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
					
					$user_log_activity[] = "BTC updation:".$amount;
					
				}				
			}
			
			if($postarray['add_deduct_type_eth'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_eth']);
				
				$userInfo->addValue('ETH_balance',$amount);
				
				Transactions::createTransaction($userId,'ETH',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
				
				$user_log_activity[] = "ETH updation:".$amount;
				
			}
			elseif($postarray['add_deduct_type_eth'] == 'debit')
			{
				if($postarray['add_deduct_amount_eth'] <= $userInfo->ETH_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_eth']);
					
					$userInfo->subtractValue('ETH_balance',abs($postarray['add_deduct_amount_eth']));
					
					Transactions::createTransaction($userId,'ETH',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
					
					$user_log_activity[] = "ETH updation:".$amount;
					
				}				
			}
			
			if($postarray['add_deduct_type_elt'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_elt']);
				
				$userInfo->addValue('ELT_balance',$amount);
				
				Transactions::createTransaction($userId,'ELT',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
				
				$user_log_activity[] = "ELT updation:".$amount;
				
				
			}
			elseif($postarray['add_deduct_type_elt'] == 'debit')
			{
				if($postarray['add_deduct_amount_elt'] <= $userInfo->ELT_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_elt']);
					
					$userInfo->subtractValue('ELT_balance',abs($postarray['add_deduct_amount_elt']));
					
					Transactions::createTransaction($userId,'ELT',$amount, 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
					
					$user_log_activity[] = "ELT updation:".$amount;
				}
			}
			
			if($postarray['add_deduct_type_eur'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_eur']);
				
				$userInfo->addValue('EUR_balance',$amount);
				
				Transactions::createTransaction($userId,'EUR',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
				
				$user_log_activity[] = "EUR updation:".$amount;
				
			}
			elseif($postarray['add_deduct_type_eur'] == 'debit')
			{
				if($postarray['add_deduct_amount_eur'] <= $userInfo->EUR_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_eur']);
					
					$userInfo->subtractValue('EUR_balance',abs($postarray['add_deduct_amount_eur']));
					
					Transactions::createTransaction($userId,'EUR',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
					
					$user_log_activity[] = "EUR updation:".$amount;
					
				}				
			}
			
			if($postarray['add_deduct_type_bch'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_bch']);
				$userInfo->addValue('BCH_balance',$amount);
				Transactions::createTransaction($userId,'BCH',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
				$user_log_activity[] = "BCH updation:".$amount;
			}
			elseif($postarray['add_deduct_type_bch'] == 'debit')
			{
				if($postarray['add_deduct_amount_bch'] <= $userInfo->BCH_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_bch']);
					$userInfo->subtractValue('BCH_balance',abs($postarray['add_deduct_amount_bch']));
					Transactions::createTransaction($userId,'BCH',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
					$user_log_activity[] = "BCH updation:".$amount;
				}
			}
			
			if($postarray['add_deduct_type_ltc'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_ltc']);
				$userInfo->addValue('LTC_balance',$amount);
				Transactions::createTransaction($userId,'LTC',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
				$user_log_activity[] = "LTC updation:".$amount;
			}
			elseif($postarray['add_deduct_type_ltc'] == 'debit')
			{
				if($postarray['add_deduct_amount_ltc'] <= $userInfo->LTC_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_ltc']);
					$userInfo->subtractValue('LTC_balance',abs($postarray['add_deduct_amount_ltc']));
					Transactions::createTransaction($userId,'LTC',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
					$user_log_activity[] = "LTC updation:".$amount;
				}
			}
			
			if($postarray['add_deduct_type_etc'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_etc']);
				$userInfo->addValue('ETC_balance',$amount);
				Transactions::createTransaction($userId,'ETC',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
				$user_log_activity[] = "ETC updation:".$amount;
			}
			elseif($postarray['add_deduct_type_etc'] == 'debit')
			{
				if($postarray['add_deduct_amount_etc'] <= $userInfo->ETC_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_etc']);
					$userInfo->subtractValue('ETC_balance',abs($postarray['add_deduct_amount_etc']));
					Transactions::createTransaction($userId,'ETC',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
					$user_log_activity[] = "ETC updation:".$amount;
				}				
			}
			
			if($postarray['add_deduct_type_xrp'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_xrp']);
				$userInfo->addValue('XRP_balance',$amount);
				Transactions::createTransaction($userId,'XRP',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
				$user_log_activity[] = "XRP updation:".$amount;
			}
			elseif($postarray['add_deduct_type_xrp'] == 'debit')
			{
				if($postarray['add_deduct_amount_xrp'] <= $userInfo->XRP_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_xrp']);
					$userInfo->subtractValue('XRP_balance',abs($postarray['add_deduct_amount_xrp']));
					Transactions::createTransaction($userId,'XRP',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
					$user_log_activity[] = "XRP updation:".$amount;
				}
			}
			
			if($postarray['add_deduct_type_dash'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_dash']);
				$userInfo->addValue('DASH_balance',$amount);
				Transactions::createTransaction($userId,'DASH',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
				$user_log_activity[] = "DASH updation:".$amount;
			}
			elseif($postarray['add_deduct_type_dash'] == 'debit')
			{
				if($postarray['add_deduct_amount_dash'] <= $userInfo->DASH_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_dash']);
					$userInfo->subtractValue('DASH_balance',abs($postarray['add_deduct_amount_dash']));
					Transactions::createTransaction($userId,'DASH',$amount , 'Adjustment by admin of amount:'.$amount, 1, uniqid(), $phaseId, NULL, 5, NULL, 'bonus');
					$user_log_activity[] = "DASH updation:".$amount;
				}
			}

			if($userInfo->save())
			{
				$response['status'] = true;
			
				//Check if process referral bonus in checked
				if(isset($postarray['processReferral']))
				{
					$configuration = Configurations::where('valid_to', '=', "9999-12-31")->get();
				
					//Getting all referral commission
					foreach($configuration as $config)
					{
						if($config->name == "Referral-%-Level-1") {
							$commissionLevel1 = $config->defined_value;
						} elseif($config->name == "Referral-%-Level-2") {
							$commissionLevel2 = $config->defined_value;
						} elseif($config->name == "Referral-%-Level-3") {
							$commissionLevel3 = $config->defined_value;
						}
					}           

					/*Calculations for referral system*/
					//If amount is being added not subtracted and its not of ELT Balance TYPE
					if($postarray['source_amount']>0 && $postarray['source_wallet_type'] != 'ELT_balance')
					{
						$user1 = User::find($userInfo->referrer_user_id);
						if($user1 == null) {
							$response['userInfo'] = $userInfo;
							echo json_encode($response,true);exit;
						}
						if($postarray['source_wallet_type'] == "BTC_balance"){
                            $trans = "BTC";
						}
						if($postarray['source_wallet_type'] == "EUR_balance"){
							$trans = "EUR";
						}
						if($postarray['source_wallet_type'] == "ELT_balance"){
							$trans = "ELT";
						}
						if($postarray['source_wallet_type'] == "ETH_balance"){
							$trans = "ETH";
						}
						$commisionUser1 = User::calculateCommision($postarray['source_amount'],$commissionLevel1);
						if($commisionUser1>0) {
							$user1->addValue($postarray['source_wallet_type'],$commisionUser1);
						}
						$user1->save();
						
						Transactions::createTransaction($user1->id, $trans, $commisionUser1, 'Commission by User referral', 1, uniqid(), $phaseId, NULL, 5, NULL, 'admin');

						$user2 = User::find($user1->referrer_user_id);
						if($user2 == null) {
							$response['userInfo'] = $userInfo;
							echo json_encode($response,true);exit;
						}
						
						$commisionUser2 = User::calculateCommision($postarray['source_amount'],$commissionLevel2);
						if($commisionUser2>0) {
							$user2->addValue($postarray['source_wallet_type'],$commisionUser2);
						} 
						$user2->save();
						Transactions::createTransaction($user2->id, $trans, $commisionUser2, 'Commission by User referral', 1, uniqid(), $phaseId, NULL, 5, NULL, 'admin');

						$user3 = User::find($user2->referrer_user_id);
						if($user3 == null) {							
							$response['userInfo'] = $userInfo;
							echo json_encode($response,true);exit;
						}
						$commisionUser3 = User::calculateCommision($postarray['source_amount'],$commissionLevel3);
						if($commisionUser3>0) {
							$user3->addValue($postarray['source_wallet_type'],$commisionUser3);
						} 
						$user3->save();
						Transactions::createTransaction($user3->id,$trans, $commisionUser3, 'Commission by User referral', 1, uniqid(), $phaseId, NULL, 5, NULL, 'admin');
					}			  
				}	
				
				$record = [
					'message'   => 'Account balance updation: '.$user->email.' via admin',
					'level'     => 'INFO',
					'context'   => 'Account updation for user '.implode(", ",$user_log_activity).' via admin',
					'userId'	=> $userId
				];
				LoggerHelper::writeDB($record);
			
			}
			else
			{
				$response['status'] = false;
			}			
		}		
		
		$userInfo->BTC_balance = CommonHelper::format_float_balance($userInfo->BTC_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->ETH_balance = CommonHelper::format_float_balance($userInfo->ETH_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->EUR_balance = CommonHelper::format_float_balance($userInfo->EUR_balance,config("constants.EUR_PRECISION"));
		$userInfo->ELT_balance = round($userInfo->ELT_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->BCH_balance = CommonHelper::format_float_balance($userInfo->BCH_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->LTC_balance = CommonHelper::format_float_balance($userInfo->LTC_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->ETC_balance = CommonHelper::format_float_balance($userInfo->ETC_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->XRP_balance = CommonHelper::format_float_balance($userInfo->XRP_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->DASH_balance = CommonHelper::format_float_balance($userInfo->DASH_balance,config("constants.DEFAULT_PRECISION"));
		
		$response['userInfo'] = $userInfo;
		echo json_encode($response,true);exit;
	}
	
	public function update_sponsor(Request $request)
	{
		$response = array();
		
		$status = false;
		
		error_reporting(0);
		
		$userModel = new User();

		if(isset($_POST['findUserId']) && isset($_POST['currentUserId']))
		{
			$userId = $_POST['currentUserId'];
			
			$userInfo = User::find($userId);
			
			$userInfo->referrer_user_id = $_POST['findUserId'];
			
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
			
			$htmlContent = '';
			if(isset($parent_level1_users) && !empty($parent_level1_users))
			{
				$htmlContent.='<tr class="">
				   <td>'.$parent_level1_users->id.'</td>
				   <td >Level 1</td>
				   <td><a href="'.route('admin.usersdetail',['id'=> $parent_level1_users->id ]).'"  title="View Detail">'.$parent_level1_users->first_name.' '.$parent_level1_users->last_name.'</a></td>
				   <td>'.$parent_level1_users->email.'</td></tr>';
			}
			
			if(isset($parent_level2_users) && !empty($parent_level2_users))
			{
				$htmlContent.='<tr class="">
				   <td>'.$parent_level2_users->id.'</td>
				   <td >Level 2</td>
				   <td><a href="'.route('admin.usersdetail',['id'=> $parent_level2_users->id ]).'"  title="View Detail">'.$parent_level2_users->first_name.' '.$parent_level2_users->last_name.'</a></td>
				   <td>'.$parent_level2_users->email.'</td></tr>';
			}
			
			if(isset($parent_level3_users) && !empty($parent_level3_users))
			{
				$htmlContent.='<tr class="">
				   <td>'.$parent_level3_users->id.'</td>
				   <td >Level 3</td>
				   <td><a href="'.route('admin.usersdetail',['id'=> $parent_level3_users->id ]).'"  title="View Detail">'.$parent_level3_users->first_name.' '.$parent_level3_users->last_name.'</a></td>
				   <td>'.$parent_level3_users->email.'</td></tr>';
			}
			
			if(isset($parent_level4_users) && !empty($parent_level4_users))
			{
				$htmlContent.='<tr class="">
				   <td>'.$parent_level4_users->id.'</td>
				   <td >Level 4</td>
				   <td><a href="'.route('admin.usersdetail',['id'=> $parent_level4_users->id ]).'"  title="View Detail">'.$parent_level4_users->first_name.' '.$parent_level4_users->last_name.'</a></td>
				   <td>'.$parent_level4_users->email.'</td></tr>';
			}
			
			if(isset($parent_level5_users) && !empty($parent_level5_users))
			{
				$htmlContent.='<tr class="">
				   <td>'.$parent_level5_users->id.'</td>
				   <td >Level 5</td>
				   <td><a href="'.route('admin.usersdetail',['id'=> $parent_level5_users->id ]).'"  title="View Detail">'.$parent_level5_users->first_name.' '.$parent_level5_users->last_name.'</a></td>
				   <td>'.$parent_level5_users->email.'</td></tr>';
			}
			
			if($userInfo->save())
			{
				/* Re-structure parent child relation table for this user */
				
				ParentChild::restructure_child($userId);
				
				DB::statement("UPDATE users SET referrer_count=referrer_count+1 WHERE id=".$userInfo->referrer_user_id);
				
				$current_sponsor_id = $_POST['current_sponsor_id'];
				
				DB::statement("UPDATE users SET referrer_count=referrer_count-1 WHERE id=".$current_sponsor_id);

				$response['status'] = true;
				
				$response['htmlContent'] = $htmlContent;

				$record = [
                    'message'   => 'Changed sponsor to: '.$parent_level1_users->email.' from: '.$parent_level2_users->email,
                    'level'     => 'INFO',
                    'context'   => 'Update Sponsor',
                    'userId'   => $userId
                ];
                LoggerHelper::writeDB($record);
			}
			else
			{
				$response['status'] = false;
			}			    
		}
		$response['userInfo'] = $userInfo;
		echo json_encode($response,true);exit;
	}
	
	public function update_amount(Request $request)
	{
		$response = array();
		$status = false;
		$userModel = new User();
		if(isset($_POST['type']))
		{
			if(isset($_POST['request_id']))
			{
				$userId = $_POST['user_id'];
				$userInfo = User::find($userId);

				if($_POST['ledger'] =='BTC'){
					$amount = $_POST['amonut_t'];
					$final_amount = $userInfo->addValue('BTC_balance',$amount);	
					DB::table('users')->where('id',$_POST['user_id'])->update(['BTC_balance' =>$final_amount]);				
				}
				if($_POST['ledger'] =='ETH'){
					$amount = $_POST['amonut_t'];
					$final_amount =	$userInfo->addValue('ETH_balance',$amount);
					DB::table('users')->where('id',$_POST['user_id'])->update(['ETH_balance' =>$final_amount]);					
				}
				if($_POST['ledger'] =='EUR'){
					$amount = $_POST['amonut_t'];
					$final_amount = 	$userInfo->addValue('EUR_balance',$amount);
					DB::table('users')->where('id',$_POST['user_id'])->update(['EUR_balance' =>$final_amount]);					
				}

				$refund_transaction_id = uniqid();
				Transactions::createTransaction($userId, $balanceType, $amount, $description, $status, $transaction_id, $phaseId = 0, $address = NULL, $type = 1, $_POST['request_id'], 'withdrawal');
				
				DB::table('withdraw_request')->where('transaction_id',$_POST['request_id'])->update(['status' =>'2']);
				
				DB::table('withdraw_request')->where('transaction_id',$_POST['request_id'])->update(['remarks' =>$_POST['remarks']]);
				
				DB::table('transactions')->where('transaction_id',$_POST['request_id'])->update(['status' =>'0']);

				$response['status1'] = true;
				
				echo json_encode($response,true);exit;
			}
		} 
		else
		{
			if(isset($_POST['request_id'])) 
			{
				DB::table('withdraw_request')->where('transaction_id',$_POST['request_id'])->update(['status' =>'1']);
				DB::table('withdraw_request')->where('transaction_id',$_POST['request_id'])->update(['remarks' =>$_POST['remarks']]);
				DB::table('transactions')->where('transaction_id',$_POST['request_id'])->update(['status' =>'1']);
				$response['status'] = true;
				echo json_encode($response,true);exit;
			}
		}		
	}
	
	public function check_username(Request $request)
	{
		$response = array();
		$status = false;
		$historyArray = array();
		$userModel = new User();
		if(!empty($_POST['username']))
		{
			$user_row = $userModel->check_username($_POST['username']);
			if($user_row)
			{
				$response['data'] = $user_row;
				$response['status'] = true;
				$response['msg'] = "success";
			}
			else
			{
				$response['status'] = false;
				$response['msg'] = "fail";
			}
		}
		else
		{
			$response['status'] = false;
			$response['msg'] = "fail";
		}
		echo json_encode($response,true);exit;
	}
	
	public function update_elt_balance(Request $request)
	{
		parse_str($_POST['data'], $postarray);
		$response = array();
		
		$response['status'] = "0";
		$response['msg'] = "fail";

		if(isset($postarray['receiverId']) && isset($postarray['transaction_id']) && isset($postarray['elt_amount']))
		{
			$userId = $postarray['receiverId'];
			$txn_id = $postarray['transaction_id'];			
			$elt_amount = $postarray['elt_amount'];
			$userInfo = User::find($userId);
			
			$updateStatus = Transactions::where('transaction_id', $txn_id)->where('user_id', $userId)->update(['status' => 1]);
			
			if($updateStatus)
			{
				$transactionInfo = Transactions::get_transactions_list(array($txn_id));

				$userInfo->addValue('ELT_balance',$transactionInfo[0]->value);

				if($transactionInfo[0]->term_currency == 'NEXO'){
					$userInfo->addValue('NEXO_balance',$transactionInfo[0]->term_amount);
				}
				elseif($transactionInfo[0]->term_currency == 'SALT'){
					$userInfo->addValue('SALT_balance',$transactionInfo[0]->term_amount);
				}
				elseif($transactionInfo[0]->term_currency == 'ORME'){
					$userInfo->addValue('ORME_balance',$transactionInfo[0]->term_amount);
				}
				
				if($userInfo->save())
				{
					$record = 
					[
						'message'   => 'Account balance updation for user: '.$userInfo->email.' via admin',
						'level'     => 'INFO',
						'context'   => 'ELT account updated with amount: '.$transactionInfo[0]->value.' against coin name:'.$transactionInfo[0]->term_currency.' amount: '.$transactionInfo[0]->term_amount.' via admin',
						'userId'	=> $userId
					];
					LoggerHelper::writeDB($record);
				}
			}			
			
			$response['status'] = "1";
			$response['msg'] = "success";
		}		
		echo json_encode($response,true);exit;
	}
	
	public function cancel_coin_transaction(Request $request)
	{
		$response = array();
		
		$status = "0";
		
		if(isset($_POST['transaction_id']) && isset($_POST['user_id']))
		{
			$txn_id = $_POST['transaction_id'];
			
			$user_id = $_POST['user_id'];
			
			Transactions::where('transaction_id', $txn_id)->where('user_id', $user_id)->update(['status' => 0]);
			
			$record = 
			[
				'message' => 'Transaction cancelled; Txn_id :  ' . $txn_id,
				'level' => 'INFO',
				'context' => 'Admin cancelled transaction',
				'extra' => [
					'request' => $request->all()
				]
			];
			LoggerHelper::writeDB($record);
			
			$response['status'] = "1";
			
			$response['msg'] = "success";
		}
		else
		{
			$response['status'] = "1";
			
			$response['msg'] = "fail";
		}

		echo json_encode($response,true);exit;
	}
	
	public function ajaxchild_transactions(Request $request)
	{
		$response = array();		
		$status = "0";		
		$htmlContent="";
		if(isset($_POST['transaction_id']))
		{
			$txn_id = $_POST['transaction_id'];
			$Transactions = Transactions::get_child_transaction($txn_id);
			if($Transactions){
				foreach($Transactions as $transaction_row){
					$currencyName='';
					$currencyAmount='';
					$eltAmount='';
					if($transaction_row->term_currency != NULL) 
					{
						$currencyName = $transaction_row->term_currency;
					}
					else
					{
						$currencyName = $transaction_row->ledger;
					}
					if($transaction_row->ledger == 'ELT')
					{
						$currencyAmount = $transaction_row->term_amount == NULL ? $transaction_row->term_amount : '-';
						$eltAmount = round($transaction_row->value,6);
					}
					else
					{
						$currencyAmount = round($transaction_row->value,6);
						$eltAmount = '-';
					}
					
					$status = '-';
					if($transaction_row->status == 0)
					{
						$status = 'Failed';
					} 
					else if($transaction_row->status == 1)
					{
						$status = 'Success';
					}
					else if($transaction_row->status == 2)
					{
						$status = 'Pending';
					}
					
					$unpaid_bonus=0;
					
					if($currencyName == 'EUR')
					{
						$unpaid_bonus = CommonHelper::format_float_balance($transaction_row->unpaid_bonus, config("constants.EUR_PRECISION"));
					}
					else
					{
						$unpaid_bonus = CommonHelper::format_float_balance($transaction_row->unpaid_bonus, config("constants.DEFAULT_PRECISION"));
					}
					
					if($currencyName == 'EUR' && $currencyAmount > 0){
						$currencyAmount = CommonHelper::format_float_balance($currencyAmount,config("constants.EUR_PRECISION"));
					}
					elseif($currencyName != 'EUR' && $currencyAmount > 0){
						$currencyAmount = CommonHelper::format_float_balance($currencyAmount,config("constants.DEFAULT_PRECISION"));
					}
					
					$total_bonus = $currencyAmount + $unpaid_bonus;
					
					$bonus_per = $total_bonus > 0 ? ($currencyAmount * 100) / $total_bonus : '0';
					$unpaid_bonus_per = $total_bonus > 0 ? ($unpaid_bonus * 100) / $total_bonus : '0';
					
					$htmlContent.='<tr>';
					$htmlContent.='<td>'.$transaction_row->transaction_id.'</td>';
					$htmlContent.='<td>'.date("m/d/Y",strtotime($transaction_row->created_at)).'</td>';
					$htmlContent.='<td>'.$transaction_row->email.'</td>';
					$htmlContent.='<td>'.$transaction_row->type_name.'</td>';
					$htmlContent.='<td>'.$currencyName.'</td>';
					$htmlContent.='<td>'.$currencyAmount.'</td>';
					$htmlContent.='<td>'.$bonus_per.'</td>';
					$htmlContent.='<td>'.$unpaid_bonus.'</td>';
					$htmlContent.='<td>'.$unpaid_bonus_per.'</td>';
					$htmlContent.='<td>'.$eltAmount.'</td>';
					$htmlContent.='<td>'.$status.'</td>';
					$htmlContent.='<td>'.$transaction_row->description.'</td>';
					$htmlContent.='</tr>';
				}
			}
			else
			{
				$htmlContent="<tr><td colspan='12'>No child transaction(s) found</td></tr>";
			}
			$response['htmlContent'] = $htmlContent;
			$response['status'] = "1";			
			$response['msg'] = "success";
		}
		else
		{
			$response['status'] = "1";
			$response['msg'] = "fail";
		}		
		echo json_encode($response,true);exit;
	}
	
	public function ajaxchild_payments(Request $request)
	{
		$response = array();		
		$status = "0";		
		$htmlContent="";
		if(isset($_POST['transaction_id']))
		{
			$txn_id = $_POST['transaction_id'];
			$Transactions = Transactions::get_child_transaction($txn_id);
			if($Transactions){
				foreach($Transactions as $transaction_row){
					$currencyName='';
					$currencyAmount='';
					$eltAmount='';
					if($transaction_row->term_currency != NULL) 
					{
						$currencyName = $transaction_row->term_currency;
					}
					else
					{
						$currencyName = $transaction_row->ledger;
					}
					if($transaction_row->ledger == 'ELT')
					{
						$currencyAmount = $transaction_row->term_amount == NULL ? $transaction_row->value : '-';
						$eltAmount = round($transaction_row->value,6);
					}
					else
					{
						$currencyAmount = round($transaction_row->value,6);
						$eltAmount = '-';
					}
					
					$status = '-';
					if($transaction_row->status == 0)
					{
						$status = 'Failed';
					} 
					else if($transaction_row->status == 1)
					{
						$status = 'Success';
					}
					else if($transaction_row->status == 2)
					{
						$status = 'Pending';
					}
					
					$unpaid_bonus = CommonHelper::format_float_balance($transaction_row->unpaid_bonus, config("constants.DEFAULT_PRECISION"));
					$currencyAmount = CommonHelper::format_float_balance($currencyAmount,config("constants.DEFAULT_PRECISION"));
					
					$total_bonus = $currencyAmount + $unpaid_bonus;
					
					$bonus_per = $total_bonus > 0 ? ($currencyAmount * 100) / $total_bonus : '0';
					$unpaid_bonus_per = $total_bonus > 0 ? ($unpaid_bonus * 100) / $total_bonus : '0';
					
					$htmlContent.='<tr>';
					$htmlContent.='<td>'.$transaction_row->transaction_id.'</td>';
					$htmlContent.='<td>'.date("m/d/Y",strtotime($transaction_row->created_at)).'</td>';
					$htmlContent.='<td>'.$transaction_row->email.'</td>';
					$htmlContent.='<td>'.$transaction_row->type_name.'</td>';
					$htmlContent.='<td>'.$currencyName.'</td>';
					$htmlContent.='<td>'.$currencyAmount.'</td>';
					$htmlContent.='<td>'.$bonus_per.'%</td>';
					$htmlContent.='<td>'.$unpaid_bonus.'</td>';
					$htmlContent.='<td>'.$unpaid_bonus_per.'%</td>';
					$htmlContent.='<td>'.$transaction_row->description.'</td>';
					$htmlContent.='</tr>';
				}
			}
			else
			{
				$htmlContent="<tr><td colspan='11'>No child transaction(s) found</td></tr>";
			}
			$response['htmlContent'] = $htmlContent;
			$response['status'] = "1";			
			$response['msg'] = "success";
		}
		else
		{
			$response['status'] = "1";
			$response['msg'] = "fail";
		}		
		echo json_encode($response,true);exit;
	}
	
	/* Ajax call for All Loan Priority */
	public function ajaxproforma()
    {				
        $user = Auth::user();
		
		$search_value = '';
		
		$total_count = Proforma::total_count();
		
		$filtered_count = 0;
		
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        
		if(is_null($user->OTP))
        {
			$column_order = array
			(
				"proforma_invoices.created_at",
				"proforma_invoices.reference_no",
				"users.first_name",
				"users.last_name",
				"users.email"
			);			
			$transaction_list = Proforma::get_datatables_join($column_order,$_POST);
			$transaction_count = Proforma::get_datatables_join($column_order,$_POST,1);
			if( !empty($_POST['search_text']) || !empty($_POST['start_date']) )
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
			$data = array();
			//print_r($transaction_list);die;
			foreach($transaction_list as $transaction_row)
			{
				//print_r($transaction_row);
				$row = array();				
				$row[] = $i;				
				$row[] = $transaction_row->reference_no;	
				$row[] = $transaction_row->first_name.' '.$transaction_row->last_name;				
				if(!CommonHelper::isUserAllowedForSubAdmin(Auth::user()->custom_role,$transaction_row->id))
				{
					$row[] = $transaction_row->email;
				}
				else
				{
					$row[] = "<a href='" . route('admin.usersdetail', ['id' => $transaction_row->user_id]) . "'>" . $transaction_row->email . "</a>";
				}
				$row[] = CommonHelper::format_float_balance($transaction_row->elt_amount,config("constants.DEFAULT_PRECISION"));
				$row[] = $transaction_row->token_price;
				$row[] = CommonHelper::format_float_balance($transaction_row->elt_amount * $transaction_row->token_price,config("constants.EUR_PRECISION"));					
				$row[] = date("d/M/Y", strtotime($transaction_row->created_at));
				$row[] = "";
				$data[] = $row;
				$i++;
			}
			//die('..end...');
			
			$output = array(
				"draw" => $_POST['draw'],
				"recordsTotal" => $total_count,
				"recordsFiltered" =>$filtered_count,
				"data" => $data,
			);
        }
        else
        {
            
        }
		echo json_encode($output);exit;
    }
	
}
