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
            if(!Storage::disk('local')->exists('public/surveys'))
            {
                $permissions = intval( config('permissions.directory'), 8 );
                Storage::disk('local')->makeDirectory('public/surveys', $permissions, true);
            }

            $base64_str = substr($img_str, strpos($img_str, ",")+1);
            $base64_image = base64_decode($base64_str);
            $image_filename = 'survey-'.time().'.png';
            $image_url = 'public/surveys/'.$image_filename;
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

if(!function_exists('read_doc')) {
    function read_doc($filename) {
        $striped_content = '';
        $content = '';
        if(!$filename || !file_exists($filename)) return false;
        $zip = zip_open($filename);
        if (!$zip || is_numeric($zip)) return false;
        while ($zip_entry = zip_read($zip)) {
            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;
            if (zip_entry_name($zip_entry) != "word/document.xml") continue;
            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
        }

        zip_close($zip);
        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $striped_content = strip_tags($content);
        return $striped_content;
    }
}

if(!function_exists('get_survey_from_doc')) {
    function get_survey_from_doc($content) {
        $survey = [
            'title' => '',
            'intro' => '',
            'questions' => []
        ];

        if($content !== false) {
            $title_check = strpos($content, 'Title');
            $intro_check = strpos($content, 'Intro');

            $content_list = explode("\r\n", $content);

            $title = '';
            $title_flag = false;

            $intro = '';
            $intro_flag = false;

            $question_list = [];
            $question = [];
            $answers = [];

            $question_flag = false;
            for($i = 0; $i < count($content_list); $i++) {
                $line = $content_list[$i];

                // Parse Title
                if(!$title_flag && $title_check > 0 ) {
                    $title_pos = strpos($line, 'Title');
                    if($title_pos !== false) {
                        $removed_title = str_replace('Title', '', $line);
                        $line = $removed_title;
                        $title_flag = true;
                    }

                    $title .= $line.' ';
                    continue;
                }

                // Parse Intro
                if(!$intro_flag && $intro_check > 0) {
                    $intro_pos = strpos($line, 'Intro Text');
                    if($intro_pos !== false) {
                        $removed_intro = str_replace('Intro Text', '', $line);
                        $line = $removed_intro;
                        $intro_flag = true;
                    }

                    $intro .= $line.' ';
                    continue;
                }

                // Question and Answers
                $colon_pos = strpos($line, ':');
                $ar_question_pos = strpos($line, '؟');
                $en_question_pos = strpos($line, '?');
                $q_no = preg_split('/(?<=[0-9]. )(?)/i',$line);

                if( strpos($line, '_') === false &&
                    $colon_pos !== false ||
                    $ar_question_pos !== false ||
                    $en_question_pos !== false ||
                    (count($q_no) == 2 && strpos($q_no[0], '.') > 0 ) ) {
                    if(count($answers) > 0) {
                        $question['answers'] = $answers;
                        array_push($question_list, $question);
                        $question = [];
                        $answers = [];
                        $question_flag = false;
                    }

                    // remove no and full stop
                    if (count($q_no) == 2 && strpos($q_no[0], '.') > 0 ) {
                        $line = $q_no[1];
                    }

                    $question['question'] = $line;
                    $question['answers'] = [];
                    $question_flag = true;

                    continue;
                }

                if($question_flag) {
                    array_push($answers, $line);
                }

                if($i == count($content_list) -1 && count($answers) > 0 ) {
                    $question['answers'] = $answers;
                    array_push($question_list, $question);
                    $question = [];
                    $answers = [];
                    continue;
                }
            }
            // return survey
            $survey['title'] = $title;
            $survey['intro'] = $intro;
            $survey['questions'] = $question_list;
        }

        return $survey;
    }
}