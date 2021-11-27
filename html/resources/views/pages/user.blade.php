@extends('layout')

@section('content')
        </div> <!-- end header -->
        <script src="{{ asset('assets/js/shower_strimers.js') }}" ></script>
        <div class="content">
            <div class="user">
                <div class="top">
                    <div class="img">
                        <img src="{{ $avatar }}">
                    </div>
                    <div class="right-info">
                        <div class="name">{{ $username }}</div>
                        <div class="user-info">
                            <div class="gameinfo-hist">
                                <div class="games">
                                Игр: <span>{{ $games }}</span>
                                </div>
                                <div class="winners">
                                Побед: <span>{{ $wins }}</span>
                                </div>
                                <div class="winrate">
                                Средний шанс победы: <span>{{ $winrate }}%</span>
                                </div>
                                <div class="allbanks">
                                Сумма банков: <span>{{ $totalBank }}</span> Р
                                </div>
                            </div>
                            <div class="steamid64">SteamId64 пользователя: <span>{{ $steamid }}</span></div>
                            <div class="steam-link">Профиль steam: <a href="{{ $url }}" target="_blank">{{ $url }}</a></div>
                        </div>
                    </div>
                </div>
                <div class="game-hist">
                    <div class="title">
                    @if(!empty($u) && $u->steamid64 == $steamid)
                        Мои игры
                    @else
                        Игры {{ $username }}    
                    @endif
                    </div>
                    <div class="info">
                        <div class="number">
                        Номер игры
                        </div>
                        <div class="chance">
                        Шанс
                        </div>
                        <div class="bank">
                        Банк игры
                        </div>
                        <div class="status">
                        Статус
                        </div>
                        <div class="show">
                        Просмотр
                        </div>
                    </div>
                    <div class="hist">
                        @foreach($list as $game)
                        <div class="game-hist-list">
                            <div class="number">#{{ $game -> id }}</div>
                            <div class="chance">{{ $game -> chance }}%</div>
                            <div class="bank">{{ $game -> bank }} Р</div>
                            <div class="status">
                                @if($game->win == 1)
                                <div class="success">выиграл</div>
                                @elseif($game->win == -1)
                                <div class="success">не завершена</div>
                                @else
                                <div class="error">проиграл</div>
                                @endif
                            </div>
                            <div class="show">
                                <a href="/game/{{ $game -> id }}" target="_blank">Посмотреть игру</a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
@endsection