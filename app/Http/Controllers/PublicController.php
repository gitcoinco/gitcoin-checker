<?php

namespace App\Http\Controllers;

use App\Models\ProjectDonation;
use App\Models\Round;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function home()
    {
        return redirect()->route('public.projects.home');
    }

    public function gmv()
    {
        $totalMatchPools = Round::selectRaw('YEAR(created_at) as year, SUM(funded_amount_in_usd) as fundedAmountInUSD, SUM(total_amount_donated_in_usd) as totalAmountDonatedInUSD')
            ->groupBy('year')
            ->get();


        $totalGMV = [];
        // Loop through the years and calculate the GMV
        foreach ($totalMatchPools as $totalMatchPool) {
            $year = $totalMatchPool->year;

            $totalGMV[$year] = $totalMatchPool->fundedAmountInUSD + $totalMatchPool->totalAmountDonatedInUSD;
        }



        return view('public.gmv', [
            'totalGMV' => $totalGMV,
        ]);
    }
}
