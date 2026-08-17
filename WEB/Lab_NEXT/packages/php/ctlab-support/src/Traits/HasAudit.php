<?php

namespace Ctlab\Support\Traits;

use App\Domains\Audit\Models\AuditLog;

trait HasAudit
{
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
