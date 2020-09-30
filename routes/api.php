<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('logout', 'Api\UserController@logout')->name('logout');
Route::post('login', 'Api\UserController@login')->name('login');
Route::post('register', 'Api\UserController@register')->name('register');

Route::get('test', 'Api\ResultController@test')->name('test');

// Live-Result
Route::prefix('live-result')->group(function(){
    Route::post('/total_by_population/{survey_id}', 'Api\ResultController@total_by_population')->name('result.total_by_population');
    Route::post('/get_text_by_survey/{survey_id}', 'Api\ResultController@get_text_by_survey')->name('result.get_text_by_survey');
    Route::post('/get_answers_of_detail/{survey_id}', 'Api\ResultController@get_answers_of_detail')->name('result.get_answers_of_detail');
    Route::delete('/delete_results/{random_session_id}', 'Api\ResultController@delete_results')->name('delete_results');
    Route::post('/random_cut_results/{survey_id}', 'Api\ResultController@random_cut_results')->name('random_cut_results');
    Route::post('/get_table_by_survey/{survey_id}', 'Api\ResultController@get_table_by_survey')->name('result.get_table_by_survey');
    Route::post('/get_graph_by_survey/{survey_id}', 'Api\ResultController@get_graph_by_survey')->name('result.get_graph_by_survey');
    Route::post('/get_weight_by_survey/{survey_id}', 'Api\ResultController@get_weight_by_survey')->name('result.get_weight_by_survey');
    Route::post('/get_insight_by_survey/{survey_id}', 'Api\ResultController@get_insight_by_survey')->name('result.get_insight_by_survey');
    Route::get('/get_random_session_ids/{survey_id}', 'Api\ResultController@get_random_session_ids')->name('result.get_random_session_ids');
});

// Export
Route::prefix('export')->group(function(){
    Route::get('/to_excel/{survey_id}', 'Api\ExportController@to_excel')->name('to_excel');
    Route::get('/get_survey_data/{survey_id}', 'Api\ExportController@get_survey_data')->name('get_survey_data');
    Route::get('/get_survey_result/{survey_id}', 'Api\ExportController@get_survey_result')->name('get_survey_result');
    Route::get('/get_survey_table/{survey_id}', 'Api\ExportController@get_survey_table')->name('get_survey_table');
    Route::get('/get_survey_weight/{survey_id}', 'Api\ExportController@get_survey_weight')->name('get_survey_weight');
    Route::get('/get_survey_demographic/{survey_id}', 'Api\ExportController@get_survey_demographic')->name('get_survey_demographic');
});

// Pre-Survey
Route::prefix('pre-survey')->group(function(){
    Route::get('/basic/{survey_id}', 'Api\ResultController@basic')->name('results.basic');
    Route::get('/is_answered/{survey_id}', 'Api\ResultController@is_answered')->name('results.is_answered');
    Route::get('/is_limited/{survey_id}/{population_id}', 'Api\ResultController@is_limited')->name('results.is_limited');
    Route::get('/is_expired/{survey_id}', 'Api\ResultController@is_expired')->name('results.is_expired');
    Route::post('/user_answer', 'Api\ResultController@user_answer')->name('results.user_answer');

    // Drop-Off
    Route::post('/drop-off/init/{survey_id}', 'Api\DropOffController@init')->name('drop_offs.init');
    Route::put('/drop-off/{survey_id}', 'Api\DropOffController@update')->name('drop_offs.update');
    Route::put('/answer-started/{survey_id}', 'Api\DropOffController@answer_started')->name('drop_offs.answer_started');

    // Language
    Route::get('/language/{lang}', 'Api\LanguageController@index')->name('language.index');
    Route::get('/survey/{id}','Api\SurveyController@edit');
});


Route::middleware('auth:api')->group(function(){
    // Get Current User
    Route::get('/user', 'Api\UserController@currentUser')->name('user');

    // Settings
    Route::get('/settings', 'Api\SettingController@index')->name('settings');
    Route::put('/settings', 'Api\SettingController@update')->name('settings.update');

    // Themes
    Route::get('/themes', 'Api\ThemeController@index')->name('themes');
    Route::get('/themes/{id}', 'Api\ThemeController@get')->name('themes.get');
    Route::post('/themes', 'Api\ThemeController@create')->name('themes.create');
    Route::put('/themes/{id}', 'Api\ThemeController@update')->name('themes.update');
    Route::delete('/themes/{id}', 'Api\ThemeController@delete')->name('themes.delete');

    // Population Sample Set
    Route::get('/populations','Api\PopulationController@index')->name('populations');
    Route::get('/populations/{id}','Api\PopulationController@get')->name('populations.get');
    Route::post('/populations','Api\PopulationController@create')->name('populations.create');
    Route::post('/populations/duplicate/{id}', 'Api\PopulationController@duplicate')->name('populations.duplicate');
    Route::put('/populations/{id}', 'Api\PopulationController@update')->name('populations.update');
    Route::delete('/populations/{id}', 'Api\PopulationController@delete')->name('populations.delete');

    // Surveys
    Route::get('/all_surveys', 'Api\SurveyController@all')->name('surveys.all');
    Route::get('/survey/template/{id}/{utm}', 'Api\SurveyController@template')->name('surveys.template');
    Route::get('/survey/edit/{id}','Api\SurveyController@edit')->name('surveys.edit');
    Route::post('/surveys', 'Api\SurveyController@create')->name('surveys.create');
    Route::post('/surveys/duplicate/{id}', 'Api\SurveyController@duplicate')->name('surveys.duplicate');
    Route::delete('/surveys/{id}', 'Api\SurveyController@delete')->name('surveys.delete');

    // Import
    Route::post('/doc-import', 'Api\SurveyController@doc_import')->name('surveys.doc_import');

    // Upload
    Route::post('/upload','Api\SurveyController@upload')->name('surveys.upload');

});