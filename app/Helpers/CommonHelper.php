<?php
namespace App\Helpers;
use Cache;
use Illuminate\Support\Facades\Auth;
class CommonHelper
{
    public static function get_coinbase_currency($from_currency)
    {
        $currency = $from_currency;
        $url = 'https://api.coinbase.com/v2/exchange-rates?currency=' . $currency;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($output, true);
        return $data;
    }
	
	public static function current_eth_price($currency)
	{
		$currency = "ETH";		
		$url = 'https://api.coinbase.com/v2/exchange-rates?currency='.$currency;
		$ch = curl_init($url);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$output = curl_exec($ch);		
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);		
		$data = json_decode($output,true);
    }
	
	/* Get coin base live rates */
	public static function get_coin_base_curreny($from_currency)
	{
		$currency = $from_currency;		
		$url = 'https://api.coinbase.com/v2/exchange-rates?currency='.$currency;
		$ch = curl_init($url);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$output = curl_exec($ch);		
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);		
		$data = json_decode($output,true);
		return  $data;
	}
	
    public static function get_brave_coin_rates($from_currency, $to_currency, $qty = 1)
    {
        $url = "https://bravenewcoin-v1.p.mashape.com/convert?from=" . $from_currency . "&qty=" . $qty . "&to=" . $to_currency;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Mashape-Key : 2ae9HyLxJymshTQ07YsKS2WHBgJup1lV0zEjsnWsw8XgWWTF5y',
            'Accept : application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($output, true);
        if (isset($data['success']) && $data['success'] == 1) {
            return number_format($data['to_quantity'], 4);
        }
        return 0;
    }
	
	public static function get_coinmarketcap_rates($id, $to_currency='EUR')
    {        
		$url = "https://api.coinmarketcap.com/v2/ticker/".$id."/?convert=" . $to_currency;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Mashape-Key : 2ae9HyLxJymshTQ07YsKS2WHBgJup1lV0zEjsnWsw8XgWWTF5y',
            'Accept : application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);		
        $data = json_decode($output, true);
        if (isset($data['data']['quotes'][$to_currency]['price'])) {
            return number_format($data['data']['quotes'][$to_currency]['price'], 4);
        }
        return 0;
    }
	
    public static function call_eth_api($action,$parameter,$type=1)
	{
		$serverNames = array('webcomclients.in', 'icoweb.lendo.io', 'test.lendo.io');
		if(in_array($_SERVER['SERVER_NAME'],$serverNames))
		{
			$ETH_API = 'http://139.59.90.53:3080/elt/';
		}
		else
		{
			$ETH_API = 'http://139.59.90.53:3076/elt/';
		}		
		$ch = curl_init();
		$param_string = http_build_query($parameter);
		curl_setopt($ch, CURLOPT_URL,$ETH_API.$action);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$param_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		return $server_output;
	}

    public static function get_wallet_balance($wallet_address, $type = 'ETH')
    {
        if ($type == 'ELT') {
            $action_eth = "get_elt_balance";
        } else {
            $action_eth = "get_balance";
        }

        if ($wallet_address == "") {
            return 0;
        } else {
            $data_eth = array("address" => $wallet_address);
            $response_eth = self::call_eth_api($action_eth, $data_eth);
            $eth_data = json_decode($response_eth, true);
            $eth_balance = $eth_data['balance'];
            return $eth_balance;
        }
    }
	
	public static function transfer_elt($address,$to_address,$amount, $type=1)
	{	
		$action_eth  = "transfer";
		
		if($type==2) // for ETH transfer
		{
			$action_eth  = "transfer_eth";
		}
		
		$blockchain_response = array();		
		if(!empty($address) && !empty($to_address) && $amount > 0)
		{
			$data_eth = array("address"=>$address,"to_address"=>$to_address,"amount"=>$amount);
			$response_eth = self::call_eth_api($action_eth,$data_eth);
			$blockchain_response = json_decode($response_eth,true);
			return $blockchain_response;
		}
		return $blockchain_response;
	}
	
	public static function transfer_elt_by_admin($address,$to_address,$amount,$time_stamp)
	{
		$action_eth  = "transfer_by_owner";		
		$blockchain_response = array();		
		if(!empty($address) && !empty($to_address) && $amount > 0)
		{
			$data_eth = array("address"=>$address,"to_address"=>$to_address,"amount"=>$amount,"time_vault"=>$time_stamp);
			$response_eth = self::call_eth_api($action_eth,$data_eth);
			$blockchain_response = json_decode($response_eth,true);
			return $blockchain_response;
		}
		return $blockchain_response;
	}
	
    public static function get_now($wallet_address)
    {
        $action = "get_now";
        if ($wallet_address == "") {
            return 0;
        } else {
            $data = array("address" => $wallet_address);
            $response = self::call_eth_api($action, $data);
            $get_now_data = json_decode($response, true);
            $get_now = $get_now_data['get_now'];
            return $get_now;
        }
    }

    public static function get_locked_till($wallet_address)
    {
        $action = "get_locked_till";
        if ($wallet_address == "") {
            return 0;
        } else {
            $data = array("address" => $wallet_address);
            $response = self::call_eth_api($action, $data);
            $locked_till_data = json_decode($response, true);
            $locked_till = $locked_till_data['locked_till'];
            return $locked_till;
        }
    }

    public static function get_date_diff_in_days($start, $end)
    {
        $start_ts = strtotime($start);
        $end_ts = strtotime($end);
        $diff_in_days = 0;

        if ($start_ts < $end_ts) {
            $datediff = $end_ts - $start_ts;
            $diff_in_days = $datediff / (60 * 60 * 24);
        }
        if ($diff_in_days >= 27 && $diff_in_days <= 54) {
            return "1 Month";
        }
        if ($diff_in_days >= 54 && $diff_in_days <= 81) {
            return "2 Months";
        }
        return "$diff_in_days Days";
    }

    public static function filter_trans_from_desc($Transaction)
    {		
        $description = $Transaction->description;
		if($Transaction->type == '2' || $Transaction->type == '3')
		{
		$description = self::shorter($description,$Transaction,59);
		}
		elseif($Transaction->type == '4')
		{
			$description = self::shorter($description,$Transaction,39);
		}
		else
		{
			$description = self::shorter($description,$Transaction,100);
		}
        return $description;
    }

    public static function format_wallet_balance($wallet_balance,$precision=2)
    {
		if($wallet_balance > 0){
			return floatval(number_format($wallet_balance, $precision, '.', ''));
		}
		else{
			return 0;
		}
    }
	
	public static function format_balance_view($wallet_balance,$precision=2)
    {
		if($wallet_balance > 0){
			return number_format((float)$wallet_balance, $precision);
		}
		else{
			return 0;
		}
    }
	
	public static function floor_format($wallet_balance)
	{
		if($wallet_balance > 0){
			$integer = (int)$wallet_balance;		
			if($wallet_balance > $integer){
				return floor(($wallet_balance*1000000))/1000000;
			}
		}
		return $wallet_balance;
	}
	
	public static function format_wallet_balance_float($wallet_balance,$precision=2)
    {
		if($wallet_balance > 0){
			$integer = (int)$wallet_balance;		
			if($wallet_balance > $integer){
				return number_format((float)$wallet_balance, $precision);
			}
		}
		return $wallet_balance;
    }
	

    public static function countries_without_postal($type = 'all')
    {
        $list = config('global_vars.country_without_postal');
        $allList = array();
        $codeList = array();
        $nameList = array();
        foreach ($list as $name => $code) {
            $codeList[] = trim($code);
            $nameList[] = trim($name);
            $allList[] = array("code" => $code, "name" => $name);
        }
        return array("allList" => $allList, "codeList" => $codeList, "nameList" => $nameList);
    }		
	
	public static function current_week_date_range()
	{
		$sunday = strtotime("last sunday");
		$sunday = date('w', $sunday)==date('w') ? $sunday+7*86400 : $sunday;
		$saturday = strtotime(date("Y-m-d",$sunday)." +6 days");
		$this_week_sd = date("Y-m-d 00:00:00",$sunday);
		$this_week_ed = date("Y-m-d 23:59:59",$saturday);
		return array("start_week"=>$this_week_sd,"end_week"=>$this_week_ed);
	}

	public static function last_week_date_range()
	{
		$previous_week = strtotime("-1 week +1 day");
		$start_week = strtotime("last sunday midnight",$previous_week);
		$end_week = strtotime("next saturday",$start_week);
		$start_week = date("Y-m-d 00:00:00",$start_week);
		$end_week = date("Y-m-d 23:59:59",$end_week);
		return array("start_week"=>$start_week,"end_week"=>$end_week);
	}
	
	public static function shorter($text, $Transaction, $chars_limit = 100)	
	{				
		if(strlen($text) > $chars_limit)		
		{			
			$new_text = substr($text, 0, $chars_limit);

			$new_text = trim($new_text);
			
			return $new_text;
			
		}
		else		
		{			
			return $text;		
		}
	}
	
	public static function session_logout()
	{
		Auth::logout();
		Cache::flush();
		return redirect()->to('/admin99/login')->withErrors(['email' =>'User already logged-in on some other device.']);
	}

	public static function encodeDecode( $string, $action = 'e' ) 
	{
		$secret_key = 'tportal@321';
		
		$secret_iv = 'travelp@456';	 
		
		$output = false;
		
		$encrypt_method = "AES-256-CBC";
		
		$key = hash( 'sha256', $secret_key );
		
		$iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

		if( $action == 'e' ) 
		{
			$output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
		}
		else if( $action == 'd' )
		{
			$output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
		}
		return $output;
	}	
	
	public static function checkHeaderValidation()
	{
		$HTTP_WALLETAPPID = isset($_SERVER['HTTP_WALLETAPPID']) ? $_SERVER['HTTP_WALLETAPPID'] : '';
        $HTTP_WALLETAPPSECRETKEY = isset($_SERVER['HTTP_WALLETAPPSECRETKEY']) ? $_SERVER['HTTP_WALLETAPPSECRETKEY'] : '';
        
        if(env('WALLET_APP_ID') == $HTTP_WALLETAPPID && env('WALLET_APP_SECRET') == $HTTP_WALLETAPPSECRETKEY )
        {
			return true;
		}		
		return false;
	}
	
	
	public static function sendPushNotification($device_type, $device_token, $data = array())
	{
		if($device_type == 'ios')
		{			
			self::sendIosNotification($device_token,$data['msg'] , true, 1);
		}
		elseif($device_type == 'android')
		{
			define('API_ACCESS_KEY', 'AAAAsBmvAMM:APA91bE2uoB11xOw1jYKGcBJjI6yTc-zMDUGRqA8YHbDdDG5WlvDCBFanLSVzU6tjW7NwmuNrO5eUpzonFjkmHvPy3MH11i0HIRmzUHBqSlg09PgURctWfTjcuzJzKEKv85V2_cLxIZh');
						
			$msg = array
            (
                'title' => '',
                'subtitle' => '',
                'fragment' => 'home',
                'msg' => $data['msg']
            );
			
            $fields = array('registration_ids' => array($device_token),'data' => $msg);

            $headers = array
            (
                'Authorization: key=' . API_ACCESS_KEY,
                'Content-Type: application/json'
            );
			
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            curl_close($ch);
		}		
	}
	
	public static function sendIosNotification($token ='', $mess = '', $sound = true,$test=0) 
	{
		if($test==1)
		{
			$root = public_path().'/ck.pem';
			$passphrase = '123456';
			$url="ssl://gateway.sandbox.push.apple.com:2195";
		}
		else
		{
			$root = 'XtagLivePemFileV2.pem'; 		// Where the pem file is located
			$passphrase = 'xtag@007';
			$url="ssl://gateway.push.apple.com:2195";
		}
		
		$deviceToken = $token;
        $message = $mess;
        $response = array();
		
		$response['status'] = false;
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $root);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

        $fp = stream_socket_client($url, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
		
		if (!$fp) 
		{
            $response['message'] = "Failed to connect to server: " . $err . " String error: " . $errstr;
            echo json_encode($response);
            return;
        }
		
        $body['aps'] = array(
            'badge' => +1,
            'alert' => $message,
			'sound' => 'default',
        );
		
		
        $payload = json_encode($body);

        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

        $result = fwrite($fp, $msg, strlen($msg));
		
		if (!$result) {
			$response['status'] = false;
		}else{
            $response['message'] = 'sent';
            $response['status'] = true;
        }
        fclose($fp);
    }
	
	public static function get_eth_balance($address)
	{
		$URL = Config('constants.ROPSTEN_API_URL').'?module=account&action=balance&address='.$address.'&tag=latest';		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $URL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		curl_close($curl);
		$data = json_decode($result,true);	
		$balance = 0;
		if(isset($data['result']))
		{
			$balance = $data['result'];
		}
		return $balance/1000000000000000000;
	}
	
	public static function get_elt_balance($address)
	{
		$contractaddress = config('constants.elt_contract_address');
		
		$URL = Config('constants.ROPSTEN_API_URL').'?module=account&action=tokenbalance&address='.$address.'&tag=latest&contractaddress='.$contractaddress;
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $URL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		curl_close($curl);
		$data = json_decode($result,true);	
		
		$balance = 0;
		
		if(isset($data['result']))
		{
			$balance = $data['result'];
		}
		return $balance/100000000;
		
	}
	
	public static function balance_format($amount,$precision=6)
	{
		return number_format($amount, $precision, '.', '');
	}
	
	public static function fees_format($fees,$precision=6)
	{
		return number_format($fees, $precision, '.', '');
	}
	
	public static function is_float_number($value)
	{
		$integer = (int)$value;		
		if($value > $integer){
			return true;
		}
		return false;
	}	
	
	public static function format_float_balance($value, $precision=2)
	{	
		return self::format_wallet_balance($value,$precision);
	}
	
	public static function set_ranking_order($values)
	{
		arsort($values);
		$result = array();
		$pos = $real_pos = 0;
		$alloted_rank = array();
		$prev_score = -1;
		foreach ($values as $exam_n => $score) {
			$real_pos += 1;
			$pos = ($prev_score != $score) ? $real_pos : $pos;
			$result[$exam_n] = $pos;			
			$prev_score = $score;
		}
		return $result;
	}
	
	public static function get_client_ip() 
	{
		$ipaddress = '';
		if (getenv('HTTP_CLIENT_IP'))
			$ipaddress = getenv('HTTP_CLIENT_IP');
		else if(getenv('HTTP_X_FORWARDED_FOR'))
			$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
		else if(getenv('HTTP_X_FORWARDED'))
			$ipaddress = getenv('HTTP_X_FORWARDED');
		else if(getenv('HTTP_FORWARDED_FOR'))
			$ipaddress = getenv('HTTP_FORWARDED_FOR');
		else if(getenv('HTTP_FORWARDED'))
		   $ipaddress = getenv('HTTP_FORWARDED');
		else if(getenv('REMOTE_ADDR'))
			$ipaddress = getenv('REMOTE_ADDR');
		else
			$ipaddress = '';
		
		$ipaddress = explode(",",$ipaddress);
		
		return trim($ipaddress[0]);
	}
	
	public static function isUserAllowedForSubAdmin($role, $userid)
	{
		$blockedUsersForSubAdmin = config("global_vars.blockedUsersForSubAdmin");
		if($role > 2 && in_array($userid,$blockedUsersForSubAdmin))
		{
			return false;
		}
		return true;
	}
	
	public static function generateInvoiceNumber()
	{
		return date("YmdHis").rand(1000,9999);
	}
	
	public static function generateProformaNumber()
	{
		return rand(10,99).time();		
	}
	
	public static function isUserLogin()
	{
		if(Auth::user()->role == 2){
			return 1;
		}
		return 0;
	}
	
	public static function isAdminLogin()
	{
		if(Auth::user()->role == 1 && Auth::user()->custom_role != 1){
			return 1;
		}
		return 0;
	}
	
	public static function isSuperAdmin()
	{
		if(Auth::user()->role == 1 && Auth::user()->custom_role == 1){
			return 1;
		}
		return 0;
	}
	
	public static function getAppropriateWalletAddress($user, $currency)
    {
        switch ($currency) {
            case 'ELT':
                return $user->ELT_wallet_address;
			case 'ETH':
                return $user->ETH_wallet_address;
            case 'BTC':
                return $user->BTC_wallet_address;
            case 'EUR':
                return '';
            case 'BCH':
                return $user->BCH_wallet_address;
            case 'LTC':
                return $user->LTC_wallet_address;
            case 'XRP':
                return $user->XRP_wallet_address;
            case 'DASH':
                return $user->DASH_wallet_address;
            default:
                return null;
        }
    }
	
	public static function getAppropriateWalletBalance($user, $currency)
    {
        switch ($currency){
			case 'ELT':
                return $user->ELT_balance;
            case 'ETH':
                return $user->ETH_balance;
            case 'BTC':
                return $user->BTC_balance;
            case 'EUR':
                return $user->EUR_balance;
            case 'BCH':
                return $user->BCH_balance;
            case 'LTC':
                return $user->LTC_balance;
            case 'XRP':
                return $user->XRP_balance;
            case 'DASH':
                return $user->DASH_balance;
            default:
                return null;
        }
    }
	
	public static function download_filedata($setCounter,$setExcelName,$setRec,$setMainHeader,$setData,$customHeader,$data, $type='csv')
	{
		if($type == 'xls'){
			$download_extension = 'xls';
			$download_type = 'application/octet-stream';
		}
		else if($type == 'csv'){
			$download_extension = 'csv';
			$download_type = 'application/csv';
		}
		
		ob_clean();
		foreach($customHeader as $key => $value){
			$setMainHeader .= $value."\t";
		}
		
		for($i=0; $i<count($setRec); $i++) {
			$rowLine = '';
			foreach($setRec[$i] as $value){
				if(!isset($value) || $value == "")  {
					$value = "\t";
					} else  {
					$value = strip_tags(str_replace('"', '""', $value));
					$value = '"' . $value . '"' . "\t";
				}
				$rowLine .= $value;
			}
			$setData .= trim($rowLine)."\n";
		}
		$setData = str_replace("\r", "", $setData);
		if ($setData == "") {
			$setData = "\nno matching records found\n";
		}
		$setCounter 	= count($data);
	
		header("Content-type: ".$download_type);
		header("Content-Disposition: attachment; filename=".$setExcelName."_Report.".$download_extension);
		header("Pragma: no-cache");
		header("Expires: 0");
		echo ucwords($setMainHeader)."\n".$setData."\n";
	}
	
	public static function getCryptoManiaEuroWorthPercent($euroWorth)
    {
        $percent = 0;
	
		if($euroWorth <= 4999){
			$percent = 10;
		}
		else if($euroWorth >= 5000 && $euroWorth < 10000){
			$percent = 15;
		}
		else if($euroWorth >= 10000 && $euroWorth < 20000){
			$percent = 20;
		}
		else if($euroWorth >= 20000 && $euroWorth < 50000){
			$percent = 30;
		}
		else if($euroWorth >= 50000 && $euroWorth < 100000){
			$percent = 75;
		}
		else if($euroWorth >= 100000){
			$percent = 100;
		}		
		return $percent;
	}
	
	public static function withdraw_status($status=0)
	{
		$statusMessage = '';
		if($status == 0)
		{
			$statusMessage = 'Pending';
		}
		else if($status == 1)
		{
			$statusMessage = 'Approved';
		}
		else if($status == 2)
		{
			$statusMessage = 'Processing';
		}
		else if($status == 3)
		{
			$statusMessage = 'Rejected';
		}
		else
		{
			$statusMessage = 'Cancelled';
		}
		return $statusMessage;
	}
	
	
}