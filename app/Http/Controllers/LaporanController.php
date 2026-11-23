<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LaporanService;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function index(Request $request, LaporanService $service): View
    {
        $dari = $request->string('dari')->toString() ?: date('Y-m-01');
        $sampai = $request->string('sampai')->toString() ?: date('Y-m-d');

        return view('laporan.index', [
            'laporan' => $service->labaRugi($dari, $sampai),
            'dari' => $dari,
            'sampai' => $sampai,
        ]);
    }

    public function csv(Request $request, LaporanService $service): StreamedResponse
    {
        $dari = $request->string('dari')->toString() ?: date('Y-m-01');
        $sampai = $request->string('sampai')->toString() ?: date('Y-m-d');
        $laporan = $service->labaRugi($dari, $sampai);

        return response()->streamDownload(function () use ($laporan) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Komponen', 'Nilai']);
            foreach ([
                'Penjualan Bersih' => $laporan['penjualan_bersih'],
                'HPP' => $laporan['hpp'],
                'Laba Kotor' => $laporan['laba_kotor'],
                'Biaya Operasional' => $laporan['biaya_operasional'],
                'Laba Bersih' => $laporan['laba_bersih'],
            ] as $label => $nilai) {
                fputcsv($handle, [$label, $nilai]);
            }
            fclose($handle);
        }, 'laporan-laba-rugi.csv');
    }
}
