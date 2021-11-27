@extends('layout')

@section('content')
    <div class="user-history-block bid-history">

        <div class="title-block">
            <h2>История ваших ставок</h2>
        </div>
        @forelse($games as $game)
            <div class="user-history-content" id="showMoreContainer">
                <div class="prize-container  @if($game->winner_id == $u->id) win @else fail @endif">
                    <div class="prize-head">
                        <div class="left-block">
                            <div class="prize-number">
                                <a href="/game/{{ $game->id }}">Игра <span>#{{ $game->id }}</span></a>
                                <a href="/game/{{ $game->id }}" class="round-history">История игры</a>
                            </div>
                            <div class="prize-info" style="margin-top: -2px;">
                                <div class="my-deposit-info">
                                    Вы внесли <span>{{ \App\Http\Controllers\GameController::_getUserItemsOfGame($game->winner_id, $game) }} <u>{{ trans_choice('lang.items', \App\Http\Controllers\GameController::_getUserItemsOfGame($game->winner_id, $game)) }}</u></span>
                                    на сумму
                                    <span>{{ \App\Http\Controllers\GameController::_getUserMoneyOfGame($game->winner_id, $game) }} <u>руб</u></span>
                                </div>
                                <div class="my-deposit-info" style="margin-top: 10px; margin-bottom: 10px;">
                                    Ваш шанс: <span>{{ \App\Http\Controllers\GameController::_getUserIDChanceOfGame($game->winner_id, $game) }}%</span>
                                </div>
                                <div class="my-deposit-info">
                                    Сумма джекпота:
                                    <span>{{ $game->price }} <u>руб</u></span>
                                </div>
                            </div>
                        </div>

                        <div class="right-block">
                            <div class="right-content">
                                @if($game->winner_id == $u->id)
                                    <h2>Победа</h2>
                                @else
                                    <h2>Проигрыш</h2>
                                @endif
                            </div>
                        </div>
                    </div>

                        <div class="prize-footer">
                            <h3 class="prize-title open" onclick="showGameItems(this, 49140)">Нажмите чтобы посмотреть внесенные предметы:</h3>
                            <div class="deposit-content" style="width: 100%;">
                            </div>
                        </div>

                    </div>

                </div>
        @empty
            <div class="user-history-content">
                <div class="deposit-txt-info">
                    Вы пока что не участвовали ни в одной игре
                </div>
            </div>
        @endforelse
    </div>
@endsection