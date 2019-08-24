<?php



namespace App\Models;

use DB;

use Illuminate\Database\Eloquent\Model;


class Phases extends Model
{

    protected $table = 'phases';



    /**

     * The method to subtract provided value to existing balance.

     *

     * @var amount - total amount 

     * @var bonus - bonus to be subtracted

     */

    public static function calculateBonusToken($amount, $bonus)

    {

        if( isset($amount) && $amount>0 ) { 

            $finalAmount = $amount*($bonus/100);

            return $finalAmount;

        } else {

            return "Negative values are not allowed";

        }  

    }
	
	public static function get_current_phase_row()
	{
		$row = DB::table('phases')->select('*')->where('status','=',1)->first();
		if(isset($row->id) && $row->id > 0)
		{
			return $row;
		}
		return array();
	}
	
	public static function get_next_phase_row($start_date)
	{
		$row = DB::table('phases')->select('*')->where('phase_start_date','=',$start_date)->first();
				
		if(isset($row->id) && $row->id > 0)
		{
			return $row;
		}
		return array();
	}
	
	public static function phase_update_row($id, $data)
	{
		$row = DB::table('phases')->select('*')->where('phase_start_date','=',$start_date)->first();
		DB::table('whitelist_users')->where($where)->update($data);	
		if(isset($row->id) && $row->id > 0)
		{
			return $row;
		}
		return array();
	}
	
}
