<?php
// Debug endpoint to check authentication status
namespace App\Controllers;

class DebugController extends BaseController
{
    public function auth()
    {
        $data = [
            'logged_in' => auth()->loggedIn(),
            'user' => auth()->user()?->toArray() ?? null,
            'session_id' => session_id(),
            'session_data' => $_SESSION,
            'cookies' => $_COOKIE,
        ];
        
        return $this->response->setJSON($data);
    }
}
