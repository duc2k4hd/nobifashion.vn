<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountLogResource;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AccountLogController extends Controller
{
    public function index(Request $request, Account $account)
    {
        $this->authorize('view', $account);

        $filters = $request->validate([
            'type' => ['nullable', 'string'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $query = $account->logs()->with('admin')->latest();
        $this->applyFilters($query, $filters);

        $logs = $query->paginate($request->integer('per_page', 25));

        return AccountLogResource::collection($logs);
    }

    public function export(Request $request, Account $account)
    {
        $this->authorize('view', $account);

        $filters = $request->validate([
            'type' => ['nullable', 'string'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $query = $account->logs()->with('admin')->latest();
        $this->applyFilters($query, $filters);
        $logs = $query->get();

        $filename = sprintf('account-%d-logs-%s.csv', $account->id, now()->format('Ymd_His'));

        return Response::streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Type', 'Admin', 'Payload', 'IP', 'User Agent', 'Time']);

            $logs->each(function ($log) use ($handle) {
                fputcsv($handle, [
                    $log->id,
                    $log->type,
                    $log->admin?->displayName() ?? 'System',
                    json_encode($log->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    $log->ip,
                    $log->user_agent,
                    optional($log->created_at)->toDateTimeString(),
                ]);
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['types'])) {
            $query->whereIn('type', array_filter($filters['types']));
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }
}
