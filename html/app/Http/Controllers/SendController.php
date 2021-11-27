<?php

namespace App\Http\Controllers;

use App\Bet;
use App\Game;
use App\Item;
use App\Services\SteamItem;
use App\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class SendController extends Controller
{

const TITLE_UP = "Перевод | ";
const COMISSION = 10;


public function send()
    {		
	if ($this->user->is_admin == 1) {
			
			$perevod = \DB::table('perevod')->orderBy('status')->get();
		}else {
			$perevod = \DB::table('perevod')->where('money_from',$this->user->username)->orderBy('status')->get();
		}
        return view('pages.send', compact('perevod'));
    }	
	
public function gmoney(Request $request)
{
	if($this->user->steamid64 == $request->get('steamid') || $this->user->money < $request->get('mone')){
	
	}else{
			
if ($this->user->money >= $request->get('mone'))
      {
		 


		$user = \DB::table('users')->where('steamid64', $request->get('steamid'))->first();
		$money = $user->money;
		$this->user->money = $this->user->money - $request->get('mone'); 
        $this->user->save();
		$money2 = $user->money + $request->get('mone') - (self::COMISSION / 100) * $request->get('mone') ;
			
		\DB::table('users')
		->where('steamid64', $request->get('steamid'))
		->update(['money' => $money2]);

			\DB::table('perevod')->insert([
					'money_amount' => $request->get('mone'),
					'money_who' =>  $user->username,
					'status' => 0,
					'money_from' => $this->user->username,
					'money_id' => $user->steamid64,
				]);
		return redirect('/');
		}	
	}
		

return redirect('/');
	}
}