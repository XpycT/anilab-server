<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('update/info', 'Admin\UpdateController@getVersion');
Route::get('update/file', 'Admin\UpdateController@getFile');

Route::group(array('prefix' => 'api/v1','middleware' => 'api'), function () {
    Route::group(array('prefix' => 'animeland'), function () {
        Route::match(['get', 'post'], 'page/{page?}', 'Api\v1\animeland\MovieController@page')->where('page', '[0-9]+');
        Route::get('show/{movieId}', 'Api\v1\animeland\MovieController@show')->where('movieId', '[0-9]+');
        Route::get('show/{movieId}/comments', 'Api\v1\animeland\MovieController@comments')->where('movieId', '[0-9]+');
        Route::get('show/{movieId}/files', 'Api\v1\animeland\MovieController@files')->where('movieId', '[0-9]+');
    });
    Route::group(array('prefix' => 'anidub'), function () {
        Route::match(['get', 'post'], 'page/{page?}', 'Api\v1\anidub\MovieController@page')->where('page', '[0-9]+');
        Route::get('show/{movieId}', 'Api\v1\anidub\MovieController@show')->where('movieId', '[0-9]+');
        Route::get('show/{movieId}/comments', 'Api\v1\anidub\MovieController@comments')->where('movieId', '[0-9]+');
        Route::get('show/{movieId}/files', 'Api\v1\anidub\MovieController@files')->where('movieId', '[0-9]+');
    });
    Route::group(array('prefix' => 'anistar'), function () {
        Route::match(['get', 'post'], 'page/{page?}', 'Api\v1\anistar\MovieController@page')->where('page', '[0-9]+');
        Route::get('show/{movieId}', 'Api\v1\anistar\MovieController@show')->where('movieId', '[0-9]+');
        Route::get('show/{movieId}/comments', 'Api\v1\anistar\MovieController@comments')->where('movieId', '[0-9]+');
        Route::get('show/{movieId}/files', 'Api\v1\anistar\MovieController@files')->where('movieId', '[0-9]+');
    });
});
Route::get('api/v1/anistar/image', 'Api\v1\anistar\MovieController@image');
// Admin area
Route::get('admin', function () {
    return redirect('admin/token');
});
Route::group(array('namespace' => 'Admin', 'middleware' => 'auth' ), function () {
    Route::resource('admin/token', 'TokenController', ['except' => 'show']);
    Route::resource('admin/update', 'UpdateController', ['except' => 'show']);
});

// Logging in and out
Route::get('auth/login', 'Auth\AuthController@getLogin');
Route::post('auth/login', 'Auth\AuthController@postLogin');
Route::get('auth/logout', 'Auth\AuthController@getLogout');