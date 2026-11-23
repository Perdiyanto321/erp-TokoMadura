<?php

namespace App\Http\Controllers;

use App\Services\LaporanService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(LaporanService $laporanService): View
    {
        return view('dashboard', [
            'dashboard' => $laporanService->getDasborData(),
        ]);
    }
}