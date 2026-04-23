<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = AdminAuditLog::with('admin')
            ->when($request->admin_id, fn($q, $id) => $q->where('admin_id', $id))
            ->when($request->action, fn($q, $a) => $q->where('action', 'like', "%{$a}%"))
            ->when($request->entite_type, fn($q, $t) => $q->where('entite_type', $t))
            ->when($request->entite_id, fn($q, $id) => $q->where('entite_id', $id))
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($logs);
    }
}
