<?php

namespace App\Http\Controllers\KepalaCabang;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // 1. Ambil ID cabang dari kepala cabang yang sedang login
        $branchId = auth()->user()->branch_id;

        // Jika kepala cabang tidak memiliki ID cabang, kembalikan data kosong
        if (!$branchId) {
            return view('kacab.Dashboard.Dashboard', [
                'totalAllCustomers' => 0,
                'invalidCount' => 0,
                'followUpCount' => 0,
                'savedCount' => 0,
                'salesman_goals' => [],
                'branchName' => 'Tidak ada cabang',
            ]);
        }

        // 2. Menghitung data utama (kartu summary) yang spesifik untuk cabang ini
        $totalAllCustomers = Customer::where('branch_id', $branchId)->count();
        $invalidCount = Customer::where('branch_id', $branchId)->where('progress', 'tidak valid')->count();
        
        // [LOGIKA BARU] Hitung savedCount: saved = 1 DAN progress KOSONG untuk cabang ini
        $savedCount = Customer::where('branch_id', $branchId)->where('saved', 1)->whereNull('progress')->count();

        // [LOGIKA BARU] Hitung followUpCount: saved = 1 DAN progress TIDAK KOSONG untuk cabang ini
        $followUpCount = Customer::where('branch_id', $branchId)->where('saved', 1)->whereNotNull('progress')->count();

        // 3. Mengambil statistik per salesman di cabang ini dengan satu query yang efisien
        $salesmanStats = Customer::query()
            ->select(
                'salesman_id',
                DB::raw('COUNT(*) as total_customers'),
                // [LOGIKA BARU] Menghitung saved_count per salesman
                DB::raw("SUM(CASE WHEN saved = 1 AND progress IS NULL THEN 1 ELSE 0 END) as saved_count"),
                // [LOGIKA BARU] Menghitung follow_up_count per salesman
                DB::raw("SUM(CASE WHEN saved = 1 AND progress IS NOT NULL THEN 1 ELSE 0 END) as follow_up_count"),
                DB::raw('MAX(created_at) as latest_customer_date')
            )
            // Filter hanya untuk customer yang salesman-nya berada di cabang yang sama
            ->whereHas('salesman', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->whereNotNull('salesman_id')
            ->groupBy('salesman_id')
            ->orderBy('latest_customer_date', 'desc')
            ->get();

        // 4. Mengambil data model Salesman dan Branch untuk digabungkan
        $salesmanIds = $salesmanStats->pluck('salesman_id');
        $salesmen = User::with('branch')->whereIn('id', $salesmanIds)->get()->keyBy('id');

        // 5. Menggabungkan data statistik dengan data model Salesman
        $salesman_goals = [];
        foreach ($salesmanStats as $index => $stat) {
            $salesmanModel = $salesmen->get($stat->salesman_id);
            if ($salesmanModel) {
                $salesman_goals[] = [
                    'no' => $index + 1,
                    'branch' => $salesmanModel->branch,
                    'salesman' => $salesmanModel,
                    'total_customers' => (int) $stat->total_customers,
                    'saved_count' => (int) $stat->saved_count,
                    'follow_up_count' => (int) $stat->follow_up_count,
                ];
            }
        }
        
        // Ambil nama cabang untuk ditampilkan di view
        $branchName = Branch::where('id', $branchId)->value('name');

        return view('kacab.Dashboard.Dashboard', compact(
            'totalAllCustomers',
            'invalidCount',
            'followUpCount',
            'savedCount',
            'salesman_goals',
            'branchName' // Mengirim nama cabang saat ini
        ));
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
}
