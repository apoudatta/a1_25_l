<?php

namespace App\Controllers\Vendor;

use App\Controllers\BaseController;

class Reports extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        // REDEEMED meals across all time (ordered newest first)
        

        // Employment types (unchanged)
        $employmentTypes = $db->table('employment_types')
            ->select('id, name')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        // Dynamic dropdowns (unchanged)
        $mealTypes = $db->table('meal_types')
            ->select('id, name')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        $locations = $db->table('cafeterias')
            ->select('id, location')
            ->orderBy('location', 'ASC')
            ->get()
            ->getResultArray();

        return view('vendor/reports/index', [
            'employee'        => $this->getMealData([1,2,3,4,5,6,7]),
            'guest'           => $this->getMealData([8,9,10]),
            'employmentTypes' => $employmentTypes,
            'mealTypes'       => $mealTypes,
            'locations'       => $locations,
        ]);
    }

    private function getMealData($empTypeIds) {
        $db = \Config\Database::connect();
        return $db->table('meal_subscriptions ms')
            ->select("
                u.name AS name,
                u.employee_id AS employee_id,
                ms.subs_date AS meal_date,
                ms.id
            ", false)
            ->select([
                'meal_types.name AS meal_type_name',
                'cafeterias.name AS cafeteria_name',
                'employment_types.name AS emp_type_name',
                'meal_reference.ref_name',
            ])
            ->join('users u', 'u.id = ms.user_id', 'left')
            ->join('meal_types', 'meal_types.id = ms.meal_type_id', 'left')
            ->join('cafeterias', 'cafeterias.id = ms.cafeteria_id', 'left')
            ->join('employment_types', 'employment_types.id = ms.emp_type_id', 'left')
            ->join('meal_reference', 'meal_reference.subs_id = ms.id', 'left')
            ->whereIn('ms.emp_type_id', $empTypeIds)
            ->where('ms.status', 'REDEEMED')
            ->orderBy('ms.subs_date', 'DESC')
            ->orderBy('ms.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function download()
    {
        // Placeholder: implement Excel/CSV export logic here
        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="report.csv"')
            ->setBody("Date,Registrations,Consumed\n");
    }
}
