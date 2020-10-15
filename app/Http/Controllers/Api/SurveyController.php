<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Answer;
use Auth;

class SurveyController extends Controller
{
    public function all() {
        $user_id = Auth::user()->id;

        if($user_id == 1 ){
            $surveys = Survey::all();
        } else {
            $surveys = Survey::where('user_id', $user_id)->get();
        }

        $result = [];
        foreach($surveys as $s) {
            $result[] = [
                'id'            =>  $s->id,
                'title'         =>  $s->title,
                'population_id' =>  $s->population_id,
                'group_name'    =>  $s->population->group_name,
                'language'      =>  $s->language,
                'views'         =>  $s->views,
                'data_created'  =>  $s->created_at
            ];
        }
        return response()->json([
            'message'   =>  'Get all surveies',
            'result'    =>  $result
        ]);
    }

    public function template(Request $request, $id, $utm) {
        dd('template');
    }

    public function edit($id) {
        $survey = Survey::find($id);

        if(!$survey)
            return response()->json([
                'message'   =>  'The survey id is null.',
                'result'    =>  null,
                'next'      =>  false,
                'code'      =>  400,
            ]);

        $result = [];

        $result['survey'] = [
            'id'                =>  $id,
            'title'             =>  $survey->title,
            'intro'             =>  $survey->intro,
            'btn_start'         =>  $survey->btn_start,
            'btn_submit'        =>  $survey->btn_submit,
            'google_analytics'  =>  $survey->google_analytics,
            'facebook_pixel'    =>  $survey->facebook_pixel,
            'welcome_image'     =>  $survey->welcome_image,
            'population_id'     =>  $survey->population_id,
            'theme_id'          =>  $survey->theme_id,
            'language'          =>  $survey->language,
            'limit'             =>  $survey->limit,
            'views'             =>  $survey->views,
            'timer_min'         =>  $survey->timer_min,
            'timer_sec'         =>  $survey->timer_sec,
            'expired_at'        =>  $survey->expired_at,
            'auto_submit'       =>  $survey->auto_submit,
            'is_one_response'   =>  $survey->is_one_response,
            'redirect_url'      =>  $survey->redirect_url,
            'questions'         =>  [],
        ];

        foreach($survey->questions as $q) {
            $question = [
                'question_id'           =>  $q->id,
                'survey_id'             =>  $q->survey_id,
                'type'                  =>  $q->type,
                'question'              =>  $q->question,
                'image'                 =>  $q->image,
                'order'                 =>  $q->order,
                'is_reliability'        =>  $q->is_reliability,
                'is_required'           =>  $q->is_required,
                'is_main'               =>  $q->is_main,
                'is_random'             =>  $q->is_random,
                'demographic'           =>  $q->demographic,
                'answer_limit'          =>  $q->answer_limit,
                'jump_id'               =>  $q->jump_id,
                'shape'                 =>  $q->shape,
                'point'                 =>  $q->point,
                'video_src'             =>  $q->video_src,
                'jump_logic'            =>  $q->jump_logic,
                'answers'               =>  []
            ];

            $answers = $q->answers;

            foreach($answers as $a) {
                $question['answers'][] = $a;
            }

            $result['survey']['questions'][] = $question;
        }


        return response()->json([
            'message'   =>  'Get the survey by id',
            'result'    =>  $result,
            'code'      =>  200,
            'status'    =>  'success'
        ]);
    }

