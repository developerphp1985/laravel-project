<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;


class ChangeRequests extends Model

{

    /**

     * The attributes that are mass assignable.

     *

     * @var array

     */

    protected $fillable = [

    	'id', 'old_value', 'new_value', 'unique_confirmation_key', 'user_id', 'is_delete', 'type'];
		
	public static function get_change_request_list($user_id, $type)
	{
		$result = ChangeRequests::where([['user_id', $user_id], ['is_delete', 0], ['type', $type]])->get();
		return $result;
	}
	
}

