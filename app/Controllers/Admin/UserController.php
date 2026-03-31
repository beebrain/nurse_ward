<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class UserController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $data = [
            'users' => $this->userModel->findAll(),
            'title' => 'Manage Users',
        ];

        return view('admin/users/index', $data);
    }

    public function approve($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'User not found.');
        }

        $this->userModel->update($id, ['approval_status' => 'approved']);

        return redirect()->to('admin/users')->with('message', 'User account approved successfully.');
    }

    public function deactivate($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'User not found.');
        }

        if ($user->inGroup('superadmin') && auth()->id() === $user->id) {
            return redirect()->to('admin/users')->with('error', 'You cannot deactivate your own superadmin account.');
        }

        $this->userModel->update($id, ['approval_status' => 'deactivated']);

        return redirect()->to('admin/users')->with('message', 'User account deactivated successfully.');
    }

    public function activate($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'User not found.');
        }

        $this->userModel->update($id, ['approval_status' => 'approved']);

        return redirect()->to('admin/users')->with('message', 'User account activated successfully.');
    }
}
