<?php

namespace App\Http\Controllers\IslamicPrayTime;

use App\Http\Controllers\Controller;
use OpenCage\Geocoder\Geocoder;
use GuzzleHttp\Client;
use App\IslamicPrayTime;

class PrayController extends Controller
{
    protected $geocoder;

    public function __construct()
    {
        $this->geocoder = new Geocoder(env('OPENCAGE_API'));
    }

    public function get_city($latitude, $longitude)
    {
        $result = $this->geocoder->geocode($latitude.','.$longitude);
        $state = $result['results'][0]['components']['state'];
        $state = explode(" ", $state);
        $city = null;

        if (count($state) > 2) {
            for ($i = 0; $i < count($state) - 1; $i++) {
                $city = $city.$state[$i];
                if ($i != count($state) - 2) {
                    $city = $city." ";
                }
            }
        } else {
            $city = $city.$state[0];
        }

        return $city;
    }

    public function get_pray_time($user_id, $tomorrow = null)
    {
        $user_data = IslamicPrayTime::where('user_id', $user_id)->first();
        $date = date("d-m-Y");
        if ($tomorrow) {
            $date = $tomorrow;
        }
        $destination = 'https://muslimsalat.com/'. $user_data->city .'/'.$date.'.json';
        $client = new Client;
        $request = $client->get($destination, [
            'query' => [
                'key' => env('MUSLIMSALAT_API')
            ]
        ]);
        $result = json_decode($request->getBody()->getContents());
        $result = $this->template($result->items[0],  $user_data->city, $date, $tomorrow);
        return $result;
    }

    private function template($data, $city, $date, $tomorrow)
    {
        $date = explode("-", $date);
        $TH_Month = array("มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฏาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม");
        $month = (int)$date[1] - 1;
        $years = (int)$date[2] + 543 ;
        $tmrMessage = ($tomorrow) ? "เวลาละหมาดพรุ่งนี้\n--------\n" : '';
        $message = $tmrMessage .
        "เวลาละหมาดสำหรับ " . $city . "\n".
        "--------\n".
        "วันที่ \t" . $date[0] . ' ' . $TH_Month[$month] . ' ' . $years . "\n".
        "--------\n".
        "ซุบฮิ \t\t" . $data->fajr  . "\n".
        "ซุฮฺริ \t\t" . $data->dhuhr  . "\n".
        "อัศริ \t\t" . $data->asr  . "\n".
        "มักริบ \t" . $data->maghrib  . "\n".
        "อีซา \t\t" . $data->isha   . "\n".
        "--------\n".
        "ชูรุก \t\t" . $data->shurooq . "\n".
        "--------\n".
        "เวลาอาจคลาดเคลื่อนจาก Aplication อื่นๆ ±5 นาที\n";

        return $message;
    }

}
