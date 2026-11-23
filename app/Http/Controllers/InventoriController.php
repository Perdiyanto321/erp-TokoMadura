<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\View\View;

class InventoriController extends Controller
{
    public function index(Request $request): View
    {
        $produk = Produk::query()
            ->with('kategori')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim((string) $request->string('q'));
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('nama', 'like', "%{$q}%")
                        ->orWhere('kode', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return view('inventori.index', [
            'produk' => $produk,
            'kategori' => Kategori::orderBy('nama')->get(),
        ]);
    }

    public function kartuStok(Produk $produk): View
    {
        $produk->load(['kategori']);
        $gerakan = $produk->pergerakanStok()
            ->with('user')
            ->latest('id')
            ->paginate(30);

        return view('inventori.kartu-stok', [
            'produk' => $produk,
            'gerakan' => $gerakan,
        ]);
    }
}
