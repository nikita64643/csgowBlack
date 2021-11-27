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

use Storage;

class GameController extends Controller
{
    const BOT_TRADE_LINK = 'https://steamcommunity.com/tradeoffer/new/?partner=219857529&token=gudpJ-Gh';

    const TICKETS_RATE = 10;
    const MIN_PRICE = 0;                    # Минимальная ставка
    const MAX_ITEMS = 20;                   # Максимальное кол-во предметов в ставке
    const COMMISSION = 10;                   # Комиссия
    const COMMISSION_FOR_FIRST_PLAYER = 10;   # Комиссия для первого игрока сделавшего ставку.
    const APPID = 730;                  # AppID игры: 570 - Dota2, 730 - CS:GO

    const URL_REQUEST = 'http://backpack.tf/api/IGetMarketPrices/v1/?key=';
    const API_KEY = '56005704ba8d88da148b46b7'; # Ключ для сайта backpack.tf

    const GDonateKeyPublic = 'bb405-2691';
    const GDonateKey = '67e2f34ecdcaba8def8b919c706631ba';

    const SEND_OFFERS_LIST = 'send.offers.list';
    const NEW_BET_CHANNEL = 'newDeposit';
    const BET_DECLINE_CHANNEL = 'depositDecline';
    const INFO_CHANNEL = 'msgChannel';
    const SHOW_WINNERS = 'show.winners';

    public $game;

    protected $lastTicket = 0;

    private static $chances_cache = [];

    public function __construct()
    {
        parent::__construct();
        $this->game = $this->getLastGame();
        $this->lastTicket = $this->redis->get('last.ticket');
    }

    public function deposit()
    {
        return redirect(self::BOT_TRADE_LINK);
    }

    public function getPriceItems()
    {
        $data = file_get_contents(self::URL_REQUEST . self::API_KEY . '&compress=1&appid=' . self::APPID);
        $response = json_decode($data);
        $success = $response->response->success;
        if ($success !== 0) {
            Storage::disk('local')->put('items.txt', $data);
            return 'Successfully Parsing';
        } else {
            $message = $response->response->message;
            return $message;
        }
    }

    public function currentGame()
    {
        $game = Game::orderBy('id', 'desc')->first();
        $bets = $game->bets()->with(['user', 'game'])->get()->sortByDesc('created_at');
        $user_chance = $this->_getUserChanceOfGame($this->user, $game);
        $chances = $chances = PagesController::sortByChance(json_decode(json_encode($this->_getChancesOfGame($game))));
        if (!is_null($this->user))
            $user_items = $this->user->itemsCountByGame($game);

        parent::setTitle($game->price . ' руб - ');

        return view('pages.index', compact('game', 'bets', 'user_chance', 'chances', 'user_items', 'cardsCount'));
    }

    public function getLastGame()
    {
        $game = Game::orderBy('id', 'desc')->first();
        if (is_null($game)) $game = $this->newGame();
        return $game;
    }

    public function getCurrentGame()
    {
        $this->game->winner;
        return $this->game;
    }

    public function getWinners()
    {
        $us = $this->game->users();

        $lastBet = Bet::where('game_id', $this->game->id)->orderBy('to', 'desc')->first();
        $winTicket = round($this->game->rand_number * $lastBet->to);

        $winningBet = Bet::where('game_id', $this->game->id)->where('from', '<=', $winTicket)->where('to', '>=', $winTicket)->first();

        $this->game->winner_id = $winningBet->user_id;
        $this->game->status = Game::STATUS_FINISHED;
        $this->game->finished_at = Carbon::now();
        $this->game->won_items = json_encode($this->sendItems($this->game->bets, $this->game->winner));
        $this->game->save();

        $returnValue = [
            'game' => $this->game,
            'winner' => $this->game->winner,
            'round_number' => $this->game->rand_number,
            'ticket' => $winTicket,
            'tickets' => $lastBet->to,
            'users' => $us,
            'date' => $this->game->updated_at->format('d.m.Y'),
            'date_hours' => $this->game->updated_at->format(' - H:i'),
            'chance' => $this->_getUserChanceOfGame($this->game->winner, $this->game)
        ];

        return response()->json($returnValue);
    }

