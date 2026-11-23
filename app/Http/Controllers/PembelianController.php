<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\BuatPoRequest;
use App\Http\Requests\TerimaBarangRequest;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\Supplier;
use App\Services\PembelianService;
use App\Services\RestokService;
use App\Exceptions\AksiTidakDiizinkan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PembelianController extends Controller
{
    public function index(RestokService $restokService): View
    {
        return view('pembelian.index', [
            'rekomendasi' => $restokService->getRekomendasi(),
            'supplier' => Supplier::where('status', 'aktif')->orderBy('nama')->get(),
            'produk' => Produk::where('status', 'aktif')->orderBy('nama')->get(),
            'pembelian' => Pembelian::with(['supplier', 'details.produk', 'penerimaan'])
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function store(BuatPoRequest $request, PembelianService $service): RedirectResponse
    {
        try {
            $po = $service->buatDraf($request->validated(), $request->user());
            return redirect()->route('pembelian.index')->with('status', "PO {$po->nomor_po} berhasil dibuat.");
        } catch (AksiTidakDiizinkan $exception) {
            return back()->withInput()->withErrors(['pembelian' => $exception->getMessage()]);
        }
    }

    public function setujui(Pembelian $pembelian, PembelianService $service, Request $request): RedirectResponse
    {
        try {
            $po = $service->setujui($pembelian, $request->user());
            return back()->with('status', "PO {$po->nomor_po} disetujui.");
        } catch (AksiTidakDiizinkan $exception) {
            return back()->withErrors(['pembelian' => $exception->getMessage()]);
        }
    }

    public function terima(
        Pembelian $pembelian,
        TerimaBarangRequest $request,
        PembelianService $service
    ): RedirectResponse {
        try {
            $penerimaan = $service->terima(
                $pembelian,
                $request->validated('items'),
                $request->user(),
                $request->validated('tanggal')
            );
            return back()->with('status', "Penerimaan {$penerimaan->nomor} berhasil dicatat.");
        } catch (AksiTidakDiizinkan $exception) {
            return back()->withErrors(['pembelian' => $exception->getMessage()]);
        }
    }
}
