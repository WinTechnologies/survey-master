<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Theme;

class ThemeController extends Controller
{
    public function index()
    {
        return response()->json([
            'message'   =>  'Get the theme list',
            'result' => Theme::all(),
            'code'  => 200
        ]);
    }

    public function get($id) {
        return response()->json([
            'message'   =>  'Get the theme by id',
            'result'    =>  Theme::find($id),
            'code'      =>  200
        ]);
    }

    public function create(Request $request) {
        $message = '';
        $code = 200;
        $status = 'error';

        try {
            // Create Theme Directory in the storage
            if(!Storage::disk('local')->exists('public/themes'))
            {
                $permissions = intval( config('permissions.directory'), 8 );
                Storage::disk('local')->makeDirectory('public/themes', $permissions, true);
            }

            $params = $request->json()->all();
            $params['img_url'] = $params['img_url'];
            $params['created_at'] = now();

            Theme::insert($params);
            $message = 'New theme has been created successfully.';
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

    public function update(Request $request, $id) {
        $message = '';
        $code = 200;
        $status = 'error';

        try {
            $params = $request->json()->all();

            // Create Theme Directory in the storage
            if(!Storage::disk('local')->exists('public/themes'))
            {
                $permissions = intval( config('permissions.directory'), 8 );
                Storage::disk('local')->makeDirectory('public/themes', $permissions, true);
            }

            // Image Upload
            $upload_path = $params['img_url'];
            Storage::disk('local')->put($upload_path, $image, 'public');

            $theme = Theme::find($id);
            $theme->name = $params['name'];
            $theme->img_url = $upload_path;
            $theme->size_ans = $params['size_ans'];
            $theme->size_ques = $params['size_ques'];
            $theme->text_color = $params['text_color'];
            $theme->border_color = $params['border_color'];
            $theme->size_ans_img = $params['size_ans_img'];
            $theme->background_color = $params['background_color'];
            $theme->button_color = $params['button_color'];
            $theme->footer_color = $params['footer_color'];
            $theme->font_family = $params['font_family'];
            $theme->save();

            $message = 'The theme has been updated successfully.';
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

    public function delete($id) {
        $theme = Theme::find($id);
        if($theme)
            $theme->delete();

        return response()->json([
            'status'        =>  'success',
            'message'       =>  'The theme has been deleted successfully',
            'code'          =>  200
        ]);

    }
}
