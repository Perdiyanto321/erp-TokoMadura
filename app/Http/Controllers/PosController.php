<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\SimpanPenjualanRequest;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Services\PenjualanService;
use App\Exceptions\AksiTidakDiizinkan;
use App\Exceptions\BatasKasbonTerlampaui;
use App\Exceptions\PembayaranKurang;
use App\Exceptions\StokTidakCukup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $produk = Produk::query()
            ->with('kategori')
            ->aktif()
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim((string) $request->string('q'));
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('nama', 'like', "%{$q}%")
                        ->orWhere('kode', 'like', "%{$q}%");
                });
            })
            ->when($request->integer('kategori_id') > 0, fn ($query) => $query->where('kategori_id', $request->integer('kategori_id')))
            ->orderBy('nama')
            ->get();

        return view('kasir.index', [
            'produk' => $produk,
            'kategori' => Kategori::orderBy('nama')->get(),
            'pelanggan' => Pelanggan::where('status', 'aktif')->orderBy('nama')->get(),
        ]);
    }

    public function store(SimpanPenjualanRequest $request, PenjualanService $service): RedirectResponse
    {
        $data = $request->validated();
        $data['key'] = (string) ($data['key'] ?? Str::uuid());

        try {
            $penjualan = $service->simpan($data, $request->user());
            return redirect()->route('kasir.index')->with('penjualan', $penjualan->id);
        } catch (StokTidakCukup|PembayaranKurang|BatasKasbonTerlampaui|AksiTidakDiizinkan $exception) {
            return back()->withInput()->withErrors(['penjualan' => $exception->getMessage()]);
        }
    }

    public function riwayat(Request $request): View
    {
        $riwayat = Penjualan::with(['kasir', 'pelanggan'])
            ->where('kasir_id', $request->user()->id)
            ->latest('tanggal')
            ->latest('id')
            ->paginate(20);

        return view('kasir.riwayat', ['riwayat' => $riwayat]);
    }
}