    public function sendItems($bets, $user)
    {
        $itemsInfo = [];
        $items = [];
        $commission = self::COMMISSION;
        $commissionItems = [];
        $returnItems = [];
        $tempPrice = 0;
        $firstBet = Bet::where('game_id', $this->game->id)->orderBy('created_at', 'asc')->first();
        if ($firstBet->user == $user) $commission = self::COMMISSION_FOR_FIRST_PLAYER;
        $commissionPrice = round(($this->game->price / 100) * $commission);
        foreach ($bets as $bet) {
            $betItems = json_decode($bet->items, true);
            foreach ($betItems as $item) {
                //(Отдавать всю ставку игроку обратно)
                if ($bet->user == $user) {
                    $itemsInfo[] = $item;
                    if (isset($item['classid'])) {
                        $returnItems[] = $item['classid'];
                    } else {
                        $user->money = $user->money + $item['price'];
                    }
                } else {
                    $items[] = $item;
                }
            }
        }

        foreach ($items as $item) {
            if ($item['price'] < 1) $item['price'] = 1;
            if (($item['price'] <= $commissionPrice) && ($tempPrice < $commissionPrice)) {
                $commissionItems[] = $item;
                $tempPrice = $tempPrice + $item['price'];
            } else {
                $itemsInfo[] = $item;
                if (isset($item['classid'])) {
                    $returnItems[] = $item['classid'];
                } else {
                    $user->money = $user->money + $item['price'];
                }
            }
        }
        $user->save();

        $value = [
            'appId' => self::APPID,
            'steamid' => $user->steamid64,
            'accessToken' => $user->accessToken,
            'items' => $returnItems,
            'game' => $this->game->id
        ];

        $this->redis->rpush(self::SEND_OFFERS_LIST, json_encode($value));
        return $itemsInfo;
    }

    public function newGame()
    {
        $rand_number = "0." . mt_rand(100000000, 999999999) . mt_rand(100000000, 999999999);
        $game = Game::create(['rand_number' => $rand_number]);
        $game->hash = md5($game->rand_number);
        $game->rand_number = 0;
        $this->redis->set('current.game', $game->id);
        return $game;
    }

