<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\CustomersImport;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BigDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $agama = Customer::select('agama')->distinct()->pluck('agama');

        $branches = Branch::all();

        $salesmen = User::where('role', 'salesman')
                ->orderBy('name', 'asc')
                ->get();

        // Ambil daftar kota yang ada di database secara unik
        $cities = Customer::select('kota')->distinct()->get();

        // Ambil data dari model AdminSalesmanGoals
        $customers = Customer::with(['branch', 'salesman'])->get();

        return view('Admin.BigData.bigdata', compact('branches', 'cities', 'customers', 'salesmen', 'agama'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
       //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi input, branch_name sebagai nama cabang
        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'salesman_id' => 'nullable|exists:users,id',
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'nomor_hp_1' => 'nullable|string|max:255',
            'nomor_hp_2' => 'nullable|string|max:255',
            'kelurahan' => 'nullable|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'kota' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|string|in:L,P',
            'tipe_pelanggan' => 'nullable|string|in:first buyer,replacement,additional',
            'jenis_pelanggan' => 'nullable|string|in:retail,fleet',
            'pekerjaan' => 'nullable|string|max:255',
            'tenor' => 'nullable|integer',
            'tanggal_gatepass' => 'nullable|date',
            'model_mobil' => 'nullable|string|max:255',
            'nomor_rangka' => 'nullable|string|max:255',
            'sumber_data' => 'nullable|string|max:255',
            'progress' => 'required|string|in:DO,SPK,pending,reject,tidak valid',
            'alasan' => 'nullable|string|max:255',
            'old_salesman' => 'nullable|string|max:255',
            'agama' => 'nullable|string|max:255',
            'lease_name' => 'nullable|string|max:255',
        ]);

        // Cari branch berdasarkan nama
        $branch = Branch::where('name', $validated['branch_name'])->first();

        if (!$branch) {
            return back()->withErrors(['branch_name' => 'Cabang tidak ditemukan'])->withInput();
        }

        // Siapkan data untuk simpan, ganti 'branch_name' dengan 'branch_id'
        $data = $validated;
        $data['branch_id'] = $branch->id;
        unset($data['branch_name']); // hapus karena tidak ada di DB

        // Simpan data customer
        Customer::create($data);

        return redirect()->route('admin.bigdata')->with('success', 'Data berhasil ditambahkan!');
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
        // Hapus data customer berdasarkan ID
        $customers = Customer::findOrFail($id);
        $customers->delete();

        // Mengembalikan respons JSON setelah berhasil hapus
        return redirect()->route('admin.bigdata')->with('deleted', 'Data berhasil dihapus!');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'xlsx' => 'required|file|mimes:xlsx,xls',
        ]);

        Excel::import(new CustomersImport, $request->file('xlsx'));

        return back()->with('success', 'Import selesai');
    }
}
