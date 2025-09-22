<?php namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\ApprovalFlowModel;
use App\Models\MealTypeModel;
use App\Models\EmploymentTypeModel;

class ApprovalFlows extends BaseController
{
    protected $model;
    protected $mealTypeModel;
    protected $empTypeModel;

    public function __construct()
    {
        $this->model         = new ApprovalFlowModel();
        $this->mealTypeModel = new MealTypeModel();
        $this->empTypeModel  = new EmploymentTypeModel();
    }

    public function index()
    {
        // 1) Optional: grab search/sort inputs
        $search  = $this->request->getGet('search');
        $perPage = 10;

        // 2) Build base query (join meal_types + employment_types)
        $qb = $this->model
            ->select('approval_flows.*, mt.name AS meal_type_name, et.name AS emp_type_name')
            ->join('meal_types mt', 'mt.id = approval_flows.meal_type_id')
            // Some legacy rows may have emp_type_id=0 meaning "ALL" → LEFT JOIN (null name shown as ALL in view)
            ->join('employment_types et', 'et.id = approval_flows.emp_type_id', 'left');

        if ($search) {
            $qb->groupStart()
                   ->like('mt.name', $search)
                   ->orLike('et.name', $search)
                   ->orLike('approval_flows.type', $search)
               ->groupEnd();
        }

        // 3) Paginate
        $rows  = $qb->orderBy('approval_flows.id', 'DESC')
                    ->paginate($perPage, 'group1');
        $pager = $this->model->pager;

        // 4) Send to view
        return view('admin/crud/approval_flows/index', [
            'rows'   => $rows,
            'pager'  => $pager,
            'search' => $search,
        ]);
    }

    public function new()
    {
        // Employment types for dropdown (plus "ALL" option with value 0)
        $empTypes = $this->empTypeModel
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('admin/crud/approval_flows/form', [
            'flow'             => null,
            'mealTypes'        => $this->mealTypeModel->findAll(),
            'employmentTypes'  => $empTypes,
            'ALL_VALUE'        => 0, // special "ALL" option
        ]);
    }

    public function create()
    {
        $post = $this->request->getPost();

        $data = [
            'meal_type_id'   => (int) ($post['meal_type_id'] ?? 0),
            'emp_type_id'    => (int) ($post['emp_type_id']   ?? 0), // 0 = ALL (legacy behavior)
            'type'           => $post['type'] ?? 'MANUAL',          // MANUAL|AUTO
            'effective_date' => ($post['effective_date'] ?? '') ?: null,
            'is_active'      => isset($post['is_active']) ? 1 : 0,
        ];

        $this->model->insert($data);

        return redirect()->to('approval-flows')
                         ->with('success', 'Flow created.');
    }

    public function edit($id)
    {
        $flow = $this->model->find($id);
        if (! $flow) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $empTypes = $this->empTypeModel
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('admin/crud/approval_flows/form', [
            'flow'            => $flow,
            'mealTypes'       => $this->mealTypeModel->findAll(),
            'employmentTypes' => $empTypes,
            'ALL_VALUE'       => 0,
        ]);
    }

    public function update($id)
    {
        $post = $this->request->getPost();

        $data = [
            'meal_type_id'   => (int) ($post['meal_type_id'] ?? 0),
            'emp_type_id'    => (int) ($post['emp_type_id']   ?? 0), // 0 = ALL
            'type'           => $post['type'] ?? 'MANUAL',
            'effective_date' => ($post['effective_date'] ?? '') ?: null,
            'is_active'      => isset($post['is_active']) ? 1 : 0,
        ];

        $this->model->update($id, $data);

        return redirect()->to('approval-flows')
                         ->with('success', 'Flow updated.');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('approval-flows')
                         ->with('success', 'Flow deleted.');
    }
}
