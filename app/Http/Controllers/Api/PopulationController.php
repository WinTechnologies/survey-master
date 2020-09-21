<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Population;

class PopulationController extends Controller
{
    public function index()
    {
        $populations_result = Population::where('parent_set',0)->get();
        $population = [];
        foreach($populations_result as $row) {
            $population[$row->id] = [
                'id'            =>  $row->id,
                'group_name'    =>  $row->group_name,
                'type'          =>  $row->type,
                'size_set'      =>  $row->size_set,
                'group_size_set'    => 0,
            ];
        }

        $sub_populations  = DB::table('populations')
                        ->select(DB::raw('sum(size_set) as size_set, parent_set'))
                        ->where('parent_set', '<>', 0)
                        ->groupBy('parent_set')
                        ->get();

        foreach($sub_populations as $row) {
            $population[$row->parent_set]['group_size_set'] = $row->size_set;
        }
        return response()->json([
            'message'   =>  'Get the population list',
            'result' => $population,
            'code'  =>  200,
        ]);
    }

    public function get($id) {
        return response()->json([
            'message'   =>  'Get the population by id',
            'result'    =>  Population::where('parent_set',$id)->get(),
            'code'      =>  200,
        ]);
    }

    public function create(Request $request) {
        $message = '';
        $code = 200;
        $status = 'error';

        try {
            $params = $request->json()->all();
            $params['created_at'] = now();
            $params['utm'] = create_random_string();
            Population::insert($params);
            $message = 'New population has been created successfully.';
            $status = 'success';
        } catch (\ErrorException $ex) {
            $status = 'error';
            $message = $ex->getMessage();
            $code = 451;
        } catch( \Illuminate\Database\QueryException $qe) {
            $status = 'error';
            $message =$qe->errorInfo;
            $code = 400;
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

        try {
            $population = Population::find($id);
            $parent_set = duplicate_population($population, 'population');
            $sub_samples = Population::where('parent_set', $id)->get();
            foreach($sub_samples as $row) {
                $sub_population = Population::find($row->id);
                duplicate_population($sub_population, 'sub_population', $parent_set);
            }

            $message = 'The population has been duplicated successfully.';
            $status = 'success';
        } catch (\ErrorException $ex) {
            $status = 'error';
            $message = $ex->message;
            $code = 451;
        } catch( \Illuminate\Database\QueryException $qe) {
            $status = 'error';
            $message =$qe->errorInfo;
            $code = 400;
        }

        return response()->json([
            'status'        =>  $status,
            'message'       =>  $message,
            'code'          =>  $code
        ]);
    }

    public function update(Request $request, $id) {
        $message = '';
        $code = 200;
        $status = 'error';

        try {
            $params = $request->json()->all();

            $population = Population::find($id);
            $population->group_name = $params['group_name'];
            $population->parent_set = $params['parent_set'];
            $population->size_set = $params['size_set'];
            $population->type = $params['type'];
            $population->save();

            $message = 'The population has been updated successfully.';
            $status = 'success';
        } catch (\ErrorException $ex) {
            $status = 'error';
            $message = $ex->message;
            $code = 451;
        } catch( \Illuminate\Database\QueryException $qe) {
            $status = 'error';
            $message =$qe->errorInfo;
            $code = 400;
        }

        return response()->json([
            'status'        =>  $status,
            'message'       =>  $message,
            'code'          =>  $code
        ]);
    }

    public function delete($id) {
        $populations = Population::where('id', $id)->orWhere('parent_set', $id);

        if ($populations->count() > 0)
        {
            $populations->delete();
            return response()->json([
                'status'        =>  'success',
                'message'       =>  'The population has been deleted successfully',
                'code'          =>  200
            ]);
        } else {
            return response()->json([
                'status'        =>  'warning',
                'message'       =>  'The population does not exist.',
                'code'          =>  404
            ]);
        }
    }
}
