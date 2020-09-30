<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Survey;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Result;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\File;
use \stdClass;

class ExportController extends Controller
{
    private function survey_data($survey_id, $trend, $trust, $population) {
        $output = '';
        /* Survey Data */
        $sql = "SELECT
                        q.type, q.id as question_id, q.point, q.question, a.content, results.answer, results.id as result_id, results.random_session_id, results.created_at as start_time, results.updated_at as end_time, a.id as answer_id
                FROM questions q
                LEFT JOIN results ON results.question_id = q.id
                LEFT JOIN answers a ON a.id = results.answer
                WHERE q.survey_id=".$survey_id." ".$population." ".$trust." ".$trend." ORDER BY results.random_session_id, q.id ASC";

        $questions_graph =  DB::select($sql);
        $results = [];
        $question_list = [];
        $final_answer=null;
        $current_randome_session_id = null;

        for ($i=0; $i < count($questions_graph); $i++) {
            $question_id = $questions_graph[$i]->question_id;
            $question = $questions_graph[$i]->question;
            $answer = $questions_graph[$i]->answer;
            $content = $questions_graph[$i]->content;
            $type = $questions_graph[$i]->type;
            $point = $questions_graph[$i]->point;
            $random_session_id = $questions_graph[$i]->random_session_id;
            $start_time = $questions_graph[$i]->start_time;
            $end_time = $questions_graph[$i]->end_time;

            if ($type != 'one' && $type != 'multi' && $type != 'yn') {
                if ($type == 'rating') {
                    $final_answer = $answer.'/5';
                } else if ($type == 'mark') {
                    $final_answer = $answer.'/'.$point;
                } else {
                    $final_answer = $answer;
                }
            } else if ($type == 'one' || $type == 'yn') {
                $final_answer = $content;
            } else {
                if($current_randome_session_id == $random_session_id && $current_question_id == $question_id && $type == 'multi')
                    $final_answer = $final_answer.", ".$content;
                else {
                    $final_answer = $content;
                }
                $current_randome_session_id = $random_session_id;
                $current_question_id = $question_id;
            }

            $row = ['answer' => $final_answer, 'question' => $question, 'type' => $type, 'random_session_id' => $random_session_id, 'start_time' => $start_time, 'end_time' => $end_time];
            if(array_key_exists($question_id, $results)) {
                array_push($results[$question_id], $row);
            } else {
                $results[$question_id] = [$row];
            }
        }

        $rows = [];
        $first_question = null;
        foreach($results as $q) {
            $first_question = $q;
            break;
        }

        for($i = 0; $i< count($first_question); $i++) {
            $tr = [];
            foreach($results as $question) {
                array_push($tr, $question[$i]['answer']);
            }
            array_push($tr, $question[$i]['start_time']);
            array_push($tr, $question[$i]['end_time']);
            array_push($tr, $question[$i]['random_session_id']);

            array_push($rows, $tr);
        }

        $output = '<table>';
        $no = 1;
        $th = '';

        foreach($results as $key => $arr) {
            $question = ($results[$key]&&count($results[$key])>0)?$results[$key][0]['question']:'';

            $th .= "<th>Question".$no."-".$question."</th>";
            $no++;
        }

        $output .= '<tr><th>#</th>'.$th."<th>Start Date(UTC)</th><th>End Date(UTC)</th><th>Network ID</th></tr>";

        for($i=0; $i<count($rows);$i++) {
            $row = $rows[$i];
            $output .= '<tr><td>'.($i+1).'</td>';
            $index=0;
            for($j=0; $j<count($row);$j++) {
                $output .= '<td>'.$row[$j].'</td>';
                $index++;
            }
            $output .= '</tr>';
        }
        $output .= '</table>';

        return $output;
    }

