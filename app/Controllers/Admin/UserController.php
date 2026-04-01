<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

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
            'title' => 'จัดการผู้ใช้งาน',
        ];

        return view('admin/users/index', $data);
    }

    // ─── CREATE ──────────────────────────────────────────────────────────────

    public function create()
    {
        return view('admin/users/create', [
            'title' => 'เพิ่มผู้ใช้งานใหม่',
        ]);
    }

    public function store()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'email'    => 'required|valid_email|is_unique[auth_identities.secret]',
            'password' => 'required|min_length[8]',
            'role'     => 'required|in_list[superadmin,manager,nurse]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $users = auth()->getProvider();

        $user = new User([
            'username' => $this->request->getPost('username'),
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ]);

        $users->save($user);

        if ($users->errors()) {
            return redirect()->back()->withInput()->with('errors', $users->errors());
        }

        $newUser = $users->findById($users->getInsertID());
        $newUser->addGroup($this->request->getPost('role'));

        // Auto-approve since superadmin is creating
        $this->userModel->update($newUser->id, ['approval_status' => 'approved']);

        return redirect()->to('admin/users')->with('message', 'เพิ่มผู้ใช้งาน "' . $newUser->username . '" สำเร็จ');
    }

    // ─── EDIT ────────────────────────────────────────────────────────────────

    public function edit($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'ไม่พบผู้ใช้งาน');
        }

        return view('admin/users/edit', [
            'title'    => 'แก้ไขผู้ใช้งาน',
            'editUser' => $user,
        ]);
    }

    public function update($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'ไม่พบผู้ใช้งาน');
        }

        // Prevent superadmin from demoting their own account
        if ($user->inGroup('superadmin') && auth()->id() === $user->id) {
            $role = $this->request->getPost('role');
            if ($role !== 'superadmin') {
                return redirect()->back()->with('error', 'ไม่สามารถเปลี่ยน Role ของบัญชี Superadmin ของตัวเองได้');
            }
        }

        $newRole = $this->request->getPost('role');

        if (!in_array($newRole, ['superadmin', 'manager', 'nurse'], true)) {
            return redirect()->back()->with('error', 'Role ไม่ถูกต้อง');
        }

        // Replace all groups with new role
        $currentGroups = $user->getGroups();
        foreach ($currentGroups as $group) {
            $user->removeGroup($group);
        }
        $user->addGroup($newRole);

        // Update approval status if provided
        $status = $this->request->getPost('approval_status');
        if (in_array($status, ['pending', 'approved', 'deactivated'], true)) {
            $this->userModel->update($id, ['approval_status' => $status]);
        }

        return redirect()->to('admin/users')->with('message', 'อัปเดตข้อมูลผู้ใช้งาน "' . $user->username . '" สำเร็จ');
    }

    // ─── DELETE ──────────────────────────────────────────────────────────────

    public function delete($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'ไม่พบผู้ใช้งาน');
        }

        if ($user->id === auth()->id()) {
            return redirect()->to('admin/users')->with('error', 'ไม่สามารถลบบัญชีของตัวเองได้');
        }

        auth()->getProvider()->delete($id, true);

        return redirect()->to('admin/users')->with('message', 'ลบผู้ใช้งาน "' . $user->username . '" สำเร็จ');
    }

    // ─── STATUS ACTIONS ──────────────────────────────────────────────────────

    public function approve($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'ไม่พบผู้ใช้งาน');
        }

        $this->userModel->update($id, ['approval_status' => 'approved']);

        return redirect()->to('admin/users')->with('message', 'อนุมัติบัญชี "' . $user->username . '" สำเร็จ');
    }

    public function deactivate($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'ไม่พบผู้ใช้งาน');
        }

        if ($user->inGroup('superadmin') && auth()->id() === $user->id) {
            return redirect()->to('admin/users')->with('error', 'ไม่สามารถปิดใช้งานบัญชี Superadmin ของตัวเองได้');
        }

        $this->userModel->update($id, ['approval_status' => 'deactivated']);

        return redirect()->to('admin/users')->with('message', 'ปิดการใช้งานบัญชี "' . $user->username . '" สำเร็จ');
    }

    public function activate($id = null)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('admin/users')->with('error', 'ไม่พบผู้ใช้งาน');
        }

        $this->userModel->update($id, ['approval_status' => 'approved']);

        return redirect()->to('admin/users')->with('message', 'เปิดการใช้งานบัญชี "' . $user->username . '" สำเร็จ');
    }
}
