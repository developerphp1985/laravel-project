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

class AdminComments extends Model
{
	
	protected $table = 'admin_comments';
	 
    use Notifiable;
    use HasRoleAndPermission;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'transaction_id', 'user_id', 'comments', 'comment_by', 'created_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
	
	public static function total_count()
	{
		return DB::table('admin_comments')->count();
	}
	
	public static function get_datatables_join($column_order, $postarray, $get_count=0)
	{
		$query = AdminComments::query();		
		
		$query->select('admin_comments.*', 'users.first_name', 'users.last_name');		
		
		$query->leftjoin('users', 'users.id', '=', 'admin_comments.comment_by');
		
		$query->where('admin_comments.user_id', $postarray['userid']);
		
		if(isset($postarray['search_text']) && !empty($postarray['search_text']))
		{
			$search_text = $postarray['search_text'];			
			$query->where(function($query) use ($search_text)
			{
				$query->where('admin_comments.comments', 'LIKE', "%$search_text%");
			});
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
			$query->orderBy('admin_comments.created_at', 'desc');
		}
		
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
	
}
