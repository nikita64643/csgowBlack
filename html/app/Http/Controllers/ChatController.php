<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use PhpParser\Node\Expr\Cast\Object_;
use App\User;
use LRedis;

class ChatController extends Controller
{

    const CHAT_CHANNEL = 'chat.message';
    const NEW_MSG_CHANNEL = 'new.msg';

    public function __construct()
    {
        parent::__construct();
        $this->redis = LRedis::connection();
    }

    public function  __destruct()
    {
        $this->redis->disconnect();
    }

    public function add_message(Request $request)
    {

        if (\Cache::has('addmsg.user.' . $this->user->id)) {
            return response()->json(['message' => 'Вы слишком часто отправляете сообщения!', 'status' => 'error']);
        }

        \Cache::put('addmsg.user.' . $this->user->id, '', 0.05);

        $stavki = \DB::table('bets')->where('user_id', '=', $this->user->id)->get();

        if ($this->user->banchat == 1) {
            return response()->json(['message' => 'Вы забанены в чате ! Срок : Навсегда', 'status' => 'error']);
        }

        $userid = $this->user->steamid64;
        $admin = $this->user->is_admin;
        $username = htmlspecialchars($this->user->username);
        $avatar = $this->user->avatar;
        $time = date('H:i', time());
        $messages = $this->_validateMessage($request);


        if ($this->user->is_admin == 0) {
            if ($stavki == null) {
               return response()->json(['message' => 'Вы должны поставить ставку чтобы писать в чате', 'status' => 'error']);
            }
        }

        if ($this->user->is_admin == 1) {
            if (substr_count($messages, '/clear')) {
                $this->redis->del(self::CHAT_CHANNEL);
                return response()->json(['message' => 'Вы отчистили чат !', 'status' => 'success']);
            }

        }

        if (preg_match("/href|url|http|www|.ru|.com|.net|.info|csgo|winner|ru|com|net|info|.org/i", $messages)) {
            return response()->json(['message' => 'Ссылки запрещены !', 'status' => 'error']);
        }

        $returnValue = ['userid' => $userid, 'avatar' => $avatar, 'time' => $time, 'messages' => htmlspecialchars($messages), 'username' => $username, 'admin' => $admin];

        $this->redis->rpush(self::CHAT_CHANNEL, json_encode($returnValue));

        $this->redis->publish(self::NEW_MSG_CHANNEL, json_encode($returnValue));

        return response()->json(['message' => 'Ваше сообщение успешно отправлено !', 'status' => 'success']);

    }


    private function _validateMessage($request)
    {
        $val = \Validator::make($request->all(), [
            'messages' => 'required|string|max:255'
        ], [
            'required' => 'Сообщение не может быть пустым!',
            'string' => 'Сообщение должно быть строкой!',
            'max' => 'Максимальный размер сообщения 255 символов.',
        ]);
        if ($val->fails())
            $this->throwValidationException($request, $val);

        return $request->get('messages');
    }


    public function chat(Request $request)
    {
        $value5 = $this->redis->lrange(self::CHAT_CHANNEL, 0, -1);
        $is = 0;
        foreach ($value5 as $key => $newchat5[$is]) {
            $is++;
        }
        $test = $is;

        $min = $test-15;

        $value = $this->redis->lrange(self::CHAT_CHANNEL, $min, $test);
        $i = 0;
        foreach ($value as $key => $newchat[$i]) {
            $value2[$i] = json_decode($newchat[$i], true);
            $returnValue[$i] = [
                'userid' => $value2[$i]['userid'],
                'avatar' => $value2[$i]['avatar'],
                'time' => $value2[$i]['time'],
                'messages' => $value2[$i]['messages'],
                'username' => $value2[$i]['username'],
                'admin' => $value2[$i]['admin']];

            $i++;

        }
        return $returnValue;
    }


}
