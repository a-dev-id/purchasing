<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class NeedsAttentionPurchaseRequests extends TableWidget
{
    protected static ?string $heading = 'Needs Attention';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('request_number')
                    ->label('PR')
                    ->placeholder('Draft')
                    ->searchable(),

                TextColumn::make('requester_name')
                    ->label('Requester')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn(PurchaseRequest $record) => $record->requester_name),

                TextColumn::make('department_name')
                    ->label('Department')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('title')
                    ->label('Request')
                    ->searchable()
                    ->wrap()
                    ->limit(40)
                    ->tooltip(fn(PurchaseRequest $record) => $record->title),

                TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? ucfirst($state) : '-')
                    ->color(fn(?string $state) => match ($state) {
                        'urgent' => 'danger',
                        'normal' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => $this->statusLabel($state))
                    ->color(fn(?string $state): string => $this->statusColor($state)),

                TextColumn::make('current_desk')
                    ->label('Desk')
                    ->badge()
                    ->state(fn(PurchaseRequest $record): string => $this->deskLabel($record->status))
                    ->color(fn(string $state): string => $this->deskColor($state)),

                TextColumn::make('waiting_days')
                    ->label('Days Waiting')
                    ->state(function (PurchaseRequest $record): int {
                        $since = $record->last_activity_at
                            ?? $record->current_status_at
                            ?? $record->submitted_at
                            ?? $record->created_at;

                        return $since ? $since->diffInDays(now()) : 0;
                    })
                    ->badge()
                    ->color(function (int $state): string {
                        if ($state >= 6) {
                            return 'danger';
                        }

                        if ($state >= 3) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                TextColumn::make('last_activity_at')
                    ->label('Last Activity')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(fn(PurchaseRequest $record): string => PurchaseRequestResource::canEdit($record) ? 'Open PR' : 'View Form')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn(PurchaseRequest $record): string => PurchaseRequestResource::canEdit($record)
                        ? PurchaseRequestResource::getUrl('edit', ['record' => $record])
                        : PurchaseRequestResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No PR needs attention right now');
    }

    protected function getTableQuery(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        $query = PurchaseRequest::query();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->isAdminUser($user) || $this->isOwnerUser($user)) {
            return $query
                ->whereIn('status', $this->allNonCancelledStatuses())
                ->orderByRaw("
                    CASE
                        WHEN priority = 'urgent' THEN 0
                        ELSE 1
                    END
                ")
                ->orderBy('last_activity_at')
                ->orderByDesc('id');
        }

        if ($this->isRequesterUser($user)) {
            return $query
                ->where('department_name', $user->department_name)
                ->whereIn('status', $this->allNonCancelledStatuses())
                ->orderByRaw("
                    CASE
                        WHEN priority = 'urgent' THEN 0
                        ELSE 1
                    END
                ")
                ->orderBy('last_activity_at')
                ->orderByDesc('id');
        }

        if ($this->isPurchasingUser($user)) {
            return $query
                ->whereIn('status', $this->purchasingVisibleStatuses())
                ->orderByRaw("
                    CASE
                        WHEN priority = 'urgent' THEN 0
                        ELSE 1
                    END
                ")
                ->orderBy('last_activity_at')
                ->orderByDesc('id');
        }

        if ($this->isAccountingUser($user)) {
            return $query
                ->whereIn('status', $this->accountingVisibleStatuses())
                ->orderByRaw("
                    CASE
                        WHEN priority = 'urgent' THEN 0
                        ELSE 1
                    END
                ")
                ->orderBy('last_activity_at')
                ->orderByDesc('id');
        }

        if ($this->isGmUser($user)) {
            return $query
                ->whereIn('status', $this->gmVisibleStatuses())
                ->orderByRaw("
                    CASE
                        WHEN priority = 'urgent' THEN 0
                        ELSE 1
                    END
                ")
                ->orderBy('last_activity_at')
                ->orderByDesc('id');
        }

        if ($this->isFinancialControllerUser($user)) {
            return $query
                ->whereIn('status', $this->financialControllerVisibleStatuses())
                ->orderByRaw("
                    CASE
                        WHEN priority = 'urgent' THEN 0
                        ELSE 1
                    END
                ")
                ->orderBy('last_activity_at')
                ->orderByDesc('id');
        }

        return $query->whereRaw('1 = 0');
    }

    protected function allNonCancelledStatuses(): array
    {
        return [
            'draft',
            'submitted',
            'revision_from_purchasing',
            'submitted_to_accounting',
            'on_hold_by_accounting',
            'revision_from_accounting',
            'revision_to_purchasing_from_accounting',
            'revision_to_requester_from_accounting',
            'submitted_to_gm',
            'on_hold_by_gm',
            'revision_from_gm',
            'revision_to_purchasing_from_gm',
            'revision_to_accounting_from_gm',
            'revision_to_requester_from_gm',
            'gm_approved',

            'pending',
            'on_progress',
            'waiting_payment',
            'paid_to_vendor',
            'on_shipping',
            'received_by_requester',

            'pending_by_fc',
            'on_progress_by_fc',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'on_shipping_by_fc',
            'item_arrived_by_fc',
            'received_by_requester_by_fc',
            'on_hold_by_fc',

            'approved',
            'rejected',
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

            'pending',
            'on_progress',
            'waiting_payment',
            'paid_to_vendor',
            'on_shipping',

            'pending_by_fc',
            'on_progress_by_fc',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'on_shipping_by_fc',
            'item_arrived_by_fc',

            'on_hold_by_fc',
        ];
    }

    protected function statusLabel(?string $state): string
    {
        return match ($state) {
            'draft' => 'Draft',
            'submitted' => 'To Purchasing',
            'revision_from_purchasing' => 'Back to Requester',
            'revision_from_accounting' => 'Back to Requester',
            'revision_from_gm' => 'Back to Requester',
            'revision_to_purchasing_from_accounting' => 'Back to Purchasing',
            'revision_to_requester_from_accounting' => 'Back to Requester',
            'submitted_to_accounting' => 'To Accounting',
            'on_hold_by_accounting' => 'Hold Accounting',
            'submitted_to_gm' => 'To GM',
            'revision_to_purchasing_from_gm' => 'Back to Purchasing',
            'revision_to_accounting_from_gm' => 'Back to Accounting',
            'revision_to_requester_from_gm' => 'Back to Requester',
            'on_hold_by_gm' => 'Hold GM',
            'gm_approved' => 'To Financial Controller',

            'pending' => 'Pending',
            'on_progress' => 'On Progress',
            'waiting_payment' => 'Waiting Payment',
            'paid_to_vendor' => 'Paid to Vendor',
            'on_shipping' => 'On Shipping',
            'received_by_requester' => 'Received by Requester (Done)',

            'pending_by_fc' => 'Pending',
            'on_progress_by_fc' => 'On Progress',
            'waiting_payment_by_fc' => 'Waiting Payment',
            'paid_to_vendor_by_fc' => 'Paid to Vendor',
            'on_shipping_by_fc' => 'On Shipping',
            'item_arrived_by_fc' => 'On Shipping',
            'received_by_requester_by_fc' => 'Received by Requester (Done)',
            'on_hold_by_fc' => 'Hold Financial Controller',

            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            null, '' => '-',
            default => str($state)->replace('_', ' ')->title()->toString(),
        };
    }

    protected function statusColor(?string $state): string
    {
        return match ($state) {
            'draft' => 'gray',

            'submitted',
            'submitted_to_accounting',
            'submitted_to_gm',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
            'revision_to_accounting_from_gm',
            'gm_approved',
            'pending',
            'pending_by_fc',
            'waiting_payment',
            'waiting_payment_by_fc' => 'warning',

            'on_progress',
            'on_progress_by_fc',
            'on_shipping',
            'on_shipping_by_fc',
            'item_arrived_by_fc' => 'info',

            'paid_to_vendor',
            'paid_to_vendor_by_fc',
            'received_by_requester',
            'received_by_requester_by_fc',
            'approved' => 'success',

            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm',
            'rejected',
            'cancelled' => 'danger',

            'on_hold_by_accounting',
            'on_hold_by_gm',
            'on_hold_by_fc' => 'gray',

            default => 'gray',
        };
    }

    protected function deskLabel(?string $state): string
    {
        return match ($state) {
            'draft' => 'Requester',
            'submitted' => 'Purchasing',
            'revision_from_purchasing' => 'Requester',
            'submitted_to_accounting' => 'Accounting',
            'revision_from_accounting' => 'Requester',
            'revision_to_purchasing_from_accounting' => 'Purchasing',
            'revision_to_requester_from_accounting' => 'Requester',
            'on_hold_by_accounting' => 'Accounting',
            'submitted_to_gm' => 'GM',
            'revision_from_gm' => 'Requester',
            'revision_to_purchasing_from_gm' => 'Purchasing',
            'revision_to_accounting_from_gm' => 'Accounting',
            'revision_to_requester_from_gm' => 'Requester',
            'on_hold_by_gm' => 'GM',
            'gm_approved' => 'Financial Controller',

            'pending',
            'on_progress',
            'waiting_payment',
            'paid_to_vendor',
            'on_shipping',
            'pending_by_fc',
            'on_progress_by_fc',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'on_shipping_by_fc',
            'item_arrived_by_fc',
            'on_hold_by_fc' => 'Financial Controller',

            'received_by_requester',
            'received_by_requester_by_fc',
            'approved' => 'Done',

            'rejected' => 'Stopped',
            'cancelled' => 'Cancelled',
            default => '-',
        };
    }

    protected function deskColor(string $state): string
    {
        return match ($state) {
            'Purchasing' => 'warning',
            'Accounting' => 'info',
            'GM' => 'danger',
            'Financial Controller' => 'success',
            'Requester' => 'gray',
            'Done' => 'success',
            'Stopped' => 'danger',
            'Cancelled' => 'danger',
            default => 'gray',
        };
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
