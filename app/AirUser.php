<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AirUser extends Model
{
    protected $table="air_pollution_users";

    protected $fillable = [
        'user_id', 'latitude', 'longitude', 'notification', 'location_id',
    ];

    public function aqi()
    {
        return $this->belongsTo('App\Location', 'location_id', 'id');
    }
}
