<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
use App\Models\EmploymentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Employment Statuses
        $statuses = [
            ['name' => 'Working', 'code' => 'working'],
            ['name' => 'Retired', 'code' => 'retired'],
            ['name' => 'On Leave', 'code' => 'on_leave'],
            ['name' => 'Deputation', 'code' => 'deputation'],
        ];

        foreach ($statuses as $status) {
            EmploymentStatus::firstOrCreate(['code' => $status['code']], $status);
        }

        $workingStatus = EmploymentStatus::where('code', 'working')->first();

        // Create Positions
        $positions = [
            ['name' => 'Vice Chancellor', 'code' => 'vc', 'is_unique' => true],
            ['name' => 'Registrar', 'code' => 'registrar', 'is_unique' => true],
            ['name' => 'Director Academics', 'code' => 'director_academics', 'is_unique' => true],
            ['name' => 'Dean', 'code' => 'dean', 'is_unique' => false],
            ['name' => 'Faculty', 'code' => 'faculty', 'is_unique' => false],
            ['name' => 'Staff', 'code' => 'staff', 'is_unique' => false],
        ];

        foreach ($positions as $pos) {
            Position::firstOrCreate(['code' => $pos['code']], $pos);
        }

        $users = [
            [
                'name' => 'Kashif Ahmad Khan',
                'username' => 'thekhansoft',
                'email' => 'kashif.ahmad@awkum.edu.pk',
                'password' => Hash::make('password'),
                'role' => 'Super Admin',
                'appointment_date' => '2010-10-01',
                'position_code' => 'staff',
                'cnic_no' => '16101-1234567-1',
                'phone' => '0314-9955914',
                'gender' => 'Male',
                'nationality' => 'Pakistani',
            ],
            [
                'name' => 'Prof. Dr. Jameel Ahmed Khan',
                'username' => 'vc',
                'email' => 'vc@awkum.edu.pk',
                'password' => Hash::make('password'),
                'role' => 'VC',
                'appointment_date' => '2023-01-01',
                'position_code' => 'vc',
                'cnic_no' => '16101-1234567-2',
                'phone' => '0300-1234568',
                'gender' => 'Male',
                'nationality' => 'Pakistani',
            ],
            [
                'name' => 'Hassan Khan',
                'username' => 'registrar',
                'email' => 'registrar@awkum.edu.pk',
                'password' => Hash::make('password'),
                'role' => 'Registrar',
                'appointment_date' => '2022-05-15',
                'position_code' => 'registrar',
                'permissions' => ['view settings'], // Extra permission for Registrar
                'cnic_no' => '16101-1234567-3',
                'phone' => '0300-1234569',
                'gender' => 'Male',
                'nationality' => 'Pakistani',
            ],
            [
                'name' => 'Prof. Dr. Sher Afzal Khan',
                'username' => 'director_academics',
                'email' => 'academics@awkum.edu.pk',
                'password' => Hash::make('password'),
                'role' => 'Director',
                'appointment_date' => '2021-09-01',
                'position_code' => 'director_academics',
                'cnic_no' => '16101-1234567-4',
                'phone' => '0300-1234570',
                'gender' => 'Male',
                'nationality' => 'Pakistani',
            ],
            [
                'name' => 'Sana Ullah',
                'username' => 'sanaullah',
                'email' => 'sana.ullah@awkum.edu.pk',
                'password' => Hash::make('password'),
                'role' => 'Staff',
                'appointment_date' => '2020-03-10',
                'position_code' => 'staff',
                'cnic_no' => '16101-1234567-5',
                'phone' => '0300-1234571',
                'gender' => 'Male',
                'nationality' => 'Pakistani',
            ],
            [
                'name' => 'Hina Malik',
                'username' => 'hinamalik',
                'email' => 'hina.malik@awkum.edu.pk',
                'password' => Hash::make('password'),
                'role' => 'Faculty',
                'appointment_date' => '2019-08-20',
                'position_code' => 'faculty',
                'cnic_no' => '16101-1234567-6',
                'phone' => '0300-1234572',
                'gender' => 'Female',
                'nationality' => 'Pakistani',
            ],
            [
                'name' => 'Dr. Usman Gondal',
                'username' => 'usmangondal',
                'email' => 'usman.gondal@awkum.edu.pk',
                'password' => Hash::make('password'),
                'role' => 'Dean',
                'appointment_date' => '2018-01-01',
                'position_code' => 'dean',
                'cnic_no' => '16101-1234567-7',
                'phone' => '0300-1234573',
                'gender' => 'Male',
                'nationality' => 'Pakistani',
            ],
            [
                'name' => 'Ayesha Siddiqui',
                'username' => 'ayeshasiddiqui',
                'email' => 'ayesha.siddiqui@awkum.edu.pk',
                'password' => Hash::make('password'),
                'role' => 'Faculty',
                'appointment_date' => '2024-02-01',
                'position_code' => 'faculty',
                'cnic_no' => '16101-1234567-8',
                'phone' => '0300-1234574',
                'gender' => 'Female',
                'nationality' => 'Pakistani',
            ],
        ];

        foreach ($users as $userData) {
            $positionCode = $userData['position_code'];
            $roleName = $userData['role'];
            $startDate = $userData['appointment_date'];

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => $userData['username'] ?? null,
                    'password' => $userData['password'],
                    'email_verified_at' => now(),
                    'employment_status_id' => $workingStatus->id,
                    'cnic_no' => $userData['cnic_no'] ?? null,
                    'phone' => $userData['phone'] ?? null,
                    'gender' => $userData['gender'] ?? null,
                    'nationality' => $userData['nationality'] ?? null,
                ]
            );

            $user->assignRole($roleName);

            if (isset($userData['permissions'])) {
                $user->givePermissionTo($userData['permissions']);
            }

            $position = Position::where('code', $positionCode)->first();
            if ($position) {
                // Check if user already has this position as current
                $hasPosition = $user->positions()
                    ->where('position_id', $position->id)
                    ->wherePivot('is_current', true)
                    ->exists();
                
                if (!$hasPosition) {
                    $user->positions()->attach($position->id, [
                        'appointment_date' => $startDate ?? now(),
                        'is_current' => true,
                    ]);
                }
            }
        }
    }
}
