<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $table = 'shop';

    public $timestamps = false;

    protected $fillable = ['name', 'inventoryId', 'classid', 'price', 'steam_price', 'rarity', 'quality', 'type'];

    const ITEM_STATUS_FOR_SALE = 0;
    const ITEM_STATUS_SOLD = 1;
    const ITEM_STATUS_NOT_FOUND = 2;
    const ITEM_STATUS_SEND = 3;
    const ITEM_STATUS_ERROR_TO_SEND = 4;
    const ITEM_STATUS_RETURNED = 5;


    public function buyer()
    {
        return $this->belongsTo('App\User', 'buyer_id', 'id');
    }

    public static function countItems()
    {
        return self::where('status', Shop::ITEM_STATUS_FOR_SALE)->count();
    }

    public static function countItem($classid){
        return self::where('status', Shop::ITEM_STATUS_FOR_SALE)->where('classid', $classid)->count();
    }

    public static function getClassRarity($type){
        switch ($type) {
            case 'Армейское качество':      return 'milspec'; break;
            case 'Запрещенное':             return 'restricted'; break;
            case 'Засекреченное':           return 'classified'; break;
            case 'Тайное':                  return 'covert'; break;
            case 'Ширпотреб':               return 'common'; break;
            case 'Промышленное качество':   return 'common'; break;
        }
    }
}
