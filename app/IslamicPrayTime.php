<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IslamicPrayTime extends Model
{
    protected $table="islamic_pray_time";

    protected $fillable = [
        'user_id', 'latitude', 'longitude', 'city', 'notification'
    ];

}
