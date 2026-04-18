<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequest;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PurchasingOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        if ($this->isRequesterUser($user)) {
            $departmentQuery = PurchaseRequest::query()
                ->where('department_name', $user->department_name);

            $draftCount = (clone $departmentQuery)
                ->whereIn('status', $this->requesterDraftStatuses())
                ->count();

            $returnedCount = (clone $departmentQuery)
                ->whereIn('status', $this->returnedToRequesterStatuses())
                ->count();

            $waitingApprovalCount = (clone $departmentQuery)
                ->whereIn('status', $this->requesterWaitingApprovalStatuses())
                ->count();

            $waitingPaymentCount = (clone $departmentQuery)
                ->whereIn('status', $this->requesterWaitingPaymentStatuses())
                ->count();

            $paidOrArrivedCount = (clone $departmentQuery)
                ->whereIn('status', $this->requesterPaidOrArrivedStatuses())
                ->count();

            $completedCount = (clone $departmentQuery)
                ->whereIn('status', $this->requesterCompletedStatuses())
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
                    ->description('Approved by GM, waiting Financial Controller action')
                    ->color($waitingPaymentCount > 0 ? 'warning' : 'success'),

                Stat::make('Paid / Item Arrived', number_format($paidOrArrivedCount))
                    ->description('Already paid to vendor or item already arrived')
                    ->color('primary'),

                Stat::make('Completed', number_format($completedCount))
                    ->description('Already received by requester / completed')
                    ->color('success'),
            ];
        }

        if ($this->isPurchasingUser($user)) {
            $deskQuery = PurchaseRequest::query()
                ->whereIn('status', $this->purchasingVisibleStatuses());

            $onDeskCount = (clone $deskQuery)->count();

            $newRequestCount = (clone $deskQuery)
                ->where('status', 'submitted')
                ->count();

            $returnedFromAccountingCount = (clone $deskQuery)
                ->where('status', 'revision_to_purchasing_from_accounting')
                ->count();

            $returnedFromGmCount = (clone $deskQuery)
                ->where('status', 'revision_to_purchasing_from_gm')
                ->count();

            $urgentCount = (clone $deskQuery)
                ->where('priority', 'urgent')
                ->count();

            $overdueCount = $this->countOverdue((clone $deskQuery), 3);

            return [
                Stat::make('On Purchasing Desk', number_format($onDeskCount))
                    ->description('PRs currently waiting on Purchasing')
                    ->color('warning'),

                Stat::make('New Requests', number_format($newRequestCount))
                    ->description('Freshly submitted to Purchasing')
                    ->color('info'),

                Stat::make('Back from Accounting', number_format($returnedFromAccountingCount))
                    ->description('Returned by Accounting for revision')
                    ->color($returnedFromAccountingCount > 0 ? 'danger' : 'success'),

                Stat::make('Back from GM', number_format($returnedFromGmCount))
                    ->description('Returned by GM for revision')
                    ->color($returnedFromGmCount > 0 ? 'danger' : 'success'),

                Stat::make('Urgent on Desk', number_format($urgentCount))
                    ->description('Urgent PRs on Purchasing desk')
                    ->color($urgentCount > 0 ? 'danger' : 'success'),

                Stat::make('Overdue 3+ Days', number_format($overdueCount))
                    ->description('No activity for at least 3 days on Purchasing desk')
                    ->color($overdueCount > 0 ? 'danger' : 'success'),
            ];
        }

        if ($this->isAccountingUser($user)) {
            $deskQuery = PurchaseRequest::query()
                ->whereIn('status', $this->accountingVisibleStatuses());

            $onDeskCount = (clone $deskQuery)->count();

            $newFromPurchasingCount = (clone $deskQuery)
                ->where('status', 'submitted_to_accounting')
                ->count();

            $returnedFromGmCount = (clone $deskQuery)
                ->where('status', 'revision_to_accounting_from_gm')
                ->count();

            $onHoldCount = (clone $deskQuery)
                ->where('status', 'on_hold_by_accounting')
                ->count();

            $urgentCount = (clone $deskQuery)
                ->where('priority', 'urgent')
                ->count();

            $overdueCount = $this->countOverdue((clone $deskQuery), 3);

            return [
                Stat::make('On Accounting Desk', number_format($onDeskCount))
                    ->description('PRs currently waiting on Accounting')
                    ->color('info'),

                Stat::make('New from Purchasing', number_format($newFromPurchasingCount))
                    ->description('Submitted by Purchasing')
                    ->color('warning'),

                Stat::make('Back from GM', number_format($returnedFromGmCount))
                    ->description('Returned by GM to Accounting')
                    ->color($returnedFromGmCount > 0 ? 'danger' : 'success'),

                Stat::make('On Hold', number_format($onHoldCount))
                    ->description('On hold by Accounting')
                    ->color($onHoldCount > 0 ? 'gray' : 'success'),

                Stat::make('Urgent on Desk', number_format($urgentCount))
                    ->description('Urgent PRs on Accounting desk')
                    ->color($urgentCount > 0 ? 'danger' : 'success'),

                Stat::make('Overdue 3+ Days', number_format($overdueCount))
                    ->description('No activity for at least 3 days on Accounting desk')
                    ->color($overdueCount > 0 ? 'danger' : 'success'),
            ];
        }

        if ($this->isGmUser($user)) {
            $deskQuery = PurchaseRequest::query()
                ->whereIn('status', $this->gmVisibleStatuses());

            $onDeskCount = (clone $deskQuery)->count();

            $newFromAccountingCount = (clone $deskQuery)
                ->where('status', 'submitted_to_gm')
                ->count();

            $onHoldCount = (clone $deskQuery)
                ->where('status', 'on_hold_by_gm')
                ->count();

            $urgentCount = (clone $deskQuery)
                ->where('priority', 'urgent')
                ->count();

            $overdueCount = $this->countOverdue((clone $deskQuery), 3);

            $approvedThisMonth = PurchaseRequest::query()
                ->where('status', 'gm_approved')
                ->where('updated_at', '>=', $monthStart)
                ->count();

            return [
                Stat::make('On GM Desk', number_format($onDeskCount))
                    ->description('PRs currently waiting on GM')
                    ->color('danger'),

                Stat::make('New from Accounting', number_format($newFromAccountingCount))
                    ->description('Submitted by Accounting')
                    ->color('info'),

                Stat::make('On Hold', number_format($onHoldCount))
                    ->description('On hold by GM')
                    ->color($onHoldCount > 0 ? 'gray' : 'success'),

                Stat::make('Urgent on Desk', number_format($urgentCount))
                    ->description('Urgent PRs on GM desk')
                    ->color($urgentCount > 0 ? 'danger' : 'success'),

                Stat::make('Overdue 3+ Days', number_format($overdueCount))
                    ->description('No activity for at least 3 days on GM desk')
                    ->color($overdueCount > 0 ? 'danger' : 'success'),

                Stat::make('Approved This Month', number_format($approvedThisMonth))
                    ->description('Approved by GM since ' . $monthStart->format('d M Y'))
                    ->color('success'),
            ];
        }

        if ($this->isFinancialControllerUser($user)) {
            $deskQuery = PurchaseRequest::query()
                ->whereIn('status', $this->financialControllerVisibleStatuses());

            $onDeskCount = (clone $deskQuery)->count();

            $newFromGmCount = (clone $deskQuery)
                ->where('status', 'gm_approved')
                ->count();

            $waitingPaymentCount = (clone $deskQuery)
                ->where('status', 'waiting_payment_by_fc')
                ->count();

            $paidOrArrivedCount = (clone $deskQuery)
                ->whereIn('status', [
                    'paid_to_vendor_by_fc',
                    'item_arrived_by_fc',
                ])
                ->count();

            $waitingFinalHandoverCount = (clone $deskQuery)
                ->where('status', 'received_by_requester_by_fc')
                ->count();

            $onHoldCount = (clone $deskQuery)
                ->where('status', 'on_hold_by_fc')
                ->count();

            $completedThisMonth = PurchaseRequest::query()
                ->where('status', 'approved')
                ->whereNotNull('approved_at')
                ->where('approved_at', '>=', $monthStart)
                ->count();

            return [
                Stat::make('On FC Desk', number_format($onDeskCount))
                    ->description('PRs currently waiting on Financial Controller')
                    ->color('success'),

                Stat::make('New from GM', number_format($newFromGmCount))
                    ->description('Just approved by GM')
                    ->color('warning'),

                Stat::make('Waiting Payment', number_format($waitingPaymentCount))
                    ->description('Waiting payment to vendor')
                    ->color($waitingPaymentCount > 0 ? 'warning' : 'success'),

                Stat::make('Paid / Item Arrived', number_format($paidOrArrivedCount))
                    ->description('Already paid or item already arrived')
                    ->color('primary'),

                Stat::make('Waiting Final Handover', number_format($waitingFinalHandoverCount))
                    ->description('Already received by requester, waiting completion')
                    ->color($waitingFinalHandoverCount > 0 ? 'info' : 'success'),

                Stat::make('On Hold / Completed', number_format($onHoldCount) . ' / ' . number_format($completedThisMonth))
                    ->description('On hold by FC / completed this month')
                    ->color($onHoldCount > 0 ? 'danger' : 'success'),
            ];
        }

        $waitingOnPurchasing = PurchaseRequest::query()
            ->whereIn('status', $this->purchasingVisibleStatuses())
            ->count();

        $waitingOnAccounting = PurchaseRequest::query()
            ->whereIn('status', $this->accountingVisibleStatuses())
            ->count();

        $waitingOnGm = PurchaseRequest::query()
            ->whereIn('status', $this->gmVisibleStatuses())
            ->count();

        $waitingOnFinancialController = PurchaseRequest::query()
            ->whereIn('status', $this->financialControllerVisibleStatuses())
            ->count();

        $overdueThreeDays = $this->countOverdue(
            PurchaseRequest::query()->whereIn('status', $this->allOpenStatuses()),
            3
        );

        $approvedThisMonth = PurchaseRequest::query()
            ->whereIn('status', ['approved', 'received_by_requester_by_fc'])
            ->where(function (Builder $query) use ($monthStart) {
                $query->where(function (Builder $subQuery) use ($monthStart) {
                    $subQuery->whereNotNull('approved_at')
                        ->where('approved_at', '>=', $monthStart);
                })->orWhere(function (Builder $subQuery) use ($monthStart) {
                    $subQuery->whereNull('approved_at')
                        ->where('updated_at', '>=', $monthStart);
                });
            })
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
                ->description('Approved / completed since ' . $monthStart->format('d M Y'))
                ->color('primary'),
        ];
    }

    protected function countOverdue(Builder $query, int $days = 3): int
    {
        $threshold = now()->subDays($days);

        return $query
            ->where(function (Builder $builder) use ($threshold) {
                $builder
                    ->where(function (Builder $subQuery) use ($threshold) {
                        $subQuery->whereNotNull('last_activity_at')
                            ->where('last_activity_at', '<=', $threshold);
                    })
                    ->orWhere(function (Builder $subQuery) use ($threshold) {
                        $subQuery->whereNull('last_activity_at')
                            ->whereNotNull('current_status_at')
                            ->where('current_status_at', '<=', $threshold);
                    })
                    ->orWhere(function (Builder $subQuery) use ($threshold) {
                        $subQuery->whereNull('last_activity_at')
                            ->whereNull('current_status_at')
                            ->where('updated_at', '<=', $threshold);
                    });
            })
            ->count();
    }

    protected function requesterDraftStatuses(): array
    {
        return [
            'draft',
        ];
    }

    protected function returnedToRequesterStatuses(): array
    {
        return [
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm',
        ];
    }

    protected function requesterWaitingApprovalStatuses(): array
    {
        return [
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
    }

    protected function requesterWaitingPaymentStatuses(): array
    {
        return [
            'waiting_payment_by_fc',
            'on_hold_by_fc',
        ];
    }

    protected function requesterPaidOrArrivedStatuses(): array
    {
        return [
            'paid_to_vendor_by_fc',
            'item_arrived_by_fc',
        ];
    }

    protected function requesterCompletedStatuses(): array
    {
        return [
            'received_by_requester_by_fc',
            'approved',
        ];
    }

    protected function purchasingVisibleStatuses(): array
    {
        return [
            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
        ];
    }

    protected function accountingVisibleStatuses(): array
    {
        return [
            'submitted_to_accounting',
            'on_hold_by_accounting',
            'revision_to_accounting_from_gm',
        ];
    }

    protected function gmVisibleStatuses(): array
    {
        return [
            'submitted_to_gm',
            'on_hold_by_gm',
        ];
    }

    protected function financialControllerVisibleStatuses(): array
    {
        return [
            'gm_approved',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'item_arrived_by_fc',
            'received_by_requester_by_fc',
            'on_hold_by_fc',
        ];
    }

    protected function allOpenStatuses(): array
    {
        return [
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
    }

    protected function getUserRole(?User $user): string
    {
        return strtolower(trim((string) ($user?->role ?? '')));
    }

    protected function isAdminUser(?User $user): bool
    {
        $role = $this->getUserRole($user);

        if (in_array($role, ['admin', 'administrator', 'super_admin', 'super-admin'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isAdmin')
            && $user->isAdmin();
    }

    protected function isOwnerUser(?User $user): bool
    {
        $role = $this->getUserRole($user);

        if (in_array($role, ['owner'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isOwner')
            && $user->isOwner();
    }

    protected function isRequesterUser(?User $user): bool
    {
        $role = $this->getUserRole($user);

        if (in_array($role, ['requester'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isRequester')
            && $user->isRequester();
    }

    protected function isPurchasingUser(?User $user): bool
    {
        $role = $this->getUserRole($user);

        if (in_array($role, ['purchasing'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isPurchasing')
            && $user->isPurchasing();
    }

    protected function isAccountingUser(?User $user): bool
    {
        $role = $this->getUserRole($user);

        if (in_array($role, ['accounting'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isAccounting')
            && $user->isAccounting();
    }

    protected function isGmUser(?User $user): bool
    {
        $role = $this->getUserRole($user);

        if (in_array($role, ['gm', 'general_manager', 'general-manager'], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isGm')
            && $user->isGm();
    }

    protected function isFinancialControllerUser(?User $user): bool
    {
        $role = $this->getUserRole($user);

        if (in_array($role, [
            'financial_controller',
            'financial-controller',
            'financial controller',
            'fc',
            'cost_controller',
            'cost-controller',
            'cost controller',
        ], true)) {
            return true;
        }

        return $user instanceof User
            && method_exists($user, 'isFinancialController')
            && $user->isFinancialController();
    }
}
