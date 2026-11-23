@extends('layouts.app')
@section('title', 'Riwayat Penjualan')
@section('content')
<div class="p-5">
    <div class="border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-2 text-left">Nomor</th><th class="px-4 py-2 text-left">Tanggal</th><th class="px-4 py-2 text-left">Pembayaran</th><th class="px-4 py-2 text-right">Total</th><th class="px-4 py-2 text-left">Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($riwayat as $item)
                    <tr><td class="px-4 py-2.5 font-mono">{{ $item->nomor }}</td><td class="px-4 py-2.5">{{ $item->tanggal->format('d/m/Y') }}</td><td class="px-4 py-2.5 capitalize">{{ $item->jenis_bayar }}</td><td class="px-4 py-2.5 text-right tabular-nums">Rp {{ number_format($item->total, 0, ',', '.') }}</td><td class="px-4 py-2.5">{{ $item->status }}</td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada transaksi</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $riwayat->links() }}</div>
</div>
@endsection
