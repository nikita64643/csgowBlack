<?php

namespace App\Http\Controllers;

use App\Game;
use App\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use PhpParser\Node\Expr\Cast\Object_;

class AjaxController extends Controller
{
    public function parseAction(Request $request)
    {
        switch($request->get('action')){
            case 'userInfo':
                $user = User::where('steamid64', $request->get('id'))->first();
                if(!is_null($user)) {
                    $games = Game::where('winner_id', $user->id)->get();
                    $wins = $games->count();
                    $gamesPlayed = \DB::table('games')
                        ->join('bets', 'games.id', '=', 'bets.game_id')
                        ->where('bets.user_id', $user->id)
                        ->groupBy('bets.game_id')
                        ->orderBy('games.created_at', 'desc')
                        ->select('games.*', \DB::raw('SUM(bets.price) as betValue'))->get();
                    $gamesList = [];
                    $i = 0;
                    foreach ($gamesPlayed as $game) {
                        $gamesList[$i] = (object)[];
                        $gamesList[$i]->id = $game->id;
                        $gamesList[$i]->win = false;
                        $gamesList[$i]->bank = $game->price;
                        if ($game->winner_id == $user->id) $gamesList[$i]->win = true;
                        if ($game->status != Game::STATUS_FINISHED) $gamesList[$i]->win = 1;
                        $gamesList[$i]->chance = round($game->betValue / $game->price, 3) * 100;
                        $i++;
                    }
                    return response()->json([
                        'username' => $user->username,
                        'avatar' => $user->avatar,
                        'votes' => $user->votes,
                        'wins' => $wins,
                        'url' => 'http://steamcommunity.com/profiles/' . $user->steamid64 . '/',
                        'winrate' => count($gamesPlayed) ? round($wins / count($gamesPlayed), 3) * 100 : 0,
                        'totalBank' => $games->sum('price'),
                        'games' => count($gamesPlayed),
                        'list' => $gamesList
                    ]);
                }
                return response('Error. User not found.', 404);
                break;
            case 'voteUser':
                $user = User::where('steamid64', $request->get('id'))->first();
                if(!is_null($user)) {
                    if($user->id == $this->user->id)
                        return response()->json([
                            'status' => 'error',
                            'msg' => 'Вы не можете голосовать за себя.'
                        ]);
                    $votes = $this->redis->lrange($user->steamid64 . '.user.votes.list', 0, -1);
                    if(in_array($this->user->id, $votes)){
                        return response()->json([
                            'status' => 'error',
                            'msg' => 'Вы уже голосовали за этого пользователя.'
                        ]);
                    }else{
                        $user->votes++;
                        $user->save();
                        $this->redis->rpush($user->steamid64 . '.user.votes.list', $this->user->id);
                        return response()->json([
                            'status' => 'success',
                            'votes' => $user->votes
                        ]);
                    }
                }
                return response('Error. User not found.', 404);
                break;
        }
    }
}