    private function survey_results($survey_id, $trend, $trust, $population) {
        // Survey Result
        $questions_by_order = Question::where('survey_id', $survey_id)->orderBy('order')->get();
        $all_people_sql = "SELECT random_session_id FROM results WHERE survey_id=".$survey_id." ".$trend." ".$trust." ".$population;
        $all_people_result = DB::select($all_people_sql);
        $count_all_people = count($all_people_result);
        $all_data = array();
        foreach ($questions_by_order as $q_graph) {
            if ($q_graph->type != 'custom-text' && $q_graph->type != 'custom-number' && $q_graph->type != 'custom-date' && $q_graph->type != 'custom-free-text' && $q_graph->type != 'question-with-video') {
                $each_data = new stdClass;
                $each_data->question = $q_graph->question;
                $each_data->ans = array();

                if ($q_graph->type == 'rating') {
                    for ($r=1; $r < 6; $r++) {
                        $mini_data = new stdClass;
                        $total = DB::select("SELECT random_session_id FROM results WHERE survey_id='".$survey_id."' AND question_id='".$q_graph->question_id."' AND answer='".$r."' ".$trend." ".$trust." ".$population);
                        $total = count($total);


                        $mini_data->title = $r;
                        $mini_data->count = $total;
                        $mini_data->data = ($total > 0) ? round($total / $count_all_people * 100, 2) : 0;
                        array_push($each_data->ans, $mini_data);
                    }
                } else if ($q_graph->type == 'mark') {
                    for ($r=0; $r < ($q_graph->point+1); $r++) {
                        $mini_data = new stdClass;
                        $total = DB::select("SELECT random_session_id FROM results WHERE survey_id='".$survey_id."' AND question_id='".$q_graph->question_id."' AND answer='".$r."' ".$trust." ".$trend." ".$population);
                        $total = count($total);

                        $mini_data->title = $r;
                        $mini_data->count = $total;
                        $mini_data->data = ($total > 0) ? round($total / $count_all_people * 100, 2) : 0;
                        array_push($each_data->ans, $mini_data);
                    }
                } else {
                    $all_answer = DB::select("SELECT id as answer_id, content FROM answers WHERE question_id='".$q_graph->question_id."'");
                    foreach ($all_answer as $q_ans) {
                        $mini_data = new stdClass;
                        $total = DB::select("SELECT random_session_id FROM results WHERE survey_id='".$survey_id."' AND question_id='".$q_graph->question_id."' AND answer='".$q_ans->answer_id."' ".$trust." ".$trend." ".$population);
                        $total = count($total);

                        $mini_data->title = $q_ans->content;
                        $mini_data->count = $total;
                        $mini_data->data = ($total > 0) ? round($total / $count_all_people * 100, 2) : 0;
                        array_push($each_data->ans, $mini_data);
                    }
                }
                array_push($all_data, $each_data);
            }
        }

        $output = '';
        $output = '<table>';
        foreach($all_data as $data) {
            $th = '<tr><td style="background-color: #FFFF00; border: 1px solid #000; font-weight:bold; font-size:17px; font-family: Sakkal Majalla;">'.$data->question.'</td><td></td><td></td></tr>';
            $tr = '<tr><td></td><td style="background-color:#43a1f7;border: 1px solid #000;font-weight:bold; font-size:17px; font-family: Sakkal Majalla;">العدد</td><td style="background-color:#43a1f7;border: 1px solid #000;font-weight:bold; font-size:17px; font-family: Sakkal Majalla;">النسبة</td></tr>';

            $output .= $th.$tr;
            $total_count = 0;
            $total_ratio = 0;
            foreach($data->ans as $answers) {
                $count = $answers->count;
                $title = $answers->title;
                $ratio = $answers->data;

                $output .= '<tr><td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;" >'.$title.'</td><td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$count.'</td><td style="border: 1px solid #000;font-size:17px; font-family: Sakkal Majalla;">'.$ratio.'%</td></tr>';
                $total_count += $count;
                $total_ratio += $ratio;
            }
            $output .= '<tr><td style="border: 1px solid #000; font-weight:bold; font-size:17px; font-family: Sakkal Majalla;">المجموع</td><td style="border: 1px solid #000; font-weight:bold; font-size:17px; font-family: Sakkal Majalla;">'.$total_count.'</td><td style="border: 1px solid #000;font-size:17px; font-weight:bold; font-family: Sakkal Majalla;">100%</td></tr>';
            $output .= '<tr><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td></tr>';
        }
        $output .= '</table>';
        return $output;
    }
    private function survey_table_weight($survey_id, $trend, $trust, $population) {
        $table = '';
        $weight = '';

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
        $output = '';
        foreach($result as $row) {
            $output .= '<table>';
                $output .= '<tr><td>'.$row['question'].'</td></tr>';
                $output .= '<tr>';
                    $output .= '<th style="background-color: #FFFF00; border: 1px solid #000; font-weight:bold; font-size:17px; font-family: Sakkal Majalla;">Set name</th>';
                    foreach($row['answer'] as $a_id => $answer) {
                        $output .= '<th style="background-color: #FFFF00; border: 1px solid #000; font-weight:bold; font-size:17px; font-family: Sakkal Majalla;">';
                        $output .= $answer;
                        $output .= '</th>';
                    }
                $output .= '</tr>';

                if(!is_null($row['ans'])){
                    foreach($row['ans'] as $utm) {
                        $output .= '<tr>';
                            $group_name = $utm['group']?$utm['group']:'None';
                            $output .='<td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$utm['group'].'</td>';
                            foreach($row['answer'] as $answer_id => $answer) {
                                if(array_key_exists($answer_id, $utm['data']))
                                    $percent = $utm['data'][$answer_id];
                                else
                                    $percent = 0;
                                $output  .= '<td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$percent.'%</td>';
                            }
                        $output .= '</tr>';

                    }
                }
            $output .= '</table>';
        }
        $table = $output;

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
        $weight = $output;
        $return = [];
        $return['table'] = $table;
        $return['weight'] = $weight;
        return $return;
    }

