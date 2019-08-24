<?php

namespace App\Models;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;

class WhiteList extends Model
{
	
	protected $table = 'whitelist_users';
	 
    use Notifiable;
    use HasRoleAndPermission;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'phone', 'amount', 'status', 'ip_address', 'created_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

	public static function check_whitelist_email($email)
	{
		$response = array();
		if(!empty($email))
		{
			return DB::table('whitelist_users')->where("email",$email)->count();
		}
		return 1;
	}
	
	public static function get_whitelist_members($whereClause)
	{
		$query = WhiteList::query();
		
		$query->select('whitelist_users.*');
		
		$query->where($whereClause);
		
		//print_r($query->toSql());die;
		
		$result = $query->get();
				
		return $result;
		
	}
	
	public static function update_whitelistusers($where,$data)
	{
		return DB::table('whitelist_users')->where($where)->update($data);
	}
	
	public static function get_datatables_join($column_order, $postarray)
	{
		$query = User::query();
		
		$query->leftJoin('users as usr', 'users.referrer_user_id', '=', 'usr.id');
		
		$query->select('users.*', 'usr.first_name as referrelFirstName','usr.last_name as referrelLastName','usr.email as referrelEmail','usr.id as referrelId');
		
		$query->where('users.role', '2');
		
		if(isset($postarray['search_text']) && !empty($postarray['search_text']))
		{
			$search_text = $postarray['search_text'];
			$query->where(function($query) use ($search_text)
			{
				$query->where('users.email', 'LIKE', "%$search_text%")
					->orWhere('users.first_name', 'LIKE', "%$search_text%")
					->orWhere('users.last_name', 'LIKE', "%$search_text%")
					->orWhere('usr.email', 'LIKE', "%$search_text%");
			});
		}
		
		if(!empty($_POST['start_date']) && !empty($_POST['end_date']))
		{
			$start_date_array = explode("/",$_POST['start_date']);
			$end_date_array = explode("/",$_POST['end_date']);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 00:00:00';
			
			$query->where('users.created_at', '>=', $start_date);
			$query->where('users.created_at', '<=', $end_date);
		}
	
		$query->offset($postarray['start']);
		
		$query->limit($postarray['length']);
		
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
			$query->orderBy('users.created_at', 'asc');
		}
		
		//echo $query->toSql();die;
		
		$result = $query->get();
		
		//print_r($result);die;
		
		return $result;
		
	}
}
