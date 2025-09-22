<?php namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\CafeteriaModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Cafeterias extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new CafeteriaModel();
    }

    public function index()
    {
        // Fetch everything; client-side DataTables will handle search/sort/paging
        $cafeterias = $this->model->orderBy('id', 'desc')->findAll();

        return view('admin/crud/cafeterias/index', [
            'cafeterias' => $cafeterias,
        ]);
    }



    /** GET /admin/crud/cafeterias/new */
    public function new()
    {
        return view('admin/crud/cafeterias/form', [
            'cafeteria' => null
        ]);
    }

    /** POST /admin/crud/cafeterias */
    public function create()
    {
        $post = $this->request->getPost();

        // normalize inputs (trim + collapse multiple spaces)
        $name     = trim(preg_replace('/\s+/', ' ', (string)($post['name'] ?? '')));
        $location = trim(preg_replace('/\s+/', ' ', (string)($post['location'] ?? '')));

        if ($name === '') {
            return redirect()->back()->with('error', 'Name is required.')->withInput();
        }

        // duplicate check by name (collation is *_ci so case-insensitive)
        $exists = $this->model->where('name', $name)->first();
        if ($exists) {
            return redirect()->back()
                ->with('error', 'A cafeteria with this name already exists.')
                ->withInput();
        }

        $this->model->insert([
            'name'      => $name,
            'location'  => $location !== '' ? $location : null,
            'is_active' => isset($post['is_active']) ? 1 : 0,
        ]);

        return redirect()->to('cafeterias')->with('success', 'Cafeteria created.');
    }

    public function update($id)
    {
        $caf = $this->model->find($id);
        if (!$caf) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Cafeteria #{$id} not found");
        }

        $post = $this->request->getPost();

        $name     = trim(preg_replace('/\s+/', ' ', (string)($post['name'] ?? '')));
        $location = trim(preg_replace('/\s+/', ' ', (string)($post['location'] ?? '')));

        if ($name === '') {
            return redirect()->back()->with('error', 'Name is required.')->withInput();
        }

        // duplicate check by name, excluding current row
        $exists = $this->model->where('name', $name)
                            ->where('id !=', (int)$id)
                            ->first();
        if ($exists) {
            return redirect()->back()
                ->with('error', 'Another cafeteria with the same name already exists.')
                ->withInput();
        }

        $this->model->update($id, [
            'name'      => $name,
            'location'  => $location !== '' ? $location : null,
            'is_active' => isset($post['is_active']) ? 1 : 0,
        ]);

        return redirect()->to('cafeterias')->with('success', 'Cafeteria updated.');
    }


    /** GET /admin/crud/cafeterias/{id}/edit */
    public function edit($id)
    {
        $caf = $this->model->find($id);
        if (! $caf) {
            throw new PageNotFoundException("Cafeteria #{$id} not found");
        }
        return view('admin/crud/cafeterias/form', [
            'cafeteria' => $caf
        ]);
    }
    

    /** DELETE /cafeterias/{id} */
    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('cafeterias')
                         ->with('success','Cafeteria deleted.');
    }
}
