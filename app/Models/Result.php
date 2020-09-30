<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Result extends Model
{
    protected $fillable = [
        'survey_id',
        'question_id',
        'answer',
        'population_id',
        'session_id',
        'random_session_id',
        'trust',
        'referer',
        'utm_params',
        'created_at',
        'update_at'
    ];

    public static  function get_question_total($survey_id, $trend, $trust, $population) {
        // get by group, question
        $sql = "SELECT q.id as question_id, results.answer,  COUNT(p.id) as total, p.utm
                FROM results
                LEFT JOIN questions q ON q.id = results.question_id
                LEFT JOIN populations p ON p.id = results.population_id
                WHERE results.survey_id = ".$survey_id." ".$trust." ".$population." ".$trend."
                AND (q.type != 'custom-text' AND q.type != 'custom-number' AND q.type != 'custom-date' AND q.type != 'custom-free-text' AND q.type != 'question-with-video')";

        $group_q_group_by = "
                GROUP BY results.question_id, p.id
                HAVING total > 0
                ORDER BY q.id, results.answer";

        $sql .= $group_q_group_by;
        $group_q_result = DB::select($sql);
        $q_total = [];
        foreach($group_q_result as $row) {
            $question_id = $row->question_id;
            $answer = $row->answer;
            $utm = $row->utm;
            $total = $row->total;
            if($total != null && $utm != null  && $utm != 'general') {
                $q_total[$question_id][$utm] =$total;
            }
        }
        return $q_total;
    }

    public static  function get_qa_total($survey_id, $trend, $trust, $population) {
        $group_qa_sql = "SELECT q.id as question_id, results.answer,  COUNT(results.population_id) as total, p.id as population_id, p.parent_set, p.group_name, p.utm as utm
                        FROM results
                        LEFT JOIN questions q ON q.id = results.question_id
                        LEFT JOIN populations p ON p.id = results.population_id
                        WHERE results.survey_id = ".$survey_id." ".$trend." ".$trust." ".$population."
                          AND (q.type != 'custom-text' AND q.type != 'custom-number' AND q.type != 'custom-date' AND q.type != 'custom-free-text' AND q.type != 'question-with-video')";

        $group_qa_group_by =   " GROUP BY results.question_id, results.answer, results.population_id
                        HAVING group_name != ''
                        ORDER BY q.id, results.answer";


        $group_qa_sql .= $group_qa_group_by;
        $group_qa_result = DB::select($group_qa_sql);
        return $group_qa_result;
    }

    public static function get_pariticipants_by_group($survey_id) {
        $sql = "SELECT
                    result.population_id, result.group_name, result.parent_set, result.size_set, result.utm, COUNT(result.group_name) AS participants
                FROM (SELECT ua.random_session_id, p.id as population_id, p.group_name, p.parent_set, p.size_set, p.utm
                FROM results ua
                LEFT JOIN questions q ON q.id = ua.question_id
                INNER JOIN populations p ON p.id = ua.population_id
                WHERE ua.survey_id = ".$survey_id."
                GROUP BY ua.random_session_id
                ORDER BY p.group_name ASC) result
                GROUP BY result.group_name";
        $result = DB::select($sql);

        $total_participates = 0;
        $group = [];
        foreach($result as $row) {
            $total_participates += $row->participants;
            $group[$row->utm] = $row->participants;
        }

        $group['total_participates'] = $total_participates;
        return $group;
    }

    public static function get_populations($survey_id) {
        $sql = "SELECT id, group_name, size_set, utm
                     FROM populations
                     WHERE
                     parent_set IN
                     (SELECT p.id
                        FROM populations p
                        LEFT JOIN surveys s ON s.population_id = p.id
                        WHERE s.id = ".$survey_id.") OR id IN (SELECT pp.id
                        FROM populations pp
                        LEFT JOIN surveys s ON s.population_id = pp.id
                        WHERE s.id = ".$survey_id.")
                        ORDER BY id";

        $result = DB::select($sql);
        return $result;
    }

    public static function get_random_session_ids($survey_id) {
        $trend_sql = "SELECT results.random_session_id
                        FROM results
                        INNER JOIN populations p on p.id = results.population_id
                        WHERE survey_id=".$survey_id."
                        GROUP BY random_session_id ORDER BY results.id ASC";

        $random_session_id_result = DB::select($trend_sql);
        $user_list = [];
        foreach($random_session_id_result as $row) {
            array_push($user_list, "'".$row->random_session_id."'");
        }
        return $user_list;
    }
}
