<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table="locations";

    protected $fillable = [
        'aqi', 'state', 'city', 'country'
    ];

    public function users()
    {
        return $this->hasMany('App\AirUser');
    }
}
