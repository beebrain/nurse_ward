<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(base_url('login'));
        }

        $user = auth()->user();

        if ($user->can('census.record') || $user->can('reports.view')) {
            return redirect()->to(base_url('census/productivity'));
        }

        return redirect()->to(base_url('auth/pending'));
    }
}
