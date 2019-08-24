<?php

namespace App\Models;
use App\Helpers\CommonHelper;
use App\Helpers\LoggerHelper;
use DB;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;

class StoredData extends Model
{
    //
    use Notifiable;
    use HasRoleAndPermission;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'transactions';


	private static function get_hours_array()
	{
		$hours_array = array(
							0=>1,
		                    1=>2,
							2=>3,
							3=>4,
							4=>5,
							5=>6,
							6=>7,
							7=>8,
							9=>10,
							10=>11,
							11=>12,
							12=>13,
							13=>14,
							14=>15,
							16=>17,
							17=>18,
							18=>19,
							19=>20,
							20=>21,
							21=>22,
							22=>23,
							23=>24,
		);
		
		return $hours_array;
	}
	
	private static function get_daily_array()
	{
		$dates = array();
		$start = date('Y-m-d', strtotime('today - 30 days'));
		$end = date('Y-m-d');
		
		
		 $dates = array($start);
			while(end($dates) < $end){
				$dates[] = date('Y-m-d', strtotime(end($dates).' +1 day'));
			}
   
        $dates = array_reverse($dates);
		return $dates;
	}
	
	private static function get_weekly_array()
	{
		$date = date('Y-m-d');
		$week = date("W",strtotime($date));
		$week_array = array();
		$i = 1;
		
		for($i=1;$i<=$week;$i++)
		{
			$week_array[$i] = $i .' - '.date('Y');
		}
		$week_array = array_reverse($week_array);
		return $week_array;
	}
	
	private static function get_monthly_array()
	{
		$months_array = array(
		     1=>'Jan',
			 2=>'Feb',
			 3=>'March',
			 4=>'April',
			 5=>'May',
			 6=>'June',
			 7=>'July',
			 8=>'Aug',
			 9=>'Sept',
			 10=>'Oct',
			 11=>'Nov',
			 12=>'Dec'
		);
		 return $months_array;
	}
	
	public static function get_users_hourly_data($user_id,$type)
	{
		$returnData = array();		
	    $datatype = $type;
        $hours_array = self::get_hours_array();
		
		foreach($hours_array as $hour_start=>$hour_end)
		{
		
		$referral_level_1_list = array();
		$referral_level_2_list = array();
		$referral_level_3_list = array();
		$referral_level_4_list = array();
		$referral_level_5_list = array();
		$referral_level_all_id = array();
		$referral_level_all_list = array();	
		
		$time_array = array('start'=>$hour_start,'end'=>$hour_end);
		
		$referral_level_all_list = self::get_periodic_users_downline_list($user_id,'all','list','hourly',$time_array);
		$referral_level_all_count = count($referral_level_all_list);	
		
			foreach($referral_level_all_list as $row)
		{
			$referral_level_all_id[] = $row->child_id;			
			if($row->level == 1)
			{
				$referral_level_1_list[] = $row->child_id;
			}
			elseif($row->level == 2)
			{
				$referral_level_2_list[] = $row->child_id;
			}
			elseif($row->level == 3)
			{
				$referral_level_3_list[] = $row->child_id;
			}
			elseif($row->level == 4)
			{
				$referral_level_4_list[] = $row->child_id;
			}
			elseif($row->level == 5)
			{
				$referral_level_5_list[] = $row->child_id;
			}
		}
		
		
		
		$referral_level_1_count = count($referral_level_1_list);
		$referral_level_2_count = count($referral_level_2_list);
		$referral_level_3_count = count($referral_level_3_list);
		$referral_level_4_count = count($referral_level_4_list);
		$referral_level_5_count = count($referral_level_5_list);		
		$referral_level_five_count = $referral_level_1_count + $referral_level_2_count + $referral_level_3_count +$referral_level_4_count+ $referral_level_5_count;
		
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['referral_level_1'] = $referral_level_1_count;
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['referral_level_2'] = $referral_level_2_count;
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['referral_level_3'] = $referral_level_3_count;
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['referral_level_4'] = $referral_level_4_count;
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['referral_level_5'] = $referral_level_5_count;
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['referral_level_five_count'] = $referral_level_five_count;
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['referral_level_all'] = $referral_level_all_count;
		}		
		
		//echo '<pre>';
		//print_r($returnData); exit;
		
		return $returnData;
	}
	
	
	public static function get_users_daily_data($user_id,$type)
	{
		$returnData = array();		
	    $datatype = $type;
        $daily_array = self::get_daily_array();
		
		foreach($daily_array as $key=>$value)
		{
		
		$referral_level_1_list = array();
		$referral_level_2_list = array();
		$referral_level_3_list = array();
		$referral_level_4_list = array();
		$referral_level_5_list = array();
		$referral_level_all_id = array();
		$referral_level_all_list = array();	
		
		$referral_level_all_list = self::get_periodic_users_downline_list($user_id,'all','list','daily',$value);
		$referral_level_all_count = count($referral_level_all_list);	
		
			foreach($referral_level_all_list as $row)
		{
			$referral_level_all_id[] = $row->child_id;			
			if($row->level == 1)
			{
				$referral_level_1_list[] = $row->child_id;
			}
			elseif($row->level == 2)
			{
				$referral_level_2_list[] = $row->child_id;
			}
			elseif($row->level == 3)
			{
				$referral_level_3_list[] = $row->child_id;
			}
			elseif($row->level == 4)
			{
				$referral_level_4_list[] = $row->child_id;
			}
			elseif($row->level == 5)
			{
				$referral_level_5_list[] = $row->child_id;
			}
		}
		
		
		
		$referral_level_1_count = count($referral_level_1_list);
		$referral_level_2_count = count($referral_level_2_list);
		$referral_level_3_count = count($referral_level_3_list);
		$referral_level_4_count = count($referral_level_4_list);
		$referral_level_5_count = count($referral_level_5_list);		
		$referral_level_five_count = $referral_level_1_count + $referral_level_2_count + $referral_level_3_count +$referral_level_4_count+ $referral_level_5_count;
		
		$returnData['daily_array'][$value]['referral_level_1'] = $referral_level_1_count;
		$returnData['daily_array'][$value]['referral_level_2'] = $referral_level_2_count;
		$returnData['daily_array'][$value]['referral_level_3'] = $referral_level_3_count;
		$returnData['daily_array'][$value]['referral_level_4'] = $referral_level_4_count;
		$returnData['daily_array'][$value]['referral_level_5'] = $referral_level_5_count;
		$returnData['daily_array'][$value]['referral_level_five_count'] = $referral_level_five_count;
		$returnData['daily_array'][$value]['referral_level_all'] = $referral_level_all_count;
		}		
		
		
		return $returnData;
	}
	
