<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'code'];

    public const CODE_COC = 'COC';

    private const LEGACY_CODE_MAP = [
        'CRIM' => self::CODE_COC,
        'CRIMINOLOGY' => self::CODE_COC,
        'COLLEGE OF CRIMINOLOGY' => self::CODE_COC,
    ];

    private const CODE_GROUPS = [
        ['CCS', 'IT', 'ACT'],
        ['CTE', 'ED'],
        [self::CODE_COC, 'FB', 'LD', 'QD'],
        ['SHTM', 'HM', 'TM'],
    ];

    public static function normalizeCode(?string $code): ?string
    {
        $normalized = strtoupper(trim((string) $code));

        if ($normalized === '') {
            return null;
        }

        return self::LEGACY_CODE_MAP[$normalized] ?? $normalized;
    }

    public static function aliasesFor(?string $code): array
    {
        $normalized = self::normalizeCode($code);

        if (! $normalized) {
            return [];
        }

        foreach (self::CODE_GROUPS as $group) {
            if (in_array($normalized, $group, true)) {
                return $group;
            }
        }

        return [$normalized];
    }

    public static function codesMatch(?string $first, ?string $second): bool
    {
        $first = self::normalizeCode($first);
        $second = self::normalizeCode($second);

        if (! $first || ! $second) {
            return false;
        }

        if ($first === $second) {
            return true;
        }

        return count(array_intersect(self::aliasesFor($first), self::aliasesFor($second))) > 0;
    }
}
