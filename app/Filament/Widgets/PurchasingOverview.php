<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PurchasingOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        $waitingOnPurchasingStatuses = [
            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
        ];

        $waitingOnAccountingStatuses = [
            'submitted_to_accounting',
            'on_hold_by_accounting',
            'revision_to_accounting_from_gm',
        ];

        $waitingOnGmStatuses = [
            'submitted_to_gm',
            'on_hold_by_gm',
        ];

        $overdueStatuses = [
            'submitted',
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_purchasing_from_accounting',
            'revision_to_requester_from_accounting',
            'submitted_to_accounting',
            'on_hold_by_accounting',
            'submitted_to_gm',
            'revision_to_purchasing_from_gm',
            'revision_to_accounting_from_gm',
            'revision_to_requester_from_gm',
            'on_hold_by_gm',
        ];

        $waitingOnPurchasing = PurchaseRequest::query()
            ->whereIn('status', $waitingOnPurchasingStatuses)
            ->count();

        $waitingOnAccounting = PurchaseRequest::query()
            ->whereIn('status', $waitingOnAccountingStatuses)
            ->count();

        $waitingOnGm = PurchaseRequest::query()
            ->whereIn('status', $waitingOnGmStatuses)
            ->count();

        $overdueThreeDays = PurchaseRequest::query()
            ->whereIn('status', $overdueStatuses)
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '<=', $now->copy()->subDays(3))
            ->count();

        $approvedThisMonth = PurchaseRequest::query()
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->where('approved_at', '>=', $monthStart)
            ->count();

        return [
            Stat::make('Waiting on Purchasing', number_format($waitingOnPurchasing))
                ->description('PRs currently on Purchasing desk')
                ->color('warning'),

            Stat::make('Waiting on Accounting', number_format($waitingOnAccounting))
                ->description('PRs currently on Accounting desk')
                ->color('info'),

            Stat::make('Waiting on GM', number_format($waitingOnGm))
                ->description('PRs currently on GM desk')
                ->color('danger'),

            Stat::make('Overdue 3+ Days', number_format($overdueThreeDays))
                ->description('No activity for at least 3 days')
                ->color($overdueThreeDays > 0 ? 'danger' : 'success'),

            Stat::make('Approved This Month', number_format($approvedThisMonth))
                ->description('Approved since ' . $monthStart->format('d M Y'))
                ->color('success'),
        ];
    }
}
