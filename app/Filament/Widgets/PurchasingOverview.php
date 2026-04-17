<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequest;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PurchasingOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        if ($user instanceof User && $user->isRequester()) {
            $departmentQuery = PurchaseRequest::query()
                ->where('department_name', $user->department_name);

            $draftStatuses = [
                'draft',
            ];

            $returnedToRequesterStatuses = [
                'revision_from_purchasing',
                'revision_from_accounting',
                'revision_from_gm',
                'revision_to_requester_from_accounting',
                'revision_to_requester_from_gm',
            ];

            $waitingApprovalStatuses = [
                'submitted',
                'revision_to_purchasing_from_accounting',
                'submitted_to_accounting',
                'on_hold_by_accounting',
                'submitted_to_gm',
                'revision_to_purchasing_from_gm',
                'revision_to_accounting_from_gm',
                'on_hold_by_gm',
                'gm_approved',
            ];

            $waitingPaymentStatuses = [
                'waiting_payment_by_fc',
                'on_hold_by_fc',
            ];

            $paidOrArrivedStatuses = [
                'paid_to_vendor_by_fc',
                'item_arrived_by_fc',
            ];

            $completedStatuses = [
                'received_by_requester_by_fc',
                'approved', // keep old records compatible
            ];

            $draftCount = (clone $departmentQuery)
                ->whereIn('status', $draftStatuses)
                ->count();

            $returnedCount = (clone $departmentQuery)
                ->whereIn('status', $returnedToRequesterStatuses)
                ->count();

            $waitingApprovalCount = (clone $departmentQuery)
                ->whereIn('status', $waitingApprovalStatuses)
                ->count();

            $waitingPaymentCount = (clone $departmentQuery)
                ->whereIn('status', $waitingPaymentStatuses)
                ->count();

            $paidOrArrivedCount = (clone $departmentQuery)
                ->whereIn('status', $paidOrArrivedStatuses)
                ->count();

            $completedCount = (clone $departmentQuery)
                ->whereIn('status', $completedStatuses)
                ->count();

            return [
                Stat::make('Department Draft PRs', number_format($draftCount))
                    ->description('Draft requests not submitted yet')
                    ->color('gray'),

                Stat::make('Returned to Department', number_format($returnedCount))
                    ->description('Need revision from your department')
                    ->color($returnedCount > 0 ? 'warning' : 'success'),

                Stat::make('Waiting Approval', number_format($waitingApprovalCount))
                    ->description('Still moving through Purchasing / Accounting / GM')
                    ->color('info'),

                Stat::make('Waiting Payment', number_format($waitingPaymentCount))
                    ->description('Already approved, waiting payment or on hold by FC')
                    ->color($waitingPaymentCount > 0 ? 'warning' : 'success'),

                Stat::make('Paid / Item Arrived', number_format($paidOrArrivedCount))
                    ->description('Already paid to vendor or item already arrived')
                    ->color('primary'),

                Stat::make('Completed', number_format($completedCount))
                    ->description('Already received by requester')
                    ->color('success'),
            ];
        }

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

        $waitingOnFinancialControllerStatuses = [
            'gm_approved',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'item_arrived_by_fc',
            'received_by_requester_by_fc',
            'on_hold_by_fc',
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
            'gm_approved',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'item_arrived_by_fc',
            'received_by_requester_by_fc',
            'on_hold_by_fc',
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

        $waitingOnFinancialController = PurchaseRequest::query()
            ->whereIn('status', $waitingOnFinancialControllerStatuses)
            ->count();

        $overdueThreeDays = PurchaseRequest::query()
            ->whereIn('status', $overdueStatuses)
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '<=', $now->copy()->subDays(3))
            ->count();

        $approvedThisMonth = PurchaseRequest::query()
            ->whereIn('status', ['approved', 'received_by_requester_by_fc'])
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

            Stat::make('Waiting on Financial Controller', number_format($waitingOnFinancialController))
                ->description('PRs currently on Financial Controller desk')
                ->color('success'),

            Stat::make('Overdue 3+ Days', number_format($overdueThreeDays))
                ->description('No activity for at least 3 days')
                ->color($overdueThreeDays > 0 ? 'danger' : 'success'),

            Stat::make('Approved This Month', number_format($approvedThisMonth))
                ->description('Approved since ' . $monthStart->format('d M Y'))
                ->color('primary'),
        ];
    }
}
