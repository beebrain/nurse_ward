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
}
