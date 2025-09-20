<?php namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\ContributionModel;
use App\Models\MealTypeModel;
use App\Models\EmploymentTypeModel;
use App\Models\CafeteriaModel;

class Contributions extends BaseController
{
    protected $model;
    protected $mealTypeModel;
    protected $empTypeModel;
    protected $cafeteriaModel;

    public function __construct()
    {
        $this->model          = new ContributionModel();
        $this->mealTypeModel  = new MealTypeModel();
        $this->empTypeModel   = new EmploymentTypeModel();
        $this->cafeteriaModel = new CafeteriaModel();
    }

    public function index()
    {
        $search  = trim((string) ($this->request->getGet('search') ?? ''));
        $perPage = 10;

        $qb = $this->model
            ->select("
                meal_contributions.*,
                mt.name AS meal_type_name,
                COALESCE(et.name, 'ALL') AS emp_type_name,
                c.name AS cafeteria_name
            ", false)
            ->join('meal_types mt', 'mt.id = meal_contributions.meal_type_id', 'inner')
            ->join('employment_types et', 'et.id = meal_contributions.emp_type_id', 'left')
            ->join('cafeterias c', 'c.id = meal_contributions.cafeteria_id', 'left');

        if ($search !== '') {
            $qb->groupStart()
                ->like('mt.name', $search)
                ->orLike('et.name', $search)
                ->orLike('c.name',  $search)
              ->groupEnd();
        }

        $rows  = $qb->orderBy('meal_contributions.id', 'DESC')->paginate($perPage, 'group1');
        $pager = $this->model->pager;

        return view('admin/crud/contributions/index', [
            'rows'   => $rows,
            'pager'  => $pager,
            'search' => $search,
        ]);
    }

    public function new()
    {
        $mealTypes   = $this->mealTypeModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
        $empTypes    = $this->empTypeModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
        $cafeterias  = $this->cafeteriaModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll();

        return view('admin/crud/contributions/form', [
            'contrib'         => null,
            'mealTypes'       => $mealTypes,
            'employmentTypes' => $empTypes,
            'cafeterias'      => $cafeterias,
            'ALL_VALUE'       => 0,
        ]);
    }

    public function create()
    {
        $cafeteriaId = $this->request->getPost('cafeteria_id');
        $mealTypeId  = (int) $this->request->getPost('meal_type_id');
        $empTypeId   = (int) ($this->request->getPost('emp_type_id') ?? 0);
        $isActive    = $this->request->getPost('is_active') ? 1 : 0;

        $costRow = $this->resolveCostRow($mealTypeId);
        if (! $costRow || (float) ($costRow['base_price'] ?? 0) <= 0) {
            return redirect()->back()->withInput()->with('error', 'Please insert meal cost first for this meal type.');
        }
        $base   = (float) $costRow['base_price'];

        $companyTk = (float) $this->request->getPost('company_tk');
        $userTk    = (float) $this->request->getPost('user_tk');

        $data = [
            'cafeteria_id' => ($cafeteriaId === '' || $cafeteriaId === null) ? null : (int) $cafeteriaId,
            'meal_type_id' => $mealTypeId,
            'emp_type_id'  => $empTypeId,
            'base_price'   => number_format($base, 2, '.', ''),
            'company_tk'   => number_format($companyTk, 2, '.', ''),
            'user_tk'      => number_format($userTk, 2, '.', ''),
            'is_active'    => $isActive,
        ];

        if (! $this->model->insert($data)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->model->errors()));
        }

