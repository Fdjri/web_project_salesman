<?php

namespace App\Http\Controllers\KepalaCabang;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Models\Customer;
use App\Exports\SalesmanProgressExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Ambil ID cabang dari Kepala Cabang yang sedang login
        $branchId = auth()->user()->branch_id;

        // Jika tidak ada ID cabang, kembalikan data kosong
        if (!$branchId) {
            return view('kacab.Laporan.Laporan', [
                'allSalesmanProgress' => [],
                'branches' => []
            ]);
        }

        // 2. Mengambil statistik progres untuk setiap salesman di cabang ini
        $salesmanStats = Customer::query()
            ->select(
                'salesman_id',
                DB::raw('COUNT(*) as total_customers'),
                DB::raw("SUM(CASE WHEN progress IS NOT NULL THEN 1 ELSE 0 END) as total_follow_up"),
                DB::raw("SUM(CASE WHEN progress = 'SPK' THEN 1 ELSE 0 END) as total_spk"),
                DB::raw("SUM(CASE WHEN progress = 'pending' THEN 1 ELSE 0 END) as total_pending"),
                DB::raw("SUM(CASE WHEN progress IN ('tidak valid', 'reject') THEN 1 ELSE 0 END) as total_non_valid")
            )
            // Filter hanya untuk customer yang salesman-nya berada di cabang yang sama
            ->whereHas('salesman', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->whereNotNull('salesman_id')
            ->groupBy('salesman_id')
            ->get();

        // 3. Mengambil data model Salesman dan Branch untuk digabungkan
        $salesmanIds = $salesmanStats->pluck('salesman_id');
        $salesmen = User::with('branch')->whereIn('id', $salesmanIds)->get()->keyBy('id');

        // 4. Memproses data statistik untuk ditambahkan persentase
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

        // Ambil semua cabang untuk filter (opsional, jika masih diperlukan)
        $branches = Branch::all();
        
        // Kirim data ke view
        return view('kacab.Laporan.Laporan', compact('allSalesmanProgress', 'branches'));
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
        $user = auth()->user();

        $branchId = $user->branch_id;

        return Excel::download(new SalesmanProgressExport(null, $branchId), 'laporan_cabang_'.date('Ymd_His').'.xlsx');
    }

}
