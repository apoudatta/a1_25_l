<?php namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\PublicHolidayModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Holidays extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new PublicHolidayModel();
    }

    /** GET /admin/public-holidays */
    public function index()
    {
        // First day of the current year
        $start = date('Y-01-01');

        $rows = $this->model
            ->where('holiday_date >=', $start)   // current year + future years
            ->orderBy('holiday_date', 'DESC')
            ->findAll();

        return view('admin/crud/holidays/index', [
            'rows' => $rows,
        ]);
    }

        


    /** GET /admin/public-holidays/new */
    public function new()
    {
        return view('admin/crud/holidays/form', ['holiday'=>null]);
    }

    /** POST /admin/public-holidays */
    // Optional: put this as a private helper in the same controller
    private function holidayDateExists(string $date, ?int $exceptId = null): bool
    {
        $b = $this->model->where('holiday_date', $date);
        if ($exceptId !== null) {
            $b->where('id !=', $exceptId);
        }
        return $b->countAllResults() > 0;
    }

    public function create()
    {
        $date = (string) $this->request->getPost('holiday_date');

        // if same date already exists, do nothing
        if ($this->holidayDateExists($date)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'A holiday for this date already exists. No changes made.');
        }

        $this->model->insert([
            'holiday_date' => $date,
            'description'  => (string) $this->request->getPost('description'),
            'is_active'    => $this->request->getPost('is_active') ? 1 : 0,
            'created_by'   => session('user_id'),
        ]);

        return redirect()->to('admin/public-holidays')
                        ->with('success', 'Holiday added.');
    }

    /** GET /admin/public-holidays/{id}/edit */
    public function edit($id)
    {
        $h = $this->model->find($id);
        if (! $h) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Holiday #{$id} not found");
        }
        return view('admin/crud/holidays/form', ['holiday' => $h]);
    }

    /** POST /admin/public-holidays/{id} */
    public function update($id)
    {
        $h = $this->model->find($id);
        if (! $h) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Holiday #{$id} not found");
        }

        $date = (string) $this->request->getPost('holiday_date');

        // if another row already has this date, do nothing
        if ($this->holidayDateExists($date, (int) $id)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'A holiday for this date already exists. No changes made.');
        }

        $this->model->update($id, [
            'holiday_date' => $date,
            'description'  => (string) $this->request->getPost('description'),
            'is_active'    => $this->request->getPost('is_active') ? 1 : 0,
            'updated_by'   => session('user_id'),
        ]);

        return redirect()->to('admin/public-holidays')
                        ->with('success', 'Holiday updated.');
    }


    /** DELETE /admin/public-holidays/{id} */
    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('admin/public-holidays')
                         ->with('success','Holiday deleted.');
    }
}
