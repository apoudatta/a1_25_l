<?php namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\RamadanConfigModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class RamadanConfigs extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new RamadanConfigModel();
    }

   /** GET /admin/ramadan-periods */
   public function index()
    {
        $minYear = (int) date('Y') - 1;   // show last year and newer

        $rows = $this->model
            ->where('year >=', $minYear)
            ->orderBy('year', 'DESC')
            ->findAll(); // client-side DataTable

        return view('admin/crud/ramadan_periods/index', [
            'rows' => $rows,
        ]);
    }

   


    /** GET /admin/ramadan-periods/new */
    public function new()
    {
        return view('admin/crud/ramadan_periods/form', ['row' => null]);
    }

    /** POST /admin/ramadan-periods */
    public function create()
    {
        $year = (int) $this->request->getPost('year');

        // block duplicates
        $exists = $this->model->where('year', $year)->countAllResults();
        if ($exists > 0) {
            return redirect()->back()->withInput()->with('error', "Year {$year} already exists.");
        }

        $this->model->insert([
            'year'       => $year,
            'start_date' => $this->request->getPost('start_date'),
            'end_date'   => $this->request->getPost('end_date'),
        ]);

        return redirect()->to('admin/ramadan-periods')
                        ->with('success', 'Ramadan period added.');
    }

    /** GET /admin/ramadan-periods/{id}/edit */
    public function edit($id)
    {
        $row = $this->model->find($id);
        if (! $row) {
            throw new PageNotFoundException("Period #{$id} not found");
        }
        return view('admin/crud/ramadan_periods/form', ['row' => $row]);
    }

    /** POST /admin/ramadan-periods/{id} */
    public function update($id)
    {
        $row = $this->model->find($id);
        if (! $row) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Period #{$id} not found");
        }

        $year = (int) $this->request->getPost('year');

        // block duplicates except the current record
        $exists = $this->model->where('year', $year)
                            ->where('id !=', $id)
                            ->countAllResults();
        if ($exists > 0) {
            return redirect()->back()->withInput()->with('error', "Year {$year} already exists.");
        }

        $this->model->update($id, [
            'year'       => $year,
            'start_date' => $this->request->getPost('start_date'),
            'end_date'   => $this->request->getPost('end_date'),
        ]);

        return redirect()->to('admin/ramadan-periods')
                        ->with('success', 'Ramadan period updated.');
    }


    /** DELETE /admin/ramadan-periods/{id} */
    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('admin/ramadan-periods')
                         ->with('success','Ramadan period deleted.');
    }
}
