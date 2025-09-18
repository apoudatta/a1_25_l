<?php

namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\EmploymentTypeModel;

class EmploymentTypes extends BaseController
{
    protected EmploymentTypeModel $types;

    public function __construct()
    {
        $this->types = new EmploymentTypeModel();
        helper(['form', 'url']);
    }

    public function index()
    {
        $rows = $this->types->orderBy('name', 'asc')->findAll();

        return view('admin/crud/employment_types/index', [
            'rows' => $rows,
        ]);
    }

    public function create()
    {
        // default active
        $row = ['is_active' => 1];

        return view('admin/crud/employment_types/form', compact('row'));
    }

    public function edit(int $id)
    {
        $row = $this->types->find($id);
        if (! $row) {
            return redirect()->to(site_url('admin/employment-types'))
                             ->with('error', 'Employment Type not found.');
        }
        return view('admin/crud/employment_types/form', compact('row'));
    }

    public function store()
    {
        $id = (int) ($this->request->getPost('id') ?? 0);

        $data = [
            'id'          => $id ?: null,
            'name'        => trim((string) $this->request->getPost('name')),
            'description' => trim((string) $this->request->getPost('description')),
            'is_active'   => (int) ($this->request->getPost('is_active') ?? 1),
        ];

        if (! $this->types->save($data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->types->errors());
        }

        return redirect()->to(site_url('admin/employment-types'))
                         ->with('success', 'Employment Type saved successfully.');
    }

    public function delete(int $id)
    {
        $row = $this->types->find($id);
        if (! $row) {
            return redirect()->to(site_url('admin/employment-types'))
                             ->with('error', 'Employment Type not found.');
        }

        $this->types->delete($id);

        return redirect()->to(site_url('admin/employment-types'))
                         ->with('success', 'Employment Type deleted.');
    }

    public function toggle(int $id)
    {
        $row = $this->types->find($id);
        if (! $row) {
            return redirect()->to(site_url('admin/employment-types'))
                             ->with('error', 'Employment Type not found.');
        }

        $row['is_active'] = (int)!$row['is_active'];
        $this->types->save($row);

        return redirect()->to(site_url('admin/employment-types'))
                         ->with('success', 'Status updated.');
    }
}