    public function checkOffer()
    {
        $data = $this->redis->lrange('check.list', 0, -1);
        foreach ($data as $offerJson) {
            $offer = json_decode($offerJson);
            $accountID = $offer->accountid;
            $items = json_decode($offer->items, true);
            $itemsCount = count($items);

            $user = User::where('steamid64', $accountID)->first();
            if (is_null($user)) {
                $this->redis->lrem('usersQueue.list', 1, $accountID);
                $this->redis->lrem('check.list', 0, $offerJson);
                $this->redis->rpush('decline.list', $offer->offerid);
                continue;
            } else {
                if (empty($user->accessToken)) {
                    $this->redis->lrem('usersQueue.list', 1, $accountID);
                    $this->redis->lrem('check.list', 0, $offerJson);
                    $this->redis->rpush('decline.list', $offer->offerid);
                    $this->_responseErrorToSite('Введите трейд ссылку!', $accountID, self::BET_DECLINE_CHANNEL);
                    continue;
                }
            }
            $totalItems = $user->itemsCountByGame($this->game);
            if ($itemsCount > self::MAX_ITEMS || $totalItems > self::MAX_ITEMS || ($itemsCount + $totalItems) > self::MAX_ITEMS) {
                $this->_responseErrorToSite('Максимальное кол-во предметов для депозита - ' . self::MAX_ITEMS, $accountID, self::BET_DECLINE_CHANNEL);
                $this->redis->lrem('usersQueue.list', 1, $accountID);
                $this->redis->lrem('check.list', 0, $offerJson);
                $this->redis->rpush('decline.list', $offer->offerid);
                continue;
            }

            $total_price = $this->_parseItems($items, $missing, $price);

            if ($missing) {
                $this->_responseErrorToSite('Принимаются только предметы из CS:GO', $accountID, self::BET_DECLINE_CHANNEL);
                $this->redis->lrem('usersQueue.list', 1, $accountID);
                $this->redis->lrem('check.list', 0, $offerJson);
                $this->redis->rpush('decline.list', $offer->offerid);
                continue;
            }

            if ($price) {
                $this->_responseErrorToSite('В вашем трейде есть предметы, цены которых мы не смогли определить', $accountID, self::BET_DECLINE_CHANNEL);
                $this->redis->lrem('usersQueue.list', 1, $accountID);
                $this->redis->lrem('check.list', 0, $offerJson);
                $this->redis->rpush('decline.list', $offer->offerid);
                continue;
            }

            if ($total_price < self::MIN_PRICE) {
                $this->_responseErrorToSite('Минимальная сумма депозита ' . self::MIN_PRICE . 'р.', $accountID, self::BET_DECLINE_CHANNEL);
                $this->redis->lrem('usersQueue.list', 1, $accountID);
                $this->redis->lrem('check.list', 0, $offerJson);
                $this->redis->rpush('decline.list', $offer->offerid);
                continue;
            }

            if (empty($items)) {
                $this->_responseErrorToSite('В вашем депозите не найдено предметов !', $accountID, self::BET_DECLINE_CHANNEL);
                $this->redis->lrem('usersQueue.list', 1, $accountID);
                $this->redis->lrem('check.list', 0, $offerJson);
                $this->redis->rpush('decline.list', $offer->offerid);
                continue;
            }

            $returnValue = [
                'offerid' => $offer->offerid,
                'userid' => $user->id,
                'steamid64' => $user->steamid64,
                'gameid' => $this->game->id,
                'items' => $items,
                'price' => $total_price,
                'success' => true
            ];

            if ($this->game->status == Game::STATUS_PRE_FINISH || $this->game->status == Game::STATUS_FINISHED) {
                $this->_responseMessageToSite('Ваша ставка пойдёт на следующую игру.', $accountID);
                $returnValue['gameid'] = $returnValue['gameid'] + 1;
            }

            $this->redis->rpush('checked.list', json_encode($returnValue));
            $this->redis->lrem('check.list', 0, $offerJson);
        }
        return response()->json(['success' => true]);
    }


    public function newBet()
    {
        $data = $this->redis->lrange('bets.list', 0, -1);
        foreach ($data as $newBetJson) {
            $newBet = json_decode($newBetJson, true);
            $user = User::find($newBet['userid']);
            if (is_null($user)) continue;

            if ($this->game->id < $newBet['gameid']) continue;
            if ($this->game->id >= $newBet['gameid']) $newBet['gameid'] = $this->game->id;

            if ($this->game->status == Game::STATUS_PRE_FINISH || $this->game->status == Game::STATUS_FINISHED) {
                $this->_responseMessageToSite('Ваша ставка пойдёт на следующую игру.', $user->steamid64);
                $this->redis->lrem('bets.list', 0, $newBetJson);
                $newBet['gameid'] = $newBet['gameid'] + 1;
                $this->redis->rpush('bets.list', json_encode($newBet));
                continue;
            }
            $ticketFrom = $this->lastTicket + 1;

            $ticketTo = $ticketFrom + ($newBet['price'] * self::TICKETS_RATE) - 1;
            $this->redis->set('last.ticket', $ticketTo);

            $bet = new Bet();
            $bet->user()->associate($user);
            $bet->items = json_encode($newBet['items']);
            $bet->itemsCount = count($newBet['items']);
            $bet->price = $newBet['price'];
            $bet->from = $ticketFrom;
            $bet->to = $ticketTo;
            $bet->game()->associate($this->game);
            $bet->save();

            $bets = Bet::where('game_id', $this->game->id);
            $this->game->items = $bets->sum('itemsCount');
            $this->game->price = $bets->sum('price');

            if (count($this->game->users()) >= 2 || $this->game->items >= 100) {
                $this->game->status = Game::STATUS_PLAYING;
                $this->game->started_at = Carbon::now();
            }

            if ($this->game->items >= 100) {
                $this->game->status = Game::STATUS_FINISHED;
                $this->redis->publish(self::SHOW_WINNERS, true);
            }

            $this->game->save();

            $chances = $this->_getChancesOfGame($this->game);
            $returnValue = [
                'betId' => $bet->id,
                'userId' => $user->steam64,
                'html' => view('includes.bet', compact('bet'))->render(),
                'itemsCount' => $this->game->items,
                'gamePrice' => $this->game->price,
                'gameStatus' => $this->game->status,
                'chances' => $chances
            ];
            $this->redis->publish(self::NEW_BET_CHANNEL, json_encode($returnValue));
            $this->redis->lrem('bets.list', 0, $newBetJson);
        }
        return $this->_responseSuccess();
    }

