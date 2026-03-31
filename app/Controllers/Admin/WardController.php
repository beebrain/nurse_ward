<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\WardModel;

class WardController extends BaseController
{
    protected $wardModel;

    public function __construct()
    {
        $this->wardModel = new WardModel();
    }

    public function index()
    {
        $data = [
            'wards' => $this->wardModel->findAll(),
            'title' => 'Manage Wards',
        ];

        return view('admin/wards/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Create New Ward',
        ];

        return view('admin/wards/create', $data);
    }

    public function store()
    {
        $rules = [
            'name'       => 'required|max_length[100]|min_length[3]',
            'total_beds' => 'required|numeric|greater_than_equal_to[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->wardModel->save([
            'name'       => $this->request->getPost('name'),
            'total_beds' => $this->request->getPost('total_beds'),
            'is_active'  => $this->request->getPost('is_active') ? true : false,
        ]);

        return redirect()->to('admin/wards')->with('message', 'Ward created successfully.');
    }

    public function edit($id = null)
    {
        $ward = $this->wardModel->find($id);

        if (!$ward) {
            return redirect()->to('admin/wards')->with('error', 'Ward not found.');
        }

        $data = [
            'ward'  => $ward,
            'title' => 'Edit Ward',
        ];

        return view('admin/wards/edit', $data);
    }

    public function update($id = null)
    {
        $rules = [
            'name'       => 'required|max_length[100]|min_length[3]',
            'total_beds' => 'required|numeric|greater_than_equal_to[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->wardModel->update($id, [
            'name'       => $this->request->getPost('name'),
            'total_beds' => $this->request->getPost('total_beds'),
            'is_active'  => $this->request->getPost('is_active') ? true : false,
        ]);

        return redirect()->to('admin/wards')->with('message', 'Ward updated successfully.');
    }

    public function delete($id = null)
    {
        if ($this->wardModel->delete($id)) {
            return redirect()->to('admin/wards')->with('message', 'Ward deleted successfully.');
        }

        return redirect()->to('admin/wards')->with('error', 'Failed to delete ward.');
    }
}
