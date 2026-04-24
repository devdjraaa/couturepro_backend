<?php

namespace App\Traits;

use App\Models\Admin;
use App\Models\AdminAuditLog;

trait LogsAdminAction
{
    protected function adminUser(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();
        return $admin;
    }

    protected function audit(Admin $admin, string $action, string $entiteType, ?string $entiteId = null, array $details = [], ?string $ip = null): void
    {
        AdminAuditLog::create([
            'admin_id'    => $admin->id,
            'action'      => $action,
            'entite_type' => $entiteType,
            'entite_id'   => $entiteId,
            'details'     => $details ?: null,
            'ip_address'  => $ip,
            'created_at'  => now(),
        ]);
    }
}
