<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Result;
use App\Models\Survey;
use App\Models\Question;

class ResultController extends Controller
{
    public function basic($survey_id) {
        if(!isset($survey_id))
            return response()->json([
                'message'   =>  'The survey id is null.',
                'result'    =>  null
            ]);

        $session_id = get_session_id($survey_id);
        $random_session_id = get_random_session_id();
        $device = get_device_name();

        $result = [];
        $result['session_id'] = $session_id;
        $result['random_session_id'] = $random_session_id;
        $result['device'] = $device;
        $result['start_at'] = Carbon::parse(now())->format('Y-m-d H:i');
        $result['survey'] = Survey::find($survey_id);
        $result['drop_off_status'] = 'visit';  // visit, starts, drop-off, answered

        return response()->json([
            'message'   =>  'This is the basic information for the survey id '.$survey_id,
            'result'    =>  $result
        ]);
    }

    public function is_answered($survey_id) {
        if(!isset($survey_id))
            return response()->json([
                'message'   =>  'The survey id is null',
                'result'    =>  null,
                'next'      =>  false
            ]);

        $session_id = get_random_session_id($survey_id);
        $is_ever_answered = false;

        $answer_count = Result::where('session_id', $session_id)->where('survey_id', $survey_id)->count();

        if($answer_count > 0 )
            $is_ever_answered != $is_ever_answered;

        return response()->json([
            'message'   =>  'To check whether you\' ever participated to this survey or not.',
            'is_ever_answered'  =>  $is_ever_answered,
            'answer_count'      =>  $answer_count,
            'next'              =>  $is_ever_answered
        ]);
    }

    public function is_expired($survey_id) {
        if(!isset($survey_id)) {
            return response()->json([
                'message'   =>  'The survey id is null.',
                'result'    =>  null,
                'next'      =>  false
            ]);
        }

        $survey = Survey::find($survey_id);
        $setting = DB::table('settings')->where('id', 3)->first();
        $check_timezone = $setting->value;
        date_default_timezone_set($check_timezone);
        $date=date("Y-m-d h:m:s");

        if ($survey->expire_date < $date) {
            return response()->json([
                'message'   =>  'The survey is expired.',
                'result'    =>  null,
                'next'      =>  false
            ]);
        }

        return response()->json([
            'message'   =>  'The survey is not expired.',
            'result'    =>  null,
            'next'      =>  true
        ]);
    }

    public function is_limited($survey_id) {
        if(!isset($survey_id))
            return response()->json([
                'message'   =>  'The survey id is null.',
                'result'    =>  null,
                'next'      =>  false
            ]);

        $survey = Survey::find($survey_id);
        $population = $survey->$population();

        if($survey->limit) {
            $group_size = $population->size_set;
            $limit_number = Population::find($population->parent_set);
            $max_size = ($limit_number->size_set * $group_size) / 100;
            $replies = DB::table('results')->where('survey_id', $survey_id)->where('population_id', $survey->population_id)->count();
            if ($replies >= $max_size) {
                return response()->json([
                    'message'   =>  'This survey has collected enough data.'
                    'next'    =>  false
                ]);
            }
        }

        return response()->json([
            'message'   =>    'This survey is not limited.'
            'next'    =>  true
        ]);
    }

    public function user_answer(Request $request) {
        $message = '';
        $code = 200;
        $status = 'error';

        try {
            // Begin Transaction
            DB::beginTransaction();
            $params = $request->json()->all();
            $basic_info = $params['basic'];
            $survey_id = $basic_info['survey_id'];
            $population_id = $basic_info['population_id'];
            $session_id = $basic_info['session_id'];
            $random_session_id = $basic_info['random_session_id'];

            $referer = $params['referer'];
            $trust = $params['trust'];
            $group_utm = $params['group_utm'];
            $utm_params = $params['utm_params'];

            $answers = $params['answers'];
            $survey = Survey::find($survey_id);
            $questions = $survey->questions();
            $question_list = [];

            $raws = [];
            foreach($questions as $q) {
                $question_list[$q->id] = $q;
            }

            foreach($answers as $row) {
                $question_id = $row['question_id'];
                $answer_id = $row['answer_id'];
                $start_at = $row['start_at'];
                $end_at = $row['end_at'];

                if($question_list[$question_id]->type == 'multi') {
                    $multi_ans = explode(',', $answer_id);

                    foreach($multi_ans as $a_id) {
                        $temp = [
                            'survey_id'         =>  $survey_id,
                            'question_id'       =>  $question_id,
                            'answer_id'         =>  $a_id,
                            'population_id'     =>  $population_id,
                            'session_id'        =>  $session_id,
                            'random_session_id' =>  $random_session_id,
                            'trust'             =>  $trust,
                            'referer'           =>  $referer,
                            'utm_params'        =>  $utm_params,
                            'created_at'        =>  $start_at,
                            'updated_at'        =>  $end_at
                        ];
                        array_push($raws, $temp);
                    }
                } else {
                    $temp = [
                        'survey_id'         =>  $survey_id,
                        'question_id'       =>  $question_id,
                        'answer_id'         =>  $answer_id,
                        'population_id'     =>  $population_id,
                        'session_id'        =>  $session_id,
                        'random_session_id' =>  $random_session_id,
                        'trust'             =>  $trust,
                        'referer'           =>  $referer,
                        'utm_params'        =>  $utm_params,
                        'created_at'        =>  $start_at,
                        'updated_at'        =>  $end_at
                    ];

                    array_push($raws, $temp);
                }
            }
            DB::table('results')->insert($raws);
            // Commit
            DB::commit();
            $message = 'The user answer data has been saved into the database for this survey.';
            $status = 'success';
        } catch (\ErrorException $ex) {
            $status = 'error';
            $message = $ex->getMessage();
            $code = 451;

            DB::rollback();
        } catch( \Illuminate\Database\QueryException $qe) {
            $status = 'error';
            $message =$qe->errorInfo;
            $code = 400;

            DB::rollback();
        }

        return response()->json([
            'status'        =>  $status,
            'message'       =>  $message,
            'code'          =>  $code
        ]);
    }
}
