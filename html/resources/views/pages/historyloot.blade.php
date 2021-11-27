@extends('layout')

<div class="user-history-block">
    @section('content')
        <div class="title-block">
            <h2>История лутов</h2>
        </div>
        <div class="user-history-content">
            @forelse($loots as $loot)
                <div class="prize-container">
                    <div class="prize-head">
                        <div class="left-block">
                            <div class="prize-number">
                                <a href="/loot/{{ $loot->id }}">Игра <span>#{{ $loot->id }}</span></a>
                                <a href="/loot/{{ $loot->id }}" class="round-history">История игры</a>
                            </div>
                            <div class="prize-info">
                                <div class="winner-name">
                                    <span class="chance chance-two">Номер билета <span>{{ $loot->winner_id }}</span></span>
                                    Победил:
                                    <div class="img-wrap"><img src="{{ $loot->winner_avatar }}"/>
                                    </div>
                                    <a href="/user/{{ $loot->winner_steamid64 }}"
                                       class="user-name">{{ $loot->winner_username }}</a>
                                </div>
                                <div class="round-sum">
                                    Выигрыш:
                                    <span>{{ $loot->name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="right-block" style="background: none; width: 220px;">
                            <div class="publ right-content">
                                @if($loot->status_prize == \App\Game::STATUS_PRIZE_WAIT_TO_SENT)
                                    <span class="prize-status status-waiting">Отправка выигрыша</span>
                                @elseif($loot->status_prize == \App\Game::STATUS_PRIZE_SEND)
                                    <span class="prize-status status-success">Выигрыш отправлен</span>
                                @else
                                    <div class="prize-status status-error">Ошибка отправки выигрыша</div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            @empty
                <div class="deposit-txt-info">
                    Пока что не было ни одной игры
                </div>
            @endforelse
        </div>

        <div class="msg-wrap">
            <div class="icon-inform-white"></div>
            <div class="msg-white msg-mini">
                На этой страницы показаны последние <span>20 игр.</span> Вы можете сами посмотреть историю любой игры,
                вписав ее номер в конец ссылки
                <span class="color-lightblue-t"><span class="weight-normal">fastvictory.ru/loot/</span>номер игры</span>
            </div>
        </div>
@endsection