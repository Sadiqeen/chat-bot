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

// Pray time
$router->group(['prefix' => 'pray'], function () use ($router) {

    $router->post('hooks', [
        'as' => 'pray_hooks', 'uses' => 'IslamicPrayTime\LineController@hook'
    ]);

    $router->get('pushMessage/{token}', [
        'as' => 'pray_pushMessage', 'uses' => 'IslamicPrayTime\LineController@notification_to_all_user'
    ]);

});

// Test part
// $router->get('test', [
//     'as' => 'hooksxx', 'uses' => 'IslamicPrayTime\PrayController@test'
// ]);
