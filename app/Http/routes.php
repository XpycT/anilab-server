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

Route::group(array('prefix' => 'api/v1'), function()
{
    Route::group(array('prefix' => 'animeland'), function()
    {
        Route::match(['get', 'post'],'page/{page?}', 'Api\v1\animeland\MovieController@page')->where('page', '[0-9]+');
        Route::get('show/{movieId}', 'Api\v1\animeland\MovieController@show')->where('movieId', '[0-9]+');
        Route::get('show/{movieId}/comments', 'Api\v1\animeland\MovieController@comments')->where('movieId', '[0-9]+');
    });
    Route::group(array('prefix' => 'anidub'), function()
    {
        Route::match(['get', 'post'],'page/{page?}', 'Api\v1\anidub\MovieController@page')->where('page', '[0-9]+');
        Route::get('show/{movieId}', 'Api\v1\anidub\MovieController@show')->where('movieId', '[0-9]+');
        Route::get('show/{movieId}/comments', 'Api\v1\anidub\MovieController@comments')->where('movieId', '[0-9]+');
    });
});