	public static function get_users_weekly_data($user_id,$type)
	{
		$returnData = array();		
	    $datatype = $type;
        $weekly_array = self::get_weekly_array();
		
		
		
		
		foreach($weekly_array as $key=>$value)
		{
		
		$referral_level_1_list = array();
		$referral_level_2_list = array();
		$referral_level_3_list = array();
		$referral_level_4_list = array();
		$referral_level_5_list = array();
		$referral_level_all_id = array();
		$referral_level_all_list = array();	
		
		
		$referral_level_all_list = self::get_periodic_users_downline_list($user_id,'all','list','weekly',$value);
		$referral_level_all_count = count($referral_level_all_list);	
		
			foreach($referral_level_all_list as $row)
		{
			$referral_level_all_id[] = $row->child_id;			
			if($row->level == 1)
			{
				$referral_level_1_list[] = $row->child_id;
			}
			elseif($row->level == 2)
			{
				$referral_level_2_list[] = $row->child_id;
			}
			elseif($row->level == 3)
			{
				$referral_level_3_list[] = $row->child_id;
			}
			elseif($row->level == 4)
			{
				$referral_level_4_list[] = $row->child_id;
			}
			elseif($row->level == 5)
			{
				$referral_level_5_list[] = $row->child_id;
			}
		}
		
		
		
		$referral_level_1_count = count($referral_level_1_list);
		$referral_level_2_count = count($referral_level_2_list);
		$referral_level_3_count = count($referral_level_3_list);
		$referral_level_4_count = count($referral_level_4_list);
		$referral_level_5_count = count($referral_level_5_list);		
		$referral_level_five_count = $referral_level_1_count + $referral_level_2_count + $referral_level_3_count +$referral_level_4_count+ $referral_level_5_count;
		
		$returnData['weekly_array'][$value]['referral_level_1'] = $referral_level_1_count;
		$returnData['weekly_array'][$value]['referral_level_2'] = $referral_level_2_count;
		$returnData['weekly_array'][$value]['referral_level_3'] = $referral_level_3_count;
		$returnData['weekly_array'][$value]['referral_level_4'] = $referral_level_4_count;
		$returnData['weekly_array'][$value]['referral_level_5'] = $referral_level_5_count;
		$returnData['weekly_array'][$value]['referral_level_five_count'] = $referral_level_five_count;
		$returnData['weekly_array'][$value]['referral_level_all'] = $referral_level_all_count;
		}		
		
		
		return $returnData;
	}
	
