@extends('layout')

@section('content')
 <title>  {{ $title = \App\Http\Controllers\SendController::TITLE_UP }}</title>

	 <link href="{{ asset('assets/css/support.css') }}" rel="stylesheet">
	 <link href="{{ asset('assets/css/send.css') }}" rel="stylesheet">
	<script>	


  $(document).ready(function() {
		$(".newticket").click(function () {
   
		});
	});

</script>
<div class="content">
<div class="title-block">
            <h2 style="color: #ffffff;">
               Перевод средств
            </h2>
        </div>
		
	<div class="support">

<div style="overflow:hidden;     margin-left: 125px;">
			
	 <div class="page-main-block left" style="float: left;  margin-left: 50px;">
		<div class="page-block">
			<div class="btn-add-balance">
			<b style="font-weight:normal;font-size:17px;">Ваш Баланс</b><br>
			<b style="color:#ffff00" id="balance_id" align="center">{{ $u->money }} | Рублей</span></b><br>
			</div>
		</div>
	</div>
		 <div class="page-main-block left" style="float: left;  margin-left: 50px;">
		<div class="page-block">
			<div class="btn-add-balance">
			<b style="font-weight:normal;font-size:17px;">Ваш steamid64</b><br>
			<b style="color:#ffff00" id="balance_id" align="center" >{{ $u->steamid64 }}</span></b><br>
			</div>
		</div>
	</div>


</div>
		<form action="/gmoney" method="GET">
			<div class="gameamount">
				<input  type="text" name="steamid" style="margin-left: 180px;" cols="50" placeholder="steamid64" maxlength="18" autocomplete="off">
               <input  type="text" name="mone"  style=" margin-left: 180px;" cols="50" placeholder="money" maxlength="4" autocomplete="off">
           	 <input type="submit" style="margin-left: 180px;" name="submit" value="Send money">
			</div>
	<!--<input type="hidden" name="_token" value="fJ2eHdya9aNN7W6nacDROUjSY3DAtS0HRsk0rPpD"/>-->
	 <div class="about" style="
    margin-top: 50px;
	
">
<div class="other-title">ИНФОРМАЦИЯ</div>
<ol>
	<li>- Где взять stemid64? steamid.io</li>
	<li>- Будет взята коммисия {{ $ref = \App\Http\Controllers\SendController::COMISSION }} % </li>
	<li>- Mинимальная сумма перевода 10 руб</li>
</ol>

</div>

</form>



		 <div class="about" style="
    margin-top: 50px;
	
">
<div class="other-title">Последние переводы</div>
			<div class="table">
		       <div class="list">
				<div class="tb1">Кому отправил</div>
					<div class="tb2">Сколько</div>
				<div class="tb3">Кто отправил</div>
			</div>
			@forelse($perevod as $ticket)
		       <div class="list">
				<div class="tb1"><a href="/user/{{$ticket->money_id}}">{{$ticket->money_who}}</a></div>
				<div class="tb2">{{$ticket->money_amount}}</div>
				<div class="tb3"><a href="" >{{$ticket->money_from}}</a></div>
				 
			</div>
			@empty
			<br><center><h1 style="color: #FFF; font-weight: 300;">Запросы  отсутствуют!</h1></center>
			@endforelse
		</div>
	</div>

</div>


@endsection