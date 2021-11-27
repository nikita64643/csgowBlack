<?php

error_reporting(0);

include 'config.php';
include 'lib/GDonateModel.php';
include 'lib/GDonate.php';

class GDonateEvent
{
    public function check($params)
    {    
         $GDonateModel = GDonateModel::getInstance();         
         
         if ($GDonateModel->getAccountByName($params['account']))
         {
            return true;      
         }  
         return 'Character not found';
    }

    public function pay($params)
    {
         $GDonateModel = GDonateModel::getInstance();
         $countItems = floor($params['sum'] / Config::ITEM_PRICE);
         $GDonateModel->donateForAccount($params['account'], $countItems);
    }
}

$payment = new GDonate(
    new GDonateEvent()
);

echo $payment->getResult();
