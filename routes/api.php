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
    Route::get('/themes/{id}', 'Api\ThemeController@get')->name('theme.get');
    Route::post('/themes', 'Api\ThemeController@create')->name('themes.create');
    Route::put('/themes/{id}', 'Api\ThemeController@update')->name('themes.update');
    Route::delete('/themes/{id}', 'Api\ThemeController@delete')->name('themes.delete');
});