    public function get_survey_demographic($survey_id) {
        // Question & answers
        $sql = "SELECT q.id as question_id, q.question, a.id as answer_id, a.content, q.demographic
                FROM answers a
                LEFT JOIN questions q ON q.id = a.question_id
                WHERE q.survey_id = ".$survey_id;
        $qa_results = DB::select($sql);
        $question_answers = [];
        foreach($qa_results as $row) {
            $question_answers[$row->question_id][$row->answer_id] = [
                'question'  =>  $row->question,
                'answer'    =>  $row->content,
                'question_id'   =>  $row->question_id,
                'answer_id'     =>  $row->answer_id
            ];

            $question_answers[$row->question_id]['question'] = $row->question;
            $question_answers[$row->question_id]['type'] = $row->demographic;
        }

        // Get Answered users
        $sql = "SELECT ua.question_id, ua.answer, COUNT(ua.answer) as count, q.question
                    FROM results ua
                    LEFT JOIN questions q ON q.id = ua.question_id
                    WHERE ua.survey_id=".$survey_id."
                    GROUP BY ua.question_id, ua.answer";
        $ua_results = DB::select($sql);
        foreach($ua_results as $row) {
            $question_answers[$row->question_id][$row->answer]['count'] = $row->count;
        }
        // Get Demography questions
        $demographic_question_sql = "SELECT DISTINCT ua.question_id, ua.id as answer_id, ua.content
                                        FROM answers ua
                                        LEFT JOIN questions q ON q.id = ua.question_id
                                        WHERE q.survey_id = ".$survey_id." AND q.demographic > 0
                                        ORDER BY ua.id";
        $demo_question_results = DB::select($demographic_question_sql);
        $demo_question_list = [];

        foreach($demo_question_results as $row) {
            $demo_question_list[$row->question_id]['title'] = $row->content;
            $demo_question_list[$row->question_id]['answers'][$row->answer_id] = $row->answer_id;
        }
        $demography_result = [];

        foreach($demo_question_list as $question_id => $row) {
            foreach($row['answers'] as $answer_id) {
                $sql = "SELECT question_id, answer, COUNT(answer) as count1
                            FROM results
                            WHERE random_session_id IN (
                                                        SELECT random_session_id
                                                                    FROM results ua
                                                                    LEFT JOIN questions q ON q.id = ua.question_id
                                                                    WHERE ua.survey_id= ".$survey_id."
                                                                    AND ua.answer = ".$answer_id."
                                                        )
                            GROUP BY question_id, answer";

                $result = DB::select($sql);

                foreach($result as $qa) {
                    $demography_result[$question_id][$answer_id][$qa->question_id][$qa->answer] = $qa->count1;
                    if(isset($demography_result[$question_id][$answer_id][$qa->question_id]['total']))
                        $demography_result[$question_id][$answer_id][$qa->question_id]['total'] += $qa->count1;
                    else
                        $demography_result[$question_id][$answer_id][$qa->question_id]['total'] = $qa->count1;
                }
            }

        }
        $spreadsheet = null;
        $sheet_index = 0;
        $output = '';
        $reader = new Html();
        foreach($demo_question_list as $demo_question_id => $row) {
            $output = '<table>';

            foreach($question_answers as $question_id => $answers) {
                $question = $question_answers[$question_id]['question'];
                $output .= '<tr><td style="background-color: #FFFF00; border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$question.'</td><td></td>';
                foreach($row['answers'] as $demo_answer_id) {
                    $output .= '<td colspan="2" style="background-color:#70AD47; border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$question_answers[$demo_question_id][$demo_answer_id]['answer'].'</td><td></td>';
                }
                $output .= '</tr>';

                $output .= '<tr><td ></td><td></td>';
                foreach($row['answers'] as $demo_answer_id) {
                    $output .= '<td  style="background-color: #43a1f7;border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">العدد</td><td style="background-color: #43a1f7;border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">النسبة</td><td></td>';
                }
                $output .= '</tr>';
                if(is_array($answers)) {
                    $temp = [];
                    $tr = '';
                    foreach($answers as $data) {
                        $answer = isset($data['answer'])?$data['answer']:'';
                        if($answer == '')
                            continue;
                        $question_id = isset($data['question_id'])?$data['question_id']:'';
                        $answer_id = isset($data['answer_id'])?$data['answer_id']:'';

                        $tr .= '<tr>';
                        $tr .= '<td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$answer.'</td><td></td>';

                        foreach($row['answers'] as $demo_answer_id) {
                            if(isset($demography_result[$demo_question_id][$demo_answer_id][$question_id][$answer_id])) {
                                $tr .= '<td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$demography_result[$demo_question_id][$demo_answer_id][$question_id][$answer_id].'</td>';
                                $tr .= '<td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.' '.number_format ((($demography_result[$demo_question_id][$demo_answer_id][$question_id][$answer_id] / $demography_result[$demo_question_id][$demo_answer_id][$question_id]['total']) * 100),2).'%</td><td></td>';

                                if(isset($temp[$demo_question_id][$demo_answer_id]['total']))
                                    $temp[$demo_question_id][$demo_answer_id]['total'] += $demography_result[$demo_question_id][$demo_answer_id][$question_id][$answer_id];
                                else
                                    $temp[$demo_question_id][$demo_answer_id]['total'] = $demography_result[$demo_question_id][$demo_answer_id][$question_id][$answer_id];

                            } else
                                $tr .= '<td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">0</td><td style="border: 1px solid #000;font-size:17px; font-family: Sakkal Majalla;">0</td><td></td>';
                        }
                        $tr .= '</tr>';
                    }

                    $tr .= '<tr><td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;" >المجموع</td><td></td>';
                    foreach($row['answers'] as $demo_answer_id) {
                        $tr .= '<td style="border: 1px solid #000; font-size:17px; font-family: Sakkal Majalla;">'.$temp[$demo_question_id][$demo_answer_id]['total'].'</td><td style="border: 1px solid #000;font-size:17px; font-family: Sakkal Majalla;">100%</td><td></td>';
                    }
                    $tr .= '</tr>';

                    $tr .= '<tr><td></td></tr>';

                    $output .= $tr;
                }
            }

            $output .= '</table>';

            if($sheet_index > 0) {
                $reader->setSheetIndex($sheet_index);
                $spreadsheet = $reader->loadFromString($output, $spreadsheet);
            } else
                $spreadsheet = $reader->loadFromString($output);

            $sheet_name = get_demographic_name($question_answers[$demo_question_id]['type']);

            $spreadsheet->getActiveSheet()->setTitle($sheet_name);
            $spreadsheet->getActiveSheet()->setRightToLeft(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
            $sheet_index++;
        }

        if(!is_null($spreadsheet)) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xls');

            if(!Storage::disk('local')->exists('public/excels'))
            {
                $permissions = intval( config('permissions.directory'), 8 );
                Storage::disk('local')->makeDirectory('public/excels', $permissions, true);
            }

            // Create the excel file
            $filename = 'demographic-'.date('m-d-Y_hia').'.xls';
            $excel_path = 'storage/excels/'.$filename;
            $writer->save($excel_path);
            return Storage::disk('public')->download('/excels/'.$filename);
        } else {
            return response()->json([
                'message'   =>  'There is no demographic data for this survey',
                'result'    =>  null,
                'code'      =>  200
            ]);
        }
    }

