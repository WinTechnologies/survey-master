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
// Results
Route::prefix('pre-survey')->group(function(){
    Route::get('/basic/{survey_id}', 'Api\ResultController@basic')->name('results.basic');
    Route::get('/is_answered/{survey_id}', 'Api\ResultController@is_answered')->name('results.is_answered');
    Route::get('/is_limited/{survey_id}', 'Api\ResultController@is_limited')->name('results.is_limited');
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
});