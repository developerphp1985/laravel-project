<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Configurations;
use App\Models\Phases;
use App\Helpers\CommonHelper;
use App\Helpers\LoggerHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AdminConfigurationController extends Controller
{
	private $currentRouteName;
	
	public function __construct()
    {
		$this->currentRouteName = \Request::route()->getName();
		$this->middleware(function ($request, $next) 
		{
			
			$this->user= Auth::user();
			
			$session_token = session('login_token');
			
			if(!User::is_valid_admin_session(Auth::user()->id, $session_token)) 		
			{			
				auth()->logout();			
				session(['login_token' => '']);			
				return redirect()->route('admin.login')->withErrors(['email' => trans('lendo.another_user_login_with_admin_account')]);					
			}			
			return $next($request);
			
		});
	}
	
    public function index(Request $request)
    {
        $user = Auth::user();
		
        if(is_null($user->OTP))
        {
            $configuration = Configurations::where('valid_to', '=', "9999-12-31")->orderBy('name', 'asc')->get();
            if($request->get('Conversion-BTC-EUR') != null)
            {
                /*Check if data is updated if yes then create new row*/
                foreach($configuration as $config)
                {
                    if($config->defined_value != $request->get($config->name))
                    {
                        $configurationUpdate = Configurations::find($config->id);
                        $configurationUpdate->valid_to = date('Y-m-d h:i:s');
                        $configurationUpdate->save();
                        $configurationCreate = Configurations::create([
                            'name'          => $config->name,
                            'valid_from'    => date('Y-m-d h:i:s'),
                            'valid_to'      => '9999-12-31',
                            'defined_value' => $request->get($config->name),
                            'defined_unit'  => $config->defined_unit,
                            'updated_by'    => '1'
                        ]);
                    }
                }
                return redirect()->route('admin.configuration')->with('success', trans("message.config_update") ); 
            }
            return view('admin.configuration', ['configuration' => $configuration]);
        }
        else
        {
            return redirect()
                    ->route('admin.twoSetpVarification')
                        ->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }

    /**
     *
     */
    public function showUpdatePhases(Request $request)
    {
        $user = Auth::user();				
		
        if(is_null($user->OTP))
        {
            $phases = Phases::all();
            if($request->get('update_phase'))
            {
                if ($request->get('previous_id'))
                {
                    $phasesSelected             = Phases::find($request->get('previous_id'));
                    $phasesSelected->status     = 0;
                    $phasesSelected->updated_by = $user->id;
                    $phasesSelected->updated_at = date('Y-m-d h:i:s');
                    $phasesSelected->save();
                }
                
                $phasesSelected             = Phases::find($request->get('id'));
                $phasesSelected->status     = 1;
                $phasesSelected->updated_by = $user->id;
                $phasesSelected->updated_at = date('Y-m-d h:i:s');
                $phasesSelected->save();
                return redirect()->route('admin.phases')->with('success', trans("message.config_update") ); 
            }
            return view('admin.phases', ['phases' => $phases]);
        }
        else
        {
            return redirect()
                    ->route('admin.twoSetpVarification')
                        ->withErrors(['OTP' => 'Please enter your OTP']);
        }
    }			
	
	
	public function configsetting(Request $request)    
	{	
		error_reporting(0);
		
		$dataForView = array();

		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		
		$etc_to_euro = CommonHelper::get_brave_coin_rates("etc","EUR",1);
		$xrp_to_euro = CommonHelper::get_brave_coin_rates("xrp","EUR",1);
		$dash_to_euro = CommonHelper::get_brave_coin_rates("dash","EUR",1);
				
		$nexo_to_euro = CommonHelper::get_coinmarketcap_rates(config('global_vars.NEXO_ID'),"EUR");
		$salt_to_euro = CommonHelper::get_coinmarketcap_rates(config('global_vars.SALT_ID'),"EUR");
		$orme_to_euro = CommonHelper::get_coinmarketcap_rates(config('global_vars.ORME_ID'),"EUR");
		
		$user = Auth::user();
		$configuration_data = array();
		if(is_null($user->OTP))      
		{  
			$configuration = Configurations::where('valid_to', '=', "9999-12-31")->orderBy('name', 'asc')->getQuery()->get();
		
			$base_currency = config('global_vars.base_currency');
			$term_currency = config('global_vars.term_currency');
		
			foreach($configuration as $config)
			{
				$configuration_data[$config->name] = $config;
			}
			if($request->isMethod('post')) 
			{
				foreach($configuration as $config)
                {
					if(isset($_POST[$config->name]))
					{
						if($config->defined_value != $_POST[$config->name] && $config->name!='Conversion-EUR-ELT')
						{
							$configurationUpdate = Configurations::find($config->id);
							$configurationUpdate->valid_to = date('Y-m-d h:i:s');
							$configurationUpdate->save();
							$configurationCreate = Configurations::create([
								'name'          => $config->name,
								'valid_from'    => date('Y-m-d h:i:s'),
								'valid_to'      => '9999-12-31',
								'defined_value' => $_POST[$config->name],
								'defined_unit'  => $config->defined_unit,
								'updated_by'    => '1'
							]);
							
							$postedData = array();
							$postedData['name'] = $config->name;
							$postedData['value'] = $_POST[$config->name];
							
							$record = [
								'user_id'	=> Auth::user()->id,
								'message'   => "Make configuration for ".$config->name." from: ".$config->defined_value.' to:'.$_POST[$config->name],
								'level'     => 'INFO',
								'context'   => 'Configuration changes',
								'extra' => [
									'configuration_changes' => json_encode($postedData)
								]
							];
							LoggerHelper::writeDB($record);
						}
					}
                }
				
				if(isset($_POST['Conversion-EUR-ELT']))
				{
					$_POST['Conversion-EUR-ELT'] = 1/$_POST['Conversion-EUR-ELT'];
					if($configuration_data['Conversion-EUR-ELT']->defined_value != $_POST['Conversion-EUR-ELT'])
					{
						$configurationUpdate = Configurations::find($configuration_data['Conversion-EUR-ELT']->id);
						$configurationUpdate->valid_to = date('Y-m-d h:i:s');
						$configurationUpdate->save();
						$configurationCreate = Configurations::create([
							'name'          => 'Conversion-EUR-ELT',
							'valid_from'    => date('Y-m-d h:i:s'),
							'valid_to'      => '9999-12-31',
							'defined_value' => $_POST['Conversion-EUR-ELT'],
							'defined_unit'  => 'ELT',
							'updated_by'    => '1'
						]);
						
						$postedData = array();
						$postedData['name'] = 'Conversion-EUR-ELT';
						$postedData['value'] = $_POST['Conversion-EUR-ELT'];
						
						$record = [
							'user_id'	=> Auth::user()->id,
							'message'   => "Make configuration for ELT-EURO price from: ".$configuration_data['Conversion-EUR-ELT'].' to:'.$_POST[$config->name],
							'level'     => 'INFO',
							'context'   => 'Configuration changes',
							'extra' => [
								'configuration_changes' => json_encode($postedData)
							]
						];
						LoggerHelper::writeDB($record);
					}
				}
				return redirect()->route('admin.configsetting')->with('success', trans("message.config_update") );
			}
			
			
			$euro_worth_stage_0 = config('global_vars.euro_worth_stage_0');
			$euro_worth_stage_1 = config('global_vars.euro_worth_stage_1');
			$euro_worth_stage_2 = config('global_vars.euro_worth_stage_2');
			$euro_worth_stage_3 = config('global_vars.euro_worth_stage_3');
			$euro_worth_stage_4 = config('global_vars.euro_worth_stage_4');
			$euro_worth_stage_5 = config('global_vars.euro_worth_stage_5');
		
			$dataForView['configuration'] = $configuration;
			$dataForView['configuration_data'] = $configuration_data;
			$dataForView['base_currency'] = $base_currency;
			$dataForView['term_currency'] = $term_currency;
			$dataForView['etc_to_euro'] = $etc_to_euro;
			$dataForView['xrp_to_euro'] = $xrp_to_euro;
			$dataForView['dash_to_euro'] = $dash_to_euro;
			$dataForView['nexo_to_euro'] = $nexo_to_euro;
			$dataForView['salt_to_euro'] = $salt_to_euro;
			$dataForView['orme_to_euro'] = $orme_to_euro;
			
			$dataForView['euro_worth_stage_0'] = $euro_worth_stage_0;
			$dataForView['euro_worth_stage_1'] = $euro_worth_stage_1;
			$dataForView['euro_worth_stage_2'] = $euro_worth_stage_2;
			$dataForView['euro_worth_stage_3'] = $euro_worth_stage_3;
			$dataForView['euro_worth_stage_4'] = $euro_worth_stage_4;
			$dataForView['euro_worth_stage_5'] = $euro_worth_stage_5;
			
			return view('admin.configsetting',$dataForView);        
		} 
		else    
		{            
			return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
		}    
	}

	public function loansetting(Request $request)    
	{	
		error_reporting(0);		
		if(!User::check_page_access($this->currentRouteName,Auth::user()->custom_role)){
			return redirect()->route('admin.dashboard');
		}
		$user = Auth::user();
		$loan_settings = array();
		if(is_null($user->OTP))      
		{  
			$Configurations = Configurations::where([['valid_to', '9999-12-31']])->get();
			$Configurations_data = array();
			if (!$Configurations->isEmpty()){
				foreach ($Configurations as $key => $value) {
					$Configurations_data[$value->name][] = $value->defined_value;
					$Configurations_data[$value->name][] = $value->defined_unit;
				}
			}
			
			if($request->isMethod('post')) 
			{
				foreach($Configurations as $config)
                {
					if(isset($_POST[$config->name]))
					{
						if($config->defined_value != $_POST[$config->name])
						{
							$configurationUpdate = Configurations::find($config->id);
							$configurationUpdate->valid_to = date('Y-m-d h:i:s');
							$configurationUpdate->save();
							$configurationCreate = Configurations::create([
								'name'          => $config->name,
								'valid_from'    => date('Y-m-d h:i:s'),
								'valid_to'      => '9999-12-31',
								'defined_value' => $_POST[$config->name],
								'defined_unit'  => $config->defined_unit,
								'updated_by'    => '1'
							]);
							
							$postedData = array();
							$postedData['name'] = $config->name;
							$postedData['value'] = $_POST[$config->name];
							$record = [
								'user_id'	=> Auth::user()->id,
								'message'   => Auth::user()->email,
								'level'     => 'Loan configuration',
								'context'   => 'Loan configuration changes',
								'extra' => [
									'loan_configuration_changes' => json_encode($postedData)
								]
							];
							LoggerHelper::writeDB($record);
						}
					}
                }
				return redirect()->route('admin.loansetting')->with('success', "Loan setting updated successfully!" );
			}
			return view('admin.loansetting',['Configurations_data'=>$Configurations_data]);
		}
		else    
		{            
			return redirect()->route('admin.twoSetpVarification')->withErrors(['OTP' => 'Please enter your OTP']);
		}
	}
}
