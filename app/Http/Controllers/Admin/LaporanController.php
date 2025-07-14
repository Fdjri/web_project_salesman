<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SalesmanProgressExport;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Mengambil statistik progres untuk setiap salesman menggunakan satu query
        $salesmanStats = Customer::query()
            ->select(
                'salesman_id',
                // Menghitung total customer per salesman
                DB::raw('COUNT(*) as total_customers'),
                // Menghitung total customer yang sudah di-follow up (progress tidak kosong)
                DB::raw("SUM(CASE WHEN progress IS NOT NULL THEN 1 ELSE 0 END) as total_follow_up"),
                // Menghitung total SPK
                DB::raw("SUM(CASE WHEN progress = 'SPK' THEN 1 ELSE 0 END) as total_spk"),
                // Menghitung total Pending
                DB::raw("SUM(CASE WHEN progress = 'pending' THEN 1 ELSE 0 END) as total_pending"),
                // Menghitung total Tidak Valid (termasuk reject jika perlu)
                DB::raw("SUM(CASE WHEN progress IN ('tidak valid', 'reject') THEN 1 ELSE 0 END) as total_non_valid")
            )
            ->whereNotNull('salesman_id')
            ->groupBy('salesman_id')
            ->get();

        // 2. Mengambil data model Salesman dan Branch untuk digabungkan
        $salesmanIds = $salesmanStats->pluck('salesman_id');
        $salesmen = User::with('branch')->whereIn('id', $salesmanIds)->get()->keyBy('id');

        // 3. Memproses data statistik untuk ditambahkan persentase dan digabungkan
        $allSalesmanProgress = [];
        foreach ($salesmanStats as $stat) {
            $salesmanModel = $salesmen->get($stat->salesman_id);

            if ($salesmanModel) {
                $totalCustomers = (int) $stat->total_customers;
                $totalFollowUp = (int) $stat->total_follow_up;
                $totalSPK = (int) $stat->total_spk;
                $totalPending = (int) $stat->total_pending;
                $totalNonValid = (int) $stat->total_non_valid;

                // Hitung persentase
                $progressPercentage = $totalCustomers > 0 ? ($totalFollowUp / $totalCustomers) * 100 : 0;
                $spkPercentage = $totalFollowUp > 0 ? ($totalSPK / $totalFollowUp) * 100 : 0;
                $pendingPercentage = $totalFollowUp > 0 ? ($totalPending / $totalFollowUp) * 100 : 0;
                $nonValidPercentage = $totalFollowUp > 0 ? ($totalNonValid / $totalFollowUp) * 100 : 0;

                $allSalesmanProgress[] = [
                    'salesman' => $salesmanModel->name,
                    'branch' => $salesmanModel->branch->name ?? 'N/A',
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
            }
        }

        // Ambil semua cabang untuk filter
        $branches = Branch::all();
        
        // Kirim data ke view
        return view('Admin.Laporan.Laporan', compact('allSalesmanProgress', 'branches'));
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
    public function store()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update()
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        //
    }

    public function export()
    {
        return Excel::download(new SalesmanProgressExport(), 'laporan_salesman_'.date('Ymd_His').'.xlsx');
    }
}
