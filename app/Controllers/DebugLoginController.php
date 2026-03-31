<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Shield\Models\UserModel;

class DebugLoginController extends BaseController
{
    public function test()
    {
        $auth = auth('session');
        
        // Test credentials
        $credentials = [
            'username' => 'superadmin',
            'password' => env('DEBUG_LOGIN_PASSWORD'),
        ];
        
        $result = $auth->attempt($credentials);
        
        $data = [
            'attempt_result' => $result->isOK() ? 'SUCCESS' : 'FAILED',
            'reason' => $result->reason(),
            'username_field_exists' => isset($credentials['username']),
            'password_field_exists' => isset($credentials['password']),
            'valid_fields_config' => config('Auth')->validFields,
            'user_exists' => (new UserModel())->where('username', 'superadmin')->first() ? 'YES' : 'NO',
        ];
        
        return $this->response->setJSON($data);
    }
    
    public function checkUser()
    {
        $userModel = new UserModel();
        $user = $userModel->where('username', 'superadmin')->first();
        
        if (!$user) {
            return $this->response->setJSON(['error' => 'User not found']);
        }
        
        // Get identities
        $identities = $user->identities;
        
        return $this->response->setJSON([
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'status' => $user->approval_status,
            'groups' => $user->getGroups(),
            'has_password_identity' => count($identities) > 0 ? 'YES' : 'NO',
            'identities_count' => count($identities),
        ]);
    }
}
