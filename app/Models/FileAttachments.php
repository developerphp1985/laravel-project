<?php
namespace App\Models;use DB;use Illuminate\Database\Eloquent\Model;

class FileAttachments extends Model{

	protected $table = 'file_attachments';       
	
	protected $guarded = ['id','user_id','filename','created_at','updated_at','status'];
	
	public static function fetch_kyc_status($user_id)		
	{			
		error_reporting(0);		
		
		$kyc_result = DB::table('file_attachments')->where("user_id",$user_id)->get();		

		if($kyc_result[0]->status == 1 && $kyc_result[1]->status == 1 && $kyc_result[2]->status == 1)	
		{						
			return 1;				
		}				
		return 0;	    
	}
	
	public static function getKYCStatus($user_id)		
	{			
		error_reporting(0);        
		
		$kyc_result = DB::table('file_attachments')->where("user_id",$user_id)->get(); 
		
		if($kyc_result[0]->status == 1 && $kyc_result[1]->status == 1 && $kyc_result[2]->status == 1)        
		{						
			return 1;		        
		}	        
		elseif($kyc_result[0]->status == 2 && $kyc_result[1]->status == 2 && $kyc_result[2]->status == 2)        
		{						
			return 2;		        
		}		
		return 0;	    
	}

	public static function getKYCRows($user_id)		
	{			
		error_reporting(0);
		
		$KYC_status = array();
		
		$kyc_result = DB::table('file_attachments')->where("user_id",$user_id)->get(); 
					
		if($kyc_result[0]->status == 1 && $kyc_result[1]->status == 1 && $kyc_result[2]->status == 1)        
		{		
			$KYC_status["status"] = 1;
			$KYC_status["message"] = trans('lendo.VerifiedText');
		}	        
		elseif($kyc_result[0]->status == 2 && $kyc_result[1]->status == 2 && $kyc_result[2]->status == 2)        
		{						
			$KYC_status["status"] = 2;
			$KYC_status["message"] = trans('lendo.DeclinedText');		        
		}
		elseif( count($kyc_result) != 0 && ($kyc_result[0]->status == 0 || $kyc_result[1]->status == 0 || $kyc_result[2]->status == 0) )        
		{								
			$KYC_status["status"] = 0;
			$KYC_status["message"] = trans('lendo.PendingText');		        
		}
		else
		{
			$KYC_status["status"] = 0;			
			$KYC_status["message"] = trans('lendo.UnverifiedText');	
		}	
		return $KYC_status;
	}		
}