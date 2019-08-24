<?php

use App\Models\User;
use jeremykenedy\LaravelRoles\Models\Role;
use jeremykenedy\LaravelRoles\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userRole    = Role::where('name', '=', 'User')->first();
        $adminRole   = Role::where('name', '=', 'Admin')->first();
        $permissions = Permission::all();
        /**
         * Add Users
         *
         */
        if (User::where('email', '=', 'admin@lendo.io')->first() === null) {
            $newUser = User::create([
                'email'             => 'admin@lendo.io',
                'user_name'         => 'admin',
                'password'          => bcrypt('password'),
                'first_name'        => 'admin',
                'last_name'         => 'lendo',
                'referrer_key'      => 'Lend0',
                'referrer_user_id'  => 0,
                'role'              => 1,
                'join_via'          => 1,
                'status'            => 1,
            ]);

            $newUser->attachRole($adminRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'admin@hiteshi.com')->first() === null) {
            $newUser = User::create([
                'email'             => 'admin@hiteshi.com',
                'user_name'         => 'hiteshi',
                'password'          => bcrypt('password'),
                'first_name'        => 'admin',
                'last_name'         => 'hiteshi',
                'referrer_key'      => 'Hite0',
                'referrer_user_id'  => 1,
                'role'              => 1,
                'join_via'          => 1,
                'status'            => 1,
            ]);

            $newUser->attachRole($adminRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'eer@erline.eu')->first() === null) {
            $newUser = User::create([
                'email'             => 'eer@erline.eu',
                'user_name'         => 'erkaner',
                'password'          => bcrypt('password'),
                'first_name'        => 'Erkan',
                'last_name'         => 'Er',
                'referrer_key'      => 'f38h',
                'referrer_user_id'  => 1,
                'role'              => 2,
                'join_via'          => 1,
                'status'            => 1,
            ]);
            $newUser->attachRole($userRole);
        }

        if (User::where('email', '=', 'test@lendo.io')->first() === null) {
            $newUser = User::create([
                'email'             => 'test@lendo.io',
                'user_name'         => 'test',
                'password'          => bcrypt('test@123'),
                'first_name'        => 'test',
                'last_name'         => 'lendo',
                'referrer_key'      => 'teSt1',
                'referrer_user_id'  => 1,
                'role'              => 2,
                'join_via'          => 1,
                'status'            => 1,
            ]);
            $newUser->attachRole($userRole);
        }

        if (User::where('email', '=', 'testreferrer1@lendo.io')->first() === null) {
            $testReferrelDetail = User::where('email', '=', 'test@lendo.io')->pluck('id');
            $newUser = User::create([
                'email'             => 'testreferrer1@lendo.io',
                'user_name'         => 'testreferrer1',
                'password'          => bcrypt('test@123'),
                'first_name'        => 'testreferrer1',
                'last_name'         => 'lendo',
                'referrer_key'      => 'teSt2',
                'referrer_user_id'  => ((isset($testReferrelDetail[0]) && !empty($testReferrelDetail[0])) ? $testReferrelDetail[0] : 1),
                'role'              => 2,
                'join_via'          => 1,
                'status'            => 1,
            ]);
            $newUser->attachRole($userRole);
        }

        if (User::where('email', '=', 'testreferrer2@lendo.io')->first() === null) {
            $testReferrelDetail = User::where('email', '=', 'testreferrer1@lendo.io')->pluck('id');
            $newUser = User::create([
                'email'             => 'testreferrer2@lendo.io',
                'user_name'         => 'testreferrer2',
                'password'          => bcrypt('test@123'),
                'first_name'        => 'testreferrer2',
                'last_name'         => 'lendo',
                'referrer_key'      => 'teSt3',
                'referrer_user_id'  => ((isset($testReferrelDetail[0]) && !empty($testReferrelDetail[0])) ? $testReferrelDetail[0] : 1),
                'role'              => 2,
                'join_via'          => 1,
                'status'            => 1,
            ]);
            $newUser->attachRole($userRole);
        }
    }
}
