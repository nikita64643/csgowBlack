<?php
$merchant_id = '19023';
$merchant_secret = 'lwabgldi';



// function getIP() {
// if(isset($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
   // return $_SERVER['REMOTE_ADDR'];
// }
// if (!in_array(getIP(), array('5.9.72.245', '5.9.72.243', '5.9.29.230', '5.9.227.163'))) {
    // die("hacking attempt!");
// }

$sign = md5($merchant_id.':'.$_REQUEST['AMOUNT'].':'.$merchant_secret.':'.$_REQUEST['MERCHANT_ORDER_ID']);

$summ = $_REQUEST['AMOUNT'];
$uid = $_REQUEST['MERCHANT_ORDER_ID'];

$link = mysqli_connect("localhost", "kalash", "kalash", "nazik113");
if (!$link) {
   printf("Невозможно подключиться к базе данных. Код ошибки: %s\n", mysqli_connect_error());
   exit;
} 
$query = "UPDATE `users` SET money = money +".$summ." WHERE `id`=".$uid."";

mysqli_query($link,$query);
//Так же, рекомендуется добавить проверку на сумму платежа и не была ли эта заявка уже оплачена или отменена
//Оплата прошла успешно, можно проводить операцию.
die('YES');