    public function get_survey_weight($survey_id) {
        $trend = '';
        $trust = '';
        $population = '';

        $table_weight = $this->survey_table_weight($survey_id, $trend, $trust, $population);
        $output = $table_weight['weight'];
        $reader = new Html();
        $spreadsheet = $reader->loadFromString($output);
        $spreadsheet->getActiveSheet()->setTitle('Weight Adjustment');

        // Prepare the excel file
        $reader->setSheetIndex(0);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Sakkal Majalla');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(22);

        $writer = IOFactory::createWriter($spreadsheet, 'Xls');

        if(!Storage::disk('local')->exists('public/excels'))
        {
            $permissions = intval( config('permissions.directory'), 8 );
            Storage::disk('local')->makeDirectory('public/excels', $permissions, true);
        }

        // Create the excel file
        $filename = 'survey-weight-'.date('m-d-Y_hia').'.xls';
        $excel_path = 'storage/excels/'.$filename;
        $writer->save($excel_path);
		return Storage::disk('public')->download('/excels/'.$filename);
    }

    public function get_survey_table($survey_id) {
        $trend = '';
        $trust = '';
        $population = '';

        $table_weight = $this->survey_table_weight($survey_id, $trend, $trust, $population);
        $reader = new Html();
        $output = $table_weight['table'];
        $spreadsheet = $reader->loadFromString($output);
        $spreadsheet->getActiveSheet()->setTitle('Survey Table');

        // Prepare the excel file
        $reader->setSheetIndex(0);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Sakkal Majalla');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(22);

        $writer = IOFactory::createWriter($spreadsheet, 'Xls');

        if(!Storage::disk('local')->exists('public/excels'))
        {
            $permissions = intval( config('permissions.directory'), 8 );
            Storage::disk('local')->makeDirectory('public/excels', $permissions, true);
        }

        // Create the excel file
        $filename = 'survey-table-'.date('m-d-Y_hia').'.xls';
        $excel_path = 'storage/excels/'.$filename;
        $writer->save($excel_path);
		return Storage::disk('public')->download('/excels/'.$filename);
    }

