@extends('layout')

@section('content')
        </div> <!-- end header -->
        <script src="{{ asset('assets/js/shower_strimers.js') }}" ></script>
        <div class="content">
            <div class="info">
                <div class="title">ТОП 20 ИГРОКОВ</div>
                <div class="info">
                    <div class="slot">Место</div>
                    <div class="profile">Профиль</div>
                    <div class="games">Сыграно игр</div>
                    <div class="winners">Побед</div>
                    <div class="winrate">Win Rate</div>
                    <div class="banks">Сумма</div>
                </div>
            </div>
            <div class="top">
                <div class="list">
                @foreach($users as $user)
                <div class="user_top" style="width:1050px; margin:1px auto;">
                    <div class="slot">{{ $place++ }}</div>
                    <div class="profile">
                        <div class="img">
                            <img src="{{ $user->avatar }}">
                        </div>
                        <div class="name"><a href="/user/{{ $user->steamid64 }}" style="text-decoration:none; color:#fff;">{{ $user->username }}</a></div>
                    </div>
                    <div class="games">{{ $user->games_played }}</div>
                    <div class="winners">{{ $user->wins_count }}</div>
                    <div class="winrate">{{ $user->win_rate }}<span>%</span></div>
                    <div class="banks">{{ round($user->top_value) }}<span>Р</span></div>
                </div>
                @endforeach
                </div>
            </div>
@endsection