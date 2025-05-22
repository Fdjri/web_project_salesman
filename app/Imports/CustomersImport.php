<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class CustomersImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public function headingRow(): int
    {
        return 1;
    }

    public function model(array $row)
    {
        // Cari cabang berdasarkan nama (case-insensitive)
        $branch = Branch::where('name', trim($row['cabang'] ?? ''))->first();
        if (! $branch) {
            return null; // bisa juga throw exception jika perlu
        }

        // Cari salesman berdasarkan nama, bisa null jika tidak ditemukan atau kosong
        $salesmanName = trim($row['salesman'] ?? '');
        $salesman = $salesmanName !== '' ? User::where('name', $salesmanName)->first() : null;

        // Konversi tanggal lahir (Excel date atau string)
        $tanggalLahir = null;
        if (! empty($row['tanggal_lahir'])) {
            $tl = $row['tanggal_lahir'];
            $tanggalLahir = is_numeric($tl)
                ? Carbon::instance(ExcelDate::excelToDateTimeObject($tl))
                : Carbon::parse($tl);
        }

        // Konversi tanggal gatepass (Excel date atau string)
        $tanggalGatepass = null;
        if (! empty($row['tanggal_gatepass'])) {
            $tg = $row['tanggal_gatepass'];
            $tanggalGatepass = is_numeric($tg)
                ? Carbon::instance(ExcelDate::excelToDateTimeObject($tg))
                : Carbon::parse($tg);
        }

        // Validasi jenis kelamin (enum 'L' atau 'P')
        $rawJk = strtoupper(trim($row['jenis_kelamin'] ?? ''));
        $jenisKelamin = in_array($rawJk, ['L', 'P'], true) ? $rawJk : null;

        // Validasi tipe pelanggan (enum 'first buyer','replacement','additional')
        $rawTipe = strtolower(trim($row['tipe_pelanggan'] ?? ''));
        $allowedTipe = ['first buyer','replacement','additional'];
        $tipePelanggan = in_array($rawTipe, $allowedTipe, true) ? $rawTipe : null;

        // Validasi jenis pelanggan (enum 'retail','fleet')
        $rawJenis = strtolower(trim($row['jenis_pelanggan'] ?? ''));
        $allowedJenis = ['retail','fleet'];
        $jenisPelanggan = in_array($rawJenis, $allowedJenis, true) ? $rawJenis : null;

        // Validasi progress (enum 'DO','SPK','pending','reject','tidak valid'), bisa kosong (null)
        $rawProgress = strtolower(trim($row['progress'] ?? ''));
        $allowedProgress = ['do','spk','pending','reject','tidak valid'];
        $progress = null;
        if ($rawProgress !== '' && in_array($rawProgress, $allowedProgress, true)) {
            $progressMap = [
                'do'          => 'DO',
                'spk'         => 'SPK',
                'pending'     => 'pending',
                'reject'      => 'reject',
                'tidak valid' => 'tidak valid',
            ];
            $progress = $progressMap[$rawProgress];
        }

        // Saved selalu 0 saat import
        $saved = 0;

        return new Customer([
            'branch_id'        => $branch->id,
            'salesman_id'      => $salesman?->id,
            'nama'             => trim($row['nama'] ?? ''),
            'alamat'           => trim($row['alamat'] ?? null),
            'nomor_hp_1'       => trim($row['nomor_hp_1'] ?? ''),
            'nomor_hp_2'       => trim($row['nomor_hp_2'] ?? null),
            'kelurahan'        => trim($row['kelurahan'] ?? null),
            'kecamatan'        => trim($row['kecamatan'] ?? null),
            'kota'             => trim($row['kota'] ?? null),
            'tanggal_lahir'    => $tanggalLahir,
            'jenis_kelamin'    => $jenisKelamin,
            'agama'            => trim($row['agama'] ?? null),
            'tipe_pelanggan'   => $tipePelanggan,
            'jenis_pelanggan'  => $jenisPelanggan,
            'pekerjaan'        => trim($row['pekerjaan'] ?? null),
            'tenor'            => is_numeric($row['tenor'] ?? null) ? (int) $row['tenor'] : null,
            'tanggal_gatepass' => $tanggalGatepass,
            'model_mobil'      => trim($row['model_mobil'] ?? null),
            'nomor_rangka'     => trim($row['nomor_rangka'] ?? null),
            'sumber_data'      => trim($row['sumber_data'] ?? null),
            'progress'         => $progress,
            'saved'            => $saved,
            'alasan'           => trim($row['alasan'] ?? null),
            'old_salesman'     => trim($row['old_salesman'] ?? null),
        ]);
    }
}
