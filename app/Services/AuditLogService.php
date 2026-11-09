<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Arr;

class AuditLogService
{
    /**
     * Sensitive parameter keys that must never be recorded in audit logs.
     *
     * @var list<string>
     */
    protected const array SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'remember_token',
        'secret',
        'api_key',
        'credit_card',
        'session_id',
    ];

    /**
     * Record a standardized audit event in the platform activity log with data sanitization.
     *
     * @param  string  $action
     * @param  string  $description
     * @param  Business|int|null  $business
     * @param  User|int|null  $user
     * @param  array<string, mixed>  $properties
     * @return ActivityLog
     */
    public function log(
        string $action,
        string $description,
        Business|int|null $business = null,
        User|int|null $user = null,
        array $properties = []
    ): ActivityLog {
        $sanitizedProperties = $this->sanitizeProperties($properties);

        return ActivityLog::log(
            action: $action,
            description: $description,
            business: $business,
            user: $user,
            properties: $sanitizedProperties
        );
    }

    /**
     * Log a business operational or configuration modification.
     *
     * @param  Business  $business
     * @param  User|null  $actor
     * @param  string  $action
     * @param  string  $description
     * @param  array<string, mixed>  $properties
     * @return ActivityLog
     */
    public function logBusinessEvent(
        Business $business,
        ?User $actor,
        string $action,
        string $description,
        array $properties = []
    ): ActivityLog {
        return $this->log(
            action: $action,
            description: $description,
            business: $business,
            user: $actor,
            properties: $properties
        );
    }

    /**
     * Log an administrative or security user lifecycle modification.
     *
     * @param  User  $targetUser
     * @param  User|null  $actor
     * @param  string  $action
     * @param  string  $description
     * @param  array<string, mixed>  $properties
     * @return ActivityLog
     */
    public function logUserEvent(
        User $targetUser,
        ?User $actor,
        string $action,
        string $description,
        array $properties = []
    ): ActivityLog {
        $properties['target_user_id'] = $targetUser->id;
        $properties['target_user_email'] = $targetUser->email;
        $properties['target_user_role'] = $targetUser->role;

        return $this->log(
            action: $action,
            description: $description,
            business: $targetUser->business,
            user: $actor,
            properties: $properties
        );
    }

    /**
     * Sanitize audit metadata by stripping sensitive credentials and masking PII.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public function sanitizeProperties(array $properties): array
    {
        $sanitized = [];

        foreach ($properties as $key => $value) {
            $lowerKey = strtolower((string) $key);

            // Strip sensitive credentials immediately
            if (in_array($lowerKey, self::SENSITIVE_KEYS, true) || str_contains($lowerKey, 'password')) {
                continue;
            }

            // Mask phone numbers if present in properties
            if (str_contains($lowerKey, 'phone') && is_string($value) && trim($value) !== '') {
                $sanitized[$key] = $this->maskPhoneNumber($value);
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeProperties($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Helper to mask customer phone numbers in audit metadata (OQ-14).
     */
    protected function maskPhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        $length = strlen($clean);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        if ($length <= 8) {
            return substr($clean, 0, 2) . str_repeat('*', $length - 4) . substr($clean, -2);
        }

        return substr($clean, 0, 4) . '****' . substr($clean, -4);
    }
}

