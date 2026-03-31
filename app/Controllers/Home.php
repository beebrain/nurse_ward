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

        if ($user->can('reports.view')) {
            return redirect()->to(base_url('reports/dashboard'));
        }

        if ($user->can('census.record')) {
            return redirect()->to(base_url('census'));
        }

        return redirect()->to(base_url('auth/pending'));
    }
}
