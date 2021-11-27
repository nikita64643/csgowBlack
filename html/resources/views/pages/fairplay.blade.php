@extends('layout')

@section('content')
        </div> <!-- end header -->
        <script src="{{ asset('assets/js/shower_strimers.js') }}" ></script>
        <script src="{{ asset('assets/js/md5.min.js') }}"></script>
        <script src="{{ asset('assets/js/check.js') }}"></script>
        <div class="content">
            <div class="fair_play">
<div class="title">Как это работает</div>
Наша система честной игры работает таким образом, что победитель определяется с помощью <b>Числа раунда</b>, которое случайным образом генерируется в начале игры.
<br>
<b>Число раунда</b> зашифровывается с помощью MD5, и этот хэш показывается в начале каждого раунда.
<br>
В конце раунда система показывает то самое расшифрованное <b>Число раунда</b>, которое было зашифровано в самом начале, и вы сможете проверить, что <b>Число раунда</b> не менялось на протяжении игры.
<br>
Число раунда умножается на общее количество билетов в раунде и таким образом выбирается победный билет. У кого из игроков будет данный победный билет, тот и окажется победителем. 
<br>
<br>
То есть принцип честной игры работает таким образом, что мы никак не можем знать сколько билетов будет на момент завершения раунда, а <b>Число раунда</b> для умножения дается в самом начале раунда.
<div class="title">Обозначения</div>
<b>Хэш</b> - MD5 хэш шифруется от следующей строки: <b>Число_раунда</b> , используется чтобы доказать честность игры.
<br>
<b>Число раунда</b> - Случайное дробное число от 0 до 1 (например: 0.8612523461234567)
<br>
<b>Билет</b> - За каждые внесенные 10 коп. вы получите 1 билет (1 рубль = 10 билетов).
<div class="title">Выбор победителя</div>
Каждый депозит переводится в билеты. Билеты сортируются по времени депозита. 
<br>
<br>
Номер победного билета считается по следующей формуле: <b style="text-decoration:underline;">floor(число билетов * число раунда) + 1 = победитель</b>
<br>
(функция floor возвращает ближайшее целое число, округляя переданное ей число в меньшую сторону). 
<br>
<br>
Игрок, у которого будет выбранный победный билет и окажется победителем в раунде.
<div class="title">Проверка</div>
Вы можете использовать этот инструмент, чтобы убедиться, что хэш соответствует <b>Числу раунда</b>, и вычислить номер победного билета.
<input type="text" placeholder="Хэш" class="input" style="margin-top:20px;" value="@if(!empty($game)) {{ md5($game->rand_number) }} @endif" id="roundHash">
<input type="text" placeholder="Число раунда" class="input" value="@if(!empty($game)) {{ $game->rand_number }}  @endif" id="roundRandom">
<input type="text" placeholder="Общее кол-во билетов в раунде" class="input" value="@if(!empty($game)) {{ $bankTotal = $game->price * \App\Http\Controllers\GameController::TICKETS_RATE }} @endif" id="totalbank">
<div class="submit" id="checkHash">Проверить</div><div id="checkResult" class="result">@if(!empty($game)) Хэш соответствует числу раунда. Победный билет: {{ floor($game->rand_number * $bankTotal = $game->price * \App\Http\Controllers\GameController::TICKETS_RATE) }} @else &nbsp; @endif</div>

            </div>
@endsection