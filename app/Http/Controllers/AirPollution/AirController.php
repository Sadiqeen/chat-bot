<?php

namespace App\Http\Controllers\AirPollution;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use App\Http\Controllers\AirPollution\UserController;
use App\AirUser;
use App\Location;
use DateTime;

class AirController extends Controller
{
    protected $user;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->user = new UserController;
    }

    public function get_air_original($lat, $lon)
    {
        $destination = 'https://api.airvisual.com/v2/nearest_city';
        $client = new Client;
        $request = $client->get($destination, [
            'query' => [
                'lat' => $lat,
                'lon' => $lon,
                'key' => env('AIR_TOKEN_KEY')
            ]
        ]);
        $response = json_decode($request->getBody()->getContents());
        return $response->data;
    }

    public function get_air_quality($user_id)
    {
        $user_data = $this->user->get_user($user_id);
        $aqi = $this->get_data_api($user_data->aqi);
        $result = $this->air_description($aqi);

        return $result;
    }

    public function get_air_quality_from_location($location)
    {
        $aqi = $this->get_data_api($location);
        $result = $this->air_description($aqi);

        return $result;
    }

    private function air_description($aqi)
    {
        $result = array(
            'title' =>  '',
            'description' =>  '',
            'imageUrl' =>  '',
            'aqi' => $aqi,
        );

        if ($aqi < 50) {
            $result['title'] = 'ดี';
            $result['description'] = 'ใช้ชีวิตได้ตามปกติ';
            $result['imageUrl'] = 'https://res.cloudinary.com/dc4jn4lc0/image/upload/v1568965890/aqi/good_vl9ubt.png';
        } elseif ($aqi < 100) {
            $result['title']  = 'ปานกลาง';
            $result['description'] = 'ประชาชนที่ไวต่อมลพิษมากกว่าคนทั่วไป ควรลดการออกแรงหนักหรือเป็นเวลานาน';
            $result['imageUrl'] = 'https://res.cloudinary.com/dc4jn4lc0/image/upload/v1568966430/aqi/Moderate_nkrwak.png';
        } elseif ($aqi < 150) {
            $result['title']  = 'ไม่ดีต่อกลุ่มเสี่ยง';
            $result['description']  = 'ประชาชนในกลุ่มเสี่ยง ควรลดกิจกรรมนอกอาคารที่ใช้แรงหนักหรือเป็นเวลานาน';
            $result['imageUrl'] = 'https://res.cloudinary.com/dc4jn4lc0/image/upload/v1568966622/aqi/unhealthy_s_ffvyhs.png';
        } elseif ($aqi < 200) {
            $result['title']  = 'ไม่ดี';
            $result['description'] = 'ประชาชนควรลดกิจกรรมนอกอาคารที่ใช้แรงหนักหรือเป็นเวลานาน';
            $result['imageUrl'] = 'https://res.cloudinary.com/dc4jn4lc0/image/upload/v1568967035/aqi/unhealthy_yrdw37.png';
        } elseif ($aqi < 300) {
            $result['title']  = 'ไม่ดีอย่างยิ่ง';
            $result['description'] = 'ประชาชนควรหลีกเลี่ยงกิจกรรมนอกอาคารที่ใช้แรงหนักหรือเป็นเวลานาน';
            $result['imageUrl'] = 'https://res.cloudinary.com/dc4jn4lc0/image/upload/v1568967035/aqi/v_unhealthy_muo7qo.png';
        } elseif ($aqi < 500 || $aqi > 500) {
            $result['title']  = 'อันตราย';
            $result['description'] = 'ประชาชนควรงดกิจกรรมนอกอาคารทุกชนิด';
            $result['imageUrl'] = 'https://res.cloudinary.com/dc4jn4lc0/image/upload/v1568967035/aqi/hazardous_smxldp.png';
        }

        return $result;
    }

    private function get_data_api($location)
    {
        $aqi = $location->aqi;

        $now = new DateTime();
        $timestamp = strtotime($location->updated_at);
        $last_update = new DateTime("@{$timestamp}");
        $last_update->modify('+30 minutes');

        if ($last_update < $now) {
            $destination = 'https://api.airvisual.com/v2/city';
            $client = new Client;
            $request = $client->get($destination, [
                'query' => [
                    'city' => $location->city,
                    'state' => $location->state,
                    'country' => $location->country,
                    'key' => env('AIR_TOKEN_KEY')
                ]
            ]);
            $response = json_decode($request->getBody()->getContents());

            $aqi = $response->data->current->pollution->aqius;

            // update db
            Location::where('id', $location->id)->update([
                'aqi'  => $aqi,
            ]);
        }

        return $aqi;
    }

    public function test()
    {
        $now = new DateTime();
        $new = new DateTime();
        $new->modify('+30 minutes');

        return Location::with('users')->get();
    }
}
