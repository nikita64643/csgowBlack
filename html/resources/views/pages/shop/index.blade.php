@extends('layout')

@section('content')
        </div> <!-- end header -->
        <script src="{{ asset('assets/js/shower_strimers.js') }}" ></script>
        <script src="{{ asset('assets/js/shop.js') }}"></script>
        <div class="content">
            <div class="shop">
                <div class="title">Магазин / <a href="{{ route('cards-history') }}" style="color:#fff;">История покупок</a></div>
                <div class="items">
                    @forelse($items as $item)
                    <div class="item" onclick="buy({{ $item->id }});updateBalance()">
                        <div class="status">@if($item->quality == "Прямо с завода") (FN) @elseif ($item->quality == "Немного поношенное") (MW) @elseif ($item->quality == "После полевых испытаний") (FT) @elseif($item->quality == "Поношенное") (WW) @elseif($item->quality == "Закаленное в боях") (BS) @elseif($item->quality == "null") @endif</div>
                        <div class="kolvo">(x{{  \App\Shop::countItem($item->classid) }})</div>
                        <div class="img">
                            <img src="https://steamcommunity-a.akamaihd.net/economy/image/class/{{ \App\Http\Controllers\GameController::APPID }}/{{ $item->classid }}/200fx200f">
                        </div>
                        <div class="price">({{ floor($item->price) }} Р)</div>
                        <div class="name">{{ $item->name }}</div>
                    </div>
                    @empty
                    <center style="height:49px; line-height:49px ; font-size:13px;">Подождите немного. В данный момент идет обновления вещей.</center>
                    @endforelse
                </div>
            </div>
            
        <script>
            function buy(id) {
                $.ajax({
                    url: '/shop/buy',
                    type: 'POST',
                    dataType: 'json',
                    data: {id: id},
                    success: function (data) {
                        if (data.success) {

                            $.notify(data.msg, {className: "success"});
                            setTimeout(function () {
                                that.parent().parent().parent().hide()

                            }, 5500);
                        }
                        else {
                            if (data.msg) $.notify(data.msg, {className: "error"});
                        }
                    },
                    error: function () {
                        that.notify("Произошла ошибка. Попробуйте еще раз", {
                            className: "error"
                        });
                    }
                });
                return false;
            }


            function updateBalance() {
                $.post('/getBalance', function (data) {
                    $('#balance').text(data);
                });
            }
        </script>
@endsection