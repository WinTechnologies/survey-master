<?php

if(!function_exists('create_random_string')) {
    function create_random_string($length = 10) {
        $str = "";
        $characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }
        return $str;
    }
}

if(!function_exists('upload_image')) {
    function upload_image($img_str) {
        $image_url = "";
        if(isset($img_str)) {
            // Create Survey Directory in the storage
            if(!Storage::disk('local')->exists('public/surveies'))
            {
                $permissions = intval( config('permissions.directory'), 8 );
                Storage::disk('local')->makeDirectory('public/surveies', $permissions, true);
            }

            $base64_str = substr($img_str, strpos($img_str, ",")+1);
            $base64_image = base64_decode($base64_str);
            $image_filename = 'survey-'.time().'.png';
            $image_url = 'public/surveies/'.$image_filename;
            Storage::disk('local')->put($image_url, $base64_image, 'public');
        }

        return $image_url;
    }
}


if(!function_exists('get_random_session_id')) {
    function get_random_session_id() {
        $t = time().mt_rand();
        $random_session_id = md5('survey-master-'.$t);
        return $random_session_id;
    }
}

if(!function_exists('get_session_id')) {
    function get_session_id($survey_id) {
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
	        $ipaddress = 'UNKNOWN';

        $session_id = md5($ipaddress.':'.$survey_id);
        return $session_id;
    }
}

if(!function_exists('get_device_name')) {
    function get_device_name() {
        $agent = new \Jenssegers\Agent\Agent;

        $device = '';
        if($agent->isMobile())
            $device = 'mobile';
        else if($agent->isTablet())
            $device = 'tablet';
        else if($agent->isDesktop())
            $device = 'desktop';
        else
            $device = $agent->browser();

        return $device;
    }
}

