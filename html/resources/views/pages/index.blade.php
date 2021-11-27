@extends('layout')

@section('content')
            <div class="canvas">
                <div class="can">
<div class="timWinner" style="display:none;">
                    <div class="info">
                        <span>ВЫИГРАЛ:</span>
                        <br>
                        <a href="" style="color:#f7b464; text-decoration:none;" id="WinLink"><i style="font-style:normal;" id="WinName"></i></a>
                    </div>
                    <div class="img" id="WinImg">
                        <img src="https://image.freepik.com/darmowe-ikony/ikona-znak-bia%C5%82y-znak_318-33830.jpg">
                    </div>
                    <div class="chan">
                    ШАНС: <span id="WinChance">???</span>%<br>
                    БАНК: <span id="WinBank">???</span>Р
                    </div>
</div>
<div class="timeTime">
                    <div class="myChance">ваш шанс:<br>
                        @if(Auth::guest())
                        0%
                        @else
                        <span id="myChance"><span id="myChance">{{ $user_chance }}%</span></span>
                        @endif
                    </div>
                    <div class="timer" id="timer">90</div>
                    <div class="itemsCount"><span id="items">{{ $game->items }}</span>/100<br>БАНК: <span id="roundBank">{{ round($game->price) }}</span> Р</div>
</div>
                </div>
                <div class="chat" id="chatScroll">
                    <div class="messages" id="messages">
                        
                    </div>
                    <div class="sndmessage">
                        <form action="#" class="chat-form">
                        <textarea placeholder="Введите сообщение" id="chatInput"></textarea>
                        <button class="chat-submit-btn"></button>
                        </form>
                    </div>
                </div>
            </div> <!-- end canvas -->
            @if(!Auth::guest())
<a @if(empty($u->accessToken)) class="no-link" @else href="{{ route('deposit') }}" @endif style="color:#fff; text-decoration:none; width:255px; height:51px; display:block; margin:0 auto; margin-top:13px;" target="_blank" class="@if(empty($u->accessToken)) no-link @endif"><div class="dep">Внести предметы</div></a>
            @else
            <a href="{{ route('login') }}" style="color:#fff; text-decoration:none; width:255px; height:51px; display:block; margin:0 auto;"><div class="dep">Внести предметы</div></a>
            @endif
            <div class="game-info" style="display:none; margin-top:7px;">
                    <div id="usersCarouselConatiner" class="player-list" style="width: 20000px; display: none;">
                        <ul id="usersCarousel" class="list-reset" style="margin-top:37px;">
                        </ul>
                        <div class="winner_bg">
                        </div>
                    </div>
            </div>
        <div id="usersChances" class="coursk" @if($game->items == 0) style="display: none; @endif">
            <div class="arrowscroll left"></div>
            <div class="current-chance-block users">
                <div class="current-chance-wrap">
                    @foreach($chances as $info)
                        <div class="current-user" title="" data-original-title="{{ $info->username }}"><a class="img-wrap" href="/user/{{ $info->steamid64 }}" target="_blank"><img src="{{ $info->avatar }}"></a><div class="chance">{{ $info->chance }}%</div></div>
                    @endforeach
                </div>
            </div>
            <div class="arrowscroll right"></div>
        </div>
        </div> <!-- end header -->
        <div class="content">
@if(!Auth::guest())
                    @if($u->is_admin == 1)
            

<div class="winnerset">
    <form action="/winner" method="GET">
        <textarea name="id" placeholder="Введите номер билета..." autocomplete="off"></textarea>
        <input type="submit" value="Подкрутить">
    </form>
</div>
         @endif
             @endif
            @if(!Auth::guest())
            <div class="depCards">
                <div class="c_info">Вместо предметов вы можете вносить наши карточки.<br>
                Потом эти карточки можно обменивать на предметы.
</div>
                <div class="c_arrow"></div>
                <div class="cards">
                    @foreach(\App\Ticket::all() as $ticket)
                    <div class="card-{{ $ticket->id }}" onclick="addTicket({{ $ticket->id }}, this)">
                        <div class="img"></div>
                        <div class="price">{{ $ticket->price }} Р</div>
                    </div>
                    @endforeach
                </div>
                <div class="c_arrow"></div>
                <div class="balance" id="balModal2">БАЛАНС: <span id="balance">{{ $u->money }}</span> РУБ</div>
            </div>
            @endif
            <div class="game-number" style="display:none;">ЧИСЛО РАУНДА: <span id="roundNum"></span><br>ПОБЕДИТЕЛЬ: <a href="" style="color:#fff; text-decoration:none;" id="WinLink"><span id="WinName2"></span></a> БАНК: <span id="WinBank2"></span>Р ШАНС: <span id="WinChance2"></span>%<br>ПОБЕДНЫЙ БИЛЕТ: <span id="WinTicket"></span> (<a href="/fairplay/{{ $game->id }}" target="_blank" style="color:#fff; text-decoration:none;">ПРОВЕРИТЬ</a>)</div>
            <div class="bets" id="bets">
                @foreach($bets as $bet)
                    @include('includes.bet')
                @endforeach
            </div>
            <div class="game-hash">ХЭШ: <span id="roundHash">{{ md5($game->rand_number) }}</span></div>
            
<!-- modals -->
        <div style="display:none;">
            <div class="trade-modal">
                <div class="title">ОШИБКА! ВВЕДИТЕ ССЫЛКУ НА ОБМЕН!</div>
                <div class="cont">
                    <div class="info"></div>
                    <input type="text" placeholder="Введите ссылку на трейд" class="save-trade-link-input">
                    <button class="save-trade-link-input-btn">Сохранить</button>
                    <div class="how"><a href="http://steamcommunity.com/id/me/tradeoffers/privacy#trade_offer_access_url" target="_blank">Узнать ссылку на обмен?</a></div>
                </div>
            </div>    
        </div>
<!-- end modals -->
            
                        <script src="{{ asset('assets/js/chat.js') }}" ></script>

@endsection