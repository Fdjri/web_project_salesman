<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\User;
use App\Models\Branch;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterExport;

class CustomerExport implements FromQuery, WithMapping, WithHeadings, WithEvents
{
    /**
     * Mengambil data customer yang akan diekspor.
     */
    public function query()
    {
        // [DIUBAH] Gunakan eager loading dengan with() untuk mengambil relasi branch dan salesman.
        // Ini akan mencegah N+1 query problem dan membuat ekspor jauh lebih cepat.
        return Customer::query()
            ->with(['branch', 'salesman']) // Memuat relasi
            ->where('progress', 'tidak valid');
    }

    /**
     * Mendefinisikan header untuk file Excel.
     */
    public function headings(): array
    {
        // [DIUBAH] Mengganti header dari ID menjadi Nama
        return [
            'ID', 'Cabang', 'Salesman', 'Nama Customer', 'Alamat',
            'No. HP 1', 'No. HP 2', 'Kelurahan', 'Kecamatan', 'Kota',
            'Tanggal Lahir', 'Agama', 'Jenis Kelamin', 'Tipe Pelanggan',
            'Jenis Pelanggan', 'Pekerjaan', 'Tenor', 'Tanggal Gatepass',
            'Model Mobil', 'Nomor Rangka', 'Sumber Data', 'Progress',
            'Saved', 'Alasan', 'Old Salesman',
        ];
    }

    /**
     * Memetakan data dari setiap customer ke dalam kolom Excel.
     *
     * @param Customer $customer
     */
    public function map($customer): array
    {
        // [DIUBAH] Mengambil nama dari relasi, bukan ID.
        // Menggunakan null-safe operator (??) untuk menangani jika relasi kosong.
        return [
            $customer->id,
            $customer->branch->name ?? 'N/A', // Ambil nama cabang
            $customer->salesman->name ?? 'N/A', // Ambil nama salesman
            $customer->nama,
            $customer->alamat,
            $customer->nomor_hp_1,
            $customer->nomor_hp_2,
            $customer->kelurahan,
            $customer->kecamatan,
            $customer->kota,
            // Ini akan berfungsi dengan benar setelah Anda memperbarui model Customer
            optional($customer->tanggal_lahir)->toDateString(),
            $customer->agama,
            $customer->jenis_kelamin,
            $customer->tipe_pelanggan,
            $customer->jenis_pelanggan,
            $customer->pekerjaan,
            $customer->tenor,
            optional($customer->tanggal_gatepass)->toDateString(),
            $customer->model_mobil,
            $customer->nomor_rangka,
            $customer->sumber_data,
            $customer->progress,
            $customer->saved,
            $customer->alasan,
            $customer->old_salesman,
        ];
    }

    /**
     * Mendaftarkan event yang akan dijalankan setelah ekspor selesai.
     */
    public function registerEvents(): array
    {
        return [
            AfterExport::class => function(AfterExport $event) {
                // Hapus data 'tidak valid' setelah ekspor selesai
                Customer::where('progress', 'tidak valid')->delete();
            },
        ];
    }
}