@extends('layout')

@section('content')
        </div> <!-- end header -->
        <script src="{{ asset('assets/js/shower_strimers.js') }}" ></script>
        <div class="content">
        <div class="shop-hist">
            <div class="title">История покупок</div>
            <div class="info">
                <div class="id">id</div>
                <div class="date">Дата</div>
                <div class="name">Название предмета</div>
                <div class="qual">Качество</div>
                <div class="status">Статус</div>
                <div class="price">Цена</div>
            </div>
            <div class="hist-shop">
                @forelse($items as $item)
                <div class="hist-p">
                    <div class="id">{{ $item->id }}</div>
                    <div class="date">{{ $item->buy_at }}</div>
                    <div class="name">{{ $item->name }}</div>
                    <div class="qual">{{ $item->quality }}</div>
                    <div class="status">
                                        @if($item->status == \App\Shop::ITEM_STATUS_SOLD)
                                            Отправка предмета
                                        @elseif($item->status == \App\Shop::ITEM_STATUS_SEND)
                                            Предмет отправлен
                                        @elseif($item->status == \App\Shop::ITEM_STATUS_NOT_FOUND)
                                            Предмет не найден
                                        @elseif($item->status == \App\Shop::ITEM_STATUS_ERROR_TO_SEND)
                                            Ошибка отправки
                                        @endif
                    </div>
                    <div class="price">{{ $item->price }} Р</div>
                </div>
                @empty
                    <center style="width:100%; height:49px; line-height: 49px; text-align:center; background: url("../img/inventory_bg.png"); margin:1px auto;"></center>
                @endforelse
            </div>
        </div>
@endsection