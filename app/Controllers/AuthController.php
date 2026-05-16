<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AuthController extends BaseController
{
    public function pending()
    {
        return view('auth/pending', ['title' => 'Account Pending Approval']);
    }

    public function deactivated()
    {
        return view('auth/deactivated', ['title' => 'Account Deactivated']);
    }

    /**
     * Block public self-registration; accounts are created by superadmin only.
     */
    public function registrationDisabled()
    {
        return redirect()->to('login')
            ->with('error', 'ระบบไม่เปิดรับลงทะเบียนเอง กรุณาติดต่อผู้ดูแลระบบเพื่อขอบัญชีผู้ใช้');
    }
}
