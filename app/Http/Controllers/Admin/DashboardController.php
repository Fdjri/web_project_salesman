<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // 1. Menghitung data utama untuk kartu summary di bagian atas
        $totalAllCustomers = Customer::count();
        $invalidCount = Customer::where('progress', 'tidak valid')->count();
        
        // [LOGIKA BARU] Hitung followUpCount: saved = 1 DAN progress TIDAK KOSONG
        $followUpCount = Customer::where('saved', 1)->whereNotNull('progress')->count();

        // [LOGIKA BARU] Hitung savedCount: saved = 1 DAN progress KOSONG
        $savedCount = Customer::where('saved', 1)->whereNull('progress')->count();

        // 2. Mengambil statistik per salesman dengan satu query yang efisien
        $salesmanStats = Customer::query()
            ->select(
                'salesman_id',
                DB::raw('COUNT(*) as total_customers'),
                // [LOGIKA BARU] Menghitung saved_count per salesman: saved=1 DAN progress KOSONG
                DB::raw("SUM(CASE WHEN saved = 1 AND progress IS NULL THEN 1 ELSE 0 END) as saved_count"),
                // [LOGIKA BARU] Menghitung follow_up_count per salesman: saved=1 DAN progress TERISI
                DB::raw("SUM(CASE WHEN saved = 1 AND progress IS NOT NULL THEN 1 ELSE 0 END) as follow_up_count"),
                DB::raw('MAX(created_at) as latest_customer_date')
            )
            ->whereNotNull('salesman_id')
            ->whereHas('salesman.branch') // Memastikan salesman dan cabangnya ada
            ->groupBy('salesman_id')
            ->orderBy('latest_customer_date', 'desc')
            ->get();

        // 3. Mengambil data model Salesman dan Branch untuk digabungkan
        $salesmanIds = $salesmanStats->pluck('salesman_id');
        $salesmen = User::with('branch')->whereIn('id', $salesmanIds)->get()->keyBy('id');

        // 4. Menggabungkan data statistik dengan data model Salesman
        $admin_salesman_goals = [];
        foreach ($salesmanStats as $index => $stat) {
            $salesmanModel = $salesmen->get($stat->salesman_id);
            if ($salesmanModel) {
                $admin_salesman_goals[] = [
                    'no' => $index + 1,
                    'branch' => $salesmanModel->branch,
                    'salesman' => $salesmanModel,
                    'total_customers' => (int) $stat->total_customers,
                    'saved_count' => (int) $stat->saved_count,
                    'follow_up_count' => (int) $stat->follow_up_count,
                ];
            }
        }
        
        $branches = Branch::all();

        return view('Admin.Dashboard.Dashboard', compact(
            'totalAllCustomers',
            'invalidCount',
            'followUpCount',
            'savedCount',
            'admin_salesman_goals',
            'branches'
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
