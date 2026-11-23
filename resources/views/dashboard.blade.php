@extends('layouts.app')
@section('title', 'Dasbor')
@section('content')
<div class="p-5">
    <div class="grid grid-cols-4 gap-3">
        <div class="border border-slate-200 bg-white p-4">
            <div class="text-xs font-medium text-slate-500">Penjualan Hari Ini</div>
            <div class="mt-2 text-2xl font-semibold tabular-nums">Rp {{ number_format($dashboard['penjualan_hari_ini'], 0, ',', '.') }}</div>
            <div class="mt-1 text-xs {{ $dashboard['persen_perubahan'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $dashboard['persen_perubahan'] >= 0 ? '+' : '' }}{{ $dashboard['persen_perubahan'] }}% vs kemarin</div>
        </div>
        <div class="border border-slate-200 bg-white p-4">
            <div class="text-xs font-medium text-slate-500">Jumlah Transaksi</div>
            <div class="mt-2 text-2xl font-semibold tabular-nums">{{ $dashboard['transaksi_hari_ini'] }}</div>
            <div class="mt-1 text-xs text-slate-500">Rata-rata Rp {{ number_format($dashboard['rata_rata_transaksi'], 0, ',', '.') }}</div>
        </div>
        <div class="border border-slate-200 bg-white p-4">
            <div class="text-xs font-medium text-slate-500">Saldo Kas</div>
            <div class="mt-2 text-2xl font-semibold tabular-nums">Rp {{ number_format($dashboard['saldo_kas'], 0, ',', '.') }}</div>
        </div>
        <div class="border border-slate-200 bg-white p-4">
            <div class="text-xs font-medium text-slate-500">Utang Supplier</div>
            <div class="mt-2 text-2xl font-semibold tabular-nums">Rp {{ number_format($dashboard['total_utang'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-[7fr_5fr] gap-3">
        <div class="border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold">Penjualan 14 Hari Terakhir</h2>
            <canvas id="trenPenjualan" class="mt-3 h-56"></canvas>
        </div>
        <div class="border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold">Peringatan Stok Minimum</h2>
                <span class="border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs text-amber-800">{{ $dashboard['jumlah_perlu_restok'] }}</span>
            </div>
            <div class="mt-3 divide-y divide-slate-100">
                @forelse($dashboard['produk_perlu_restok'] as $produk)
                    <a href="{{ route('stok.kartu', $produk) }}" class="flex items-center justify-between py-2.5 text-sm">
                        <span>{{ $produk->nama }}</span>
                        <span class="font-semibold tabular-nums text-red-700">{{ $produk->stok }} / {{ $produk->stok_minimum }}</span>
                    </a>
                @empty
                    <div class="py-8 text-center text-sm text-slate-500">Semua stok aman</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-4 border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Produk Terlaris Bulan Ini</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr><th class="px-4 py-2 text-left">Produk</th><th class="px-4 py-2 text-right">Terjual</th><th class="px-4 py-2 text-right">Pendapatan</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($dashboard['produk_terlaris'] as $item)
                    <tr><td class="px-4 py-2.5">{{ $item->produk->nama }}</td><td class="px-4 py-2.5 text-right tabular-nums">{{ $item->total_qty }}</td><td class="px-4 py-2.5 text-right tabular-nums">Rp {{ number_format($item->total_omzet, 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">Belum ada penjualan pada periode ini</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const values = @json(array_values($dashboard['tren_14_hari']));
        const labels = @json(array_keys($dashboard['tren_14_hari']));
        new Chart(document.getElementById('trenPenjualan'), {
            type: 'line',
            data: { labels, datasets: [{ label: 'Penjualan', data: values, borderColor: '#1B5E7B', backgroundColor: '#E3EEF3', fill: true, tension: 0.25 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    });
</script>
@endsection