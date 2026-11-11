<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\QueueStatusHistory;
use App\Models\QueueTicket;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class UserDeactivationService
{
    /**
     * Deactivate a user account while strictly enforcing the active queue tickets interlock rule.
     *
     * @param  User  $targetUser The user account to be deactivated
     * @param  User  $performedBy The operator/admin performing the deactivation
     * @param  bool  $emergencyForce Whether to invoke the emergency force override (admin only)
     * @param  string|null  $reason Reason for deactivation
     * @return array{success: bool, cancelled_tickets_count: int, message: string}
     *
     * @throws DomainException
     */
    public function deactivate(
        User $targetUser,
        User $performedBy,
        bool $emergencyForce = false,
        ?string $reason = null
    ): array {
        return DB::transaction(function () use ($targetUser, $performedBy, $emergencyForce, $reason): array {
            $cancelledCount = 0;

            // Last Active Admin Protection (M8-19)
            if ($targetUser->isAdmin()) {
                $activeAdminCount = User::query()
                    ->where('role', User::ROLE_ADMIN)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->count();

                if ($activeAdminCount <= 1) {
                    throw new DomainException(
                        'Tidak dapat menonaktifkan akun administrator aktif terakhir. Platform wajib memiliki minimal satu administrator aktif.'
                    );
                }

                if ($targetUser->id === $performedBy->id && $activeAdminCount <= 1) {
                    throw new DomainException(
                        'Anda tidak dapat menonaktifkan akun administrator Anda sendiri karena Anda adalah satu-satunya administrator aktif yang tersisa.'
                    );
                }
            }

            // Only merchant owners have operational business queues to safeguard
            if ($targetUser->isMerchant() && $targetUser->business) {
                $business = $targetUser->business;

                $activeTickets = QueueTicket::query()
                    ->where('business_id', $business->id)
                    ->whereIn('status', [
                        QueueTicket::STATUS_MENUNGGU,
                        QueueTicket::STATUS_DIPANGGIL,
                        QueueTicket::STATUS_SEDANG_DILAYANI,
                    ])
                    ->lockForUpdate()
                    ->get();

                if ($activeTickets->isNotEmpty()) {
                    if (! $emergencyForce) {
                        throw new DomainException(
                            sprintf(
                                'Akun pemilik tidak dapat dinonaktifkan karena masih terdapat %d tiket antrean aktif. Harap selesaikan atau batalkan antrean terlebih dahulu, atau gunakan prosedur darurat (emergency force).',
                                $activeTickets->count()
                            )
                        );
                    }

                    if (! $performedBy->isAdmin()) {
                        throw new DomainException(
                            'Prosedur penonaktifan darurat (emergency force) hanya boleh dijalankan oleh Administrator platform.'
                        );
                    }

                    $cancellationReason = 'Penonaktifan akun pemilik oleh administrator (Darurat)'.($reason ? ': '.$reason : '');
                    $now = now();

                    foreach ($activeTickets as $ticket) {
                        $previousStatus = $ticket->status;

                        $ticket->update([
                            'status' => QueueTicket::STATUS_DIBATALKAN,
                            'cancelled_at' => $now,
                            'cancellation_reason' => $cancellationReason,
                        ]);

                        QueueStatusHistory::create([
                            'queue_ticket_id' => $ticket->id,
                            'event_type' => QueueStatusHistory::EVENT_TICKET_CANCELLED,
                            'from_status' => $previousStatus,
                            'to_status' => QueueTicket::STATUS_DIBATALKAN,
                            'actor_type' => QueueStatusHistory::ACTOR_SYSTEM,
                            'actor_id' => $performedBy->id,
                            'note' => $cancellationReason,
                            'created_at' => $now,
                        ]);

                        $cancelledCount++;
                    }

                    ActivityLog::log(
                        action: 'emergency_deactivation_force',
                        description: sprintf('Penonaktifan darurat membatalkan otomatis %d tiket antrean aktif.', $cancelledCount),
                        business: $business,
                        user: $performedBy,
                        properties: [
                            'target_user_id' => $targetUser->id,
                            'cancelled_count' => $cancelledCount,
                            'reason' => $reason,
                        ]
                    );
                }
            }

            $targetUser->is_active = false;
            $targetUser->save();

            ActivityLog::log(
                action: 'user_deactivated',
                description: sprintf('Akun %s (%s) dinonaktifkan.', $targetUser->name, $targetUser->email),
                business: $targetUser->business,
                user: $performedBy,
                properties: [
                    'target_user_id' => $targetUser->id,
                    'emergency_force' => $emergencyForce,
                    'cancelled_tickets_count' => $cancelledCount,
                    'reason' => $reason,
                ]
            );

            return [
                'success' => true,
                'cancelled_tickets_count' => $cancelledCount,
                'message' => $cancelledCount > 0
                    ? sprintf('Akun dinonaktifkan via prosedur darurat. %d antrean aktif telah dibatalkan otomatis.', $cancelledCount)
                    : 'Akun berhasil dinonaktifkan.',
            ];
        });
    }

    /**
     * Activate an inactive user account.
     *
     * @param  User  $targetUser The user account to be activated
     * @param  User  $performedBy The administrator performing the activation
     * @return array{success: bool, message: string}
     */
    public function activate(User $targetUser, User $performedBy): array
    {
        return DB::transaction(function () use ($targetUser, $performedBy): array {
            $targetUser->is_active = true;
            $targetUser->save();

            ActivityLog::log(
                action: 'user_activated',
                description: sprintf('Akun %s (%s) diaktifkan kembali.', $targetUser->name, $targetUser->email),
                business: $targetUser->business,
                user: $performedBy,
                properties: [
                    'target_user_id' => $targetUser->id,
                ]
            );

            return [
                'success' => true,
                'message' => sprintf('Akun %s berhasil diaktifkan kembali.', $targetUser->name),
            ];
        });
    }
}
