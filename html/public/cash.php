<?php
$uid = $_GET['id'];
$merchant_id = '37101'; //merchant_id ID мазагина в free-kassa.ru http://free-kassa.ru/merchant/cabinet/help/
$merchant_key = 'ea51xlxr'; //Секретное слово http://free-kassa.ru/merchant/cabinet/profile/tech.php
if (isset($_GET['prepare_once'])) {
    $hash = md5($merchant_id.":".$_GET['oa'].":".$merchant_key.":".$_GET['l']);
    echo '<hash>'.$hash.'</hash>';
    exit;
}
$sign = md5($merchant_id.':'.$_GET['oa'].':'.$merchant_key.':'.$_GET['l']);
?>
<!doctype html>
<html class="no-js" lang="ru">
<body>
<head>
    <meta charset="utf-8" />
<script src="http://yandex.st/jquery/1.6.0/jquery.min.js"></script>
<script type="text/javascript">
var min = 1;
function calculate() {
    var re = /[^0-9\.]/gi;
    var url = window.location.href;
    var desc = $('#desc').val();
    var sum = $('#sum').val();
    if (re.test(sum)) {
        sum = sum.replace(re, '');
        $('#oa').val(sum);
    }
    if (sum < min) {
        $('#error').html('Сумма должна быть больше '+min);
        $('#submit').attr("disabled", "disabled");
        return false;
    } else {
        $('#error').html('');
    }
    if (desc.length < 1) {
        $('#error').html('Необходимо ввести номер заявки');
        return false;
    }
    $.get('cash.php?prepare_once=1&l='+desc+'&oa='+sum, function(data) {
         var re_anwer = /<hash>([0-9a-z]+)<\/hash>/gi;
         $('#s').val(re_anwer.exec(data)[1]);
         $('#submit').removeAttr("disabled");
    });
}
</script>
</head>
    <div style="width: 550px; color: #0000; background: #91F7B7; border: 3px #CCCCCC 
             solid; -moz-border-radius: 10px; -webkit-border-radius: 10px; -khtml-border-radius: 10px; 
             border-radius: 10px; padding: 5px; margin: auto;">
<h2>Оплата через <a href="http://wwww.free-kassa.ru">free-kassa.ru</a></h2>
<div id="error"></div>
<form method='get' action='http://www.free-kassa.ru/merchant/cash.php'>
    <input type="hidden" name="m" value="19023">
    <input type="text" name="oa" id="sum" id="oa" onchange="calculate()" onkeyup="calculate()" onfocusout="calculate()" onactivate="calculate()" ondeactivate="calculate()"> Введите сумму для оплаты
    <input type="hidden" name="s" id="s" value="0">
    <br>
    <input type="hidden" name="o" id="desc" value="<?=$uid?>" >
    <br>
    <input type="submit" id="submit" value="Оплатить">
</form>
    </div>
</body>