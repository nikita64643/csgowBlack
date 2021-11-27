@extends('layout')

@section('content')
        </div> <!-- end header -->
        <script src="{{ asset('assets/js/shower_strimers.js') }}" ></script>
        <div class="content">
            <div class="game-number">ЧИСЛО РАУНДА: <span id="roundNum">{{ $game->rand_number }}</span><br>ПОБЕДИТЕЛЬ: <a href="/user/{{ $game->winner->steamid64 }}" style="color:#fff; text-decoration:none;" id="WinLink"><span id="WinName2">{{ $game->winner->username }}</span></a> БАНК: <span id="WinBank2">{{ round($game->price) }}</span>Р ШАНС: <span id="WinChance2">{{ \App\Http\Controllers\GameController::_getUserChanceOfGame($game->winner, $game) }}%</span><br>ПОБЕДНЫЙ БИЛЕТ: <span id="WinTicket">#{{ floor($game->rand_number * $bankTotal = $game->price * 10) }}</span> (<a href="/fairplay/{{ $game->id }}" target="_blank" style="color:#fff; text-decoration:none;">ПРОВЕРИТЬ</a>)</div>
            <div class="bets" id="bets">
                @foreach($bets as $bet)
                    @include('includes.bet')
                @endforeach
            </div>
            <div class="game-hash">ХЭШ: <span id="roundHash">{{ md5($game->rand_number) }}</span></div>
@endsection