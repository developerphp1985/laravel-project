<?php

function getFileExtension($filename){
    if(!empty($filename)){
        $tmpArr = explode('.',$filename);
        return end($tmpArr);
    }
    return '';
}

function formatUsPhoneNumber($number)
{
    if(!empty($number))
    {
        return "(".substr($number, 0, 3).") ".substr($number, 3, 3)."-".substr($number,6);
    }
    else
    {
        return ''; 
    }
}

function filterString($string)
{
    return (trim(htmlentities(preg_replace('!\s+!u', ' ', $string))));
}

function showBootStrapMsg($msg,$msgType='success')
{
    return '<div class="alert alert-'.$msgType.' alert-dismissable">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    <strong>'.ucfirst($msgType).'!</strong> '.$msg.'</div>';
}

function showCustomMsg($msg,$msgType='success'){
    return '<div class="alert alert-'.$msgType.'"></strong> '.$msg.'</div>';
}

////get distance between zipcode
function getDistanceBetweenZipcodes($fromzipcode,$tozipcode){
	$distance = 0;
	$url = 'http://maps.googleapis.com/maps/api/distancematrix/json?origins='.$fromzipcode.'&destinations='.$tozipcode.'&mode=driving&language=en-EN&sensor=false';
	$process = curl_init($url);
	curl_setopt($process, CURLOPT_TIMEOUT, 10000);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
	$response = curl_exec($process);	
	curl_close($process);
	if($response){
		$arrResponse = json_decode($response);
		///distance in meter
		$distance = $arrResponse->rows[0]->elements[0]->distance->value;		
	}
	return $distance;
}
////get distance from zipcode
function getDistanceFromZipcodes($zipcode){
	$distance = 0;	
	$url = 'http://maps.googleapis.com/maps/api/geocode/json?address='.$zipcode.'&sensor=false';
	$process = curl_init($url);
	curl_setopt($process, CURLOPT_TIMEOUT, 10000);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
	$response = curl_exec($process);	
	curl_close($process);   
	if($response){
		$arrResponse = json_decode($response);
        if($arrResponse->status=='OK'){
            $arrLatLong = $arrResponse->results[0]->geometry->location;
        }else{
           $arrLatLong = array(); 
        }	
	}	
	return $arrLatLong;
}

// convert into US date format
function viewUSDateFormat($dateTime){
    if(!empty($dateTime))
        return date('M-d-Y', strtotime($dateTime));
    return '-';
}



// get domain name from url 
function domain_from_url($url){
    if(!empty($url) && (strpos($url, 'http://') || strpos($url, 'https://'))){
        $parsed_url_array = parse_url($url);
        if(isset($parsed_url_array['host']))
            return $parsed_url_array['host'];            
    }
    return $url;
}


// get current browser detail
function getBrowser($agent = null){
    $u_agent = ($agent!=null)? $agent : $_SERVER['HTTP_USER_AGENT']; 
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version= "";
    $ub = "Chrome";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }

    // Next get the name of the useragent yes seperately and for good reason
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) 
    { 
        $bname = 'Internet Explorer'; 
        $ub = "MSIE"; 
    } 
    elseif(preg_match('/Firefox/i',$u_agent)) 
    { 
        $bname = 'Mozilla Firefox'; 
        $ub = "Firefox"; 
    } 
    elseif(preg_match('/Chrome/i',$u_agent)) 
    { 
        $bname = 'Google Chrome'; 
        $ub = "Chrome"; 
    } 
    elseif(preg_match('/Safari/i',$u_agent)) 
    { 
        $bname = 'Apple Safari'; 
        $ub = "Safari"; 
    } 
    elseif(preg_match('/Opera/i',$u_agent)) 
    { 
        $bname = 'Opera'; 
        $ub = "Opera"; 
    } 
    elseif(preg_match('/Netscape/i',$u_agent)) 
    { 
        $bname = 'Netscape'; 
        $ub = "Netscape"; 
    }
    else
    {
        $bname = 'Google Chrome'; 
        $ub = "Chrome";
    }

    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }

    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= isset($matches['version'][0])?$matches['version'][0]:'';
        }
        else {
            $version= isset($matches['version'][1])?$matches['version'][1]:'';
        }
    }
    else {
        $version= isset($matches['version'][0])?$matches['version'][0]:'';
    }

    // check if we have a number
    if ($version==null || $version=="") {$version="?";}

    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'    => $pattern
    );
}

function filteredWebSiteLink($url){    
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }
    return $url;
}

function filterInputString($inputString){
    return preg_replace('/[^a-zA-Z0-9_ -]/s','',$inputString);
}

