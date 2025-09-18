<?php namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\ContributionModel;
use App\Models\CafeteriaModel;
use App\Models\MealTypeModel;
use App\Models\MealCostModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Contributions extends BaseController
{
    protected $model;
    protected $cafModel;
    protected $mealTypeModel;
    protected $mealCostModel;

    public function __construct()
    {
        $this->model         = new ContributionModel();
        $this->mealCostModel = new MealCostModel();
        $this->cafModel      = new CafeteriaModel();
        $this->mealTypeModel = new MealTypeModel();
    }

    /** GET /admin/contributions */
	public function index()
    {
        $rows = $this->model
            ->select('meal_contributions.*, meal_types.name AS meal_type, cafeterias.name AS cafeteria')
            ->join('meal_types','meal_types.id=meal_contributions.meal_type_id')
            ->join('cafeterias','cafeterias.id=meal_contributions.cafeteria_id','left')
            ->orderBy('meal_contributions.id','DESC')
            ->findAll(); // client-side DT

        return view('admin/crud/contributions/index', [
            'rows' => $rows,
        ]);
    }

	

    /** GET /admin/crud/contributions/new */
    public function new()
    {
        $db = db_connect();

        // active ET names (uppercase for stable comparisons)
        $etRows  = $db->table('employment_types')
                    ->select('UPPER(name) AS name')
                    ->where('is_active', 1)
                    ->orderBy('name', 'ASC')
                    ->get()->getResultArray();
        $etNames = array_map(fn($r) => (string) $r['name'], $etRows);

        // EMPLOYEE + ETs + GUEST (no duplicates)
        $typeOptions = array_values(array_unique(array_merge(['EMPLOYEE'], $etNames, ['GUEST'])));

        return view('admin/crud/contributions/form', [
            'row'         => null,
            'cafeterias'  => $this->cafModel->findAll(),
            'mealTypes'   => $this->mealTypeModel->findAll(),
            'types' => $typeOptions,
        ]);
    }

    /** POST /admin/crud/contributions */
    public function create()
    {
        $data = [
            'meal_type_id'         => $this->request->getPost('meal_type_id'),
            'user_type'            => $this->request->getPost('user_type'),
            'company_contribution' => (float)$this->request->getPost('company_contribution'),
            'user_contribution'    => (float)$this->request->getPost('user_contribution'),
            'base_price'           => (float)$this->request->getPost('base_price'),
            'company_tk'           => (float)$this->request->getPost('company_tk'),
            'user_tk'              => (float)$this->request->getPost('user_tk'),
            //'cafeteria_id'         => $this->request->getPost('cafeteria_id') ?: null,
            'effective_date'       => $this->request->getPost('effective_date'),
        ];

        $this->model->insert($data);

        return redirect()->to('admin/contributions')
                        ->with('success', 'Contribution rule created.');
    }


    /** GET /admin/contributions/{id}/edit */
    public function edit($id)
    {
        $row = $this->model->find($id);
        if (! $row) {
            throw new PageNotFoundException("Rule #{$id} not found");
        }

        // Build types: EMPLOYEE + active employment_types + GUEST
        $db      = db_connect();
        $etRows  = $db->table('employment_types')
                    ->select('UPPER(name) AS name')
                    ->where('is_active', 1)
                    ->orderBy('name', 'ASC')
                    ->get()->getResultArray();
        $etNames = array_map(static fn($r) => (string) $r['name'], $etRows);

        // Merge and de-dupe
        $types = array_values(array_unique(array_merge(['EMPLOYEE'], $etNames, ['GUEST'])));

        return view('admin/crud/contributions/form', [
            'row'        => $row,
            'cafeterias' => $this->cafModel->findAll(),
            'mealTypes'  => $this->mealTypeModel->findAll(),
            'types'      => $types, // <- merged list
        ]);
    }
    /** POST /admin/contributions/{id} */
    public function update($id)
    {
        $row = $this->model->find($id);
        if (! $row) {
            throw new PageNotFoundException("Rule #{$id} not found");
        }

        $data = [
            'meal_type_id'        => $this->request->getPost('meal_type_id'),
            'user_type'           => $this->request->getPost('user_type'),
            'company_contribution'=> (int)$this->request->getPost('company_contribution'),
            'user_contribution'   => (int)$this->request->getPost('user_contribution'),
            'base_price'           => (float)$this->request->getPost('base_price'),
            'company_tk'           => (float)$this->request->getPost('company_tk'),
            'user_tk'              => (float)$this->request->getPost('user_tk'),
            //'cafeteria_id'        => $this->request->getPost('cafeteria_id') ?: null,
            'effective_date'      => $this->request->getPost('effective_date'),
        ];
        $this->model->update($id, $data);

        return redirect()->to('admin/contributions')
                         ->with('success','Contribution rule updated.');
    }

    /** DELETE /admin/contributions/{id} */
    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('admin/contributions')
                         ->with('success','Contribution rule deleted.');
    }

    public function getBasePrice($mealTypeId)
    {
        $cost = $this->mealCostModel
            ->where('meal_type_id', $mealTypeId)
            ->orderBy('effective_date', 'DESC')
            ->first();

        if ($cost) {
            return $this->response->setJSON([
                'success' => true,
                'base_price' => $cost['base_price']
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'base_price' => 0
        ]);
    }
}
