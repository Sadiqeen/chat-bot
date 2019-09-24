<?php

namespace App\Http\Controllers\AirPollution;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AirPollution\AirController;
use App\AirUser;
use App\Location;

class UserController extends Controller
{
    protected $user;
    protected $location;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->user = new AirUser;
        $this->location = new Location;
    }

    public function add_new_user($event)
    {
        $latitude = $event['message']['latitude'];
        $longitude = $event['message']['longitude'];

        $air = new AirController;
        $air_data_location = $air->get_air_original($latitude, $longitude);

        $exist_location = $this->location->where('state', $air_data_location->state)
                    ->where('city', $air_data_location->city)
                    ->first();

        if (!$exist_location) {
            $this->location->aqi      = $air_data_location->current->pollution->aqius;
            $this->location->state      = $air_data_location->state;
            $this->location->city       = $air_data_location->city;
            $this->location->country    = $air_data_location->country;
            $this->location->save();
        }

        $this->user->user_id    = $event['source']['userId'];
        $this->user->latitude   = $latitude;
        $this->user->longitude  = $longitude;
        $this->user->location_id  = $exist_location ? $exist_location->id : $this->location->id;
        $this->user->save();
    }

    public function update_user($event)
    {
        $user_id =  $event['source']['userId'];
        $latitude = $event['message']['latitude'];
        $longitude = $event['message']['longitude'];

        $air = new AirController;
        $air_data_location = $air->get_air_original($latitude, $longitude);

        $exist_location = $this->location->where('state', $air_data_location->state)
                    ->where('city', $air_data_location->city)
                    ->first();

        if (!$exist_location) {
            $this->location->aqi      = $air_data_location->current->pollution->aqius;
            $this->location->state      = $air_data_location->state;
            $this->location->city       = $air_data_location->city;
            $this->location->country    = $air_data_location->country;
            $this->location->save();
        }

        $this->user->where('user_id', $user_id)->update([
            'location_id'  => $exist_location ? $exist_location->id : $this->location->id,
        ]);
    }

    public function notification_switch($user_id)
    {
        $user = $this->user->where('user_id', $user_id)->first();
        if ($user->notification === 1) {
            $this->user->where('user_id', $user_id)->update([
                'notification'  => 0,
            ]);
            return "off";
        } else {
            $this->user->where('user_id', $user_id)->update([
                'notification'  => 1,
            ]);
            return "on";
        }
    }

    public function check_exist_user($user_id)
    {
        $exist_user = $this->user->where('user_id', $user_id)->first();
        if ($exist_user) {
            return true;
        }
        return false;
    }

    public function get_user($user_id)
    {
        return $this->user->with('aqi')->where('user_id', $user_id)->first();
    }
}
