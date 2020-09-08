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

    // Surveies
    Route::get('/all_surveies', 'Api\SurveyController@all')->name('surveies.all');
    Route::get('/survey/template/{id}/{utm}', 'Api\SurveyController@template')->name('surveies.template');
    Route::get('/survey/edit/{id}','Api\SurveyController@edit')->name('survieies.edit');
    Route::post('/surveies', 'Api\SurveyController@create')->name('surveies.create');
    Route::post('/surveies/duplicate/{id}', 'Api\SurveyController@duplicate')->name('surveies.duplicate');
    Route::put('/surveies/{id}', 'Api\SurveyController@update')->name('surveies.update');
    Route::delete('/surveies/{id}', 'Api\SurveyController@delete')->name('surveies.delete');

});