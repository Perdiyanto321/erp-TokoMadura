@extends('layouts.app')
@section('title', 'Inventori')
@section('content')
<div class="p-5">
    <form method="GET" class="mb-3 flex gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Cari SKU atau nama produk" class="w-80 border border-slate-300 px-3 py-2 text-sm">
        <button class="border border-slate-300 px-4 text-sm">Cari</button>
    </form>
    <div class="border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="px-3 py-2 text-left">Kode</th><th class="px-3 py-2 text-left">Produk</th><th class="px-3 py-2 text-left">Konversi</th><th class="px-3 py-2 text-right">HPP</th><th class="px-3 py-2 text-right">Harga Jual</th><th class="px-3 py-2 text-right">Stok</th><th class="px-3 py-2 text-left">Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($produk as $item)
                    <tr>
                        <td class="px-3 py-2.5 font-mono text-xs">{{ $item->kode }}</td>
                        <td class="px-3 py-2.5">{{ $item->nama }}</td>
                        <td class="px-3 py-2.5 text-slate-600">1 {{ $item->satuan_beli }} = {{ $item->isi_per_satuan_beli }} {{ $item->satuan }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums">Rp {{ number_format($item->hpp_rata_rata, 0, ',', '.') }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums">Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                        <td class="px-3 py-2.5 text-right font-semibold tabular-nums {{ $item->stok <= $item->stok_minimum ? 'text-red-700' : '' }}">{{ $item->stok }}</td>
                        <td class="px-3 py-2.5"><span class="border px-2 py-0.5 text-xs {{ $item->status === 'aktif' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-300 text-slate-500' }}">{{ $item->status }}</span></td>
                        <td class="px-3 py-2.5 text-right"><a href="{{ route('stok.kartu', $item) }}" class="border border-slate-300 px-2 py-1 text-xs">Kartu Stok</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $produk->links() }}</div>
</div>
@endsection
