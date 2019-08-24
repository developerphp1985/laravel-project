<?php
namespace App\Models;
use App\Helpers\LoggerHelper;
use App\Helpers\CommonHelper;
use DB;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;
use Lab404\Impersonate\Services\ImpersonateManager;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoleAndPermission;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	 
    protected $fillable = 
	[
        'card_requested_on','loan_requested_on','notUScitizen','read_wps','tc','loan_amount_requested','loan_term','security_type','reserve_card','email','user_name','password','first_name','last_name','referrer_key','referrer_user_id', 'registration_ip','address1','address2','postal_code','city','country_code','mobile_number', 'language','BTC_wallet_address','ETH_wallet_address','BTC_balance','ETH_balance','ELT_balance','EUR_balance','last_update_ip','updated_at','status','OTP','telegram_id','friend_telegram_id', 'ERC20_wallet_address','telegram_referral_email'
	];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    public function Transactions() {
        return $this->hasMany('App\Models\Transactions', 'user_id');
    }

    public function Country() {
        return $this->belongsTo('App\Models\Country', 'country_code');
    }

    /**
     * Return true or false if the user can impersonate an other user.
     *
     * @param   void
     * @return  bool
     */
    public function canImpersonate()
    {
        return true;
    }

    /**
     * Return true or false if the user can be impersonate.
     *
     * @param   void
     * @return  bool
     */
    public function canBeImpersonated()
    {
        return true;
    }

    /**
     * Impersonate the given user.
     *
     * @param   Model $user
     * @return  bool
     */
    public function impersonate(Model $user)
    {
        return app(ImpersonateManager::class)->take($this, $user);
    }

    /**
     * Check if the current user is impersonated.
     *
     * @param   void
     * @return  bool
     */
    public function isImpersonated()
    {
        return app(ImpersonateManager::class)->isImpersonating();
    }

    /**
     * Leave the current impersonation.
     *
     * @param   void
     * @return  bool
     */
    public function leaveImpersonation()
    {
        if ($this->isImpersonated())
        {
            return app(ImpersonateManager::class)->leave();
        }
    }

    /**
     * The method to add provided value to existing balance.
     *
     * @var key - column name to be updated 
     * @var value - value to be subtracted
     */
    public function addValue($key, $value)
    {        
        if( (isset($value) && ($value>0)) && (abs($this->attributes[$key]) == $this->attributes[$key]) ) { 

            $this->attributes[$key] = $value + $this->attributes[$key];
            //
            $record = [
                'message'   => 'BalanceUpdate username:'.$this->attributes['email'],
                'level'     => 'INFO',
                'context'   => 'add value',
               
                'extra'     => [
                    $this->attributes[$key]   => $key
                ]
            ];
            LoggerHelper::writeDB($record);
            
            return $this->attributes[$key];
        } else {
            return "Negative values are not allowed";
        }        
    }

    /**
     * The method to subtract provided value to existing balance.
     *
     * @var key - column name to be updated 
     * @var value - value to be subtracted
     */
    public function subtractValue($key, $value)
    {
        if( (isset($value) && ($value>0)) && (abs($this->attributes[$key]) == $this->attributes[$key]) ) { 

            $finalBalance = $this->attributes[$key] - $value;

            if(abs($finalBalance) == $finalBalance) {
               $this->attributes[$key] = $finalBalance;
                //
                $record = [
                    'message'   => 'BalanceUpdate username:'.$this->attributes['email'],
                    'level'     => 'INFO',
                    'context'   => 'subtract value',
                    'extra'     => [
                        $this->attributes[$key]   => $key
                    ]
                ];
                LoggerHelper::writeDB($record);
                return $this->attributes[$key];
            } else {
               return "Not Enough Funds On Account";
            }           

        } else {
             return "Negative values are not allowed";
        }  
    }

    /**
     * The method to subtract provided value to existing balance.
     *
     * @var amount - total amount 
     * @var commission - commission to be subtracted
     */
    public static function calculateCommision($amount, $commision)
    {
        if( isset($amount) && $amount>0 ) { 
            $finalAmount = $amount*($commision/100);
            return $finalAmount;
        } else {
            return "Negative values are not allowed";
        }  
    }		
	
	public function update_user_wallet_history($data)	
	{				
		return self::insertIntoTable('user_wallet_history',['user_id' => $data['user_id'],'amount' =>$data['amount'],'date'=>date("Y-m-d H:i:s"),'type'=>$data['type'],'naration'=>$data['naration'],'transaction_type'=>$data['transaction_type'],'income_type'=>$data['income_type']]);
	}
	
	public function check_username($username)	
	{
		$response = DB::table('users')->select('id','email','user_name','first_name','last_name')->where('user_name',$username)->orWhere('email',$username)->get();
		if(isset($response[0])){			 
			return $response[0];		 
		}		 
		return '';	
	}

	public static function get_user_stats($start='', $end='')
	{
		$userStatsData = array();
		$today_date = date("Y-m-d");				
		$yesterday_date = date("Y-m-d",strtotime("-1 days"));
		$thisMonth = (int)date("m");		
		$lastMonth = (int)date('m', strtotime('-1 month'));
		$thisYear = date("Y");
		$lastYear = date("Y")-1;		
		$current_week_date_range = CommonHelper::current_week_date_range();
		$this_week_sd = $current_week_date_range['start_week'];
		$this_week_ed = $current_week_date_range['end_week'];
		$last_week_date_range = CommonHelper::last_week_date_range();
		$last_week_sd = $last_week_date_range['start_week'];
		$last_week_ed = $last_week_date_range['end_week'];
		$userStatsData['user_total'] = 0;
		$userStatsData['user_today'] = 0;
		$userStatsData['user_yesterday'] = 0;
		$userStatsData['user_this_week'] = 0;
		$userStatsData['user_last_week'] = 0;
		$userStatsData['user_this_month'] = 0;
		$userStatsData['user_last_month'] = 0;
		$userStatsData['user_this_year'] = 0;
		$userStatsData['user_last_year'] = 0;
		$userStatsData['total_active_users'] = 0;
		if(!empty($start) && !empty($end))
		{
			$start_date_array = explode("/",$start);
			$end_date_array = explode("/",$end);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			$start_datetime = $start_date.' 00:00:00';
			$end_datetime = $end_date.' 23:59:59';
			$user_total = DB::select( DB::raw( "SELECT count(*) AS user_total FROM users WHERE role=2 AND created_at>='".$start_datetime."' AND created_at<='".$end_datetime."'" ) );
			$userStatsData['user_total'] = $user_total[0]->user_total;
			$total_active_users = DB::select( DB::raw("SELECT count(users.id) AS active_users FROM users LEFT JOIN role_user ON role_user.user_id = users.id WHERE users.role = 2 AND users.status = 1 AND role_user.role_id = 2 AND users.created_at>='".$start_datetime."' AND users.created_at<='".$end_datetime."'") );
			$userStatsData['total_active_users'] = $total_active_users[0]->active_users;
		}
		else
		{
			$user_total = DB::select( DB::raw("SELECT count(*) AS user_total FROM users WHERE role=2") );
			$userStatsData['user_total'] = $user_total[0]->user_total;
			
			$total_active_users = DB::select( DB::raw("SELECT count(users.id) AS active_users FROM users LEFT JOIN role_user ON role_user.user_id = users.id WHERE users.role = 2 AND users.status = 1 AND role_user.role_id = 2") );
			$userStatsData['total_active_users'] = $total_active_users[0]->active_users;
			
			$user_today = DB::select( DB::raw("SELECT count(*) AS user_today FROM users WHERE role=2 AND DATE(created_at)='$today_date'") );
			$userStatsData['user_today'] = $user_today[0]->user_today;
			
			$user_yesterday = DB::select( DB::raw("SELECT count(*) AS user_yesterday FROM users WHERE role=2 AND DATE(created_at)='$yesterday_date'") );
			$userStatsData['user_yesterday'] = $user_yesterday[0]->user_yesterday;
			
			$user_this_week = DB::select( DB::raw("SELECT count(*) AS user_this_week FROM users WHERE role=2 AND created_at>='$this_week_sd' AND created_at<='$this_week_ed'") );
			$userStatsData['user_this_week'] = $user_this_week[0]->user_this_week;

			$user_last_week = DB::select( DB::raw("SELECT count(*) AS user_last_week FROM users WHERE role=2 AND created_at>='$last_week_sd' AND created_at<='$last_week_ed'") );
			$userStatsData['user_last_week'] = $user_last_week[0]->user_last_week;
			
			$user_this_month = DB::select( DB::raw("SELECT count(*) AS user_this_month FROM users WHERE role=2 AND MONTH(created_at)='".$thisMonth."' AND YEAR(created_at)='$thisYear'") );
			$userStatsData['user_this_month'] = $user_this_month[0]->user_this_month;
			
			$user_last_month = DB::select( DB::raw("SELECT count(*) AS user_last_month FROM users WHERE role=2 AND MONTH(created_at)='".$lastMonth."' AND YEAR(created_at)='$thisYear'") );
			$userStatsData['user_last_month'] = $user_last_month[0]->user_last_month;
			
			$user_this_year = DB::select( DB::raw("SELECT count(*) AS user_this_year FROM users WHERE role=2 AND YEAR(created_at)='$thisYear'") );
			$userStatsData['user_this_year'] = $user_this_year[0]->user_this_year;
			
			$user_last_year = DB::select( DB::raw("SELECT count(*) AS user_last_year FROM users WHERE role=2 AND YEAR(created_at)='$lastYear'") );
			$userStatsData['user_last_year'] = $user_last_year[0]->user_last_year;
		
		}
		
		return $userStatsData;	
	}

	public static function check_whitelist_email($email)
	{
		$response = array();
		if(!empty($email))
		{
			return DB::table('whitelist_users')->where("email",$email)->count();
		}
		return 1;
	}
	
	public static function get_whitelist_user_by_token($token)
	{
		$response = array();
		if(!empty($token))
		{
			$response = DB::table('whitelist_users')->select('id')->where('token',$token)->first();
			return $response;
		}
		return array();
	}
	
	public static function get_whitelist_user_by_id($id)
	{
		$response = array();
		if(!empty($id))
		{
			$response = DB::table('whitelist_users')->select('*')->where('id',$id)->first();
			return $response;
		}
		return array();
	}
	
	public static function add_whitelist_user($white_list_data)
	{
		return self::insertIntoTable('whitelist_users',$white_list_data);
	}
	
	public static function update_whitelist_user($id,$data)
	{
		return DB::table('whitelist_users')->where('id',$id)->update($data);
	}
	
	public static function get_datatables_users($column_order, $postarray, $is_count = 0)
	{
		$query = User::query();	
		
		$query->leftJoin('users as usr', 'users.referrer_user_id', '=', 'usr.id');
		
		$query->leftJoin('countries', 'countries.code', '=', 'users.country_code');
		
		$query->select('users.id','users.email','users.user_name','users.first_name','users.last_name','users.referrer_user_id','users.BTC_balance','users.ETH_balance','users.ELT_balance','users.EUR_balance','users.LTC_balance','users.BCH_balance','users.XRP_balance','users.registration_ip','users.city','users.DASH_balance','users.created_at','users.referrer_count','usr.user_name as referrelFirstName','usr.user_name as referrelLastName','usr.email as referrelEmail','usr.id as referrelId','countries.name AS country_name');		
		
		$query->where('users.role', '2');
		
		if(!CommonHelper::isSuperAdmin()){
			$query->where('users.make_user_invisible', '0');
			$query->where('usr.make_user_invisible', '0');
		}
		
		if(isset($_POST['ajaxadminreferrals']) && $_POST['ajaxadminreferrals'] == 1){
			$query->where('users.referrer_count', '>', 0);
		}
		
		if(isset($postarray['ajaxmarkedusers']) && $postarray['ajaxmarkedusers'] == 1){
			
			if(isset($postarray['selectedConfigSetting'])){
				
				switch($postarray['selectedConfigSetting']) 
				{
					case 'exclude_saleslist':
						$query->where('users.exclude_saleslist', 1);
						break;
					case 'make_user_invisible':
						$query->where('users.make_user_invisible', 1);
						break;
					case 'admin_opt_bonus':
						$query->where('users.admin_opt_bonus', 1);
						break;
					case 'downline':
						$query->where('users.downline', 1);
						break;
					case 'exclude_toplist':
						$query->where('users.exclude_toplist', 1);
						break;
					case 'exclude_salestoplist':
						$query->where('users.exclude_salestoplist', 1);
						break;
					case 'exclude_payment_transaction':
						$query->where('users.exclude_payment_transaction', 1);
						break;
				}
			}			
		}
		
		if(isset($_POST['ajaxfinance']) && $_POST['ajaxfinance'] == 1){
			$query->where('users.'.$_POST['selectedCurrency'].'_balance','>',0);
		}
		
		if(isset($postarray['search_text']) && !empty($postarray['search_text']))
		{
			$search_text = $postarray['search_text'];
			$query->where(function($query) use ($search_text, $postarray){
				
				$is_filter_checkbox = 0;
				
				if(isset($postarray['filter_in_fname']) && $postarray['filter_in_fname'] == 1){
					$is_filter_checkbox = 1;					
					if(strpos($search_text, ' ') !== false){
						$query->orWhere('users.first_name', '=', "$search_text");
					}
					else{
						$query->orWhere('users.first_name', 'LIKE', "$search_text%");
					}
				}
				
				if(isset($postarray['filter_in_lname']) && $postarray['filter_in_lname'] == 1){
					$is_filter_checkbox = 1;
					if(strpos($search_text, ' ') !== false){
						$query->orWhere('users.last_name', '=', "$search_text");
					}
					else{
						$query->orWhere('users.last_name', 'LIKE', "$search_text%");
					}
				}
				
				if(isset($postarray['filter_in_email']) && $postarray['filter_in_email'] == 1){
					$is_filter_checkbox = 1;
					$query->orWhere('users.email', 'LIKE', "%$search_text%");
				}
				
				if(isset($postarray['filter_in_referby']) && $postarray['filter_in_referby'] == 1){
					$is_filter_checkbox = 1;
					$query->orWhere('usr.email', 'LIKE', "%$search_text%");
				}
				
				if(isset($postarray['filter_in_city']) && $postarray['filter_in_city'] == 1){
					$is_filter_checkbox = 1;
					$query->orWhere('users.city', 'LIKE', "%$search_text%");
				}
				
				if(isset($postarray['filter_in_country']) && $postarray['filter_in_country'] == 1){
					$is_filter_checkbox = 1;
					$query->orWhere('countries.name', 'LIKE', "%$search_text%");
				}
				
				if($is_filter_checkbox == 0){
					$query->where(function($query) use ($search_text){
						$query->where('users.email', 'LIKE', "%$search_text%")
						->orWhere('users.first_name', 'LIKE', "%$search_text%")
						->orWhere('users.last_name', 'LIKE', "%$search_text%")
						->orWhere('users.city', 'LIKE', "%$search_text%")
						->orWhere('countries.name', 'LIKE', "%$search_text%")
						->orWhere('usr.email', 'LIKE', "%$search_text%");
					});
				}
			});
		}
		if(!empty($_POST['start_date']) && !empty($_POST['end_date'])){
			$start_date_array = explode("/",$_POST['start_date']);
			$end_date_array = explode("/",$_POST['end_date']);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';
			$query->whereBetween('users.created_at', [$start_date, $end_date]);			
		}	
		
		if($is_count == 0){
            if (isset($postarray['start'])) {
                $query->offset($postarray['start']);
            }
            if (isset($postarray['length'])) {
                $query->limit($postarray['length']);
            }
        }
		
		if(isset($postarray['order'])){
			$direction = 'desc';
			if($postarray['order']['0']['dir'] == 'asc'){
				$direction = 'desc';
			}
			elseif($postarray['order']['0']['dir'] == 'desc'){
				$direction = 'asc';
			}
			$query->orderBy($column_order[$postarray['order']['0']['column']], $direction);
		}
		else{
			$query->orderBy('users.created_at', 'asc');
		}
		
		//echo $query->toSql();die;
		
		if($is_count == 1){
			return $query->count();
		}
		else{
			return $query->get();
		}
	}
	
	public static function get_datatables_salesrevenue($column_order, $postarray, $is_count = 0)
	{
		$query = User::query();
		$query->selectRaw
		(
		    'users.id,
			users.email,
			users.user_name,
			users.first_name,
			users.last_name,
			users.status,
			users.created_at,
			users.referrer_count,
			countries.name AS country_name,
			SUM(transactions.value * phases.token_price) as euro_worth'
		);
		$query->leftJoin('transactions', 'transactions.user_id', '=', 'users.id');
		$query->leftJoin('phases', 'phases.id', '=', 'transactions.phase_id');
		$query->leftJoin('countries', 'countries.code', '=', 'users.country_code');
		$query->where('users.role', '2');
		
		if(!CommonHelper::isSuperAdmin()){
			$query->where('users.make_user_invisible', '0');
		}
		
		$query->where('transactions.status', '1');
		$query->where('transactions.ledger', '=','ELT');
		$query->where('transactions.type_name','!=','bonus');
		$query->where('transactions.type','1');
		
		if(isset($postarray['search_text']) && !empty($postarray['search_text'])){
			$search_text = $postarray['search_text'];
			$search_text1 =	explode(' ',$search_text);
			$search_text3 = $search_text1[0];	
			if(isset($search_text1[1])){
				$search_text2 = $search_text1[1];
			} 
			else{
				$search_text2 = $search_text1[0];
			}
			$query->where(function($query) use ($search_text3, $search_text,$search_text2){
				$query->where('users.email', 'LIKE', "%$search_text%")
				->orWhere('users.first_name', 'LIKE', "%$search_text3%")
				->orWhere('users.last_name', 'LIKE', "%$search_text2%")
				->orWhere('users.telegram_id', 'LIKE', "%$search_text%");
			});
		}		
		$query->groupBy('transactions.user_id');
		if(!empty($_POST['start_date']) && !empty($_POST['end_date'])){
			$start_date_array = explode("/",$_POST['start_date']);
			$end_date_array = explode("/",$_POST['end_date']);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';
			$query->whereBetween('users.created_at', [$start_date, $end_date]);			
		}	
		if($is_count == 0){
            if (isset($postarray['start'])) {
                $query->offset($postarray['start']);
            }
            if (isset($postarray['length'])) {
                $query->limit($postarray['length']);
            }
        }
		if(isset($postarray['order'])){
			$direction = 'desc';
			if($postarray['order']['0']['dir'] == 'asc'){
				$direction = 'desc';
			}
			elseif($postarray['order']['0']['dir'] == 'desc'){
				$direction = 'asc';
			}
			$query->orderBy($column_order[$postarray['order']['0']['column']], $direction);
		}
		else{
			$query->orderBy('users.created_at', 'asc');
		}		
		if($is_count == 1){
			return $query->count();
		}
		else{
			return $query->get();
		}		
	}

	
	public static function get_datatables_demographics($column_order, $postarray, $is_count = 0)
	{
		$start = 0;
		$length = 25;
		if($is_count == 0){			
            if (isset($postarray['start'])) {
                $start = $postarray['start'];
            }
            if (isset($postarray['length'])) {
               $length = $postarray['length'];
            }
			$limitSql = "LIMIT $start, $length";
        }
		else{
			$limitSql = "";
		}		
		if(isset($postarray['order'])){
			if($postarray['order']['0']['dir'] == 'asc'){
				$sortBy = 'desc';
			}
			else if($postarray['order']['0']['dir'] == 'desc'){
				$sortBy = 'asc';
			}
			$orderBy = $column_order[$postarray['order']['0']['column']];
		}
		else{
			$orderBy = 'total_country_user';
			$sortBy = 'desc';
		}
		
		$AndQuery = '';
		if(isset($_POST['searchByCountry']) && !empty($_POST['searchByCountry'])){
			$AndQuery = " AND users.country_code='".$_POST['searchByCountry']."'";
		}		
		$Sql = "
		SELECT users.country_code, countries.name AS country_name, count(users.id) as total_country_user 
		FROM users 
		LEFT JOIN countries ON countries.code = users.country_code
		WHERE users.role=2 $AndQuery
		GROUP BY users.country_code
		ORDER BY $orderBy $sortBy
		$limitSql";		
		$result = DB::select(DB::raw( $Sql ));
		return $result;
		
	}
	
	public static function get_datatables_loan_list($column_order, $postarray, $is_count=0)
	{
		$query = User::query();	
		$query->select
		(
			'users.id',
			'users.email',
			'users.user_name',
			'users.first_name',
			'users.last_name',			
			'users.loan_amount_requested',
			'users.loan_term',
			'users.security_type',
			'users.loan_requested_on'		
		);		
		$query->where('users.role', '2');
		$query->where('users.loan_amount_requested','>','0');
		
		if(!CommonHelper::isSuperAdmin()){
			$query->where('users.make_user_invisible', '0');
		}
		
		if(isset($postarray['search_text']) && !empty($postarray['search_text']))
		{
			$search_text = $postarray['search_text'];
			$search_text1 =	explode(' ',$search_text);
			$search_text3 = $search_text1[0];
	
			if(isset($search_text1[1])){
				$search_text2 = $search_text1[1];
			} else {
				$search_text2 = $search_text1[0];
			}
			$query->where(function($query) use ($search_text3, $search_text,$search_text2)
			{
				$query->where('users.email', 'LIKE', "%$search_text%")
				->orWhere('users.first_name', 'LIKE', "%$search_text%");
			});
		}
		
		if(isset($postarray['loan_term']) && $postarray['loan_term']!=-1)
		{
			$loan_term = $postarray['loan_term'];
			$query->where(function($query) use ($loan_term)
			{
				$query->where('users.loan_term', '=', "$loan_term");
			});
		}
		
		if(isset($postarray['security_type']) && $postarray['security_type']!=-1)
		{
			$security_type = $postarray['security_type'];
			$query->where(function($query) use ($security_type)
			{
				$query->where('users.security_type', '=', "$security_type");
			});
		}
		
		if(!empty($_POST['start_date']) && !empty($_POST['end_date']))
		{
			$start_date_array = explode("/",$_POST['start_date']);
			$end_date_array = explode("/",$_POST['end_date']);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';
			$query->where('users.loan_requested_on', '>=', $start_date);
			$query->where('users.loan_requested_on', '<=', $end_date);
		}
	
		if(isset($postarray['start'])){
			$query->offset($postarray['start']);
		}
		
		if(isset($postarray['length'])){
			$query->limit($postarray['length']);
		}
		
		if(isset($postarray['order'])){
			$direction = 'desc';
			if($postarray['order']['0']['dir'] == 'asc'){
				$direction = 'desc';
			}
			else if($postarray['order']['0']['dir'] == 'desc'){
				$direction = 'asc';
			}
			$query->orderBy($column_order[$postarray['order']['0']['column']], $direction);
		}
		else{
			$query->orderBy('users.loan_requested_on', 'desc');
		}
		
		if($is_count == 1){
			return $query->count();
		}
		else{
			return $query->get();
		}	
	}
	
	
	public static function get_datatables_card_list($column_order, $postarray, $is_count=0)
	{
		$query = User::query();	
		$query->leftJoin('lendo_cards', 'lendo_cards.id', '=', 'users.reserve_card');
		$query->select
		(
			'users.id',
			'users.email',
			'users.first_name',	
			'users.last_name',	
			'users.reserve_card',
			'users.card_requested_on',
			'lendo_cards.name',
			'lendo_cards.issue_fee',
			'lendo_cards.annual_fee',
			'lendo_cards.credit_limit'					
		);
		$query->where('users.role', '2');
		$query->where('users.reserve_card','>','0');
		
		if(!CommonHelper::isSuperAdmin()){
			$query->where('users.make_user_invisible', '0');
		}
		
		if(isset($postarray['reserve_card']) && $postarray['reserve_card']!=-1){
			$reserve_card = $postarray['reserve_card'];
			$query->where(function($query) use ($reserve_card)
			{
				$query->where('users.reserve_card', '=', "$reserve_card");
			});
		}
		
		if(isset($postarray['search_text']) && !empty($postarray['search_text'])){
			$search_text = $postarray['search_text'];
			$search_text1 =	explode(' ',$search_text);
			$search_text3 = $search_text1[0];	
			if(isset($search_text1[1])){
				$search_text2 = $search_text1[1];
			} else {
				$search_text2 = $search_text1[0];
			}
			$query->where(function($query) use ($search_text3, $search_text,$search_text2){
				$query->where('users.email', 'LIKE', "%$search_text%")
				->orWhere('users.first_name', 'LIKE', "%$search_text%");
			});
		}		
		
		if(!empty($_POST['start_date']) && !empty($_POST['end_date'])){
			$start_date_array = explode("/",$_POST['start_date']);
			$end_date_array = explode("/",$_POST['end_date']);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';
			$query->where('users.card_requested_on', '>=', $start_date);
			$query->where('users.card_requested_on', '<=', $end_date);
		}
	
		if(isset($postarray['start'])){
			$query->offset($postarray['start']);
		}
		
		if(isset($postarray['length'])){
			$query->limit($postarray['length']);
		}
		
		if(isset($postarray['order'])){
			$direction = 'desc';
			if($postarray['order']['0']['dir'] == 'asc'){
				$direction = 'desc';
			}
			else if($postarray['order']['0']['dir'] == 'desc'){
				$direction = 'asc';
			}
			$query->orderBy($column_order[$postarray['order']['0']['column']], $direction);
		}
		else{
			$query->orderBy('users.card_requested_on', 'desc');
		}
		
		if($is_count == 1){
			return $query->count();
		}
		else{
			return $query->get();
		}
	}
	
	public static function get_datatables_elt_token($column_order, $postarray, $is_count = 0)
	{
		$query = User::query();		
		$query->leftJoin('users as usr', 'users.referrer_user_id', '=', 'usr.id');
		$query->leftJoin('countries', 'countries.code', '=', 'users.country_code');		
		$query->select
		(
			'users.id',
			'users.email',
			'users.user_name',
			'users.first_name',
			'users.last_name',
			'users.referrer_user_id',
			'users.ELT_balance',
			'users.created_at',
			'users.referrer_count',
			'usr.user_name as referrelFirstName',
			'usr.user_name as referrelLastName',
			'usr.email as referrelEmail',
			'usr.id as referrelId',
			'users.Custom_ETH_Address',
			'countries.name AS country_name'
		);		
		$query->where('users.role', '2');
		
		if(!CommonHelper::isSuperAdmin()){
			$query->where('users.make_user_invisible', '0');
		}
		
		$Check_ELT_token = 0;		
		$token_send=0;		
		$query->where(function($query) use ($Check_ELT_token, $token_send)
		{
			$query->where('users.ELT_balance', '>', "$Check_ELT_token");
		});			
		
		if(isset($postarray['search_text']) && !empty($postarray['search_text'])){
			$search_text = $postarray['search_text'];
			$search_text1 =	explode(' ',$search_text);
			$search_text3 = $search_text1[0];
			if(isset($search_text1[1])){
				$search_text2 = $search_text1[1];
			}
			else{
				$search_text2 = $search_text1[0];
			}			
			$query->where(function($query) use ($search_text3, $search_text,$search_text2){
				$query->where('users.email', 'LIKE', "%$search_text%")
				->orWhere('users.user_name', 'LIKE', "%$search_text3%")
				->orWhere('users.user_name', 'LIKE', "%$search_text2%")
				->orWhere('users.Custom_ETH_Address', 'LIKE', "%$search_text%")
				->orWhere('usr.email', 'LIKE', "%$search_text%");
			});
		}
		
		if(!empty($_POST['start_date']) && !empty($_POST['end_date'])){
			$start_date_array = explode("/",$_POST['start_date']);
			$end_date_array = explode("/",$_POST['end_date']);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];			
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';			
			$query->where('users.created_at', '>=', $start_date);
			$query->where('users.created_at', '<=', $end_date);
		}	
		
		if(isset($postarray['start'])){
			$query->offset($postarray['start']);
		}
		
		if(isset($postarray['length'])){
			$query->limit($postarray['length']);
		}
		if(isset($postarray['order'])) {
			$direction = 'desc';
			if($postarray['order']['0']['dir'] == 'asc')
			{
				$direction = 'desc';
			}
			else if($postarray['order']['0']['dir'] == 'desc')
			{
				$direction = 'asc';
			}
			$query->orderBy($column_order[$postarray['order']['0']['column']], $direction);
		}
		else{
			$query->orderBy('users.created_at', 'asc');
		}
		
		if($is_count == 1){
			return $query->count();
		}
		else{
			return $query->get();
		}
	}
	
	public static function get_referral_count($user_id)
	{		
		$query = User::query();	
		$query->select('id');		
		$query->where('users.role', '2');
		$query->where('users.referrer_user_id', $user_id);	
		return $query->count();
	}

	public static function get_top_referrals_in_months($Month="06", $Year='2018')
	{
		$AndInVisibleSql = '';
		if(CommonHelper::isAdminLogin()){
			//$AndInVisibleSql = " AND users.make_user_invisible = 0";
		}		
		$Sql = "
		SELECT 
		users2.id, COUNT(users1.id) as this_month, users1.referrer_user_id, users2.email, 
		users2.exclude_toplist, users2.first_name, users2.last_name, users2.display_name
		FROM users as users1
		LEFT JOIN users as users2 ON users2.id = users1.referrer_user_id
		WHERE MONTH(users1.created_at)='".$Month."' AND YEAR(users1.created_at)='".$Year."' AND users2.exclude_toplist='0' $AndInVisibleSql
		GROUP BY users1.referrer_user_id 
		ORDER BY this_month DESC";
		$leader_list = DB::select(DB::raw( $Sql ));
		return $leader_list;
	}
	
	public static function get_user_referrals_in_months($loginid, $Month='06', $Year='2018')
	{
		$AndInVisibleSql = '';
		if(CommonHelper::isAdminLogin()){
			//$AndInVisibleSql = " AND users.make_user_invisible = 0";
		}
		$Sql = "
		SELECT COUNT(users1.id) as this_month
		FROM users as users1 
		LEFT JOIN users as users2 ON users2.id = users1.referrer_user_id
		WHERE MONTH(users1.created_at)='".$Month."' AND YEAR(users1.created_at)='".$Year."' AND users2.exclude_toplist='0' AND users1.referrer_user_id ='".$loginid."' $AndInVisibleSql
		GROUP BY users1.referrer_user_id 
		ORDER BY this_month DESC
		LIMIT 1;";
		$result = DB::select(DB::raw( $Sql ));
		if(isset($result[0]->this_month) && $result[0]->this_month > 0){
			return $result[0]->this_month;
		}
		return 0;	
	}

	public static function get_whitelistuser_datatables($column_order, $postarray)
	{
		$query = WhiteList::query();		
		$query->select('whitelist_users.*');
		if(isset($postarray['search_text']) && !empty($postarray['search_text']))
		{
			$search_text = $postarray['search_text'];
			$query->where(function($query) use ($search_text)
			{
				$query->where('whitelist_users.email', 'LIKE', "%$search_text%")
					->orWhere('whitelist_users.name', 'LIKE', "%$search_text%");
			});
		}		
		if(!empty($_POST['start_date']) && !empty($_POST['end_date']))
		{
			$start_date_array = explode("/",$_POST['start_date']);
			$end_date_array = explode("/",$_POST['end_date']);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';
			$query->where('whitelist_users.created_at', '>=', $start_date);
			$query->where('whitelist_users.created_at', '<=', $end_date);
		}
	
		if(isset($postarray['start'])){
			$query->offset($postarray['start']);
		}
		if(isset($postarray['length'])){
			$query->limit($postarray['length']);
		}
		if(isset($postarray['order'])){
			$direction = 'desc';
			if($postarray['order']['0']['dir'] == 'asc'){
				$direction = 'desc';
			}
			else if($postarray['order']['0']['dir'] == 'desc'){
				$direction = 'asc';
			}
			$query->orderBy($column_order[$postarray['order']['0']['column']], $direction);
		}
		else{
			$query->orderBy('whitelist_users.created_at', 'asc');
		}
		$result = $query->get();			
		return $result;		
	}
	
	public static function total_telegram_singup()
	{
		return DB::table('users')->where('telegram_id','!=',NULL)->count();
	}

	public static function get_file_data($id,$type)
	{
		return DB::table('file_attachments')->where('user_id','=',$id)->where('type','=',$type)->get()->first();
	}
	
	public static function check_bank_info($user_id)	
	{	
		$bank_info = DB::table('users')
		->select('IBAN_number','Swift_code','Beneficiary_name','Bank_name','Bank_address')
		->where('id',$user_id)->first();					
		if(isset($bank_info->IBAN_number) && isset($bank_info->Swift_code) && isset($bank_info->Beneficiary_name) && isset($bank_info->Bank_address))
		{
			return 1;
		}
		else{
			return 0;
		}
	}

	public static function get_languages()	
	{	
		$response = DB::table('language')->select('*')->where('is_active','1')->get();
		return $response;
	}

	
	public static function create_app_token($user_id)	
	{	
		$time = time();		
		$app_token_created_on = date('Y-m-d H:i:s',$time);
		$app_token_expire_on = date('Y-m-d H:i:s',$time + config('constants.idle_timeout') ); // expire after 24 hrs
		$token = md5(time().uniqid());		
		$updateData = ['app_token'=>$token, 'app_token_created_on'=>$app_token_created_on, 'app_token_expire_on'=>$app_token_expire_on] ;
		if(DB::table('users')->where('id',$user_id)->update($updateData))
		{
			return $token;
		}
		else
		{
			return '';
		}
	}
	
	public static function create_expire_token($user_id, $token)	
	{	
		$insertData = ['token'=>$token,'user_id'=>$user_id,'ping_status' => 0,'date_time'=>date("Y-m-d H:i:s")];
		self::insertIntoTable('app_token_expire',$insertData);
	}
	
	public static function insertIntoTable($table,$insertData)
	{
		return DB::table($table)->insert($insertData);
	}
	
	public static function fetch_app_token($userid)	
	{					
		$response = DB::table('users')->select('app_token')->where('id',$userid)->first();
		if(isset($response->app_token))
		{
			return $response->app_token;
		}
		return '';
	}
	
	public static function set_app_pin($newpin,$userid)		
	{		
		$updateData = ['app_pin'=> $newpin ];		
		return DB::table('users')->where('id',$userid)->update($updateData);	
	}
	
	public static function delete_app_token($token)	
	{			
		$updateData = ['app_token'=>NULL, 'app_token_created_on'=>NULL, 'app_token_expire_on'=>NULL];
		return DB::table('users')->where('app_token',$token)->update($updateData);
	}
	
	public static function check_app_otp($token, $otp)		
	{
		$response = DB::table('users')->select('id')->where('app_token',$token)->where('app_otp',$otp)->first();
				
		if(isset($response->id))
		{
			return 1;
		}	
		else
		{
			return 0;
		}
	}
	
	public static function update_user_by_id($updateData,$userid)		
	{			
		return DB::table('users')->where('id',$userid)->update($updateData);	
	}
	
	public static function update_user_by_token($updateData,$token)		
	{			
		return DB::table('users')->where('app_token',$token)->update($updateData);	
	}
	
	public static function update_last_activity($token)	
	{
		$updateData = ['app_token_expire_on'=> date('Y-m-d H:i:s',time() + config('constants.idle_timeout') ) ];
		return DB::table('users')->where('app_token',$token)->update($updateData);
	}

	public static function is_valid_app_session($token)	
	{		
		$time = time();
		$current_time = date('Y-m-d H:i:s',$time);
		$response = DB::table('users')->select('app_token_expire_on')->where('app_token',$token)->first();		
		if($response)
		{
			if( !is_null($response->app_token_expire_on) && strtotime($current_time) <= strtotime($response->app_token_expire_on) )
			{
				return true;
			}
		}
		return false;
	}
	
	
	public static function get_user_by_token($token)		
	{		
		$response = DB::table('users')->select('users.*')->where('app_token',$token)->first();						
		if($response)		
		{			
			return $response;		
		}		
		return array();	
	}	
	
	public static function get_user_by_userid($userid)		
	{		
		$response = DB::table('users')->select('users.*')->where('id',$userid)->first();						
		if($response)		
		{			
			return $response;		
		}		
		return array();	
	}
	
	
	public static function get_user_by_address($address)		
	{		
		$response = DB::table('users')->select('users.*')->where('Custom_ETH_Address',$address)->first();						
		if($response)		
		{			
			return $response;		
		}		
		return 0;	
	}
	
	public static function is_valid_admin_session($adminid, $token)	
	{	
		return true;		
		$adminRow = DB::table('admin_token')->select('*')->where('adminid',$adminid)->first();
		if(isset($adminRow->id) && $token == $adminRow->token)
		{
			return true;
		}
		return false;
	}

	
	public static function bc_transactions_by_address($address,$offset=0,$length=100)
	{				
		$response = DB::table('bc_transations')
					->skip($offset)
					->take($length)
                    ->where('from_address', '=', $address)
                    ->orWhere('to_address', '=', $address)
					->orderBy('time_stamp','desc')
                    ->get();
					
		
		if($response)		
		{			
			return $response;		
		}		
		return array();
	}
	
	
	public static function bc_elt_eth_transactions_by_address($address,$offset=0,$length=100,$type='ELT')
	{				
		$ignore_address = '0x0000000000000000000000000000000000000000';
		
		if($type=='ELT')
		{
			$response = DB::select(DB::raw("select * from bc_transations where type='".$type."' AND (from_address = '".$address."' OR to_address = '".$address."') AND (from_address != '".$ignore_address."' AND to_address != '".$ignore_address."') ORDER BY time_stamp DESC LIMIT $offset, $length"));
		}
		else
		{
			$response = DB::select(DB::raw("select * from bc_transations where type='".$type."' AND (from_address = '".$address."' OR to_address = '".$address."') ORDER BY time_stamp DESC LIMIT $offset, $length"));
		}
		
		if($response)		
		{			
			return $response;		
		}		
		return array();
	}
	
	public static function matchWalletPin($wallet_pin_encoded,$userid)		
	{		
		$row = DB::table('users')->select('id')->where('app_pin',$wallet_pin_encoded)->where('id',$userid)->first();
		if(isset($row->id))
		{
			return 1;
		}
		return 0;
	}
	
	public static function getWalletPin($userid)		
	{		
		$row = DB::table('users')->select('app_pin')->where('id',$userid)->first();
		if(isset($row->app_pin))
		{
			return $row->app_pin;
		}
		return 0;
	}
	
	public static function table_row_count($table='users')
	{
		return DB::table($table)->count();
	}
	
	public static function table_elt_count($table='users')
	{
		return DB::table($table)->where("type","ELT")->count();
	}
	
	public static function get_nonconfirm_balance($address, $type='ELT')
	{
		$total_amount = 0;
		
		$result = DB::select(DB::raw("select COALESCE(SUM(amount),0) AS total_amount from bc_transations where type='".$type."' AND from_address='".$address."' AND confirmations=0"));
				
		$total_amount = $result[0]->total_amount;
		
		return $total_amount; 
		
	}
	
	public static function email_already_exist($email) { 
	      
		$response = array('status'=>false,'type'=>'');  
        $data = User::where('email',$email)->first();
		
		if(!empty($data))
		{
		  if($data->reserve_card == NULL)
		  {
			$response['type'] = "partial_exist";	
		  }
		 
		   $response['status'] = true;
		}
          return $response;
	
		
    }
	
	public static function getLendoAdmins()
	{
		$Sql = "
		SELECT 
		users.id, 
		users.email,
		users.first_name,
		users.last_name,
		users.status, 
		users.role, 
		users.custom_role,
		admin_custom_role.role_name
		FROM users 
		LEFT JOIN admin_custom_role ON admin_custom_role.role_id = users.custom_role
		WHERE users.role = 1 AND users.id!=1 ORDER BY first_name, last_name";		
		return DB::select(DB::raw($Sql));
	}
	
	public static function getAdminRoles()
	{
		$Sql = "SELECT * FROM admin_custom_role";		
		return DB::select(DB::raw($Sql));
	}
	
	public static function checkAdminRole($role_name)
	{
		$row = DB::table('admin_custom_role')->select('role_id')->where('role_name',$role_name)->first();
		if(isset($row->role_id))
		{
			return true;
		}
		return false;
	}
	
	public static function getAdminPages()
	{
		$Sql = "SELECT * FROM admin_pages ORDER BY name";		
		return DB::select(DB::raw($Sql));
	}
	
	public static function getAdminPageAccess()
	{
		$Sql = "SELECT * FROM admin_access_permission";
		return DB::select(DB::raw($Sql));
	}
	
	public static function getRoleAssignPages($role_id)
	{
		$Sql = "
		SELECT admin_access_permission.*,admin_pages.route_name, admin_pages.action, admin_pages.controller 
		FROM admin_access_permission 
		LEFT JOIN admin_pages ON admin_pages.id = admin_access_permission.access_level_id
		WHERE admin_access_permission.status = 1 AND admin_access_permission.custom_role_id = $role_id";
		//echo $Sql;die;
		return DB::select(DB::raw($Sql));
	}
	
	public static function check_page_access($route_name, $role_id)
	{
		$Sql = "SELECT COUNT(*) as is_accessible
		FROM admin_access_permission 
		LEFT JOIN admin_pages ON admin_pages.id = admin_access_permission.access_level_id  
		WHERE admin_access_permission.custom_role_id = $role_id AND admin_access_permission.status=1 AND admin_pages.route_name='".$route_name."' LIMIT 1";
		$result = DB::select(DB::raw($Sql));
		return $result[0]->is_accessible;
	}
	
	public static function update_page_role($page_id, $role_id, $action)
	{
		$row = DB::table('admin_access_permission')->select('id')->where('access_level_id',$page_id)->where('custom_role_id',$role_id)->first();		
		if(isset($row->id)){
			$response = DB::table('admin_access_permission')->where('id',$row->id)->update(['status'=>$action]);
		}
		else{
			$response = self::insertIntoTable("admin_access_permission",['custom_role_id'=>$role_id, 'access_level_id'=>$page_id,'status'=>$action]);
		}
		return $response;
	}
	
	public static function update_admin_role($admin_id, $role_id)
	{
		return DB::table('users')->where('id',$admin_id)->update(['custom_role'=>$role_id]);
	}
	
	public static function total_loan_applied()
	{
		return DB::table("users")->where("loan_amount_requested",'>',0)->count();
	}
	
	public static function total_card_applied()
	{
		return DB::table("users")->where("reserve_card",'>',0)->count();
	}
	
	public static function total_card_applied_type($type=1)
	{
		return DB::table("users")->where("reserve_card",'>',0)->where("reserve_card",$type)->count();
	}
	
	public static function total_loan_amount()
	{		
		return DB::table('users')->where("loan_amount_requested",'>',0)->sum('loan_amount_requested');
	}
	
	public static function total_loan_term()
	{		
		return DB::table('users')->where("loan_amount_requested",'>',0)->sum('loan_term');
	}
	
	public static function average_loan_amount()
	{		
		return DB::table('users')->where("loan_amount_requested",'>',0)->avg('loan_amount_requested');
	}
	
	public static function average_loan_term()
	{		
		return DB::table('users')->where("loan_amount_requested",'>',0)->avg('loan_term');
	}
	
	public static function total_community_bonus()
	{		
		return DB::table('transactions')->whereIn("type",[2, 3, 4])->sum('value');
	}
	
	public static function getMyUpline($userid, $level=0)
	{
		DB::select("CALL getMyUpline('".$userid."',@ParentIDs);");
		$myupline = DB::select("SELECT @ParentIDs as myupline;");
		$parentList = explode(",",$myupline[0]->myupline);
		if(count($parentList))
		{
			if($level > 0)
			{
				$parentList = array_slice($parentList, 0, $level);
			}
		}
		return $parentList;
	}
	
	public static function total_users(){
		return DB::table('users')->where('role', 2)->count();
	}
	
	public static function total_verified_users(){
		return DB::table('users')->where('role', 2)->where('status', 1)->count();
	}
	
	public static function total_online_users(){
		return DB::table('users')->where('role', 2)->where('online', 1)->count();
	}
	
	public static function total_opt_in_users(){
		return DB::table('users')->where('role', 2)->where('status', 1)->where('user_opt_bonus',1)->count();
	}
	
	public static function minimum_one_referrals(){
		return DB::table('users')->where('role', 2)->where('referrer_count', '>',0)->count();
	}
	
	public static function kyc_status_count($status=1, $type='count'){
		
		if($status == 1){ /* approved */
			$count = 2;
		}
		elseif($status == 2){ /* declined */
			$count = 2;
		}
		else{ /* pending */
			$count = 0;
		}
		
		$result = DB::table('file_attachments')->select('user_id')->groupBy('user_id')->where('status',$status)->havingRaw('COUNT(*) > ?', [$count])->get();
		if($type == 'count'){
			return count($result);
		}
		else{
			return $result;
		}
	}
	
	public static function kyc_status_list($status=1){
		
		if($status == 1){ /* approved */
			$count = 2;
		}
		elseif($status == 2){ /* declined */
			$count = 2;
		}
		else{ /* pending */
			$count = 0;
		}
		
		$result = DB::table('file_attachments')->select('user_id')->groupBy('user_id')->where('status',$status)->havingRaw('COUNT(*) > ?', [$count])->get();
		if($type == 'count'){
			return count($result);
		}
		else{
			return $result;
		}
	}
	
	
	public static function user_login_today($today){
		
		$sql = "SELECT COUNT(DISTINCT user_id) as total_count FROM logs WHERE context='\"Login\"' AND user_id > 0 AND DATE(created_at)='".$today."'";
		$result = DB::select( DB::raw( $sql ) );
		$total_count = $result[0]->total_count;
		return $total_count;
	}
	
	public static function user_login_in_date($start, $end){
		$sql = "SELECT COUNT(DISTINCT user_id) as total_count FROM logs WHERE context='\"Login\"' AND user_id > 0 AND DATE(created_at) BETWEEN '".$start."' AND '".$end."'";
		$result = DB::select( DB::raw( $sql ) );
		$total_count = $result[0]->total_count;
		return $total_count;
	}
	
	public static function users_who_bought_elt(){
		$total_count = DB::table('transactions')->select('id')->where('status',1)->where('ledger', '=', 'ELT')->where('term_currency', '!=', 'ELT')->whereNotNull('term_currency')->count();
		return $total_count;
	}
	
	public static function users_who_have_elt_balance(){
		$total_count = DB::table('users')->select('id')->where('role',2)->where('status',1)->where('ELT_balance', '>', 0)->count();
		return $total_count;
	}
	
	public static function getadminuserstats()
	{
		$returnData = array();		
		$today_date = date("Y-m-d");
		$current_week_date_range = CommonHelper::current_week_date_range();
		$week_start_date = date("Y-m-d",strtotime($current_week_date_range['start_week']));
		$week_end_date = date("Y-m-d",strtotime($current_week_date_range['end_week']));
		$month_start_date = date('Y-m-01');
		$month_end_date  = date('Y-m-t');
		$returnData['total_users'] = self::total_users();
		$returnData['total_online_users'] = self::total_online_users();
		$returnData['mail_verified'] = self::total_verified_users();
		$returnData['total_opt_in_users'] = self::total_opt_in_users();
		$returnData['kyc_approved'] = self::kyc_status_count(1,'count');		
		$returnData['minimum_one_referral'] = self::minimum_one_referrals();
		$returnData['user_login_today'] = self::user_login_today($today_date);
		$returnData['user_login_week'] = self::user_login_in_date($week_start_date, $week_end_date);
		$returnData['user_login_month'] = self::user_login_in_date($month_start_date, $month_end_date);
		$returnData['boughtELT'] = self::users_who_bought_elt();
		$returnData['elt_in_account'] = self::users_who_have_elt_balance();
		return $returnData;
	}	
	
	public static function get_users_financial_stats()
	{
		$Sql = "SELECT 
		SUM(BTC_balance) as total_btc, 
		SUM(ETH_balance) as total_eth, 
		SUM(EUR_balance) as total_eur, 
		SUM(ELT_balance) as total_elt, 
		SUM(LTC_balance) as total_ltc, 
		SUM(BCH_balance) as total_bch, 
		SUM(ETC_balance) as total_etc, 
		SUM(XRP_balance) as total_xrp, 
		SUM(DASH_balance) as total_dash 
		FROM `users` WHERE role=2";
		$result  = DB::select(DB::raw($Sql));
		return $result[0];		
	}
	
	public static function get_pending_elt_to_distribute()
	{
		$Sql = "SELECT SUM(value) as total_pending_elt FROM transactions WHERE type IN (2,3,4,7) AND ledger='ELT' AND value IN (5,25,50) AND status=2";
		$result  = DB::select(DB::raw($Sql));
		return $result[0];
	}
	
	public static function get_user_details_in_list($userids)
	{
		$result = DB::table('users')->select('*')->whereIn('id',$userids)->get();
		return $result;
	}
	
}