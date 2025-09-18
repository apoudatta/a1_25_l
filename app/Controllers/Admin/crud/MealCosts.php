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

    /** GET /admin/meal-costs */
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


    /** GET /admin/meal-costs/new */
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

    /** POST /admin/meal-costs */
    public function create()
    {
        $cafeteriaId = $this->request->getPost('cafeteria_id') ?: null;
        $mealTypeId  = $this->request->getPost('meal_type_id');
        $base        = (float) $this->request->getPost('base_price');
        $effective   = $this->request->getPost('effective_date');
        $isActive    = $this->request->getPost('is_active') ? 1 : 0;


        $this->model->insert([
            'cafeteria_id'   => $cafeteriaId,
            'meal_type_id'   => $mealTypeId,
            'base_price'     => $base,
            'effective_date' => $effective,
            'is_active'      => $isActive,
        ]);

        return redirect()->to('admin/meal-costs')
                         ->with('success','Meal cost created.');
    }

    /** GET /admin/meal-costs/{id}/edit */
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

    /** POST /admin/meal-costs/{id} */
    public function update($id)
    {
        $cost = $this->model->find($id);
        if (! $cost) {
            throw new PageNotFoundException("Meal Cost #{$id} not found");
        }

        $cafeteriaId = $this->request->getPost('cafeteria_id') ?: null;
        //$mealTypeId  = $this->request->getPost('meal_type_id');
        $base        = (float) $this->request->getPost('base_price');
        $pct         = (int)   $this->request->getPost('subsidy_pct');
        $effective   = $this->request->getPost('effective_date');
        $isActive    = $this->request->getPost('is_active') ? 1 : 0;

        $final = round($base * (1 - $pct/100), 2);

        $this->model->update($id, [
            'cafeteria_id'   => $cafeteriaId,
            //'meal_type_id'   => $mealTypeId,
            'base_price'     => $base,
            'subsidy_pct'    => $pct,
            'final_price'    => $final,
            'effective_date' => $effective,
            'is_active'      => $isActive,
        ]);

        return redirect()->to('admin/meal-costs')
                         ->with('success','Meal cost updated.');
    }

    /** DELETE /admin/meal-costs/{id} */
    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('admin/meal-costs')
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


}
