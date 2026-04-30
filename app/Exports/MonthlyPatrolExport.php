<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MonthlyPatrolExport implements FromCollection, WithHeadings
{
    protected $year;

    public function __construct($year = null)
    {
        $this->year = $year ?? now()->year;
    }

    public function collection()
    {
        return DB::table('patrol_sessions')
            ->whereYear('started_at', $this->year)
            ->whereNotNull('ended_at')
            ->selectRaw('
                MONTH(started_at) as month,
                COUNT(*) as sessions,
                COUNT(DISTINCT user_id) as guards,
                ROUND(SUM(distance)/1000,2) as distance_km
            ')
            ->groupByRaw('MONTH(started_at)')
            ->orderBy('month')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Month',
            'Total Sessions',
            'Active Forest Guards',
            'Total Distance (KM)'
        ];
    }
}
