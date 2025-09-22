<?php namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\MealCostModel;
use App\Models\CafeteriaModel;
use App\Models\MealTypeModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class MealCosts extends BaseController
{
    protected $model;
    protected $cafModel;
    protected $mealTypeModel;

    public function __construct()
    {
        $this->model          = new MealCostModel();
        $this->cafModel       = new CafeteriaModel();
        $this->mealTypeModel  = new MealTypeModel();
    }

    public function index()
    {
        // Client-side table: load all rows (with joins for labels)
        $costs = $this->model
            ->select('meal_costs.*, cafeterias.name AS cafeteria, meal_types.name AS meal_type')
            ->join('cafeterias','cafeterias.id = meal_costs.cafeteria_id','left')
            ->join('meal_types','meal_types.id = meal_costs.meal_type_id','left')
            ->orderBy('effective_date', 'DESC')
            ->findAll();

        return view('admin/crud/meal_costs/index', ['costs' => $costs]);
    }


    public function new()
    {
        $cafeterias = $this->cafModel->findAll();
        $mealTypes  = $this->mealTypeModel->select('id,name')->where('is_active', '1')->findAll();

        return view('admin/crud/meal_costs/form', [
            'cost'        => null,
            'cafeterias'  => $cafeterias,
            'mealTypes'   => $mealTypes,
        ]);
    }

    public function create()
    {
        $cafeteriaId = $this->request->getPost('cafeteria_id');
        $mealTypeId  = (int) $this->request->getPost('meal_type_id');
        $base        = (float) $this->request->getPost('base_price');
        $effective   = $this->request->getPost('effective_date');
        $isActive    = $this->request->getPost('is_active') ? 1 : 0;

        $data = [
            'cafeteria_id'   => ($cafeteriaId === '' || $cafeteriaId === null) ? null : (int) $cafeteriaId,
            'meal_type_id'   => $mealTypeId,
            'base_price'     => number_format($base, 2, '.', ''),
            'effective_date' => $effective,
            'is_active'      => $isActive,
            // created_at omitted; DB will set CURRENT_TIMESTAMP
        ];

        if (! $this->model->insert($data)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->model->errors()));
        }

        return redirect()->to('meal-costs')->with('success', 'Meal cost created.');
    }

    public function edit($id)
    {
        $cost = $this->model->find($id);
        if (! $cost) {
            throw new PageNotFoundException("Meal Cost #{$id} not found");
        }

        $cafeterias = $this->cafModel->findAll();
        $mealTypes  = $this->mealTypeModel->findAll();

        return view('admin/crud/meal_costs/form', [
            'cost'        => $cost,
            'cafeterias'  => $cafeterias,
            'mealTypes'   => $mealTypes,
        ]);
    }

    public function update($id)
    {
        $cafeteriaId = $this->request->getPost('cafeteria_id');
        $mealTypeId  = (int) $this->request->getPost('meal_type_id');
        $base        = (float) $this->request->getPost('base_price');
        $effective   = $this->request->getPost('effective_date');
        $isActive    = $this->request->getPost('is_active') ? 1 : 0;

        $data = [
            'cafeteria_id'   => ($cafeteriaId === '' || $cafeteriaId === null) ? null : (int) $cafeteriaId,
            'meal_type_id'   => $mealTypeId,
            'base_price'     => number_format($base, 2, '.', ''),
            'effective_date' => $effective,
            'is_active'      => $isActive,
        ];

        if (! $this->model->update((int) $id, $data)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->model->errors()));
        }

        return redirect()->to('meal-costs')->with('success', 'Meal cost updated.');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('meal-costs')
                         ->with('success','Meal cost deleted.');
    }

    public function toggle(int $id)
    {
        $row = $this->model->select('is_active')->find($id);
        if (! $row) {
            return redirect()->back()->with('success', "Meal Cost #{$id} not found");
        }

        $new = $row['is_active'] ? 0 : 1;

        // Update by primary key (requires is_active in $allowedFields)
        $this->model->update($id, ['is_active' => $new]);

        return redirect()->back()->with('success', 'Status updated.');
    }

    public function horizon($mealTypeId)
    {
        $mealTypeId = (int) $mealTypeId;
        $db = db_connect();

        // Pick the most recent active cutoff for this meal type
        $row = $db->table('cutoff_times')
            ->select('max_horizon_days')
            ->where('meal_type_id', $mealTypeId)
            ->where('is_active', 1)
            ->orderBy('cutoff_date', 'DESC')   // may be NULL, that's ok
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getFirstRow('array');

        $days = (int) ($row['max_horizon_days'] ?? 0);
        return $this->response->setJSON(['max_horizon_days' => $days]);
    }

}
