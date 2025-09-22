<?php namespace App\Controllers\Admin\crud;

use App\Controllers\BaseController;
use App\Models\MealTypeModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class MealTypes extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new MealTypeModel();
    }

    public function index()
    {
        $types = $this->model->orderBy('name','ASC')->findAll();
        return view('admin/crud/meal_types/index', ['types'=>$types]);
    }

    public function new()
    {
        return view('admin/crud/meal_types/form', [
          'type'=>null,
          'action'=>site_url('meal-types'),
          'button'=>'Create',
        ]);
    }

    public function create()
    {
        $post = $this->request->getPost();
        $this->model->insert([
          'name'        => $post['name'],
          'description' => $post['description'] ?? null,
          'is_active'   => isset($post['is_active']) ? 1 : 0,
        ]);
        return redirect()->to('meal-types')
                         ->with('success','Meal type created.');
    }

    public function edit($id)
    {
        $type = $this->model->find($id);
        if (! $type) throw new PageNotFoundException("Meal type #{$id} not found");
        return view('admin/crud/meal_types/form', [
          'type'   => $type,
          'action' => site_url("meal-types/{$id}"),
          'button' => 'Update',
        ]);
    }

    public function update($id)
    {
        $post = $this->request->getPost();
        $this->model->update($id, [
          'name'        => $post['name'],
          'description' => $post['description'] ?? null,
          'is_active'   => isset($post['is_active']) ? 1 : 0,
        ]);
        return redirect()->to('meal-types')
                         ->with('success','Meal type updated.');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to('meal-types')
                         ->with('success','Meal type deleted.');
    }
}
