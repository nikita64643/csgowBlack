                <div class="bet">
                    <div class="top_bet_bet">
                        <div class="center">
                            <div class="left">
                                <div class="avatar">
                                    <img src="{{ $bet->user->avatar }}">
                                </div>
                                <div class="name"><a href="/user/{{ $bet->user->steamid64 }}" style="text-decoration:none; color:#fff;" target="_blank">{{ $bet->user->username }}</a></div>
                                <span class="depCount">внес {{ $bet->itemsCount }} {{ trans_choice('lang.items', $bet->itemsCount) }}</span>
                            </div>
                            <div class="arrow"></div>
                            <div class="betSumm">На сумму: <span>{{ $bet->price }} Р</span></div>
                            <div class="arrow"></div>
                            <div class="betChance">Шанс: <span class="id-{{ $bet->user->steamid64 }}">{{ \App\Http\Controllers\GameController::_getUserChanceOfGame($bet->user, $bet->game) }}%</span></div>
                            <div class="arrow"></div>
                            <div class="ticket">
                            Билеты от <span>#{{ round($bet->from) }}</span> до <span>#{{ round($bet->to) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="center">
                        <div class="items">
                            @foreach(json_decode($bet->items) as $i)
                            <div class="item base @if(!isset($i->img)){{ $i->rarity }} @else card @endif" market_hash_name="" title="{{ $i->name }}" data-toggle="tooltip">
                                <div class="img">
                                    @if(!isset($i->img))
                                    <img src="https://steamcommunity-a.akamaihd.net/economy/image/class/{{ \App\Http\Controllers\GameController::APPID }}/{{ $i->classid }}/100fx100f">
                                    @else
                                    <img src="{{ asset($i->img) }}" style="margin-top:19px; width:47px; height:22px; margin-left:14px;">
                                    @endif
                                </div>
                                <div class="price">{{ $i->price }} Р</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>