<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesmanProgressExport implements FromCollection, WithHeadings, WithMapping
{
    protected $salesmanId;

    public function __construct($salesmanId = null)
    {
        $this->salesmanId = $salesmanId;
    }

    public function collection()
    {
        $query = User::where('role', 'salesman')->with(['branch', 'customers']);

        if ($this->salesmanId) {
            $query->where('id', $this->salesmanId);
        }

        $salesmen = $query->get();

        $this->data = $salesmen->map(function ($salesman) {
            $totalFollowUp = $salesman->customers->count();
            $totalSPK = $salesman->customers->where('progress', 'SPK')->count();
            $totalPending = $salesman->customers->where('progress', 'Pending')->count();
            $totalNonValid = $salesman->customers->where('progress', 'Invalid')->count();

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

        return $this->data;
    }

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
