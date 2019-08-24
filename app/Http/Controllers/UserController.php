<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Phases;
use App\Models\Country;
use App\Models\ParentChild;
use App\Models\Transactions;
use App\Models\Configurations;
use App\Models\FileAttachments;
use App\Models\Withdrawal;
use App\Helpers\CommonHelper;
use App\Helpers\LoggerHelper;
use App\Helpers\PhaseHelper;
use Hiteshi\Coinpayments\Coinpayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PerfectMoney;
use Illuminate\Support\Facades\Storage;
use File;
class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['coinpaymentHook']]);
    }

    /**
     *
     */
    public function getVerified(Request $request)
    {		
        error_reporting(0);		
        $aws_kyc_path = asset("kyc/");
        $check_kyc = DB::table('users')
            ->select('users.first_name', 'users.last_name', 'users.address1', 'users.city', 'users.postal_code', 'users.country_code')
            ->where('users.id', Auth::user()->id)
            ->first();

		if ($check_kyc->first_name && $check_kyc->last_name && $check_kyc->address1 && $check_kyc->city && $check_kyc->postal_code && $check_kyc->country_code) 
		{
            $kyc_data = '';
        } 
		else 
		{
            $kyc_data = __('message.kyc_preference_validation');
        }
        
		if ($request->get('submit_kyc')) 
		{
            $check_kyc = DB::table('users')
                ->select('users.first_name', 'users.last_name', 'users.address1', 'users.city', 'users.postal_code', 'users.country_code')
                ->where('users.id', Auth::user()->id)->first();
				
            if ($check_kyc->first_name && $check_kyc->last_name && $check_kyc->address1 && $check_kyc->city && $check_kyc->postal_code && $check_kyc->country_code) 
			{
                $validator1 = Validator::make($request->all(), [
                    'kyc_document_dlf' => 'required|mimes:jpeg,png,jpg,pdf',
                ]);
				
                if ($validator1->fails() && $request->file('kyc_document_dlf')) {
                    return redirect()->route('home.preferences')->withErrors($validator1)->withInput($request->all())->with('tabname', 'KYC');
                }
				
                $validator2 = Validator::make($request->all(), [
                    'kyc_document_dlb' => 'required|mimes:jpeg,png,jpg,pdf',
                ]);
				
                if ($validator2->fails() && $request->file('kyc_document_dlb')) {
                    return redirect()->route('home.preferences')->withErrors($validator2)->withInput($request->all())->with('tabname', 'KYC');
                }
				
                $validator3 = Validator::make($request->all(), [
                    'kyc_document_poa' => 'required|mimes:jpeg,png,jpg,pdf',
                ]);
				
                if ($validator3->fails() && $request->file('kyc_document_poa')) 
				{
                    return redirect()->route('home.preferences')->withErrors($validator3)->withInput($request->all())->with('tabname', 'KYC');
                }
				
                $destinationPath = public_path('/kyc');
				
                $type = NULL;
                
				$s3 = \Storage::disk('s3');
                
				
				$s3update1 = $s3update2 = $s3update3 = '';
				
				
                if($request->file('kyc_document_dlf')) 
				{					
                    $kyc_document_dlf = $request->file('kyc_document_dlf');
                    
					$kyc_document_dlf_filename = 'dlf-' . time().'-'.uniqid().'.' . $kyc_document_dlf->getClientOriginalExtension();
                    
					$kyc_document_dlf_type = 'DLF';
					
					$awsFilePath = '/kyc/' . $kyc_document_dlf_filename;
					
					$s3update1 = $s3->put($awsFilePath, file_get_contents($kyc_document_dlf), 'private');
					
                    
					//$s3update1 = $kyc_document_dlf->move($destinationPath, $kyc_document_dlf_filename);
                
				}
				
                if($request->file('kyc_document_dlb')) 
				{
                    $kyc_document_dlb = $request->file('kyc_document_dlb');
                    
					$kyc_document_dlb_filename = 'dlb-' . time() . '-'. uniqid().'.' . $kyc_document_dlb->getClientOriginalExtension();
                    
					$kyc_document_dlb_type = 'DLB';
                    
					$awsFilePath = '/kyc/' . $kyc_document_dlb_filename;
					
					$s3update2 = $s3->put($awsFilePath, file_get_contents($kyc_document_dlb), 'private');
					
					//$s3update2 = $kyc_document_dlb->move($destinationPath, $kyc_document_dlb_filename);
					
                }
				
                if ($request->file('kyc_document_poa')) 
				{
                    $kyc_document_poa = $request->file('kyc_document_poa');
					
                    $kyc_document_poa_filename = 'poa-' . time() . '-'.uniqid().'.' . $kyc_document_poa->getClientOriginalExtension();
					
                    $kyc_document_poa_type = 'POA';
                    
					$awsFilePath = '/kyc/' . $kyc_document_poa_filename;
					
					$s3update3 = $s3->put($awsFilePath, file_get_contents($kyc_document_poa), 'private');
					
					/*
					$s3update3 = $kyc_document_poa->move($destinationPath, 
					$kyc_document_poa_filename);*/
					
                }
				
				
                if ($s3update1) 
				{
                    $check_kyc = array();
                    $check_kyc = DB::table('file_attachments')
                        ->select('file_attachments.id','file_attachments.filename')
                        ->where('file_attachments.user_id', Auth::user()->id)
                        ->where('file_attachments.type', $kyc_document_dlf_type)
                        ->first();
						
                    if (isset($check_kyc->id)) 
					{
                        $FileAttachments = FileAttachments::find($check_kyc->id);
						
						$deletePath = '/kyc/'.$FileAttachments->filename;
						
						if(\Storage::disk('s3')->exists($deletePath)) 
						{
							\Storage::disk('s3')->delete($deletePath);
						}
						
                        $FileAttachments->filename = $kyc_document_dlf_filename;
                        $FileAttachments->updated_at = date("Y-m-d H:i:s");
                        $FileAttachments->status = 0;
                        $FileAttachments->save();
                    } 
					else 
					{
                        $FileAttachments = new FileAttachments();
                        $FileAttachments->user_id = Auth::user()->id;
                        $FileAttachments->filename = $kyc_document_dlf_filename;
                        $FileAttachments->type = $kyc_document_dlf_type;
                        $FileAttachments->save();
                    }
                }
                if ($s3update2) 
				{
                    $check_kyc = array();
                    $check_kyc = DB::table('file_attachments')
                        ->select('file_attachments.id','file_attachments.filename')
                        ->where('file_attachments.user_id', Auth::user()->id)
                        ->where('file_attachments.type', $kyc_document_dlb_type)
                        ->first();
                    if (isset($check_kyc->id)) 
					{
                        $FileAttachments = FileAttachments::find($check_kyc->id);
						
						$deletePath = '/kyc/'.$FileAttachments->filename;
						if(\Storage::disk('s3')->exists($deletePath)) 
						{
							\Storage::disk('s3')->delete($deletePath);
						}						
                        
						$FileAttachments->filename = $kyc_document_dlb_filename;
                        $FileAttachments->updated_at = date("Y-m-d H:i:s");
                        $FileAttachments->status = 0;
                        $FileAttachments->save();
						
								
                    } 
					else 
					{
                        $FileAttachments = new FileAttachments();
                        $FileAttachments->user_id = Auth::user()->id;
                        $FileAttachments->filename = $kyc_document_dlb_filename;
                        $FileAttachments->type = $kyc_document_dlb_type;
                        $FileAttachments->save();
                    }
                }
                if ($s3update3) 
				{
                    $check_kyc = array();
                    $check_kyc = DB::table('file_attachments')
                        ->select('file_attachments.id','file_attachments.filename')
                        ->where('file_attachments.user_id', Auth::user()->id)
                        ->where('file_attachments.type', $kyc_document_poa_type)
                        ->first();
						
                    if(isset($check_kyc->id)) 
					{
                        $FileAttachments = FileAttachments::find($check_kyc->id);
						
						$deletePath = '/kyc/'.$FileAttachments->filename;
						if(\Storage::disk('s3')->exists($deletePath)) 
						{
							\Storage::disk('s3')->delete($deletePath);
						}
						
                        $FileAttachments->filename = $kyc_document_poa_filename;
                        $FileAttachments->updated_at = date("Y-m-d H:i:s");
                        $FileAttachments->status = 0;
                        $FileAttachments->save();
                    } 
					else 
					{
                        $FileAttachments = new FileAttachments();
                        $FileAttachments->user_id = Auth::user()->id;
                        $FileAttachments->filename = $kyc_document_poa_filename;
                        $FileAttachments->type = $kyc_document_poa_type;
                        $FileAttachments->save();
                    }
                }
                if ($s3update1 || $s3update2 || $s3update3) 
				{
                    $narration = Auth::user()->email . " has updated a new KYC documents";
                    $loggerRecord = [
                        'userId' => Auth::user()->id,
                        'message' => $narration,
                        'level' => 'INFO',
                        'context' => 'KYC_UPLOAD',
                    ];
                    LoggerHelper::writeDB($loggerRecord);
                    return redirect()->route('home.verification')->with('success', trans("message.kyc_upload_success"))->with('tabname', 'KYC');
                } 
				else 
				{
                    return redirect()->route('home.verification')->with('error', trans('message.error'))->with('tabname', 'KYC');
                }
            }
			else 
			{
                $kyc_data = __('message.kyc_preference_validation');
            }
        } 
		else if ($request->get('update_profile')) 
		{
            $validator = Validator::make($request->all(),
                [
                    'kyc_first_name' => 'required|alpha_spaces',
                    'kyc_last_name' => 'required|alpha_spaces',
                    'kyc_address1' => 'required',
                    'kyc_postal_code' => 'required|alpha_num|max:10',
                    'kyc_city' => 'required',
                    'kyc_country_code' => 'required|min:2|max:2',
                ],
                [
                    'kyc_first_name.required' => trans('auth.fNameRequired'),
                    'kyc_last_name.required' => trans('auth.lNameRequired'),
                    'kyc_address1.required' => trans('lendo.address1Required'),
                    'kyc_postal_code.required' => trans('lendo.postalCodeRequired'),
                    'kyc_city.required' => trans('lendo.cityRequired'),
                    'kyc_country_code.required' => trans('lendo.selectCountry'),
                ]);
            if ($validator->fails()) {
                return redirect()
                    ->route('home.verification')
                    ->withErrors($validator)
                    ->withInput($request->all())->with('tabname', 'KYC');
            } else {
                $user = User::find(Auth::user()->id);
                $user->first_name = $request->get('kyc_first_name');
                $user->last_name = $request->get('kyc_last_name');
                $user->address1 = $request->get('kyc_address1');
                $user->address2 = $request->get('kyc_address2');
                $user->postal_code = $request->get('kyc_postal_code');
                $user->city = $request->get('kyc_city');
                $user->country_code = $request->get('kyc_country_code');
                $user->save();
                return redirect()->route('home.verification')->with('success', trans("message.profile_update"))->with('tabname', 'KYC');
            }
        }
		
        $DLF_list_file = FileAttachments::where([['user_id', Auth::user()->id], ['type', 'DLF']])->orderBy('created_at', 'DESC')->first();
		
        $DLB_list_file = FileAttachments::where([['user_id', Auth::user()->id], ['type', 'DLB']])->orderBy('created_at', 'DESC')->first();
		
        $POA_list_file = FileAttachments::where([['user_id', Auth::user()->id], ['type', 'POA']])->orderBy('created_at', 'DESC')->first();
		
        $kyc_status = -1; // KYC not uploaded yet
        if (isset($DLF_list_file->status) && $DLF_list_file->status == 0 && isset($DLB_list_file->status) && $DLB_list_file->status == 0 && isset($POA_list_file->status) && $POA_list_file->status == 0) {
            $kyc_status = 0;
        } 
		else if (isset($DLF_list_file->status) && $DLF_list_file->status == 1 && isset($DLB_list_file->status) && $DLB_list_file->status == 1 && isset($POA_list_file->status) && $POA_list_file->status == 1) {
            $kyc_status = 1;
        } 
		else if (isset($DLF_list_file->status) && $DLF_list_file->status == 2 && isset($DLB_list_file->status) && $DLB_list_file->status == 2 && isset($POA_list_file->status) && $POA_list_file->status == 2) {
            $kyc_status = 2;
        }
		
        $Countries = Country::all();
		
        return view('verification', [
            'DLF_list_file' => $DLF_list_file,
            'DLB_list_file' => $DLB_list_file,
            'POA_list_file' => $POA_list_file,
            'aws_kyc_path' => $aws_kyc_path,
            'kyc_data' => $kyc_data,
            'kyc_status' => $kyc_status,
            'Countries' => $Countries,
        ]);
    }

    public function buyToken(Request $request)
    {				
		error_reporting(0);
        $currency = $request->get('type');
        $allowedType = array('BTC', 'ETH', 'BCH', 'BTH', 'LTC', 'ETC', 'XRP', 'DASH', 'no_currency');
        if (in_array($currency, $allowedType)) {
            $view_data['PAYMENT_UNITS'] = $currency;
        } elseif ($currency == 'EUR') {
            $view_data = [
                'PAYEE_ACCOUNT' => (config('perfectmoney.marchant_id')),
                'PAYEE_NAME' => (config('perfectmoney.marchant_name')),
                'PAYMENT_AMOUNT' => '',
                'PAYMENT_UNITS' => (config('perfectmoney.units')),
                'PAYMENT_ID' => (null),
                'PAYMENT_URL' => (config('perfectmoney.payment_url')),
                'NOPAYMENT_URL' => (config('perfectmoney.nopayment_url')),
            ];
            // Status URL
            $view_data['STATUS_URL'] = null;
            if (config('perfectmoney.status_url') || isset($data['STATUS_URL'])) {
                $view_data['STATUS_URL'] = (config('perfectmoney.status_url'));
            }
            // Payment URL Method
            $view_data['PAYMENT_URL_METHOD'] = null;
            if (config('perfectmoney.payment_url_method') || isset($data['PAYMENT_URL_METHOD'])) {
                $view_data['PAYMENT_URL_METHOD'] = (config('perfectmoney.payment_url_method'));
            }
            // No Payment URL Method
            $view_data['NOPAYMENT_URL_METHOD'] = null;
            if (config('perfectmoney.nopayment_url_method') || isset($data['NOPAYMENT_URL_METHOD'])) {
                $view_data['NOPAYMENT_URL_METHOD'] = (config('perfectmoney.nopayment_url_method'));
            }
            // Memo
            $view_data['MEMO'] = null;
            if (config('perfectmoney.suggested_memo') || isset($data['SUGGESTED_MEMO'])) {
                $view_data['MEMO'] = (config('perfectmoney.suggested_memo'));
            }
        } else {
            return redirect()
                ->to('/home')
                ->with('error', trans('message.error'));
        }
        //conversion rate
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
        $view_data['conversion_rate_data'] = $conversion_rate_data;
        $view_data['type'] = $currency;
        $allowedTypeBraveAPI = array('ETC', 'XRP', 'DASH');
        $__to_euro = 0;
        if (in_array($currency, $allowedTypeBraveAPI)) {
            $__to_euro = CommonHelper::get_brave_coin_rates(strtolower($currency), "EUR", 1);
        }
        $view_data['__to_euro'] = $__to_euro;
        $coin_base_rate_data = CommonHelper::get_coinbase_currency($currency);
        $view_data['coin_base_rate_data'] = $coin_base_rate_data;
        return view('buyTokenConfirmBTC', $view_data);
    }
	
	
	
    public function cryptomania(Request $request)
    {
		error_reporting(0);
        $currency = $request->get('type');
        $allowedType = array('BTC', 'ETH', 'BCH', 'BTH', 'LTC', 'ETC', 'XRP', 'DASH', 'no_currency');
        if (in_array($currency, $allowedType)) {
            $view_data['PAYMENT_UNITS'] = $currency;
        } elseif ($currency == 'EUR') {
            $view_data = [
                'PAYEE_ACCOUNT' => (config('perfectmoney.marchant_id')),
                'PAYEE_NAME' => (config('perfectmoney.marchant_name')),
                'PAYMENT_AMOUNT' => '',
                'PAYMENT_UNITS' => (config('perfectmoney.units')),
                'PAYMENT_ID' => (null),
                'PAYMENT_URL' => (config('perfectmoney.payment_url')),
                'NOPAYMENT_URL' => (config('perfectmoney.nopayment_url')),
            ];
            // Status URL
            $view_data['STATUS_URL'] = null;
            if (config('perfectmoney.status_url') || isset($data['STATUS_URL'])) {
                $view_data['STATUS_URL'] = (config('perfectmoney.status_url'));
            }
            // Payment URL Method
            $view_data['PAYMENT_URL_METHOD'] = null;
            if (config('perfectmoney.payment_url_method') || isset($data['PAYMENT_URL_METHOD'])) {
                $view_data['PAYMENT_URL_METHOD'] = (config('perfectmoney.payment_url_method'));
            }
            // No Payment URL Method
            $view_data['NOPAYMENT_URL_METHOD'] = null;
            if (config('perfectmoney.nopayment_url_method') || isset($data['NOPAYMENT_URL_METHOD'])) {
                $view_data['NOPAYMENT_URL_METHOD'] = (config('perfectmoney.nopayment_url_method'));
            }
            // Memo
            $view_data['MEMO'] = null;
            if (config('perfectmoney.suggested_memo') || isset($data['SUGGESTED_MEMO'])) {
                $view_data['MEMO'] = (config('perfectmoney.suggested_memo'));
            }
        } else {
            return redirect()
                ->to('/home')
                ->with('error', trans('message.error'));
        }
        //conversion rate
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
        $view_data['conversion_rate_data'] = $conversion_rate_data;
        $view_data['type'] = $currency;
        $allowedTypeBraveAPI = array('ETC', 'XRP', 'DASH');
        $__to_euro = 0;
        if (in_array($currency, $allowedTypeBraveAPI)) {
            $__to_euro = CommonHelper::get_brave_coin_rates(strtolower($currency), "EUR", 1);
        }
        $view_data['__to_euro'] = $__to_euro;
        $coin_base_rate_data = CommonHelper::get_coinbase_currency($currency);
        $view_data['coin_base_rate_data'] = $coin_base_rate_data;
        return view('cryptomaniabuytoken', $view_data);
    }
	
	
    
	public function bankTransfer(Request $request)
    {		
		//print_r(Transactions::get_new_bonus_percent_per_euro_worth(62911,10));die;
		
		error_reporting(0);
        $currency = 'EUR';
        $allowedType = array('BTC', 'ETH', 'BCH', 'BTH', 'LTC', 'ETC', 'XRP', 'DASH', 'no_currency');
        if (in_array($currency, $allowedType)) {
            $view_data['PAYMENT_UNITS'] = $currency;
        } elseif ($currency == 'EUR') {
            $view_data = [
                'PAYEE_ACCOUNT' => (config('perfectmoney.marchant_id')),
                'PAYEE_NAME' => (config('perfectmoney.marchant_name')),
                'PAYMENT_AMOUNT' => '',
                'PAYMENT_UNITS' => (config('perfectmoney.units')),
                'PAYMENT_ID' => (null),
                'PAYMENT_URL' => (config('perfectmoney.payment_url')),
                'NOPAYMENT_URL' => (config('perfectmoney.nopayment_url')),
            ];
            // Status URL
            $view_data['STATUS_URL'] = null;
            if (config('perfectmoney.status_url') || isset($data['STATUS_URL'])) {
                $view_data['STATUS_URL'] = (config('perfectmoney.status_url'));
            }
            // Payment URL Method
            $view_data['PAYMENT_URL_METHOD'] = null;
            if (config('perfectmoney.payment_url_method') || isset($data['PAYMENT_URL_METHOD'])) {
                $view_data['PAYMENT_URL_METHOD'] = (config('perfectmoney.payment_url_method'));
            }
            // No Payment URL Method
            $view_data['NOPAYMENT_URL_METHOD'] = null;
            if (config('perfectmoney.nopayment_url_method') || isset($data['NOPAYMENT_URL_METHOD'])) {
                $view_data['NOPAYMENT_URL_METHOD'] = (config('perfectmoney.nopayment_url_method'));
            }
            // Memo
            $view_data['MEMO'] = null;
            if (config('perfectmoney.suggested_memo') || isset($data['SUGGESTED_MEMO'])) {
                $view_data['MEMO'] = (config('perfectmoney.suggested_memo'));
            }
        } else {
            return redirect()
                ->to('/home')
                ->with('error', trans('message.error'));
        }
        //conversion rate
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
        $view_data['conversion_rate_data'] = $conversion_rate_data;
        $view_data['type'] = $currency;
        $allowedTypeBraveAPI = array('ETC', 'XRP', 'DASH');
        $__to_euro = 0;
        if (in_array($currency, $allowedTypeBraveAPI)) {
            $__to_euro = CommonHelper::get_brave_coin_rates(strtolower($currency), "EUR", 1);
        }
        $view_data['__to_euro'] = $__to_euro;
        $coin_base_rate_data = CommonHelper::get_coinbase_currency($currency);
        $view_data['coin_base_rate_data'] = $coin_base_rate_data;
        return view('bankTransfer', $view_data);
    }
	
	
	
	/** Credit Card Payment **/
	
	
	    public function creditcardpayment(Request $request)
    {		
		
		error_reporting(0);
        $currency = 'EUR';
        $allowedType = array('BTC', 'ETH', 'BCH', 'BTH', 'LTC', 'ETC', 'XRP', 'DASH', 'no_currency');
        if (in_array($currency, $allowedType)) {
            $view_data['PAYMENT_UNITS'] = $currency;
        } elseif ($currency == 'EUR') {
            $view_data = [
                'PAYEE_ACCOUNT' => (config('perfectmoney.marchant_id')),
                'PAYEE_NAME' => (config('perfectmoney.marchant_name')),
                'PAYMENT_AMOUNT' => '',
                'PAYMENT_UNITS' => (config('perfectmoney.units')),
                'PAYMENT_ID' => (null),
                'PAYMENT_URL' => (config('perfectmoney.payment_url')),
                'NOPAYMENT_URL' => (config('perfectmoney.nopayment_url')),
            ];
            // Status URL
            $view_data['STATUS_URL'] = null;
            if (config('perfectmoney.status_url') || isset($data['STATUS_URL'])) {
                $view_data['STATUS_URL'] = (config('perfectmoney.status_url'));
            }
            // Payment URL Method
            $view_data['PAYMENT_URL_METHOD'] = null;
            if (config('perfectmoney.payment_url_method') || isset($data['PAYMENT_URL_METHOD'])) {
                $view_data['PAYMENT_URL_METHOD'] = (config('perfectmoney.payment_url_method'));
            }
            // No Payment URL Method
            $view_data['NOPAYMENT_URL_METHOD'] = null;
            if (config('perfectmoney.nopayment_url_method') || isset($data['NOPAYMENT_URL_METHOD'])) {
                $view_data['NOPAYMENT_URL_METHOD'] = (config('perfectmoney.nopayment_url_method'));
            }
            // Memo
            $view_data['MEMO'] = null;
            if (config('perfectmoney.suggested_memo') || isset($data['SUGGESTED_MEMO'])) {
                $view_data['MEMO'] = (config('perfectmoney.suggested_memo'));
            }
        } else {
            return redirect()
                ->to('/home')
                ->with('error', trans('message.error'));
        }
        //conversion rate
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
        $view_data['conversion_rate_data'] = $conversion_rate_data;
        $view_data['type'] = $currency;
        $allowedTypeBraveAPI = array('ETC', 'XRP', 'DASH');
        $__to_euro = 0;
        if (in_array($currency, $allowedTypeBraveAPI)) {
            $__to_euro = CommonHelper::get_brave_coin_rates(strtolower($currency), "EUR", 1);
        }
        $view_data['__to_euro'] = $__to_euro;
        $coin_base_rate_data = CommonHelper::get_coinbase_currency($currency);
        $view_data['coin_base_rate_data'] = $coin_base_rate_data;
        return view('creditcardpayment', $view_data);
    }
	
	
	/** Credit Card Payment **/
	
	
	    public function creditcardpaymentsuccess(Request $request)
    {		
        $dataForView = array();	
		if($request->isMethod('post'))
		{
		//conversion rate
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
		
		$minimumBuyCurrency = $conversion_rate_data['Minimum-Buy-EUR'][0];
        $maximumBuyCurrency = $conversion_rate_data['Maximum-Buy-EUR'][0];
		
		if($_POST['PAYMENT_AMOUNT'] >= $minimumBuyCurrency && $_POST['PAYMENT_AMOUNT'] <= $maximumBuyCurrency)
		{
		$user_id = Auth::user()->id;
		
		 
		 
		$coin_base_rate_data = CommonHelper::get_coinbase_currency('EUR');
		$Conversion_to_BTC = $coin_base_rate_data['data']['rates']['BTC'];
		$BTC_amount = $Conversion_to_BTC * $_POST['PAYMENT_AMOUNT'];
		
		$payment_unit = 'BTC';	
		/* pay with coinpayment */
		  try {
            $coinpayment = new Coinpayments();
            $apiResult = $coinpayment->createTransactionSimple(
                $BTC_amount,
                $payment_unit,
                $payment_unit,
                ['buyer_email' => Auth::user()->email]
            );
            if (!isset($apiResult['response']['error']) || empty($apiResult['response']['error']) ||
                !isset($apiResult['response']['result']) || empty($apiResult['response']['result']) ||
                $apiResult['response']['error'] != 'ok') {
                return redirect()
                    ->back()
                    ->withErrors(['PAYMENT_AMOUNT' => $apiResult['response']['error']])
                    ->withInput($request->all());
            }
            
            $ledger = 'ELT';
            $value = $_POST['convertedRateAmount'];
            $ELTVal = $_POST['convertedRateAmount'];
			$phaseId = NULL;
			$transaction_id = $apiResult['response']['result']['txn_id'];
         
         
			$description = 'Converted unit BTC : ' .$BTC_amount .'against EUR :'.@$request->get('PAYMENT_AMOUNT') . ' with ELT ' . $ELTVal . ' Wallet:local Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s") . ' QR : ' . $apiResult['response']['result']['qrcode_url'] . ' Status_url : ' . $apiResult['response']['result']['status_url'] . ' address : ' . $apiResult['response']['result']['address'];
            
			Transactions::createTransactionWithTermCurrency($user_id, $ledger, $value, $description, 2, $transaction_id, $phaseId, $apiResult['response']['result']['address'], $payment_unit, $BTC_amount, 'credit-card');
           
		   $record = [
                'message' => 'Username ' . Auth::user()->email . ' coinpayment action',
                'level' => 'INFO',
                'context' => 'creditcard',
                'extra' => [
                    'coinpayment_response' => json_encode($apiResult)
                ]
            ];
            
			
			LoggerHelper::writeDB($record);
			$dataForView['address'] = $apiResult['response']['result']['address'];
			$dataForView['amount'] =  $_POST['PAYMENT_AMOUNT'];
			$dataForView['elt_amount'] =  $ELTVal;
			return view('credit_paymentsuccess', $dataForView);
        
		} catch (Exception $exception) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => $exception->getMessage()])
                ->withInput($request->all());
        }
		
		}
		else
		{
			
			$error_message = trans('message.EURmin').$minimumBuyCurrency.trans('message.EURmax').$maximumBuyCurrency;
			
			return redirect('creditcardpayment')->withErrors(['PAYMENT_AMOUNT' => $error_message])
                ->withInput($request->all());
			
		}
		
		}
		
		
		
		
    }
	
	
	
	 	/**
     * Load proforma detail
     */
    public function proforma_detail($number =  null,Request $request)
    {
		$dataForView = array();
		if($request->isMethod('post'))
		{
		
		//conversion rate
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
		
		$minimumBuyCurrency = $conversion_rate_data['Minimum-Buy-EUR'][0];
        $maximumBuyCurrency = $conversion_rate_data['Maximum-Buy-EUR'][0];
		
		if($_POST['PAYMENT_AMOUNT'] >= $minimumBuyCurrency && $_POST['PAYMENT_AMOUNT'] <= $maximumBuyCurrency)
		{
		$login_user_id = Auth::user()->id;
		$invoice_data = array();
		$invoice_data['reference_no'] = CommonHelper::generateProformaNumber();
		$invoice_data['status'] = 0;
		$invoice_data['user_id'] = $login_user_id;
		$invoice_data['elt_amount'] = $_POST['convertedRateAmount'];
		$invoice_data['token_price'] = 1/$conversion_rate_data['Conversion-EUR-ELT'][0];
		$invoice_data['currency'] = $_POST['PAYMENT_UNITS'];
		$invoice_data['currency_amount'] = $_POST['PAYMENT_AMOUNT'];
		$invoice_data['description'] = 'By system';
		$invoice_data['created_at'] = date("Y-m-d H:i:s");
		Transactions::add_row_in_table("proforma_invoices",$invoice_data);
		$number = $invoice_data['reference_no'];
		}
		else
		{
			
			$error_message = trans('message.EURmin').$minimumBuyCurrency.trans('message.EURmax').$maximumBuyCurrency;
			
			return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => $error_message])
                ->withInput($request->all());
			
		}
		//return redirect()->back()->with('message','Proforma has been created successfully!')->with('number',$number);
		}
		
		
		$exchange_value_in_euro='';
		$invoice_detail = Transactions::get_performa_detail($number);
		$dataForView['invoice_detail'] = $invoice_detail;
		$dataForView['exchange_value_in_euro'] = $exchange_value_in_euro;
		$dataForView['invoice_bonus_row'] = array();
		return view('proforma_detail', $dataForView);
    }  
 
	
	
	public function buyTokenDeal(Request $request)
    {
		error_reporting(0);
		
        $currency = $request->get('type');
		
		$currency2 = $request->get('type2');
			
		$view_data = 
		[
			'PAYEE_ACCOUNT' => (config('perfectmoney.marchant_id')),
			'PAYEE_NAME' => (config('perfectmoney.marchant_name')),
			'PAYMENT_AMOUNT' => '',
			'PAYMENT_UNITS' => (config('perfectmoney.units')),
			'PAYMENT_ID' => (null),
			'PAYMENT_URL' => (config('perfectmoney.payment_url')),
			'NOPAYMENT_URL' => (config('perfectmoney.nopayment_url')),
		];
		
		// Status URL
		$view_data['STATUS_URL'] = null;
		if (config('perfectmoney.status_url') || isset($data['STATUS_URL'])) {
			$view_data['STATUS_URL'] = (config('perfectmoney.status_url'));
		}
		
		// Payment URL Method
		$view_data['PAYMENT_URL_METHOD'] = null;
		if (config('perfectmoney.payment_url_method') || isset($data['PAYMENT_URL_METHOD'])) {
			$view_data['PAYMENT_URL_METHOD'] = (config('perfectmoney.payment_url_method'));
		}

		// No Payment URL Method
		$view_data['NOPAYMENT_URL_METHOD'] = null;
		if (config('perfectmoney.nopayment_url_method') || isset($data['NOPAYMENT_URL_METHOD'])) {
			$view_data['NOPAYMENT_URL_METHOD'] = (config('perfectmoney.nopayment_url_method'));
		}
		
		// Memo
		$view_data['MEMO'] = null;
		if (config('perfectmoney.suggested_memo') || isset($data['SUGGESTED_MEMO'])) {
			$view_data['MEMO'] = (config('perfectmoney.suggested_memo'));
		}
		
		//conversion rate
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
		
		$view_data['conversion_rate_data'] = $conversion_rate_data;
		
		$nexo_to_elt = $this->getCoinConversionIn_ELT_WithFee("NEXO");
		$salt_to_elt = $this->getCoinConversionIn_ELT_WithFee("SALT");
		$orme_to_elt = $this->getCoinConversionIn_ELT_WithFee("ORME");
		
		$view_data['nexo_to_elt'] = $nexo_to_elt;
		$view_data['salt_to_elt'] = $salt_to_elt;
		$view_data['orme_to_elt'] = $orme_to_elt;
		
		$view_data['elt_to_nexo'] = $this->balance_format(1/$nexo_to_elt,6);
		$view_data['elt_to_salt'] = $this->balance_format(1/$salt_to_elt,6);
		$view_data['elt_to_orme'] = $this->balance_format(1/$orme_to_elt,6);

		$view_data['btc_to_elt'] = $this->getConversionInELTWithFee("BTC",1);
		$view_data['elt_to_btc'] = $this->balance_format(1/$view_data['btc_to_elt'],6);
		
		$view_data['bch_to_elt'] = $this->getConversionInELTWithFee("BCH",1);
		$view_data['elt_to_bch'] = $this->balance_format(1/$view_data['bch_to_elt'],6);
		
		$view_data['eth_to_elt'] = $this->getConversionInELTWithFee("ETH",1);
		$view_data['elt_to_eth'] = $this->balance_format(1/$view_data['eth_to_elt'],6);
		
		$view_data['ltc_to_elt'] = $this->getConversionInELTWithFee("LTC",1);
		$view_data['elt_to_ltc'] = $this->balance_format(1/$view_data['ltc_to_elt'],6);
		
		$view_data['etc_to_elt'] = $this->getConversionInELTWithFee("ETC",1);
		$view_data['elt_to_etc'] = $this->balance_format(1/$view_data['etc_to_elt'],6);
		
		$view_data['dash_to_elt'] = $this->getConversionInELTWithFee("DASH",1);
		$view_data['elt_to_dash'] = $this->balance_format(1/$view_data['dash_to_elt'],6);
		
		$view_data['xrp_to_elt'] = $this->getConversionInELTWithFee("XRP",1);
		$view_data['elt_to_xrp'] = $this->balance_format(1/$view_data['xrp_to_elt'],6);
		
		
        $view_data['type'] = $currency;
		$view_data['type2'] = $currency2;
		
        return view('buyTokenConfirmDeal', $view_data);
    }
	
	public function buyTokenDealAjax(Request $request)
    {
		error_reporting(0);
		
        $currency = $request->get('type');
		
		$currency2 = $request->get('type2');
			
		$view_data = 
		[
			'PAYEE_ACCOUNT' => (config('perfectmoney.marchant_id')),
			'PAYEE_NAME' => (config('perfectmoney.marchant_name')),
			'PAYMENT_AMOUNT' => '',
			'PAYMENT_UNITS' => (config('perfectmoney.units')),
			'PAYMENT_ID' => (null),
			'PAYMENT_URL' => (config('perfectmoney.payment_url')),
			'NOPAYMENT_URL' => (config('perfectmoney.nopayment_url')),
		];
		
		// Status URL
		$view_data['STATUS_URL'] = null;
		if (config('perfectmoney.status_url') || isset($data['STATUS_URL'])) {
			$view_data['STATUS_URL'] = (config('perfectmoney.status_url'));
		}
		
		// Payment URL Method
		$view_data['PAYMENT_URL_METHOD'] = null;
		if (config('perfectmoney.payment_url_method') || isset($data['PAYMENT_URL_METHOD'])) {
			$view_data['PAYMENT_URL_METHOD'] = (config('perfectmoney.payment_url_method'));
		}

		// No Payment URL Method
		$view_data['NOPAYMENT_URL_METHOD'] = null;
		if (config('perfectmoney.nopayment_url_method') || isset($data['NOPAYMENT_URL_METHOD'])) {
			$view_data['NOPAYMENT_URL_METHOD'] = (config('perfectmoney.nopayment_url_method'));
		}
		
		// Memo
		$view_data['MEMO'] = null;
		if (config('perfectmoney.suggested_memo') || isset($data['SUGGESTED_MEMO'])) {
			$view_data['MEMO'] = (config('perfectmoney.suggested_memo'));
		}
		
		//conversion rate
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
		
		$view_data['conversion_rate_data'] = $conversion_rate_data;
		
		$nexo_to_elt = $this->getCoinConversionIn_ELT_WithFee("NEXO");
		$salt_to_elt = $this->getCoinConversionIn_ELT_WithFee("SALT");
		$orme_to_elt = $this->getCoinConversionIn_ELT_WithFee("ORME");
		
		$view_data['nexo_to_elt'] = $nexo_to_elt;
		$view_data['salt_to_elt'] = $salt_to_elt;
		$view_data['orme_to_elt'] = $orme_to_elt;
		
		$view_data['elt_to_nexo'] = $this->balance_format(1/$nexo_to_elt,6);
		$view_data['elt_to_salt'] = $this->balance_format(1/$salt_to_elt,6);
		$view_data['elt_to_orme'] = $this->balance_format(1/$orme_to_elt,6);

		$view_data['btc_to_elt'] = $this->getConversionInELTWithFee("BTC",1);
		$view_data['elt_to_btc'] = $this->balance_format(1/$view_data['btc_to_elt'],6);
		
		$view_data['bch_to_elt'] = $this->getConversionInELTWithFee("BCH",1);
		$view_data['elt_to_bch'] = $this->balance_format(1/$view_data['bch_to_elt'],6);
		
		$view_data['eth_to_elt'] = $this->getConversionInELTWithFee("ETH",1);
		$view_data['elt_to_eth'] = $this->balance_format(1/$view_data['eth_to_elt'],6);
		
		$view_data['ltc_to_elt'] = $this->getConversionInELTWithFee("LTC",1);
		$view_data['elt_to_ltc'] = $this->balance_format(1/$view_data['ltc_to_elt'],6);
		
		$view_data['etc_to_elt'] = $this->getConversionInELTWithFee("ETC",1);
		$view_data['elt_to_etc'] = $this->balance_format(1/$view_data['etc_to_elt'],6);
		
		$view_data['dash_to_elt'] = $this->getConversionInELTWithFee("DASH",1);
		$view_data['elt_to_dash'] = $this->balance_format(1/$view_data['dash_to_elt'],6);
		
		$view_data['xrp_to_elt'] = $this->getConversionInELTWithFee("XRP",1);
		$view_data['elt_to_xrp'] = $this->balance_format(1/$view_data['xrp_to_elt'],6);
		
		
        $view_data['type'] = $currency;
		$view_data['type2'] = $currency2;

		//echo "<pre>";print_r($view_data);die;
        return view('buyTokenDealAjax', $view_data);
    }
	
	public function buyTokenDealThanks(Request $request)
    {
		$view_data = array();
		$view_data['request'] = array();
		$view_data['result'] = array();
		$view_data['you_receive'] = '';
		$view_data['exchange_rate'] = '';
		$view_data['type'] = '';
			
		if(isset($_GET['apiResult'])){
			$apiResult = unserialize($_GET['apiResult']);
			$view_data['request'] = $apiResult['request'];
			$view_data['result'] = $apiResult['response']['result'];
			$view_data['you_receive'] = $_GET['ELTVal'];
			$view_data['type'] = $_GET['type'];
		}
        return view('buyTokenConfirmDealThanks', $view_data);
    }
	
	public function buyTokenDealCoinThanks(Request $request)
    {
		$view_data = array();
		$view_data['request'] = array();
		$view_data['result'] = array();
		$view_data['you_receive'] = '';
		$view_data['exchange_rate'] = '';
		$view_data['type'] = '';
			
		if(isset($_GET['apiResult'])){
			$apiResult = unserialize($_GET['apiResult']);
			$view_data['request'] = $apiResult['request'];
			$view_data['result'] = $apiResult['response']['result'];
			$view_data['you_receive'] = $_GET['ELTVal'];
			$view_data['type'] = $_GET['type'];
		}
        return view('buyTokenConfirmDealCoinThanks', $view_data);
    }
	
	
	public function buyTokenAjax(Request $request)
    {
        $currency = $request->get('type');
		
        $allowedType = array('BTC', 'ETH', 'BCH', 'BTH', 'LTC', 'ETC', 'XRP', 'DASH', 'no_currency');
		
        if (in_array($currency, $allowedType)) 
		{
            $view_data['PAYMENT_UNITS'] = $currency;
        } 
		elseif ($currency == 'EUR') 
		{
            
        } 
		else 
		{
            return redirect()
                ->to('/home')
                ->with('error', trans('message.error'));
        }
		
		$view_data = 
		[
			'PAYEE_ACCOUNT' => (config('perfectmoney.marchant_id')),
			'PAYEE_NAME' => (config('perfectmoney.marchant_name')),
			'PAYMENT_AMOUNT' => '',
			'PAYMENT_UNITS' => (config('perfectmoney.units')),
			'PAYMENT_ID' => (null),
			'PAYMENT_URL' => (config('perfectmoney.payment_url')),
			'NOPAYMENT_URL' => (config('perfectmoney.nopayment_url')),
		];
		
		// Status URL
		$view_data['STATUS_URL'] = null;
		if (config('perfectmoney.status_url') || isset($data['STATUS_URL'])) {
			$view_data['STATUS_URL'] = (config('perfectmoney.status_url'));
		}
		
		// Payment URL Method
		$view_data['PAYMENT_URL_METHOD'] = null;
		if (config('perfectmoney.payment_url_method') || isset($data['PAYMENT_URL_METHOD'])) {
			$view_data['PAYMENT_URL_METHOD'] = (config('perfectmoney.payment_url_method'));
		}
		
		// No Payment URL Method
		$view_data['NOPAYMENT_URL_METHOD'] = null;
		if (config('perfectmoney.nopayment_url_method') || isset($data['NOPAYMENT_URL_METHOD'])) {
			$view_data['NOPAYMENT_URL_METHOD'] = (config('perfectmoney.nopayment_url_method'));
		}
		
		// Memo
		$view_data['MEMO'] = null;
		if (config('perfectmoney.suggested_memo') || isset($data['SUGGESTED_MEMO'])) {
			$view_data['MEMO'] = (config('perfectmoney.suggested_memo'));
		}
		
        //conversion rate
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }		
        $view_data['conversion_rate_data'] = $conversion_rate_data;
        $view_data['type'] = $currency;
        $allowedTypeBraveAPI = array('ETC', 'XRP', 'DASH');
        $__to_euro = 0;
        if (in_array($currency, $allowedTypeBraveAPI)) {
            $__to_euro = CommonHelper::get_brave_coin_rates(strtolower($currency), "EUR", 1);
        }
        $view_data['__to_euro'] = $__to_euro;
        $coin_base_rate_data = CommonHelper::get_coinbase_currency($currency);
        $view_data['coin_base_rate_data'] = $coin_base_rate_data;
        return view('buyTokenConfirmAjax', $view_data);
    }
	
	public function getUserBuyCurrencyInfo(Request $request)
    {
		$userInfo = Auth::user();
		$response = array();
		$response['status'] = "0";
		$buying_currency = $_POST['buying_currency'];
        $allowedType = array('BTC', 'ETH', 'BCH', 'BTH', 'LTC', 'EUR', 'ETC', 'XRP', 'DASH');
        if(in_array($buying_currency, $allowedType))
		{
			$fees = 0;			
			$__to_euro = 0;	
			$response['status'] = "1";
			$response['type'] = $buying_currency;
			$response['wallet_balance'] = number_format((float)CommonHelper::getAppropriateWalletBalance($userInfo, $buying_currency), 6, '.', '');
			$response['wallet_balance_display'] = CommonHelper::format_wallet_balance(CommonHelper::getAppropriateWalletBalance($userInfo, $buying_currency), 6);
			$conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
			$conversion_rate_data = array();			
			if (!$conversion_rates->isEmpty()) {
				foreach ($conversion_rates as $key => $rates_minimume_values) {
					$conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
					$conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
				}
			}
			
			$EUR_to_ELT = $conversion_rate_data['Conversion-EUR-ELT'][0];
			$response['EUR_to_ELT'] = $EUR_to_ELT;
			$allowedTypeBraveAPI = array('ETC', 'XRP', 'DASH');
			$response['Minimum_Buy_Amount'] = number_format($conversion_rate_data['Minimum-Buy-'.$buying_currency][0], 6);
			$response['Maximum_Buy_Amount'] = $conversion_rate_data['Maximum-Buy-'.$buying_currency][0];
			if(in_array($buying_currency, $allowedTypeBraveAPI)) // lives rates from bravecoin-api
			{
				$__to_euro = CommonHelper::get_brave_coin_rates(strtolower($buying_currency), "EUR", 1);
				$Conversion_to_EUR = $__to_euro;
				$Conversion_to_ELT = $Conversion_to_EUR * $EUR_to_ELT;
				$to_type_currency = $Conversion_to_ELT;
				$ELT_to_CURRENCY = 1 / $Conversion_to_ELT;
				$fees = $conversion_rate_data['Conversion-'.$buying_currency.'-EUR-Fee'][0];
				$Conversion_to_ELT = $ELT_to_CURRENCY + ($ELT_to_CURRENCY * $fees / 100);
				$response['Conversion_to_ELT'] = number_format(1/$Conversion_to_ELT,6);
				$response['Conversion_to_ELT_Display'] = str_replace(",","",number_format(1/$Conversion_to_ELT,6));
				$response['Conversion_to_Crypto'] = number_format($Conversion_to_ELT,6);
			}
			elseif($buying_currency == 'EUR') // EURO conversion
			{
				$Conversion_to_ELT = $conversion_rate_data['Conversion-EUR-ELT'][0];
				$to_type_currency = $Conversion_to_ELT;
				$ELT_to_EUR = 1 / $Conversion_to_ELT;
				$to_elt_currency = $ELT_to_EUR;
				$Conversion_to_ELT = $ELT_to_EUR + ($ELT_to_EUR * $fees / 100);
				$response['Conversion_to_ELT'] = number_format(1/$Conversion_to_ELT,6);
				$response['Conversion_to_ELT_Display'] = str_replace(",","",number_format(1/$Conversion_to_ELT,6));
				$response['Conversion_to_Crypto'] = number_format($Conversion_to_ELT,6);
			}
			else // lives rates from coinbase-api
			{
				$coin_base_rate_data = CommonHelper::get_coinbase_currency($buying_currency);
				$Conversion_to_EUR = $coin_base_rate_data['data']['rates']['EUR'];
				$response['Conversion_to_EUR'] = $Conversion_to_EUR;				
				$Conversion_to_ELT = $Conversion_to_EUR * $EUR_to_ELT;
				$response['Conversion_to_EUR_ELT'] = $Conversion_to_ELT;				
				$ELT_to_CURRENCY = 1 / $Conversion_to_ELT;
				$fees = $conversion_rate_data['Conversion-'.$buying_currency.'-EUR-Fee'][0];
				$Conversion_to_ELT = $ELT_to_CURRENCY + ($ELT_to_CURRENCY * $fees / 100);				
				$response['Conversion_to_ELT'] = number_format(1/$Conversion_to_ELT,6);
				$response['Conversion_to_ELT_Display'] = str_replace(",","",number_format(1/$Conversion_to_ELT,6));
				$response['Conversion_to_Crypto'] = number_format($Conversion_to_ELT,6);
			}			
			$response['you_wallet_balance_text'] = trans('lendo.YourCurrentWalletBalance',['balance'=>$response['wallet_balance'],'currency'=>$buying_currency]);
			$response['fees'] = $fees;
			$response['__to_euro'] = $__to_euro;
		}
		else
		{
			
		}
		echo json_encode($response);exit;
	}
	
	/* Buy Token from ICO Local Wallet */
    public function buyTokenConfirm(Request $request)
    {		
		error_reporting(0);
        $payment_amount=$request->get('PAYMENT_AMOUNT');
        $payment_units=$request->get('PAYMENT_UNITS');
        if (!$payment_amount || !$payment_units) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.error')])
                ->withInput($request->all());
        }
        $user = Auth::user();
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
        
        /* For multiple phases calculations - Select active phases data */
        $activePhaseData = PhaseHelper::getCurrentPhase();
        if (!isset($activePhaseData)) {
            $record = [
                'message' => 'Username ' . $user->email . ' Phase not activated ',
                'level' => 'ERROR',
                'context' => 'perfectMoney'
            ];
            LoggerHelper::writeDB($record);
            return redirect()
                ->to('/home')
                ->with('error', trans('lendo.ELT_tokens_not_available'));
        }

        if (isset($activePhaseData->id) && !empty($activePhaseData->id)) {
            $phaseId = $activePhaseData->id;
        } else {
            $phaseId = NULL;
        }
		
        if(!isset($conversion_rate_data) || empty($conversion_rate_data)) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.DataTableError')])
                ->withInput($request->all());
        }
        
        $ELTVal = $this->getConversionInELTWithFee($payment_units, $payment_amount);
		
        /*Generic config setting for currency*/
        $currency = $payment_units;
        $minimumBuyCurrency = $conversion_rate_data['Minimum-Buy-' . $currency][0];
        $maximumBuyCurrency = $conversion_rate_data['Maximum-Buy-' . $currency][0];
        $txtBalance = $currency . '_balance';
        $usrAccountBalance = CommonHelper::getAppropriateWalletBalance($user, $currency);
        if ($ELTVal < $conversion_rate_data['Minimum-Buy-ELT'][0] || $ELTVal > $conversion_rate_data['Maximum-Buy-ELT'][0]) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('lendo.validateMinMaxCurrency', ['unit' => 'ELT', 'greaterThan' => $conversion_rate_data['Minimum-Buy-ELT'][0], 'lessThan' => $conversion_rate_data['Maximum-Buy-ELT'][0]])])
                ->withInput($request->all())->with("ELT_Error", "true");
        }
        
        if ($payment_amount < $minimumBuyCurrency || $payment_amount > $maximumBuyCurrency) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.validateMinMax', ['unit' => $payment_units, 'greaterThan' => $minimumBuyCurrency, 'lessThan' => $maximumBuyCurrency])])
                ->withInput($request->all());
        }
        
        if ($usrAccountBalance < $payment_amount) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.insufficientBalance')])->withInput($request->all());
        }
        
        $user_id = $user->id;
        $ledger = $payment_units;
        $value = '-' . $payment_amount;
        $ref_transaction_id = $transaction_id = uniqid();
        $description = 'Converted unit ' . $payment_units . ' ' . @$payment_amount . ' into ELT ' . $ELTVal . ' wallet: ico-wallet Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
		
		$user->subtractValue($txtBalance, $payment_amount);
		$user->addValue('ELT_balance', $ELTVal);
		$user->save();
		
		/* main transaction */
		$Transaction = Transactions::createTransactionWithReference($user_id, 'ELT', $ELTVal, $description, 1, $transaction_id, $phaseId, NULL, $payment_units, $payment_amount, NULL, 'ico-wallet');
		
        $record1 = [
            'message' => 'Username ' . $user->email . '  Buy ELT Token :'.$ELTVal.' using Currency:' . $payment_units.' Amount: '.$payment_amount,
            'level' => 'INFO',
            'context' => 'wallet'
        ];
        LoggerHelper::writeDB($record1);
        
        /* Bonus calculation on multiple phase Get phase bonus percentage*/
        $bonus = $activePhaseData->phase_bonus;
        $bonusToken = Phases::calculateBonusToken($ELTVal, $bonus);
        $ledger = 'ELT';
        $value = round($bonusToken, config('constants.ELT_PRECISION'));
        $phase_transaction_id = uniqid();
        $description = 'Got bonus ELT ' . round($bonusToken, config('constants.ELT_PRECISION')) . '  on purchasing of ELT ' . $ELTVal . ' Payment id : ' . @$phase_transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
		$Transaction = Transactions::createTransactionWithReference($user_id, $ledger, $value, $description, 1, $phase_transaction_id, $phaseId, NULL, NULL, 0, $ref_transaction_id, 'bonus');
        $user->addValue('ELT_balance', $value);
        $user->save();
		
        $record2 = [
            'message' => 'Username ' . $user->email . ' transaction has created for adding ELT in bonus: ,   ' . $value,
            'level' => 'INFO',
            'context' => 'wallet'
        ];
        LoggerHelper::writeDB($record2);
        
		/**** Distribute Parent Referral Bonus **/
        $this->distributeBonusToParentReferrals($user->id, $txtBalance, $payment_units, $payment_amount, $ref_transaction_id, $phaseId, $conversion_rate_data, $ELTVal);
		/**** Distribute Parent Referral Bonus **/
		
        return redirect()->route('buy.token', ['type' => $payment_units])->with('success', trans('message.transactionSuccess'));
    }
	
	
	/* Buy Token from Local Wallet */
    public function buyTokenConfirmDeal(Request $request)
    {
        $payment_amount=$request->get('PAYMENT_AMOUNT');
		$payment_amount2=$request->get('PAYMENT_AMOUNT2');
        $payment_units=$request->get('PAYMENT_UNITS');
		$payment_units2=$request->get('PAYMENT_UNITS2');

        if(!$payment_amount || !$payment_units) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.error')])
                ->withInput($request->all());
        }
		
		if(!$payment_amount2 || !$payment_units2) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT2' => trans('message.error')])
                ->withInput($request->all());
        }
        
        $user = Auth::user();
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
        
        /* For multiple phases calculations - Select active phases data */
        $activePhaseData = PhaseHelper::getCurrentPhase();

        if (!isset($activePhaseData)) {
            $record = [
                'message' => 'Username ' . $user->email . ' Phase not activated ',
                'level' => 'ERROR',
                'context' => 'perfectMoney'
            ];
            LoggerHelper::writeDB($record);
            return redirect()
                ->to('/home')
                ->with('error', trans('lendo.ELT_tokens_not_available'));
        }

        if (isset($activePhaseData->id) && !empty($activePhaseData->id)) {
            $phaseId = $activePhaseData->id;
        } else {
            $phaseId = NULL;
        }

        if (!isset($conversion_rate_data) || empty($conversion_rate_data)) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.DataTableError')])
                ->withInput($request->all());
        }
        
        //$ELTVal = $this->getConversionInELTWithFee($payment_units, $payment_amount);
		$ELTVal = $_POST['convertedRateAmount']/2;
		
		
        /*Generic config setting for currency*/
        $currency = $payment_units;
		$currency2 = $payment_units2;
		
        $minimumBuyCurrency = $conversion_rate_data['Minimum-Buy-' . $currency][0];
        $maximumBuyCurrency = $conversion_rate_data['Maximum-Buy-' . $currency][0];
		
        $txtBalance = $currency . '_balance';
        $usrAccountBalance = CommonHelper::getAppropriateWalletBalance($user, $currency);
        
		$minimumBuyCoin = $conversion_rate_data['Minimum-Buy-' . $currency2][0];
    
		
		/*if (($ELTVal*2) < $conversion_rate_data['Minimum-Buy-ELT'][0] || $ELTVal > $conversion_rate_data['Maximum-Buy-ELT'][0]) {*/
		
		$Minimum_Buy_ELT = 5000;
        if( $_POST['convertedRateAmount'] < $Minimum_Buy_ELT ) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.validateMinCoin', ['unit' => 'ELT', 'greaterThan' => $Minimum_Buy_ELT ] ) ] )
                ->withInput($request->all())->with("ELT_Error", "true");
        }
		
        if ($payment_amount < $minimumBuyCurrency || $payment_amount > $maximumBuyCurrency) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.validateMinMax', ['unit' => $payment_units, 'greaterThan' => $minimumBuyCurrency, 'lessThan' => $maximumBuyCurrency])])
                ->withInput($request->all());
        }
		
		if ($payment_amount2 < $minimumBuyCoin) 
		{
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT2' => trans('message.validateMinCoin', ['unit' => $payment_units2, 'greaterThan' => $minimumBuyCoin ] ) ] )
                ->withInput($request->all());
        }
		
        
        if ($usrAccountBalance < $payment_amount) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.insufficientBalance')])->withInput($request->all());
        }
        
        $user_id = $user->id;
        $ledger = $payment_units;
        $value = '-' . $payment_amount;
        $ref_transaction_id = $transaction_id = 'lendo_'.uniqid();
		$transaction_currency = $transaction_id;
        $description = 'Converted unit ' . $payment_units . ' ' . @$payment_amount . ' into ELT ' . $ELTVal . ' wallet: ico-wallet Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
		Transactions::createTransactionWithReference($user_id, 'ELT', $ELTVal, $description, 1, $transaction_id, $phaseId, NULL, $payment_units, $value, NULL, 'ico-wallet');
        $user->subtractValue($txtBalance, $payment_amount);
		$user->addValue('ELT_balance', $ELTVal);
        $userUpdate = $user->save();
		
        $record1 = [
            'message' => 'Username ' . $user->email . '  Buy ELT Token '.$ELTVal.' using ' . $payment_units.' amount: '.$payment_amount,
            'level' => 'INFO',
            'context' => 'wallet'
        ];
        LoggerHelper::writeDB($record1);
		
		/* Coin transaction */
		$transaction_id = 'lendo_'.uniqid();
		$description = 'Converted unit ' . $payment_units2 . ' ' . @$payment_amount2 . ' into ELT ' . $ELTVal . ' Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
		$Transaction = Transactions::createTransactionWithReference($user_id, 'ELT', $ELTVal, $description, 2, $transaction_id, $phaseId, NULL, $payment_units2, $payment_amount2, $ref_transaction_id, 'coins');
		$transaction_coin = $transaction_id;
		
        /* Bonus calculation on multiple phase Get phase bonus percentage*/
        $bonus = $activePhaseData->phase_bonus;
        $bonusToken = Phases::calculateBonusToken($ELTVal, $bonus);
        $ledger = 'ELT';
        $value = round($bonusToken, config('constants.ELT_PRECISION'));
		$transaction_id = 'lendo_'.uniqid();
        $description = 'Got bonus ELT ' . round($bonusToken, config('constants.ELT_PRECISION')) . '  on purchasing of ELT ' . $ELTVal . ' Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
        $Transaction = Transactions::createTransactionWithReference($user_id, $ledger, $value, $description, 1, $transaction_id, $phaseId, NULL, NULL, 0, $ref_transaction_id, 'bonus');
        $user->addValue('ELT_balance', $value);
        $userUpdate = $user->save();

        $record2 = [
            'message' => 'Username ' . $user->email . ' transaction has created for adding ELT in bonus: ,   ' . $value,
            'level' => 'INFO',
            'context' => 'wallet'
        ];
        LoggerHelper::writeDB($record2);
        
		/**** Distribute Parent Referral Bonus **/
		$this->distributeBonusToParentReferrals($user->id, $txtBalance, $payment_units, $payment_amount, $ref_transaction_id, $phaseId, $conversion_rate_data, $ELTVal);
		
        return redirect()->route('buy.token.deal.thanks', ['type' => $payment_units, 'type2' => $payment_units2,'type_amount' => $payment_amount, 'type2_amount' => $payment_amount2, "ELTVal"=>$ELTVal ])->with('success', trans('message.transactionSuccess'));
    }
	
	/* Buy Token from Coinpayment Wallet */
    public function buyTokenConfirmCoinpayment(Request $request)
    {		
        if(!$request->get('PAYMENT_AMOUNT') || !$request->get('PAYMENT_UNITS')) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.error')])
                ->withInput($request->all());
        }
		
        $user = Auth::user();
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
		
        /* For multiple phases calculations - Select active phases data*/
        $activePhaseData = Phases::where('status', '1')->first();
        if (!isset($activePhaseData)) {
            $record = [
                'message' => 'Username ' . $user->email . ' Phase not activated ',
                'level' => 'ERROR',
                'context' => 'perfectMoney'
            ];
            LoggerHelper::writeDB($record);
            return redirect()
                ->to('/home')
                ->with('error', trans('lendo.ELT_tokens_not_available'));
        }
        if (isset($activePhaseData->id) && !empty($activePhaseData->id)) {
            $phaseId = $activePhaseData->id;
        } else {
            $phaseId = NULL;
        }
		
        if (!isset($conversion_rate_data) || empty($conversion_rate_data)) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.DataTableError')])
                ->withInput($request->all());
        }
		
        $allowedType = array('BTC', 'ETH', 'EUR', 'BCH', 'LTC', 'ETC', 'XRP', 'DASH');
		
        if (in_array($request->get('PAYMENT_UNITS'), $allowedType)) {
            $ELTVal = $this->getConversionInELTWithFee($request->get('PAYMENT_UNITS'), $request->get('PAYMENT_AMOUNT'));
            $minimumBuyCurrency = $conversion_rate_data['Minimum-Buy-' . $request->get('PAYMENT_UNITS')][0];
            $maximumBuyCurrency = $conversion_rate_data['Maximum-Buy-' . $request->get('PAYMENT_UNITS')][0];
        } else {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.error')])
                ->withInput($request->all());
        }
        if ($ELTVal < $conversion_rate_data['Minimum-Buy-ELT'][0] || $ELTVal > $conversion_rate_data['Maximum-Buy-ELT'][0]) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.validateMinMax', ['unit' => 'ELT', 'greaterThan' => $conversion_rate_data['Minimum-Buy-ELT'][0], 'lessThan' => $conversion_rate_data['Maximum-Buy-ELT'][0]])])
                ->withInput($request->all());
        }
        if ($request->get('PAYMENT_AMOUNT') < $minimumBuyCurrency || $request->get('PAYMENT_AMOUNT') > $maximumBuyCurrency) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.validateMinMax', ['unit' => $request->get('PAYMENT_UNITS'), 'greaterThan' => $minimumBuyCurrency, 'lessThan' => $maximumBuyCurrency])])
                ->withInput($request->all());
        }
        try {

            $coinpayment = new Coinpayments();
            $apiResult = $coinpayment->createTransactionSimple(
                $request->get('PAYMENT_AMOUNT'),
                $request->get('PAYMENT_UNITS'),
                $request->get('PAYMENT_UNITS'),
                ['buyer_email' => Auth::user()->email]
            );
            if (!isset($apiResult['response']['error']) || empty($apiResult['response']['error']) ||
                !isset($apiResult['response']['result']) || empty($apiResult['response']['result']) ||
                $apiResult['response']['error'] != 'ok') {
                return redirect()
                    ->back()
                    ->withErrors(['PAYMENT_AMOUNT' => $apiResult['response']['error']])
                    ->withInput($request->all());
            }
            $user_id = $user->id;
            $ledger = 'ELT';
            $value = $ELTVal;
			
            $transaction_id = $apiResult['response']['result']['txn_id'];
            $description = 'Converted unit ' . $request->get('PAYMENT_UNITS') . ' ' . @$request->get('PAYMENT_AMOUNT') . ' into ELT ' . $ELTVal . ' Wallet:local Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s") . ' QR : ' . $apiResult['response']['result']['qrcode_url'] . ' Status_url : ' . $apiResult['response']['result']['status_url'] . ' address : ' . $apiResult['response']['result']['address'];
			
			$is_cryptomania = 0;			
			if(isset($_POST['cryptomania']) && $_POST['cryptomania'] == md5('cryptomania')){
				$is_cryptomania = 1;
			}
			
			if($is_cryptomania == 1){
				$type_name = 'cryptomania';
			}
			else{
				$type_name = 'cp';
			}
			
            Transactions::createTransactionWithTermCurrency($user_id, $ledger, $value, $description, 2, $transaction_id, $phaseId, $apiResult['response']['result']['address'], $request->get('PAYMENT_UNITS'), $_POST['PAYMENT_AMOUNT'], $type_name);
			
			if($is_cryptomania == 1 && isset($_POST['bonusELTAmount']) && $_POST['bonusELTAmount'] > 0){
				$bonusELTAmount = $_POST['bonusELTAmount'];
				$description = 'Additional cryptomania bonus token of: '.$bonusELTAmount;
				$bonus_transaction_id = uniqid();
				Transactions::createTransactionWithReference($user_id, $ledger, $bonusELTAmount, $description, 2, $bonus_transaction_id, $phaseId, NULL, NULL, 0, $transaction_id, 'bonus');
			}
			
            $record = [
                'message' => 'Username ' . $user->email . ' coinpayment action',
                'level' => 'INFO',
                'context' => 'Coinpayment',
                'extra' => [
                    'coinpayment_response' => json_encode($apiResult)
                ]
            ];
            LoggerHelper::writeDB($record);
			
			$dataForView = array();
			$dataForView['request'] = $apiResult['request'];
			$dataForView['result'] = $apiResult['response']['result'];
			$dataForView['you_receive'] = $request->get('convertedRateAmount');
			$dataForView['exchange_rate'] = $request->get('exchangeRate');
			$dataForView['type'] = $request->get('type');
			//echo '<pre>';print_r($dataForView);die;
			
            return view('buyTokenConfirmInfo',$dataForView);
        } catch (Exception $exception) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => $exception->getMessage()])
                ->withInput($request->all());
        }
    }

	/* Buy Token from Coinpayment Wallet from 50:50 deal page */
    public function buyTokenConfirmCoinpaymentDeal(Request $request)
    {
		$_POST['type'] = $_POST['PAYMENT_UNITS'];
        if (!$request->get('PAYMENT_AMOUNT') || !$request->get('PAYMENT_UNITS')) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.error')])
                ->withInput($request->all());
        }
		
		if (!$request->get('PAYMENT_AMOUNT2') || !$request->get('PAYMENT_UNITS2')) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT2' => trans('message.error')])
                ->withInput($request->all());
        }
		
        $user = Auth::user();
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
        /* For multiple phases calculations - Select active phases data*/
        $activePhaseData = Phases::where('status', '1')->first();
        if (!isset($activePhaseData)) {
            $record = [
                'message' => 'Username ' . $user->email . ' Phase not activated ',
                'level' => 'ERROR',
                'context' => 'perfectMoney'
            ];
            LoggerHelper::writeDB($record);
            return redirect()
                ->to('/home')
                ->with('error', trans('lendo.ELT_tokens_not_available'));
        }
        if (isset($activePhaseData->id) && !empty($activePhaseData->id)) {
            $phaseId = $activePhaseData->id;
        } else {
            $phaseId = NULL;
        }
        if (!isset($conversion_rate_data) || empty($conversion_rate_data)) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.DataTableError')])
                ->withInput($request->all());
        }
		
        $allowedType = array('BTC', 'ETH', 'EUR', 'BCH', 'LTC', 'ETC', 'XRP', 'DASH');
        if (in_array($request->get('PAYMENT_UNITS'), $allowedType)) {
            $ELTVal = $this->getConversionInELTWithFee($request->get('PAYMENT_UNITS'), $request->get('PAYMENT_AMOUNT'));
            $minimumBuyCurrency = $conversion_rate_data['Minimum-Buy-' . $request->get('PAYMENT_UNITS')][0];
            $maximumBuyCurrency = $conversion_rate_data['Maximum-Buy-' . $request->get('PAYMENT_UNITS')][0];
        } 
		else {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.error')])
                ->withInput($request->all());
        }
		
		$allowedType2 = array('NEXO', 'SALT', 'ORME');
        if (in_array($request->get('PAYMENT_UNITS2'), $allowedType2)) {
           
            $minimumBuyCoin = $conversion_rate_data['Minimum-Buy-' . $request->get('PAYMENT_UNITS2')][0];
        } 
		else {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT2' => trans('message.error')])
                ->withInput($request->all());
        }
		
		$ELTVal = $_POST['convertedRateAmount']/2;
		
		/*
        if (($ELTVal*2) < $conversion_rate_data['Minimum-Buy-ELT'][0] || $ELTVal > $conversion_rate_data['Maximum-Buy-ELT'][0]) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.validateMinMax', ['unit' => 'ELT', 'greaterThan' => $conversion_rate_data['Minimum-Buy-ELT'][0], 'lessThan' => $conversion_rate_data['Maximum-Buy-ELT'][0]])])
                ->withInput($request->all());
        }
		*/
		
		$Minimum_Buy_ELT = 5000;
        if( $_POST['convertedRateAmount'] < $Minimum_Buy_ELT ) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.validateMinCoin', ['unit' => 'ELT', 'greaterThan' => $Minimum_Buy_ELT ] ) ] )
                ->withInput($request->all())->with("ELT_Error", "true");
        }
		
		
        if ($request->get('PAYMENT_AMOUNT') < $minimumBuyCurrency || $request->get('PAYMENT_AMOUNT') > $maximumBuyCurrency) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => trans('message.validateMinMax', ['unit' => $request->get('PAYMENT_UNITS'), 'greaterThan' => $minimumBuyCurrency, 'lessThan' => $maximumBuyCurrency])])
                ->withInput($request->all());
        }
		
		if ($request->get('PAYMENT_AMOUNT2') < $minimumBuyCoin ) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT2' => trans('message.validateMinCoin', ['unit' => $request->get('PAYMENT_UNITS2'), 'greaterThan' => $minimumBuyCoin ])])
                ->withInput($request->all());
        }
		
        try 
		{
            $coinpayment = new Coinpayments();
            $apiResult = $coinpayment->createTransactionSimple(
                $request->get('PAYMENT_AMOUNT'),
                $request->get('PAYMENT_UNITS'),
                $request->get('PAYMENT_UNITS'),
                ['buyer_email' => Auth::user()->email]
            );
            if (!isset($apiResult['response']['error']) || empty($apiResult['response']['error']) ||
                !isset($apiResult['response']['result']) || empty($apiResult['response']['result']) ||
                $apiResult['response']['error'] != 'ok') {
					
				
                return redirect()
                    ->back()
                    ->withErrors(['PAYMENT_AMOUNT' => $apiResult['response']['error']])
                    ->withInput($request->all());
            }
            $user_id = $user->id;
            $ledger = 'ELT';
            $value = $ELTVal;
            $ref_transaction_id = $transaction_id = $apiResult['response']['result']['txn_id'];
            $description = 'Converted unit ' . $request->get('PAYMENT_UNITS') . ' ' . @$request->get('PAYMENT_AMOUNT') . ' into ELT ' . $ELTVal . ' Wallet:local Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s") . ' QR : ' . $apiResult['response']['result']['qrcode_url'] . ' Status_url : ' . $apiResult['response']['result']['status_url'] . ' address : ' . $apiResult['response']['result']['address'];
            Transactions::createTransactionWithTermCurrency($user_id, $ledger, $value, $description, 2, $transaction_id, $phaseId, $apiResult['response']['result']['address'], $request->get('PAYMENT_UNITS'), $_POST['PAYMENT_AMOUNT'], 'cp');
			
			$record = [
                'message' => 'Username ' . $user->email . ' coinpayment action',
                'level' => 'INFO',
                'context' => 'Coinpayment',
                'extra' => [
                    'coinpayment_response' => json_encode($apiResult)
                ]
            ];
            LoggerHelper::writeDB($record);
			
			
			/* Coin transaction */
			$transaction_id = uniqid();
			$description = 'Converted unit ' . $request->get('PAYMENT_UNITS2') . ' ' . @$request->get('PAYMENT_AMOUNT2') . ' into ELT ' . $ELTVal . ' Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
			$Transaction = Transactions::createTransactionWithReference($user_id, 'ELT', $ELTVal, $description, 2, $transaction_id, $phaseId, '', $request->get('PAYMENT_UNITS2'), $request->get('PAYMENT_AMOUNT2'), $ref_transaction_id, 'coins');
			$record = [
                'message' => 'Username ' . $user->email . ' coin pay with : '.$request->get('PAYMENT_UNITS2'),
                'level' => 'INFO',
                'context' => 'Coinpay',
                'extra' => [
                    'coinpayment_response' => json_encode($_POST)
                ]
            ];
            LoggerHelper::writeDB($record);
		
            return redirect()->route('buy.token.deal.coin.thanks', ['type' => $request->get('PAYMENT_UNITS'), 'type2' => $request->get('PAYMENT_UNITS2'),'type_amount' => $request->get('PAYMENT_AMOUNT'), 'type2_amount' => $request->get('PAYMENT_AMOUNT2'), "ELTVal"=>$ELTVal, 'apiResult'=>serialize($apiResult)])->with('success', trans('message.transactionSuccess'));
			
        } catch (Exception $exception) {
            return redirect()
                ->back()
                ->withErrors(['PAYMENT_AMOUNT' => $exception->getMessage()])
                ->withInput($request->all());
        }
    }
	
	
	/* Buy Token from PerfectMoney Wallet */
    public function topupEURSuccess(Request $request)
    {
        if (!isset($_POST) || empty($_POST)) {
            return redirect()
                ->to('/home')
                ->with('error', trans("message.PerfectMoneyError"));
        }
        $record = [
            'message' => 'topupEURSuccess hook response',
            'level' => 'INFO',
            'context' => 'perfectMoney hook',
            'extra' => [
                'request' => $_REQUEST,
                'server' => $_SERVER,
                'file_get_contents' => ''
            ]
        ];
        LoggerHelper::writeDB($record);
        $pm = new PerfectMoney;
        if (!isset($_POST['V2_HASH']) || empty($_POST['V2_HASH'])) {
            //Log info of change in balance by user
            $user = Auth::user();
            $record = [
                'message' => 'Username ' . $user->email . 'PerfectMoney api not provide Hash value with this transaction ' . json_encode($_POST),
                'level' => 'ERROR',
                'context' => 'perfectMoney',
                'extra' => [
                    'perfectmoney_response' => json_encode($_POST)
                ]
            ];
            LoggerHelper::writeDB($record);
            return redirect()
                ->to('/home')
                ->with('error', trans('lendo.InvalidTransactionResponse'));
        }
        $hash = $pm->generateHash($request);
        if ($_POST['V2_HASH'] != $hash) {
            //Log info of change in balance by user
            $user = Auth::user();
            //
            $record = [
                'message' => 'Username ' . $user->email . ' transaction has failed, the V2 HAsh in response is invalid, Recieved Hash : ' . $_POST['V2_HASH'] . ', Generated Hash : ' . $hash,
                'level' => 'ERROR',
                'context' => 'perfectMoney',
                'extra' => [
                    'perfectmoney_response' => json_encode($_POST)
                ]
            ];
            LoggerHelper::writeDB($record);
            return redirect()
                ->to('/home')
                ->with('error', trans('lendo.InvalidTransactionResponse'));
        }
        $paymentHistory = $pm->getHistory(date("d", $_POST['TIMESTAMPGMT']), date("m", $_POST['TIMESTAMPGMT']), date("Y", $_POST['TIMESTAMPGMT']), date("d", $_POST['TIMESTAMPGMT']), date("m", $_POST['TIMESTAMPGMT']), date("Y", $_POST['TIMESTAMPGMT']), ['payment_id' => $_POST['PAYMENT_ID'], 'batchfilter' => $_POST['PAYMENT_BATCH_NUM']]);
        $paymentIncome = FALSE;
        $paymentNotMatch = '';
        foreach ($paymentHistory['history'] as $key => $value) {
            if (
                $value['type'] == 'Income' &&
                $value['payer_account'] == $_POST['PAYEE_ACCOUNT'] &&
                $value['amount'] == $_POST['PAYMENT_AMOUNT'] &&
                $value['currency'] == $_POST['PAYMENT_UNITS'] &&
                $value['batch'] == $_POST['PAYMENT_BATCH_NUM'] &&
                $value['payment_id'] == $_POST['PAYMENT_ID'] &&
                $value['payee_account'] == $_POST['PAYER_ACCOUNT']
            ) {
                $paymentIncome = TRUE;
                break;
            } else {
                $paymentNotMatch .= "Some payment data not match: batch:  {$_POST['PAYMENT_BATCH_NUM']} vs. {$value['batch']} = " . (($value['batch'] == $_POST['PAYMENT_BATCH_NUM']) ? 'OK' : '!!!NOT MATCH!!!') . "
                            payment_id:  {$_POST['PAYMENT_ID']} vs. {$value['payment_id']} = " . (($value['payment_id'] == $_POST['PAYMENT_ID']) ? 'OK' : '!!!NOT MATCH!!!') . "
                            type:  Income vs. {$value['type']} = " . (('Income' == $value['type']) ? 'OK' : '!!!NOT MATCH!!!') . "
                            payee_account:  {$_POST['PAYEE_ACCOUNT']} vs. {$value['payee_account']} = " . (($value['payee_account'] == $_POST['PAYEE_ACCOUNT']) ? 'OK' : '!!!NOT MATCH!!!') . "
                            amount:  {$_POST['PAYMENT_AMOUNT']} vs. {$value['amount']} = " . (($value['amount'] == $_POST['PAYMENT_AMOUNT']) ? 'OK' : '!!!NOT MATCH!!!') . "
                            currency:  {$_POST['PAYMENT_UNITS']} vs. {$value['currency']} = " . (($value['currency'] == $_POST['PAYMENT_UNITS']) ? 'OK' : '!!!NOT MATCH!!!') . "
                            payer account:  {$_POST['PAYER_ACCOUNT']} vs. {$value['payer_account']} = " . (($value['payer_account'] == $_POST['PAYER_ACCOUNT']) ? 'OK' : '!!!NOT MATCH!!!');
            }
        }
        if (!isset($paymentIncome) || empty($paymentIncome)) {
            //Log info of change in balance by user
            $user = Auth::user();
            //
            $record = [
                'message' => 'Username ' . $user->email . ' transaction has failed, the transaction history does not match with this transaction : ' . $_POST['V2_HASH'],
                'level' => 'ERROR',
                'context' => 'perfectMoney',
                'extra' => [
                    'perfectmoney_response' => json_encode($_POST)
                ]
            ];
            LoggerHelper::writeDB($record);
            return redirect()
                ->to('/home')
                ->with('error', trans('lendo.InvalidTransactionResponse'));
        }
        $data = array();
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
        //For multiple phases calculations
        /* Select actiave phases data*/
        $activePhaseData = Phases::where('status', '1')->first();
        if (isset($activePhaseData->id) && !empty($activePhaseData->id)) {
            $phaseId = $activePhaseData->id;
        } else {
            $phaseId = NULL;
        }
        $user = Auth::user();
        $user_id = $user->id;
        $ledger = $_POST['PAYMENT_UNITS'];
        $value = $_POST['PAYMENT_AMOUNT'];
		
		$description = 'Received Payment to ' . @$_POST['PAYEE_ACCOUNT'] . ' UNIT: ' . @$_POST['PAYMENT_UNITS'] . ' AMOUNT: '.@$_POST['PAYMENT_AMOUNT'].' from account ' . @$_POST['PAYER_ACCOUNT'] . '. PaymentID : ' . @$_POST['PAYMENT_ID'] . ' Payment Batch id : ' . $_POST['PAYMENT_BATCH_NUM'] . ' Time created at: ' . $_POST['TIMESTAMPGMT'] . ' Suggested Memo: ' . $_POST['SUGGESTED_MEMO'];
		
        //$transaction_id = $_POST['PAYMENT_BATCH_NUM'];
        $transaction_id = $_POST['PAYMENT_ID'];
        
        $Transaction = Transactions::createTransactionWithReference($user_id, $ledger, $value, $description, 1, $transaction_id, $phaseId, NULL, NULL, 0, NULL, 'pm');
		
        $user->addValue('EUR_balance', $value);
		
        $userUpdate = $user->save();
		
		
		$data['ELT_value'] = round(($_POST['PAYMENT_AMOUNT'] * $conversion_rate_data['Conversion-EUR-ELT'][0]), config('constants.ELT_PRECISION'));
		
		
		$coin_name = $coin_amount = $eur_amount = $elt_amount = '';
		/* Coin transaction */
		$SUGGESTED_MEMO_ARRAY = array();
		
		if(isset($_POST['SUGGESTED_MEMO']) && $_POST['SUGGESTED_MEMO'] == md5('cryptomania'))
		{
			$bonusPercentage = CommonHelper::getCryptoManiaEuroWorthPercent($value);
			$bonusELTAmount = User::calculateCommision($data['ELT_value'],$bonusPercentage);
			$description = 'Additional cryptomania bonus token of: '.$bonusELTAmount;
			$bonus_transaction_id = uniqid();
			Transactions::createTransactionWithReference($user_id, 'ELT', $bonusELTAmount, $description, 1, $bonus_transaction_id, $phaseId, NULL, NULL, 0, $transaction_id, 'bonus');			
			$user->addValue('ELT_balance', $bonusELTAmount);
			$user->save();
		}
		elseif(isset($_POST['SUGGESTED_MEMO']))
		{
			$SUGGESTED_MEMO_ARRAY = explode(",",$_POST['SUGGESTED_MEMO']);
			if(isset($SUGGESTED_MEMO_ARRAY[0]) && isset($SUGGESTED_MEMO_ARRAY[1]) && isset($SUGGESTED_MEMO_ARRAY[2]) && isset($SUGGESTED_MEMO_ARRAY[3]))
			{
				$coin_name = $SUGGESTED_MEMO_ARRAY[0];
				$coin_amount = $SUGGESTED_MEMO_ARRAY[1];
				$eur_amount = $SUGGESTED_MEMO_ARRAY[2];
				$elt_amount = $SUGGESTED_MEMO_ARRAY[3];
				
				$transaction_id = uniqid();
				$description = 'Converted unit ' . @$coin_name . ' ' . @$coin_amount . ' into ELT ' . @$elt_amount . ' Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
				$Transaction = Transactions::createTransactionWithReference($user_id, 'ELT', $elt_amount, $description, 2, $transaction_id, $phaseId, '', $coin_name, $coin_amount, $_POST['PAYMENT_ID'], 'coins');
			}
		}
				
        
        //
        $record = [
            'message' => 'Username ' . $user->email . ' coinpayment action',
            'level' => 'INFO',
            'context' => 'perfectMoney',
            'extra' => [
                'perfectmoney_response' => json_encode($_POST)
            ]
        ];
        LoggerHelper::writeDB($record);
        if (!isset($activePhaseData)) {
            //
            $record = [
                'message' => 'Username ' . $user->email . ' Phase not activated ',
                'level' => 'ERROR',
                'context' => 'perfectMoney',
                'extra' => [
                    'perfectmoney_response' => json_encode($_POST)
                ]
            ];
            LoggerHelper::writeDB($record);
            return redirect()
                ->to('/home')
                ->with('error', trans('lendo.ELT_tokens_not_available'));
        }
        //
        if (!isset($conversion_rate_data) || empty($conversion_rate_data)) {
            return redirect()
                ->to('/home')
                ->with('success', trans('message.EURToELTConvertedmsg'));
        }
        //
        if (
            $data['ELT_value'] < $conversion_rate_data['Minimum-Buy-ELT'][0] || $data['ELT_value'] > $conversion_rate_data['Maximum-Buy-ELT'][0]
        ) {
            return redirect()
                ->to('/home')
                ->with('success', trans('message.validateMinMaxSuccess', ['unit' => 'ELT', 'greaterThan' => $conversion_rate_data['Minimum-Buy-ELT'][0], 'lessThan' => $conversion_rate_data['Maximum-Buy-ELT'][0]]));
        }
        if (
            $request->get('PAYMENT_UNITS') != 'EUR' || $request->get('PAYMENT_AMOUNT') < $conversion_rate_data['Minimum-Buy-EUR'][0] || $request->get('PAYMENT_AMOUNT') > $conversion_rate_data['Maximum-Buy-EUR'][0]
        ) {
            return redirect()
                ->to('/home')
                ->with('success', trans('message.validateMinMaxSuccess', ['unit' => 'EUR', 'greaterThan' => $conversion_rate_data['Minimum-Buy-EUR'][0], 'lessThan' => $conversion_rate_data['Maximum-Buy-EUR'][0]]));
        }
		
        $ledger = $_POST['PAYMENT_UNITS'];
        $value = '-' . $_POST['PAYMENT_AMOUNT'];
        $transaction_id = uniqid();
        $description = 'Converted unit ' . $_POST['PAYMENT_UNITS'] . ' ' . @$_POST['PAYMENT_AMOUNT'] . ' into ELT ' . $data['ELT_value'] . ' Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
        //
        $Transaction = Transactions::createTransactionWithReference($user_id, $ledger, $value, $description, 1, $transaction_id, $phaseId, '', NULL, 0, $_POST['PAYMENT_ID']);

        $user->subtractValue('EUR_balance', $_POST['PAYMENT_AMOUNT']);
        $user->save();
		
        LoggerHelper::writeEventInfo($user->email, "perfectMoney", 'Success:', 'Username ' . $user->email . ' transaction has created for Subracting EUR: ,   ' . $_POST['PAYMENT_AMOUNT']);
		
		
        $ledger = 'ELT';
        $value = $data['ELT_value'];
        $transaction_id = uniqid();
        $description = 'Converted unit ' . $_POST['PAYMENT_UNITS'] . ' ' . @$_POST['PAYMENT_AMOUNT'] . ' into ELT ' . $data['ELT_value'] . ' Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
        
		Transactions::createTransactionWithReference($user_id, $ledger, $value, $description, 1, $transaction_id, $phaseId, '', 'EUR', $_POST['PAYMENT_AMOUNT'], $_POST['PAYMENT_ID']);
        $user->addValue('ELT_balance', $data['ELT_value']);
        $user->save();
		
        $record = [
            'message' => 'Username ' . $user->email . ' transaction has created for adding ELT : ,   ' . $data['ELT_value'],
            'level' => 'INFO',
            'context' => 'perfectMoney',
            'extra' => [
                'perfectmoney_response' => json_encode($_POST)
            ]
        ];
        LoggerHelper::writeDB($record);
        
		//Bonus calculation on multiple phase
        //Get phase bonus percentage
        $bonus = $activePhaseData->phase_bonus;
        $bonusToken = Phases::calculateBonusToken($data['ELT_value'], $bonus);
        $ledger = 'ELT';
        $value = round($bonusToken, config('constants.ELT_PRECISION'));
        $transaction_id = uniqid();
        $description = 'Got bonus  ' . round($bonusToken, config('constants.ELT_PRECISION')) . ' ELT on purchasing of ' . $data['ELT_value'] . ' ELT ' . $data['ELT_value'] . ' Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
		Transactions::createTransactionWithReference($user_id, $ledger, $value, $description, 1, $transaction_id, $phaseId, '', NULL, 0, $_POST['PAYMENT_ID'], 'bonus');
        $user->addValue('ELT_balance', $value);
        $updateELTAmount = $user->save();
        //
        $record = [
            'message' => 'Username ' . $user->email . ' transaction has created for adding ELT in bonus: ,   ' . $value,
            'level' => 'INFO',
            'context' => 'perfectMoney',
            'extra' => [
                'perfectmoney_response' => json_encode($_POST)
            ]
        ];
        LoggerHelper::writeDB($record);
        
		
		/**** Distribute Parent Referral Bonus **/
		if($updateELTAmount)
		{
			$this->distributeBonusToParentReferrals($user->id, "EUR_balance", "EUR", $_POST['PAYMENT_AMOUNT'], $_POST['PAYMENT_ID'], $phaseId, $conversion_rate_data, $data['ELT_value']);
		}
		
		if(isset($SUGGESTED_MEMO_ARRAY[0]) && isset($SUGGESTED_MEMO_ARRAY[1]) && isset($SUGGESTED_MEMO_ARRAY[2]) && isset($SUGGESTED_MEMO_ARRAY[3]))
		{
			return redirect()->route('buy.token.deal.thanks', ['type' =>$_POST['PAYMENT_UNITS'], 'type2' => $coin_name,'type_amount' => $eur_amount, 'type2_amount' => $coin_amount, "ELTVal"=>$elt_amount ])->with('success', trans('message.transactionSuccess'));
		}

        return redirect()
            ->to('/home')
            ->with('success', trans('message.transactionSuccess'));
		
    }
	

    public function topupEURFail()
    {
        if (isset($_POST) && !empty($_POST)) {
            $record = [
                'message' => 'topupEURFail hook response',
                'level' => 'INFO',
                'context' => 'perfectMoney hook',
                'extra' => [
                    'request' => $_POST,
                    'server' => $_SERVER,
                    'file_get_contents' => ''
                ]
            ];
            LoggerHelper::writeDB($record);
            $user = Auth::user();
            $user_id = $user->id;
            $ledger = @$_POST['PAYMENT_UNITS'];
            $value = @$_POST['PAYMENT_AMOUNT'];
            $description = 'Received Payment to ' . @$_POST['PAYEE_ACCOUNT'] . ' ' . @$_POST['PAYMENT_UNITS'] . ' from account ' . @$_POST['PAYER_ACCOUNT'] . '. Payment id : ' . @$_POST['PAYMENT_ID'] . ' Payment Batch id : ' . @$_POST['PAYMENT_BATCH_NUM'] . ' Time created at: ' . @$_POST['TIMESTAMPGMT'] . ' Suggested Memo: ' . @$_POST['SUGGESTED_MEMO'];
            //$transaction_id = $_POST['PAYMENT_BATCH_NUM'];
			$transaction_id = $_POST['PAYMENT_ID'];
            //For multiple phases calculations
            /* Select actiave phases data*/
            $activePhaseData = Phases::where('status', '1')->first();
            if (isset($activePhaseData->id) && !empty($activePhaseData->id)) {
                $phaseId = $activePhaseData->id;
            } else {
                $phaseId = NULL;
            }
            //Create transaction to user account
            Transactions::createTransactionWithReference($user_id, $ledger, $value, $description, 0, $transaction_id, $phaseId, NULL, NULL, 0, NULL, 'pm');
			
			/* Coin transaction */
			if(isset($_POST['SUGGESTED_MEMO']))
			{
				$SUGGESTED_MEMO_ARRAY = explode(",",$_POST['SUGGESTED_MEMO']);
				if(isset($SUGGESTED_MEMO_ARRAY[0]) && isset($SUGGESTED_MEMO_ARRAY[1]) && isset($SUGGESTED_MEMO_ARRAY[2]) && isset($SUGGESTED_MEMO_ARRAY[3]))
				{
					$coin_name = $SUGGESTED_MEMO_ARRAY[0];
					$coin_amount = $SUGGESTED_MEMO_ARRAY[1];
					$eur_amount = $SUGGESTED_MEMO_ARRAY[2];
					$elt_amount = $SUGGESTED_MEMO_ARRAY[3];
					
					$transaction_id_coin = uniqid();
					$description = 'Converted unit ' . @$coin_name . ' ' . @$coin_amount . ' into ELT ' . @$elt_amount . ' Payment id : ' . @$transaction_id_coin . ' Time created at: ' . date("m/d/Y H:i:s");
					$Transaction = Transactions::createTransactionWithReference($user_id, 'ELT', $elt_amount, $description, 0, $transaction_id_coin, $phaseId, '', $coin_name, $coin_amount, $transaction_id,'coins');
				}
			}
		
            //
            $record = [
                'message' => 'Username ' . $user->email . ' perfectMoney action cancel request',
                'level' => 'INFO',
                'context' => 'perfectMoney',
                'extra' => [
                    'perfectmoney_response' => json_encode($_POST)
                ]
            ];
            LoggerHelper::writeDB($record);
			
			
            return redirect()
                ->to('/home')
                ->with('error', trans("message.PerfectMoneyError"));
        } else {
            return redirect()
                ->to('/home')
                ->with('error', trans("message.PerfectMoneyError"));
        }
    }

    public function coinpaymentHook(Request $request)
    {
        error_reporting(0);
        $record = [
            'message' => 'Coinpayments hook response hook',
            'level' => 'INFO',
            'context' => 'coinpayment hook',
            'extra' => [
                'request' => $request->all(),
                'server' => $request->server(),
                'file_get_contents' => ''
            ]
        ];
        LoggerHelper::writeDB($record);
        try {
            if (!$request->all() || !$request->server()) {
                //
                $record = [
                    'message' => $exception->getMessage(),
                    'level' => 'ERROR',
                    'context' => 'coinpayment hook request all or request server not found',
                    'extra' => [
                        'Exception' => json_encode($exception),
                        'request' => $request->all(),
                        'server' => $request->server(),
                        'file_get_contents' => ''
                    ]
                ];
                LoggerHelper::writeDB($record);
                exit('IPN ERROR');
            }
            $coinpayment = new Coinpayments();
            $txn_id = $request->get('txn_id');
            $coin_payment_status = $request->get('status');
            $term_amount = $request->get('received_amount');
            $term_currency = $request->get('currency1');
            $apiResult = $coinpayment->validateIPN($request->all(), $request->server());
            $Transactions = Transactions::where([['transaction_id', $txn_id]])->get();
            if ($Transactions[0]->status == 1) {
                exit('IPN OK');
            }
            if (isset($Transactions[0]->user_id) && !empty($Transactions[0]->user_id)) {
                $user = User::find($Transactions[0]->user_id);
            }
            if ($coin_payment_status == -1) // IF transaction failed from coinpayment
            {
                Transactions::where([['transaction_id', $txn_id]])->update(['status' => 0]);
                $record = [
                    'message' => 'Transaction cancelled; Txn_id :  ' . $txn_id . ' Amount : , ' . $Transactions[0]->value,
                    'level' => 'INFO',
                    'context' => 'coinpayment hook',
                    'extra' => [
                        'request' => $request->all()
                    ]
                ];
                LoggerHelper::writeDB($record);
                exit('IPN ERROR');
            }
            if (!isset($apiResult) || empty($apiResult) || ($coin_payment_status != 2 && $coin_payment_status < 100) || $Transactions[0]->status != 2) {
                //
                $record = [
                    'message' => 'Coin payment callback with pending status: Username ' . $user->email,
                    'level' => 'INFO',
                    'context' => 'coinpayment hook',
                    'extra' => [
                        'request' => $request->all()
                    ]
                ];
                LoggerHelper::writeDB($record);
                Transactions::where([['transaction_id', $txn_id]])->update(['status' => 2]);
                exit('IPN ERROR');
            }
            if (!isset($Transactions[0]->user_id) || empty($Transactions[0]->user_id)) {
                //
                $record = [
                    'message' => 'Coinpayments hook Transaction user id not found',
                    'level' => 'INFO',
                    'context' => 'coinpayment hook',
                    'extra' => [
                        'request' => $request->all(),
                        'server' => $request->server(),
                        'file_get_contents' => ''
                    ]
                ];
                LoggerHelper::writeDB($record);
                exit('IPN ERROR');
            }
            $user = User::find($Transactions[0]->user_id);
            /* payment is complete or queued for nightly payout, success*/
            if (!isset($Transactions[0]->status) || $Transactions[0]->status == 1) {
                $record = [
                    'message' => 'Coinpayments hook Transaction already updated status',
                    'level' => 'INFO',
                    'context' => 'coinpayment hook',
                    'extra' => [
                        'request' => $request->all(),
                        'server' => $request->server(),
                        'file_get_contents' => ''
                    ]
                ];
                LoggerHelper::writeDB($record);
                exit('IPN ERROR');
            }
            if ($Transactions[0]->term_amount != $term_amount) {
                $newETLAmount = $this->getConversionInELT($request->get('currency1'), $term_amount);
                $Transactions[0]->value = $newETLAmount;
                $newDescription = 'Converted unit ' . $term_currency . ' ' . @$term_amount . ' into ELT(adjustment) ' . $newETLAmount . ' Payment id : ' . @$txn_id . ' Time created at: ' . date("m/d/Y H:i:s");
                Transactions::where([['transaction_id', $txn_id]])->update(['term_amount' => $term_amount, 'value' => $newETLAmount, 'description' => $newDescription]);
            }
			
            $user->addValue('ELT_balance', $Transactions[0]->value);
            $user->save();
            Transactions::where([['transaction_id', $txn_id]])->update(['status' => 1]);
			
			$bonusTransactionRow = Transactions::where([['ref_transaction_id', $txn_id]])->get();
			
			if(isset($bonusTransactionRow[0]->id) && $bonusTransactionRow[0]->id > 0)
			{
				$user->addValue('ELT_balance', $bonusTransactionRow[0]->value);
				$user->save();
				Transactions::where([['ref_transaction_id', $txn_id]])->update(['status' => 1]);
			}
			
            $record = [
                'message' => 'Transaction completed successfully : Username ' . $user->email . ' have been added  ELT : ,   ' . $Transactions[0]->value,
                'level' => 'INFO',
                'context' => 'coinpayment hook',
                'extra' => [
                    'request' => $request->all()
                ]
            ];
            LoggerHelper::writeDB($record);
            /* Select active phases data*/
            $activePhaseData = Phases::where('status', '1')->first();
            if (!isset($activePhaseData->id)) {
                //
                $record = [
                    'message' => 'Phases data error :Username ' . $user->email . ' Phase not activated Bonus transaction',
                    'level' => 'ERROR',
                    'context' => 'coinpayment hook',
                    'extra' => [
                        'request' => $request->all()
                    ]
                ];
                LoggerHelper::writeDB($record);
                exit();
            }
			
            $phaseId = $activePhaseData->id;
            //Bonus calculation on multiple phase
            //Get phase bonus percentage
            $bonus = $activePhaseData->phase_bonus;
            $bonusToken = Phases::calculateBonusToken($Transactions[0]->value, $bonus);
            $currency1 = $ledger = 'ELT';
            $value = round($bonusToken, config('constants.ELT_PRECISION'));
            $transaction_id = uniqid();
            $description = 'Got bonus  ' . round($bonusToken, config('constants.ELT_PRECISION')) . ' ELT on purchasing of ' . $Transactions[0]->value . ' ELT by coinpayment Payment id : ' . @$transaction_id . ' Time created at: ' . date("m/d/Y H:i:s");
            $Transaction = Transactions::createTransactionWithReference($Transactions[0]->user_id, $ledger, $value, $description, 1, $transaction_id, $phaseId, NULL, NULL, 0, $txn_id, 'cp');
            $user->addValue('ELT_balance', $value);
            $user->save();
			
            $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
            $conversion_rate_data = array();
            if (!$conversion_rates->isEmpty()) {
                foreach ($conversion_rates as $key => $rates_minimume_values) {
                    $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                    $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
                }
            }
			$term_currency = $request->get('currency1');
			$term_amount = $request->get('received_amount');
			if (isset($term_currency) && isset($term_amount) && $term_amount > 0) {
				$term_currency = $term_currency;
				
				/**** Distribute Parent Referral Bonus **/
				$this->distributeBonusToParentReferrals($user->id, $term_currency.'_balance', $term_currency , $term_amount, $txn_id, $phaseId, $conversion_rate_data, $Transactions[0]->value);
			
			} else {
				$term_currency = $currency1;
				$term_amount = $Transactions[0]->value;
			}
            exit('IPN OK');
        } catch (Exception $exception) {
            //
            $record = [
                'message' => $exception->getMessage(),
                'level' => 'ERROR',
                'context' => 'coinpayment hook exception',
                'extra' => [
                    'Exception' => json_encode($exception),
                    'request' => $request->all(),
                    'server' => $request->server(),
                    'file_get_contents' => ''
                ]
            ];
            LoggerHelper::writeDB($record);
            exit('IPN ERROR');
        }
    }

    
    private function getConversionInELT($base_currency, $amount)
    {
        $__to_euro = 0;
        $__to_elt = 0;
        $allowedTypeBraveAPI = array('ETC', 'XRP', 'DASH');
        $allowedTypeCoinBaseAPI = array('BTC', 'ETH', 'BCH', 'BTH', 'LTC');
        $Conversion_EUR_ELT = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Conversion-EUR-ELT']])->get();
        if ($base_currency == 'EUR') {
            return round($amount * $Conversion_EUR_ELT[0]->defined_value, config('constants.ELT_PRECISION'));
        }
        if (in_array($base_currency, $allowedTypeCoinBaseAPI)) {
            $coinbase_rate = CommonHelper::get_coinbase_currency($base_currency);
            $__to_euro = $coinbase_rate['data']['rates']['EUR'];
        } elseif (in_array($base_currency, $allowedTypeBraveAPI)) {
            $__to_euro = CommonHelper::get_brave_coin_rates(strtolower($base_currency), "EUR", 1);
        }
        if ($__to_euro > 0) {
            $__to_elt = $__to_euro * $Conversion_EUR_ELT[0]->defined_value;
        }
        $total_elt_amount = $__to_elt * $amount;
        if ($total_elt_amount > 0 && $total_elt_amount < 1) {
            return round($total_elt_amount, config('constants.ELT_PRECISION'));
        }
        return round($__to_elt * $amount, config('constants.ELT_PRECISION'));
    }

    private function getConversionInELTWithFee($currency, $amount)
    {
        $__to_euro = 0;
        $__to_elt = 0;
        $total_amount_in_ELT = 0;
        $allowedTypeBraveAPI = array('ETC', 'XRP', 'DASH');
        $allowedTypeCoinBaseAPI = array('BTC', 'ETH', 'BCH', 'BTH', 'LTC');
        $Conversion_EUR_ELT = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Conversion-EUR-ELT']])->get();
        if ($currency == 'EUR') {
            return round($amount * $Conversion_EUR_ELT[0]->defined_value, config('constants.EUR_PRECISION'));
        }
        if (in_array($currency, $allowedTypeCoinBaseAPI)) {
            $coinbase_rate = CommonHelper::get_coinbase_currency($currency);
            $__to_euro = $coinbase_rate['data']['rates']['EUR'];
        } elseif (in_array($currency, $allowedTypeBraveAPI)) {
            $__to_euro = CommonHelper::get_brave_coin_rates(strtolower($currency), "EUR", 1);
        }
        if ($__to_euro > 0) {
            $__to_elt = $__to_euro * $Conversion_EUR_ELT[0]->defined_value;
            $__to_elt_reverse = 1 / $__to_elt;
            $Conversion_EUR_Fee = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Conversion-' . $currency . '-EUR-Fee']])->get();
            $fees = $Conversion_EUR_Fee[0]->defined_value;
            $__to_elt_reverse = $__to_elt_reverse + ($__to_elt_reverse * $fees / 100);
            $total_amount_in_ELT = (1 / $__to_elt_reverse) * $amount;
        }
		return $this->balance_format($total_amount_in_ELT,config('constants.EUR_PRECISION'));
    }
	
	private function getCoinConversionIn_ELT_WithFee($coin)
    {
        $conversion_rates = Configurations::where([['valid_to', '9999-12-31']])->get();
        $conversion_rate_data = array();
        if (!$conversion_rates->isEmpty()) {
            foreach ($conversion_rates as $key => $rates_minimume_values) {
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_value;
                $conversion_rate_data[$rates_minimume_values->name][] = $rates_minimume_values->defined_unit;
            }
        }
		
		$ELT_to_Coin = $coin_to_euro = $fees = 0;		
		if($coin == "NEXO"){
			$fees = $conversion_rate_data['Conversion-NEXO-EUR-Fee'][0];
			$coin_to_euro = CommonHelper::get_coinmarketcap_rates(config('global_vars.NEXO_ID'),"EUR");
		}
		elseif($coin == "SALT"){
			$fees = $conversion_rate_data['Conversion-SALT-EUR-Fee'][0];
			$coin_to_euro = CommonHelper::get_coinmarketcap_rates(config('global_vars.SALT_ID'),"EUR");
		}
		elseif($coin == "ORME"){
			$fees = $conversion_rate_data['Conversion-ORME-EUR-Fee'][0];
			$coin_to_euro = CommonHelper::get_coinmarketcap_rates(config('global_vars.ORME_ID'),"EUR");
		}
		
		$Coin_to_ELT = $coin_to_euro * $conversion_rate_data['Conversion-EUR-ELT'][0];
		$ELT_to_Coin = 1 / $Coin_to_ELT;		
		$ELT_to_Coin = $ELT_to_Coin + ($ELT_to_Coin * $fees / 100);
		return $this->balance_format(1/$ELT_to_Coin,config('constants.EUR_PRECISION'));
    }
	
	private function balance_format($amount,$precision=2)
	{
		return number_format($amount, $precision, '.', '');
	}
	
    private function getActivePhaseId()
    {
        $activePhaseData = Phases::where('status', '1')->first();
        if (!isset($activePhaseData)) {
            $user = Auth::user();
            $record = [
                'message' => 'Username ' . $user->email . ' Phase not activated ',
                'level' => 'ERROR',
                'context' => 'Transfer Fund'
            ];
            LoggerHelper::writeDB($record);
        }
        if (isset($activePhaseData) && !empty($activePhaseData->id)) {
            $phaseId = $activePhaseData->id;
        } else {
            $phaseId = NULL;
        }
        return $phaseId;
    }
	
	
	private function distributeBonusToParentReferrals($login_user_id, $txtBalance, $payment_units, $payment_amount, $parent_transaction_id, $phaseId, $conversion_rate_data, $buyingToken=0)
	{
		$invoice_data = array();
		$invoice_data['invoice_number'] = CommonHelper::generateInvoiceNumber();
		$invoice_data['ref_transaction_id'] = $parent_transaction_id;
		$invoice_data['user_id'] = $login_user_id;
		$invoice_data['elt_amount'] = $buyingToken;
		$invoice_data['token_price'] = 1/$conversion_rate_data['Conversion-EUR-ELT'][0];
		$invoice_data['currency'] = $payment_units;
		$invoice_data['invoice_count'] = Transactions::get_invoice_no($login_user_id);
		$invoice_data['currency_amount'] = $payment_amount;
		$invoice_data['description'] = 'By system';
		$invoice_data['created_at'] = date("Y-m-d H:i:s");
		Transactions::add_row_in_table("elt_invoices",$invoice_data);
		$user = User::find($login_user_id);
		
		$bonusDistributionUptoLevel = 5;
		$ParentList = ParentChild::get_my_upline($login_user_id);
		if(count($ParentList))
		{
			for($level=1; $level<=$bonusDistributionUptoLevel; $level++)
			{
				if(isset($ParentList[$level-1]) && $ParentList[$level-1] > 0)
				{
					$parentId = $ParentList[$level-1];
					$parentRefferralUserDetails = User::find($parentId);
					if(isset($parentRefferralUserDetails->id) && !empty($parentRefferralUserDetails->id))
					{
						if($buyingToken > 0 && ($parentRefferralUserDetails->admin_opt_bonus == 1 || $parentRefferralUserDetails->user_opt_bonus == 0) && $level == 1)
						{
							$additionalTokenBonus = User::calculateCommision($buyingToken,$conversion_rate_data['Additional-token-when-bonus-not-opted'][0]);
							$user->addValue('ELT_balance',$additionalTokenBonus);
							$user->save();
							Transactions::createTransaction($login_user_id,'ELT', $additionalTokenBonus, 'Additional token bonus: ' . $additionalTokenBonus, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus',0);
						}
						
						$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($parentRefferralUserDetails->id,$conversion_rate_data['Referral-%-Level-'.$level][0]);
						$newBonusPercentage = $newBonusData["new_bonus_percent"];
						$parentRefferralCommision = User::calculateCommision($payment_amount,$newBonusPercentage);
						if(isset($parentRefferralCommision) && !empty($parentRefferralCommision) && ($parentRefferralUserDetails->user_opt_bonus == 1 || $parentRefferralUserDetails->admin_opt_bonus == 1)) 
						{
							$parentRefferralUserDetails->addValue($txtBalance, $parentRefferralCommision);
							$parentRefferralUserDetails->save();
							$unpaidBonus=0;
							$unpaid_bonus_percent = $newBonusData["unpaid_bonus_percent"];
							$unpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent);
							Transactions::createTransaction($parentRefferralUserDetails->id, $payment_units, $parentRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $unpaidBonus);
							$direct_bonus_ELT = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-'.$level.'-Bonus-ELT']])->get();							
							$ELT_bonus = $direct_bonus_ELT[0]->defined_value;
							if(isset($ELT_bonus) && $ELT_bonus > 0)
							{
								$parentRefferralUserDetails->addValue('ELT_balance', $ELT_bonus);
								$parentRefferralUserDetails->save();
								Transactions::createTransaction($parentRefferralUserDetails->id, 'ELT', $ELT_bonus, 'Level-'.$level.' Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
							}
						}
						else
						{
							$unpaid_bonus_amount = User::calculateCommision($payment_amount,$conversion_rate_data['Referral-%-Level-'.$level][0]);
							Transactions::createEmptyTransaction($parentRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
						}
					}
				}
			}
		}
		
		/*		
        $levelOneRefferralUserDetails = User::find($user->referrer_user_id);
        if(isset($levelOneRefferralUserDetails->id) && !empty($levelOneRefferralUserDetails->id)) 
		{			
			if($buyingToken > 0 && ($levelOneRefferralUserDetails->admin_opt_bonus == 1 || $levelOneRefferralUserDetails->user_opt_bonus == 0))
			{
				$additionalTokenBonus = User::calculateCommision($buyingToken,$conversion_rate_data['Additional-token-when-bonus-not-opted'][0]);
				$user->addValue('ELT_balance',$additionalTokenBonus);
                $user->save();
				Transactions::createTransaction($user->id,'ELT', $additionalTokenBonus, 'Additional token bonus: ' . $additionalTokenBonus, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus',0);
			}
			
			$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($levelOneRefferralUserDetails->id,$conversion_rate_data['Referral-%-Level-1'][0]);
			$newBonusPercentageLevelOne = $newBonusData["new_bonus_percent"];
            $levelOneRefferralCommision = User::calculateCommision($payment_amount,$newBonusPercentageLevelOne);
            
			if(isset($levelOneRefferralCommision) && !empty($levelOneRefferralCommision) && ($levelOneRefferralUserDetails->user_opt_bonus == 1 || $levelOneRefferralUserDetails->admin_opt_bonus == 1)) 
			{
                $levelOneRefferralUserDetails->addValue($txtBalance, $levelOneRefferralCommision);
                $levelOneRefferralUserDetails->save();                
				$levelOneUnpaidBonus=0;
				$unpaid_bonus_percent_level_one = $newBonusData["unpaid_bonus_percent"];
				$levelOneUnpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent_level_one);			
                Transactions::createTransaction($levelOneRefferralUserDetails->id, $payment_units, $levelOneRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $levelOneUnpaidBonus);
				
               
                $direct_bonus_ELT_level_one = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-1-Bonus-ELT']])->get();
                $ELT_bonus_level_one = $direct_bonus_ELT_level_one[0]->defined_value;
                if (isset($ELT_bonus_level_one) && $ELT_bonus_level_one > 0) 
				{
                    $levelOneRefferralUserDetails->addValue('ELT_balance', $ELT_bonus_level_one);
                    $levelOneRefferralUserDetails->save();
                    Transactions::createTransaction($levelOneRefferralUserDetails->id, 'ELT', $ELT_bonus_level_one, 'Level-1 Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
                }
            }
			else
			{
				$unpaid_bonus_amount = User::calculateCommision($payment_amount,$conversion_rate_data['Referral-%-Level-1'][0]);
				Transactions::createEmptyTransaction($levelOneRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
			}
			
			$levelTwoRefferralUserDetails = User::find($levelOneRefferralUserDetails->referrer_user_id);
			if(isset($levelTwoRefferralUserDetails->id) && !empty($levelTwoRefferralUserDetails->id)) 
			{
				$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($levelTwoRefferralUserDetails->id,$conversion_rate_data['Referral-%-Level-2'][0]);
				$newBonusPercentageLevelTwo = $newBonusData["new_bonus_percent"];
				$levelTwoRefferralCommision = User::calculateCommision($payment_amount,$newBonusPercentageLevelTwo);
				if (isset($levelTwoRefferralCommision) && !empty($levelTwoRefferralCommision) && ($levelTwoRefferralUserDetails->user_opt_bonus == 1 || $levelTwoRefferralUserDetails->admin_opt_bonus == 1)) 
				{
					$levelTwoRefferralUserDetails->addValue($txtBalance, $levelTwoRefferralCommision);
					$levelTwoRefferralUserDetails->save();				
					$levelTwoUnpaidBonus=0;
					$unpaid_bonus_percent_level_two = $newBonusData["unpaid_bonus_percent"];
					$levelTwoUnpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent_level_two);
					Transactions::createTransaction($levelTwoRefferralUserDetails->id, $payment_units, $levelTwoRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $levelTwoUnpaidBonus);
					
					
					$direct_bonus_ELT_level_two = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-2-Bonus-ELT']])->get();
					$ELT_bonus_level_two = $direct_bonus_ELT_level_two[0]->defined_value;
					if (isset($ELT_bonus_level_two) && $ELT_bonus_level_two > 0) 
					{
						$levelTwoRefferralUserDetails->addValue('ELT_balance', $ELT_bonus_level_two);
						$levelTwoRefferralUserDetails->save();					
						Transactions::createTransaction($levelTwoRefferralUserDetails->id, 'ELT', $ELT_bonus_level_two, 'Level-2 Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
					}
				}
				else 
				{
					$unpaid_bonus_amount = User::calculateCommision($payment_amount,$conversion_rate_data['Referral-%-Level-2'][0]);
					Transactions::createEmptyTransaction($levelTwoRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
				}			
			
				
				$levelThreeRefferralUserDetails = User::find($levelTwoRefferralUserDetails->referrer_user_id);
				if(isset($levelThreeRefferralUserDetails->id) && !empty($levelThreeRefferralUserDetails->id)) 
				{
					$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($levelThreeRefferralUserDetails->id,$conversion_rate_data['Referral-%-Level-3'][0]);			
					$newBonusPercentageLevelThree = $newBonusData["new_bonus_percent"];
					$levelThreeRefferralCommision = User::calculateCommision($payment_amount, $newBonusPercentageLevelThree);
					if (isset($levelThreeRefferralCommision) && !empty($levelThreeRefferralCommision) && ($levelThreeRefferralUserDetails->user_opt_bonus == 1 || $levelThreeRefferralUserDetails->admin_opt_bonus == 1)) 
					{
						$levelThreeRefferralUserDetails->addValue($txtBalance, $levelThreeRefferralCommision);
						$levelThreeRefferralUserDetails->save();
						$levelThreeUnpaidBonus=0;
						$unpaid_bonus_percent_level_three = $newBonusData["unpaid_bonus_percent"];
						$levelThreeUnpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent_level_three);
						Transactions::createTransaction($levelThreeRefferralUserDetails->id, $payment_units, $levelThreeRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $levelThreeUnpaidBonus);
						
						$direct_bonus_ELT_level_three = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-3-Bonus-ELT']])->get();
						$ELT_bonus_level_three = $direct_bonus_ELT_level_three[0]->defined_value;
						if(isset($ELT_bonus_level_three) && $ELT_bonus_level_three > 0) 
						{
							$levelThreeRefferralUserDetails->addValue('ELT_balance', $ELT_bonus_level_three);
							$levelThreeRefferralUserDetails->save();
							Transactions::createTransaction($levelThreeRefferralUserDetails->id, 'ELT', $ELT_bonus_level_three, 'Level-3 Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
						}
					}
					else 
					{
						$unpaid_bonus_amount = User::calculateCommision($payment_amount,$conversion_rate_data['Referral-%-Level-3'][0]);
						Transactions::createEmptyTransaction($levelThreeRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
					}
				
				
					$levelFourRefferralUserDetails = User::find($levelThreeRefferralUserDetails->referrer_user_id);				
					if (isset($levelFourRefferralUserDetails->id) && !empty($levelFourRefferralUserDetails->id)) 
					{
						$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($levelFourRefferralUserDetails->id,$conversion_rate_data['Referral-%-Level-4'][0]);									
						$newBonusPercentageLevelFour = $newBonusData["new_bonus_percent"];
						$levelFourRefferralCommision = User::calculateCommision($payment_amount, $newBonusPercentageLevelFour);									
						if (isset($levelFourRefferralCommision) && !empty($levelFourRefferralCommision) && ($levelFourRefferralUserDetails->user_opt_bonus == 1 || $levelFourRefferralUserDetails->admin_opt_bonus == 1)) 
						{
							$levelFourRefferralUserDetails->addValue($txtBalance, $levelFourRefferralCommision);
							$levelFourRefferralUserDetails->save();
							$levelFourUnpaidBonus=0;
							$unpaid_bonus_percent_level_four = $newBonusData["unpaid_bonus_percent"];
							$levelFourUnpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent_level_four);								
							Transactions::createTransaction($levelFourRefferralUserDetails->id, $payment_units, $levelFourRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $levelFourUnpaidBonus);
							
							$direct_bonus_ELT_level_four = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-4-Bonus-ELT']])->get();
							$ELT_bonus_level_four = $direct_bonus_ELT_level_four[0]->defined_value;
							if(isset($ELT_bonus_level_four) && $ELT_bonus_level_four > 0) 
							{
								$levelFourRefferralUserDetails->addValue('ELT_balance', $ELT_bonus_level_four);
								$levelFourRefferralUserDetails->save();
								Transactions::createTransaction($levelFourRefferralUserDetails->id, 'ELT', $ELT_bonus_level_four, 'Level-4 Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
							}
						}
						else
						{
							$unpaid_bonus_amount = User::calculateCommision($payment_amount,$conversion_rate_data['Referral-%-Level-4'][0]);
							Transactions::createEmptyTransaction($levelFourRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
						}
				
						
						$levelFiveRefferralUserDetails = User::find($levelFourRefferralUserDetails->referrer_user_id);
						if(isset($levelFiveRefferralUserDetails->id) && !empty($levelFiveRefferralUserDetails->id)) 
						{
							$newBonusData = Transactions::get_new_bonus_percent_per_euro_worth($levelFiveRefferralUserDetails->id,$conversion_rate_data['Referral-%-Level-5'][0]);
							$newBonusPercentageLevelFive = $newBonusData["new_bonus_percent"];
							$levelFiveRefferralCommision = User::calculateCommision($payment_amount, $newBonusPercentageLevelFive);
							if (isset($levelFiveRefferralCommision) && !empty($levelFiveRefferralCommision) && ($levelFiveRefferralUserDetails->user_opt_bonus == 1 || $levelFiveRefferralUserDetails->admin_opt_bonus == 1)) 
							{
								$levelFiveRefferralUserDetails->addValue($txtBalance, $levelFiveRefferralCommision);
								$levelFiveRefferralUserDetails->save();
								$levelFiveUnpaidBonus=0;
								$unpaid_bonus_percent_level_five = $newBonusData["unpaid_bonus_percent"];
								$levelFiveUnpaidBonus = User::calculateCommision($payment_amount,$unpaid_bonus_percent_level_five);		
								Transactions::createTransaction($levelFiveRefferralUserDetails->id, $payment_units, $levelFiveRefferralCommision, 'Commission by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus', $levelFiveUnpaidBonus);

								
								$direct_bonus_ELT_level_five = Configurations::where([['valid_to', '9999-12-31'], ['name', 'Referral-Level-5-Bonus-ELT']])->get();
								$ELT_bonus_level_five = $direct_bonus_ELT_level_five[0]->defined_value;
								
								if(isset($ELT_bonus_level_five) && $ELT_bonus_level_five > 0) 
								{
									$levelFiveRefferralUserDetails->addValue('ELT_balance', $ELT_bonus_level_five);
									$levelFiveRefferralUserDetails->save();
									Transactions::createTransaction($levelFiveRefferralUserDetails->id, 'ELT', $ELT_bonus_level_five, 'Level-5 Bonus ELT by User referral: ' . $user->email, 1, uniqid(), $phaseId, NULL, 1, $parent_transaction_id, 'bonus');
								}
							}
							else
							{
								$unpaid_bonus_amount = User::calculateCommision($payment_amount,$conversion_rate_data['Referral-%-Level-5'][0]);
								Transactions::createEmptyTransaction($levelFiveRefferralUserDetails->id, $payment_units, 'No bonus', uniqid(),$parent_transaction_id, 'bonus', $unpaid_bonus_amount);
							}
						}
					}
				}
			}
		}
		*/
	}
	
	
	/**
     *Show the user Withdraw section
    */
    public function withdrawbalance(Request $request)
    {
        return view('withdraw_balance');
    }


}