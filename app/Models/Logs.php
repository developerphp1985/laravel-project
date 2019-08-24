<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Logs extends Model
{
    //use SoftDeletes;

    protected $table = 'logs';

    protected $fillable = [
        'user_id',
        'env',
        'message',
        'level',
        'context',
        'extra'
    ];
    
    protected $casts = [
        'context' => 'array',
        'extra' => 'array'
    ];
    
    protected function write(array $record)
    {
        Logs::create($record);
    }		
	
	public static function get_datatables_logs_list($postarray, $is_count=0, $admin_logs=0)	
	{				
		$query = Logs::query();		
		$query->select('logs.*','users.first_name', 'users.last_name', 'users.email');
		$query->leftjoin('users', 'users.id', '=', 'logs.user_id');		
		if($admin_logs == 1){
			$query->where('users.role', 1);	
		}		
		if(isset($postarray['search_text']) && !empty($postarray['search_text'])){			
			$search_text = $postarray['search_text'];			
			$query->where(function($query) use ($search_text){
					$query->where('logs.message', 'LIKE', "%$search_text%");			
			});
		}
		$query->where('logs.message', 'NOT LIKE', '%Block chain%');
		if(!empty($_POST['start_date']) && !empty($_POST['end_date'])){
			$start_date_array = explode("/",$_POST['start_date']);			
			$end_date_array = explode("/",$_POST['end_date']);			
			$start_date = $start_date_array[2].'-'.$start_date_array[0].'-'.$start_date_array[1];			
			$end_date = $end_date_array[2].'-'.$end_date_array[0].'-'.$end_date_array[1];	
			$start_date = $start_date.' 00:00:00';			
			$end_date = $end_date.' 23:59:59';	
			$query->where('logs.created_at', '>=', $start_date);			
			$query->where('logs.created_at', '<=', $end_date);		
		}
		
		if(isset($postarray['start'])) 
			$query->offset($postarray['start']);						
		
		if(isset($postarray['length']))			
			$query->limit($postarray['length']);								
		
		$query->orderBy('logs.created_at', 'desc');						
		
		//echo $query->toSql();die;
		
		if($is_count == 1){ 
			return $query->count();		
		}		
		else{			
			return $query->get();	
		}
	}
}

