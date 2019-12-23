<?php

namespace App\Http\Controllers\IslamicPrayTime;

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
use App\IslamicPrayTime;
use App\Http\Controllers\IslamicPrayTime\PrayController;

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
        $httpClient = new CurlHTTPClient(env('PRAY_CHANNEL_ACCESS_TOKEN'));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => env('PRAY_CHANNEL_SECRET')]);
    }

    public function notification_to_all_user($token)
    {
        $api_token = ApiToken::where('app', 'islamic_pray_time')->first();
        if ($api_token->token === $token) {
            $users = IslamicPrayTime::all()->toArray();
            foreach ($users as $key => $value) {
                $pray = new PrayController;
                $tomorrow = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
                $tomorrow = date('d-m-Y', $tomorrow);
                $result = $pray->get_pray_time($value['user_id'], $tomorrow);
                $result = new TextMessageBuilder($result);
                $this->bot->multicast([$value['user_id']], $result);
            }
        }
    }

    public function hook(Request $request)
    {
        foreach ($request->events as $event) {

            if (isset($event['message'])) {
                if ($event['message']['type'] === 'location') {
                    $result = $this->hook_location($event);
                }

                // Message Event = TextMessage
                if ($event['message']['type'] === 'text') {
                    $user_data = IslamicPrayTime::where('user_id', $event['source']['userId'])->first();
                    if ($user_data) {
                        $result = $this->hook_text($event);
                    } else {
                        $result = new TextMessageBuilder("แชร์ตำแหน่งล่าสุดของคุณเพื่อดูเวลาละหมาด");
                    }
                }
            }

            $this->bot->replyMessage($event['replyToken'], $result);
        }
    }

    private function hook_location($event)
    {
        $user_data = IslamicPrayTime::where('user_id', $event['source']['userId'])->first();
        $user_id = $event['source']['userId'];
        $latitude = $event['message']['latitude'];
        $longitude = $event['message']['longitude'];
        $pray = new PrayController;
        $city = $pray->get_city($latitude, $longitude);
        $res = '';

        if ($user_data) {
            $user_data->update([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'city' => $city
                ]);
            $res = "อัปเดทข้อมูลของคุณแล้ว";
        } else {
            IslamicPrayTime::create([
                'user_id' => $user_id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'city' => $city
                ]);
            $res = "เพิ่มข้อมูลของคุณแล้ว";
        }

        return new TextMessageBuilder($res);
    }

    private function hook_text($event)
    {
        $messageText = strtolower($event['message']['text']);

        switch ($messageText) {
            case "today":
                $pray = new PrayController;
                $result = $pray->get_pray_time($event['source']['userId']);
                $result = new TextMessageBuilder($result);
                break;
            case "tomorrow":
                $pray = new PrayController;
                $tomorrow = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
                $tomorrow = date('d-m-Y', $tomorrow);
                $result = $pray->get_pray_time($event['source']['userId'], $tomorrow);
                $result = new TextMessageBuilder($result);
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

}
