<?php
namespace App\Http\Controllers\Admin;
use DB;
use Lang;
use App\Models\User;
use App\Models\Logs;
use App\Helpers\LoggerHelper;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class AdminTranslationController extends Controller
{
	private $allowed_routes = array();
	
	private $currentRouteName;
	
	public function __construct()
    {
		$this->middleware(function ($request, $next) {
			
		$this->user= Auth::user();
		
		$session_token = session('login_token');
				
		$this->allowed_routes = array("admin.users","admin.ajaxusers","admin.usersdetail");
		
		$this->currentRouteName = \Request::route()->getName();
		
		if(!User::is_valid_admin_session(Auth::user()->id, $session_token)) 		
		{			
			auth()->logout();			
			session(['login_token' => '']);			
			return redirect()->route('admin.login')->withErrors(['email' => trans('lendo.another_user_login_with_admin_account')]);					
		}
		
		return $next($request);
			
		});
	}
	
	public function translation($code = null,$filename = null,$is_m = 0,Request $request)    
	{
	
	if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			//return redirect()->route('admin.dashboard');
		}
		$user = Auth::user();
		if(is_null($user->OTP))      
		{  
			$dataForView = array();
			$admin_lang = $user->language;
			$filename = 'api';
			$language = 'en';	
            // set locale
			\App::setLocale($language);	
						
			
			if(isset($_REQUEST['filename'])){
				$filename = $_REQUEST['filename'];
			}
			
			if(isset($_REQUEST['code'])){
				$language = $_REQUEST['code'];
			}
			
			// set posted locale
			$en_data = Lang::get($filename);
			\App::setLocale($language);
			$langdir = base_path().'/resources/lang/'.$language; 
			
			$lang_password = $this->get_language_password($language);
			
			if($request->isMethod('post'))
			{
				
				$posted_lang = $_POST['lang_name'];
				$langdir = base_path().'/resources/lang/'.$posted_lang; 
				
		        $update_post_array = array();
				$en_keys_array = array();
                $filepath = $langdir.'/'.$filename.'.php';			
				$updated_file_content = $_POST;	

				array_shift($updated_file_content);				
				$content = '';
				
				if(isset($updated_file_content['lang_name']))
				{
					unset($updated_file_content['lang_name']);
				}
				if(isset($updated_file_content['translation_dataTable_length']))
				{
					unset($updated_file_content['translation_dataTable_length']);
				}
				
				
				
				$filename_content = Lang::get($filename);
				
				$updated_file_content_new = array();
				foreach($filename_content as $fileKey=>$fileValue){
					$updated_file_content_new[$fileKey] = $fileValue;
					foreach($updated_file_content as $postKey=>$postValue){	
					
						if($fileKey == $postKey && $postValue != $fileValue){
							$updated_file_content_new[$fileKey] = $postValue;
						}
						
						
					}
				}
				  $array_diff = array_diff($updated_file_content,$updated_file_content_new);
				  
				  if(!empty($array_diff))
				  {
					  foreach($array_diff as $diff_key=>$diff_value)
					  {
						  
						  $updated_file_content_new[$diff_key] = $diff_value;
						  
					  }
					  
					  
				  }
				  
				 if($posted_lang != 'en')
				{
					$i = 0;	
					foreach($en_data as $key=>$value)
					{
						$en_keys_array[] = $key;
					}
					
					$i = 0;
					foreach($updated_file_content_new as $key=>$value)
					{
						$update_post_array[$en_keys_array[$i]] = $value;
						$i++;
					}
					
					foreach($update_post_array as $key=>$value)
					{
						$new_value = addslashes($value);
						 $content .= "'".$key."'=>'".$new_value."',\n";
					}
				}
				else
				{
					foreach($updated_file_content_new as $key=>$value)
					{
						$new_value = addslashes($value);
						$content .= "'".$key."'=>'".$new_value."',\n";
					}					
				}
				//echo $filepath;die;
				$this->writeFile($content,$filepath);
				
				// reset locale 
				\App::setLocale($admin_lang);
				
				return redirect()->route('admin.translation', ['code' => $posted_lang, 'filename'=>$filename,'is_m'=>1])->with('success','Language updated successfully');
			}
			
		    $updated_old_data = array();
			$old_data = Lang::get($filename);

			if($language != 'en')
			{
				//array_unique($en_data);
				foreach($en_data as $ekey=>$edata)
				{
					$new_edata = $ekey;
					if(isset($old_data[$ekey]))
					{
						$updated_old_data[$new_edata] = $old_data[$ekey];
					}
					else
					{
						if(!empty($new_edata))
						{
							$updated_old_data[$new_edata] = "";
						}
						else
						{
							$updated_old_data[$ekey] = "";
						}						
					}
				}

				\App::setLocale($language);				
				$old_data = array();
				$old_data = $updated_old_data;
			}			
			if(isset($old_data['lang_name']))
			{				
			unset($old_data['lang_name']);
			}
			if(isset($en_data['lang_name']))
			{				
			unset($en_data['lang_name']);
			}
			
			$langlist =  DB::table('language')->select('language.*')->get();
			
			$dataForView['langlist'] =  $langlist;
			$dataForView['filelist'] =  scandir($langdir);			
			$dataForView['lang_file_data'] =  $old_data;
			$dataForView['en_data'] =  $en_data;
			$dataForView['filename'] = $filename;
			$dataForView['language'] = $language;
			
			$show_modal = 'OK';
			if(isset($_REQUEST['is_m']) || $language == 'en')
			{
				$show_modal = 'NOK';
			}
			
			$dataForView['show_modal'] = $show_modal;
			$dataForView['lang_password'] = $lang_password;
			
			 //echo "<pre>";print_r($dataForView);die;
			
			return view('admin.translation',$dataForView);        
		}
		else    
		{            
			return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
		}
	}
	
	public function translationnew($code = null,$filename = null,$is_m = 0,Request $request)    
	{
		
        if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			//return redirect()->route('admin.dashboard');
		}
		$user = Auth::user();
		if(is_null($user->OTP))      
		{  
			$dataForView = array();
			$admin_lang = $user->language;
			$filename = 'api';
			$language = 'en';	
            // set locale
			\App::setLocale($language);	
						
			
			if(isset($_REQUEST['filename'])){
				$filename = $_REQUEST['filename'];
			}
			
			if(isset($_REQUEST['code'])){
				$language = $_REQUEST['code'];
			}
			
			// set posted locale
			$en_data = Lang::get($filename);
			\App::setLocale($language);
			$langdir = base_path().'/resources/lang/'.$language; 
			
			$lang_password = $this->get_language_password($language);
			
			if($request->isMethod('post'))
			{
				
				$posted_lang = $_POST['lang_name'];
				$langdir = base_path().'/resources/lang/'.$posted_lang; 
				
		        $update_post_array = array();
				$en_keys_array = array();
                $filepath = $langdir.'/'.$filename.'.php';			
				$updated_file_content = $_POST;	

				array_shift($updated_file_content);				
				$content = '';
				
				if(isset($updated_file_content['lang_name']))
				{
					unset($updated_file_content['lang_name']);
				}
				if(isset($updated_file_content['translation_dataTable_length']))
				{
					unset($updated_file_content['translation_dataTable_length']);
				}
				
				
				
				$filename_content = Lang::get($filename);
				//print_r($filename_content);
				$updated_file_content_new = array();
				foreach($filename_content as $fileKey=>$fileValue){
					$updated_file_content_new[$fileKey] = $fileValue;
					foreach($updated_file_content as $postKey=>$postValue){	
					
						if($fileKey == $postKey && $postValue != $fileValue){
							$updated_file_content_new[$fileKey] = $postValue;
						}
						
						/*
						if(array_key_exists($postKey,$filename_content)){
							
						}
						else
						{							
							$updated_file_content_new[$postKey] = $postValue;
						}
						*/
					}
				}
				  $array_diff = array_diff($updated_file_content,$updated_file_content_new);
				  
				  if(!empty($array_diff))
				  {
					  foreach($array_diff as $diff_key=>$diff_value)
					  {
						  
						  $updated_file_content_new[$diff_key] = $diff_value;
						  
					  }
					  
					  
				  }
				  
				 if($posted_lang != 'en')
				{
					$i = 0;	
					foreach($en_data as $key=>$value)
					{
						$en_keys_array[] = $key;
					}
					
					$i = 0;
					foreach($updated_file_content_new as $key=>$value)
					{
						$update_post_array[$en_keys_array[$i]] = $value;
						$i++;
					}
					
					foreach($update_post_array as $key=>$value)
					{
						$new_value = addslashes($value);
						 $content .= "'".$key."'=>'".$new_value."',\n";
					}
				}
				else
				{
					foreach($updated_file_content_new as $key=>$value)
					{
						$new_value = addslashes($value);
						$content .= "'".$key."'=>'".$new_value."',\n";
					}					
				}
				//echo $filepath;die;
				$this->writeFile($content,$filepath);
				
				// reset locale 
				\App::setLocale($admin_lang);
				
				return redirect()->route('admin.translationnew', ['code' => $posted_lang, 'filename'=>$filename,'is_m'=>1])->with('success','Language updated successfully');
			}
			
		    $updated_old_data = array();
			$old_data = Lang::get($filename);

			if($language != 'en')
			{
				//array_unique($en_data);
				foreach($en_data as $ekey=>$edata)
				{
					$new_edata = $ekey;
					if(isset($old_data[$ekey]))
					{
						$updated_old_data[$new_edata] = $old_data[$ekey];
					}
					else
					{
						if(!empty($new_edata))
						{
							$updated_old_data[$new_edata] = "";
						}
						else
						{
							$updated_old_data[$ekey] = "";
						}						
					}
				}

				\App::setLocale($language);				
				$old_data = array();
				$old_data = $updated_old_data;
			}			
			if(isset($old_data['lang_name']))
			{				
			unset($old_data['lang_name']);
			}
			if(isset($en_data['lang_name']))
			{				
			unset($en_data['lang_name']);
			}
			
			$langlist =  DB::table('language')->select('language.*')->get();
			
			$dataForView['langlist'] =  $langlist;
			$dataForView['filelist'] =  scandir($langdir);			
			$dataForView['lang_file_data'] =  $old_data;
			$dataForView['en_data'] =  $en_data;
			$dataForView['filename'] = $filename;
			$dataForView['language'] = $language;
			
			$show_modal = 'OK';
			if(isset($_REQUEST['is_m']) || $language == 'en')
			{
				$show_modal = 'NOK';
			}
			
			$dataForView['show_modal'] = $show_modal;
			$dataForView['lang_password'] = $lang_password;
			
			 //echo "<pre>";print_r($dataForView);die;
			
			return view('admin.translationnew',$dataForView);        
		}
		else    
		{            
			return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
		}
	}
	
	private function get_language_password($language)
	{
		$lang_password = 'klXXXX';
		$lang_detail  = DB::table('language')->select('language.password')->where('code',$language)->first();
		if(!empty($lang_detail))
		{
		$lang_password = $lang_detail->password;
		}
		$lang_password = CommonHelper::encodeDecode($lang_password,'d');
		return $lang_password;
	}
	
	private function writeFile($content,$filepath)    
	{		
	    
		$fileContent =  'return '.'['.$content.'];'; 
		$mode = "w+";	
		$filesize = filesize($filepath);
		$handle = $this->openFile($mode,$filepath);
		flock($handle,LOCK_EX);	
		ftruncate($handle,$filesize);
		$old_content = "<?php ";
		$updatedfileContent = $old_content."\n".$fileContent;
		file_put_contents($filepath,$updatedfileContent);	
		flock($handle,LOCK_UN);		
		fclose($handle);
		return true;
	}
	
	private function openFile($mode,$filepath)    
	{
		$handle = fopen($filepath, $mode);   
	
		if(!$handle){		   
			die('The file could not be opened!');       
		}				
		return $handle;
	}	
	
}