    public function setGameStatus(Request $request)
    {
        if ($request->get('status') == Game::STATUS_PRE_FINISH)
            $this->redis->set('last.ticket', 0);
        $this->game->status = $request->get('status');
        $this->game->save();
        return $this->game;
    }

    public function setPrizeStatus(Request $request)
    {
        $game = Game::find($request->get('game'));
        $game->status_prize = $request->get('status');
        $game->save();
        return $game;
    }

    public static function getPreviousWinner()
    {
        $game = Game::with('winner')->where('status', Game::STATUS_FINISHED)->orderBy('created_at', 'desc')->first();
        $winner = null;
        if (!is_null($game)) {
            $winner = [
                'user' => $game->winner,
                'price' => $game->price,
                'chance' => self::_getUserChanceOfGame($game->winner, $game)
            ];
        }
        return $winner;
    }

    public function getBalance()
    {
        return $this->user->money;
    }

    public function addTicket(Request $request)
    {

        if (\Cache::has('ticket.user.' . $this->user->id))
            return response()->json(['text' => 'Подождите...', 'type' => 'error']);
        \Cache::put('ticket.user.' . $this->user->id, '', 0.02);

        $totalItems = $this->user->itemsCountByGame($this->game);
        if ($totalItems > self::MAX_ITEMS || (1 + $totalItems) > self::MAX_ITEMS) {
            return response()->json(['text' => 'Максимальное кол-во предметов для депозита - ' . self::MAX_ITEMS, 'type' => 'error']);
        }

        if (!$request->has('id')) return response()->json(['text' => 'Ошибка. Попробуйте обновить страницу.', 'type' => 'error']);
        if ($this->game->status == Game::STATUS_PRE_FINISH || $this->game->status == Game::STATUS_FINISHED) return response()->json(['text' => 'Дождитесь следующей игры!', 'type' => 'error']);
        $id = $request->get('id');
        $ticket = Ticket::find($id);
        if (is_null($ticket)) return response()->json(['text' => 'Ошибка.', 'type' => 'error']);
        else {
            if ($this->user->money >= $ticket->price) {


                $ticketFrom = $this->lastTicket + 1;
                $ticketTo = $ticketFrom + ($ticket->price * self::TICKETS_RATE) - 1;
                $this->redis->set('last.ticket', $ticketTo);

                $bet = new Bet();
                $bet->user()->associate($this->user);
                $bet->items = json_encode([$ticket]);
                $bet->itemsCount = 1;
                $bet->price = $ticket->price;
                $bet->from = $ticketFrom;
                $bet->to = $ticketTo;
                $bet->game()->associate($this->game);
                $bet->save();

                $bets = Bet::where('game_id', $this->game->id);
                $this->game->items = $bets->sum('itemsCount');
                $this->game->price = $bets->sum('price');

                if (count($this->game->users()) >= 2) {
                    $this->game->status = Game::STATUS_PLAYING;
                    $this->game->started_at = Carbon::now();
                }

                if ($this->game->items >= 100) {
                    $this->game->status = Game::STATUS_FINISHED;
                    $this->redis->publish(self::SHOW_WINNERS, true);
                }

                $this->game->save();

                $this->user->money = $this->user->money - $ticket->price;
                $this->user->save();

                $chances = $this->_getChancesOfGame($this->game);

                $returnValue = [
                    'betId' => $bet->id,
                    'userId' => $this->user->steamid64,
                    'html' => view('includes.bet', compact('bet'))->render(),
                    'itemsCount' => $this->game->items,
                    'gamePrice' => $this->game->price,
                    'gameStatus' => $this->game->status,
                    'chances' => $chances
                ];
                $this->redis->publish(self::NEW_BET_CHANNEL, json_encode($returnValue));
                return response()->json(['text' => 'Действие выполнено.', 'type' => 'success']);
            } else {
                return response()->json(['text' => 'Недостаточно средств на балансе', 'type' => 'error']);
            }
        }
    }

