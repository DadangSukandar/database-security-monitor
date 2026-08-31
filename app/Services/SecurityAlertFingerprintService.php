<?php

namespace App\Services;

use App\Models\VulnerabilityFinding;

class SecurityAlertFingerprintService
{
    public function forVulnerabilityFinding(
        ?int $databaseConnectionId,
        string $databaseName,
        VulnerabilityFinding $finding,
        ?string $tableName = null
    ): string {
        $ruleIdentity = trim((string) $finding->rule_code) !== ''
            ? 'rule:'.$finding->rule_code
            : 'title:'.$finding->title;

        return hash('sha256', implode('|', [
            $this->normalize('VULNERABILITY'),
            (string) ($databaseConnectionId ?? ''),
            $this->normalize($databaseName),
            $this->normalize($ruleIdentity),
            $this->normalize($finding->username),
            $this->normalize($finding->host),
            $this->normalize($tableName),
        ]));
    }

    public function normalize(mixed $value): string
    {
        return mb_strtolower(
            preg_replace('/\s+/', ' ', trim((string) $value)) ?? ''
        );
    }
}