    public function create(Request $request) {
        $message = '';
        $code = 200;
        $status = 'error';

        try {
            $user_id = Auth::user()->id;
            $params = $request->json()->all();

            // Begin Transaction
            DB::beginTransaction();
            $input_survey = $params['survey'];

            // Upload Welcome Image
            $welcome_image = $input_survey['welcome_image'];

            // New Survey
            $new_survey = new Survey;
            $new_survey->title = $input_survey['title'];
            $new_survey->intro = $input_survey['intro'];
            $new_survey->btn_start = $input_survey['btn_start'];
            $new_survey->btn_submit = $input_survey['btn_submit'];
            $new_survey->google_analytics = isset($input_survey['google_analytics'])?$input_survey['google_analytics']:'';
            $new_survey->facebook_pixel = isset($input_survey['facebook_pixel'])?$input_survey['facebook_pixel']:'';
            $new_survey->welcome_image = $welcome_image;
            $new_survey->population_id = $input_survey['population_id'];
            $new_survey->theme_id = $input_survey['theme_id'];
            $new_survey->language = $input_survey['language'];
            $new_survey->limit = isset($input_survey['limit'])?$input_survey['limit']:0;
            $new_survey->timer_min = $input_survey['timer_min'];
            $new_survey->timer_sec = $input_survey['timer_sec'];
            $new_survey->expired_at = $input_survey['expired_at'];
            $new_survey->auto_submit = $input_survey['auto_submit'];
            $new_survey->is_one_response = isset($input_survey['is_one_response'])?1:0;
            $new_survey->redirect_url = $input_survey['redirect_url'];
            $new_survey->user_id = $user_id;
            $new_survey->save();
            $survey_id = $new_survey->id;

            // New Question
            $input_questions = $input_survey['questions'];

            $answer_list = [];
            foreach($input_questions as $question) {
                $type = $question['type'];
                $btn_text = '';
                $statement_btn_color = '';

                if($type == 'statement' || $type == 'thank-you') {
                    $btn_text = isset($question['btn_text'])?$question['btn_text']:'Continue';
                    $statement_btn_color = isset($question['statement_btn_color'])?$question['statement_btn_color']:'#404040';
                }

                $new_question_values = [
                    'survey_id'         =>  $survey_id,
                    'type'              =>  $type,
                    'question'          =>  $question['question'],
                    'image'             =>  $question['image'],
                    'order'             =>  $question['order'],
                    'is_reliability'    =>  $question['is_reliability'],
                    'is_required'       =>  $question['is_required'],
                    'is_main'           =>  $question['is_main'],
                    'is_random'         =>  $question['is_random'],
                    'demographic'       =>  $question['demographic'],
                    'answer_limit'      =>  $question['answer_limit'],
                    'jump_id'           =>  $question['jump_id'],
                    'btn_text'          =>  $btn_text,
                    'statement_btn_color'=> $statement_btn_color,
                    'shape'              =>  $question['shape'],
                    'point'              =>  $question['point'],
                    'video_src'          =>  $question['video_src']
                ];

                $new_question = Question::create($new_question_values);
                $question_id = $new_question->id;

                $input_answers = $question['answers'];

                foreach($input_answers as $answer) {
                    $answer_list[] = [
                        'question_id'       =>  $question_id,
                        'content'           =>  $answer['content'],
                        'image'             =>  $answer['image'],
                        'correct'           =>  $answer['correct'],
                        'jump_question_id'  =>  $answer['jump_question_id'],
                        'created_at'        =>  now()
                    ];
                }
            }

            if(isset($answer_list)) {
                Answer::insert($answer_list);
            }
            // Commit
            DB::commit();
            $message = 'New survey has been created successfully.';
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

    public function duplicate(Request $request, $id) {
        $message = '';
        $code = 200;
        $status = 'error';

        try
        {
            $survey = Survey::find($id);
            $result = [];
            // Begin Transaction
            DB::beginTransaction();

            $input_survey  = [
                'title'             =>  $survey->title,
                'intro'             =>  $survey->intro,
                'btn_start'         =>  $survey->btn_start,
                'btn_submit'        =>  $survey->btn_submit,
                'google_analytics'  =>  $survey->google_analytics,
                'facebook_pixel'    =>  $survey->facebook_pixel,
                'welcome_image'     =>  $survey->welcome_image,
                'population_id'     =>  $survey->population_id,
                'theme_id'          =>  $survey->theme_id,
                'language'          =>  $survey->language,
                'limit'             =>  $survey->limit,
                'views'             =>  0,
                'timer_min'         =>  $survey->timer_min,
                'timer_sec'         =>  $survey->timer_sec,
                'expired_at'        =>  $survey->expired_at,
                'auto_submit'       =>  $survey->auto_submit,
                'user_id'           =>  $user_id
            ];

            // New Survey
            $new_survey = new Survey;
            $new_survey->title = $input_survey['title'];
            $new_survey->intro = $input_survey['intro'];
            $new_survey->btn_start = $input_survey['btn_start'];
            $new_survey->btn_submit = $input_survey['btn_submit'];
            $new_survey->google_analytics = $input_survey['google_analytics'];
            $new_survey->facebook_pixel = $input_survey['facebook_pixel'];
            $new_survey->welcome_image = $input_survey['welcome_image'];
            $new_survey->population_id = $input_survey['population_id'];
            $new_survey->theme_id = $input_survey['theme_id'];
            $new_survey->language = $input_survey['language'];
            $new_survey->limit = $input_survey['limit'];
            $new_survey->timer_min = $input_survey['timer_min'];
            $new_survey->timer_sec = $input_survey['timer_sec'];
            $new_survey->expired_at = $input_survey['expired_at'];
            $new_survey->auto_submit = $input_survey['auto_submit'];
            $new_survey->user_id = $input_survey['user_id'];
            $new_survey->save();
            $survey_id = $new_survey->id;

            // New Question
            foreach($survey->questions as $q) {
                $question = [
                    'survey_id'             =>  $q->survey_id,
                    'type'                  =>  $q->type,
                    'question'              =>  $q->question,
                    'image'                 =>  $q->image,
                    'order'                 =>  $q->order,
                    'is_reliability'        =>  $q->is_reliability,
                    'is_required'           =>  $q->is_required,
                    'is_main'               =>  $q->is_main,
                    'is_random'             =>  $q->is_random,
                    'demographic'           =>  $q->demographic,
                    'answer_limit'          =>  $q->answer_limit,
                    'jump_id'               =>  $q->jump_id,
                    'jump_logic'            =>  $q->jump_logic,
                    'answers'               =>  null
                ];

                $answers = $q->answers;

                foreach($answers as $a) {
                    $question['answers'][] = $a;
                }

                $result['questions'][] = $question;
            }
            $input_questions = $result['questions'];

            $answer_list = [];
            foreach($input_questions as $question) {
                $image = $question['image']; // When duplicates, it does not need to upload the image as the base 64, just use the image url path
                $type = $question['type'];
                $btn_text = '';
                $statement_btn_color = '';

                if($type == 'statement' || $type == 'thank-you') {
                    $btn_text = isset($question['btn_text'])?$question['btn_text']:'Continue';
                    $statement_btn_color = isset($question['statement_btn_color'])?$question['statement_btn_color']:'#404040';
                }

                $new_question_values = [
                    'survey_id'         =>  $survey_id,
                    'type'              =>  $type,
                    'question'          =>  $question['question'],
                    'image'             =>  $image,
                    'order'             =>  $question['order'],
                    'is_reliability'    =>  $question['is_reliability'],
                    'is_required'       =>  $question['is_required'],
                    'is_main'           =>  $question['is_main'],
                    'is_random'         =>  $question['is_random'],
                    'demographic'       =>  $question['demographic'],
                    'answer_limit'      =>  $question['answer_limit'],
                    'jump_id'           =>  $question['jump_id'],
                    'btn_text'          =>  $btn_text,
                    'statement_btn_color'=> $statement_btn_color,
                ];

                $new_question = Question::create($new_question_values);
                $question_id = $new_question->id;

                $input_answers = $question['answers'];
                if($input_answers == null) {
                    continue;
                }

                foreach($input_answers as $answer) {
                    // Upload Answer Image
                    $answer_image = '';
                    if(isset($answer->image)) {
                        $answer_image = $answer->image;
                    }

                    $answer_list[] = [
                        'question_id'       =>  $question_id,
                        'content'           =>  $answer->content,
                        'image'             =>  $answer_image,
                        'correct'           =>  $answer->correct,
                        'jump_question_id'  =>  $answer->jump_question_id,
                        'created_at'        =>  now()
                    ];
                }
            }


            if(isset($answer_list)) {
                Answer::insert($answer_list);
            }
            // Commit
            DB::commit();
            $message = 'New survey has been duplicated successfully.';
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

    public function delete($id) {
        $survey = Survey::find($id);

        if ($survey != null)
        {
            $survey->delete();
            return response()->json([
                'status'        =>  'success',
                'message'       =>  'The survey has been deleted successfully',
                'code'          =>  200
            ]);
        } else {
            return response()->json([
                'status'        =>  'warning',
                'message'       =>  'The survey does not exist.',
                'code'          =>  404
            ]);
        }
    }

    public function doc_import(Request $request) {
        $message = '';
        $code = 400;
        $status = 'error';
        $survey = null;

        if($request->hasFile('doc')) {
            $doc = $request->file('doc');
            $filename = $doc->path();
            $content = read_doc($filename);
            $survey = get_survey_from_doc($content);
            $message = 'The survey has been parsed from the file you imported.';
            $status = 'success';
            $code = 200;
        }

        return response()->json([
            'status'        =>  $status,
            'message'       =>  $message,
            'code'          =>  $code,
            'survey'        =>  $survey
        ]);
    }

    public function upload(Request $request) {
        $params = $request->all();
        $base64 = $params['base64'];

        $image_url = '';
        if($base64) {
            $image_url = upload_image($base64);
        }

        return $image_url;
    }

    public function jumplogic(Request $request, $question_id) {
        $question = Question::find($question_id);

        if(!$question)
            return response()->json([
                'message'   =>  'The question id is null.',
                'result'    =>  null,
                'next'      =>  false,
                'code'      =>  400,
            ]);
        $params = $request->all();

        $jump_logic = $params['jumps'];

        $data = [
            'jump_logic'    => $jump_logic
        ];

        $result = Question::where('id', $question_id)->update($data);

        return response()->json([
            'message'   =>  'The jump logic is updated',
            'result'    =>  $result,
            'code'      =>  200,
            'status'    =>  'success'
        ]);
    }
}
