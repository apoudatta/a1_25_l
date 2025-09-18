<?php

namespace App\Controllers\Vendor;

use App\Controllers\BaseController;
use Config\Database;

class Reports extends BaseController
{
    public function index()
{
    $db = \Config\Database::connect();

    $rows = $db->table('meal_tokens mt')
    // CASE selects the proper name based on employment type
    ->select("CASE
                WHEN mt.subscription_table = 'GUEST'  THEN gs.guest_name
                WHEN mt.subscription_table = 'INTERN' THEN ins.intern_name
                ELSE u.name
              END AS name", false)  // false => don't escape the CASE
    ->select([
        'mt.id',
        'mt.meal_date',
        'mt.subscription_table AS employment_type',
        'meal_types.name AS meal_type_name',
        'cafeterias.location AS location',
    ])
    // Employees
    ->join('users u', 'u.id = mt.user_id', 'left')
    // Guests (join only when type is GUEST)
    ->join(
        'guest_subscriptions gs',
        "gs.id = mt.subscription_id AND mt.subscription_table = 'GUEST'",
        'left'
    )
    // Interns (join only when type is INTERN)
    ->join(
        'intern_subscriptions ins',
        "ins.id = mt.subscription_id AND mt.subscription_table = 'INTERN'",
        'left'
    )
    ->join('meal_types', 'meal_types.id = mt.meal_type_id', 'left')
    ->join('cafeterias', 'cafeterias.id = mt.cafeteria_id', 'left')
    ->where('mt.status', 'REDEEMED')
    ->orderBy('mt.meal_date', 'DESC')
    ->orderBy('mt.id', 'DESC')
    ->get()
    ->getResultArray();



    // Employment types (still static unless you store them in a table)
    $employmentTypes = [
        'EMP'           => 'Employee',
        'INTERN'        => 'Intern',
        'GUEST'         => 'Guest',
        'OS'            => 'OS',
        'SECURITY'      => 'Security Guard',
        'SUPPORT_STAFF' => 'Support Staff',
    ];

    // Dynamic dropdowns
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
        'rows'            => $rows,
        'employmentTypes' => $employmentTypes,
        'mealTypes'       => $mealTypes,     // array of ['id','name']
        'locations'       => $locations,     // array of ['id','location']
    ]);
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
