<?php
namespace App\Libraries;

use App\Models\CutoffTimeModel;
use App\Models\PublicHolidayModel;

class CutoffResolver
{
    protected $cutoff;
    protected $holiday;

    public function __construct(
        CutoffTimeModel     $cutoff,
        PublicHolidayModel  $holiday
    ) {
        $this->cutoff = $cutoff;
        $this->holiday = $holiday;
    }

    public function getDefault(int $mealTypeId): array
    {
        $row = $this->cutoff
            ->select('max_horizon_days, cut_off_time, lead_days')
            ->where('cutoff_date', null)
            ->where('is_active', 1)
            ->where('meal_type_id', $mealTypeId)
            ->first();

        if (! $row) {
            return [
                'days'       => 30,
                'time'       => '22:00:00',
                'lead_days'  => 1,
            ];
        }

        return [
            'days'       => (int) $row['max_horizon_days'],
            'time'       => $row['cut_off_time'],
            'lead_days'  => (int) $row['lead_days'],
        ];
    }

    public function getHolidays(string $start, string $end): array
    {
        return $this->holiday
            ->select('holiday_date')
            ->where('is_active', 1)
            ->where('holiday_date >=', $start)
            ->where('holiday_date <=', $end)
            ->orderBy('holiday_date','ASC')
            ->findColumn('holiday_date');
    }
}
