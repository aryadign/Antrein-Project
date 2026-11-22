<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ExportAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:export
                            {path=storage/app/akun-antrein.csv : Lokasi file CSV yang dibuat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ekspor daftar akun (admin, pemilik, operator, pelanggan) beserta toko dan kata sandi seeder ke CSV';

    /**
     * Seeder passwords tried per role. Passwords are stored hashed, so a password is only
     * written to the sheet after Hash::check proves it is still the account's password.
     *
     * @var array<string, list<string>>
     */
    private const SEEDER_PASSWORDS = [
        'owner' => ['merchant12345', 'operator12345', 'pelanggan12345'],
        'operator' => ['operator12345', 'merchant12345', 'pelanggan12345'],
        'customer' => ['pelanggan12345'],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $absolutePath = str_starts_with($path, '/') ? $path : base_path($path);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        $handle = fopen($absolutePath, 'w');
        // UTF-8 BOM so Excel shows Indonesian names correctly.
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Peran', 'Nama', 'Email', 'Kata sandi', 'Toko / cabang', 'Status toko', 'Halaman toko'], ';');

        $rows = 0;

        foreach (User::where('role', User::ROLE_ADMIN)->orderBy('id')->get() as $admin) {
            fputcsv($handle, ['Admin', $admin->name, $admin->email, 'Lihat INITIAL_ADMIN_PASSWORD di env server', '-', '-', '-'], ';');
            $rows++;
        }

        $memberships = BusinessMembership::query()
            ->with(['user', 'business'])
            ->whereHas('user')
            ->whereHas('business')
            ->get()
            ->sortBy(fn (BusinessMembership $membership): string => $membership->business->name.'|'.($membership->role === BusinessMembership::ROLE_OWNER ? '0' : '1'));

        foreach ($memberships as $membership) {
            $role = $membership->role === BusinessMembership::ROLE_OWNER ? 'owner' : 'operator';

            fputcsv($handle, [
                $role === 'owner' ? 'Pemilik UMKM' : 'Operator',
                $membership->user->name,
                $membership->user->email,
                $this->knownPassword($membership->user, $role),
                $membership->business->name,
                $this->approvalLabel($membership->business),
                route('customer.queue', $membership->business->slug),
            ], ';');
            $rows++;
        }

        foreach (User::where('role', User::ROLE_CUSTOMER)->orderBy('id')->get() as $customer) {
            fputcsv($handle, ['Pelanggan', $customer->name, $customer->email, $this->knownPassword($customer, 'customer'), '-', '-', '-'], ';');
            $rows++;
        }

        fclose($handle);

        $this->info("{$rows} akun diekspor ke {$absolutePath}");
        $this->warn('File ini berisi kata sandi. Jangan di-commit atau dibagikan publik.');

        return self::SUCCESS;
    }

    /**
     * The seeder password for this account, or a note when it was changed.
     */
    private function knownPassword(User $user, string $role): string
    {
        foreach (self::SEEDER_PASSWORDS[$role] as $candidate) {
            if (Hash::check($candidate, (string) $user->password)) {
                return $candidate;
            }
        }

        return '(sudah diganti pengguna)';
    }

    /**
     * Approval state of the store, so the sheet shows which accounts demo the admin review flow.
     */
    private function approvalLabel(Business $business): string
    {
        return match ($business->approval_status) {
            Business::APPROVAL_PENDING => 'Menunggu verifikasi admin',
            Business::APPROVAL_REJECTED => 'Ditolak admin',
            default => $business->is_suspended ? 'Ditangguhkan' : 'Aktif',
        };
    }
}