		public static function get_users_monthly_data($user_id,$type)
	{
		$returnData = array();		
	    $datatype = $type;
        $monthly_array = self::get_monthly_array();
		
		
		
		
		foreach($monthly_array as $key=>$value)
		{
		
		
		$referral_level_1_list = array();
		$referral_level_2_list = array();
		$referral_level_3_list = array();
		$referral_level_4_list = array();
		$referral_level_5_list = array();
		$referral_level_all_id = array();
		$referral_level_all_list = array();	
		
		$referral_level_all_list = self::get_periodic_users_downline_list($user_id,'all','list','monthly',$key);
		
		
		
		$referral_level_all_count = count($referral_level_all_list);	
		
			foreach($referral_level_all_list as $row)
		{
			$referral_level_all_id[] = $row->child_id;			
			if($row->level == 1)
			{
				$referral_level_1_list[] = $row->child_id;
			}
			elseif($row->level == 2)
			{
				$referral_level_2_list[] = $row->child_id;
			}
			elseif($row->level == 3)
			{
				$referral_level_3_list[] = $row->child_id;
			}
			elseif($row->level == 4)
			{
				$referral_level_4_list[] = $row->child_id;
			}
			elseif($row->level == 5)
			{
				$referral_level_5_list[] = $row->child_id;
			}
		}
		
		
		$referral_level_1_count = count($referral_level_1_list);
		$referral_level_2_count = count($referral_level_2_list);
		$referral_level_3_count = count($referral_level_3_list);
		$referral_level_4_count = count($referral_level_4_list);
		$referral_level_5_count = count($referral_level_5_list);		
		$referral_level_five_count = $referral_level_1_count + $referral_level_2_count + $referral_level_3_count +$referral_level_4_count+ $referral_level_5_count;
		
		
		$returnData['monthly_array'][$value]['referral_level_1'] = $referral_level_1_count;
		$returnData['monthly_array'][$value]['referral_level_2'] = $referral_level_2_count;
		$returnData['monthly_array'][$value]['referral_level_3'] = $referral_level_3_count;
		$returnData['monthly_array'][$value]['referral_level_4'] = $referral_level_4_count;
		$returnData['monthly_array'][$value]['referral_level_5'] = $referral_level_5_count;
		$returnData['monthly_array'][$value]['referral_level_five_count'] = $referral_level_five_count;
		$returnData['monthly_array'][$value]['referral_level_all'] = $referral_level_all_count;
		
		}		
		
		
		
		//echo '<pre>';
		//print_r($referral_level_1_list); exit;
		
		return $returnData;
	}
	
	
	public static function get_hourly_data($user_id,$type)
	{
		$returnData = array();		
	    $datatype = $type;
        $hours_array = self::get_hours_array();
		
		
		$five_level_elt_worth = 0;
		$five_level_euro_worth = 0;
		$all_level_elt_worth = 0;
		$all_level_euro_worth = 0;		
		$referral_level_1_list = array();
		$referral_level_2_list = array();
		$referral_level_3_list = array();
		$referral_level_4_list = array();
		$referral_level_5_list = array();
		$referral_level_all_id = array();
		$referral_level_all_list = array();	
		$updated_array = array();
		$referral_level_all_list = self::get_user_downline_list($user_id,'all','list');
		$referral_level_all_count = count($referral_level_all_list);		
		foreach($referral_level_all_list as $row)
		{
			$referral_level_all_id[] = $row->child_id;			
			if($row->level == 1)
			{
				$referral_level_1_list[] = $row->child_id;
			}
			elseif($row->level == 2)
			{
				$referral_level_2_list[] = $row->child_id;
			}
			elseif($row->level == 3)
			{
				$referral_level_3_list[] = $row->child_id;
			}
			elseif($row->level == 4)
			{
				$referral_level_4_list[] = $row->child_id;
			}
			elseif($row->level == 5)
			{
				$referral_level_5_list[] = $row->child_id;
			}
		}		
	
		foreach($hours_array as $hour_start=>$hour_end)
		{
		
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_1_sales_data'] = array();
		if(count($referral_level_1_list) > 0){
			
			$data = self::get_user_elt_worth_in_euro_hours($referral_level_1_list,'list',$datatype,$hour_start,$hour_end);
			$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_1_sales_data'] = $data[0]; 
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_2_sales_data'] = array();
		if(count($referral_level_2_list) > 0){
			$data = self::get_user_elt_worth_in_euro_hours($referral_level_2_list,'list',$datatype,$hour_start,$hour_end);
			$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_2_sales_data'] = $data[0];
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_3_sales_data'] = array();
		if(count($referral_level_3_list) > 0){
			$data = self::get_user_elt_worth_in_euro_hours($referral_level_3_list,'list',$datatype,$hour_start,$hour_end);
			$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_3_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_4_sales_data'] = array();
		if(count($referral_level_4_list) > 0){
			$data = self::get_user_elt_worth_in_euro_hours($referral_level_4_list,'list',$datatype,$hour_start,$hour_end);
			$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_4_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_5_sales_data'] = array();
		if(count($referral_level_5_list) > 0){
			$data = self::get_user_elt_worth_in_euro_hours($referral_level_5_list,'list',$datatype,$hour_start,$hour_end);
			$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_5_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;			
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_all_sales_data'] = array();
		if(count($referral_level_all_id) > 0){
			$data = self::get_user_elt_worth_in_euro_hours($referral_level_all_id,'list',$datatype,$hour_start,$hour_end);
			$returnData['hours_array'][$hour_start.'>'.$hour_end]['level_all_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
		}
		}		
		
		$returnData['five_level_euro_worth'] = $five_level_euro_worth;
		$returnData['all_level_euro_worth'] = $all_level_euro_worth;
		
		//echo '<pre>';
		//print_R($returnData); exit;
		return $returnData;
	}

	public static function get_daily_data($user_id,$type)
	{
		$returnData = array();
		$datatype = $type;
		$daily_array = self::get_daily_array();
		
		$five_level_elt_worth = 0;
		$five_level_euro_worth = 0;
		$all_level_elt_worth = 0;
		$all_level_euro_worth = 0;		
		$referral_level_1_list = array();
		$referral_level_2_list = array();
		$referral_level_3_list = array();
		$referral_level_4_list = array();
		$referral_level_5_list = array();
		$referral_level_all_id = array();
		$referral_level_all_list = array();	
		
		$referral_level_all_list = self::get_user_downline_list($user_id,'all','list');
		$referral_level_all_count = count($referral_level_all_list);		
		foreach($referral_level_all_list as $row)
		{
			$referral_level_all_id[] = $row->child_id;			
			if($row->level == 1)
			{
				$referral_level_1_list[] = $row->child_id;
			}
			elseif($row->level == 2)
			{
				$referral_level_2_list[] = $row->child_id;
			}
			elseif($row->level == 3)
			{
				$referral_level_3_list[] = $row->child_id;
			}
			elseif($row->level == 4)
			{
				$referral_level_4_list[] = $row->child_id;
			}
			elseif($row->level == 5)
			{
				$referral_level_5_list[] = $row->child_id;
			}
		}		
	
		foreach($daily_array as $key=>$value)
		{
		
		
		$returnData['daily_array'][$value]['level_1_sales_data'] = array();
		if(count($referral_level_1_list) > 0){
			
			$data = self::get_user_elt_worth_in_euro_daily($referral_level_1_list,'list',$datatype,$value);
			$returnData['daily_array'][$value]['level_1_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['daily_array'][$value]['level_2_sales_data'] = array();
		if(count($referral_level_2_list) > 0){
			$data = self::get_user_elt_worth_in_euro_daily($referral_level_2_list,'list',$datatype,$value);
			$returnData['daily_array'][$value]['level_2_sales_data'] = $data[0];
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['daily_array'][$value]['level_3_sales_data'] = array();
		if(count($referral_level_3_list) > 0){
			$data = self::get_user_elt_worth_in_euro_daily($referral_level_3_list,'list',$datatype,$value);
			$returnData['daily_array'][$value]['level_3_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['daily_array'][$value]['level_4_sales_data'] = array();
		if(count($referral_level_4_list) > 0){
			$data = self::get_user_elt_worth_in_euro_daily($referral_level_4_list,'list',$datatype,$value);
			$returnData['daily_array'][$value]['level_4_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['daily_array'][$value]['level_5_sales_data'] = array();
		if(count($referral_level_5_list) > 0){
			$data = self::get_user_elt_worth_in_euro_daily($referral_level_5_list,'list',$datatype,$value);
			$returnData['daily_array'][$value]['level_5_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;			
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['daily_array'][$value]['level_all_sales_data'] = array();
		if(count($referral_level_all_id) > 0){
			$data = self::get_user_elt_worth_in_euro_daily($referral_level_all_id,'list',$datatype,$value);
			$returnData['daily_array'][$value]['level_all_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
		}
		}		
		$returnData['five_level_elt_worth'] = $five_level_elt_worth;
		$returnData['five_level_euro_worth'] = $five_level_euro_worth;
		$returnData['all_level_elt_worth'] = $all_level_elt_worth;
		$returnData['all_level_euro_worth'] = $all_level_euro_worth;
		
		
		
		
		return $returnData;
		
	}
	
	public static function get_weekly_data($user_id,$type)
	{
		$returnData = array();
		$datatype = $type;
		$weekly_array = self::get_weekly_array();
		
		$five_level_elt_worth = 0;
		$five_level_euro_worth = 0;
		$all_level_elt_worth = 0;
		$all_level_euro_worth = 0;		
		$referral_level_1_list = array();
		$referral_level_2_list = array();
		$referral_level_3_list = array();
		$referral_level_4_list = array();
		$referral_level_5_list = array();
		$referral_level_all_id = array();
		$referral_level_all_list = array();	
		
		$referral_level_all_list = self::get_user_downline_list($user_id,'all','list');
		$referral_level_all_count = count($referral_level_all_list);		
		foreach($referral_level_all_list as $row)
		{
			$referral_level_all_id[] = $row->child_id;			
			if($row->level == 1)
			{
				$referral_level_1_list[] = $row->child_id;
			}
			elseif($row->level == 2)
			{
				$referral_level_2_list[] = $row->child_id;
			}
			elseif($row->level == 3)
			{
				$referral_level_3_list[] = $row->child_id;
			}
			elseif($row->level == 4)
			{
				$referral_level_4_list[] = $row->child_id;
			}
			elseif($row->level == 5)
			{
				$referral_level_5_list[] = $row->child_id;
			}
		}		
	
		foreach($weekly_array as $key=>$week_number)
		{
		
		
		$returnData['weekly_array'][$week_number]['level_1_sales_data'] = array();
		if(count($referral_level_1_list) > 0){
			
			$data = self::get_user_elt_worth_in_euro_weekly($referral_level_1_list,'list',$datatype,$key);
			$returnData['weekly_array'][$week_number]['level_1_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['weekly_array'][$week_number]['level_2_sales_data'] = array();
		if(count($referral_level_2_list) > 0){
			$data = self::get_user_elt_worth_in_euro_weekly($referral_level_2_list,'list',$datatype,$key);
			$returnData['weekly_array'][$week_number]['level_2_sales_data'] = $data[0];
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['weekly_array'][$week_number]['level_3_sales_data'] = array();
		if(count($referral_level_3_list) > 0){
			$data = self::get_user_elt_worth_in_euro_weekly($referral_level_3_list,'list',$datatype,$key);
			$returnData['weekly_array'][$week_number]['level_3_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['weekly_array'][$week_number]['level_4_sales_data'] = array();
		if(count($referral_level_4_list) > 0){
			$data = self::get_user_elt_worth_in_euro_weekly($referral_level_4_list,'list',$datatype,$key);
			$returnData['weekly_array'][$week_number]['level_4_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['weekly_array'][$week_number]['level_5_sales_data'] = array();
		if(count($referral_level_5_list) > 0){
			$data = self::get_user_elt_worth_in_euro_weekly($referral_level_5_list,'list',$datatype,$key);
			$returnData['weekly_array'][$week_number]['level_5_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;			
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['weekly_array'][$week_number]['level_all_sales_data'] = array();
		if(count($referral_level_all_id) > 0){
			$data = self::get_user_elt_worth_in_euro_weekly($referral_level_all_id,'list',$datatype,$key);
			$returnData['weekly_array'][$week_number]['level_all_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
		}
		}		
		$returnData['five_level_elt_worth'] = $five_level_elt_worth;
		$returnData['five_level_euro_worth'] = $five_level_euro_worth;
		$returnData['all_level_elt_worth'] = $all_level_elt_worth;
		$returnData['all_level_euro_worth'] = $all_level_euro_worth;
		
		
		
		
		return $returnData;
		
	}
	
	public static function get_monthly_data($user_id,$type)
	{
		$returnData = array();
		$datatype = $type;
		$monthly_array = self::get_monthly_array();
		
		$five_level_elt_worth = 0;
		$five_level_euro_worth = 0;
		$all_level_elt_worth = 0;
		$all_level_euro_worth = 0;		
		$referral_level_1_list = array();
		$referral_level_2_list = array();
		$referral_level_3_list = array();
		$referral_level_4_list = array();
		$referral_level_5_list = array();
		$referral_level_all_id = array();
		$referral_level_all_list = array();	
		
		$referral_level_all_list = self::get_user_downline_list($user_id,'all','list');
		$referral_level_all_count = count($referral_level_all_list);		
		foreach($referral_level_all_list as $row)
		{
			$referral_level_all_id[] = $row->child_id;			
			if($row->level == 1)
			{
				$referral_level_1_list[] = $row->child_id;
			}
			elseif($row->level == 2)
			{
				$referral_level_2_list[] = $row->child_id;
			}
			elseif($row->level == 3)
			{
				$referral_level_3_list[] = $row->child_id;
			}
			elseif($row->level == 4)
			{
				$referral_level_4_list[] = $row->child_id;
			}
			elseif($row->level == 5)
			{
				$referral_level_5_list[] = $row->child_id;
			}
		}		
		
		foreach($monthly_array as $key=>$value)
		{
		
		
		$returnData['monthly_array'][$value]['level_1_sales_data'] = array();
		if(count($referral_level_1_list) > 0){
			
			$data = self::get_user_elt_worth_in_euro_monthly($referral_level_1_list,'list',$datatype,$key);
			$returnData['monthly_array'][$value]['level_1_sales_data'] = $data[0]; 
		
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['monthly_array'][$value]['level_2_sales_data'] = array();
		if(count($referral_level_2_list) > 0){
			$data = self::get_user_elt_worth_in_euro_monthly($referral_level_2_list,'list',$datatype,$key);
			$returnData['monthly_array'][$week_number]['level_2_sales_data'] = $data[0];
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['monthly_array'][$value]['level_3_sales_data'] = array();
		if(count($referral_level_3_list) > 0){
			$data = self::get_user_elt_worth_in_euro_monthly($referral_level_3_list,'list',$datatype,$key);
			$returnData['monthly_array'][$value]['level_3_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['monthly_array'][$value]['level_4_sales_data'] = array();
		if(count($referral_level_4_list) > 0){
			$data = self::get_user_elt_worth_in_euro_monthly($referral_level_4_list,'list',$datatype,$key);
			$returnData['monthly_array'][$value]['level_4_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['monthly_array'][$value]['level_5_sales_data'] = array();
		if(count($referral_level_5_list) > 0){
			$data = self::get_user_elt_worth_in_euro_monthly($referral_level_5_list,'list',$datatype,$key);
			$returnData['monthly_array'][$value]['level_5_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;			
			$five_level_euro_worth += $data[0]->euro_worth_total;
		}
		
		$returnData['monthly_array'][$value]['level_all_sales_data'] = array();
		if(count($referral_level_all_id) > 0){
			$data = self::get_user_elt_worth_in_euro_monthly($referral_level_all_id,'list',$datatype,$key);
			$returnData['monthly_array'][$value]['level_all_sales_data'] = $data[0]; 
			
			$all_level_euro_worth += $data[0]->euro_worth_total;
		}
		}		
		$returnData['five_level_elt_worth'] = $five_level_elt_worth;
		$returnData['five_level_euro_worth'] = $five_level_euro_worth;
		$returnData['all_level_elt_worth'] = $all_level_elt_worth;
		$returnData['all_level_euro_worth'] = $all_level_euro_worth;
		
		return $returnData;
		
	}
	
	
		public static function get_user_downline_list($userId, $level='all', $type='count')
	{
		$selectSql = '';		
		if($type == 'list')
		{
			$selectSql = 'SELECT child_id,parent_id,level';
		}
		else
		{
			$selectSql = 'SELECT count(child_id) as referral_count';
		}
		
		if($level != 'all')
		{
			$levelSql = " AND level = $level";
		}
		else
		{
			$levelSql = '';
		}
		
		$sql = $selectSql." FROM parent_child_relation WHERE parent_id = $userId $levelSql";
		
		$result = DB::select( DB::raw($sql));
		
		if($type == 'list')
		{
			$referrer_user_id = array();
			foreach($result as $row)
			{
				$referrer_user_id[] = $row;
			}
			return $referrer_user_id;
		}
		else
		{
			return $result[0]->referral_count;
		}
	}

	
	
	
	public static function get_periodic_users_downline_list($userId, $level='all', $type='count',$time_type,$time_value)
	{
		$selectSql = '';

        if($type == 'list')
		{
			$selectSql = 'SELECT child_id,parent_id,level';
		}
		else
		{
			$selectSql = 'SELECT count(child_id) as referral_count';
		}
		
		
		
		
		if($level != 'all')
		{
			$levelSql = " AND level = $level";
		}
		else
		{
			$levelSql = '';
		}
		
		
		$timeSql = '';
		if($time_type == "monthly")
		{
			
			$month = $time_value;
			$year = date('Y');
			$timeSql = " AND MONTH(users.created_at) = $month AND YEAR(users.created_at) = $year";
		}
		elseif($time_type == "weekly")
		{
			$week_number = $time_value;
			$year = date('Y');
			$get_dates = self::getStartAndEndDate($week_number,$year);
			
			$start_date = $get_dates[0];
			$end_date = $get_dates[1];
			
			$timeSql = " AND DATE(users.created_at) BETWEEN '".$start_date."' AND '".$end_date."'";
			
		}
		elseif($time_type == "daily")
		{
			$date =  $time_value;
			$timeSql = " AND DATE(users.created_at) = '".$date."'";
			
		}
		else
		{
			
			$date = date('Y-m-d');
			$start_hour = $time_value['start'];
			$end_hour = $time_value['end'];
			$timeSql = " AND DATE(users.created_at) = '".$date."' AND HOUR(users.created_at) BETWEEN $start_hour AND $end_hour";
		}
		
		$sql = $selectSql." FROM parent_child_relation LEFT JOIN users ON users.id = parent_child_relation.child_id WHERE parent_child_relation.parent_id = $userId $levelSql".$timeSql;
		
		//echo $sql; exit;
		$result = DB::select( DB::raw($sql));
		
		if($type == 'list')
		{
			$referrer_user_id = array();
			foreach($result as $row)
			{
				$referrer_user_id[] = $row;
			}
			
			return $referrer_user_id;
			
			
		}
		else
		{
			return $result[0]->referral_count;
		}
	}
	
	
	
	public static function get_user_elt_worth_in_euro_hours($user_id,$type,$datatype,$hour_start,$hour_end)
	{
		
		$date = date('Y-m-d');
		if(!empty($type) && $type == 'list'){
			$InSql = "transactions.user_id IN (".implode(',',$user_id).")";
		}
		elseif($type=='all'){
			$InSql = "1 ";
		}
		else{
			if($user_id == 114774){
				$InSql = "transactions.user_id = ".$user_id." AND transactions.value NOT LIKE '-%' ";
			}
			else{
				$InSql = "transactions.user_id = ".$user_id;
			}
		}
		
		if($datatype == "bonuses")
		{
		
		$Sql = "
		SELECT 
		SUM(transactions.value * transactions.value_in_euro) as euro_worth_total FROM transactions 
		LEFT JOIN phases ON phases.id = transactions.phase_id
		WHERE $InSql AND transactions.status=1 AND transactions.type_name IN ('bonus') AND transactions.phase_id IS NOT NULL AND DATE(transactions.created_at) = '".$date."'AND HOUR(transactions.created_at) BETWEEN $hour_start AND $hour_end";
		
		}
		else
		{
			
		$Sql = "
		SELECT 
		SUM(transactions.value * transactions.value_in_euro) as euro_worth_total FROM transactions 
		LEFT JOIN phases ON phases.id = transactions.phase_id
		WHERE $InSql AND transactions.status=1 AND transactions.type_name NOT IN ('bonus') AND transactions.ledger='ELT' AND transactions.type IN (1,5) AND transactions.phase_id IS NOT NULL AND DATE(transactions.created_at) = '".$date."' AND HOUR(transactions.created_at) BETWEEN $hour_start AND $hour_end";
			
		}
		
		$result = DB::select( DB::raw($Sql));
		return $result;
	}
	
	public static function get_user_elt_worth_in_euro_daily($user_id,$type,$datatype,$date)
	{
		$date = date('Y-m-d');
		if(!empty($type) && $type == 'list'){
			$InSql = "transactions.user_id IN (".implode(',',$user_id).")";
		}
		elseif($type=='all'){
			$InSql = "1 ";
		}
		else{
			if($user_id == 114774){
				$InSql = "transactions.user_id = ".$user_id." AND transactions.value NOT LIKE '-%' ";
			}
			else{
				$InSql = "transactions.user_id = ".$user_id;
			}
		}
		
		if($datatype == "bonuses")
		{
			
		$Sql = "
		SELECT 
		SUM(transactions.value * transactions.value_in_euro) as euro_worth_total FROM transactions 
		LEFT JOIN phases ON phases.id = transactions.phase_id
		WHERE $InSql AND transactions.status=1 AND transactions.type_name IN ('bonus') AND transactions.phase_id IS NOT NULL AND DATE(transactions.created_at) = $date";	
		}
		else
		{
			
		$Sql = "
		SELECT 
		SUM(transactions.value * transactions.value_in_euro) as euro_worth_total FROM transactions 
		LEFT JOIN phases ON phases.id = transactions.phase_id
		WHERE $InSql AND transactions.status=1 AND transactions.type_name NOT IN ('bonus') AND transactions.ledger='ELT' AND transactions.type IN (1,5) AND transactions.phase_id IS NOT NULL AND DATE(transactions.created_at) = $date";
		}
		$result = DB::select( DB::raw($Sql));
		return $result;
	}
	
	public static function get_user_elt_worth_in_euro_weekly($user_id,$type,$datatype,$week_number)
	{
		$year = date('Y');
		$get_dates = self::getStartAndEndDate($week_number,$year);
		
		$start_date = $get_dates[0];
		$end_date = $get_dates[1];
		if(!empty($type) && $type == 'list'){
			$InSql = "transactions.user_id IN (".implode(',',$user_id).")";
		}
		elseif($type=='all'){
			$InSql = "1 ";
		}
		else{
			if($user_id == 114774){
				$InSql = "transactions.user_id = ".$user_id." AND transactions.value NOT LIKE '-%' ";
			}
			else{
				$InSql = "transactions.user_id = ".$user_id;
			}
		}
		
		if($datatype == "bonuses")
		{
			
		$Sql = "
		SELECT 
		SUM(transactions.value * transactions.value_in_euro) as euro_worth_total FROM transactions 
		LEFT JOIN phases ON phases.id = transactions.phase_id
		WHERE $InSql AND transactions.status=1 AND transactions.type_name IN ('bonus') AND transactions.phase_id IS NOT NULL AND DATE(transactions.created_at) BETWEEN $start_date AND $end_date";	
			
		
		}
		else
		{
			
		$Sql = "
		SELECT 
		SUM(transactions.value * transactions.value_in_euro) as euro_worth_total FROM transactions 
		LEFT JOIN phases ON phases.id = transactions.phase_id
		WHERE $InSql AND transactions.status=1 AND transactions.type_name NOT IN ('bonus') AND transactions.ledger='ELT' AND transactions.type IN (1,5) AND transactions.phase_id IS NOT NULL AND DATE(transactions.created_at) BETWEEN $start_date AND $end_date";
		
			
		}
		$result = DB::select( DB::raw($Sql));
		
		return $result;
	}
	
	public static function get_user_elt_worth_in_euro_monthly($user_id,$type,$month)
	{
		$year = date('Y');
		if(!empty($type) && $type == 'list'){
			$InSql = "transactions.user_id IN (".implode(',',$user_id).")";
		}
		elseif($type=='all'){
			$InSql = "1 ";
		}
		else{
			if($user_id == 114774){
				$InSql = "transactions.user_id = ".$user_id." AND transactions.value NOT LIKE '-%' ";
			}
			else{
				$InSql = "transactions.user_id = ".$user_id;
			}
		}
		
		if($datatype == "bonuses")
		{
		
		$Sql = "
		SELECT 
		SUM(transactions.value * transactions.value_in_euro) as euro_worth_total FROM transactions 
		LEFT JOIN phases ON phases.id = transactions.phase_id
		WHERE $InSql AND transactions.status=1 AND transactions.type_name IN ('bonus') AND transactions.phase_id IS NOT NULL AND MONTH(created_at) = $month AND YEAR(created_at) = $year";	
		
		}
		
		else
		{
		
		$Sql = "
		SELECT 
		SUM(transactions.value * transactions.value_in_euro) as euro_worth_total FROM transactions 
		LEFT JOIN phases ON phases.id = transactions.phase_id
		WHERE $InSql AND transactions.status=1 AND transactions.type_name NOT IN ('bonus') AND transactions.ledger='ELT' AND transactions.type IN (1,5) AND transactions.phase_id IS NOT NULL AND MONTH(created_at) = $month AND YEAR(created_at) = $year";

		}
		$result = DB::select( DB::raw($Sql));
		return $result;
	}
	
	private static function getStartAndEndDate($week, $year)
	{

	   
		$time = strtotime("1 January $year", time());
		$day = date('w', $time);
		$time += ((7*$week)+1-$day)*24*3600;
		$return[0] = date('Y-n-j', $time);
		$time += 6*24*3600;
		$return[1] = date('Y-n-j', $time);
		return $return;
	}
  
}
