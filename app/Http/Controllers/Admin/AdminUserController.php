<?php
namespace App\Http\Controllers\Admin;
use DB;
use Mail;
use Carbon\Carbon;
use App\Models\Logs;
use App\Models\User;
use App\Models\Phases;
use App\Models\Country;
use App\Models\Blockchain;
use App\Models\Withdrawal;
use App\Models\Activation;
use App\Models\ParentChild;
use App\Models\Transactions;
use App\Models\AdminComments;
use App\Models\FileAttachments;
use App\Models\Configurations;
use App\Models\ChangeRequests;
use App\Helpers\LoggerHelper;
use App\Helpers\CommonHelper;
use App\Helpers\GeneratePDF;
use Illuminate\Support\Facades\Route;
use App\Notifications\SendChangeEmailRequest;
use App\Notifications\SendChangeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use App\Traits\ActivationTrait;
//use App\Notifications\SendActivationEmail;
use App\Notifications\SendNewActivationEmail;
use App\Notifications\SendWhiteListWelcomeEmail;
use jeremykenedy\LaravelRoles\Models\Role;

class AdminUserController extends Controller
{
	private $currentRouteName;
	private $allowed_routes = array();
	private $_export_limit = 25000;
	
	public function __construct()
    {
		$this->middleware(function ($request, $next) {
			
		$this->user= Auth::user();
		
		$session_token = session('login_token');
				
		$this->allowed_routes = array("admin.users","admin.ajaxusers","admin.usersdetail");
		
		$this->currentRouteName = \Request::route()->getName();
		
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
		$dataForView = array();     

		$user = Auth::user();
		
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		
		$is_tab_accessible = User::check_page_access('user-tab-info',Auth::user()->custom_role);
		$dataForView['is_tab_accessible'] = $is_tab_accessible;	

		$is_export_accessible = User::check_page_access('admin.exportusers',Auth::user()->custom_role);
		$dataForView['is_export_accessible'] = $is_export_accessible;	
		
        if(is_null($user->OTP))
        {
			$get_user_stats = $this->getUserStats('','');			
			$total_record = $get_user_stats['user_total'];
			$length = $this->_export_limit;
			$dataForView['_user_export_count'] = $this->_export_limit;
			if($total_record > $length )
			{
				$loop_counter = (int)($total_record/$length) + 1;
				for($index=0; $index < $loop_counter; $index++)
				{
					$start = $end = 0;					
					$start = ($index*$length+1);
					$end = ($index+1)*$length;
					if($index == ($loop_counter-1))
					{
						$end = $total_record;
					}
					$dataForView['export_loop'][$index] = array("start"=>$start,"end"=>$end);
				}
			}
			$dataForView['total_users'] = $get_user_stats['user_total'];
			$dataForView['today_users'] = $get_user_stats['user_today'];
			$dataForView['yesterday_users'] = $get_user_stats['user_yesterday'];
			$dataForView['this_week_users'] = $get_user_stats['user_this_week'];
			$dataForView['last_week_users'] = $get_user_stats['user_last_week'];
			$dataForView['this_month_users'] = $get_user_stats['user_this_month'];
			$dataForView['last_month_users'] = $get_user_stats['user_last_month'];
			$dataForView['this_year_users'] = $get_user_stats['user_this_year'];
			$dataForView['last_year_users'] = $get_user_stats['user_last_year'];
			$dataForView['total_active_users'] = $get_user_stats['total_active_users'];
            return view('admin.users',$dataForView);			
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function loanpriority()
    {		
        $user = Auth::user();
		$dataForView = array();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}		
        if(is_null($user->OTP))
        {
			$default_loan_apply = 0;
			$default_loan_apply += 500;			
			$default_loan_apply += 1000000;
			$default_loan_apply += 1300000;
			$default_loan_apply += 1300000;
			$dataForView['total_loan_applied'] = User::total_loan_applied();
			$dataForView['total_loan_amount'] = User::total_loan_amount();
			$dataForView['total_loan_amount'] = $dataForView['total_loan_amount'] + $default_loan_apply;
			$dataForView['total_loan_term'] = User::total_loan_term();
			$dataForView['average_loan_amount'] = User::average_loan_amount();
			$dataForView['average_loan_term'] = User::average_loan_term();
			$dataForView['total_community_bonus'] = User::total_community_bonus();
            return view('admin.loanpriority',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function proforma()
    {				
        $user = Auth::user();
		$dataForView = array();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}		
        if(is_null($user->OTP))
        {
			$dataForView['page_title'] = "Admin Proforma";
			$dataForView['page_heading'] = "Proforma Request";
            return view('admin.proforma',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	
	public function marked_users()
    {
        $user = Auth::user();
		$dataForView = array();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}		
        if(is_null($user->OTP))
        {
			$dataForView['page_title'] = "Marked User";
			$dataForView['page_heading'] = "Marked User";
            return view('admin.marked_users',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function adminroles()
    {		
        $user = Auth::user();		
		$dataForView = array();
        if(is_null($user->OTP))
        {
			if($_POST)
			{
				if(!User::checkAdminRole($_POST['role_name']))
				{
					if(User::insertIntoTable("admin_custom_role",["role_name"=>$_POST['role_name']]))
					{
						return redirect()->to('/admin99/adminroles')->with('success',"Admin role added successfully");
					}
					else
					{
						return redirect()->to('/admin99/adminroles')->with('error',"Error occurred");
					}
				}
				else
				{
					return redirect()->to('/admin99/adminroles')->with('error',"Admin role already exist");
				}				
			}			
			$dataForView["lendo_Admin_roles"] = User::getAdminRoles();
            return view('admin.adminroles',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function assignroles()
    {		
        $user = Auth::user();
		
		$dataForView = array();
        if(is_null($user->OTP))
        {
			$dataForView["lendo_Admins"] = User::getLendoAdmins();
			$dataForView["lendo_Admin_roles"] = User::getAdminRoles();
            return view('admin.assignroles',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function pageaccess()
    {		
        $user = Auth::user();
		$dataForView = array();
        if(is_null($user->OTP))
        {
			$dataForView["getAdminRoles"] = User::getAdminRoles();
			$dataForView["getAdminPages"] = User::getAdminPages();
			$dataForView["getAdminPageAccess"] = User::getAdminPageAccess();			
            return view('admin.pageaccess',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function cardpriority()
    {
		
        $user = Auth::user();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		$dataForView = array();
		
        if(is_null($user->OTP))
        {
			$dataForView['total_card_applied'] = User::total_card_applied();
			$dataForView['total_card_blue'] = User::total_card_applied_type(1);
			$dataForView['total_card_silver'] = User::total_card_applied_type(2);
			$dataForView['total_card_gold'] = User::total_card_applied_type(3);
			$dataForView['total_card_black'] = User::total_card_applied_type(4);
			
			
			$total_record = $dataForView['total_card_applied'];
			$length = $this->_export_limit;
			$dataForView['_user_export_count'] = $length;
			if($total_record > $length )
			{
				$loop_counter = (int)($total_record/$length) + 1;
				for($index=0; $index < $loop_counter; $index++)
				{
					$start = $end = 0;					
					$start = ($index*$length+1);
					$end = ($index+1)*$length;
					if($index == ($loop_counter-1))
					{
						$end = $total_record;
					}
					$dataForView['export_loop'][$index] = array("start"=>$start,"end"=>$end);
				}
			}
			
			
			if(isset($_GET['debug']) && $_GET['debug'] == 1){echo "<pre>";print_r($dataForView);die;}
			
            return view('admin.cardpriority',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function leaderboard()
    {
        $user = Auth::user();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		$dataForView = array();
		
        if(is_null($user->OTP))
        {
			$currentMonth = date("m");
			$currentYear = date("Y");
			
			$top_referrals = array();
			$top_referrals_list = User::get_top_referrals_in_months($currentMonth, $currentYear);
			
			foreach($top_referrals_list as $top_referrals_row){
				if(FileAttachments::getKYCStatus($top_referrals_row->id) == 1){
					$top_referrals[] = $top_referrals_row;
				}
			}
			
			$pre_rank_array = array();
			foreach($top_referrals as $top_referral){
				$pre_rank_array[$top_referral->id] = $top_referral->this_month;
			}
			$dataForView['pre_rank_array'] = $pre_rank_array;
			$post_rank_array = CommonHelper::set_ranking_order($pre_rank_array);
			
			$dataForView['post_rank_array'] = $post_rank_array;
			$top_referrals_final = array();
			$referral_limit = config("constants.REFERRAL_LEADERBOARD_LIMIT");
			$counter = 1;
			foreach($top_referrals as $top_referral)
			{
				if($counter<=$referral_limit)
				{
					$top_referral->rank = $post_rank_array[$top_referral->id];
					$top_referrals_final[] = $top_referral;
				}
				$counter++;
			}
			$dataForView['top_referrals'] = $top_referrals_final;
            return view('admin.leaderboard',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function salesleaderboard()
    {
        $user = Auth::user();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		$dataForView = array();		
        if(is_null($user->OTP))
        {
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
			
			//echo "<pre>";print_r($top_sales_with_kycverified);	
			
			//echo "<pre>";print_r($pre_rank_array);			
			$dataForView['pre_rank_array'] = $pre_rank_array;
			$post_rank_array = CommonHelper::set_ranking_order($pre_rank_array);
			
			//echo "<pre>";print_r($post_rank_array);
			
			$dataForView['post_rank_array'] = $post_rank_array;
			$top_referrals_final = array();
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
							$top_referrals_final[] = $top_referral;
							$counter++;
						}
					}
				}
			}
			
			/*
			foreach($top_sales_with_kycverified as $top_referral)
			{
				if($counter<=$referral_limit)
				{
					$top_referral->rank = $post_rank_array[$top_referral->parent_id];
					$top_referrals_final[] = $top_referral;
				}
				$counter++;
			}
			*/
			
			//echo "<pre>";print_r($top_referrals_final);die;
			
			$dataForView['top_referrals'] = $top_referrals_final;
            return view('admin.salesleaderboard',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function tokenusers()
    {
        $user = Auth::user();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		$dataForView = array();
		
		$adminAddress = Config('constants.admin_elt_address');
		
		$dataForView['adminAddress'] = $adminAddress;
		
        if(is_null($user->OTP))
        {
			$dataForView['eth_balance'] = CommonHelper::balance_format(CommonHelper::get_eth_balance($adminAddress),6);
					
			$dataForView['elt_balance'] = CommonHelper::balance_format(CommonHelper::get_elt_balance($adminAddress),4);
					
            return view('admin.tokenusers',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function bctransactions()
    {
        $user = Auth::user();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		$dataForView = array();

		$dataForView['export_loop'] = array();
		
        if(is_null($user->OTP))
        {
			$total_record = DB::table('bc_transations')->count();
			
			$length = $this->_export_limit;
			
			if($total_record > $length )
			{
				$loop_counter = (int)($total_record/$length) + 1;
				for($index=0; $index < $loop_counter; $index++)
				{
					$start = $end = 0;					
					$start = ($index*$length+1);
					$end = ($index+1)*$length;
					if($index == ($loop_counter-1))
					{
						$end = $total_record;
					}
					$dataForView['export_loop'][$index] = array("start"=>$start,"end"=>$end);
				}				
			}
            return view('admin.bctransactions',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }

	public function adminlogs()
    {		
        $user = Auth::user();
		$dataForView = array();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}		
        if(is_null($user->OTP))
        {
			$default_loan_apply = 0;
			$default_loan_apply += 500;			
			$default_loan_apply += 1000000;
			$default_loan_apply += 1300000;
			$default_loan_apply += 1300000;
			$dataForView['total_loan_applied'] = User::total_loan_applied();
			$dataForView['total_loan_amount'] = User::total_loan_amount();
			$dataForView['total_loan_amount'] = $dataForView['total_loan_amount'] + $default_loan_apply;
			$dataForView['total_loan_term'] = User::total_loan_term();
			$dataForView['average_loan_amount'] = User::average_loan_amount();
			$dataForView['average_loan_term'] = User::average_loan_term();
			$dataForView['total_community_bonus'] = User::total_community_bonus();
            return view('admin.adminlogs',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function withdrawal()
    {
		$user = Auth::user();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}		
		$dataForView = array();
		if(is_null($user->OTP))
		{
			$currencyList = Withdrawal::withdraw_request_statistics('all');
			$dataForView['currencyList'] = $currencyList;		
			return view('admin.withdrawal',$dataForView);	
		}
		else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
	}
	
	public function adminkyc() 
	{	
		error_reporting(0);

		$user = Auth::user(); 

		$length = 25;

		if(is_null($user->OTP)) 
		{
			$total_kyc_record = '';
			
			if(isset($_GET['page']) && !empty($_GET['page']))

			{

				$page = intval($_GET["page"]);

			}

			else

			{

				$page = 1;

			}

			$calc = $length * $page;

			$start = $calc - $length;

			if(isset($_GET['status_filter']) && $_GET['status_filter'] != -1 && ($_GET['status_filter'] == 0 || $_GET['status_filter'] == 1 || $_GET['status_filter'] == 2))
			{		

				$having_status_count = 0;				
				if(in_array($_GET['status_filter'],array(1,2)))
				{

					$having_status_count=2;

				}

				$TotalSql = "SELECT count(*) as total_kyc

				FROM file_attachments 

				WHERE file_attachments.status = ".$_GET['status_filter']."

				GROUP BY file_attachments.user_id 

				HAVING COUNT(file_attachments.user_id)>$having_status_count";				

				$total_kyc_row = DB::select($TotalSql);				

				$total_kyc_count = count($total_kyc_row);

				$Sql = "SELECT 

				file_attachments.id,

				file_attachments.user_id,

				file_attachments.status,

				users.first_name, 

				users.last_name, 

				users.email,

				COUNT(file_attachments.user_id)

				FROM file_attachments 

				LEFT JOIN users ON users.id = file_attachments.user_id

				WHERE users.role=2 AND file_attachments.status = ".$_GET['status_filter']."

				GROUP BY file_attachments.user_id 

				HAVING COUNT(file_attachments.user_id)>$having_status_count
				
				ORDER BY users.id DESC

				LIMIT $start,$length";

			}

			elseif(isset($_GET['status_filter']) && $_GET['status_filter'] == 3 )
			{		

				$TotalSql = "SELECT count(*) as total_kyc

				FROM file_attachments 

				WHERE file_attachments.status = 0 AND file_attachments.comments IS NULL

				GROUP BY file_attachments.user_id 

				HAVING COUNT(file_attachments.user_id)=3";				

				$total_kyc_row = DB::select($TotalSql);				

				$total_kyc_count = count($total_kyc_row);

				$Sql = "SELECT 

				file_attachments.id,

				file_attachments.user_id,

				file_attachments.status,

				users.first_name, 

				users.last_name, 

				users.email,

				COUNT(file_attachments.user_id)

				FROM file_attachments 

				LEFT JOIN users ON users.id = file_attachments.user_id

				WHERE users.role=2 AND file_attachments.status = 0 AND file_attachments.comments IS NULL

				GROUP BY file_attachments.user_id 

				HAVING COUNT(file_attachments.user_id)=3
				
				ORDER BY users.id DESC

				LIMIT $start,$length";
			}
			else
			{			

				$TotalSql = "SELECT count(*) as total_kyc

				FROM file_attachments GROUP BY file_attachments.user_id";

				$total_kyc_row = DB::select($TotalSql);

				$total_kyc_count = count($total_kyc_row);



				$Sql = "SELECT 

				file_attachments.id,

				file_attachments.user_id,

				file_attachments.status,

				users.first_name, 

				users.last_name, 

				users.email

				FROM file_attachments 

				LEFT JOIN users ON users.id = file_attachments.user_id

				WHERE users.role=2

				GROUP BY file_attachments.user_id
				
				ORDER BY users.id DESC

				LIMIT $start,$length";
			}
			
			$total_pages = ceil($total_kyc_count / $length);		
			$users_list = DB::select($Sql);
			$user_new_list = array(); 
			foreach($users_list as $users_row)
			{
				$user_new_list[$users_row->user_id][] = $users_row;
			}
			$kyc_listing = array(); 
			foreach($user_new_list as $key=>$values)
			{	
				$kyc_array = array ( "user_id"=>$key); 
				foreach($values as $value) 
				{ 
					$kyc_array["first_name"] = $value->first_name; 
					$kyc_array["last_name"] = $value->last_name; 
					$kyc_array["email"] = $value->email;
				}
				$kyc_listing[] = (object)$kyc_array;
			}
		}
		$kyc_listing = (object)$kyc_listing;
		return view
		(

			'admin.testing', 

			[

				'kyc_list' => $kyc_listing, 

				'users_list' => $users_list,

				'total_pages'=> $total_pages,

				'page' => $page,

			]

		); 



	}

	
	public function allkyc()
	{	
	
		echo "<pre>";
		
        $user = Auth::user(); 
		$admin_id = 4;
		for($index=1; $index<=16; $index++)
		{
			$insertData = array();
			$insertData = 
			[
				"admin_id"=>$admin_id,
				"access_level_id"=>$index
			];
			//User::insertIntoTable("admin_access_permission",$insertData);
		}
		
		die('all KYC end here...');
	}
	
	public function whitelistusers()
	{		
        $user = Auth::user();
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		$UserModel = new User();
        if(is_null($user->OTP))
        {
           $whitelistusers = DB::table('whitelist_users')->select('whitelist_users.*')->orderBy('created_at','desc')->get();
            return view('admin.whitelistusers',['whitelistusers' => $whitelistusers]);
        }
	}
	
	public function update_kyc($id, $status, Request $request)
	{
		if($id && $status)
		{
			DB::table('file_attachments')->where('user_id',$id)->update(['status' =>$status]);
			if($status == 1)
			{
				$message = "KYC approved successfully";
			}
			elseif($status == 2)
			{
				$message = "KYC declined successfully";
			}
		}
		return  redirect()->route('admin.testing')->with('success',$message);
	}
	
	
	
	public function update_role_access(Request $request)
	{
		$page_id = $_POST['page_id'];
		$role_id = $_POST['role_id'];
		$action = $_POST['action'];
		$response = array();
		$status = "0";
		if($page_id > 0 && $role_id > 0 )
		{
			if(User::update_page_role($page_id, $role_id, $action))
			{
				$status = "1";
			}
		}
		echo $status;exit;
	}
	public function update_admin_role(Request $request)
	{
		$role_id = $_POST['role_id'];
		$admin_id = $_POST['admin_id'];
		$status = "0";
		$response = array();
		
		if($admin_id > 0 && $role_id > 0 )
		{
			if(User::update_admin_role($admin_id, $role_id))
			{
				$status = "1";
			}
		}
		echo $status;exit;
	}
    public function transferfund($id, Request $request) {
		
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		
        //
        $wallet_options = array("BTC_balance" => "BTC", "ETH_balance" => "ETH", "EUR_balance" => "EUR", "ELT_balance" => "ELT");
        //
        $source_wallet_options = array("BTC_balance" => "BTC", "ETH_balance" => "ETH", "EUR_balance" => "EUR", "ELT_balance" => "ELT");

        //
        if($request != null &&  $request->get('amount') > 0)
        {
            // For multiple phases calculations - Select actiave phases data
            $activePhaseData = Phases::where('status','1')->first();

            if(!isset($activePhaseData)) {
                $user= Auth::user();
                $record = [
                    'message'   => 'Username '.$user->email.' Phase not activated ',
                    'level'     => 'ERROR',
                    'context'   => 'Transfer Fund'
                ];
                LoggerHelper::writeDB($record);
                return redirect()
                            ->to('/home')
                                ->with('error', 'The ELT tokens are not available, please contact admin for further detail'); 
            }

            if(isset($activePhaseData) && !empty($activePhaseData->id))
            {
                $phaseId = $activePhaseData->id;
            }
            else
            {
                $phaseId = NULL;
            }

            //Creating trsaction for adding ELT into User account
            $wallet_name = $wallet_options[$request->get('wallet_type')];

            //Create transaction to user account
			Transactions::createTransactionWithReference($id, $wallet_name, $request->get('amount'), 'Transferred by admin', 1, uniqid(), $phaseId, NULL, NULL, NULL, NULL, 'bonus');
			

            //Getting the user 
            $user = User::find($id);

            //Adding/substract the same amount on user 1's balance 
            if($request->get('amount')>0) {
                $user->addValue($request->get('wallet_type'),$request->get('amount'));
            } /*else {
                $user->subtractValue($request->get('wallet_type'),abs($request->get('amount')));
            }*/
            $user->save();
			
			$record = [
				'message'   => 'Transfer Fund to: '.$user->email.' via admin',
				'level'     => 'ERROR',
				'context'   => 'Transfer Fund',
				'userId'	=> $id
			];
			LoggerHelper::writeDB($record);
				

            //Check if process referrel bonus in checked
            if($request->get('processReferral')){
				
                $this->processReferralBonus($userId, $postarray['source_wallet_type'], $postarray['source_amount'],NULL, $phaseId);
          
                return redirect()->to('/admin99/users')->with('success', trans("message.amount_update"));
            }
            return redirect()->to('/admin99/users')->with('success', trans("message.amount_update"));
        }
        return view('admin.transferfund', ['users_id' => $id, 'wallet_options' => $wallet_options, 'source_wallet_options' => $source_wallet_options]);
    }


    /**
     *
     */
    public function showDetails($id, Request $request) {
		
		$dataForView = array();				
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}		
		if(!CommonHelper::isUserAllowedForSubAdmin(Auth::user()->custom_role, $id)){
			return redirect()->route('admin.dashboard');
		}
		
        $wallet_options = array("BTC_balance" => "BTC", "ETH_balance" => "ETH", "EUR_balance" => "EUR", "ELT_balance" => "ELT");		
        $source_wallet_options = array("BTC_balance" => "BTC", "ETH_balance" => "ETH", "EUR_balance" => "EUR", "ELT_balance" => "ELT");		
        $userInfo = User::find($id);
		
		/* IF user is invisible then send admin to dashboard except for superadmin */
		if(!CommonHelper::isSuperAdmin() && $userInfo->make_user_invisible == 1){
			return redirect()->route('admin.dashboard');
		}
		
        if($request->get('uesr_detail_update')){
            $validator = Validator::make($request->all(), [
                'first_name'                => 'alpha_spaces',
                'last_name'                 => 'alpha_spaces',
                'address1'                  => 'sometimes|nullable|string',
                'address2'                  => 'sometimes|nullable|string',
                'postal_code' 				=> 'sometimes|nullable|valid_postal_code',
                'city'                      => 'sometimes|nullable|alpha',
                'country_code'              => 'sometimes|nullable|alpha|min:2|max:2',
                'mobile_number'             => 'sometimes|nullable|max:15',
                'language'                  => 'required|alpha|min:2|max:2',
            ],[
                'first_name.required'   => trans('auth.fNameRequired'),
                'last_name.required'    => trans('auth.lNameRequired'),
                'postal_code.valid_postal_code' => trans('auth.alphabets_with_space_dash_allowed'),
            ]);
			
            if ($validator->fails()){
                return redirect()
                        ->to(Config('constants.admin_url').'/users/detail/'.$id)
                            ->withErrors($validator)
                                ->withInput($request->all());
            } 
			else{
				$telegram_id = $request->get('telegram_id');					
				if(!empty($telegram_id) && isset($telegram_id))
				{
					$telegramUser = User::select('id')->where('telegram_id', '=', $telegram_id)->where('role', '=', 2)->where('id', '!=', $id)->first();
					if(isset($telegramUser->id))
					{
						return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error', trans("message.telegram_id_already_exist"));
					}
				}
				else
				{
					$telegram_id = NULL;
				}
				
                $user                  = User::find($id);
                $user->first_name      = $request->get('first_name');
                $user->last_name       = $request->get('last_name');
				$user->display_name    = $request->get('display_name');
                $user->address1        = $request->get('address1');
                $user->address2        = $request->get('address2');
                $user->postal_code     = $request->get('postal_code');
                $user->city            = $request->get('city');
                $user->country_code    = $request->get('country_code');
                $user->mobile_number   = $request->get('mobile_number');
                $user->language        = $request->get('language');
				$user->telegram_id     = $telegram_id;
                $user->save();

                $record = 
				[
                    'message'   => 'Update general profile',
                    'level'     => 'INFO',
                    'context'   => 'Update general profile',
                    'userId'   => $id
                ];
                LoggerHelper::writeDB($record);
                return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('success', trans("message.preference_update"));
            }
        }
		else if($request->get('new_password_save')) 
		{
            $validator = Validator::make($request->all(), [
                'new_password'              => 'required|min:6|max:20|confirmed',
                'new_password_confirmation' => 'required|same:new_password'
            ], [
                'new_password.required' => trans('auth.passwordRequired'),
                'new_password.min'      => trans('auth.PasswordMin'),
                'new_password.max'      => trans('auth.PasswordMax')
            ]);
			
            if($validator->fails()) {
                return redirect()
                        ->to(Config('constants.admin_url').'/users/detail/'.$id)
                            ->withErrors($validator)
                                ->withInput($request->all());
            }else {
                $user = User::find($id);
                $user->password = bcrypt($_POST['new_password']);
                $user->save();
                //mail data
                $emailData = array(
                    'ChangeEntity' => 'Password'
                );
                // Send activation email notification
                self::SendChangeNotification($user, $emailData);

                  $record = [
                    'message'   => 'Update Password',
                    'level'     => 'INFO',
                    'context'   => 'Update Password',
                    'userId'   => $id
                ];
                LoggerHelper::writeDB($record);
                return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('success', trans("message.preference_update"));
            }
        }
		else if($request->get('new_email_change')) 
		{
			$user = User::find($id);
			$validator = Validator::make($request->all(),[
                'new_email'             => 'unique:users,email,'.$id.'|required|email|max:255',
            ],[
                'new_email.required'        => trans('auth.emailRequired'),
                'new_email.email'           => trans('auth.emailInvalid'),
            ]);
			
            if($validator->fails()){
                return redirect()
                        ->to(Config('constants.admin_url').'/users/detail/'.$id)
                            ->withErrors($validator)
                                ->withInput($request->all())->with('tabname','general');
            }
			elseif($request->get('new_email') == $user->email){
				return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error',"Please enter other email address")->with('tabname', 'general');
			}
			else 
			{
				$new_email = $request->get('new_email');
				$confirm_email_checkbox = $request->get('confirm_email_checkbox');
				if($confirm_email_checkbox == 0){
					$unique_confirmation_key = uniqid(bin2hex(openssl_random_pseudo_bytes(10)), true);
					$changeRequests = ChangeRequests::create([
						'old_value' => $user->email,
						'new_value' => $new_email,
						'unique_confirmation_key' => $unique_confirmation_key,
						'type' => 3,
						'user_id' => $user->id,
						'is_delete' => 0
					]);
					if($changeRequests->id){
						$emailData = array(
							'old_value' => $user->email,
							'new_value' => $new_email,
							'first_name'=>$user->first_name,
							'last_name'=>$user->last_name,
							'unique_confirmation_key' => $unique_confirmation_key,
							'id' => $changeRequests->id,
							'type' => 3
						);
						self::sendChangeEmailRequest($user, $emailData);
						return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('success',"Email change request has been sent successfully")->with('tabname', 'general');
					}
				}
				else
				{
					$find_all_pending_request = ChangeRequests::where([['type', 3],['is_delete', '0'],['user_id',$id]])->get();
		
					if(!empty($find_all_pending_request))
					{
						foreach($find_all_pending_request as $single_request)
						{
							ChangeRequests::where('id', $single_request->id)->update(['is_delete' => '1']);
						}
					}
					$user->email = $new_email;
					$user->save();
				}
				
                //mail data
                $emailData = array(
                    'ChangeEntity' => 'Email Address'
                );
                // Send activation email notification
                self::SendChangeNotification($user, $emailData);

                  $record = [
                    'message'   => 'Email Address',
                    'level'     => 'INFO',
                    'context'   => 'Email Address',
                    'userId'   => $id
                ];
                LoggerHelper::writeDB($record);
                return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('success', "Email address changed successfully");
            }
        }
		else if($request->get('update_elt_address'))
		{
			$wallet_address = $request->get('ELT_wallet_address');
			$current_address = $userInfo->ELT_wallet_address;
			if(isset($wallet_address))
			{
				$userInfo->ELT_wallet_address = $wallet_address;
				if($userInfo->save())
				{
					$dataToUpdate = ChangeRequests::where('user_id',$id)->where('is_delete',0)->whereIn('type',[11])->first();
					if(isset($dataToUpdate->id)){
						ChangeRequests::where('id', $dataToUpdate->id)->update(['is_delete' => '1']);
					}
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('success','Wallet address updated successfully')->with('tabname','wallet');
				}
				else
				{
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error', 'Something went wrong!');
				}
			}
			else
			{
				return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error','Please enter valid wallet address');
			}
		}
		else if($request->get('update_btc_address'))
		{
			$wallet_address = $request->get('BTC_wallet_address');
			$current_address = $userInfo->BTC_wallet_address;
			if(isset($wallet_address))
			{
				$userInfo->BTC_wallet_address = $wallet_address;
				if($userInfo->save())
				{
					$dataToUpdate = ChangeRequests::where('user_id',$id)->where('is_delete',0)->whereIn('type',[1])->first();
					if($dataToUpdate->id){
						ChangeRequests::where('id', $dataToUpdate->id)->update(['is_delete' => '1']);
					}
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('success','Wallet address updated successfully')->with('tabname','wallet');
				}
				else
				{
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error', 'Something went wrong!');
				}
			}
			else
			{
				return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error','Please enter valid wallet address');
			}
		}
		else if($request->get('update_eth_address'))
		{
			$wallet_address = $request->get('ETH_wallet_address');
			$current_address = $userInfo->ETH_wallet_address;
			if(isset($wallet_address))
			{
				$userInfo->ETH_wallet_address = $wallet_address;
				if($userInfo->save())
				{
					$dataToUpdate = ChangeRequests::where('user_id',$id)->where('is_delete',0)->whereIn('type',[2])->first();
					if($dataToUpdate->id){
						ChangeRequests::where('id', $dataToUpdate->id)->update(['is_delete' => '1']);
					}
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('success','Wallet address updated successfully')->with('tabname','wallet');
				}
				else
				{
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error', 'Something went wrong!');
				}
			}
			else
			{
				return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error','Please enter valid wallet address');
			}
		}
		else if($request->get('update_ltc_address'))
		{
			$wallet_address = $request->get('LTC_wallet_address');
			$current_address = $userInfo->LTC_wallet_address;
			if(isset($wallet_address))
			{
				$userInfo->LTC_wallet_address = $wallet_address;
				if($userInfo->save())
				{
					$dataToUpdate = ChangeRequests::where('user_id',$id)->where('is_delete',0)->whereIn('type',[7])->first();
					if($dataToUpdate->id){
						ChangeRequests::where('id', $dataToUpdate->id)->update(['is_delete' => '1']);
					}
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('success','Wallet address updated successfully')->with('tabname','wallet');
				}
				else
				{
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error', 'Something went wrong!');
				}
			}
			else
			{
				return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error','Please enter valid wallet address');
			}
		}
		else if($request->get('update_bch_address'))
		{
			$wallet_address = $request->get('BCH_wallet_address');
			$current_address = $userInfo->BCH_wallet_address;
			if(isset($wallet_address))
			{
				$userInfo->BCH_wallet_address = $wallet_address;
				if($userInfo->save())
				{
					$dataToUpdate = ChangeRequests::where('user_id',$id)->where('is_delete',0)->whereIn('type',[8])->first();
					if($dataToUpdate->id){
						ChangeRequests::where('id', $dataToUpdate->id)->update(['is_delete' => '1']);
					}
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('success','Wallet address updated successfully')->with('tabname','wallet');
				}
				else
				{
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error', 'Something went wrong!');
				}
			}
			else
			{
				return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error','Please enter valid wallet address');
			}
		}
		else if($request->get('update_xrp_address'))
		{
			$wallet_address = $request->get('XRP_wallet_address');
			$current_address = $userInfo->XRP_wallet_address;
			if(isset($wallet_address))
			{
				$userInfo->XRP_wallet_address = $wallet_address;
				if($userInfo->save())
				{
					$dataToUpdate = ChangeRequests::where('user_id',$id)->where('is_delete',0)->whereIn('type',[9])->first();
					if($dataToUpdate->id){
						ChangeRequests::where('id', $dataToUpdate->id)->update(['is_delete' => '1']);
					}
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('success','Wallet address updated successfully')->with('tabname','wallet');
				}
				else
				{
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error', 'Something went wrong!');
				}
			}
			else
			{
				return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error','Please enter valid wallet address');
			}
		}
		else if($request->get('update_dash_address'))
		{
			$wallet_address = $request->get('DASH_wallet_address');
			$current_address = $userInfo->DASH_wallet_address;
			if(isset($wallet_address))
			{
				$userInfo->DASH_wallet_address = $wallet_address;
				if($userInfo->save())
				{
					$dataToUpdate = ChangeRequests::where('user_id',$id)->where('is_delete',0)->whereIn('type',[10])->first();
					if($dataToUpdate->id){
						ChangeRequests::where('id', $dataToUpdate->id)->update(['is_delete' => '1']);
					}
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('success','Wallet address updated successfully')->with('tabname','wallet');
				}
				else
				{
					return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error', 'Something went wrong!');
				}
			}
			else
			{
				return  redirect()->to(Config('constants.admin_url').'/users/detail/'.$id)->with('error','Please enter valid wallet address');
			}
		}
		
		$ChangeRequests = ChangeRequests::where('user_id',$id)->where('is_delete',0)->whereIn('type',[1,2,3,7,8,9,10,11])->get();		
		$changeWalletAddressRequest= array();
		foreach($ChangeRequests as $ChangeRequest){
			$changeWalletAddressRequest[$ChangeRequest->type] = 1;
		}		
		$dataForView['changeWalletAddressRequest']= $changeWalletAddressRequest;
		
        $parent_level1_users =[];
        $parent_level2_users =[];
        $parent_level3_users =[];
		$parent_level4_users =[];
		$parent_level5_users =[];
		
        $Countries = Country::all();
		$all_transactions_list = Transactions::where( [ ['user_id', $id] ] )->orderBy('created_at','DESC')->get();
		$user_logs = Logs::where( [ ['user_id', $id] ] )->orderBy('created_at','DESC')->get();        
		$referred_users = User::where( [ ['referrer_user_id', $id] ] )->orderBy('created_at','DESC')->get();
		
        if(isset($userInfo->referrer_user_id)) 
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
		
		$kyc_list = DB::table('file_attachments')->select('file_attachments.*')->where('file_attachments.user_id',$id)->get();
		
		$kyc_record = array();
		foreach($kyc_list as $kyc_row){
			$kyc_row->filepath = env("AWS_KYC_PATH").$kyc_row->filename;
			$kyc_record[$kyc_row->type] = $kyc_row;
		}
		
		$invoice_list = DB::table('elt_invoices')->select('elt_invoices.*')->where('elt_invoices.user_id',$id)->orderBy('id', 'DESC')->get();
		
		$dataForView['user_info'] = $userInfo;
		$dataForView['user_logs'] = $user_logs;
		$dataForView['kyc_record'] = $kyc_record;
		$dataForView['Countries'] = $Countries;
		$dataForView['invoice_list'] = $invoice_list;
		$dataForView['wallet_options'] = $wallet_options;
		$dataForView['referred_users'] = $referred_users;		
		$dataForView['parent_level1_users'] = $parent_level1_users;
		$dataForView['parent_level2_users'] = $parent_level2_users;
		$dataForView['parent_level3_users'] = $parent_level3_users;
		$dataForView['parent_level4_users'] = $parent_level4_users;
		$dataForView['parent_level5_users'] = $parent_level5_users;
		$dataForView['all_transactions_list'] = $all_transactions_list;
		$dataForView['source_wallet_options'] = $source_wallet_options;
		
		$levelwise_data = Transactions::get_levelwise_user_elt_euro_worth($id);
		$dataForView['levelwise_data'] = $levelwise_data;
		
		$current_level_array = Transactions::get_current_user_level($id);
		$dataForView['current_level'] = $current_level_array['current_level'];
		$level_detail = Transactions::get_user_elt_worth_in_euro($id);
		$dataForView['euro_worth'] = $level_detail[0]->euro_worth_total;		
		$dataForView['euro_worth_for_next_level'] = $current_level_array['euro_worth_for_next_level'];
		
		$all_withdraw_list = Withdrawal::where( [ ['user_id', $id] ] )->orderBy('created_at','DESC')->get();
		$dataForView['all_withdraw_list'] = $all_withdraw_list;		
		
		$all_languages = User::get_languages();
		$dataForView['all_languages'] = $all_languages;		
		
		$configuration = Configurations::where('valid_to', '=', "9999-12-31")->orderBy('name', 'asc')->getQuery()->get();		
		$configuration_data = array();
		foreach($configuration as $config)
		{
			$configuration_data[$config->name] = $config;
		}
		$dataForView['configuration_data'] = $configuration_data;
		
		$dataForView['bonus_level'] = $userInfo->bonus_level;

		$dataForView['downline_sales_level1'] = Transactions::getUserDownlineSalesData($id, 'level', 1);
		
		$dataForView['downline_sales_level2'] = Transactions::getUserDownlineSalesData($id, 'level', 2);		
		
		$dataForView['downline_sales_level3'] = Transactions::getUserDownlineSalesData($id, 'level', 3);		
		
		$dataForView['downline_sales_level4'] = Transactions::getUserDownlineSalesData($id, 'level', 4);
		
		$dataForView['downline_sales_level5'] = Transactions::getUserDownlineSalesData($id, 'level', 5);		
		
		$dataForView['downline_sales_level_all_five'] = Transactions::getUserDownlineSalesData($id, 'five');
		
		$dataForView['downline_sales_level_all'] = Transactions::getUserDownlineSalesData($id, 'all');	
		
		//echo "<pre>";print_r($dataForView);die;
        return view('admin.userDetail',$dataForView);
    }
	
	/**
     *
     */
    public function showInvoiceDetails($id, Request $request) {
		
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
	
	
	
	public function finance()
    {
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		
        $user = Auth::user();
		$dataForView = array();
        if(is_null($user->OTP))
        {
			$dataForView['selectedCurrency'] = "elt";
			$dataForView['financialStats'] = User::get_users_financial_stats();
			$dataForView['pendingELT'] = User::get_pending_elt_to_distribute();
			//echo "<pre>";print_r($dataForView);die;
            return view('admin.finance',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function adminreferrals()
    {
		$dataForView = array();
        $user = Auth::user();
        if(is_null($user->OTP))
        {
            return view('admin.adminreferrals',$dataForView);			
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function demographics()
    {		
        $user = Auth::user();
		$dataForView = array();
        if(is_null($user->OTP))
        {
			$Countries = Country::all();
			$dataForView['Countries'] = $Countries;
            return view('admin.demographics',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function withdrawsetting(Request $request)
    {		
        $user = Auth::user();
		$dataForView = array();		
		$conversion_rate_data = array();
		$conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
		if (!$conversion_rates->isEmpty()) {
			foreach ($conversion_rates as $key => $rates_minimume_values) {
				$conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
				$conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
			}
		}
		$dataForView["conversion_rate_data"] = $conversion_rate_data;
		$currencyList["ELT"] = array("code"=>"ELT","name"=>"ELT");
		$currencyList["BTC"] = array("code"=>"BTC","name"=>"Bitcoin");
		$currencyList["EUR"] = array("code"=>"EUR","name"=>"Euro");
		$currencyList["ETH"] = array("code"=>"ETH","name"=>"Etherium");
		$currencyList["BCH"] = array("code"=>"BCH","name"=>"Bitcoin Cash");
		$currencyList["LTC"] = array("code"=>"LTC","name"=>"Litcoin Cash");
		$currencyList["XRP"] = array("code"=>"XRP","name"=>"Ripple");
		$currencyList["DASH"] = array("code"=>"DASH","name"=>"Dash");
		$dataForView["currencyList"] = $currencyList;
		$configuration = Configurations::where('valid_to', '=', "9999-12-31")->orderBy('name', 'asc')->getQuery()->get();
		if($request->isMethod('post')) 
		{
			//echo "<pre>";print_r($_POST);die;
			foreach($configuration as $config)
			{
				if(isset($_POST[$config->name]))
				{
					if($config->defined_value != $_POST[$config->name])
					{
						$configurationUpdate = Configurations::find($config->id);
						$configurationUpdate->valid_to = date('Y-m-d h:i:s');
						$configurationUpdate->save();
						$configurationCreate = Configurations::create([
							'name'          => $config->name,
							'valid_from'    => date('Y-m-d h:i:s'),
							'valid_to'      => '9999-12-31',
							'defined_value' => $_POST[$config->name],
							'defined_unit'  => $config->defined_unit,
							'updated_by'    => '1'
						]);
					}
				}
			}
			return redirect()->route('admin.withdrawsetting')->with('success', trans("message.withdrawal_updated") );
		}
		//echo "<pre>";print_r($dataForView);die;
        return view('admin.withdraw_setting',$dataForView);
    }
	
	public function salesrevenue()
    {
        $user = Auth::user();
		$dataForView = array();
        if(is_null($user->OTP))
        {
            return view('admin.salesrevenue',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function userstats()
    {
        $user = Auth::user();
		$dataForView = array();
        if(is_null($user->OTP))
        {			
			$dataForView = User::getadminuserstats();
			//echo "<pre>";print_r($dataForView);die;
            return view('admin.userstats',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	
	public function update_kyc_stats(Request $request)
	{
		parse_str($_POST['data'], $postarray);
		$response = array();
		$status = false;
		$userModel = new User();
		if(isset($postarray['userId'])) 
		{
			$id = $postarray['userId'];
			$dlf_status = $postarray['dlf_user_id_'.$id];
			$poa_status = $postarray['poa_user_id_'.$id];
			$record = 
			[
                    'message'   => 'Update kyc',
                    'level'     => 'INFO',
                    'context'   => 'Update kyc',
                    'userId'    =>  $id
            ];
            LoggerHelper::writeDB($record);
			
			$kyc_exist = DB::table('file_attachments')->where('user_id',$id)->get();
			
			if(!$kyc_exist){
				$response['status'] = false;
				echo json_encode($response,false);exit;
			}
			
			DB::table('file_attachments')->where('user_id',$id)->where('type','DLF')->update(['status' =>$dlf_status,'comments'=>$postarray['dlf_comment']]);
			
			DB::table('file_attachments')->where('user_id',$id)->where('type','DLB')->update(['status' =>$dlf_status,'comments'=>$postarray['dlf_comment']]);
			
			DB::table('file_attachments')->where('user_id',$id)->where('type','POA')->update(['status' =>$poa_status,'comments'=>$postarray['poa_comment']]);
			
			$response['status'] = true;
			
			$kyc_status = FileAttachments::fetch_kyc_status($id);
			
			$user_model = User::find( $id );
			
			if($kyc_status ==  1)
			{
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Approved KYC: '.$user_model->email,
					'level'     => 'INFO',
					'context'   => 'KYC Approval'
				];
				LoggerHelper::writeDB($record);
				
				// transaction type 2 or 3 (for laon and card bonus)
				$pending_transactions = DB::table('transactions')
                    ->whereIn('type', array('2','3','7'))
				    ->where('status', '2')
					->where('user_id', $id)
					->where('ledger', 'ELT')
					->get();
				
				if(!empty($pending_transactions))
				{					
					$add_amount = 0;
					foreach($pending_transactions as $transaction)
					{
						$update_array = array('status'=>1,'show_to_user'=>1);	
						DB::table('transactions')->where('id',$transaction->id)->update($update_array);
						$add_amount += $transaction->value;
					}
					$user_model->ELT_balance += $add_amount;
					$user_model->save();
				}
				
				$user_email = $user_model->email;
				
				/* distribute to parent if parent user KYC is approved */
				$parent_user_id = $user_model->referrer_user_id; 
				$parent_user = User::find( $parent_user_id );
				$parent_kyc_status = FileAttachments::fetch_kyc_status($parent_user_id);
				if($parent_kyc_status ==  1)
				{
					// transaction type 4 (for refferal bonus)
					$pending_referral_transaction = DB::table('transactions')
						->where('type','4')
						->where('status', '2')
						->where('ledger', 'ELT')
						->where('user_id', $parent_user_id)
						->where('description', 'like', '%' . $user_email . '%')
						->first();
					if(!empty($pending_referral_transaction))
					{						
						$ref_update_array = array('status'=>1,'show_to_user'=>1);	
						DB::table('transactions')->where('id',$pending_referral_transaction->id)->update($ref_update_array);						
						$amount = $pending_referral_transaction->value;
						$parent_user->ELT_balance += $amount;
						$parent_user->save();	
					}
				}
				/* distribute to parent if parent user KYC is approved */
				
				
				/* distribute to childrens if current user KYC is approved and child user KYC is approved */
				// get current user childrens list	
				$reffered_users = DB::table('users')->where('referrer_user_id', $id)->get();

                if(!empty($reffered_users))
				{
					foreach($reffered_users as $reffered_user)
					{						
						// check this user KYC status
						$child_kyc_status = FileAttachments::fetch_kyc_status($reffered_user->id);
						if($child_kyc_status == 1)
						{							
							// transaction type 4 (for refferal bonus)
							$pending_referral_transaction = DB::table('transactions')
							->where('type','4')
							->where('status', '2')
							->where('user_id', $id)
							->where('ledger', 'ELT')
							->where('description', 'like', '%' . $reffered_user->email . '%')
							->first();
							if(!empty($pending_referral_transaction))
							{
								$reff_update_array = array('status'=>1,'show_to_user'=>1);	
								DB::table('transactions')->where('id',$pending_referral_transaction->id)->update($reff_update_array);						
								$amount = $pending_referral_transaction->value;
								$user_model->ELT_balance += $amount;
								$user_model->save();	
							}
						}
					}					
				}
				/* distribute to childrens if current user KYC is approved and child user KYC is approved */
			}
		}
		echo json_encode($response,true);exit;
	}
	
	public function update_bank_info(Request $request)
	{
		parse_str($_POST['data'], $postarray);
		
		$response = array();
		
		$updateData = array();
		
		$login_user_id = $postarray['login_user_id'];
		
		if(isset($login_user_id) && $login_user_id > 0)
		{
			if(isset($postarray['new_IBAN_number']) && !empty($postarray['new_IBAN_number'])){
				$updateData['IBAN_number'] = $postarray['new_IBAN_number'];
			}
			if(isset($postarray['new_Swift_code']) && !empty($postarray['new_Swift_code'])){
				$updateData['Swift_code'] = $postarray['new_Swift_code'];
			}
			if(isset($postarray['new_Beneficiary_name']) && !empty($postarray['new_Beneficiary_name'])){
				$updateData['Beneficiary_name'] = $postarray['new_Beneficiary_name'];
			}
			if(isset($postarray['new_Bank_name']) && !empty($postarray['new_Bank_name'])){
				$updateData['Bank_name'] = $postarray['new_Bank_name'];
			}
			if(isset($postarray['new_Bank_address']) && !empty($postarray['new_Bank_address'])){
				$updateData['Bank_address'] = $postarray['new_Bank_address'];
			}
			if(isset($postarray['new_Bank_street_name']) && !empty($postarray['new_Bank_street_name'])){
				$updateData['Bank_street_name'] = $postarray['new_Bank_street_name'];
			}
			if(isset($postarray['new_Bank_city_name']) && !empty($postarray['new_Bank_city_name'])){
				$updateData['Bank_city_name'] = $postarray['new_Bank_city_name'];
			}
			if(isset($postarray['new_Bank_postal_code']) && !empty($postarray['new_Bank_postal_code'])){
				$updateData['Bank_postal_code'] = $postarray['new_Bank_postal_code'];
			}
			if(isset($postarray['new_Bank_country']) && !empty($postarray['new_Bank_country'])){
				$updateData['Bank_country'] = $postarray['new_Bank_country'];
			}			
			$result = User::where('id', $login_user_id)->update($updateData);
			
			$userInfo = User::find($login_user_id);
			
			$response['userInfo'] = $userInfo;
			
			$response['msg'] = "success";
		}
		else
		{
			$response['msg'] = "Unauthorized access";
		}
		echo json_encode($response,true);exit;		
	}
	
	


	public function filterUserStats()
	{
		$response = array();
		if(isset($_POST['start_date']) && isset($_POST['end_date']))
		{
			$response = $this->getUserStats($_POST['start_date'],$_POST['end_date']);
		}
		else
		{
			$response = $this->getUserStats('','');
		}
		echo json_encode($response);exit;
	}
	
	public function ajaxtokenusers()
    {
        $user = Auth::user();
		$search_value = '';
		$total_count = DB::table('users')->where('role',2)->count();
		$filtered_count = 0;
		$output = array("draw" => '',"recordsTotal" => 0,"recordsFiltered" =>0,"data" => []);
        if(is_null($user->OTP))
        {
			$column_order = array
			(
				"users.created_at",
				"users.user_name",
				"users.email",
				"users.ELT_balance"
			);
			
			
			$users_list = User::get_datatables_elt_token($column_order,$_POST);			
			$users_count = User::get_datatables_elt_token($column_order,$_POST,1);
			
			if(!empty($_POST['search_text']) || !empty($_POST['start_date']))
			{
				$filtered_count = $users_count;
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
			
			$data = array();
			
		
			foreach($users_list as $users_row)
			{
				$row = array();
				
				$row[] = $i;
				
				$row[] = $users_row->user_name;
				
				$row[] = "<a href='".route('admin.usersdetail',['id'=> $users_row->id ])."'>".$users_row->email."</a>";	
				
				$row[] = $users_row->ELT_balance;
				
				$row[] = $users_row->ETH_balance;
				
				$row[] = $users_row->Custom_ETH_Address;
				
				$buttonHtml = '<button type="button" id="token_'.$users_row->id.'" class="btn btn-info" title="Send Token" onclick="send_token_popup('."'".$users_row->id."'".', '."'".$users_row->Custom_ETH_Address."'".', '."'".$users_row->ELT_balance."'".')">Send Token</button> &nbsp;';
				
				$buttonHtml .= ' <button type="button" id="balance_'.$users_row->id.'" class="btn btn-info" title="Get Token" onclick="get_bc_balance('."'".$users_row->id."'".')">Get Balance</button>';
				
				$row[] = $buttonHtml;
				
				
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
	
	
	
	
	public function ajax_bc_balance()
    {
		$response = array();
		
		$response['msg'] = "";
		
		$response['status'] = "0";
		
		$response['ELT_balance'] = 0;
		
		$response['ETH_balance'] = 0;
		
		if(isset($_POST['data']) && $_POST['data'] > 0)
		{
			$userId = $_POST['data'];
			
			$userInfo = User::find($userId);
			
			if(!is_null($userInfo->Custom_ETH_Address))
			{			
				$response['ELT_balance'] = CommonHelper::balance_format(CommonHelper::get_elt_balance($userInfo->Custom_ETH_Address),6);
					
				$response['ETH_balance'] = CommonHelper::balance_format(CommonHelper::get_eth_balance($userInfo->Custom_ETH_Address),6);
				
				$response['status'] = "1";
			}
			else
			{
				$response['msg'] = "ETH address not found";
			}
		}
		
		echo json_encode($response);exit;
	}
	
	public function ajax_logs_details()
    {
		$response = array();
		
		$response['msg'] = "";
		
		$response['status'] = "0";
		
		$response['logInfo'] = '';
		
		if(isset($_REQUEST['log_id']) && $_REQUEST['log_id'] > 0)
		{
			$log_id = $_REQUEST['log_id'];
			
			$logInfo = Logs::find($log_id);
			
			if(isset($logInfo->extra))
			{
				$response['logInfo'] = $logInfo->extra;
				$response['status'] = "1";
			}			
		}		
		echo json_encode($response);exit;
	}
	
	public function sendtokentousers()
	{		
		parse_str($_POST['data'], $postarray);

		$postarray['date_time'] = $postarray['date_time'].":00";
		
		$adminUser = Auth::user();
		 
		$output = array();
		
		$output["msg"] = '';
		
		$output["status"] = "0";
		
		$adminAddress = Config('constants.admin_elt_address');
		
		if(isset($postarray['receiverId']) && $postarray['receiverId'] > 0)
		{
			$receiverId = $postarray['receiverId'];
			
			$time_stamp = strtotime($postarray['date_time']);
			
			$elt_amount = floatval($postarray['elt_amount']);
			
			$receiverUser = User::find($receiverId);
			
			$ELT_balance = $receiverUser->ELT_balance;
			
			//$token_send = $receiverUser->token_send;
			
			$receiverAddress = $receiverUser->Custom_ETH_Address;
			
			if(empty($receiverAddress))
			{
				$output["msg"] = "Destination not found for $receiverUser->email";
			}
			elseif( $time_stamp < time() )
			{
				$output["msg"] = "Please select future date time";
			}
			elseif($elt_amount >  $ELT_balance )
			{
				$output["msg"] = "Please enter amount less than $ELT_balance ELT";
			}
			elseif($ELT_balance > 0 && $elt_amount > 0 && !empty($receiverAddress) && !empty($adminAddress))
			{
				$transfer_response = CommonHelper::transfer_elt_by_admin($adminAddress,$receiverAddress,$elt_amount,$time_stamp);
								
				if(isset($transfer_response['status']) && $transfer_response['status'] == 1)
				{
					$elt_amount = abs($elt_amount);				
					
					$receiverUser->subtractValue('ELT_balance',$elt_amount);
					
					$receiverUser->save();
					
					$activePhaseData = Phases::where('status','1')->first();

					if(isset($activePhaseData) && !empty($activePhaseData->id))
					{
						$phaseId = $activePhaseData->id;
					}
					else
					{
						$phaseId = NULL;
					}
			
					Transactions::createTransaction($receiverId,'ELT',$elt_amount , 'ELT amount:'.$elt_amount." sent by admin to blockchain on address:".$receiverAddress, 1, uniqid(), $phaseId);
					
					$record = [
						'message'   => 'Transfer ELT to user to: '.$receiverUser->email.' ',
						'level'     => 'ERROR',
						'context'   => 'Transfer ELT on address:'.$receiverAddress,
						'userId'	=> $receiverId
					];
					LoggerHelper::writeDB($record);
			
			
					$fees = isset($transfer_response['fee'])?$transfer_response['fee']:0;
					
					$fees = $fees / 1000000000000000000;								
					
					$insertData = 
					[
						"txid"=>$transfer_response['txid'],
						"blockNumber"=>0,
						"fees"=>$fees,
						"from_address"=>$adminAddress,
						"to_address"=>$receiverAddress,
						"amount"=>$elt_amount,
						"confirmations"=>0,
						"type"=>'ELT',
						"time_stamp"=>date("Y-m-d H:i:s")
					];
					User::insertIntoTable("bc_transations",$insertData);
					
					$logRecord = 
					[
						'message'   => "Admin#$adminUser->email send:ELT, amount:$elt_amount from_address:$adminAddress, to_address:$receiverAddress",
						'level'     => 'INFO',
						'context'   => 'admin send ELT to user:$receiverUser->email'
					];
					LoggerHelper::writeDB($logRecord);
					
					/*
					if($ELT_balance == $elt_amount)
					{
						DB::table('users')->where('id',$receiverId)->update(['token_send'=>1]);
					}
					*/
					
					$output["msg"] = "$elt_amount ELT token sent successfully";
					
					$output["status"] = "1";
				}
				else
				{
					$output["msg"] = isset($transfer_response['msg'])?$transfer_response['msg']:'Something went wrong on blockchain';
				}
			}
			else
			{
				$output["msg"] = "Something went wrong..";
			}
		}
		else
		{
			$output["msg"] = "Invalid request";
		}		
		echo json_encode($output);exit;
	}
	
	public function exportusers()
	{
		error_reporting(0);
		$data = array();
		$user = Auth::user();
		if(is_null($user->OTP))
		{
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
			
			if(isset($_POST['fstart_date']))
			{
				$_POST['start_date'] = $_POST['fstart_date'];
			}			
			if(isset($_POST['fend_date']))
			{
				$_POST['end_date'] = $_POST['fend_date'];
			}			
			if(isset($_POST['fsearch_text']))
			{
				$_POST['search_text'] = $_POST['fsearch_text'];
			}
			
			$_POST['start'] = $_POST['fexport_check'];
			
			if(isset($_POST['flength']))
			{
				$_POST['length'] = $_POST['flength'];
			}
			
			if(isset($_POST['fsort_by']))
			{
				$_POST['order']['0']['column'] = $_POST['fsort_by'];
			}
			if(isset($_POST['forder_by']))
			{
				$_POST['order']['0']['dir'] = $_POST['forder_by'];
			}
			
			$result = User::get_datatables_users($column_order,$_POST);
			
			foreach($result as $row)
			{
				$rowExcel = array();				
				$rowExcel[] = ($row->first_name) ? $row->first_name:'NA';				
				$rowExcel[] = ($row->email) ? $row->email:'-';
				$rowExcel[] = ($row->referrelEmail) ? $row->referrelEmail:'-';
				$rowExcel[] = ($row->city) ? $row->city:'-';
				$rowExcel[] = ($row->country_name) ? $row->country_name:'-';
				$rowExcel[] = $row->ELT_balance > 0 ? $row->ELT_balance : '-';
				$rowExcel[] = $row->referrer_count > 0 ? $row->referrer_count : '-';
				$rowExcel[] = date("d/M/Y",strtotime($row->created_at));				
				$data[] = $rowExcel;
			}			
		}
		
		$setRec 		= $data;
		$setCounter 	= count($data);
		$setMainHeader  = "";
		$setData		= "";
		$setExcelName = "Csv_Users_List_".date("YmdHis");
		$customHeader = array("Name","Email","Referred By","City","Country","ELT Balance","Total Referrals","Date");
		ob_start();
		$this->download_csv($setCounter,$setExcelName,$setRec,$setMainHeader,$setData,$customHeader,$data);
	}


	public function exportcardpriority()
	{
		error_reporting(0);
		$data = array();
		$user = Auth::user();
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
						
			if(isset($_POST['fstart_date']))
			{
				$_POST['start_date'] = $_POST['fstart_date'];
			}			
			if(isset($_POST['fend_date']))
			{
				$_POST['end_date'] = $_POST['fend_date'];
			}			
			if(isset($_POST['fsearch_text']))
			{
				$_POST['search_text'] = $_POST['fsearch_text'];
			}
			
			$_POST['start'] = $_POST['fexport_check'];
			
			if(isset($_POST['flength']))
			{
				$_POST['length'] = $_POST['flength'];
			}
			
			if(isset($_POST['fsort_by']))
			{
				$_POST['order']['0']['column'] = $_POST['fsort_by'];
			}
			if(isset($_POST['forder_by']))
			{
				$_POST['order']['0']['dir'] = $_POST['forder_by'];
			}
						
			$transaction_list = User::get_datatables_card_list($column_order,$_POST);
						
			foreach($transaction_list as $transaction_row)
			{
				$row = array();				
				$row[] = isset($transaction_row->first_name) ? $transaction_row->first_name : '-';
				$row[] = isset($transaction_row->last_name) ? $transaction_row->last_name : '-';
				$row[] = $transaction_row->email;
				$row[] = $transaction_row->name;
				$row[] = date("d/M/Y",strtotime($transaction_row->card_requested_on));
				$data[] = $row;
				$i++;
			}
		}
		
		$setRec 		= $data;
		$setCounter 	= count($data);
		$setMainHeader  = "";
		$setData		= "";
		$setExcelName = "Csv_Cards_List_".date("YmdHis");
		$customHeader = array("FirstName","LastName","Email","CardName","RequestedDate");
		ob_start();
		$this->download_csv($setCounter,$setExcelName,$setRec,$setMainHeader,$setData,$customHeader,$data);
	}

	
	public function exporttransactions()
	{
		$user = Auth::user();
	
		if(is_null($user->OTP))
		{
			$column_order = array("transactions.created_at");
			
			if(isset($_POST['fCampaignOnly']))
			{
				$_POST['CampaignOnly'] = $_POST['fCampaignOnly'];
			}			
			if(isset($_POST['fstart_date']))
			{
				$_POST['start_date'] = $_POST['fstart_date'];
			}			
			if(isset($_POST['fend_date']))
			{
				$_POST['end_date'] = $_POST['fend_date'];
			}	
			if(isset($_POST['fcurrency_filter']))
			{
				$_POST['currency_filter'] = $_POST['fcurrency_filter'];
			}
			if(isset($_POST['ftype_name']))
			{
				$_POST['type_name'] = $_POST['ftype_name'];
			}
			if(isset($_POST['fstatus_filter']))
			{
				$_POST['status_filter'] = $_POST['fstatus_filter'];
			}		
			if(isset($_POST['fsearch_text']))
			{
				$_POST['search_text'] = $_POST['fsearch_text'];
			}

			$_POST['start'] = 0;
			
			$_POST['length'] = $this->_export_limit;
			
			$result = Transactions::get_datatables_join($column_order,$_POST,0,1);
			
			
			foreach($result as $row)
			{
				$rowExcel = array();				
				$rowExcel[] = $row->transaction_id;		
				$rowExcel[] = date("m/d/Y",strtotime($row->created_at));			
				$rowExcel[] = $row->email;		
				$rowExcel[] = $row->type_name;

				if($row->term_currency != NULL) 
				{
					$currencyName = $row->term_currency;
				}
				else
				{
					$currencyName = $row->ledger;
				}
				$rowExcel[] = $currencyName;
				
				if($row->ledger == 'ELT')
				{
					if( $row->term_amount > 0 )
					{
						if($row->term_currency == 'EUR')
						{
							$rowExcel[] = CommonHelper::format_float_balance($row->term_amount,config("constants.EUR_PRECISION"));
						}
						else
						{
							$rowExcel[] = CommonHelper::format_float_balance($row->term_amount,config("constants.DEFAULT_PRECISION"));
						}
					}
					else
					{
						$rowExcel[] = '-';
					}
					
					$rowExcel[] = CommonHelper::format_float_balance($row->value,config("constants.DEFAULT_PRECISION"));
				}
				else
				{
					if($row->type == 8)
					{
						$rowExcel[] = CommonHelper::format_float_balance(abs($row->value),config("constants.DEFAULT_PRECISION"));
					}
					else
					{
						$rowExcel[] = CommonHelper::format_float_balance($row->value,config("constants.DEFAULT_PRECISION"));
					}					
					$rowExcel[] = '-';
				}
				
				
				if($row->status == 0)
				{
					$rowExcel[] = 'Failed';
				} 
				else if($row->status == 1)
				{
					$rowExcel[] = 'Success';
				}
				else if($row->status == 2)
				{
					$rowExcel[] = 'Pending';
				} 
				else
				{
					$rowExcel[] = '-';
				}
				
				$rowExcel[] = $row->description;
				
				$data[] = $rowExcel;
			}
		}
		
		$setRec 		= $data;
		$setCounter 	= count($data);
		$setMainHeader  = "";
		$setData		= "";
		$setExcelName = "Csv_Transactions_List_".date("YmdHis");
		$customHeader = array("TransactionID","Date","Email","Type","Currency","Amount","ELT","Status","Description");
		ob_start();
		$this->download_csv($setCounter,$setExcelName,$setRec,$setMainHeader,$setData,$customHeader,$data);
	}
	
	public function exportbctransactions()
	{
		error_reporting(0);
		$data = array();
		$user = Auth::user();
		$total_count = DB::table('bc_transations')->count();
		if(is_null($user->OTP))
		{
			$column_order = array
			(
				"bc_transations.time_stamp",
				"bc_transations.txid",
				"bc_transations.amount",
				"bc_transations.fees",
				"bc_transations.blockNumber",
				"bc_transations.confirmations"
			);
			
			if(isset($_POST['fstart_date']))
			{
				$_POST['start_date'] = $_POST['fstart_date'];
			}
			
			if(isset($_POST['fend_date']))
			{
				$_POST['end_date'] = $_POST['fend_date'];
			}
			
			if(isset($_POST['fsearch_text']))
			{
				$_POST['search_text'] = $_POST['fsearch_text'];
			}
			
			$transaction_list = Blockchain::get_datatables_join_blockchain($column_order,$_POST,'', 0);			
			
			$data = array();
			
			$i = 1;
			
			foreach($transaction_list as $transaction_row)
			{
				$rowExcel = array();				
				$rowExcel[] = date("d/M/Y",strtotime($transaction_row->time_stamp));
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
		}
		
		$setRec 		= $data;
		$setCounter 	= count($data);
		$setMainHeader  = "";
		$setData		= "";
		$setExcelName = "Blockchain_transactions_".date("Y_m_d_H_i_s");
		$customHeader = array("DateTime","Txid","Amount","Fees","Type","BlockNumber","Confirmations","From address","To address");
		ob_start();
		$this->download_csv($setCounter,$setExcelName,$setRec,$setMainHeader,$setData,$customHeader,$data);
	}
	
	
	public function logs()
    {
        $user = Auth::user();
		$dataForView = array();
        if(is_null($user->OTP))
        {
			$dataForView['total_loan_applied'] = User::total_loan_applied();
			$dataForView['total_loan_amount'] = User::total_loan_amount();
			$dataForView['total_loan_term'] = User::total_loan_term();
			$dataForView['average_loan_amount'] = User::average_loan_amount();
			$dataForView['average_loan_term'] = User::average_loan_term();
			$dataForView['total_community_bonus'] = User::total_community_bonus();
            return view('admin.logs',$dataForView);
        }
        else
        {
            return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }
	
	public function ajaxlogs()
    {
		error_reporting(0);
		
		ini_set('memory_limit', '-1');
		
        $user = Auth::user();
		
		$search_value = '';
		
		$total_count = DB::table('logs')->count();
		
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
			
			$transaction_list = Logs::get_datatables_logs_list($_POST);
				
			$transaction_count = Logs::get_datatables_logs_list($_POST,1);
			
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
						
			foreach($transaction_list as $transaction_row)
			{
				$row = array();
				$row[] = $i;				
				$row[] = $transaction_row->message;
				$row[] = '<a href="javascript:void(0)" id="view_log_'.$transaction_row->id.'" 
				onclick="view_logs(' . "'" . $transaction_row->id ."'" .')"  class="btn btn-primary btn-sm">View Logs</a>';
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
	
	
	private function download_excel($setCounter,$setExcelName,$setRec,$setMainHeader,$setData,$customHeader,$data)
	{
		ob_clean();
		foreach($customHeader as $key => $value){
			$setMainHeader .= $value."\t";
		}
		for($i=0; $i<count($setRec); $i++) {
			$rowLine = '';
			foreach($setRec[$i] as $value){
				if(!isset($value) || $value == "")  {
					$value = "\t";
					} else  {
					$value = strip_tags(str_replace('"', '""', $value));
					$value = '"' . $value . '"' . "\t";
				}
				$rowLine .= $value;
			}
			$setData .= trim($rowLine)."\n";
		}
		$setData = str_replace("\r", "", $setData);
		if ($setData == "") {
			$setData = "\nno matching records found\n";
		}
		$setCounter 	= count($data);
	
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=".$setExcelName."_Report.xls");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo ucwords($setMainHeader)."\n".$setData."\n";
		
	}

	private function download_csv($setCounter,$setExcelName,$setRec,$setMainHeader,$setData,$customHeader,$data)
	{
		ob_clean();
		foreach($customHeader as $key => $value){
			$setMainHeader .= $value.",";
		}
		for($i=0; $i<count($setRec); $i++) {
			$rowLine = '';
			foreach($setRec[$i] as $value){
				if(!isset($value) || $value == "")  {
					$value = "\t";
					} else  {
					$value = strip_tags(str_replace('"', '""', $value));
					$value = '"' . $value . '"' . ",";
				}
				$rowLine .= $value;
			}
			$setData .= trim($rowLine)."\n";
		}
		$setData = str_replace("\r", "", $setData);
		if ($setData == "") {
			$setData = "\nno matching records found\n";
		}
		$setCounter 	= count($data);
	
		header("Content-Type: application/csv");
		header("Content-Disposition: attachment; filename=".$setExcelName."_Report.csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo ucwords($setMainHeader)."\n".$setData."\n";
	}
		
	public function resend($id)
    {
		
		if($id > 0)
		{
			$user = User::find($id);		
			
			$lastActivation = Activation::where('user_id', $user->id)->get()->last();
			
			if ($user->status == 0) 
			{
				$activation = new Activation();

				$activation->user_id = $user->id;

				$activation->token = str_random(64);

				$activation->ip_address = \Request::getClientIp(true);

				$activation->save();

				self::sendUserActivationEmail($user, $activation->token);
				
				return redirect()->route('admin.usersdetail',['id'=>$user->id])->with('success', trans('auth.activationSent'));
			}
			else
			{			
				return redirect()->route('admin.usersdetail',['id'=>$user->id])->with('error','Already activated');
			}
		}
		return redirect()->route('admin.users');
    }
	
	public function restructure($id)
    {
		if($id > 0)
		{
			ParentChild::restructure_child($id);
			return redirect()->route('admin.usersdetail',['id'=>$id])->with('success','ParentChild relation structured successfully');
		}
		else
		{
			return redirect()->route('admin.usersdetail',['id'=>$user->id])->with('error','Invalid request');
		}
		return redirect()->route('admin.users');
    }
	
	
	public function update_wallet_balance(Request $request)
	{
		error_reporting(0);
		parse_str($_POST['data'], $postarray);
		$response = array();
		$status = false;
		
		//print_r($postarray);die;
		
		if(isset($postarray['userId']))
		{
			/*For multiple phases calculations - Select active phases data*/
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
				$transaction_id = uniqid();
				Transactions::createTransaction($userId,'BTC',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');				
				$user_log_activity[] = "BTC updation:".$amount;				
				
				
				if(isset($postarray['add_comments_btc']) && !empty($postarray['add_comments_btc']))
				{
					$addCommentData = array();
					$addCommentData['user_id'] = $userId;
					$addCommentData['transaction_id'] = $transaction_id;
					$addCommentData['comments'] = $postarray['add_comments_btc'];
					$addCommentData['created_at'] = date("Y-m-d H:i:s");
					$addCommentData['currency'] = 'BTC';
					$addCommentData['amount'] = $amount;
					$addCommentData['comment_by'] = Auth::user()->id;
					User::insertIntoTable("admin_comments",$addCommentData);
				}				
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Credit comment: '.$postarray['add_comments_btc'].' for user :'.$userInfo->email.' Currency:BTC Amount:'.$amount,
					'level'     => 'INFO',
					'context'   => 'BTC credit'
				];
				LoggerHelper::writeDB($record);
				
			}
			elseif($postarray['add_deduct_type_btc'] == 'debit')
			{
				if($postarray['add_deduct_amount_btc'] <= $userInfo->BTC_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_btc']);
					$userInfo->subtractValue('BTC_balance',abs($postarray['add_deduct_amount_btc']));
					$transaction_id = uniqid();					
					Transactions::createTransaction($userId,'BTC',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
					$user_log_activity[] = "BTC updation:".$amount;
					
					
					if(isset($postarray['add_comments_btc']) && !empty($postarray['add_comments_btc']))
					{
						$addCommentData = array();
						$addCommentData['user_id'] = $userId;
						$addCommentData['transaction_id'] = $transaction_id;
						$addCommentData['comments'] = $postarray['add_comments_btc'];
						$addCommentData['created_at'] = date("Y-m-d H:i:s");
						$addCommentData['currency'] = 'BTC';
						$addCommentData['amount'] = $amount;
						$addCommentData['comment_by'] = Auth::user()->id;
						User::insertIntoTable("admin_comments",$addCommentData);
					}
					
					$record = 
					[
						'user_id'	=> Auth::user()->id,
						'message'   => 'Debit comment: '.$postarray['add_comments_btc'].' for user : '.$userInfo->email.' Currency:BTC Amount:'.$amount,
						'level'     => 'INFO',
						'context'   => 'BTC debit'
					];
					LoggerHelper::writeDB($record);
					
				}
			}
			
			if($postarray['add_deduct_type_eth'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_eth']);
				$userInfo->addValue('ETH_balance',$amount);
				$transaction_id = uniqid();				
				Transactions::createTransaction($userId,'ETH',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
				$user_log_activity[] = "ETH updation:".$amount;
				
				if(isset($postarray['add_comments_eth']) && !empty($postarray['add_comments_eth']))
				{
					$addCommentData = array();
					$addCommentData['user_id'] = $userId;
					$addCommentData['transaction_id'] = $transaction_id;
					$addCommentData['comments'] = $postarray['add_comments_eth'];
					$addCommentData['created_at'] = date("Y-m-d H:i:s");
					$addCommentData['currency'] = 'ETH';
					$addCommentData['amount'] = $amount;
					$addCommentData['comment_by'] = Auth::user()->id;
					User::insertIntoTable("admin_comments",$addCommentData);
				}
				
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Credit comment: '.$postarray['add_comments_eth'].' for user : '.$userInfo->email.' Currency:ETH Amount:'.$amount,
					'level'     => 'INFO',
					'context'   => 'ETH credit'
				];
				LoggerHelper::writeDB($record);
				
			}
			elseif($postarray['add_deduct_type_eth'] == 'debit')
			{
				if($postarray['add_deduct_amount_eth'] <= $userInfo->ETH_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_eth']);
					$userInfo->subtractValue('ETH_balance',abs($postarray['add_deduct_amount_eth']));
					$transaction_id = uniqid();					
					Transactions::createTransaction($userId,'ETH',$amount , 'Adjustment by admin of amount:'.$amount, 1,$transaction_id, $phaseId, NULL, 9, NULL, 'admin');
					$user_log_activity[] = "ETH updation:".$amount;
					
					if(isset($postarray['add_comments_eth']) && !empty($postarray['add_comments_eth']))
					{
						$addCommentData = array();
						$addCommentData['user_id'] = $userId;
						$addCommentData['transaction_id'] = $transaction_id;
						$addCommentData['comments'] = $postarray['add_comments_eth'];
						$addCommentData['created_at'] = date("Y-m-d H:i:s");
						$addCommentData['currency'] = 'ETH';
						$addCommentData['amount'] = $amount;
						$addCommentData['comment_by'] = Auth::user()->id;
						User::insertIntoTable("admin_comments",$addCommentData);
					}
					
					$record = 
					[
						'user_id'	=> Auth::user()->id,
						'message'   => 'Debit comment: '.$postarray['add_comments_eth'].' for user : '.$userInfo->email.' Currency:ETH Amount:'.$amount,
						'level'     => 'INFO',
						'context'   => 'ETH debit'
					];
					LoggerHelper::writeDB($record);
				
				}
			}
			
			if($postarray['add_deduct_type_elt'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_elt']);				
				$userInfo->addValue('ELT_balance',$amount);				
				$transaction_id = uniqid();				
				Transactions::createTransaction($userId,'ELT',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id , $phaseId, NULL, 5, NULL, 'admin');
				$user_log_activity[] = "ELT updation:".$amount;
				
				
				if(isset($postarray['add_comments_elt']) && !empty($postarray['add_comments_elt']))
				{
					$addCommentData = array();
					$addCommentData['user_id'] = $userId;
					$addCommentData['transaction_id'] = $transaction_id;
					$addCommentData['comments'] = $postarray['add_comments_elt'];
					$addCommentData['created_at'] = date("Y-m-d H:i:s");					
					$addCommentData['currency'] = 'ELT';
					$addCommentData['amount'] = $amount;
					$addCommentData['comment_by'] = Auth::user()->id;					
					User::insertIntoTable("admin_comments",$addCommentData);
				}
				
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Credit comment: '.$postarray['add_comments_elt'].' for user : '.$userInfo->email.' Currency:ELT Amount:'.$amount,
					'level'     => 'INFO',
					'context'   => 'ELT credit'
				];
				LoggerHelper::writeDB($record);
					
					
			}
			elseif($postarray['add_deduct_type_elt'] == 'credit-sales-count-no')
			{
				$amount = abs($postarray['add_deduct_amount_elt']);				
				$userInfo->addValue('ELT_balance',$amount);
				$transaction_id = uniqid();
				Transactions::createTransaction($userId,'ELT',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
				$user_log_activity[] = "ELT updation:".$amount;
				
				
				if(isset($postarray['add_comments_elt']) && !empty($postarray['add_comments_elt']))
				{
					$addCommentData = array();
					$addCommentData['user_id'] = $userId;
					$addCommentData['transaction_id'] = $transaction_id;
					$addCommentData['comments'] = $postarray['add_comments_elt'];
					$addCommentData['created_at'] = date("Y-m-d H:i:s");
					
					$addCommentData['currency'] = 'ELT';
					$addCommentData['amount'] = $amount;
					$addCommentData['comment_by'] = Auth::user()->id;
					
					User::insertIntoTable("admin_comments",$addCommentData);
				}
				
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Credit comment: '.$postarray['add_comments_elt'].' for user : '.$userInfo->email.' Currency:ELT Amount:'.$amount,
					'level'     => 'INFO',
					'context'   => 'ELT credit without sales count'
				];
				LoggerHelper::writeDB($record);
				
				
			}
			elseif($postarray['add_deduct_type_elt'] == 'debit')
			{
				if($postarray['add_deduct_amount_elt'] <= $userInfo->ELT_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_elt']);					
					$userInfo->subtractValue('ELT_balance',abs($postarray['add_deduct_amount_elt']));
					$transaction_id = uniqid();
					Transactions::createTransaction($userId,'ELT',$amount, 'Adjustment by admin of amount:'.$amount, 1, $transaction_id , $phaseId, NULL, 5, NULL, 'admin');
					$user_log_activity[] = "ELT updation:".$amount;
					
					
					if(isset($postarray['add_comments_elt']) && !empty($postarray['add_comments_elt']))
					{
						$addCommentData = array();
						$addCommentData['user_id'] = $userId;
						$addCommentData['transaction_id'] = $transaction_id;
						$addCommentData['comments'] = $postarray['add_comments_elt'];
						$addCommentData['created_at'] = date("Y-m-d H:i:s");
						
						$addCommentData['currency'] = 'ELT';
						$addCommentData['amount'] = $amount;
						$addCommentData['comment_by'] = Auth::user()->id;
					
						User::insertIntoTable("admin_comments",$addCommentData);
					}
					
					$record = 
					[
						'user_id'	=> Auth::user()->id,
						'message'   => 'Debit comment: '.$postarray['add_comments_elt'].' for user : '.$userInfo->email.' Currency:ELT Amount:'.$amount,
						'level'     => 'INFO',
						'context'   => 'ELT debit'
					];
					LoggerHelper::writeDB($record);
				
				}
			}
			elseif($postarray['add_deduct_type_elt'] == 'debit-sales-count-no')
			{
				if($postarray['add_deduct_amount_elt'] <= $userInfo->ELT_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_elt']);					
					$userInfo->subtractValue('ELT_balance',abs($postarray['add_deduct_amount_elt']));
					$transaction_id = uniqid();
					Transactions::createTransaction($userId,'ELT',$amount, 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');					
					$user_log_activity[] = "ELT updation:".$amount;
					
					
					if(isset($postarray['add_comments_elt']) && !empty($postarray['add_comments_elt']))
					{
						$addCommentData = array();
						$addCommentData['user_id'] = $userId;
						$addCommentData['transaction_id'] = $transaction_id;
						$addCommentData['comments'] = $postarray['add_comments_elt'];
						$addCommentData['created_at'] = date("Y-m-d H:i:s");
						
						$addCommentData['currency'] = 'ELT';
						$addCommentData['amount'] = $amount;
						$addCommentData['comment_by'] = Auth::user()->id;
					
						User::insertIntoTable("admin_comments",$addCommentData);
					}
				
					$record = 
					[
						'user_id'	=> Auth::user()->id,
						'message'   => 'Debit comment: '.$postarray['add_comments_elt'].' for user : '.$userInfo->email.' Currency:ELT Amount:'.$amount,
						'level'     => 'INFO',
						'context'   => 'ELT debit without sales count'
					];
					LoggerHelper::writeDB($record);
					
				}
			}
			
			if($postarray['add_deduct_type_eur'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_eur']);
				$userInfo->addValue('EUR_balance',$amount);
				$transaction_id = uniqid();				
				Transactions::createTransaction($userId,'EUR',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
				$user_log_activity[] = "EUR updation:".$amount;
				
				
				if(isset($postarray['add_comments_eur']) && !empty($postarray['add_comments_eur']))
				{
					$addCommentData = array();
					$addCommentData['user_id'] = $userId;
					$addCommentData['transaction_id'] = $transaction_id;
					$addCommentData['comments'] = $postarray['add_comments_eur'];
					$addCommentData['created_at'] = date("Y-m-d H:i:s");
					
					$addCommentData['currency'] = 'EUR';
					$addCommentData['amount'] = $amount;
					$addCommentData['comment_by'] = Auth::user()->id;
						
					User::insertIntoTable("admin_comments",$addCommentData);
				}
				
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Credit comment: '.$postarray['add_comments_eur'].' for user : '.$userInfo->email.' Currency:EUR Amount:'.$amount,
					'level'     => 'INFO',
					'context'   => 'EUR credit'
				];
				LoggerHelper::writeDB($record);
				
				
			}
			elseif($postarray['add_deduct_type_eur'] == 'debit')
			{
				if($postarray['add_deduct_amount_eur'] <= $userInfo->EUR_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_eur']);
					$userInfo->subtractValue('EUR_balance',abs($postarray['add_deduct_amount_eur']));
					$transaction_id = uniqid();					
					Transactions::createTransaction($userId,'EUR',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
					$user_log_activity[] = "EUR updation:".$amount;	
					
					
					if(isset($postarray['add_comments_eur']) && !empty($postarray['add_comments_eur']))
					{
						$addCommentData = array();
						$addCommentData['user_id'] = $userId;
						$addCommentData['transaction_id'] = $transaction_id;
						$addCommentData['comments'] = $postarray['add_comments_eur'];
						$addCommentData['created_at'] = date("Y-m-d H:i:s");
						
						$addCommentData['currency'] = 'EUR';
						$addCommentData['amount'] = $amount;
						$addCommentData['comment_by'] = Auth::user()->id;
						
						User::insertIntoTable("admin_comments",$addCommentData);
					}
					
					$record = 
					[
						'user_id'	=> Auth::user()->id,
						'message'   => 'Debit comment: '.$postarray['add_comments_eur'].' for user : '.$userInfo->email.' Currency:EUR Amount:'.$amount,
						'level'     => 'INFO',
						'context'   => 'EUR debit'
					];
					LoggerHelper::writeDB($record);
					
				}
			}
			
			if($postarray['add_deduct_type_bch'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_bch']);
				$userInfo->addValue('BCH_balance',$amount);
				$transaction_id = uniqid();
				Transactions::createTransaction($userId,'BCH',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
				$user_log_activity[] = "BCH updation:".$amount;
				
				
				if(isset($postarray['add_comments_bch']) && !empty($postarray['add_comments_bch']))
				{
					$addCommentData = array();
					$addCommentData['user_id'] = $userId;
					$addCommentData['transaction_id'] = $transaction_id;
					$addCommentData['comments'] = $postarray['add_comments_bch'];
					$addCommentData['created_at'] = date("Y-m-d H:i:s");
					$addCommentData['currency'] = 'BCH';
					$addCommentData['amount'] = $amount;
					$addCommentData['comment_by'] = Auth::user()->id;
					User::insertIntoTable("admin_comments",$addCommentData);
				}				
				
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Credit comment: '.$postarray['add_comments_bch'].' for user : '.$userInfo->email.' Currency:BCH Amount:'.$amount,
					'level'     => 'INFO',
					'context'   => 'BCH credit'
				];
				LoggerHelper::writeDB($record);
					
					
			}
			elseif($postarray['add_deduct_type_bch'] == 'debit')
			{
				if($postarray['add_deduct_amount_bch'] <= $userInfo->BCH_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_bch']);
					$userInfo->subtractValue('BCH_balance',abs($postarray['add_deduct_amount_bch']));
					$transaction_id = uniqid();
					Transactions::createTransaction($userId,'BCH',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
					$user_log_activity[] = "BCH updation:".$amount;
					
					
					if(isset($postarray['add_comments_bch']) && !empty($postarray['add_comments_bch']))
					{
						$addCommentData = array();
						$addCommentData['user_id'] = $userId;
						$addCommentData['transaction_id'] = $transaction_id;
						$addCommentData['comments'] = $postarray['add_comments_bch'];
						$addCommentData['created_at'] = date("Y-m-d H:i:s");
						
						$addCommentData['currency'] = 'BCH';
						$addCommentData['amount'] = $amount;
						$addCommentData['comment_by'] = Auth::user()->id;
					
						User::insertIntoTable("admin_comments",$addCommentData);
					}
					
					$record = 
					[
						'user_id'	=> Auth::user()->id,
						'message'   => 'Debit comment: '.$postarray['add_comments_bch'].' for user : '.$userInfo->email.' Currency:BCH Amount:'.$amount,
						'level'     => 'INFO',
						'context'   => 'BCH debit'
					];
					LoggerHelper::writeDB($record);
					
				
				}
			}
			
			if($postarray['add_deduct_type_ltc'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_ltc']);
				$userInfo->addValue('LTC_balance',$amount);
				$transaction_id = uniqid();
				Transactions::createTransaction($userId,'LTC',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
				$user_log_activity[] = "LTC updation:".$amount;
				
				
				if(isset($postarray['add_comments_ltc']) && !empty($postarray['add_comments_ltc']))
				{
					$addCommentData = array();
					$addCommentData['user_id'] = $userId;
					$addCommentData['transaction_id'] = $transaction_id;
					$addCommentData['comments'] = $postarray['add_comments_ltc'];
					$addCommentData['created_at'] = date("Y-m-d H:i:s");
					
					$addCommentData['currency'] = 'LTC';
					$addCommentData['amount'] = $amount;
					$addCommentData['comment_by'] = Auth::user()->id;
					
					User::insertIntoTable("admin_comments",$addCommentData);
				}
				
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Credit comment: '.$postarray['add_comments_ltc'].' for user : '.$userInfo->email.' Currency:LTC Amount:'.$amount,
					'level'     => 'INFO',
					'context'   => 'LTC credit'
				];
				LoggerHelper::writeDB($record);
				
				
			}
			elseif($postarray['add_deduct_type_ltc'] == 'debit')
			{
				if($postarray['add_deduct_amount_ltc'] <= $userInfo->LTC_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_ltc']);
					$userInfo->subtractValue('LTC_balance',abs($postarray['add_deduct_amount_ltc']));
					$transaction_id = uniqid();
					Transactions::createTransaction($userId,'LTC',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
					$user_log_activity[] = "LTC updation:".$amount;
					
					
					$addCommentData = array();
					$addCommentData['user_id'] = $userId;
					$addCommentData['transaction_id'] = $transaction_id;
					$addCommentData['comments'] = $postarray['add_comments_ltc'];
					$addCommentData['created_at'] = date("Y-m-d H:i:s");
					
					$addCommentData['currency'] = 'LTC';
					$addCommentData['amount'] = $amount;
					$addCommentData['comment_by'] = Auth::user()->id;
					
					User::insertIntoTable("admin_comments",$addCommentData);
					
					$record = 
					[
						'user_id'	=> Auth::user()->id,
						'message'   => 'Debit comment: '.$postarray['add_comments_ltc'].' for user : '.$userInfo->email.' Currency:LTC Amount:'.$amount,
						'level'     => 'INFO',
						'context'   => 'LTC debit'
					];
					LoggerHelper::writeDB($record);
					
				}
			}
			
			if($postarray['add_deduct_type_etc'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_etc']);
				$userInfo->addValue('ETC_balance',$amount);
				$transaction_id = uniqid();
				Transactions::createTransaction($userId,'ETC',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
				$user_log_activity[] = "ETC updation:".$amount;
				
				
				$addCommentData = array();
				$addCommentData['user_id'] = $userId;
				$addCommentData['transaction_id'] = $transaction_id;
				$addCommentData['comments'] = $postarray['add_comments_etc'];
				$addCommentData['created_at'] = date("Y-m-d H:i:s");
				
				$addCommentData['currency'] = 'ETC';
				$addCommentData['amount'] = $amount;
				$addCommentData['comment_by'] = Auth::user()->id;
					
				User::insertIntoTable("admin_comments",$addCommentData);
				
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Credit comment: '.$postarray['add_comments_etc'].' for user : '.$userInfo->email.' Currency:ETC Amount:'.$amount,
					'level'     => 'INFO',
					'context'   => 'ETC credit'
				];
				LoggerHelper::writeDB($record);
				
					
			}
			elseif($postarray['add_deduct_type_etc'] == 'debit')
			{
				if($postarray['add_deduct_amount_etc'] <= $userInfo->ETC_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_etc']);
					$transaction_id = uniqid();
					$userInfo->subtractValue('ETC_balance',abs($postarray['add_deduct_amount_etc']));
					Transactions::createTransaction($userId,'ETC',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
					$user_log_activity[] = "ETC updation:".$amount;
					
					
					$addCommentData = array();
					$addCommentData['user_id'] = $userId;
					$addCommentData['transaction_id'] = $transaction_id;
					$addCommentData['comments'] = $postarray['add_comments_etc'];
					$addCommentData['created_at'] = date("Y-m-d H:i:s");
					
					$addCommentData['currency'] = 'ETC';
					$addCommentData['amount'] = $amount;
					$addCommentData['comment_by'] = Auth::user()->id;
					
					User::insertIntoTable("admin_comments",$addCommentData);
					
					$record = 
					[
						'user_id'	=> Auth::user()->id,
						'message'   => 'Debit comment: '.$postarray['add_comments_etc'].' for user : '.$userInfo->email.' Currency:ETC Amount:'.$amount,
						'level'     => 'INFO',
						'context'   => 'ETC debit'
					];
					LoggerHelper::writeDB($record);
				
				}				
			}
			
			if($postarray['add_deduct_type_xrp'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_xrp']);
				$userInfo->addValue('XRP_balance',$amount);
				$transaction_id = uniqid();
				Transactions::createTransaction($userId,'XRP',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
				$user_log_activity[] = "XRP updation:".$amount;
				
				
				$addCommentData = array();
				$addCommentData['user_id'] = $userId;
				$addCommentData['transaction_id'] = $transaction_id;
				$addCommentData['comments'] = $postarray['add_comments_xrp'];
				$addCommentData['created_at'] = date("Y-m-d H:i:s");
				User::insertIntoTable("admin_comments",$addCommentData);
				
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Credit comment: '.$postarray['add_comments_xrp'].' for user : '.$userInfo->email.' Currency:XRP Amount:'.$amount,
					'level'     => 'INFO',
					'context'   => 'XRP credit'
				];
				LoggerHelper::writeDB($record);
				
					
			}
			elseif($postarray['add_deduct_type_xrp'] == 'debit')
			{
				if($postarray['add_deduct_amount_xrp'] <= $userInfo->XRP_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_xrp']);
					$userInfo->subtractValue('XRP_balance',abs($postarray['add_deduct_amount_xrp']));
					$transaction_id = uniqid();
					Transactions::createTransaction($userId,'XRP',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
					$user_log_activity[] = "XRP updation:".$amount;
					
					
					$addCommentData = array();
					$addCommentData['user_id'] = $userId;
					$addCommentData['transaction_id'] = $transaction_id;
					$addCommentData['comments'] = $postarray['add_comments_xrp'];
					$addCommentData['created_at'] = date("Y-m-d H:i:s");
					
					$addCommentData['currency'] = 'XRP';
					$addCommentData['amount'] = $amount;
					$addCommentData['comment_by'] = Auth::user()->id;
					
					User::insertIntoTable("admin_comments",$addCommentData);
					
					$record = 
					[
						'user_id'	=> Auth::user()->id,
						'message'   => 'Debit comment: '.$postarray['add_comments_xrp'].' for user : '.$userInfo->email.' Currency:XRP Amount:'.$amount,
						'level'     => 'INFO',
						'context'   => 'XRP debit'
					];
					LoggerHelper::writeDB($record);
				
				}
			}
			
			if($postarray['add_deduct_type_dash'] == 'credit')
			{
				$amount = abs($postarray['add_deduct_amount_dash']);
				$userInfo->addValue('DASH_balance',$amount);
				$transaction_id = uniqid();
				Transactions::createTransaction($userId,'DASH',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
				$user_log_activity[] = "DASH updation:".$amount;
				
				
				$addCommentData = array();
				$addCommentData['user_id'] = $userId;
				$addCommentData['transaction_id'] = $transaction_id;
				$addCommentData['comments'] = $postarray['add_comments_dash'];
				$addCommentData['created_at'] = date("Y-m-d H:i:s");
				
				$addCommentData['currency'] = 'DASH';
				$addCommentData['amount'] = $amount;
				$addCommentData['comment_by'] = Auth::user()->id;
				
				User::insertIntoTable("admin_comments",$addCommentData);
				
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Credit comment: '.$postarray['add_comments_dash'].' for user : '.$userInfo->email.' Currency:DASH Amount:'.$amount,
					'level'     => 'INFO',
					'context'   => 'Dash credit'
				];
				LoggerHelper::writeDB($record);
				
			}
			elseif($postarray['add_deduct_type_dash'] == 'debit')
			{
				if($postarray['add_deduct_amount_dash'] <= $userInfo->DASH_balance)
				{
					$amount = -1 * abs($postarray['add_deduct_amount_dash']);
					$userInfo->subtractValue('DASH_balance',abs($postarray['add_deduct_amount_dash']));
					$transaction_id = uniqid();
					Transactions::createTransaction($userId,'DASH',$amount , 'Adjustment by admin of amount:'.$amount, 1, $transaction_id, $phaseId, NULL, 9, NULL, 'admin');
					$user_log_activity[] = "DASH updation:".$amount;
					
					
					$addCommentData = array();
					$addCommentData['user_id'] = $userId;
					$addCommentData['transaction_id'] = $transaction_id;
					$addCommentData['comments'] = $postarray['add_comments_dash'];
					$addCommentData['created_at'] = date("Y-m-d H:i:s");
					
					$addCommentData['currency'] = 'DASH';
					$addCommentData['amount'] = $amount;
					$addCommentData['comment_by'] = Auth::user()->id;
					
					User::insertIntoTable("admin_comments",$addCommentData);
					
					$record = 
					[
						'user_id'	=> Auth::user()->id,
						'message'   => 'Debit comment: '.$postarray['add_comments_dash'].' for user : '.$userInfo->email.' Currency:DASH Amount:'.$amount,
						'level'     => 'INFO',
						'context'   => 'Dash debit'
					];
					LoggerHelper::writeDB($record);
				}
			}

			if($userInfo->save())
			{
				$response['status'] = true;
				
				//Check if process referral bonus in checked
				if(isset($postarray['processReferral']))
				{
					$this->processReferralBonus($userId, $postarray['source_wallet_type'], $postarray['source_amount'],NULL, $phaseId);
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
			$userInfo = User::find($userId);
		}
		
		$userInfo->BTC_balance = CommonHelper::format_float_balance($userInfo->BTC_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->ETH_balance = CommonHelper::format_float_balance($userInfo->ETH_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->EUR_balance = CommonHelper::format_float_balance($userInfo->EUR_balance,config("constants.EUR_PRECISION"));
		$userInfo->ELT_balance = round($userInfo->ELT_balance,config('constants.ELT_PRECISION'));
		$userInfo->BCH_balance = CommonHelper::format_float_balance($userInfo->BCH_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->LTC_balance = CommonHelper::format_float_balance($userInfo->LTC_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->ETC_balance = CommonHelper::format_float_balance($userInfo->ETC_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->XRP_balance = CommonHelper::format_float_balance($userInfo->XRP_balance,config("constants.DEFAULT_PRECISION"));
		$userInfo->DASH_balance = CommonHelper::format_float_balance($userInfo->DASH_balance,config("constants.DEFAULT_PRECISION"));
		
		$response['userInfo'] = $userInfo;
		
		echo json_encode($response,true);exit;
	
	}
	
	
	private function processReferralBonus($UserId, $source_wallet_type, $source_amount,$ref_transaction_id=NULL, $phaseId)
	{
		$userInfo = User::find($UserId);
		$configuration = Configurations::where('valid_to', '=', "9999-12-31")->get();
		/*Getting all referral commission*/
		foreach($configuration as $config)
		{
			if($config->name == "Referral-%-Level-1") {
				$commissionLevel1 = $config->defined_value;
			} 
			elseif($config->name == "Referral-%-Level-2") {
				$commissionLevel2 = $config->defined_value;
			} 
			elseif($config->name == "Referral-%-Level-3") {
				$commissionLevel3 = $config->defined_value;
			}
			elseif($config->name == "Referral-%-Level-4") {
				$commissionLevel4 = $config->defined_value;
			} 
			elseif($config->name == "Referral-%-Level-5") {
				$commissionLevel5 = $config->defined_value;
			}
		}
		
		/*
		Calculations for referral system
		If amount is being added not subtracted and its not of ELT Balance TYPE
		*/
		if($source_amount>0 && $source_wallet_type != 'ELT_balance')
		{
			$user1 = User::find($userInfo->referrer_user_id);
			if($user1 == null) {
				return 0;
			}
			if($source_wallet_type == "BTC_balance"){
				$trans = "BTC";
			}
			if($source_wallet_type == "EUR_balance"){
				$trans = "EUR";
			}
			if($source_wallet_type == "ELT_balance"){
				$trans = "ELT";
			}
			if($source_wallet_type == "ETH_balance"){
				$trans = "ETH";
			}
			$commisionUser1 = User::calculateCommision($source_amount, $commissionLevel1);
			if($commisionUser1>0) {
				$user1->addValue($source_wallet_type,$commisionUser1);
			}
			$user1->save();
			
			Transactions::createTransactionWithReference($user1->id, $trans, $commisionUser1, 'Commission by User referral via admin adjustment', 1, uniqid(), $phaseId, $address = NULL, NULL, NULL, $ref_transaction_id, 'bonus');
			
			$user2 = User::find($user1->referrer_user_id);
			if($user2 == null) {
				return 0;
			}
			
			$commisionUser2 = User::calculateCommision($source_amount,$commissionLevel2);
			if($commisionUser2>0) {
				$user2->addValue($source_wallet_type,$commisionUser2);
			}
			$user2->save();
			
			Transactions::createTransactionWithReference($user2->id, $trans, $commisionUser2, 'Commission by User referral via admin adjustment', 1, uniqid(), $phaseId, $address = NULL, NULL, NULL, $ref_transaction_id, 'bonus');
			
			$user3 = User::find($user2->referrer_user_id);
			if($user3 == null) {							
				return 0;
			}
			
			$commisionUser3 = User::calculateCommision($source_amount,$commissionLevel3);
			if($commisionUser3>0) {
				$user3->addValue($source_wallet_type,$commisionUser3);
			} 
			$user3->save();
			
			Transactions::createTransactionWithReference($user3->id, $trans, $commisionUser3, 'Commission by User referral via admin adjustment', 1, uniqid(), $phaseId, $address = NULL, NULL, NULL, $ref_transaction_id, 'bonus');
			
			$user4 = User::find($user3->referrer_user_id);
			if($user4 == null) {							
				return 0;
			}
			$commisionUser4 = User::calculateCommision($source_amount,$commissionLevel4);
			if($commisionUser4>0) {
				$user4->addValue($source_wallet_type,$commisionUser4);
			} 
			$user4->save();
			Transactions::createTransactionWithReference($user4->id, $trans, $commisionUser4, 'Commission by User referral via admin adjustment', 1, uniqid(), $phaseId, $address = NULL, NULL, NULL, $ref_transaction_id, 'bonus');
			
			$user5 = User::find($user4->referrer_user_id);
			if($user5 == null) {							
				return 0;
			}
			$commisionUser5 = User::calculateCommision($source_amount,$commissionLevel5);
			if($commisionUser5>0) {
				$user5->addValue($source_wallet_type,$commisionUser5);
			} 
			$user5->save();
			Transactions::createTransactionWithReference($user5->id, $trans, $commisionUser5, 'Commission by User referral via admin adjustment', 1, uniqid(), $phaseId, $address = NULL, NULL, NULL, $ref_transaction_id, 'bonus');
		}
	}
	
	public function sendWhitelistEmail($id)
    {
		if($id > 0)
		{
			$whitelist_row = User::get_whitelist_user_by_id($id);
			if($whitelist_row->email)
			{
				$user = User::find(1);
				$user->email = $whitelist_row->email;
				$user->user_name = $whitelist_row->name;
				$user->token = $whitelist_row->token;
				self::SendWhiteListWelcomeEmailFunc($user,'');
			}
			
			return redirect()->route('admin.whitelistusers')->with('success','Email sent successfully');
		}
		return redirect()->route('admin.whitelistusers');
    }
	
	public function view_log_detail($id)
    {
		echo "<pre>";
		if($id > 0)
		{
			$Logs = (array)Logs::find($id);
			print_r($Logs);
		}
		die('....end here');
    }
	
	public function apply_for_invoice()
    {
		$response = array();
		$response['msg'] = "";
		$response['status'] = "0";
		parse_str($_POST['data'], $postarray);
		$no_bonus = 0;
		$no_invoice = 0;
		$elt_amount = 0;
		$euro_amount_for_bonus = 0;
		$invoiceUser = 0;
		$invoice_exclude_sales = 0;
		
		//print_r($postarray);die;
				
		if(isset($postarray['invoice_no_bonus']) && $postarray['invoice_no_bonus'] == 1){
			$no_bonus = 1;
		}
		if(isset($postarray['invoice_no_invoice']) && $postarray['invoice_no_invoice'] == 1){
			$no_invoice = 1;
		}
		if(isset($postarray['invoice_exclude_sales']) && $postarray['invoice_exclude_sales'] == 1){
			$invoice_exclude_sales = 1;
		}		
		if(isset($postarray['invoiceUser'])){
			$invoiceUser = $postarray['invoiceUser'];
		}
		if(isset($postarray['invoice_elt_amount'])){
			$elt_amount = $postarray['invoice_elt_amount'];
		}
		if(isset($postarray['invoice_token_price'])){
			$token_price = $postarray['invoice_token_price'];
		}
		
		$term_amount = $postarray['invoice_payment_amount'];
		
		$term_currency = strtoupper($postarray['invoice_payment_type']);
		
		if($elt_amount < 0 || $invoiceUser < 1)
		{
			$response['msg'] = 'Please enter valid input';
			echo json_encode($response);exit;
		}
		if($term_currency == 'EUR' && $token_price < 0 )
		{
			$response['msg'] = 'Please enter token price for EUR';
			echo json_encode($response);exit;
		}
		
		$userInfo = User::find($invoiceUser);
		
		if($userInfo)
		{
			$userInfo->addValue('ELT_balance',$elt_amount);
			
			if($userInfo->save())
			{
				$response['msg'] = 'Invoice generated successfully';
				$response['status'] = '1';
			}
			
			$ref_transaction_id = uniqid();
			
			if($term_currency == 'EUR')
			{
				$euro_amount = $elt_amount * $token_price;
				$term_amount = $euro_amount;				
			}
			else
			{
				$term_amount = ( $term_amount > 0 ) ? $term_amount : 0;
			}
			
			$tran_type_name = $invoice_exclude_sales == 1 ? 'bonus':'invoice';
			
			Transactions::createTransactionWithTermCurrency($userInfo->id, 'ELT', $elt_amount, 'By admin', 1, $ref_transaction_id, NULL, NULL, $term_currency, $term_amount, $tran_type_name, 0, 1);
			
			if($no_bonus == 0){ /*Distribute Bonus*/
				$conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
				$conversion_rate_data = array();
				if (!$conversion_rates->isEmpty()) {
					foreach ($conversion_rates as $key => $rates_minimume_values) {
						$conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
						$conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
					}
				}
				
				$phaseId = Transactions::get_current_phase_id();
				$this->distributeBonusToParentReferrals($userInfo->id, $term_currency.'_balance',$term_currency, $term_amount, $ref_transaction_id, $phaseId, $conversion_rate_data,$elt_amount, $postarray);
			}
			if($no_invoice == 0){ /*Generate invoice*/
				$generateInvoiceNumber = CommonHelper::generateInvoiceNumber();
				$invoice_data['invoice_number'] = $generateInvoiceNumber;
				$invoice_data['ref_transaction_id'] = $ref_transaction_id;
				$invoice_data['user_id'] = $invoiceUser;
				$invoice_data['elt_amount'] = $elt_amount;
				$invoice_data['token_price'] = $token_price;
				$invoice_data['currency'] = $term_currency;
				$invoice_data['invoice_count'] = Transactions::get_invoice_no($invoiceUser);
				$invoice_data['currency_amount'] = $term_amount;
				$invoice_data['description'] = 'By admin';
				$invoice_data['created_at'] = date("Y-m-d H:i:s");
				Transactions::add_row_in_table("elt_invoices",$invoice_data);
				
				if(isset($postarray['tailoredInvoiceSelected']) && $postarray['tailoredInvoiceSelected'] == 1)
				{
					$invoice_type = 'TailoredInvoice';
				}
				else
				{
					$invoice_type = 'Invoice';
				}
				
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Token buy with invoiceId: '.$generateInvoiceNumber.' for user: '.$userInfo->email.' elt amount: '.$elt_amount.' term amount: '.$term_amount.' against currency: '.$term_currency,
					'level'     => 'INFO',
					'context'   => 'Admin '.$invoice_type,
					'extra' => [
						'invoice_data' => json_encode($postarray)
					]
				];
				LoggerHelper::writeDB($record);
			}
			else
			{
				$record = 
				[
					'user_id'	=> Auth::user()->id,
					'message'   => 'Token buy without invoice for user: '.$userInfo->email.' elt amount: '.$elt_amount.' term amount: '.$term_amount.' against currency: '.$term_currency,
					'level'     => 'userInfo',
					'context'   => 'Admin '.$invoice_type,
					'extra' => [
						'invoice_data' => json_encode($postarray)
					]
				];
				LoggerHelper::writeDB($record);
			}
		}
		echo json_encode($response);exit;
	}
	
	private function distributeBonusToParentReferrals($login_user_id, $txtBalance, $payment_units, $payment_amount, $parent_transaction_id, $phaseId, $conversion_rate_data, $buyingToken=0, $postarray)
	{
		
		/*
		Array
		(
			[tailoredInvoiceSelected] => 1
			[tinvoice_additional_token] => 10
			[tinvoice_parent_bonus_checkbox_1] => 1
			[tinvoice_parent_bonus_1] => 9
			[tinvoice_parent_bonus_checkbox_2] => 1
			[tinvoice_parent_bonus_2] => 8
			[tinvoice_parent_bonus_checkbox_3] => 1
			[tinvoice_parent_bonus_3] => 7
			[tinvoice_parent_bonus_checkbox_4] => 1
			[tinvoice_parent_bonus_4] => 6
			[tinvoice_parent_bonus_checkbox_5] => 1
			[tinvoice_parent_bonus_5] => 5
		)
		*/
				
		$user = User::find($login_user_id);
		$bonusDistributionUptoLevel = 5;
		$ParentList = ParentChild::get_my_upline($login_user_id);
		if(count($ParentList))
		{
			for($level=1; $level<=$bonusDistributionUptoLevel; $level++)
			{
				if(isset($ParentList[$level-1]) && $ParentList[$level-1] > 0)
				{
					$parentId = $ParentList[$level-1];
					$parentRefferralUserDetails = User::find($parentId);
					if(isset($parentRefferralUserDetails->id) && !empty($parentRefferralUserDetails->id))
					{
						if($buyingToken > 0 && ($parentRefferralUserDetails->admin_opt_bonus == 1 || $parentRefferralUserDetails->user_opt_bonus == 0) && $level == 1)
						{
							if(isset($postarray['tailoredInvoiceSelected']) && $postarray['tailoredInvoiceSelected'] == 1 && isset($postarray['tinvoice_additional_token']) && $postarray['tinvoice_additional_token'] > 0)
							{
								$Additional_token_when_bonus_not_opted = $postarray['tinvoice_additional_token'];
							}
							else
							{
								$Additional_token_when_bonus_not_opted = $conversion_rate_data['Additional-token-when-bonus-not-opted'][0];
							}
							
							$additionalTokenBonus = User::calculateCommision($buyingToken,$Additional_token_when_bonus_not_opted);
							$user->addValue('ELT_balance',$additionalTokenBonus);
							$user->save();
							Transactions::createTransaction($login_user_id,'ELT', $additionalTokenBonus, 'Additional token bonus: ' . $additionalTokenBonus, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus',0);
						}
						
						$referralLevelBonusPercentage = 0;
						
						if(isset($postarray['tinvoice_parent_bonus_checkbox_'.$level]) && $postarray['tinvoice_parent_bonus_checkbox_'.$level] == 1 && isset($postarray['tinvoice_parent_bonus_'.$level]) && $postarray['tinvoice_parent_bonus_'.$level] > 0){
							$referralLevelBonusPercentage = $postarray['tinvoice_parent_bonus_'.$level];
						}
						else{							
							$referralLevelBonusPercentage = $conversion_rate_data['Referral-%-Level-'.$level][0];
						}
						
						$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($parentRefferralUserDetails->id,$referralLevelBonusPercentage);
						$newBonusPercentage = $newBonusData["new_bonus_percent"];
						$parentRefferralCommision = User::calculateCommision($payment_amount,$newBonusPercentage);
						
						if(isset($parentRefferralCommision) && !empty($parentRefferralCommision) && ($parentRefferralUserDetails->user_opt_bonus == 1 || $parentRefferralUserDetails->admin_opt_bonus == 1)) 
						{
							$parentRefferralUserDetails->addValue($txtBalance, $parentRefferralCommision);
							$parentRefferralUserDetails->save();
							$unpaidBonus=0;
							$unpaid_bonus_percent = $newBonusData["unpaid_bonus_percent"];
							$unpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent);
							Transactions::createTransaction($parentRefferralUserDetails->id, $payment_units, $parentRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $unpaidBonus);
							$direct_bonus_ELT = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-'.$level.'-Bonus-ELT']])->get();							
							$ELT_bonus = $direct_bonus_ELT[0]->defined_value;
							if(isset($ELT_bonus) && $ELT_bonus > 0)
							{
								$parentRefferralUserDetails->addValue('ELT_balance', $ELT_bonus);
								$parentRefferralUserDetails->save();
								Transactions::createTransaction($parentRefferralUserDetails->id, 'ELT', $ELT_bonus, 'Level-'.$level.' Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
							}
						}
						else
						{
							$unpaid_bonus_amount = User::calculateCommision($payment_amount,$referralLevelBonusPercentage);
							Transactions::createEmptyTransaction($parentRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
						}
					}
				}
			}
		}
	}
	
	/*
	private function distributeBonusToParentReferrals($login_user_id, $txtBalance, $payment_units, $payment_amount, $parent_transaction_id, $phaseId, $conversion_rate_data, $buyingToken=0)
	{
		error_reporting(0);
		$user = User::find($login_user_id);		
		
		// level One Referral Commission
        $levelOneRefferralUserDetails = User::find($user->referrer_user_id);
        if(isset($levelOneRefferralUserDetails->id) && !empty($levelOneRefferralUserDetails->id)) 
		{
			if($buyingToken > 0 && ($levelOneRefferralUserDetails->admin_opt_bonus == 1 || $levelOneRefferralUserDetails->user_opt_bonus == 0))
			{
				$additionalTokenBonus = User::calculateCommision($buyingToken,$conversion_rate_data['Additional-token-when-bonus-not-opted'][0]);
				$user->addValue('ELT_balance',$additionalTokenBonus);
                $user->save();				
				Transactions::createTransaction($user->id,'ELT', $additionalTokenBonus, 'Additional token bonus: ' . $additionalTokenBonus, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus',0);
			}
			
			$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($levelOneRefferralUserDetails->id,$conversion_rate_data['Referral-%-Level-1'][0]);
			$newBonusPercentageLevelOne = $newBonusData["new_bonus_percent"];
            $levelOneRefferralCommision = User::calculateCommision($payment_amount,$newBonusPercentageLevelOne);
            
			if(isset($levelOneRefferralCommision) && !empty($levelOneRefferralCommision) && ($levelOneRefferralUserDetails->user_opt_bonus == 1 || $levelOneRefferralUserDetails->admin_opt_bonus == 1)) 
			{
                $levelOneRefferralUserDetails->addValue($txtBalance, $levelOneRefferralCommision);
                $levelOneRefferralUserDetails->save();                
				$levelOneUnpaidBonus=0;
				$unpaid_bonus_percent_level_one = $newBonusData["unpaid_bonus_percent"];
				$levelOneUnpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent_level_one);
			
                Transactions::createTransaction($levelOneRefferralUserDetails->id, $payment_units, $levelOneRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $levelOneUnpaidBonus);
				
                // Direct Bonus ELT Level-1
                $direct_bonus_ELT_level_one = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-1-Bonus-ELT']])->get();
                $ELT_bonus_level_one = $direct_bonus_ELT_level_one[0]->defined_value;
                if (isset($ELT_bonus_level_one) && $ELT_bonus_level_one > 0) 
				{
                    $levelOneRefferralUserDetails->addValue('ELT_balance', $ELT_bonus_level_one);
                    $levelOneRefferralUserDetails->save();
                    Transactions::createTransaction($levelOneRefferralUserDetails->id, 'ELT', $ELT_bonus_level_one, 'Level-1 Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
                }
            }
			else
			{
				$unpaid_bonus_amount = User::calculateCommision($payment_amount,$conversion_rate_data['Referral-%-Level-1'][0]);
				Transactions::createEmptyTransaction($levelOneRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
			}
        
		
			// level 2
			$levelTwoRefferralUserDetails = User::find($levelOneRefferralUserDetails->referrer_user_id);
			if(isset($levelTwoRefferralUserDetails->id) && !empty($levelTwoRefferralUserDetails->id)) 
			{
				$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($levelTwoRefferralUserDetails->id,$conversion_rate_data['Referral-%-Level-2'][0]);
				$newBonusPercentageLevelTwo = $newBonusData["new_bonus_percent"];
				$levelTwoRefferralCommision = User::calculateCommision($payment_amount,$newBonusPercentageLevelTwo);
				if (isset($levelTwoRefferralCommision) && !empty($levelTwoRefferralCommision) && ($levelTwoRefferralUserDetails->user_opt_bonus == 1 || $levelTwoRefferralUserDetails->admin_opt_bonus == 1)) 
				{
					$levelTwoRefferralUserDetails->addValue($txtBalance, $levelTwoRefferralCommision);
					$levelTwoRefferralUserDetails->save();				
					$levelTwoUnpaidBonus=0;
					$unpaid_bonus_percent_level_two = $newBonusData["unpaid_bonus_percent"];
					$levelTwoUnpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent_level_two);
					Transactions::createTransaction($levelTwoRefferralUserDetails->id, $payment_units, $levelTwoRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $levelTwoUnpaidBonus);
					
					// Direct Bonus ELT Level-2
					$direct_bonus_ELT_level_two = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-2-Bonus-ELT']])->get();
					$ELT_bonus_level_two = $direct_bonus_ELT_level_two[0]->defined_value;
					if (isset($ELT_bonus_level_two) && $ELT_bonus_level_two > 0) 
					{
						$levelTwoRefferralUserDetails->addValue('ELT_balance', $ELT_bonus_level_two);
						$levelTwoRefferralUserDetails->save();					
						Transactions::createTransaction($levelTwoRefferralUserDetails->id, 'ELT', $ELT_bonus_level_two, 'Level-2 Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
					}
				}
				else
				{
					$unpaid_bonus_amount = User::calculateCommision($payment_amount,$conversion_rate_data['Referral-%-Level-2'][0]);
					Transactions::createEmptyTransaction($levelTwoRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
				}
			
			
				// level 3
				$levelThreeRefferralUserDetails = User::find($levelTwoRefferralUserDetails->referrer_user_id);
				if(isset($levelThreeRefferralUserDetails->id) && !empty($levelThreeRefferralUserDetails->id)) 
				{
					$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($levelThreeRefferralUserDetails->id,$conversion_rate_data['Referral-%-Level-3'][0]);			
					$newBonusPercentageLevelThree = $newBonusData["new_bonus_percent"];
					$levelThreeRefferralCommision = User::calculateCommision($payment_amount, $newBonusPercentageLevelThree);
					if (isset($levelThreeRefferralCommision) && !empty($levelThreeRefferralCommision) && ($levelThreeRefferralUserDetails->user_opt_bonus == 1 || $levelThreeRefferralUserDetails->admin_opt_bonus == 1)) 
					{
						$levelThreeRefferralUserDetails->addValue($txtBalance, $levelThreeRefferralCommision);
						$levelThreeRefferralUserDetails->save();
						$levelThreeUnpaidBonus=0;
						$unpaid_bonus_percent_level_three = $newBonusData["unpaid_bonus_percent"];
						$levelThreeUnpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent_level_three);
						Transactions::createTransaction($levelThreeRefferralUserDetails->id, $payment_units, $levelThreeRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $levelThreeUnpaidBonus);
						// Direct Bonus ELT Level-3
						$direct_bonus_ELT_level_three = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-3-Bonus-ELT']])->get();
						$ELT_bonus_level_three = $direct_bonus_ELT_level_three[0]->defined_value;
						if(isset($ELT_bonus_level_three) && $ELT_bonus_level_three > 0) 
						{
							$levelThreeRefferralUserDetails->addValue('ELT_balance', $ELT_bonus_level_three);
							$levelThreeRefferralUserDetails->save();
							Transactions::createTransaction($levelThreeRefferralUserDetails->id, 'ELT', $ELT_bonus_level_three, 'Level-3 Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
						}
					}
					else
					{
						$unpaid_bonus_amount = User::calculateCommision($payment_amount,$conversion_rate_data['Referral-%-Level-3'][0]);
						Transactions::createEmptyTransaction($levelThreeRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
					}
					
					
					// level Four Referral Commission 
					$levelFourRefferralUserDetails = User::find($levelThreeRefferralUserDetails->referrer_user_id);	
					if (isset($levelFourRefferralUserDetails->id) && !empty($levelFourRefferralUserDetails->id)) 
					{
						$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($levelFourRefferralUserDetails->id,$conversion_rate_data['Referral-%-Level-4'][0]);									
						$newBonusPercentageLevelFour = $newBonusData["new_bonus_percent"];
						$levelFourRefferralCommision = User::calculateCommision($payment_amount, $newBonusPercentageLevelFour);									
						if (isset($levelFourRefferralCommision) && !empty($levelFourRefferralCommision) && ($levelFourRefferralUserDetails->user_opt_bonus == 1 || $levelFourRefferralUserDetails->admin_opt_bonus == 1)) 
						{
							$levelFourRefferralUserDetails->addValue($txtBalance, $levelFourRefferralCommision);
							$levelFourRefferralUserDetails->save();
							$levelFourUnpaidBonus=0;
							$unpaid_bonus_percent_level_four = $newBonusData["unpaid_bonus_percent"];
							$levelFourUnpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent_level_four);								
							Transactions::createTransaction($levelFourRefferralUserDetails->id, $payment_units, $levelFourRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $levelFourUnpaidBonus);
							// Direct Bonus ELT Level-4
							$direct_bonus_ELT_level_four = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-4-Bonus-ELT']])->get();
							$ELT_bonus_level_four = $direct_bonus_ELT_level_four[0]->defined_value;
							if(isset($ELT_bonus_level_four) && $ELT_bonus_level_four > 0) 
							{
								$levelFourRefferralUserDetails->addValue('ELT_balance', $ELT_bonus_level_four);
								$levelFourRefferralUserDetails->save();
								Transactions::createTransaction($levelFourRefferralUserDetails->id, 'ELT', $ELT_bonus_level_four, 'Level-4 Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
							}
						}
						else 
						{
							$unpaid_bonus_amount = User::calculateCommision($payment_amount,$conversion_rate_data['Referral-%-Level-4'][0]);
							Transactions::createEmptyTransaction($levelFourRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
						}
					
					
						// level Five Referral Commission
						$levelFiveRefferralUserDetails = User::find($levelFourRefferralUserDetails->referrer_user_id);
						if(isset($levelFiveRefferralUserDetails->id) && !empty($levelFiveRefferralUserDetails->id)) 
						{
							$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($levelFiveRefferralUserDetails->id,$conversion_rate_data['Referral-%-Level-5'][0]);
							$newBonusPercentageLevelFive = $newBonusData["new_bonus_percent"];
							$levelFiveRefferralCommision = User::calculateCommision($payment_amount, $newBonusPercentageLevelFive);
							if (isset($levelFiveRefferralCommision) && !empty($levelFiveRefferralCommision) && ($levelFiveRefferralUserDetails->user_opt_bonus == 1 || $levelFiveRefferralUserDetails->admin_opt_bonus == 1)) 
							{
								$levelFiveRefferralUserDetails->addValue($txtBalance, $levelFiveRefferralCommision);
								$levelFiveRefferralUserDetails->save();
								$levelFiveUnpaidBonus=0;
								$unpaid_bonus_percent_level_five = $newBonusData["unpaid_bonus_percent"];
								$levelFiveUnpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent_level_five);
						
								Transactions::createTransaction($levelFiveRefferralUserDetails->id, $payment_units, $levelFiveRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $levelFiveUnpaidBonus);

								// Direct Bonus ELT Level-5
								$direct_bonus_ELT_level_five = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-5-Bonus-ELT']])->get();
								$ELT_bonus_level_five = $direct_bonus_ELT_level_five[0]->defined_value;
								
								if(isset($ELT_bonus_level_five) && $ELT_bonus_level_five > 0) 
								{
									$levelFiveRefferralUserDetails->addValue('ELT_balance', $ELT_bonus_level_five);
									$levelFiveRefferralUserDetails->save();
									Transactions::createTransaction($levelFiveRefferralUserDetails->id, 'ELT', $ELT_bonus_level_five, 'Level-5 Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
								}
							}
							else 
							{
								$unpaid_bonus_amount = User::calculateCommision($payment_amount,$conversion_rate_data['Referral-%-Level-5'][0]);
								Transactions::createEmptyTransaction($levelFiveRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
							}
						}
					}		
				}
			}
		}
	}
	*/
	
	
	public function sendChangeEmailRequest(User $user, $emailData)
    {
        $user->notify(new SendChangeEmailRequest($emailData));
    }

    /**
     *
     */
    public function SendChangeNotification(User $user, $emailData)
    {
        $user->notify(new SendChangeNotification($emailData));
    }
	
	private function getUserStats($start,$end)
	{
		return User::get_user_stats($start, $end);
	}
	
	public function sendUserActivationEmail(User $user, $token)
    {
        $user->notify(new SendNewActivationEmail($token));
    }
	
	public function SendWhiteListWelcomeEmailFunc(User $user, $token)
    {
        $user->notify(new SendWhiteListWelcomeEmail($token));
    }
	
	
}
