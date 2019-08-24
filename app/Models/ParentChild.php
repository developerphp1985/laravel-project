<?php

namespace App\Models;

use DB;

use Illuminate\Database\Eloquent\Model;

class ParentChild extends Model{

	protected $table = 'parent_child_relation';       
	
	protected $guarded = ['id','child_id','parent_id','level'];
	
	public static function remove_child($child_id){		
		return ParentChild::where('child_id', $child_id)->delete();
	}
	
	public static function get_my_upline($child_id){		
		DB::select("CALL getUserUpline('".$child_id."',@ParentIDs)");
		$results = DB::select("SELECT @ParentIDs as upline");
		$upline = explode(",",$results[0]->upline);		
		return $upline;
	}
	
	public static function restructure_child($child_id, $set_graph=0){		
		self::remove_child($child_id);
		$useruplines = self::get_my_upline($child_id);
		if(count($useruplines)){
			$counter = 1;
			foreach($useruplines as $userupline){
				$insertData = array();
				$insertData['child_id'] = $child_id;
				$insertData['parent_id'] = $userupline;
				$insertData['level'] = $counter;
				DB::table('parent_child_relation')->insert($insertData);				
				if($set_graph == 1){
					//DB::table('parent_child_relation_graphs')->insert($insertData);
				}
				$counter++;
			}
		}
	}
	
	public static function set_downline_graph($child_id){
		$useruplines = self::get_my_upline($child_id);
		if(count($useruplines)){
			$counter = 1;
			foreach($useruplines as $userupline){
				$insertData = array();
				$insertData['child_id'] = $child_id;
				$insertData['parent_id'] = $userupline;
				$insertData['level'] = $counter;
				DB::table('parent_child_relation_graphs')->insert($insertData);
				$counter++;
			}
		}
	}
	
	public static function get_all_downline_sales_data()
	{
		$Sql = 
		"SELECT
		SUM(t.value * p.token_price) as euro_worth_total,
		SUM(t.value) as elt_worth_total,
		pc.parent_id,
		u.first_name,
		u.last_name,
		u.display_name,
		u.email
		FROM parent_child_relation as pc
		LEFT JOIN transactions as t ON t.user_id = pc.child_id
		LEFT JOIN phases as p ON p.id = t.phase_id
		LEFT JOIN users as u ON u.id = pc.parent_id
		WHERE t.status=1 AND t.type_name NOT IN ('bonus') AND t.ledger='ELT' AND t.type IN (1,5) AND t.phase_id IS NOT NULL AND pc.level IN (1,2,3,4,5) AND u.exclude_salestoplist = 0
		GROUP BY pc.parent_id
		ORDER BY euro_worth_total DESC";
		$result = DB::select( DB::raw($Sql));
		return $result;		
	}
	
	public static function get_user_sales_fake_Data($userid)
	{
		$Sql = "SELECT SUM(sales_euro_level_1 + sales_euro_level_2 + sales_euro_level_3 + sales_euro_level_4 + sales_euro_level_5) as total_five_level_sales_Data FROM users WHERE id = $userid";	
		$result = DB::select( DB::raw($Sql));
		return $result[0]->total_five_level_sales_Data;
	}
	
	public static function get_user_downline_sales_data($userid, $levels=1)
	{
		$Sql = 
		"SELECT
		SUM(t.value * p.token_price) as euro_worth_total,
		SUM(t.value) as elt_worth_total,
		pc.parent_id,
		u.first_name,
		u.last_name,
		u.display_name,
		u.email
		FROM parent_child_relation as pc
		LEFT JOIN transactions as t ON t.user_id = pc.child_id
		LEFT JOIN phases as p ON p.id = t.phase_id
		LEFT JOIN users as u ON u.id = pc.parent_id
		WHERE t.status=1 AND t.type_name NOT IN ('bonus') AND t.ledger='ELT' AND t.type IN (1,5) AND t.phase_id IS NOT NULL AND pc.level IN ($levels) AND pc.parent_id = $userid
		GROUP BY pc.parent_id";
		$result = DB::select( DB::raw($Sql));
		return $result;		
	}
	
}