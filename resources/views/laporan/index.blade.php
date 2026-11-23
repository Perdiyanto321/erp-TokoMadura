@extends('layouts.app')
@section('title', 'Laporan Laba Rugi')
@section('content')
<div class="p-5">
    <form method="GET" class="mb-4 flex items-end gap-2">
        <label class="text-xs text-slate-500">Dari<input type="date" name="dari" value="{{ $dari }}" class="mt-1 block border border-slate-300 px-3 py-2 text-sm"></label>
        <label class="text-xs text-slate-500">Sampai<input type="date" name="sampai" value="{{ $sampai }}" class="mt-1 block border border-slate-300 px-3 py-2 text-sm"></label>
        <button class="border border-slate-300 px-4 py-2 text-sm">Terapkan</button>
        <a href="{{ route('laporan.csv', ['dari'=>$dari,'sampai'=>$sampai]) }}" class="border border-teal-700 px-4 py-2 text-sm text-teal-800">Ekspor CSV</a>
    </form>
    <div class="grid grid-cols-[2fr_1fr] gap-3">
        <div class="border border-slate-200 bg-white p-5">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    <tr><td class="py-3">Penjualan Bersih</td><td class="py-3 text-right font-semibold tabular-nums">Rp {{ number_format($laporan['penjualan_bersih'],0,',','.') }}</td></tr>
                    <tr><td class="py-3">Harga Pokok Penjualan</td><td class="py-3 text-right tabular-nums">(Rp {{ number_format($laporan['hpp'],0,',','.') }})</td></tr>
                    <tr class="border-y-2 border-slate-300"><td class="py-3 font-semibold">Laba Kotor</td><td class="py-3 text-right font-semibold tabular-nums">Rp {{ number_format($laporan['laba_kotor'],0,',','.') }}</td></tr>
                    @foreach($laporan['rincian_biaya'] as $label => $nilai)
                        <tr><td class="py-2 text-slate-600">{{ $label }}</td><td class="py-2 text-right tabular-nums">Rp {{ number_format($nilai,0,',','.') }}</td></tr>
                    @endforeach
                    <tr class="border-t-2 border-slate-300"><td class="py-3 text-lg font-semibold">Laba Bersih Sederhana</td><td class="py-3 text-right text-lg font-semibold tabular-nums">Rp {{ number_format($laporan['laba_bersih'],0,',','.') }}</td></tr>
                </tbody>
            </table>
        </div>
        <aside class="border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold">Rekap Kas & Saldo</h2>
            <dl class="mt-3 space-y-3 text-sm"><div class="flex justify-between"><dt>Kas Masuk</dt><dd class="tabular-nums">Rp {{ number_format($laporan['rekap_kas']['kas_masuk'],0,',','.') }}</dd></div><div class="flex justify-between"><dt>Kas Keluar</dt><dd class="tabular-nums">Rp {{ number_format($laporan['rekap_kas']['kas_keluar'],0,',','.') }}</dd></div><div class="flex justify-between border-t border-slate-200 pt-3 font-semibold"><dt>Selisih Bersih</dt><dd class="tabular-nums">Rp {{ number_format($laporan['rekap_kas']['saldo_akhir'],0,',','.') }}</dd></div></dl>
            <div class="mt-5 border-t border-slate-200 pt-4 text-xs leading-5 text-slate-500">Catatan: laporan ini menggunakan data transaksi real-time dan belum merupakan laporan pajak atau akuntansi resmi.</div>
        </aside>
    </div>
</div>
@endsection
