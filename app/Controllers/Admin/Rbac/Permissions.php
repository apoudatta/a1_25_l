<?php
namespace App\Controllers\Admin\Rbac;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class Permissions extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $data['permissions'] = $db->table('permissions')->orderBy('name')->get()->getResultArray();
        return view('admin/rbac/permissions_index', $data);
    }

    public function store()
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error','Permission name is required');
        }
        $db = db_connect();
        // upsert-like: ignore duplicates
        try {
            $db->table('permissions')->insert(['name'=>$name,'description'=>$this->request->getPost('description')]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error','Duplicate or invalid permission.');
        }
        return redirect()->back()->with('success','Permission created.');
    }

    public function edit(int $id)
    {
        $db = db_connect();
        $perm = $db->table('permissions')->where('id',$id)->get()->getRowArray()
             ?? abort(404, 'Permission not found');
        return view('admin/rbac/permissions_edit', ['perm'=>$perm]);
    }

    public function update(int $id)
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error','Permission name is required');
        }
        $db = db_connect();
        $db->table('permissions')->where('id',$id)->update([
            'name' => $name,
            'description' => $this->request->getPost('description')
        ]);
        return redirect()->to(site_url('permissions'))->with('success','Permission updated.');
    }

    public function delete(int $id)
    {
        $db = db_connect();
        $db->transStart();
        $db->table('role_permissions')->where('permission_id',$id)->delete();
        $db->table('permissions')->where('id',$id)->delete();
        $db->transComplete();

        return redirect()->back()->with('success','Permission deleted.');
    }
}
