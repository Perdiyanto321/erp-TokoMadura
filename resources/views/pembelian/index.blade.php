@extends('layouts.app')
@section('title', 'Pembelian / PO')
@section('content')
<div class="p-5" x-data="{tab: 'restok'}">
    <div class="mb-4 flex gap-1 border-b border-slate-200">
        <button @click="tab='restok'" :class="tab==='restok' ? 'border-teal-700 text-teal-800' : 'border-transparent text-slate-500'" class="-mb-px border-b-2 px-4 py-2 text-sm">Rekomendasi Restok</button>
        <button @click="tab='po'" :class="tab==='po' ? 'border-teal-700 text-teal-800' : 'border-transparent text-slate-500'" class="-mb-px border-b-2 px-4 py-2 text-sm">Semua PO</button>
    </div>

    <section x-show="tab==='restok'" x-cloak>
        <div class="border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500"><tr><th class="px-3 py-2 text-left">Produk</th><th class="px-3 py-2 text-right">Stok</th><th class="px-3 py-2 text-right">Minimum</th><th class="px-3 py-2 text-right">Saran ({{ 'satuan beli' }})</th><th class="px-3 py-2 text-left">Supplier</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rekomendasi as $item)
                        <tr>
                            <td class="px-3 py-2.5"><span class="font-mono text-xs text-slate-500">{{ $item['kode'] }}</span><div>{{ $item['nama'] }}</div></td>
                            <td class="px-3 py-2.5 text-right font-semibold tabular-nums text-red-700">{{ $item['stok'] }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums">{{ $item['stok_minimum'] }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums">{{ $item['saran_satuan_beli'] }} {{ $item['satuan_beli'] }}</td>
                            <td class="px-3 py-2.5">{{ $item['supplier_nama'] }}</td>
                            <td class="px-3 py-2.5 text-right">
                                <form method="POST" action="{{ route('pembelian.store') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="supplier_id" value="{{ $item['supplier_id'] }}">
                                    <input type="hidden" name="jenis_bayar" value="tunai">
                                    <input type="hidden" name="items[0][produk_id]" value="{{ $item['produk_id'] }}">
                                    <input type="hidden" name="items[0][qty_pesan]" value="{{ max(1, $item['saran_satuan_beli']) }}">
                                    <input type="hidden" name="items[0][harga_beli]" value="{{ $item['harga_beli'] }}">
                                    <button class="border border-teal-700 px-2.5 py-1 text-xs text-teal-800">Buat Draf PO</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">Tidak ada produk yang perlu direstok</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section x-show="tab==='po'" x-cloak>
        <div class="mb-4 border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-sm font-semibold">Buat PO Manual</h2>
            <form method="POST" action="{{ route('pembelian.store') }}" class="grid grid-cols-4 items-end gap-3">
                @csrf
                <label class="text-xs text-slate-500">Supplier<select name="supplier_id" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm">@foreach($supplier as $item)<option value="{{ $item->id }}">{{ $item->nama }}</option>@endforeach</select></label>
                <label class="text-xs text-slate-500">Jenis Bayar<select name="jenis_bayar" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"><option value="tunai">Tunai</option><option value="kredit">Kredit</option></select></label>
                <label class="text-xs text-slate-500">Jatuh Tempo (kredit)<input type="date" name="jatuh_tempo" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"></label>
                <label class="text-xs text-slate-500">Produk<select name="items[0][produk_id]" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm">@foreach($produk as $item)<option value="{{ $item->id }}">{{ $item->nama }}</option>@endforeach</select></label>
                <label class="text-xs text-slate-500">Jumlah Pesan<input type="number" name="items[0][qty_pesan]" min="1" value="1" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"></label>
                <button class="border border-teal-700 bg-teal-700 px-4 py-2 text-sm text-white">Simpan Draf</button>
            </form>
        </div>

        <div class="space-y-3">
            @foreach($pembelian as $po)
                <article class="border border-slate-200 bg-white p-4">
                    <div class="flex items-start justify-between">
                        <div><div class="font-mono text-sm font-semibold">{{ $po->nomor_po }}</div><div class="mt-1 text-xs text-slate-500">{{ $po->supplier->nama }} · {{ $po->tanggal->format('d/m/Y') }} · {{ ucfirst($po->jenis_bayar) }}</div></div>
                        <div class="text-right"><div class="text-xs text-slate-500">Status · {{ $po->status }}</div><div class="mt-1 font-semibold tabular-nums">Rp {{ number_format($po->total, 0, ',', '.') }}</div></div>
                    </div>
                    @if(strtolower(auth()->user()->role->nama) === 'admin' && $po->status === 'draf')
                        <form method="POST" action="{{ route('pembelian.setujui', $po) }}" class="mt-3">@csrf<button class="border border-emerald-700 px-3 py-1.5 text-xs text-emerald-800">Setujui PO</button></form>
                    @endif
                    @if(in_array($po->status, ['disetujui','dikirim','diterima_sebagian']))
                        <form method="POST" action="{{ route('pembelian.terima', $po) }}" class="mt-3 border-t border-slate-100 pt-3">@csrf
                            <div class="mb-2 text-xs font-medium text-slate-500">Penerimaan Barang</div>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach($po->details as $detail)
                                    @php($diterima = $detail->detailPenerimaan->sum('qty_diterima'))
                                    <label class="text-xs text-slate-500">{{ $detail->produk->nama }} ({{ $diterima }}/{{ $detail->qty_pesan }} {{ $detail->produk->satuan_beli }})
                                        <input type="number" name="items[{{ $detail->id }}]" min="0" max="{{ $detail->qty_pesan - $diterima }}" class="mt-1 w-full border border-slate-300 px-2 py-1.5 text-sm tabular-nums">
                                    </label>
                                @endforeach
                            </div>
                            <button class="mt-3 border border-teal-700 px-3 py-1.5 text-xs text-teal-800">Catat Penerimaan</button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $pembelian->links() }}</div>
    </section>
</div>
@endsection
