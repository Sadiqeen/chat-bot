<?php

namespace App\Http\Controllers\AirPollution;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use App\Http\Controllers\AirPollution\AirController;
use App\Http\Controllers\AirPollution\UserController;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use App\ApiToken;
use App\Location;

class LineController extends Controller
{
    protected $bot;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $httpClient = new CurlHTTPClient(env('AIR_CHANNEL_ACCESS_TOKEN'));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => env('AIR_CHANNEL_SECRET')]);
    }

    public function notification_to_all_user($token)
    {
        $api_token = ApiToken::where('app', 'air_pollution')->first();
        if ($api_token->token === $token) {
            $locations = Location::with('users')->get();
            foreach ($locations as $location) {
                $aqi = new AirController;
                $aqi = $aqi->get_air_quality_from_location($location);
                $result = $this->carousel_template($aqi);
                $users = [];
                foreach ($location->users as $user) {
                    if ($user->notification === 1) {
                        $users[] = $user->user_id;
                    }
                }
                if (count($users) !== 0) {
                    $this->bot->multicast($users, $result);
                }
            }
        }
    }

    public function hook(Request $request)
    {
        foreach ($request->events as $event) {

            if (isset($event['postback'])) {
                if ($event['postback']['data'] === 'aqi_index') {
                    $img_url = 'https://res.cloudinary.com/dc4jn4lc0/image/upload/v1569052857/aqi/aqi_index_sji3h8.png';
                    $result = new ImageMessageBuilder($img_url, $img_url);
                }
            }

            if (isset($event['message'])) {
                if ($event['message']['type'] === 'location') {
                    $result = $this->hook_location($event);
                }

                // Message Event = TextMessage
                if ($event['message']['type'] === 'text') {
                    $user = new UserController;
                    if ($user->check_exist_user($event['source']['userId'])) {
                        $result = $this->hook_text($event);
                    } else {
                        $result = new TextMessageBuilder("แชร์ตำแหน่งล่าสุดของคุณเพื่อดูดัชนีคุณภาพอากาศ");
                    }
                }
            }

            $this->bot->replyMessage($event['replyToken'], $result);
        }
    }

    private function hook_location($event)
    {
        $user = new UserController;
        $user_id = $event['source']['userId'];
        $res = '';

        if ($user->check_exist_user($user_id)) {
            $user->update_user($event);
            $res = "อัปเดทข้อมูลของคุณแล้ว";
        } else {
            $user->add_new_user($event);
            $res = "เพิ่มข้อมูลของคุณแล้ว";
        }

        return new TextMessageBuilder($res);
    }

    private function hook_text($event)
    {
        $messageText = strtolower($event['message']['text']);

        switch ($messageText) {
            case "air quality":
                $aqi = new AirController;
                $aqi = $aqi->get_air_quality($event['source']['userId']);
                $result = $this->carousel_template($aqi);
                break;
            case "aqi index":
                $img_url = 'https://res.cloudinary.com/dc4jn4lc0/image/upload/v1569052857/aqi/aqi_index_sji3h8.png';
                $result = new ImageMessageBuilder($img_url, $img_url);
                break;
            case "notification":
                $user = new UserController;
                $notification_status = $user->notification_switch($event['source']['userId']);
                $result = new TextMessageBuilder('Notification : ' . $notification_status);
                break;
            default:
                $result = new TextMessageBuilder("ไม่มีข้อมูลที่ระบุ");
                break;
        }

        return $result;
    }

    private function carousel_template($aqi)
    {
        $actions = array(
            new PostbackTemplateActionBuilder("ค่าดัชนีต่างๆ", "aqi_index"),
        );
        $columns = array(
            new CarouselColumnTemplateBuilder(
                "คุณภาพอากาศ " . $aqi['title'] . " (" . $aqi['aqi'] . ")",
                $aqi['description'],
                $aqi['imageUrl'],
                $actions
            )
        );
        $carousel = new CarouselTemplateBuilder($columns);
        return new TemplateMessageBuilder("คุณภาพอากาศ " . $aqi['title'] . " (" . $aqi['aqi'] . ")", $carousel);
    }
}
