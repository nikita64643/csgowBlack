@extends('layout')

@section('content')
        </div> <!-- end header -->
        <script src="{{ asset('assets/js/shower_strimers.js') }}" ></script>
        <div class="content">
            <div class="history" style="margin-top:20px;">
@forelse($games as $game)
<div class="hgame">
<div class="c">   
    <div class="img">
        <img src="{{ $game->winner->avatar }}">
    </div>
    <div class="hgame_info">
        ИГРА <span>#{{ $game->id }}</span><br>
        ПОБЕДИТЕЛЬ: <span class="name">{{ $game->winner->username }}</span> ВЫИГРАЛ С ШАНСОМ <i>{{ \App\Http\Controllers\GameController::_getUserChanceOfGame($game->winner, $game) }}%</i><br>
        БАНК РАУНДА: <span>{{ $game->price }} РУБЛЕЙ</span>
    </div>
    <div class="status">
        @if($game->status_prize == \App\Game::STATUS_PRIZE_WAIT_TO_SENT)
        <div class="success">Отправка выигрыша</div>
        @elseif($game->status_prize == \App\Game::STATUS_PRIZE_SEND)
        <div class="success">выигрыш отправлен</div>
        @else
        <div class="success">ошибка при отправке</div>
        @endif
    </div>
</div>
</div>
@empty
<div class="no-game-on-hist" style="width:100%; height:50px; line-height:50px; text-align:center; color:#fff; font-size:14px;">Пока что не было ни одной игры</div>
@endforelse
            </div>
@endsection