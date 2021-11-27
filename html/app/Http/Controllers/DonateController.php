<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Services\GDonate\GDonate;
use App\Services\GDonate\GDonateEvent;

class DonateController extends Controller
{

    public function GDonateDonate(Request $request)
    {
        $payment = new GDonate(
            new GDonateEvent(),
            $request
        );
        return $payment->getResult();
    }

}
