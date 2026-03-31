<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Authenticators\Session;

class LoginController extends BaseController
{
    public function loginView()
    {
        // Use custom login view
        return view('auth/login');
    }

    public function loginAction()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            log_message('error', 'Login validation failed: ' . json_encode($this->validator->getErrors()));
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $credentials = [
            'username' => $this->request->getPost('username'),
            'password' => $this->request->getPost('password'),
        ];

        log_message('info', 'Login attempt: username=' . $credentials['username']);

        $auth = auth('session');
        $result = $auth->attempt($credentials);

        if (!$result->isOK()) {
            log_message('error', 'Login failed: ' . $result->reason() . ' for user: ' . $credentials['username']);
            return redirect()->back()->withInput()->with('error', 'Login failed: ' . $result->reason());
        }

        log_message('info', 'Login successful: ' . $credentials['username']);
        return redirect()->to('/')->with('message', 'Login successful!');
    }

    public function logoutAction(): RedirectResponse
    {
        auth()->logout();
        return redirect()->to('/login');
    }
}
