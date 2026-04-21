<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $username = 'superadmin';
        $email    = 'superadmin@nurse-ward.local';
        $password = '1234554321';

        $userModel = new UserModel();
        $users     = auth()->getProvider();

        $existing = $userModel->where('username', $username)->first();

        if ($existing !== null) {
            $user = $users->findById($existing->id);
            $user->fill(['password' => $password]);
            $users->save($user);

            if ($users->errors() !== []) {
                throw new \RuntimeException('Superadmin password update failed: ' . json_encode($users->errors()));
            }

            if (! $user->inGroup('superadmin')) {
                $user->addGroup('superadmin');
            }

            $userModel->update($user->id, ['approval_status' => 'approved']);

            return;
        }

        $user = new User([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
        ]);

        $users->save($user);

        if ($users->errors() !== []) {
            throw new \RuntimeException('Superadmin create failed: ' . json_encode($users->errors()));
        }

        $newUser = $users->findById($users->getInsertID());
        $newUser->addGroup('superadmin');
        $userModel->update($newUser->id, ['approval_status' => 'approved']);
    }
}
