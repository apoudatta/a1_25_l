<?php namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\ApprovalFlowModel;
use App\Models\MealTypeModel;

class ApprovalFlows extends BaseController
{
    protected $model;
    protected $mealTypeModel;

    public function __construct()
    {
        $this->model         = new ApprovalFlowModel();
        $this->mealTypeModel = new MealTypeModel();
    }

    /**
     * GET /admin/approval-flows
     */
    public function index()
    {
        // 1) Optional: grab search/sort inputs
        $search = $this->request->getGet('search');
        $perPage = 10;

        // 2) Build base query
        $qb = $this->model
                   ->join('meal_types mt','mt.id = approval_flows.meal_type_id')
                   ->select('approval_flows.*, mt.name AS meal_type_name');

        if ($search) {
            $qb->groupStart()
               ->like('mt.name',       $search)
               ->orLike('approval_flows.user_type', $search)
               ->groupEnd();
        }

        // 3) Paginate
        $rows  = $qb->orderBy('approval_flows.id','DESC')
                    ->paginate($perPage, 'group1');
        $pager = $this->model->pager;

        // 4) Send to view
        return view('admin/crud/approval_flows/index', [
            'rows'        => $rows,
            'pager'       => $pager,
            'search'      => $search,
        ]);
    }

    /**
     * GET /admin/approval-flows/new
     */
    public function new()
    {
        return view('admin/crud/approval_flows/form', [
            'flow'       => null,
            'mealTypes'  => $this->mealTypeModel->findAll(),
            'userTypes'  => ['EMPLOYEE','GUEST','INTERN'],
        ]);
    }

    /**
     * POST /admin/approval-flows
     */
    public function create()
    {
        $post = $this->request->getPost();
        $this->model->insert([
            'meal_type_id'   => $post['meal_type_id'],
            'user_type'      => $post['user_type'],
            'type'           => $post['type'],
            'effective_date' => $post['effective_date'],
            'is_active'      => isset($post['is_active']) ? 1 : 0,
        ]);
        return redirect()->to('admin/approval-flows')
                         ->with('success','Flow created.');
    }

    /**
     * GET /admin/approval-flows/(:num)/edit
     */
    public function edit($id)
    {
        $flow = $this->model->find($id);
        if (! $flow) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return view('admin/crud/approval_flows/form', [
            'flow'       => $flow,
            'mealTypes'  => $this->mealTypeModel->findAll(),
            'userTypes'  => ['EMPLOYEE','GUEST','INTERN'],
        ]);
    }

    /**
     * PUT /admin/approval-flows/(:num)
     */
    public function update($id)
    {
        $post = $this->request->getPost();
        $this->model->update($id, [
            'meal_type_id'   => $post['meal_type_id'],
            'user_type'      => $post['user_type'],
            'type'           => $post['type'],
            'effective_date' => $post['effective_date'],
            'is_active'      => isset($post['is_active']) ? 1 : 0,
        ]);
        return redirect()->to('admin/approval-flows')
                         ->with('success','Flow updated.');
    }

    /**
     * DELETE /admin/approval-flows/(:num)
     */
    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('admin/approval-flows')
                         ->with('success','Flow deleted.');
    }
}