    public static function _getChancesOfGame($game)
    {
        $chances = [];
        foreach ($game->users() as $user) {
            $chances[] = [
                'chance' => self::_getUserChanceOfGame($user, $game),
                'items' => User::find($user->id)->itemsCountByGame($game),
                'steamid64' => $user->steamid64,
                'username' => $user->username,
                'avatar' => $user->avatar
            ];
        }
        return $chances;
    }

    public static function _getUserChanceOfGame($user, $game)
    {
        $chance = 0;
        if (!is_null($user)) {
            $bet = Bet::where('game_id', $game->id)
                ->where('user_id', $user->id)
                ->sum('price');
            if ($bet)
                $chance = round($bet / $game->price, 3) * 100;
        }
        return $chance;
    }

    public static function _getUserIDChanceOfGame($user, $game)
    {
        $chance = 0;
        if (!is_null($user)) {
            $bet = Bet::where('game_id', $game->id)
                ->where('user_id', $user)
                ->sum('price');
            if ($bet)
                $chance = round($bet / $game->price, 3) * 100;
        }
        return $chance;
    }

    public static function _getUserItemsOfGame($user, $game)
    {
        if (!is_null($user)) {
            $items = Bet::where('game_id', $game->id)
                ->where('user_id', $user)
                ->sum('itemsCount');
            if ($items)
                return $items;
        }
    }

    public static function _getUserMoneyOfGame($user, $game)
    {
        if (!is_null($user)) {
            $money = Bet::where('game_id', $game->id)
                ->where('user_id', $user)
                ->sum('price');
            if ($money)
                return $money;
        }
    }

    private function _parseItems(&$items, &$missing = false, &$price = false)
    {
        $itemInfo = [];
        $total_price = 0;
        $i = 0;

        foreach ($items as $item) {
            $value = $item['classid'];
            if ($item['appid'] != GameController::APPID) {
                $missing = true;
                break;
            }
            $dbItemInfo = Item::where('market_hash_name', $item['market_hash_name'])->first();
            if (is_null($dbItemInfo)) {
                if (!isset($itemInfo[$item['classid']]))
                    $itemInfo[$value] = new SteamItem($item);

                $dbItemInfo = Item::create((array)$itemInfo[$item['classid']]);

                if (!$itemInfo[$value]->price) $price = true;
            } else {
                if ($dbItemInfo->updated_at->getTimestamp() < Carbon::now()->subHours(5)->getTimestamp()) {
                    $si = new SteamItem($item);
                    if (!$si->price) $price = true;
                    if (!$si->price) $price = true;
                    $dbItemInfo->price = $si->price;
                    $dbItemInfo->save();
                }
            }

            $itemInfo[$value] = $dbItemInfo;

            if (!isset($itemInfo[$value]))
                $itemInfo[$value] = new SteamItem($item);
            if (!$itemInfo[$value]->price) $price = true;
            if ($itemInfo[$value]->price < 1) $itemInfo[$value]->price = 1;          //Если цена меньше единицы, ставим единицу
            $total_price = $total_price + $itemInfo[$value]->price;
            $items[$i]['price'] = $itemInfo[$value]->price;
            unset($items[$i]['appid']);
            $i++;
        }
        return $total_price;
    }

    private function _responseErrorToSite($message, $user, $channel)
    {
        return $this->redis->publish($channel, json_encode([
            'user' => $user,
            'msg' => $message
        ]));
    }

    private function _responseMessageToSite($message, $user)
    {
        return $this->redis->publish(self::INFO_CHANNEL, json_encode([
            'user' => $user,
            'msg' => $message
        ]));
    }


    private function _responseSuccess()
    {
        return response()->json(['success' => true]);
    }

}
