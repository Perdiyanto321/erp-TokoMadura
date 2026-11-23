@extends('layouts.app')
@section('title', 'Keuangan')
@section('content')
<div class="p-5">
    <div class="grid grid-cols-4 gap-3">
        <div class="border border-slate-200 bg-white p-4"><div class="text-xs text-slate-500">Kas Masuk</div><div class="mt-2 text-xl font-semibold tabular-nums">Rp {{ number_format($rekapKas['kas_masuk'],0,',','.') }}</div></div>
        <div class="border border-slate-200 bg-white p-4"><div class="text-xs text-slate-500">Kas Keluar</div><div class="mt-2 text-xl font-semibold tabular-nums">Rp {{ number_format($rekapKas['kas_keluar'],0,',','.') }}</div></div>
        <div class="border border-slate-200 bg-white p-4"><div class="text-xs text-slate-500">Saldo Akhir</div><div class="mt-2 text-xl font-semibold tabular-nums">Rp {{ number_format($rekapKas['saldo_akhir'],0,',','.') }}</div></div>
        <div class="border border-slate-200 bg-white p-4"><div class="text-xs text-slate-500">Piutang Kasbon</div><div class="mt-2 text-xl font-semibold tabular-nums">Rp {{ number_format($piutang,0,',','.') }}</div></div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3">
        <section class="border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold">Utang Supplier</h2>
            <div class="mt-3 divide-y divide-slate-100">
                @forelse($utang as $item)
                    <div class="py-3">
                        <div class="flex justify-between text-sm"><div><div class="font-mono text-xs">{{ $item['nomor_po'] }}</div><div>{{ $item['supplier_nama'] }}</div></div><div class="text-right"><div class="font-semibold tabular-nums">Rp {{ number_format($item['sisa_utang'],0,',','.') }}</div><div class="text-xs text-slate-500">{{ $item['status_utang'] }}</div></div></div>
                        @if($item['sisa_utang'] > 0)
                            <form method="POST" action="{{ route('keuangan.utang.bayar', $item['id']) }}" class="mt-2 flex gap-2">@csrf
                                <input name="jumlah" type="number" min="1" max="{{ $item['sisa_utang'] }}" placeholder="Jumlah" class="w-32 border border-slate-300 px-2 py-1.5 text-sm tabular-nums">
                                <select name="metode" class="border border-slate-300 px-2 text-sm"><option value="tunai">Tunai</option><option value="transfer">Transfer</option><option value="qris">QRIS</option></select>
                                <button class="border border-teal-700 px-3 text-xs text-teal-800">Bayar</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="py-8 text-center text-sm text-slate-500">Tidak ada utang supplier</div>
                @endforelse
            </div>
        </section>

        <section class="border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold">Catat Biaya Operasional</h2>
            <form method="POST" action="{{ route('keuangan.biaya.store') }}" class="mt-3 grid grid-cols-2 gap-2">@csrf
                <select name="kategori_biaya" class="border border-slate-300 px-3 py-2 text-sm"><option value="listrik_air">Listrik / Air</option><option value="sewa">Sewa</option><option value="gaji">Gaji</option><option value="perlengkapan">Perlengkapan</option><option value="lainnya">Lainnya</option></select>
                <input name="nominal" type="number" min="1" placeholder="Nominal" class="border border-slate-300 px-3 py-2 text-sm tabular-nums">
                <input name="tanggal" type="date" value="{{ date('Y-m-d') }}" class="border border-slate-300 px-3 py-2 text-sm">
                <input name="keterangan" placeholder="Keterangan" class="border border-slate-300 px-3 py-2 text-sm">
                <button class="col-span-2 border border-teal-700 bg-teal-700 px-3 py-2 text-sm text-white">Simpan Biaya</button>
            </form>
        </section>
    </div>

    <div class="mt-4 border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Riwayat Biaya</div>
        <table class="w-full text-sm"><thead class="bg-slate-50 text-slate-500"><tr><th class="px-3 py-2 text-left">Tanggal</th><th class="px-3 py-2 text-left">Kategori</th><th class="px-3 py-2 text-left">Keterangan</th><th class="px-3 py-2 text-right">Nominal</th></tr></thead><tbody class="divide-y divide-slate-100">
            @foreach($pengeluaran as $item)<tr><td class="px-3 py-2.5">{{ $item->tanggal->format('d/m/Y') }}</td><td class="px-3 py-2.5">{{ $item->kategori_biaya }}</td><td class="px-3 py-2.5">{{ $item->keterangan }}</td><td class="px-3 py-2.5 text-right tabular-nums">Rp {{ number_format($item->nominal,0,',','.') }}</td></tr>@endforeach
        </tbody></table>
    </div>
</div>
@endsection
