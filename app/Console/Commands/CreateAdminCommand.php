<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
                            {--email= : Alamat email administrator}
                            {--name=Administrator Platform : Nama lengkap administrator}
                            {--password= : Kata sandi akun administrator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bootstrap atau buat akun Administrator baru secara aman tanpa kredensial hardcoded (M10-40)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email');
        if (! $email) {
            $email = (string) $this->ask('Masukkan alamat email administrator');
        }

        $emailValidator = Validator::make(['email' => $email], [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ], [
            'email.unique' => 'Alamat email tersebut sudah terdaftar pada sistem.',
            'email.email' => 'Format email tidak valid.',
        ]);

        if ($emailValidator->fails()) {
            $this->error($emailValidator->errors()->first());

            return self::FAILURE;
        }

        $name = (string) $this->option('name');
        if ($name === 'Administrator Platform') {
            $inputName = $this->ask('Nama administrator', $name);
            $name = $inputName ? (string) $inputName : $name;
        }

        $password = $this->option('password');
        if (! $password) {
            $password = (string) $this->secret('Masukkan kata sandi baru (minimal 8 karakter)');
            $passwordConfirm = (string) $this->secret('Konfirmasi kata sandi');

            if ($password !== $passwordConfirm) {
                $this->error('Konfirmasi kata sandi tidak cocok.');

                return self::FAILURE;
            }
        }

        if (strlen((string) $password) < 8) {
            $this->error('Kata sandi harus memiliki panjang minimal 8 karakter.');

            return self::FAILURE;
        }

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make((string) $password),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        ActivityLog::log(
            action: 'admin_created_via_cli',
            description: sprintf('Akun administrator %s (%s) dibuat melalui CLI bootstrap.', $admin->name, $admin->email),
            user: $admin,
            properties: ['created_via' => 'artisan admin:create']
        );

        $this->info(sprintf('Administrator "%s" (%s) berhasil dibuat.', $admin->name, $admin->email));

        return self::SUCCESS;
    }
}
