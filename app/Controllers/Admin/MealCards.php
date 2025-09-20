<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MealCardModel;
use Config\Services;

class MealCards extends BaseController
{
    protected MealCardModel $cards;

    public function __construct()
    {
        $this->cards = new MealCardModel();
        helper(['form', 'auth']); // auth has can()
    }

    public function index()
    {
        // Build once, no server paging
        $builder = $this->cards->builder()
            ->select('meal_cards.*, users.name AS user_name, users.email AS user_email')
            ->join('users', 'users.id = meal_cards.user_id', 'left')
            ->orderBy('meal_cards.id', 'DESC');

        $rows = $builder->get()->getResultArray();

        return view('admin/meal_cards/index', [
            'rows'   => $rows,
            // these are only used by the client-side filters below
            'q'      => (string) $this->request->getGet('q'),
            'status' => (string) $this->request->getGet('status'),
        ]);
    }

    public function new()
    {
        return view('admin/meal_cards/form', [
            'mode'  => 'create',
            'row'   => ['user_id'=>'','employee_id'=>'','card_code'=>'','status'=>'ACTIVE'],
            'title' => 'Add Meal Card',
        ]);
    }

    public function store()
    {
        $data   = $this->request->getPost();

        // (keep any other validation you want, but NOT user_id is_unique)
        $rules = [
            'user_id'     => 'permit_empty|is_natural_no_zero',
            'employee_id' => 'permit_empty|max_length[20]',
            'card_code'   => 'required|max_length[64]', // keep unique via DB/index if you want
            'status'      => 'required|in_list[ACTIVE,INACTIVE]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $userId = (int)($data['user_id'] ?? 0) ?: null;

        // DB query check: same user already has a card?
        if ($userId !== null) {
            $dupe = $this->cards
                ->select('id, card_code')
                ->where('user_id', $userId)
                ->first();

            if ($dupe) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "This user already has a meal card (Code: {$dupe['card_code']}).");
            }
        }

        $payload = [
            'user_id'     => $userId,
            'employee_id' => $data['employee_id'] ?: null,
            'card_code'   => trim($data['card_code']),
            'status'      => $data['status'],
        ];

        $this->cards->insert($payload);

        return redirect()->to(site_url('admin/meal-cards'))
                        ->with('success', 'Meal card created.');
    }


    public function edit($id)
    {
        $row = $this->cards->find($id);
        if (! $row) {
            return redirect()->to(site_url('admin/meal-cards'))->with('error', 'Meal card not found.');
        }

        return view('admin/meal_cards/form', [
            'mode'  => 'edit',
            'row'   => $row,
            'title' => 'Edit Meal Card',
        ]);
    }

    public function update($id)
    {
        $row = $this->cards->find($id);
        if (! $row) {
            return redirect()->to(site_url('admin/meal-cards'))
                            ->with('error', 'Meal card not found.');
        }

        $data = $this->request->getPost();

        // keep basic validation, no is_unique here
        $rules = [
            'user_id'     => 'permit_empty|is_natural_no_zero',
            'employee_id' => 'permit_empty|max_length[20]',
            'status'      => 'required|in_list[ACTIVE,INACTIVE]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $userId = (int)($data['user_id'] ?? 0) ?: null;

        // DB query check: any other card with this user?
        if ($userId !== null) {
            $dupe = $this->cards
                ->select('id, card_code')
                ->where('user_id', $userId)
                ->where('id !=', $id)
                ->first();

            if ($dupe) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "This user already has another meal card (Code: {$dupe['card_code']}).");
            }
        }

        $payload = [
            'user_id'     => $userId,
            'employee_id' => $data['employee_id'] ?: null,
            'status'      => $data['status'],
        ];

        $this->cards->update($id, $payload);

        return redirect()->to(site_url('admin/meal-cards'))
                        ->with('success', 'Meal card updated.');
    }


    public function delete($id)
    {
        // CSRF + POST expected
        $this->cards->delete($id);
        return redirect()->to(site_url('admin/meal-cards'))
                         ->with('success', 'Meal card deleted.');
    }
}
