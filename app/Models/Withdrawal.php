<?php

namespace App\Models;
use DB;
use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;
use Lab404\Impersonate\Services\ImpersonateManager;
use Illuminate\Notifications\Notifiable;
use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Helpers\LoggerHelper;

class Withdrawal extends Model
{
	
	protected $table = 'withdraw_request';
	 
    use Notifiable;
    use HasRoleAndPermission;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['transaction_id', 'ledger', 'amount', 'fees', 'transfer_amount','status','remarks','ip_address','user_id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
	
	public static function total_count()
	{
		return DB::table('withdraw_request')->count();
	}
	
	public static function get_datatables_join($column_order, $postarray, $get_count=0)
	{
		$query = Withdrawal::query();
		
		$query->leftJoin('users', 'withdraw_request.user_id', '=', 'users.id');
		
		$query->select('withdraw_request.*','users.email','users.BTC_wallet_address','users.ETH_wallet_address');
	
		$query->where('users.role', '2');

		$query->where('withdraw_request.status', '!=', '0');
		
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
				->orWhere('withdraw_request.ledger', 'LIKE', "%$search_text%")
				->orWhere('withdraw_request.transaction_id', 'LIKE', "%$search_text%")
				->orWhere('withdraw_request.amount', 'LIKE', "%$search_text%")
				->orWhere('withdraw_request.fees', 'LIKE', "%$search_text%")
				->orWhere('withdraw_request.transfer_amount', 'LIKE', "%$search_text%")
				->orWhere('withdraw_request.remarks', 'LIKE', "%$search_text%");

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
			
			$query->where('withdraw_request.created_at', '>=', $start_date);
			$query->where('withdraw_request.created_at', '<=', $end_date);
		}
	
		if(isset($postarray['start']) && $get_count == 0)
		{
			$query->offset($postarray['start']);
		}
		
		if(isset($postarray['length']) && $get_count == 0)
		{
			$query->limit($postarray['length']);
		}
		
		if(isset($postarray['order'])) 
		{
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
		else
		{
			$query->orderBy('withdraw_request.created_at', 'desc');
		}
		
		//echo $query->toSql();die;
		
		if($get_count == 1)
		{
			$result = $query->count();	
			return $result;
		}
		else
		{
			$result = $query->get();		
			return $result;
		}		
	}
	
	public static function withdraw_request_accounting($dateType='today', $statusType=array(0,1,2,3))
	{
		$returnData = array();
		
		$AndDateSql = '';
		
		$todayDate = date("Y-m-d");
		
		$start_date = '';
		
		$end_date = '';
		
		$AndStatusSql = " status IN (".implode(",",$statusType).")";
		
		if($dateType == 'today'){
			$AndDateSql = " AND DATE(created_at)='".$todayDate."'";
		}
		elseif($dateType == 'week'){
			$week_date = CommonHelper::current_week_date_range();
			$start_date = $week_date['start_week'];
			$end_date = $week_date['end_week'];
			$AndDateSql = " AND created_at BETWEEN '".$start_date."' AND '".$end_date."'";
		}
		elseif($dateType == 'month'){
			$start_date = date('Y-m-01');
			$end_date = date('Y-m-31');
			$AndDateSql = " AND DATE(created_at) BETWEEN '".$start_date."' AND '".$end_date."'";
		}
		elseif($dateType == 'year'){
			$start_date = date('Y-01-01');
			$end_date = date('Y-12-31');
			$AndDateSql = " AND DATE(created_at) BETWEEN '".$start_date."' AND '".$end_date."'";
		}
		else{
			$AndDateSql = '';
		}
		
		$Sql = "SELECT SUM(amount) as amount, ledger FROM `withdraw_request` WHERE $AndStatusSql $AndDateSql GROUP BY ledger";
		
		return DB::select($Sql);	
	}
	
	
	public static function withdraw_request_statistics($dateType='today')
	{
		$currencyList = array();
		$currencyList["ELT"] = array("requested"=>0,"approved"=>0,"declined"=>0);
		$currencyList["BTC"] = array("requested"=>0,"approved"=>0,"declined"=>0);
		$currencyList["ETH"] = array("requested"=>0,"approved"=>0,"declined"=>0);
		$currencyList["EUR"] = array("requested"=>0,"approved"=>0,"declined"=>0);
		$currencyList["LTC"] = array("requested"=>0,"approved"=>0,"declined"=>0);
		$currencyList["BCH"] = array("requested"=>0,"approved"=>0,"declined"=>0);
		$currencyList["XRP"] = array("requested"=>0,"approved"=>0,"declined"=>0);
		$currencyList["DASH"] = array("requested"=>0,"approved"=>0,"declined"=>0);
		
		$requested = self::withdraw_request_accounting($dateType, array(0,1,2,3));
		if($requested){
			foreach($requested as $Value)
				$currencyList[$Value->ledger]['requested'] = $Value->amount;
		}		
		
		$approved = self::withdraw_request_accounting($dateType, array(1));
		if($approved){
			foreach($approved as $Value)
				$currencyList[$Value->ledger]['approved'] = $Value->amount;
		}
		
		$declined = self::withdraw_request_accounting($dateType, array(2));
		if($declined){
			foreach($declined as $Value)
				$currencyList[$Value->ledger]['declined'] = $Value->amount;
		}
		
		return $currencyList;
	}	
}
