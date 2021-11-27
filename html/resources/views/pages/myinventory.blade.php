@extends('layout')

@section('content')
        </div> <!-- end header -->
        <script src="{{ asset('assets/js/shower_strimers.js') }}" ></script>
        <div class="content">
            <div class="inventory">
                <div class="info">
                    <span>Профиль Steam:</span> <a href="http://steamcommunity.com/profiles/{{ $u->steamid64 }}" target="_blank">steamcommunity.com/profiles/{{ $u->steamid64 }}</a>
                    <br>
                    <i>Стоимость моего инвентаря:</i> <b id="totalPrice">0.00</b> <i>Р</i>
                </div>
                <div class="title">мой инвентарь</div>
                <div class="info_scnd">
                    <div class="name">Название предмета</div>
                    <div class="price">Цена</div>
                </div>
                <div class="item-list-inv">
                </div>
            </div>
<script>
    $(function(){
        loadMyInventory()
    });
</script>
@endsection