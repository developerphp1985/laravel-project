<?php

namespace App\Models;
use DB;
use Illuminate\Database\Eloquent\Model;
use Lab404\Impersonate\Services\ImpersonateManager;
use Illuminate\Notifications\Notifiable;
use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Helpers\LoggerHelper;

class Proforma extends Model
{
	
	protected $table = 'proforma_invoices';
	 
    use Notifiable;
    use HasRoleAndPermission;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
	
	public static function total_count()
	{
		return DB::table('proforma_invoices')->count();
	}
	
	public static function get_datatables_join($column_order, $postarray, $get_count=0)
	{
		$query = Proforma::query();		
		$query->leftJoin('users', 'proforma_invoices.user_id', '=', 'users.id');
		$query->select('proforma_invoices.*','users.email','users.first_name','users.last_name');
		if(isset($postarray['search_text']) && !empty($postarray['search_text']))
		{
			$query->where('users.email', 'LIKE', "%$search_text%")
			->orWhere('users.first_name', 'LIKE', "%$search_text%")
			->orWhere('users.last_name', 'LIKE', "%$search_text%")
			->orWhere('proforma_invoices.currency', 'LIKE', "%$search_text%")
			->orWhere('proforma_invoices.currency', 'LIKE', "%$search_text%")
			->orWhere('proforma_invoices.reference_no', 'LIKE', "%$search_text%")
			->orWhere('proforma_invoices.currency_amount', 'LIKE', "%$search_text%")
			->orWhere('proforma_invoices.elt_amount', 'LIKE', "%$search_text%")
			->orWhere('proforma_invoices.remarks', 'LIKE', "%$search_text%");
		}
		
		if(!empty($_POST['start_date']) && !empty($_POST['end_date']))
		{
			$start_date_array = explode("/",$_POST['start_date']);
			$end_date_array = explode("/",$_POST['end_date']);
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';			
			$query->where('proforma_invoices.created_at', '>=', $start_date);
			$query->where('proforma_invoices.created_at', '<=', $end_date);
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
			$query->orderBy('proforma_invoices.created_at', 'desc');
		}
		
		//echo $query->toSql();die;
		
		if($get_count == 1)
		{
			$result = $query->count();	
		}
		else
		{
			$result = $query->get();
		}
		return $result;
	}
	
}
