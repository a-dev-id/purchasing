<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NandiniPurchasingUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'IT Department',
                'email' => 'it@nandiniapps.cloud',
                'password' => '~9rsmcXu2)jb3x(z',
                'role' => 'requester',
                'department_name' => 'IT',
                'is_active' => true,
            ],
            [
                'name' => 'Engineering Department',
                'email' => 'eng@nandiniapps.cloud',
                'password' => '*2;IBvuXEb(FRe}-',
                'role' => 'requester',
                'department_name' => 'Engineering',
                'is_active' => true,
            ],
            [
                'name' => 'Housekeeping Department',
                'email' => 'hk@nandiniapps.cloud',
                'password' => '3!=n+Q4A6oh~+&ma',
                'role' => 'requester',
                'department_name' => 'Housekeeping',
                'is_active' => true,
            ],
            [
                'name' => 'Purchasing',
                'email' => 'purchasing@nandiniapps.cloud',
                'password' => '}Cd,;Z3~~O#DG{CV',
                'role' => 'purchasing',
                'department_name' => 'Purchasing',
                'is_active' => true,
            ],
            [
                'name' => 'Cost Control',
                'email' => 'costcontrol@nandiniapps.cloud',
                'password' => ';2k;6X#K$G+!(]bx',
                'role' => 'accounting',
                'department_name' => 'Accounting',
                'is_active' => true,
            ],
            [
                'name' => 'Bookkeeper',
                'email' => 'bookkeeper@nandiniapps.cloud',
                'password' => 'n%^^,qKGsZ_g1+T*',
                'role' => 'accounting',
                'department_name' => 'Accounting',
                'is_active' => true,
            ],
            [
                'name' => 'General Manager',
                'email' => 'gm@nandiniapps.cloud',
                'password' => 'WmX{-Zq.^,]lQms}',
                'role' => 'gm',
                'department_name' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Owner Representative',
                'email' => 'or@nandiniapps.cloud',
                'password' => 'QR)+1.W9kLHnrl~E',
                'role' => 'owner',
                'department_name' => null,
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            $plainPassword = $userData['password'];
            unset($userData['password']);

            User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => Hash::make($plainPassword),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ])
            );
        }
    }
}
