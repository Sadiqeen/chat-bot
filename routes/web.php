<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function ()  {
    return view('home', ['name' => 'home']);
});

// Air pollution
$router->group(['prefix' => 'air'], function () use ($router) {

    $router->post('hooks', [
        'as' => 'hooks', 'uses' => 'AirPollution\LineController@hook'
    ]);

    $router->get('pushMessage/{token}', [
        'as' => 'pushMessage', 'uses' => 'AirPollution\LineController@notification_to_all_user'
    ]);

});

// Test part
$router->get('test/{user_id}', [
    'as' => 'hooksxx', 'uses' => 'AirPollution\AirController@get_air_quality'
]);
