<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $name = (string) config('admin.super_admin.name');
        $email = (string) config('admin.super_admin.email');
        $password = config('admin.super_admin.password');

        if (! $this->filledSecret($password)) {
            $this->command->warn(
                'SUPER_ADMIN_PASSWORD kosong. Set di .env lalu jalankan: php artisan config:clear && php artisan db:seed --class=AdminUserSeeder'
            );

            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->command->error('SUPER_ADMIN_EMAIL tidak valid: '.$email);

            return;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ]
        );

        if (! $user->wasRecentlyCreated) {
            $user->forceFill([
                'name' => $name,
                'password' => Hash::make($password),
            ])->save();
        }

        if (! $user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }

        $this->command->info('Super Admin siap: '.$email);
    }

    /**
     * @param  mixed  $value
     */
    protected function filledSecret($value)
    {
        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }
}
