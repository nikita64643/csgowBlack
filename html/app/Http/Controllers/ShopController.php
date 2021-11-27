<?php

namespace App\Http\Controllers;

use App\Item;
use App\Services\SteamItem;
use App\Shop;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class ShopController extends Controller
{
    const NEW_ITEMS_CHANNEL = 'items.to.sale';
    const GIVE_ITEMS_CHANNEL = 'items.to.give';

    const PRICE_PERCENT_TO_SALE = 100;   // Процент от цены steam

    public function index()
    {
        parent::setTitle('Магазин | ');

        $items = Shop::where('status', Shop::ITEM_STATUS_FOR_SALE)
            ->orderBy('price', 'desc')
            ->groupBy('classid')
            ->get();

        return view('pages.shop.index', compact('items'));
    }

    public function history()
    {
        parent::setTitle('История покупок | ');

        $items = Shop::where('buyer_id', $this->user->id)->orderBy('buy_at', 'desc')->get();
        return view('pages.shop.history', compact('items'));
    }

    public function setItemStatus(Request $request)
    {
        $item = Shop::find($request->get('id'));
        if(!is_null($item)){
            $item->status = $request->get('status');
            $item->save();
            return $item;
        }
        return response()->json(['success' => false]);
    }

    public function addItemsToSale()
    {
        $jsonItems = $this->redis->lrange(self::NEW_ITEMS_CHANNEL, 0, -1);
        foreach($jsonItems as $jsonItem){
            $items = json_decode($jsonItem, true);
            foreach($items as $item) {
                $dbItemInfo = Item::where('market_hash_name', $item['market_hash_name'])->first();
                if (is_null($dbItemInfo)) {
                    $itemInfo = new SteamItem($item);
                    $item['steam_price'] = $itemInfo->price;
                    $item['price'] = round($item['steam_price']/100 * self::PRICE_PERCENT_TO_SALE);
                    Shop::create($item);
                }else{
                    $item['steam_price'] = $dbItemInfo->price;
                    $item['price'] = round($item['steam_price']/100 * self::PRICE_PERCENT_TO_SALE);
                    Shop::create($item);
                }
            }
            $this->redis->lrem(self::NEW_ITEMS_CHANNEL, 1, $jsonItem);
        }
        return response()->json(['success' => true]);
    }

    public function buyItem(Request $request)
    {
        $item = Shop::find($request->get('id'));
        if(!is_null($item)){
            if($item->status == Shop::ITEM_STATUS_SOLD) return response()->json(['success' => false, 'msg' => 'Предмет уже куплен!']);
            if($this->user->money >= $item->price){
                $item->status = Shop::ITEM_STATUS_SOLD;
                $item->buyer_id = $this->user->id;
                $item->buy_at = Carbon::now();
                $item->save();
                $this->sendItem($item);
                $this->user->money = $this->user->money - $item->price;
                $this->user->save();
                return response()->json(['success' => true, 'msg' => 'Вы успешно купили предмет! Вы получите его в течении 5 минут.']);
            }else{
                return response()->json(['success' => false, 'msg' => 'У вас недостаточно средств для покупки.']);
            }
        }else{
            return response()->json(['success' => false, 'msg' => 'Ошибка! Предмет не найден!']);
        }
    }

    public function sendItem($item)
    {
        $value = [
            'id' => $item->id,
            'itemId' => $item->inventoryId,
            'partnerSteamId' => $this->user->steamid64,
            'accessToken' => $this->user->accessToken,
        ];

        $this->redis->rpush(self::GIVE_ITEMS_CHANNEL, json_encode($value));
    }

}
