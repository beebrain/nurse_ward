<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Shield\Authentication\Passwords;

class AccountController extends BaseController
{
    public function changePasswordView()
    {
        if (! auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        return view('account/change_password', [
            'title' => 'เปลี่ยนรหัสผ่าน',
        ]);
    }

    public function changePasswordAction()
    {
        if (! auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[8]|' . Passwords::getMaxLengthRule(),
            'confirm_password' => 'required|matches[new_password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        /** @var \CodeIgniter\Shield\Entities\User $user */
        $user = auth()->user();
        $identity = $user?->getEmailIdentity();
        $hash = $identity?->secret2;

        if (! $user || ! $hash) {
            return redirect()->back()
                ->with('error', 'ไม่สามารถเปลี่ยนรหัสผ่านได้ (ไม่พบข้อมูลรหัสผ่านของผู้ใช้)');
        }

        $current = (string) $this->request->getPost('current_password');
        $new     = (string) $this->request->getPost('new_password');

        $passwords = service('passwords');
        if (! $passwords->verify($current, $hash)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'รหัสผ่านเดิมไม่ถูกต้อง');
        }

        if ($current === $new) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านเดิม');
        }

        $provider = auth()->getProvider();
        $user->fill(['password' => $new]);
        $provider->save($user);

        if (method_exists($provider, 'errors') && $provider->errors()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $provider->errors());
        }

        return redirect()->to('/')
            ->with('message', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
    }
}

