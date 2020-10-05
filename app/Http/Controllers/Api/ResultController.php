<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Result;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Setting;
use App\Models\Population;
use \stdClass;

class ResultController extends Controller
{
    public function basic($survey_id) {
        if(!isset($survey_id))
            return response()->json([
                'message'   =>  'The survey id is null.',
                'result'    =>  null,
                'code'      =>  200,
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
        $result['referer'] = request()->headers->get('referer');

        return response()->json([
            'message'   =>  'This is the basic information for the survey id '.$survey_id,
            'result'    =>  $result,
            'code'      => 200
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
            'next'              =>  true
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

        if ($survey->expired_at < $date) {
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

    public function is_limited($survey_id, $population_id) {
        if(!isset($survey_id))
            return response()->json([
                'message'   =>  'The survey id is null.',
                'result'    =>  null,
                'next'      =>  false,
                'code'     => 200
            ]);

        $survey = Survey::find($survey_id);
        $population = Population::find($population_id);

        if($survey->limit) {
            $group_size = $population->size_set;
            $limit_number = Population::find($population->parent_set);
            $max_size = ($limit_number->size_set * $group_size) / 100;
            $replies = DB::table('results')->where('survey_id', $survey_id)->where('population_id', $population_id)->count();
            if ($replies >= $max_size) {
                return response()->json([
                    'message'   =>  'This survey has collected enough data.',
                    'next'      =>  false,
                    'code'     => 200
                ]);
            }
        }

        return response()->json([
            'message'   =>  'This survey is not limited.',
            'next'      =>  true,
            'code'      =>  200
        ]);
    }

    public function test() {
        $survey_id = 106;
        $trend = '';
        $trust = '';
        $population = '';

        $group_total_participates = Result::get_pariticipants_by_group($survey_id);
        $population_result = Result::get_populations($survey_id);
        $all_size_set = 0;
        $population_list = [];
        $total_participates = $group_total_participates['total_participates'];

        foreach($population_result as $row) {
            $utm = $row->utm;
            if($utm == null) {
                $all_size_set = $row->size_set;
                continue;
            }

            $group_id = $row->id;
            $group_name = $row->group_name;
            $size_set = $row->size_set;

            if($total_participates  > 0 && isset($group_total_participates[$utm]) && $group_total_participates[$utm] > 0){
                $vote_fixed = round($size_set / ($group_total_participates[$utm] / $total_participates * 100),2);
            } else
                $vote_fixed = 0;

            $population_list[$utm] = ['group_id' => $group_id, 'group_name' => $group_name, 'size_set' => $size_set, 'value_in_set' => $all_size_set * $size_set / 100, 'total' => $all_size_set, 'vote_fixed' => $vote_fixed];
        }

        $q_total = Result::get_question_total($survey_id, $trend, $trust, $population);
        $qa_total_result = Result::get_qa_total($survey_id, $trend, $trust, $population);

        $qa_total = [];
        foreach($qa_total_result as $row) {
            $question_id = $row->question_id;
            $answer = $row->answer;
            $utm = $row->utm;
            $total = $row->total;
            $group_name = $row->group_name;

            if($utm == 'general' || $answer == "" || $answer == null)
                continue;

            if($total != null && $utm != null ) {
                $qa_total[$question_id][$utm]['utm'] = $utm;
                $qa_total[$question_id][$utm]['group'] = $group_name;

                if(!array_key_exists('data', $qa_total[$question_id][$utm])) {
                    $qa_total[$question_id][$utm]['data'] = [];
                    $qa_total[$question_id][$utm]['total'] = 0;
                }

                $question_with_calc = $population_list[$utm]['vote_fixed'] * $total;

                $qa_total[$question_id][$utm]['data'][$answer] = $question_with_calc;
                $qa_total[$question_id][$utm]['total'] += $question_with_calc;
            }
        }

        // Question and Answers
        $question_answers_result = Question::question_answers_by_survey($survey_id);
        $result = [];
        foreach($question_answers_result as $row) {
            $result[$row->question_id]['question'] = $row->question;
            $type = $row->type;
            $point = $row->point;
            $answer_id = $row->answer_id;

            if ($type == 'rating') {
                $result[$row->question_id]['answer'] = [1,2,3,4,5];
            } else if ($type == 'mark') {
                $temp1 = [];
                for ($r=0; $r < ($point+1); $r++) {
                    array_push($temp1, $r);
                }
                $result[$row->question_id]['answer'] = $temp1;
            } else {
                if(!array_key_exists('answer', $result[$row->question_id])){
                    $result[$row->question_id]['answer'] = [];
                }

                $result[$row->question_id]['answer'][$answer_id] = $row->content;
            }
            $q_id = isset($qa_total[$row->question_id])?$qa_total[$row->question_id]:'';
            $result[$row->question_id]['ans'] = $q_id;

            $result[$row->question_id]['total_count'] = 0;
            foreach($qa_total[$row->question_id] as $data) {
                $result[$row->question_id]['total_count'] += $data['total'];
            }
        }
        $output = '';
        $output .= '<table style="border: 1px solid #000;">';
        foreach($result as $row) {
            $output .= "<tr><td style='border: 1px solid #000; background-color: #FFFF00; font-size:17px; font-family: Sakkal Majalla;'>".$row['question']."</td><td></td></tr>";

            $output .= '<tr>';
            $output .= '<th style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">';
            $output .= '';
            $output .= '</th>';

            $output .= '<th style="background-color: #43a1f7; border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">';
            $output .= 'العدد';
            $output .= '</th>';

            $output .= '<th style="background-color: #43a1f7; border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">';
            $output .= 'النسبة';
            $output .= '</th>';

            $output .= '</tr>';
            $total_count = $row['total_count'];

            if(!is_null($row['ans'])){
                $percent_data = [];

                foreach($row['ans'] as $utm) {
                    foreach($row['answer'] as $answer_id => $answer) {
                        $data = 0;

                        if(array_key_exists($answer_id, $utm['data']))
                        {
                            $data = $utm['data'][$answer_id];

                            if(!array_key_exists($answer_id, $percent_data)) {
                                $percent_data[$answer_id]['data'] = $data;
                                $percent_data[$answer_id]['answer_id'] = $answer_id;
                            } else {
                                $percent_data[$answer_id]['data'] += $data;
                                $percent_data[$answer_id]['answer_id'] = $answer_id;
                            }
                        }
                    }
                }

                $total = 0;

                foreach($percent_data as $utm_data) {
                    $output .= '<tr>';
                    $percent = round($utm_data['data'] / $total_count * 100,2);
                    $answer_id = $utm_data['answer_id'];
                    $total += $utm_data['data'];
                    $output .= '<td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$row['answer'][$answer_id].'</td>';
                    $output .= '<td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$utm_data['data'].'</td>';
                    $output .= '<td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$percent.'%</td>';
                    $output .= '</tr>';
                }

                $output .= '<tr><td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">المجموع</td><td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$total.'</td><td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">100%</td></tr>';
                $output .= '<tr><td></td><td></td><td></td></tr>';
                $output .= '<tr><td></td><td></td><td></td></tr>';
            }
        }


        dd($output);
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
            $survey_id = $params['survey_id'];
            $survey = Survey::find($survey_id);

            $population_id = isset($params['population_id'])?$params['population_id']:$survey->population->id;

            $session_id = $basic_info['session_id'];
            $random_session_id = $basic_info['random_session_id'];

            $referer = $params['referer'];
            $utm_params = $params['utm_params'];

            $answers = $params['answers'];
            $survey = Survey::find($survey_id);
            $questions = $survey->questions;
            $question_list = [];
            $reliablity_questions = [];

            foreach($questions as $q) {
                $question_list[$q->id] = $q;
                $reliablity_questions[$q->id] = $q->is_reliability;
            }

            // Trst: Because it's not so clear this part that I only copied the logic from the previous wp-code
            $setting = Setting::where('name', 'Reliability Questions')->first();
            $trust = true;

            if($setting->value) {
                $population = Population::find($population_id);
                $utm = $population->utm;

                foreach($answers as $row) {
                    $question_id = $row['question_id'];
                    $answer_id = $row['answer'];
                    $is_reliability = $reliablity_questions[$question_id];
                    if($is_reliability) {
                        $sql = 'SELECT id FROM answers WHERE question_id='.$question_id.' AND correct LIKE "%'.$utm.'%" LIMIT 1';
                        $correct_answers_result = DB::select($sql);
                        if(empty($correct_answers_result) || $answer_id != $correct_answers_result[0]->id){
                            $trust = false;
                        }
                    }
                }
            }

            $results = [];
            foreach($answers as $row) {
                $question_id = $row['question_id'];
                $answer_id = $row['answer'];
                $start_at = $row['start_at'];
                $end_at = $row['end_at'];

                if($question_list[$question_id]->type == 'multi') {
                    $multi_ans = explode(',', $answer_id);

                    foreach($multi_ans as $a_id) {
                        if($a_id == null)
                            continue;

                        $temp = [
                            'survey_id'         =>  $survey_id,
                            'question_id'       =>  $question_id,
                            'answer'            =>  $a_id,
                            'population_id'     =>  $population_id,
                            'session_id'        =>  $session_id,
                            'random_session_id' =>  $random_session_id,
                            'trust'             =>  $trust,
                            'referer'           =>  $referer,
                            'utm_params'        =>  $utm_params,
                            'created_at'        =>  $start_at,
                            'updated_at'        =>  $end_at
                        ];
                        array_push($results, $temp);
                    }
                } else {
                    $temp = [
                        'survey_id'         =>  $survey_id,
                        'question_id'       =>  $question_id,
                        'answer'            =>  $answer_id,
                        'population_id'     =>  $population_id,
                        'session_id'        =>  $session_id,
                        'random_session_id' =>  $random_session_id,
                        'trust'             =>  $trust,
                        'referer'           =>  $referer,
                        'utm_params'        =>  $utm_params,
                        'created_at'        =>  $start_at,
                        'updated_at'        =>  $end_at
                    ];

                    array_push($results, $temp);
                }
            }

            DB::table('results')->insert($results);
            $survey->increment('views');
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

    public function get_random_session_ids($survey_id) {
        $return = Result::get_random_session_ids($survey_id);

        return response()->json([
            'message'   =>  'Return participated people by survey_id',
            'result'    =>  $return,
            'code'      =>  200
        ]);

    }

    public function get_random_session_ids_by_filter($survey_id, $params) {
        $check_result = isset($params['check-result'])?true:false;
        $random_session_ids = '';

        $min = isset($params['min'])?$params['min']:'';
        $max = isset($params['min'])?$params['min']:'';

        $limit_where = '';
        $user_list = [];

        if($min && $max) {
            $limit = $max - $min;
            $limit_where = "LIMIT ".$limit." OFFSET ".$min;

            $trend_sql = "SELECT results.random_session_id
                            FROM results
                            INNER JOIN populations p on p.id = results.population_id
                            WHERE survey_id=".$survey_id."
                            GROUP BY random_session_id ORDER BY results.id ASC LIMIT ".$limit." OFFSET ".$min;

            $random_session_id_result = DB::select($trend_sql);

            foreach($random_session_id_result as $row) {
                array_push($user_list, "'".$row->random_session_id."'");
            }

        }

        if($check_result) {
            $questions = Question::where('survey_id', $survey_id)->get();
            $where_data_filter = [];

            foreach($questions as $value) {
                if ($value->type != 'custom-text' && $value->type != 'custom-number' && $value->type != 'custom-date' && $value->type != 'custom-free-text' && $value->type != 'question-with-video') {
                    if (isset($params['ques_id_'.$value->id])) {
                        $where_data_filter[$value->id] = $params['ques_id_'.$value->id];
                    }
                }
            }

            $check_temp = [];
            foreach($where_data_filter as $q_id => $answer) {
                $qa_where = '(question_id='.$q_id.' AND answer='.$answer.') ';
                array_push($check_temp, $qa_where);
            }

            $check_filter = join(' OR ', $check_temp);
            if(count($check_temp) > 0 ) {
                if(count($check_temp)>1)
                    $random_session_id_sql = 'SELECT random_session_id FROM results WHERE survey_id='.$survey_id.' AND ('.$check_filter.') GROUP BY random_session_id HAVING count(random_session_id) > 1';
                else
                    $random_session_id_sql = 'SELECT distinct random_session_id FROM results WHERE survey_id='.$survey_id.' AND ('.$check_filter.') ';

                $random_session_id_result = DB::select($random_session_id_sql);
                foreach($random_session_id_result as $row) {
                    array_push($user_list, "'".$row->random_session_id."'");
                }
            }
        }

        $random_session_ids = "";
        if(count($user_list) > 0) {
            $random_session_id_list = implode (',', $user_list);
            $random_session_ids = str_replace("\'", "'", "AND random_session_id IN (".$random_session_id_list.")");
        }


        return $random_session_ids;
    }

    public function total_by_population(Request $request, $survey_id) {
        $params = $request->all();

        $where_conditions = get_where_conditions($params);

        $trend = $where_conditions['trend'];
        $trust = $where_conditions['trust'];
        $population = $where_conditions['population'];

        // Filter
        $random_session_id = $this->get_random_session_ids_by_filter($survey_id, $params);

        if(strlen($random_session_id) > 0)
            $trend = $random_session_id;

        session(['trend' => $trend, 'trust' => $trust, 'population' => $population]);
        // Get Population
        $sql = "SELECT p.*
                FROM populations p
                LEFT JOIN surveys s ON  s.population_id = p.parent_set
                WHERE s.id =".$survey_id;

        $group_info =  DB::select($sql);
        $group_result = array();
        foreach($group_info as $row) {
            $temp = new stdClass();

            $temp->group_id = $row->id;
            $temp->group_name = $row->group_name;
            $temp->parent_set = $row->parent_set;
            $temp->size_set = $row->size_set;
            $temp->utm = $row->utm;
            $temp->participants = 0;

            $group_result[$row->id] = $temp;
        }

        $sql = "SELECT result.id, COUNT(result.id) AS participants
                FROM (SELECT results.random_session_id, populations.id
                        FROM results
                        LEFT JOIN questions ON questions.id = results.question_id
                        INNER JOIN populations ON populations.id = results.population_id
                        WHERE results.survey_id = ".$survey_id." ".$population." ".$trust." ".$trend."
                        GROUP BY results.random_session_id, populations.id) result
                GROUP BY result.id";


        $results = DB::select($sql);
        $total_participates = 0;

        foreach($results as $row) {
            $total_participates += $row->participants;

            if(isset($group_result[$row->id]))
                $group_result[$row->id]->participants = $row->participants;

            $percent = 0;
            $real_percent = 0;
            $vote_fixed_value = 0;

            if($row->participants > 0) {
                $percent = $row->participants/$total_participates*100;
                $size_set = isset($group_result[$row->id]->size_set)?$group_result[$row->id]->size_set:1;
                $real_percent = round($size_set,2);
                $vote_fixed_value = round($size_set / $percent, 2);
            }

            if(isset($group_result[$row->id])) {
                $group_result[$row->id]->percent = $percent;
                $group_result[$row->id]->real_percent = $real_percent;
                $group_result[$row->id]->vote_fixed_value = $vote_fixed_value;
            }
        }

        $return = new stdClass;
        $return->total = $total_participates;
        $return->group = $group_result;

        return response()->json([
            'message'   =>  'Return total participants by population',
            'result'    =>  $return,
            'code'      =>  200
        ]);
    }

    public function get_insight_by_survey(Request $request, $survey_id) {
        $params = $request->all();
        $where_conditions = get_where_conditions($params);

        $trend = $where_conditions['trend'];
        $trust = $where_conditions['trust'];
        $population = $where_conditions['population'];
        $device = $where_conditions['device'];
        $dropoff_trend = $where_conditions['dropoff_trend'];
        // Filter
        $random_session_id = $this->get_random_session_ids_by_filter($survey_id, $params);

        if(strlen($random_session_id) > 0)
            $trend = $random_session_id;

        session(['trend' => $trend, 'trust' => $trust, 'population' => $population]);


        $sql = "SELECT random_session_id, COUNT(random_session_id) as answer_count, answer_status
                FROM drop_offs
                WHERE survey_id = ".$survey_id." ".$device." ".$dropoff_trend." GROUP BY random_session_id, device, answer_status";

        $insight_result = DB::select($sql);
        $insight_list = [];

        $start_count = 0;
        $response_count = 0;

        $started_list = [];
        $answered_list = [];

        foreach($insight_result as $row) {
            if($row->answer_status == 'started')
                array_push($started_list, $row->random_session_id);

            if($row->answer_status == 'answered') {
                array_push($answered_list, $row->random_session_id);
            }

            array_push($insight_list, $row->random_session_id);
        }

        foreach($answered_list as $random_session_id) {
            if(!in_array($random_session_id, $started_list))
                $response_count++;
        }

        $uniqued = array_unique($insight_list);
        $visits = count($uniqued);
        $start_count = count(array_unique($started_list)) + $response_count;

        $completion_rate = 0;

        if($start_count > 0 && $response_count > 0)
            $completion_rate = round(($response_count/$start_count)*100, 2);

        // Get Population
        $drop_off_sql = "SELECT DISTINCT random_session_id FROM drop_offs WHERE survey_id=".$survey_id." ".$device." ".$dropoff_trend;
        $drop_off_results = DB::select($drop_off_sql);
        $random_session_ids = [];
        foreach($drop_off_results as $row) {
            array_push($random_session_ids, "'".$row->random_session_id."'");
        }

        if(count($random_session_ids) > 0) {
            $random_session_id_list = implode (',', $random_session_ids);
        } else {
            $random_session_id_list = "'no_attend'";
        }

        $rsid = str_replace("\'", "'", "AND random_session_id IN (".$random_session_id_list.")");

        $sql = "SELECT SEC_TO_TIME(AVG(answer_time)) as answer_time FROM (SELECT results.random_session_id, results.referer, MIN(results.created_at) as start_at, MAX(results.updated_at) as end_at,  TIME_TO_SEC(TIMEDIFF(MAX(results.updated_at), MIN(results.created_at))) as answer_time
                    FROM results
                    WHERE results.survey_id = ".$survey_id." ".$trend." ".$population." ".$trust." ".$rsid;

        $sql .= " GROUP BY results.random_session_id
                                    ORDER BY results.id ASC) t";

        $answer_time_result = DB::select($sql);
        $avg_answer_time = isset($answer_time_result[0]->answer_time)?$answer_time_result[0]->answer_time:'00:00:00';

        $response_rate = 0;
        if($visits > 0 && $response_count > 0)
            $response_rate = round(($response_count/$visits)*100, 2);

        // Drop-off result by question
        $sql = "SELECT drop_offs.question_id, q.question, drop_offs.answer_status, count(drop_offs.answer_status) as answer_count
                FROM drop_offs
                LEFT JOIN questions q ON q.id = drop_offs.question_id
                WHERE
                drop_offs.survey_id = ".$survey_id." ".$device." ".$dropoff_trend."
                GROUP BY q.id, drop_offs.answer_status";
        $drop_off_by_question_result = DB::select($sql);
        $drop_off_list = [];

        foreach($drop_off_by_question_result as $row) {
            if(!isset($drop_off_list[$row->question_id]['no_answered']))
                $drop_off_list[$row->question_id]['no_answered'] = 0;

            if(!isset($drop_off_list[$row->question_id]['all_visit']))
                $drop_off_list[$row->question_id]['all_visit'] = 0;

            if($row->answer_status !== "answered")
                $drop_off_list[$row->question_id]['no_answered'] += $row->answer_count;


            $drop_off_list[$row->question_id]['all_visit'] += $row->answer_count;

            $drop_off_list[$row->question_id]['question'] = $row->question;
        }

        $drop_offs = [];
        foreach($drop_off_list as $q_id => $d) {
            $percent =round( $d['no_answered'] / $d['all_visit'] * 100, 2);
            $drop_offs[$q_id]['percent'] = $percent;
            $drop_offs[$q_id]['question'] = $d['question'];
        }

        $return = [
            'visits'        =>  $visits,
            'start'         => $start_count,
            'responses'     => $response_count,
            'completion_rate' => $completion_rate,
            'response_rate' => $response_rate,
            'average_time'  =>  $avg_answer_time,
            'drop_off_list' =>   $drop_offs
        ];

        return response()->json([
            'message'   =>  'Return data for insight tab on the live result page.',
            'result'    =>  $return,
            'code'      =>  200
        ]);
    }

    public function get_weight_by_survey(Request $request, $survey_id) {
        $params = $request->all();
        $where_conditions = get_where_conditions($params);

        $trend = $where_conditions['trend'];
        $trust = $where_conditions['trust'];
        $population = $where_conditions['population'];

        // Filter
        $random_session_id = $this->get_random_session_ids_by_filter($survey_id, $params);

        if(strlen($random_session_id) > 0)
            $trend = $random_session_id;

        session(['trend' => $trend, 'trust' => $trust, 'population' => $population]);

        $group_total_participates = Result::get_pariticipants_by_group($survey_id);
        $population_result = Result::get_populations($survey_id);

        $all_size_set = 0;
        $population_list = [];
        $total_participates = $group_total_participates['total_participates'];

        foreach($population_result as $row) {
            $utm = $row->utm;
            if($utm == null) {
                $all_size_set = $row->size_set;
                continue;
            }

            $group_id = $row->id;
            $group_name = $row->group_name;
            $size_set = $row->size_set;

            if($total_participates  > 0 && isset($group_total_participates[$utm]) && $group_total_participates[$utm] > 0){
                $vote_fixed = round($size_set / ($group_total_participates[$utm] / $total_participates * 100),2);
            } else
                $vote_fixed = 0;

            $population_list[$utm] = ['group_id' => $group_id, 'group_name' => $group_name, 'size_set' => $size_set, 'value_in_set' => $all_size_set * $size_set / 100, 'total' => $all_size_set, 'vote_fixed' => $vote_fixed];
        }

        $q_total = Result::get_question_total($survey_id, $trend, $trust, $population);
        $qa_total_result = Result::get_qa_total($survey_id, $trend, $trust, $population);

        $qa_total = [];
        foreach($qa_total_result as $row) {
            $question_id = $row->question_id;
            $answer = $row->answer;
            $utm = $row->utm;
            $total = $row->total;
            $group_name = $row->group_name;

            if($utm == 'general' || $answer == "" || $answer == null)
                continue;

            if($total != null && $utm != null ) {
                $qa_total[$question_id][$utm]['utm'] = $utm;
                $qa_total[$question_id][$utm]['group'] = $group_name;

                if(!array_key_exists('data', $qa_total[$question_id][$utm])) {
                    $qa_total[$question_id][$utm]['data'] = [];
                    $qa_total[$question_id][$utm]['total'] = 0;
                }

                $question_with_calc = $population_list[$utm]['vote_fixed'] * $total;

                $qa_total[$question_id][$utm]['data'][$answer] = $question_with_calc;
                $qa_total[$question_id][$utm]['total'] += $question_with_calc;
            }
        }

        // Question and Answers
        $question_answers_result = Question::question_answers_by_survey($survey_id);
        $result = [];
        foreach($question_answers_result as $row) {
            $result[$row->question_id]['question'] = $row->question;
            $type = $row->type;
            $point = $row->point;
            $answer_id = $row->answer_id;

            if ($type == 'rating') {
                $result[$row->question_id]['answer'] = [1,2,3,4,5];
            } else if ($type == 'mark') {
                $temp1 = [];
                for ($r=0; $r < ($point+1); $r++) {
                    array_push($temp1, $r);
                }
                $result[$row->question_id]['answer'] = $temp1;
            } else {
                if(!array_key_exists('answer', $result[$row->question_id])){
                    $result[$row->question_id]['answer'] = [];
                }

                $result[$row->question_id]['answer'][$answer_id] = $row->content;
            }
            $q_id = isset($qa_total[$row->question_id])?$qa_total[$row->question_id]:'';
            $result[$row->question_id]['ans'] = $q_id;

            $result[$row->question_id]['total_count'] = 0;
            if(!isset($qa_total[$row->question_id]))
                continue;
            foreach($qa_total[$row->question_id] as $data) {
                $result[$row->question_id]['total_count'] += $data['total'];
            }
        }

        return response()->json([
            'message'   =>  'Return data for Weighting adjustment tab on the live result page.',
            'result'    =>  $result,
            'code'      =>  200
        ]);

    }

    public function get_graph_by_survey(Request $request, $survey_id) {
        $params = $request->all();

        $where_conditions = get_where_conditions($params);

        $trend = $where_conditions['trend'];
        $trust = $where_conditions['trust'];
        $population = $where_conditions['population'];

        // Filter
        $random_session_id = $this->get_random_session_ids_by_filter($survey_id, $params);

        if(strlen($random_session_id) > 0)
            $trend = $random_session_id;

        session(['trend' => $trend, 'trust' => $trust, 'population' => $population]);
        $q_total = Result::get_question_total($survey_id, $trend, $trust, $population);


        $qa_total_result = Result::get_qa_total($survey_id, $trend, $trust, $population);

        $qa_total = [];
        foreach($qa_total_result as $row) {
            $question_id = $row->question_id;
            $answer = $row->answer;
            $utm = $row->utm;
            $total = $row->total;
            $group_name = $row->group_name;

            if($utm == 'general' || $answer == "" || $answer == null)
                continue;

            if($total != null && $utm != null ) {
                $qa_total[$question_id][$utm]['utm'] = $utm;
                $qa_total[$question_id][$utm]['group'] = $group_name;

                if(!array_key_exists('data', $qa_total[$question_id][$utm])) {
                    $qa_total[$question_id][$utm]['data'] = [];
                    $qa_total[$question_id][$utm]['total'] = 0;
                }

                $qa_total[$question_id][$utm]['data'][$answer] = $total;
                $qa_total[$question_id][$utm]['total'] += $total;
            }
        }

        $question_answers_result = Question::question_answers_by_survey($survey_id);
        $result = [];
        foreach($question_answers_result as $row) {
            $result[$row->question_id]['question'] = $row->question;
            $type = $row->type;
            $point = $row->point;
            $answer_id = $row->answer_id;

            if ($type == 'rating') {
                $result[$row->question_id]['answer'] = [1,2,3,4,5];
            } else if ($type == 'mark') {
                $temp1 = [];
                for ($r=0; $r < ($point+1); $r++) {
                    array_push($temp1, $r);
                }
                $result[$row->question_id]['answer'] = $temp1;
            } else {
                if(!array_key_exists('answer', $result[$row->question_id])){
                    $result[$row->question_id]['answer'] = [];
                }

                $result[$row->question_id]['answer'][$answer_id] = $row->content;
            }
            $q_id = isset($qa_total[$row->question_id])?$qa_total[$row->question_id]:'';
            $result[$row->question_id]['ans'] = $q_id;

            $result[$row->question_id]['total_count'] = 0;
            if(!isset($qa_total[$row->question_id]))
                continue;
            foreach($qa_total[$row->question_id] as $data) {
                $result[$row->question_id]['total_count'] += $data['total'];
            }
        }

        return response()->json([
            'message'   =>  'Return data for graph tab on the live result page.',
            'result'    =>  $result,
            'code'      =>  200
        ]);
    }

    public function get_table_by_survey(Request $request, $survey_id) {
        $params = $request->all();

        $where_conditions = get_where_conditions($params);

        $trend = $where_conditions['trend'];
        $trust = $where_conditions['trust'];
        $population = $where_conditions['population'];

        // Filter
        $random_session_id = $this->get_random_session_ids_by_filter($survey_id, $params);

        if(strlen($random_session_id) > 0)
            $trend = $random_session_id;

        session(['trend' => $trend, 'trust' => $trust, 'population' => $population]);

        $q_total = Result::get_question_total($survey_id, $trend, $trust, $population);
        $qa_total_result = Result::get_qa_total($survey_id, $trend, $trust, $population);

        $qa_total = [];
        foreach($qa_total_result as $row) {
            $question_id = $row->question_id;
            $answer = $row->answer;
            $utm = $row->utm;
            if($utm == 'general' || $answer == "" || $answer == null)
                continue;

            $total = $row->total;
            $group_name = $row->group_name;

            if($total != null && $utm != null ) {
                $percent = ($total>0)?round($total / $q_total[$question_id][$utm] * 100, 2):'0';

                $qa_total[$question_id][$utm]['utm'] = $utm;
                $qa_total[$question_id][$utm]['group'] = $group_name;

                if(!array_key_exists('data', $qa_total[$question_id][$utm])) {
                    $qa_total[$question_id][$utm]['data'] = [];
                }

                $qa_total[$question_id][$utm]['data'][$answer] = $percent;
            }
        }

        $question_answers_result = Question::question_answers_by_survey($survey_id);

        $result = [];
        foreach($question_answers_result as $row) {
            $result[$row->question_id]['question'] = $row->question;
            $type = $row->type;
            $point = $row->point;
            $answer_id = $row->answer_id;

            if ($type == 'rating') {
                $result[$row->question_id]['answer'] = [1,2,3,4,5];
            } else if ($type == 'mark') {
                $temp1 = [];
                for ($r=0; $r < ($point+1); $r++) {
                    array_push($temp1, $r);
                }
                $result[$row->question_id]['answer'] = $temp1;
            } else {
                if(!array_key_exists('answer', $result[$row->question_id])){
                    $result[$row->question_id]['answer'] = [];
                }

                $result[$row->question_id]['answer'][$answer_id] = $row->content;
            }
            $q_id = isset($qa_total[$row->question_id])?$qa_total[$row->question_id]:'';
            $result[$row->question_id]['ans'] = $q_id;
        }

        return response()->json([
            'message'   =>  'Return data for table tab on the live result page.',
            'result'    =>  $result,
            'code'      =>  200
        ]);
    }

    public function get_text_by_survey(Request $request, $survey_id) {
        $params = $request->all();

        $where_conditions = get_where_conditions($params);

        $trend = $where_conditions['trend'];
        $trust = $where_conditions['trust'];
        $population = $where_conditions['population'];

        // Filter
        $random_session_id = $this->get_random_session_ids_by_filter($survey_id, $params);

        if(strlen($random_session_id) > 0)
            $trend = $random_session_id;

        session(['trend' => $trend, 'trust' => $trust, 'population' => $population]);

        // Get Population
        $sql = "SELECT results.random_session_id, p.group_name, results.referer, MIN(results.created_at) as start_at, MAX(results.updated_at) as end_at,  TIMEDIFF(MAX(results.updated_at), MIN(results.created_at)) as answer_time
                FROM results
                LEFT JOIN questions q ON q.id = results.question_id
                INNER JOIN populations p ON p.id = results.population_id
                WHERE results.survey_id = ".$survey_id." ".$trend." ".$population." ".$trust;

        $sql .= " GROUP BY results.random_session_id
                            ORDER BY results.id ASC";

        $live_result_table =  DB::select($sql);
        $return = [];

        foreach($live_result_table as $row) {
            $each_data = new stdClass;
            $each_data->random_session_id = $row->random_session_id;
            $each_data->group_name = $row->group_name;
            $each_data->customID = '';
            $each_data->referer = $row->referer;
            $each_data->start_date = $row->start_at?$row->start_at:'';
            $each_data->submit_date = $row->end_at?$row->end_at:'';
            $each_data->time = $row->answer_time?$row->answer_time:'00:00:00';
            array_push($return, $each_data);
        }

        return response()->json([
            'message'   =>  'Return data for text tab on the live result page.',
            'result'    =>  $return,
            'code'      =>  200
        ]);
    }

    public function get_answers_of_detail(Request $request, $survey_id) {
        $params = $request->all();
        $random_session_id = isset($params['random_session_id'])?$params['random_session_id']:'';

        $sql = "SELECT *, q.type, q.point, q.id as question_id
                    FROM results ua
                    LEFT JOIN  answers a ON a.id =  ua.answer
                    LEFT JOIN questions q ON q.id = ua.question_id
                    WHERE ua.survey_id = ".$survey_id."
                    AND ua.random_session_id='".$random_session_id."'";

        $result =  DB::select($sql);

        $return = [];
        foreach($result as $row) {

            $type = $row->type;
            $question_id = $row->question_id;
            if (!isset($return[$question_id]))
                $return[$question_id] = '';

            if ($type != 'one' && $type != 'multi' && $type != 'yn') {
                if ($type == 'rating') {
                    $return[$question_id] = $row->answer.'/5';
                } else if ($type == 'mark') {
                    $return[$question_id] = $row->answer.'/'.$row->point;
                } else {
                    $return[$question_id] = $row->answer;
                }
            } else if ($type == 'one' || $type == 'yn') {
                if (empty($row->content)) {
                    $return[$question_id] = '';
                } else {
                    $return[$question_id] = $row->content;
                }
            }else {
                $return[$question_id] .= $row->content.', ';
            }
        }
        return response()->json([
            'message'   =>  'Return the question list for the population of the survey.',
            'result'    =>  $return,
            'code'      =>  200
        ]);
    }

    public function delete_results($random_session_id) {
        $message = '';
        $code = 200;
        $status = 'error';

        try {
            DB::beginTransaction();
            $result_info = Result::where('random_session_id', $random_session_id)->first();
            $survey_id = $result_info->survey_id;
            $survey = Survey::find($survey_id);
            $survey->decrement('views');

            $result = Result::where('random_session_id', $random_session_id)->delete();
            DB::commit();
            $message   =  'Return the question list for the population of the survey.';
            $result    =  $result;
            $code      =  200;
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

    public function random_cut_results(Request $request, $survey_id) {
        $message = '';
        $code = 200;
        $status = 'error';

        try {
            DB::beginTransaction();
            $params = $request->all();

            $where_conditions = get_where_conditions($params);
            $trend = $where_conditions['trend'];
            $trust = $where_conditions['trust'];
            $population = $where_conditions['population'];
            $count = isset($params['count'])?$params['count']:0;

            // Filter
            $random_session_id = $this->get_random_session_ids_by_filter($survey_id, $params);
            if(strlen($random_session_id) > 0)
                $trend = $random_session_id;

            $sql = 'DELETE FROM results WHERE survey_id='.$survey_id." ".$trend." ".$population." ".$trust;
            $result = DB::delete($sql);

            $survey = Survey::find($survey_id);
            $survey->decrement('views', $count);
            DB::commit();

            $message   =  'Survey results has been removed on random.';
            $result    =  $result;
            $code      =  200;
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