        return redirect()->to('admin/contributions')->with('success', 'Contribution created.');
    }

    public function edit($id)
    {
        $row = $this->model->find((int) $id);
        if (! $row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $mealTypes   = $this->mealTypeModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
        $empTypes    = $this->empTypeModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
        $cafeterias  = $this->cafeteriaModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll();

        return view('admin/crud/contributions/form', [
            'contrib'         => $row,
            'mealTypes'       => $mealTypes,
            'employmentTypes' => $empTypes,
            'cafeterias'      => $cafeterias,
            'ALL_VALUE'       => 0,
        ]);
    }

    public function update($id)
    {
        $cafeteriaId = $this->request->getPost('cafeteria_id');
        $mealTypeId  = (int) $this->request->getPost('meal_type_id');
        $empTypeId   = (int) ($this->request->getPost('emp_type_id') ?? 0);
        $isActive    = $this->request->getPost('is_active') ? 1 : 0;

        $costRow = $this->resolveCostRow($mealTypeId);
        if (! $costRow || (float) ($costRow['base_price'] ?? 0) <= 0) {
            return redirect()->back()->withInput()->with('error', 'Please insert meal cost first for this meal type.');
        }
        $base   = (float) $costRow['base_price'];

        $companyTk = (float) $this->request->getPost('company_tk');
        $userTk    = (float) $this->request->getPost('user_tk');

        $data = [
            'cafeteria_id' => ($cafeteriaId === '' || $cafeteriaId === null) ? null : (int) $cafeteriaId,
            'meal_type_id' => $mealTypeId,
            'emp_type_id'  => $empTypeId,
            'base_price'   => number_format($base, 2, '.', ''),
            'company_tk'   => number_format($companyTk, 2, '.', ''),
            'user_tk'      => number_format($userTk, 2, '.', ''),
            'is_active'    => $isActive,
        ];

        if (! $this->model->update((int) $id, $data)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->model->errors()));
        }

        return redirect()->to('admin/contributions')->with('success', 'Contribution updated.');
    }

    public function delete($id)
    {
        $this->model->delete((int) $id);
        return redirect()->to('admin/contributions')->with('success', 'Contribution deleted.');
    }

    /**
     * Toggle active flag. Works with normal POST or AJAX.
     * POST /admin/contributions/{id}/toggle
     */
   // app/Controllers/Admin/crud/Contributions.php

    public function toggle($id)
    {
        $id  = (int) $id;
        $row = $this->model->find($id);
        if (! $row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $new = $row['is_active'] ? 0 : 1;

        if ($this->model->update($id, ['is_active' => $new]) === false) {
            $err = implode(' ', (array) $this->model->errors()) ?: 'Unable to update status.';
            return redirect()->back()->with('error', $err);
        }

        return redirect()->back()->with('success', $new ? 'Contribution activated.' : 'Contribution deactivated.');
    }


    // ---------- helpers ----------

    /** Get the applicable meal_costs row for a meal type (effective or upcoming). */
    public function resolveCostRow(int $mealTypeId): ?array
    {
        if ($mealTypeId <= 0) return null;

        $db    = db_connect();
        $today = date('Y-m-d');

        $row = $db->table('meal_costs')
            ->select('base_price, effective_date')
            ->where('meal_type_id', $mealTypeId)
            ->where('is_active', 1)
            ->where('effective_date <=', $today)
            ->orderBy('effective_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->get(1)->getFirstRow('array');

        if ($row) return $row;

        $row = $db->table('meal_costs')
            ->select('base_price, effective_date')
            ->where('meal_type_id', $mealTypeId)
            ->where('is_active', 1)
            ->where('effective_date >', $today)
            ->orderBy('effective_date', 'ASC')
            ->orderBy('id', 'DESC')
            ->get(1)->getFirstRow('array');

        return $row ?: null;
    }

    public function getBasePrice(int $mealTypeId)
    {
        if ($mealTypeId <= 0) return null;

        $db    = db_connect();
        $today = date('Y-m-d');

        $row = $db->table('meal_costs')
            ->select('base_price')
            ->where('meal_type_id', $mealTypeId)
            ->where('is_active', 1)
            ->where('effective_date <=', $today)
            ->orderBy('effective_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->get(1)->getFirstRow('array');

        if ($row) {
            return $this->response->setJSON([
                'success' => true,
                'base_price' => $row['base_price']
            ]);
        }

        $row = $db->table('meal_costs')
            ->select('base_price')
            ->where('meal_type_id', $mealTypeId)
            ->where('is_active', 1)
            ->where('effective_date >', $today)
            ->orderBy('effective_date', 'ASC')
            ->orderBy('id', 'DESC')
            ->get(1)->getFirstRow('array');

        if ($row) {
            return $this->response->setJSON([
                'success' => true,
                'base_price' => $row['base_price']
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'base_price' => 0
        ]);
    }
}
