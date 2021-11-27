<?php

namespace App\Http\Controllers;

use App\Bet;
use App\Game;
use App\Item;
use App\Services\SteamItem;
use App\Ticket;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;


class TicketController extends Controller
{

    public function buyTicket(Request $request)
    {
        $user_id = $this->user->steamid64;
        $id = $request->get('id');
        $ticket = Ticket::find($id);

        if (!$request->has('id')) {
            return response()->json(['text' => 'Ошибка. Попробуйте обновить страницу.', 'type' => 'error']);
        }

        if (is_null($ticket)) {
            return response()->json(['text' => 'Ошибка.', 'type' => 'error']);
        }
        else
        {
            if ($this->user->money >= $ticket->price)
            {
                $this->user->money = $this->user->money - $ticket->price; // Снимаем деньги за карточку
                $this->user->save();

                $last_id = User::where('steamid64', $user_id)
                    ->select('inventory')
                    ->get();

                // Проверяем наличие карточки в инвентаре
                if (!empty($last_id[0]['inventory'])) {
                    $lastcard = json_decode($last_id[0]['inventory'])->id;
                }
                else{
                    $lastcard = 0;
                }

                // Массив с купленными карточками
                $returnValue = [
                    'id' => $lastcard + 1,
                    'card_id' => $ticket->id
                ];


                User::where('steamid64', $user_id)
                    ->update(['inventory' => json_encode($returnValue)]);

                return response()->json(['text' => 'Действие выполнено', 'type' => 'success']);
            }
            else
            {
                return response()->json(['text' => 'Недостаточно средств на балансе', 'type' => 'error']);
            }
        }
    }

    public function sellTicket()
    {

    }


}