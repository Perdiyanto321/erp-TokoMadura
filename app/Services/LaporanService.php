<?php

namespace App\Services;

use App\Models\DetailPenjualan;
use App\Models\DetailRetur;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use App\Models\Produk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LaporanService
{
    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    /**
     * Laporan Laba Rugi Sederhana (FR-26, TS-10).
     *
     * @param string|null $dari Format Y-m-d
     * @param string|null $sampai Format Y-m-d
     * @return array{
     *     penjualan_kotor: float,
     *     retur_penjualan: float,
     *     penjualan_bersih: float,
     *     hpp_kotor: float,
     *     retur_hpp: float,
     *     hpp: float,
     *     laba_kotor: float,
     *     biaya_operasional: float,
     *     laba_bersih: float,
     *     rincian_biaya: array<string, float>,
     *     rekap_kas: array{saldo_awal: float, kas_masuk: float, kas_keluar: float, saldo_akhir: float},
     *     total_utang: float,
     *     total_piutang: float
     * }
     */
    public function labaRugi(?string $dari = null, ?string $sampai = null): array
    {
        $penjualanQuery = Penjualan::where('status', 'selesai');
        $returQuery = DetailRetur::whereHas('retur', function ($q) {
            $q->where('status', 'disetujui');
        });
        $biayaQuery = Pengeluaran::query();

        if ($dari) {
            $penjualanQuery->where('tanggal', '>=', $dari);
            $returQuery->whereHas('retur', fn($q) => $q->where('tanggal', '>=', $dari));
            $biayaQuery->where('tanggal', '>=', $dari);
        }

        if ($sampai) {
            $penjualanQuery->where('tanggal', '<=', $sampai);
            $returQuery->whereHas('retur', fn($q) => $q->where('tanggal', '<=', $sampai));
            $biayaQuery->where('tanggal', '<=', $sampai);
        }

        $penjualanIds = $penjualanQuery->pluck('id');

        // Penjualan kotor & HPP kotor
        $detailPenjualan = DetailPenjualan::whereIn('penjualan_id', $penjualanIds)->get();
        $penjualanKotor = (float) $detailPenjualan->sum(fn ($d) => $d->qty * (float) $d->harga_jual);
        $hppKotor = (float) $detailPenjualan->sum(fn ($d) => $d->qty * (float) $d->hpp);

        // Retur pendapatan & HPP layak jual
        $returItems = $returQuery->with('detailPenjualan')->get();
        $returPenjualan = (float) $returItems->sum(fn ($r) => $r->qty * (float) $r->detailPenjualan->harga_jual);
        
        // HPP hanya dikoreksi untuk retur yang layak_jual (D-10)
        $returHpp = (float) $returItems->where('kondisi', 'layak_jual')->sum(fn ($r) => $r->qty * (float) $r->detailPenjualan->hpp);

        $penjualanBersih = $penjualanKotor - $returPenjualan;
        $hppBersih = max(0, $hppKotor - $returHpp);
        $labaKotor = $penjualanBersih - $hppBersih;

        // Biaya operasional per kategori
        $biayaList = $biayaQuery->get();
        $biayaOperasional = (float) $biayaList->sum('nominal');

        $kategoriConfig = config('keuangan.kategori_biaya', []);
        $rincianBiaya = [];
        foreach ($kategoriConfig as $key => $label) {
            $rincianBiaya[$label] = (float) $biayaList->where('kategori_biaya', $key)->sum('nominal');
        }

        $labaBersih = $labaKotor - $biayaOperasional;

        return [
            'penjualan_kotor' => $penjualanKotor,
            'retur_penjualan' => $returPenjualan,
            'penjualan_bersih' => $penjualanBersih,
            'hpp_kotor' => $hppKotor,
            'retur_hpp' => $returHpp,
            'hpp' => $hppBersih,
            'laba_kotor' => $labaKotor,
            'biaya_operasional' => $biayaOperasional,
            'laba_bersih' => $labaBersih,
            'rincian_biaya' => $rincianBiaya,
            'rekap_kas' => $this->keuanganService->rekapKas($dari, $sampai),
            'total_utang' => $this->keuanganService->hitungTotalUtang(),
            'total_piutang' => $this->keuanganService->hitungTotalPiutang(),
        ];
    }

    /**
     * Ringkasan data untuk Dasbor (FR-28).
     */
    public function getDasborData(): array
    {
        $hariIni = date('Y-m-d');
        $kemarin = date('Y-m-d', strtotime('-1 day'));

        $penjualanHariIni = (float) Penjualan::where('status', 'selesai')
            ->where('tanggal', $hariIni)
            ->sum('total');

        $penjualanKemarin = (float) Penjualan::where('status', 'selesai')
            ->where('tanggal', $kemarin)
            ->sum('total');

        $transaksiHariIni = Penjualan::where('status', 'selesai')
            ->where('tanggal', $hariIni)
            ->count();

        $rataRataTransaksi = $transaksiHariIni > 0
            ? $penjualanHariIni / $transaksiHariIni
            : 0;

        $persenPerubahan = 0;
        if ($penjualanKemarin > 0) {
            $persenPerubahan = round((($penjualanHariIni - $penjualanKemarin) / $penjualanKemarin) * 100, 1);
        }

        $rekapKas = $this->keuanganService->rekapKas();
        $totalUtang = $this->keuanganService->hitungTotalUtang();
        $totalPiutang = $this->keuanganService->hitungTotalPiutang();

        // Produk di bawah stok minimum
        $produkPerluRestok = Produk::dibawahMinimum()->where('status', 'aktif')->take(5)->get();
        $jumlahPerluRestok = Produk::dibawahMinimum()->where('status', 'aktif')->count();

        // Tren penjualan 14 hari terakhir
        $tglAwal14 = date('Y-m-d', strtotime('-13 days'));
        $trenPenjualanRaw = Penjualan::where('status', 'selesai')
            ->where('tanggal', '>=', $tglAwal14)
            ->groupBy('tanggal')
            ->selectRaw('tanggal, SUM(total) as total')
            ->pluck('total', 'tanggal');

        $tren14Hari = [];
        for ($i = 13; $i >= 0; $i--) {
            $t = date('Y-m-d', strtotime("-{$i} days"));
            $tren14Hari[$t] = (float) ($trenPenjualanRaw[$t] ?? 0);
        }

        // Produk terlaris bulan ini
        $awalBulan = date('Y-m-01');
        $produkTerlaris = DetailPenjualan::whereHas('penjualan', function ($q) use ($awalBulan) {
            $q->where('status', 'selesai')->where('tanggal', '>=', $awalBulan);
        })
            ->selectRaw('produk_id, SUM(qty) as total_qty, SUM(qty * harga_jual) as total_omzet')
            ->groupBy('produk_id')
            ->orderByDesc('total_qty')
            ->with('produk')
            ->take(5)
            ->get();

        // Utang jatuh tempo dalam 7 hari
        $tglBatasJatuhTempo = date('Y-m-d', strtotime('+7 days'));
        $utangJatuhTempo = $this->keuanganService->getDaftarUtang()
            ->filter(fn ($u) => $u['sisa_utang'] > 0 && $u['jatuh_tempo'] && $u['jatuh_tempo'] <= $tglBatasJatuhTempo)
            ->take(5);

        return [
            'penjualan_hari_ini' => $penjualanHariIni,
            'persen_perubahan' => $persenPerubahan,
            'transaksi_hari_ini' => $transaksiHariIni,
            'rata_rata_transaksi' => $rataRataTransaksi,
            'saldo_kas' => $rekapKas['saldo_akhir'],
            'total_utang' => $totalUtang,
            'total_piutang' => $totalPiutang,
            'produk_perlu_restok' => $produkPerluRestok,
            'jumlah_perlu_restok' => $jumlahPerluRestok,
            'tren_14_hari' => $tren14Hari,
            'produk_terlaris' => $produkTerlaris,
            'utang_jatuh_tempo' => $utangJatuhTempo,
        ];
    }
}
