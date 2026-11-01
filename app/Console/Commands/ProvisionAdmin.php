<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class ProvisionAdmin extends Command
{
    protected $signature = 'ecotrack:provision-admin';

    protected $description = 'Buat akun admin pertama dari konfigurasi environment.';

    public function handle(): int
    {
        $attributes = config('ecotrack.admin_provision');

        $validator = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            $this->error('Konfigurasi ECOTRACK_ADMIN_NAME, ECOTRACK_ADMIN_EMAIL, atau ECOTRACK_ADMIN_PASSWORD tidak lengkap atau tidak valid.');
            $this->line('Password provisioning harus memiliki minimal 6 karakter.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $attributes['email'])->exists()) {
            $this->error('Email tersebut sudah terdaftar. Command ini tidak mengubah akun yang ada.');

            return self::FAILURE;
        }

        User::forceCreate([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
            'role' => 'admin',
        ]);

        $this->info('Akun admin berhasil dibuat. Hapus ECOTRACK_ADMIN_PASSWORD dari environment setelah provisioning.');

        return self::SUCCESS;
    }
}
