@extends('layouts.app')
@section('title', 'Kartu Stok')
@section('content')
<div class="p-5">
    <div class="border border-slate-200 bg-white p-4">
        <div class="flex items-start justify-between">
            <div><div class="font-mono text-xs text-slate-500">{{ $produk->kode }}</div><h1 class="mt-1 text-lg font-semibold">{{ $produk->nama }}</h1></div>
            <div class="text-right"><div class="text-xs text-slate-500">Stok Saat Ini</div><div class="text-2xl font-semibold tabular-nums">{{ $produk->stok }} {{ $produk->satuan }}</div></div>
        </div>
    </div>
    <div class="mt-4 border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="px-3 py-2 text-left">Waktu</th><th class="px-3 py-2 text-left">Jenis</th><th class="px-3 py-2 text-left">Petugas</th><th class="px-3 py-2 text-right">Perubahan</th><th class="px-3 py-2 text-right">Stok Sesudah</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($gerakan as $item)
                    <tr><td class="px-3 py-2.5">{{ $item->created_at->format('d/m/Y H:i') }}</td><td class="px-3 py-2.5">{{ $item->jenis }}</td><td class="px-3 py-2.5">{{ $item->user?->username }}</td><td class="px-3 py-2.5 text-right tabular-nums {{ $item->qty < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $item->qty > 0 ? '+' : '' }}{{ $item->qty }}</td><td class="px-3 py-2.5 text-right tabular-nums">{{ $item->stok_sesudah }}</td></tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">Belum ada pergerakan stok</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $gerakan->links() }}</div>
</div>
@endsection
