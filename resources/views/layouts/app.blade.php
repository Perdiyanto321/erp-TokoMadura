<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ERP Toko Madura')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <div class="min-h-screen">
        <aside class="fixed inset-y-0 left-0 z-20 flex w-60 flex-col bg-slate-900 text-slate-300">
            <div class="flex h-14 items-center border-b border-slate-700 px-5">
                <div class="text-sm font-semibold tracking-wide text-white">TOKO MADURA ERP</div>
            </div>
            <nav class="flex-1 space-y-1 px-3 py-4 text-sm">
                @php($role = strtolower(auth()->user()->role->nama))
                <a href="{{ route('dashboard') }}" class="block border-l-3 border-emerald-500 bg-slate-800 px-3 py-2.5 text-white">Dasbor</a>
                @if(in_array($role, ['admin', 'kasir'], true))
                    <a href="{{ route('kasir.index') }}" class="block px-3 py-2.5 hover:bg-slate-800 hover:text-white">Penjualan / POS</a>
                    <a href="{{ route('kasir.riwayat') }}" class="block px-3 py-2.5 hover:bg-slate-800 hover:text-white">Riwayat Penjualan</a>
                @endif
                @if(in_array($role, ['admin', 'gudang'], true))
                    <a href="{{ route('stok.index') }}" class="block px-3 py-2.5 hover:bg-slate-800 hover:text-white">Inventori</a>
                    <a href="{{ route('pembelian.index') }}" class="block px-3 py-2.5 hover:bg-slate-800 hover:text-white">Pembelian / PO</a>
                @endif
                @if(in_array($role, ['admin', 'keuangan'], true))
                    <a href="{{ route('keuangan.index') }}" class="block px-3 py-2.5 hover:bg-slate-800 hover:text-white">Keuangan</a>
                    <a href="{{ route('laporan.index') }}" class="block px-3 py-2.5 hover:bg-slate-800 hover:text-white">Laporan</a>
                @endif
            </nav>
            <form action="{{ route('logout') }}" method="POST" class="border-t border-slate-700 p-3">
                @csrf
                <button class="w-full border border-slate-600 px-3 py-2 text-sm text-slate-200">Keluar</button>
            </form>
        </aside>

        <div class="min-h-screen pl-60">
            <header class="fixed left-60 right-0 top-0 z-10 flex h-14 items-center justify-between border-b border-slate-200 bg-white px-5">
                <div>
                    <span class="text-sm font-semibold">@yield('title', 'Dasbor')</span>
                    <span class="ml-2 text-xs text-slate-500">{{ now()->format('d M Y') }}</span>
                </div>
                <div class="text-xs text-slate-500">{{ strtoupper(auth()->user()->role->nama) }} · {{ auth()->user()->username }}</div>
            </header>
            <main class="pt-14">
                @if(session('status'))
                    <div class="mx-5 mt-4 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="mx-5 mt-4 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
