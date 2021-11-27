<!doctype html>
<html class="no-js ru" lang="ru">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title>{{ $title }}CS:GO</title>
    <meta name="keywords" content="csgo джекпот,csgo jackpot, рулетка csgo,fast рулетка,игры на скины csgo,игра на депозит," />
    <meta name="description" content="CS:GO - Умножь свои скины CS:GO" />

    <meta name="csrf-token" content="{!!  csrf_token()   !!}">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}"/>
<!--    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">-->
<!--    <link href="{{ asset('assets/css/loot.css') }}" rel="stylesheet">-->
<!--    <link href="{{ asset('assets/css/chat.css') }}" rel="stylesheet">-->
    <link href="{{ asset('assets/css/p4_design.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/perfect-scrollbar.css') }}" rel="stylesheet">
    <link href='//fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&subset=latin,cyrillic' rel='stylesheet' type='text/css' />
    <script src="{{ asset('assets/js/main.js') }}" ></script>
    <script src="{{ asset('assets/js/vendor.js') }}" ></script>
    <script src="{{ asset('assets/js/moment.min.js') }}" ></script>

    <script>
    @if(!Auth::guest())
		var avatar = '{{ $u->avatar }}';
        const USER_ID = '{{ $u->steamid64 }}';
    @else
        const USER_ID = 'null';
    @endif
        var START = true;
    </script>
</head>
    <body>
        <div class="header">
            <nav>
            <div class="center">
                <ul>
                    <li><a href="/">Главная</a></li>
                    <li><a href="{{ route('history') }}">История</a></li>
                    <li><a href="{{ route('top') }}">Топ игроков</a></li>
                    <li><a href="{{ route('fairplay') }}">Честная игра</a></li>
                    <li><a href="{{ route('support') }}">Поддержка</a></li>
                    <li><a href="{{ route('cards') }}">Магазин</a></li>
                    @if(Auth::guest())
                    @else
                    <li><a href="/user/{{ $u->steamid64 }}">Профиль</a></li>
                    <li><a href="{{ route('my-inventory') }}">Мой инвентарь</a></li>
                    @endif
                </ul>
                @if(Auth::guest())
                <a href="{{ route('login') }}">
                    <div class="steam">
                        <div class="text"></div>
                    </div>
                </a>
                @else
                <div class="profile">
                    <div class="ava">
                        <img src="{{ $u->avatar }}">
                    </div>
                    <div class="info">
                        <div class="name"><a href="/user/{{ $u->steamid64 }}">{{ $u->username }}</a></div>
                        <div class="balance" id="balModal">Ваш баланс: <span id="balance">{{ $u->money }}</span> Р</div>
                    </div>
                    <div class="exit">
                        <a href="{{ route('logout') }}"><div class="vihod"></div></a>
                    </div>
                </div>
                @endif
            </div>
            </nav><!-- end nav -->
            <div class="stats">
                <div class="center">
                    <div class="online">
                        <img src="/assets/img/online.png">
                        <div class="info">
                            <span id="online">0</span>
                            <br>
                            сейчас онлайн
                        </div>
                    </div>
                    <div class="lastgame">
                        <img src="/assets/img/lastgame.png">
                        <div class="info">
                            <span>{{ \App\Game::lastGame() }}</span>
                            <br>
                            последняя игра
                        </div>
                    </div>
                    <div class="games">
                        <img src="/assets/img/games.png">
                        <div class="info">
                            <span>{{ \App\Game::gamesToday() }}</span>
                            <br>
                            игр сегодня
                        </div>
                    </div>
                    <div class="bigbank">
                        <img src="/assets/img/bigbank.png">
                        <div class="info">
                            <span>{{ \App\Game::maxPrice() }} Р</span>
                            <br>
                            максимальный выигрыш
                        </div>
                    </div>
                </div>
            </div><!-- end stats -->
            <!-- Стримеры! /STRIM/STRIMS/Настройка/Стримы/Стрим -->
            <div id="strimers" class="strimers" style="display:none;">
                <div class="s_title">Наши партнеры</div>
                <div class="frst">
                    <iframe src="https://player.twitch.tv/?channel=mlg" frameborder="0" scrolling="no" height="506" width="900"></iframe>
                </div>
            </div>
            <!-- end streamers -->
            @yield('content')
            <div class="footer">
                <div class="copy">
                    Нашли ошибку? Пишите - <a href="https://vk.com/id275085100" target="_blank">Нам в VK</a><br>
                    <a href="">Обновить</a>
                </div>
                <div class="links">
                    <a href="https://vk.com/haxcsgo" target="_blank"><li class="vk"></li></a>
                </div>
            </div>
        </div>
        
        @if(!Auth::guest())
        <!-- modals -->
        
            <div style="display: none;">
                <div class="balance-modal" id="balance-modal">
                    <div class="title">Управление балансом</div>
                    <div class="cont">
                        <div class="info">На нашем сайте вы можете как Пополнять баланс, так и передавать его другим игрокам используя STEAMID64. Пополнить баланс можно через: GDonate</div>
                        <div class="myBal">Ваш баланс: {{ $u->money }} Руб.</div>
                        <div class="add">
                            <div class="title_m">Пополнить баланс</div>
<form action="../pay.php" method="POST" name="create_order"> 
<input placeholder="Введите сумму пополнения..." value="" type="text" name="money" style="height:30px;width:405px; text-align:center;"> 
<input value="{{ $u->steamid64 }}" type="text" name="user" hidden>
<button type="submit" name="create_order" class="paybutton" style="height:30px; border-radius:3px;">Пополнить</button> 
</form> 
                        </div>
                        <div class="send">
                            <div class="title_m">Передать баланс</div>
                            <form action="/gmoney" method="GET">
                            <input type="text" placeholder="steamid64" style="width:200px;" name="steamid" maxlenght="18" autocomplete="off">
                            <input type="text" placeholder="Сумма" style="width:200px;" name="mone" maxlength="4" autocomplete="off">
                            <button style="width:191px;" name="submit">передать</button>
                            <div class="informer">
                            - steamid64 - можно узнать в профиле игрока ("Steamid64 пользователя") <br>
                            - С каждого перевода мы взимаем комиссию {{ $ref = \App\Http\Controllers\SendController::COMISSION }} % <br>
                            - Минимальная сумма перевода - 10 рублей
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
        <!-- end modals -->
        @endif
    </body>

<script src="{{ asset('assets/js/app.js') }}" ></script>

<script>
    @if(!Auth::guest())
    function updateBalance() {
        $.post('{{route('get.balance')}}', function (data) {
            $('.userBalance').text(data);
        });
    }
    function addTicket(id, btn){
        $.post('{{route('add.ticket')}}',{id:id}, function(data){
            updateBalance();
            return $.notify(data.text, data.type);
        });
    }
    @endif

   
</script>
</html>
