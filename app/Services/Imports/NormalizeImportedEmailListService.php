<?php

namespace App\Services\Imports;

class NormalizeImportedEmailListService
{
    /**
     * @return list<string>
     */
    public function normalize(?string $value): array
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/;/', $raw) ?: [];
        $emails = [];
        $seen = [];

        foreach ($parts as $part) {
            $email = trim((string) $part);

            if ($email === '') {
                continue;
            }

            $normalizedKey = mb_strtolower($email);

            if (isset($seen[$normalizedKey])) {
                continue;
            }

            $seen[$normalizedKey] = true;
            $emails[] = $email;
        }

        return $emails;
    }
}