    public function get_survey_result($survey_id) {
        $trend = '';
        $trust = '';
        $population = '';

        $output = $this->survey_results($survey_id, $trend, $trust, $population);
        $reader = new Html();
        $spreadsheet = $reader->loadFromString($output);
        $spreadsheet->getActiveSheet()->setTitle('Survey Results');

        // Prepare the excel file
        $reader->setSheetIndex(0);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Sakkal Majalla');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(22);

        $writer = IOFactory::createWriter($spreadsheet, 'Xls');

        if(!Storage::disk('local')->exists('public/excels'))
        {
            $permissions = intval( config('permissions.directory'), 8 );
            Storage::disk('local')->makeDirectory('public/excels', $permissions, true);
        }

        // Create the excel file
        $filename = 'survey-results-'.date('m-d-Y_hia').'.xls';
        $excel_path = 'storage/excels/'.$filename;
        $writer->save($excel_path);
		return Storage::disk('public')->download('/excels/'.$filename);
    }

    public function get_survey_data($survey_id) {
        /*************************************************************************/
        $trend = '';
        $trust = '';
        $population = '';

        $output = $this->survey_data($survey_id, $trend, $trust, $population);
        $reader = new Html();
        $spreadsheet = $reader->loadFromString($output);
        $spreadsheet->getActiveSheet()->setTitle('Survey Data');

        // Prepare the excel file
        $reader->setSheetIndex(0);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Sakkal Majalla');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(22);

        $writer = IOFactory::createWriter($spreadsheet, 'Xls');

        if(!Storage::disk('local')->exists('public/excels'))
        {
            $permissions = intval( config('permissions.directory'), 8 );
            Storage::disk('local')->makeDirectory('public/excels', $permissions, true);
        }

        // Create the excel file
        $filename = 'survey-data-'.date('m-d-Y_hia').'.xls';
        $excel_path = 'storage/excels/'.$filename;
        $writer->save($excel_path);
		return Storage::disk('public')->download('/excels/'.$filename);
    }

