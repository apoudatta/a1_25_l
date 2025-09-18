<?php
namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\ApprovalStepModel;
use App\Models\RoleModel;
use App\Models\UserModel;

class ApprovalSteps extends BaseController
{
    protected $stepModel;
    protected $roleModel;
    protected $userModel;

    public function __construct()
    {
        $this->stepModel = new ApprovalStepModel();
        $this->roleModel = new RoleModel();
        $this->userModel = new UserModel();
    }

    // List steps
    public function index($flowId)
    {
        $steps = $this->stepModel
                      ->where('flow_id', $flowId)
                      ->orderBy('step_order','ASC')
                      ->findAll();

        return view('admin/crud/approval_steps/index', [
            'flowId' => $flowId,
            'steps'  => $steps,
            'roles'  => array_column($this->roleModel->findAll(),'name','id'),
        ]);
    }

    // Show “new” form
    public function create($flowId)
    {
        return view('admin/crud/approval_steps/form', [
            'isNew'    => true,
            'flowId'   => $flowId,
            'step'     => null,
            'roles'    => $this->roleModel->findAll(),
            'users'    => $this->userModel->findAll(),
            'types'    => ['ROLE','USER','LINE_MANAGER'],
        ]);
    }

    // Handle form submit for new
    public function store($flowId)
    {
        $post = $this->request->getPost();
        $data = [
            'flow_id'        => $flowId,
            'step_order'     => (int)$post['step_order'],
            'approver_type'  => $post['approver_type'],
            // only one of the next three ever gets set:
            'approver_role'       => $post['approver_type']==='ROLE'
                                   ? $post['approver_role']         : null,
            'approver_user_id'    => $post['approver_type']==='USER'
                                   ? $post['approver_user_id']      : null,
            'fallback_role'       => $post['approver_type']==='LINE_MANAGER'
                                   ? $post['fallback_role']         : null,
        ];

        $this->stepModel->insert($data);
        return redirect()->to("admin/approval-flows/{$flowId}/steps")
                         ->with('success','Step added.');
    }

    // Show “edit” form
    public function edit($flowId, $stepId)
    {
        $step = $this->stepModel->find($stepId)
              ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        return view('admin/crud/approval_steps/form', [
            'isNew'    => false,
            'flowId'   => $flowId,
            'step'     => $step,
            'roles'    => $this->roleModel->findAll(),
            'users'    => $this->userModel->findAll(),
            'types'    => ['ROLE','USER','LINE_MANAGER'],
        ]);
    }

    // Handle “edit” submit
    public function update($flowId, $stepId)
    {
        $post = $this->request->getPost();
        $data = [
            'step_order'     => (int)$post['step_order'],
            'approver_type'  => $post['approver_type'],
            'approver_role'       => $post['approver_type']==='ROLE'
                                   ? $post['approver_role']         : null,
            'approver_user_id'    => $post['approver_type']==='USER'
                                   ? $post['approver_user_id']      : null,
            'fallback_role'       => $post['approver_type']==='LINE_MANAGER'
                                   ? $post['fallback_role']         : null,
        ];

        $this->stepModel->update($stepId, $data);
        return redirect()->to("admin/approval-flows/{$flowId}/steps")
                         ->with('success','Step updated.');
    }

    // Delete a step
    public function delete($flowId, $stepId)
    {
        $this->stepModel->delete($stepId);
        return redirect()->to("admin/approval-flows/{$flowId}/steps")
                         ->with('success','Step removed.');
    }
}
