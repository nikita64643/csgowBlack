<?php
$uid = $_GET['id'];
$merchant_id = '37101'; //merchant_id ID мазагина в free-kassa.ru http://free-kassa.ru/merchant/cabinet/help/
$merchant_key = 'ea51xlxr'; //Секретное слово http://free-kassa.ru/merchant/cabinet/profile/tech.php
if (isset($_GET['prepare_once'])) {
    $hash = md5($merchant_id.":".$_GET['oa'].":".$merchant_key.":".$_GET['l']);
    echo '<hash>'.$hash.'</hash>';
    exit;
}
?>

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
    $.get('createpay.php?prepare_once=1&l='+desc+'&oa='+sum, function(data) {
         var re_anwer = /<hash>([0-9a-z]+)<\/hash>/gi;
         $('#s').val(re_anwer.exec(data)[1]);
         $('#submit').removeAttr("disabled");
    });
}
</script>

				
				<div class="input-group">
					<form method=GET action="http://www.free-kassa.ru/merchant/cash.php">
					<input type="hidden" name="m" value="19023">
						<input type="text" placeholder="Введите сумму" name="oa" id="sum" id="oa" onchange="calculate()" onkeyup="calculate()" onfocusout="calculate()" onactivate="calculate()" ondeactivate="calculate()" value="<?=$_GET['oa']?>" />
					<input type="hidden" name="s" id="s" value="0">
					<input type="hidden" name="o" id="desc" value="<?=$uid?>" >
						<button type="submit" id="submit" class="btn-add-balance" disabled>Оплатить</button>
						
                </div>
				
				
				<!--<div class="widget__content filled">
								<form method=GET action="http://www.free-kassa.ru/merchant/cash.php">
								<input type="hidden" name="m" value="19923">
								<input type="text" placeholder="Сумма пополнения" class="inputbox" name="oa" id="sum" id="oa" onchange="calculate()" onkeyup="calculate()" onfocusout="calculate()" onactivate="calculate()" ondeactivate="calculate()" value="<?=$_GET['oa']?>" >
								<input type="hidden" name="s" id="s" value="0">
								<br>
								<input type="hidden" name="o" id="desc" value="<?=$uid?>" >
								<br>
								<input type="submit" id="submit" class="btn" style="width: auto;height: 25px;display: block;text-align: center;background: #C96A38;color: #FFF;font-size: 15px;border: 1px solid #C96A38;line-height: 25px;cursor: pointer;" value="Оплатить" disabled>
								<br>
							</form>
				</div>-->
				
				
				<!--<div id="freekassa">
					<input type="hidden" id="userid" value="76561198074521068">
					<input type="text" id="money" value="" placeholder="Введите сумму платежа" style="height: 25px; border: 0; color: #000;">
					<br />
					<br />
					<button type="button" class="paybutton" style="width: auto;height: 25px;display: block;text-align: center;background: #C96A38;color: #FFF;font-size: 15px;border: 1px solid #C96A38;line-height: 25px;cursor: pointer;">Оплатить</button>
					<br />
				</div>-->