    public function to_excel($survey_id) {
        $trend = session('trend');
        $trust = session('trust');
        $population = session('population');

        /*************************************************************************/
        $output = $this->survey_data($survey_id, $trend, $trust, $population);
        $reader = new Html();
        $spreadsheet = $reader->loadFromString($output);
        $spreadsheet->getActiveSheet()->setTitle('Survey Data');
        /*************************************************************************/
        $output = $this->survey_results($survey_id, $trend, $trust, $population);
        $reader->setSheetIndex(1);
        $spreadhseet = $reader->loadFromString($output, $spreadsheet);
        $spreadsheet->getActiveSheet()->setTitle('Survey Results');
        $spreadsheet->getActiveSheet()->setRightToLeft(true);
        /************************************************************************* */
        $table_weight = $this->survey_table_weight($survey_id, $trend, $trust, $population);
        $output = $table_weight['table'];
        if(strlen($output) > 0) {
            $reader->setSheetIndex(2);
            $spreadhseet = $reader->loadFromString($output, $spreadsheet);
            $spreadsheet->getActiveSheet()->setTitle('Survey Table');
            $spreadsheet->getActiveSheet()->setRightToLeft(true);
        }
        /************************************************************************* */
        $output = $table_weight['weight'];
        if(strlen($output) > 0) {
            $reader->setSheetIndex(3);
            $spreadhseet = $reader->loadFromString($output, $spreadsheet);
            $spreadsheet->getActiveSheet()->setTitle('Weighting adjustment');
            $spreadsheet->getActiveSheet()->setRightToLeft(true);
        }

        // Prepare the excel file
        $reader->setSheetIndex(0);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Sakkal Majalla');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(22);

        $writer = IOFactory::createWriter($spreadsheet, 'Xls');

        if(!Storage::disk('local')->exists('public/excels'))
        {
            $permissions = intval( config('permissions.directory'), 8 );
            Storage::disk('local')->makeDirectory('public/excels', $permissions, true);
        }

        // Create the excel file
        $filename = 'results-'.date('m-d-Y_hia').'.xls';
        $excel_path = 'storage/excels/'.$filename;
        $writer->save($excel_path);
		return Storage::disk('public')->download('/excels/'.$filename);
    }
}
