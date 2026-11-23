<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\BayarUtangRequest;
use App\Http\Requests\SimpanBiayaRequest;
use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Services\KeuanganService;
use App\Exceptions\AksiTidakDiizinkan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KeuanganController extends Controller
{
    public function index(KeuanganService $service): View
    {
        return view('keuangan.index', [
            'utang' => $service->getDaftarUtang(),
            'piutang' => $service->hitungTotalPiutang(),
            'rekapKas' => $service->rekapKas(),
            'pengeluaran' => Pengeluaran::latest('tanggal')->latest('id')->paginate(20),
        ]);
    }

    public function storeBiaya(SimpanBiayaRequest $request): RedirectResponse
    {
        Pengeluaran::create($request->validated() + ['user_id' => $request->user()->id]);
        return back()->with('status', 'Biaya operasional berhasil disimpan.');
    }

    public function bayarUtang(
        Pembelian $pembelian,
        BayarUtangRequest $request,
        KeuanganService $service
    ): RedirectResponse {
        try {
            $data = $request->validated();
            $service->bayarUtang(
                $pembelian,
                (float) $data['jumlah'],
                $data['metode'],
                $request->user(),
                $data['tanggal'] ?? null
            );
            return back()->with('status', 'Pembayaran utang berhasil dicatat.');
        } catch (AksiTidakDiizinkan $exception) {
            return back()->withErrors(['utang' => $exception->getMessage()]);
        }
    }
}
