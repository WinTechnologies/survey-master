<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DropOff;
use App\Models\Survey;

class DropOffController extends Controller
{
    public function init(Request $request, $survey_id) {
        $message = '';
        $code = 200;
        $status = 'error';

        try {
            $params = $request->json()->all();
            $basic_info = $params['basic'];

            // Begin Transaction
            DB::beginTransaction();
            $survey = Survey::find($survey_id);
            $questions = $survey->questions();

            $drop_offs = [];
            foreach($questions as $row) {
                $temp = [
                    'survey_id'     =>  $row->survey_id,
                    'question_id'   =>  $row->id,
                    'answered'      =>  'visit',
                    'device'        =>  $basic_info['device'],
                    'random_session_id' => $basic_info['random_session_id']
                ];

                array_push($drop_offs, $temp);
            }
            DB::table('drop_offs')->insert($drop_offs);
            // Commit
            DB::commit();
            $message = 'Drop-Off has been initialized for this survey';
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

    public function answer_started(Request $request, $survey_id) {
        $message = '';
        $code = 200;
        $status = 'error';

        try {
            $params = $request->json()->all();
            $random_session_id = $params['random_session_id'];
            // Begin Transaction
            DB::beginTransaction();
            DB::table('drop_offs')->where('survey_id', $survey_id)->where('random_session_id', $random_session_id)->update(['answer_status' => 'started']);
            // Commit
            DB::commit();
            $message = 'The drop-off status has been udpated to STARTED successfully.';
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

    public function update(Request $request, $survey_id) {
        $message = '';
        $code = 200;
        $status = 'error';

        try {
            $params = $request->json()->all();

            // Begin Transaction
            DB::beginTransaction();
            $random_session_id = $params['random_session_id'];
            $question_id = $params['question_id'];

            DB::table('drop_offs')->where('survey_id', $survey_id)
                                    -> where('question_id', $question_id)
                                    ->where('random_session_id', $random_session_id)->update(['answer_status' => 'answered']);
            // Commit
            DB::commit();
            $message = 'The answer status has been udpated to ANSWERED successfully.';
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
