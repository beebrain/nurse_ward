<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserWardModel;
use App\Models\WardModel;
use App\Models\UserModel;

class NurseWardController extends BaseController
{
    protected $userWardModel;
    protected $wardModel;
    protected $userModel;

    public function __construct()
    {
        $this->userWardModel = new UserWardModel();
        $this->wardModel     = new WardModel();
        $this->userModel     = new UserModel();
    }

    /** List all nurses with their ward assignments. */
    public function index()
    {
        return view('admin/nurse_wards/index', [
            'title'  => 'จัดการสิทธิ์ Ward ของ Nurse',
            'nurses' => $this->userWardModel->getNursesWithAssignments(),
        ]);
    }

    /** Show assignment form for one nurse. */
    public function edit(int $userId)
    {
        $user = $this->userModel->find($userId);
        if (! $user || ! $user->inGroup('nurse')) {
            return redirect()->to('admin/nurse-wards')->with('error', 'ไม่พบ Nurse ที่ระบุ');
        }

        $allWards       = $this->wardModel->getActiveWithDepartment();
        $assignedIds    = $this->userWardModel->getWardIdsForUser($userId);

        return view('admin/nurse_wards/edit', [
            'title'       => 'กำหนด Ward: ' . $user->username,
            'nurseUser'   => $user,
            'allWards'    => $allWards,
            'assignedIds' => $assignedIds,
        ]);
    }

    /** Save ward assignments for one nurse. */
    public function update(int $userId)
    {
        $user = $this->userModel->find($userId);
        if (! $user || ! $user->inGroup('nurse')) {
            return redirect()->to('admin/nurse-wards')->with('error', 'ไม่พบ Nurse ที่ระบุ');
        }

        $wardId = (int)($this->request->getPost('ward_id') ?? 0);
        if ($wardId <= 0) {
            return redirect()->back()->with('error', 'กรุณาเลือก Ward สำหรับผู้กรอกข้อมูล');
        }

        $owner = $this->userWardModel->getAssignedUserForWard($wardId, $userId);
        if ($owner) {
            return redirect()->back()->with('error', 'Ward นี้ถูกกำหนดให้ผู้ใช้ "' . $owner['username'] . '" แล้ว');
        }

        $this->userWardModel->syncUserWards($userId, [$wardId]);

        return redirect()->to('admin/nurse-wards')
            ->with('message', 'บันทึกสิทธิ์ Ward ของ "' . $user->username . '" สำเร็จ');
    }
}
