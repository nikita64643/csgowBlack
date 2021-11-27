<?php

namespace App\Http\Controllers;

use App\Bet;
use App\Game;
use App\Item;
use App\Services\SteamItem;
use App\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class PagesController extends Controller
{

    public function support()
    {
        parent::setTitle('Поддержка - ');

        return view('pages.support');
    }

    public function fairplay($gameID)
    {
        parent::setTitle('Честная игра - ');

        $game = Game::with(['winner'])->where('status', Game::STATUS_FINISHED)->where('id', $gameID)->first();
        return view('pages.fairplay', compact('game'));
    }

    public function fairplay_no()
    {
        parent::setTitle('Честная игра - ');

        return view('pages.fairplay');
    }

    public function about()
    {
        parent::setTitle('О сайте - ');

        return view('pages.about');
    }

    public function top()
    {
        parent::setTitle('Топ - ');

        $users = \DB::table('users')
            ->select('users.id',
                'users.username',
                'users.avatar',
                'users.steamid64',
                \DB::raw('SUM(games.price) as top_value'),
                \DB::raw('COUNT(games.id) as wins_count')
            )
            ->join('games', 'games.winner_id', '=', 'users.id')
            ->groupBy('users.id')
            ->orderBy('top_value', 'desc')
            ->limit(20)
            ->get();
        $place = 1;
        $i = 0;
        foreach ($users as $u) {
            $users[$i]->games_played = count(\DB::table('games')
                ->join('bets', 'games.id', '=', 'bets.game_id')
                ->where('bets.user_id', $u->id)
                ->groupBy('bets.game_id')
                ->select('bets.id')->get());
            $users[$i]->win_rate = round($users[$i]->wins_count / $users[$i]->games_played, 3) * 100;
            $i++;
        }
        return view('pages.top', compact('users', 'place'));
    }

    public function user($userId)
    {
        $user = User::where('steamid64', $userId)->first();
        if (!is_null($user)) {
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
                if ($game->status != Game::STATUS_FINISHED) $gamesList[$i]->win = -1;
                if ($game->winner_id == $user->id) $gamesList[$i]->win = true;
                $gamesList[$i]->chance = round($game->betValue / $game->price, 3) * 100;
                $i++;
            }
            $username = $user->username;
            $steamid = $user->steamid64;
            $avatar = $user->avatar;
            $votes = $user->votes;
            $wins = $wins;
            $url = 'http://steamcommunity.com/profiles/' . $user->steamid64 . '/';
            $winrate = count($gamesPlayed) ? round($wins / count($gamesPlayed), 3) * 100 : 0;
            $totalBank = $games->sum('price');
            $games = count($gamesPlayed);
            $list = $gamesList;

            parent::setTitle($username . ' - ');
        } else {
            return redirect()->route('index');
        }

        return view('pages.user', compact('username', 'avatar', 'votes', 'wins', 'url', 'winrate', 'totalBank', 'games', 'list', 'steamid'));
    }

    public function settings()
    {
        return view('pages.settings');
    }

    public function myhistory()
    {
        parent::setTitle('Мои игры - ');

        $games = \DB::table('games')
            ->join('bets', function ($join) {
                $join->on('games.id', '=', 'bets.game_id')
                    ->where('bets.user_id', '=', $this->user->id);
            })
            ->join('users', 'games.winner_id', '=', 'users.id')
            ->groupBy('games.id')
            ->orderBy('games.id', 'desc')
            ->select('games.*', 'users.username as winner_username', 'users.steamid64 as winner_steamid64')
            ->simplePaginate(10);

        return view('pages.myhistory', compact('games'));
    }

    public function history()
    {
        parent::setTitle('История игр - ');

        $games = Game::with(['bets', 'winner'])->where('status', Game::STATUS_FINISHED)->orderBy('created_at', 'desc')->simplePaginate(10);
        return view('pages.history', compact('games'));
    }

    public function escrow()
    {
        parent::setTitle('ESCROW | ');

        return view('pages.escrow');
    }

    public function game($gameId)
    {
        if (isset($gameId) && Game::where('status', Game::STATUS_FINISHED)->where('id', $gameId)->count()) {
            $game = Game::with(['winner'])->where('status', Game::STATUS_FINISHED)->where('id', $gameId)->first();
            $game->ticket = floor($game->rand_number * ($game->price * 100));
            $bets = $game->bets()->with(['user', 'game'])->get()->sortByDesc('created_at');
            $chances = $this->sortByChance(json_decode(json_encode(GameController::_getChancesOfGame($game))));

            parent::setTitle('Игра #' . $gameId . ' - ');

            return view('pages.game', compact('game', 'bets', 'chances'));
        }
        return redirect()->route('index');
    }

    public static function sortByChance($chances)
    {
        usort($chances, function ($a, $b) {
            $a1 = $a->chance;
            $b1 = $b->chance;
            return $a1 < $b1;
        });

        return $chances;
    }

    public function myinventory(Request $request)
    {
        parent::setTitle('Мой инвентарь - ');

        if ($request->getMethod() == 'GET') {
            return view('pages.myinventory', compact('title'));
        } else {
            if (!\Cache::has('inventory_' . $this->user->steamid64)) {
                $jsonInventory = file_get_contents('http://steamcommunity.com/profiles/' . $this->user->steamid64 . '/inventory/json/730/2?l=russian');
                $items = json_decode($jsonInventory, true);
                if ($items['success']) {
                    foreach ($items['rgDescriptions'] as $class_instance => $item) {
                        $info = Item::where('market_hash_name', $item['market_hash_name'])->first();
                        if (is_null($info)) {
                            $info = new SteamItem($item);
                            if ($info->price != null) {
                                Item::create((array)$info);
                            }
                        }
                        $items['rgDescriptions'][$class_instance]['price'] = $info->price;
                    }

                }
                \Cache::put('inventory_' . $this->user->steamid64, $items, 15);
            } else {
                $items = \Cache::get('inventory_' . $this->user->steamid64);
            }
            return $items;
        }
    }
 public function pay(Request $request)
    {
        $amount = $request->get('sum');
        $user = $this->user;
        header('Location: https://api.gdonate.ru/pay?public_key='.\App\Http\Controllers\GameController::GDonateKeyPublic.'&sum='.$amount.'&account='.$user->id.'&desc=Пополнение баланса на '.\App\Http\Controllers\GameController::SITENAME.'');
        exit();
    }
}