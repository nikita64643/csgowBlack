<?php namespace App\Services;

use App\Http\Controllers\GameController;
use App\Http\Controllers\SteamController;
use Exception;
use Storage;

class SteamItem {

    const BANK_URL = 'http://www.cbr.ru/scripts/XML_daily.asp';

    public  $classid;
    public  $name;
    public  $market_hash_name;
    public  $price;
    public  $rarity;

    public function __construct($info)
    {
        $this->classid = $info['classid'];
        $this->name = $info['name'];
        $this->market_hash_name = $info['market_hash_name'];
        $this->rarity = isset($info['rarity']) ? $info['rarity'] : $this->getItemRarity($info);
        $this->price = $this->getItemPrice();
    }

    public function getItemPrice() {
        try{
            $json      = Storage::get('items.txt');
            $items     = json_decode($json);

            $usd       = $this->getActualCurs();
            $item_name = $this->market_hash_name;

            if($items->response->items->$item_name == 'undefined'){
                return false;
            }
            else{
                $item = $items->response->items->$item_name->value;
                $price_item = $item / 100 *$usd;

                return $price_item;
            }
        }catch(Exception $e){
            return false;
        }
    }

    public function getActualCurs() {
        $link = self::BANK_URL;
        $str  = file_get_contents($link);

        preg_match('#<Valute ID="R01235">.*?.<Value>(.*?)</Value>.*?</Valute>#is', $str, $value);

        $usd = $value[1];

        return $usd;
    }

    public function getItemRarity($info) {
        $type = $info['type'];
        $rarity = '';
        $arr = explode(',',$type);
        if (count($arr) == 2) $type = trim($arr[1]);
        if (count($arr) == 3) $type = trim($arr[2]);
        if (count($arr) && $arr[0] == 'Нож') $type = '★';
        switch ($type) {
            case 'Армейское качество':      $rarity = 'milspec'; break;
            case 'Запрещенное':             $rarity = 'restricted'; break;
            case 'Засекреченное':           $rarity = 'classified'; break;
            case 'Тайное':                  $rarity = 'covert'; break;
            case 'Ширпотреб':               $rarity = 'common'; break;
            case 'Промышленное качество':   $rarity = 'common'; break;
            case '★':                       $rarity = 'rare'; break;
        }

        return $rarity;
    }

    private function _setToFalse()
    {
        $this->name = false;
        $this->price = false;
        $this->rarity = false;
    }
}