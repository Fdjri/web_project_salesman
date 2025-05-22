<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesmanProgressExport implements FromCollection, WithHeadings, WithMapping
{
    protected $salesmanId;
    protected $branchId;

    /**
     * Constructor.
     * @param int|null $salesmanId Filter berdasarkan salesman tertentu
     * @param int|null $branchId Filter berdasarkan cabang tertentu
     */
    public function __construct($salesmanId = null, $branchId = null)
    {
        $this->salesmanId = $salesmanId;
        $this->branchId = $branchId;
    }

    /**
     * Ambil koleksi data salesman sesuai filter.
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = User::where('role', 'salesman')->with(['branch', 'customers']);

        if ($this->salesmanId) {
            $query->where('id', $this->salesmanId);
        } elseif ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }
        // Jika kedua parameter null, ambil semua salesman

        $salesmen = $query->get();

        return $salesmen->map(function ($salesman) {
            $totalFollowUp = $salesman->customers->count();
            $totalSPK = $salesman->customers->where('progress', 'SPK')->count();
            $totalPending = $salesman->customers->where('progress', 'pending')->count();
            $totalNonValid = $salesman->customers->where('progress', 'tidak valid')->count();

            return [
                'salesman' => $salesman->name,
                'branch' => $salesman->branch->name ?? '-',
                'totalFollowUp' => $totalFollowUp,
                'totalSPK' => $totalSPK,
                'totalPending' => $totalPending,
                'totalNonValid' => $totalNonValid,
                'progressPercentage' => $totalFollowUp > 0 ? round(($totalFollowUp / $totalFollowUp) * 100, 2) : 0,
                'spkPercentage' => $totalFollowUp > 0 ? round(($totalSPK / $totalFollowUp) * 100, 2) : 0,
                'pendingPercentage' => $totalFollowUp > 0 ? round(($totalPending / $totalFollowUp) * 100, 2) : 0,
                'nonValidPercentage' => $totalFollowUp > 0 ? round(($totalNonValid / $totalFollowUp) * 100, 2) : 0,
            ];
        });
    }

    /**
     * Mapping setiap baris data ke format array untuk Excel.
     */
    public function map($row): array
    {
        return [
            $row['salesman'],
            $row['branch'],
            $row['totalFollowUp'],
            $row['totalSPK'],
            $row['totalPending'],
            $row['totalNonValid'],
            $row['progressPercentage'],
            $row['spkPercentage'],
            $row['pendingPercentage'],
            $row['nonValidPercentage'],
        ];
    }

    /**
     * Judul kolom untuk Excel.
     */
    public function headings(): array
    {
        return [
            'Salesman',
            'Cabang',
            'Total Follow Up',
            'Total SPK',
            'Total Pending',
            'Total Non-valid',
            'Total Progress (%)',
            'Total SPK (%)',
            'Total Pending (%)',
            'Total Non-valid (%)',
        ];
    }
}
