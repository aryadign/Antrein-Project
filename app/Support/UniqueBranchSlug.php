<?php

namespace App\Support;

use App\Models\Business;
use Illuminate\Support\Str;

class UniqueBranchSlug
{
    /**
     * Generate a unique public branch slug.
     *
     * Legacy branch slugs are never mutated by migrations; this helper is only used
     * when an organization creates or renames its own branch.
     */
    public static function generate(?string $requestedSlug, string $name, ?int $ignoreBusinessId = null): string
    {
        $baseSlug = trim((string) $requestedSlug);

        if ($baseSlug === '') {
            $baseSlug = Str::slug($name);
        }

        if ($baseSlug === '') {
            $baseSlug = 'cabang-'.Str::lower(Str::random(6));
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Business::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreBusinessId, fn ($query) => $query->where('id', '!=', $ignoreBusinessId))
                ->exists()
        ) {
            $counter++;
            $slug = "{$baseSlug}-{$counter}";
        }

        return $slug;
    }
}
