<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Admin\DefaultAdminRoles;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

#[Signature('admin:create')]
#[Description('Provision the first Super Admin account')]
class CreateAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DefaultAdminRoles $roles): int
    {
        if (User::query()->whereHas('adminRole', fn ($query) => $query->where('slug', 'super-admin'))->exists()) {
            $this->error('A Super Admin already exists. Use the authenticated staff management screen to add accounts.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Administrator name'));
        $email = mb_strtolower(trim((string) $this->ask('Administrator email')));
        $password = (string) $this->secret('Choose a password (minimum 14 characters)');
        $passwordConfirmation = (string) $this->secret('Confirm password');
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:14', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::INVALID;
        }

        $role = $roles->sync();
        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'admin_role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->info('Super Admin account created. Sign in at /admin/login.');

        return self::SUCCESS;
    }
}
