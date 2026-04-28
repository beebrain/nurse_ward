<?php

namespace App\Database\Migrations;

use App\Models\UserModel;
use App\Models\UserWardModel;
use App\Models\WardModel;
use CodeIgniter\Database\Migration;
use CodeIgniter\Shield\Entities\User;

class GenerateWardNurseUsers extends Migration
{
    public function up()
    {
        $password = 'nurse12345';
        $wardModel = new WardModel();
        $userModel = new UserModel();
        $userWardModel = new UserWardModel();
        $users = auth()->getProvider();

        foreach ($wardModel->getActiveWithDepartment() as $ward) {
            $wardId = (int)$ward['id'];
            $username = 'nurse_ward_' . str_pad((string)$wardId, 2, '0', STR_PAD_LEFT);
            $email = $username . '@nurse-ward.local';

            $assignedUser = $userWardModel->getAssignedUserForWard($wardId);
            $existing = $assignedUser !== null
                ? $users->findById((int)$assignedUser['id'])
                : $userModel->where('username', $username)->first();

            if ($existing !== null) {
                $user = $users->findById((int)$existing->id);
                $user->fill(['password' => $password]);
                $users->save($user);

                if ($users->errors() !== []) {
                    throw new \RuntimeException('Nurse password update failed for ' . $username . ': ' . json_encode($users->errors()));
                }
            } else {
                $user = new User([
                    'username' => $username,
                    'email'    => $email,
                    'password' => $password,
                ]);

                $users->save($user);

                if ($users->errors() !== []) {
                    throw new \RuntimeException('Nurse create failed for ' . $username . ': ' . json_encode($users->errors()));
                }

                $user = $users->findById($users->getInsertID());
            }

            foreach ($user->getGroups() as $group) {
                $user->removeGroup($group);
            }
            $user->addGroup('nurse');

            $userModel->update($user->id, ['approval_status' => 'approved']);
            $userWardModel->syncUserWards((int)$user->id, [$wardId]);
        }
    }

    public function down()
    {
        $users = auth()->getProvider();
        $generatedUsers = (new UserModel())
            ->like('username', 'nurse_ward_', 'after')
            ->findAll();

        foreach ($generatedUsers as $user) {
            (new UserWardModel())->where('user_id', (int)$user->id)->delete();
            $users->delete((int)$user->id, true);
        }
    }
}
