<?php
use App\Models\Population;

if(!function_exists('duplicate_population')) {
    function duplicate_population($model, $name, $parent_set=0) {
        $newObj = $model->replicate();
        $newObj->created_at = now();
        switch($name) {
            case 'population':
                $newObj->utm = '';
                break;
            case 'sub_population':
                $newObj->utm = create_random_string();
                $newObj->parent_set = $parent_set;
                break;
        }

        $newObj->save();

        return $newObj->id;
    }
}
