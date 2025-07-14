<?php

namespace App\Http\Controllers\Salesman;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use App\Exports\SalesmanProgressExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $salesman = auth()->user();

        // 1. Menggunakan satu query untuk mengambil semua statistik customer milik salesman ini
        $stats = Customer::query()
            ->select(
                // Menghitung total customer (total kontak)
                DB::raw('COUNT(*) as total_customers'),
                // Menghitung total yang sudah di-follow up (progress tidak kosong)
                DB::raw("SUM(CASE WHEN progress IS NOT NULL THEN 1 ELSE 0 END) as total_follow_up"),
                // Menghitung total SPK
                DB::raw("SUM(CASE WHEN progress = 'SPK' THEN 1 ELSE 0 END) as total_spk"),
                // Menghitung total Pending
                DB::raw("SUM(CASE WHEN progress = 'pending' THEN 1 ELSE 0 END) as total_pending"),
                // Menghitung total Tidak Valid (termasuk reject)
                DB::raw("SUM(CASE WHEN progress IN ('tidak valid', 'reject') THEN 1 ELSE 0 END) as total_non_valid")
            )
            ->where('salesman_id', $salesman->id)
            ->first(); // Gunakan first() karena kita hanya mengambil data untuk satu salesman

        // 2. Kalkulasi persentase dari hasil query
        $totalCustomers = $stats->total_customers ?? 0;
        $totalFollowUp = $stats->total_follow_up ?? 0;
        $totalSPK = $stats->total_spk ?? 0;
        $totalPending = $stats->total_pending ?? 0;
        $totalNonValid = $stats->total_non_valid ?? 0;

        $progressPercentage = $totalCustomers > 0 ? ($totalFollowUp / $totalCustomers) * 100 : 0;
        $spkPercentage = $totalFollowUp > 0 ? ($totalSPK / $totalFollowUp) * 100 : 0;
        $pendingPercentage = $totalFollowUp > 0 ? ($totalPending / $totalFollowUp) * 100 : 0;
        $nonValidPercentage = $totalFollowUp > 0 ? ($totalNonValid / $totalFollowUp) * 100 : 0;

        // 3. Menyiapkan data untuk dikirim ke view
        $salesmanProgress = [
            'salesman' => $salesman->name,
            'branch' => $salesman->branch->name ?? 'N/A',
            'totalKontak' => $totalCustomers,
            'totalFollowUp' => $totalFollowUp,
            'totalSPK' => $totalSPK,
            'totalPending' => $totalPending,
            'totalNonValid' => $totalNonValid,
            'progressPercentage' => round($progressPercentage, 2),
            'spkPercentage' => round($spkPercentage, 2),
            'pendingPercentage' => round($pendingPercentage, 2),
            'nonValidPercentage' => round($nonValidPercentage, 2),
        ];
        
        // Ambil data cabang untuk filter (jika ada)
        $branches = Branch::all();
        
        return view('Salesman.Laporan.Laporan', compact('salesmanProgress', 'branches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function export()
    {
        $salesmanId = auth()->id();

        return Excel::download(new SalesmanProgressExport($salesmanId), 'laporan_salesman_'.auth()->user()->name.'_'.date('Ymd_His').'.xlsx');
    }
}
