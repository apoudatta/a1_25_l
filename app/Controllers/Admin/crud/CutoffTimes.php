<?php namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\CutoffTimeModel;
use App\Models\MealTypeModel;

class CutoffTimes extends BaseController
{
    protected $model;

    public function __construct()
    {
        helper(['form','url']);
        $this->model = new CutoffTimeModel();
    }

    /** GET /admin/cutoff-times */
    public function index()
    {
        $rows = $this->model
            ->select('cutoff_times.*, meal_types.name AS meal_type')
            ->join('meal_types', 'meal_types.id = cutoff_times.meal_type_id', 'left')
            ->orderBy('id', 'DESC')
            ->findAll(); // client-side DT

        return view('admin/crud/cutoff_times/index', [
            'rows' => $rows,
        ]);
    }


    /** GET /admin/cutoff-times/new */
    public function create()
    {
        $mealM = new MealTypeModel();
        return view('admin/crud/cutoff_times/form', [
            'mealTypes'       => $mealM->findAll(),
            'row'        => null,
            'isNew'      => true,
        ]);
    }

    /** POST /admin/cutoff-times */
    public function store()
    {
        $post = $this->request->getPost();

        // normalize time coming from <input type="time"> (HH:MM -> HH:MM:00)
        if (!empty($post['cut_off_time']) && strlen($post['cut_off_time']) === 5) {
            $post['cut_off_time'] .= ':00';
        }

        $rules = [
            'meal_type_id'     => 'required|integer|greater_than[0]',
            // allow HH:MM or HH:MM:SS
            'cut_off_time'     => 'required|regex_match[/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/]',
            'lead_days'        => 'required|integer',
            'max_horizon_days' => 'required|integer|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            // pass the validator so the view can show messages
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $data = [
            'meal_type_id'      => (int)$post['meal_type_id'],
            'cut_off_time'      => $post['cut_off_time'],  // now normalized
            'lead_days'         => (int)$post['lead_days'],
            'max_horizon_days'  => (int)$post['max_horizon_days'],
            'is_active'         => $this->request->getPost('is_active') ? 1 : 0,
        ];

        $this->model->insert($data);

        return redirect()->to('admin/cutoff-times')->with('success','Cut‐off time saved.');
    }


    /** GET /admin/cutoff-times/{id}/edit */
    public function edit($id)
    {
        $mealM = new MealTypeModel();
        $row = $this->model->find($id)
             ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        return view('admin/crud/cutoff_times/form', [
            'mealTypes' => $mealM->findAll(),
            'row'       => $row,
            'isNew'     => false,
        ]);
    }

    /** PUT /admin/cutoff-times/{id} */
    public function update($id)
    {
        $data = $this->validate([
            'cut_off_time'      => 'required',
            'lead_days'         => 'required|integer|greater_than[0]',
            'max_horizon_days'  => 'required|integer|greater_than[0]',
            //'cutoff_date'       => 'permit_empty|valid_date[Y-m-d]',
        ]) 
        ? $this->request->getPost() 
        : null;
        //$this->dd($data);

        // if (! $data) {
        //     return redirect()->back()
        //                      ->withInput()
        //                      ->with('errors', $this->validator->getErrors());
        // }
        if (! $data) {
            return redirect()->back()
                             ->withInput()
                             ->with('validation', \Config\Services::validation());
        }

        $data['is_active'] = $this->request->getPost('is_active') ? 1 : 0;

        $this->model->update($id, $data);

        return redirect()->to('admin/cutoff-times')
                         ->with('success','Cut‐off time updated.');
    }

    /** DELETE /admin/cutoff-times/{id} */
    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('admin/cutoff-times')
                         ->with('success','Cut‐off time removed.');
    }
}
