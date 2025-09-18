<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

class Roles extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function index()
    {
        $roles = $this->db->table('roles')
                ->where('name !=', 'SUPER ADMIN')
                ->orderBy('name')->get()
                ->getResultArray();
        return view('admin/roles/index', ['roles' => $roles]);
    }

    public function create()
    {
        return view('admin/roles/create');
    }

    public function store()
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error', 'Role name is required');
        }
        $exists = $this->db->table('roles')->where('name', $name)->countAllResults();
        if ($exists) {
            return redirect()->back()->with('error', 'Role already exists');
        }
        $this->db->table('roles')->insert([
            'name'        => $name,
            'description' => $this->request->getPost('description'),
        ]);
        return redirect()->to(site_url('admin/roles'))->with('success', 'Role created.');
    }

    public function edit(int $id)
    {
        $role = $this->db->table('roles')->where('id', $id)->get()->getRowArray();
        if (! $role) {
            throw PageNotFoundException::forPageNotFound('Role not found');
        }
        return view('admin/roles/edit', ['role' => $role]);
    }

    public function update(int $id)
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error', 'Role name is required');
        }
        $exists = $this->db->table('roles')
            ->where('name', $name)
            ->where('id !=', $id)
            ->countAllResults();
        if ($exists) {
            return redirect()->back()->with('error', 'Role already exists');
        }
        $this->db->table('roles')->where('id', $id)->update([
            'name'        => $name,
            'description' => $this->request->getPost('description'),
        ]);
        return redirect()->to(site_url('admin/roles'))->with('success', 'Role updated.');
    }

    public function delete(int $id)
    {
        $this->db->transStart();
        $this->db->table('role_permissions')->where('role_id', $id)->delete();
        $this->db->table('user_roles')->where('role_id', $id)->delete();
        $this->db->table('roles')->where('id', $id)->delete();
        $this->db->transComplete();

        return redirect()->to(site_url('admin/roles'))->with('success', 'Role deleted.');
    }
}
