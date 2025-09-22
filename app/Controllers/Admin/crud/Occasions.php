<?php namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\OccasionModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Occasions extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new OccasionModel();
    }

    public function index()
    {
        // Show from (current year - 1) Jan 1 through all future dates
        $start = sprintf('%04d-01-01', (int)date('Y') - 1);

        $rows = $this->model
            ->where('occasion_date >=', $start)   // exclude anything older than last year
            ->orderBy('occasion_date', 'DESC')
            ->findAll();                          // client-side DataTable

        return view('admin/crud/occasions/index', [
            'rows' => $rows,
        ]);
    }


    public function new()
    {
        return view('admin/crud/occasions/form', ['row'=>null]);
    }

    private function occasionDateExists(string $date, ?int $exceptId = null): bool
    {
        $b = $this->model->where('occasion_date', $date);
        if ($exceptId !== null) {
            $b->where('id !=', $exceptId);
        }
        return $b->countAllResults() > 0;
    }

    public function create()
    {
        $name = trim((string) $this->request->getPost('name'));
        $date = trim((string) $this->request->getPost('occasion_date'));

        // Block duplicate date
        if ($this->occasionDateExists($date)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An occasion with this date already exists. No changes made.');
        }

        $this->model->insert([
            'name'          => $name,
            'occasion_date' => $date,
        ]);

        return redirect()->to('occasions')->with('success', 'Occasion added.');
    }

    public function edit($id)
    {
        $row = $this->model->find($id);
        if (! $row) {
            throw new PageNotFoundException("Occasion #{$id} not found");
        }
        return view('admin/crud/occasions/form', ['row' => $row]);
    }

    public function update($id)
    {
        $row = $this->model->find($id);
        if (! $row) {
            throw new PageNotFoundException("Occasion #{$id} not found");
        }

        $name = trim((string) $this->request->getPost('name'));
        $date = trim((string) $this->request->getPost('occasion_date'));

        // Block duplicate date (excluding this row)
        if ($this->occasionDateExists($date, (int) $id)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An occasion with this date already exists. No changes made.');
        }

        $this->model->update($id, [
            'name'          => $name,
            'occasion_date' => $date,
        ]);

        return redirect()->to('occasions')->with('success', 'Occasion updated.');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('occasions')
                         ->with('success','Occasion deleted.');
    }
}
