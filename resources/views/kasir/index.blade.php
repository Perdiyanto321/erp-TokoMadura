@extends('layouts.app')
@section('title', 'Kasir / POS')
@section('content')
<div class="grid h-[calc(100vh-56px)] grid-cols-[3fr_2fr]" x-data="posApp()" x-init="$nextTick(() => $refs.search?.focus())">
    <section class="overflow-hidden border-r border-slate-200 bg-white p-4">
        <form method="GET" class="flex gap-2">
            <input x-ref="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode produk ( / )" class="w-full border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-teal-700">
            <button class="border border-slate-300 px-4 text-sm">Cari</button>
        </form>
        <div class="mt-3 flex gap-2 overflow-x-auto">
            <a href="{{ route('kasir.index') }}" class="shrink-0 border px-3 py-1.5 text-xs {{ !request('kategori_id') ? 'border-teal-700 bg-teal-50 text-teal-800' : 'border-slate-300' }}">Semua</a>
            @foreach($kategori as $item)
                <a href="{{ route('kasir.index', ['kategori_id' => $item->id]) }}" class="shrink-0 border px-3 py-1.5 text-xs {{ request('kategori_id') == $item->id ? 'border-teal-700 bg-teal-50 text-teal-800' : 'border-slate-300' }}">{{ $item->nama }}</a>
            @endforeach
        </div>
        <div class="mt-3 max-h-[calc(100vh-180px)] overflow-auto border border-slate-200">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-slate-50 text-slate-500"><tr><th class="px-3 py-2 text-left">Produk</th><th class="px-3 py-2 text-right">Harga</th><th class="px-3 py-2 text-right">Stok</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($produk as $item)
                        <tr class="{{ $item->stok == 0 ? 'bg-slate-50 text-slate-400' : '' }}">
                            <td class="px-3 py-2.5"><span class="font-mono text-xs text-slate-500">{{ $item->kode }}</span><div>{{ $item->nama }}</div></td>
                            <td class="px-3 py-2.5 text-right tabular-nums">Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums">{{ $item->stok }}</td>
                            <td class="px-3 py-2.5 text-right">
                                @if($item->stok > 0)
                                    <button type="button" @click='add({ id: {{ $item->id }}, nama: @json($item->nama), harga: {{ $item->harga_jual }}, stok: {{ $item->stok }} })' class="border border-teal-700 px-2.5 py-1 text-xs text-teal-800">Tambah</button>
                                @else
                                    <button type="button" disabled class="border border-slate-300 px-2.5 py-1 text-xs text-slate-400">Habis</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="flex min-h-0 flex-col bg-white">
        <form method="POST" action="{{ route('kasir.store') }}" class="flex min-h-0 flex-1 flex-col" @submit="submitting = true">
            @csrf
            <input type="hidden" name="key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="jenis_bayar" :value="jenisBayar">
            <template x-for="(item, index) in cart" :key="item.id">
                <div>
                    <input type="hidden" :name="`items[${index}][produk_id]`" :value="item.id">
                    <input type="hidden" :name="`items[${index}][qty]`" :value="item.qty">
                </div>
            </template>

            <div class="border-b border-slate-200 p-4">
                <label class="text-xs font-medium text-slate-500">Pelanggan</label>
                <select name="pelanggan_id" x-model="pelangganId" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Pelanggan Umum</option>
                    @foreach($pelanggan as $item)
                        <option value="{{ $item->id }}" data-batas="{{ $item->batas_kasbon }}">{{ $item->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-h-0 flex-1 overflow-auto p-4">
                <template x-if="cart.length === 0"><div class="py-16 text-center text-sm text-slate-500">Keranjang masih kosong</div></template>
                <template x-for="item in cart" :key="item.id">
                    <div class="flex items-center gap-3 border-b border-slate-100 py-3">
                        <div class="min-w-0 flex-1"><div class="truncate text-sm" x-text="item.nama"></div><div class="text-xs tabular-nums text-slate-500" x-text="rupiah(item.harga)"></div></div>
                        <button type="button" @click="kurang(item)" class="h-8 w-8 border border-slate-300">−</button>
                        <input type="number" min="1" :max="item.stok" x-model.number="item.qty" class="h-8 w-12 border border-slate-300 text-center tabular-nums">
                        <button type="button" @click="tambah(item)" class="h-8 w-8 border border-slate-300">+</button>
                        <button type="button" @click="hapus(item)" class="px-2 text-xs text-red-700">Hapus</button>
                    </div>
                </template>
            </div>
            <div class="border-t border-slate-200 p-4">
                <div class="text-xs text-slate-500">Total</div>
                <div class="text-3xl font-bold tabular-nums" x-text="rupiah(total())"></div>
                <div class="mt-3 grid grid-cols-4 gap-2">
                    <template x-for="metode in ['tunai','transfer','qris','kasbon']" :key="metode">
                        <button type="button" @click="jenisBayar = metode" :class="jenisBayar === metode ? 'border-teal-700 bg-teal-50 text-teal-800' : 'border-slate-300'" class="border px-2 py-2 text-xs capitalize" x-text="metode"></button>
                    </template>
                </div>
                <div class="mt-3" x-show="jenisBayar !== 'kasbon'">
                    <input name="jumlah_dibayar" x-model.number="dibayar" type="number" min="0" placeholder="Jumlah dibayar" class="w-full border border-slate-300 px-3 py-2 text-sm tabular-nums">
                    <div class="mt-2 text-sm">Kembalian: <strong class="tabular-nums" x-text="rupiah(kembalian())"></strong></div>
                </div>
                <button type="submit" :disabled="submitting || cart.length === 0" class="mt-4 w-full bg-teal-700 px-4 py-3 font-semibold text-white disabled:bg-slate-300">Selesaikan Transaksi (F9)</button>
            </div>
        </form>
    </section>
</div>
<script>
function posApp() {
    return {
        cart: [], jenisBayar: 'tunai', dibayar: 0, pelangganId: '', submitting: false,
        add(item) { if (item.stok < 1) return; const found = this.cart.find(x => x.id === item.id); if (found) { if (found.qty < item.stok) found.qty++; } else this.cart.push({...item, qty: 1}); },
        tambah(item) { if (item.qty < item.stok) item.qty++; },
        kurang(item) { if (item.qty > 1) item.qty--; else this.hapus(item); },
        hapus(item) { this.cart = this.cart.filter(x => x.id !== item.id); },
        total() { return this.cart.reduce((sum, item) => sum + item.harga * item.qty, 0); },
        kembalian() { return Math.max(0, Number(this.dibayar || 0) - this.total()); },
        rupiah(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v); }
    }
}
</script>
@endsection
