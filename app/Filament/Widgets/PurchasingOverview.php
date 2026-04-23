<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PurchasingOverview extends BaseWidget
{
    // protected function getStats(): array
    // {
    //     return [
    //         Stat::make('On FC Desk', $this->countByStatuses([
    //             'pending',
    //             'on_progress',
    //             'waiting_payment',
    //             'paid_to_vendor',
    //             'on_shipping',
    //             'on_hold_by_fc',

    //             // legacy support
    //             'pending_by_fc',
    //             'on_progress_by_fc',
    //             'waiting_payment_by_fc',
    //             'paid_to_vendor_by_fc',
    //             'on_shipping_by_fc',
    //             'item_arrived_by_fc',
    //         ]))
    //             ->description('PRs currently being handled by Financial Controller')
    //             ->color('primary'),

    //         Stat::make('New from GM', $this->countByStatuses([
    //             'gm_approved',
    //         ]))
    //             ->description('Just approved by GM and waiting for FC action')
    //             ->color('warning'),

    //         Stat::make('Pending', $this->countByStatuses([
    //             'pending',

    //             // legacy support
    //             'pending_by_fc',
    //         ]))
    //             ->description('Still pending on FC side')
    //             ->color('gray'),

    //         Stat::make('On Progress', $this->countByStatuses([
    //             'on_progress',

    //             // legacy support
    //             'on_progress_by_fc',
    //         ]))
    //             ->description('Currently being processed by FC')
    //             ->color('info'),

    //         Stat::make('Waiting Payment', $this->countByStatuses([
    //             'waiting_payment',

    //             // legacy support
    //             'waiting_payment_by_fc',
    //         ]))
    //             ->description('Waiting payment to vendor')
    //             ->color('warning'),

    //         Stat::make('Paid to Vendor', $this->countByStatuses([
    //             'paid_to_vendor',

    //             // legacy support
    //             'paid_to_vendor_by_fc',
    //         ]))
    //             ->description('Already paid to vendor')
    //             ->color('success'),

    //         Stat::make('On Shipping / Handover', $this->countByStatuses([
    //             'on_shipping',

    //             // legacy support
    //             'on_shipping_by_fc',
    //             'item_arrived_by_fc',
    //         ]))
    //             ->description('On delivery or waiting final handover to requester')
    //             ->color('info'),

    //         Stat::make('On Hold', $this->countByStatuses([
    //             'on_hold_by_fc',
    //         ]))
    //             ->description('Currently on hold by Financial Controller')
    //             ->color('danger'),

    //         Stat::make('Completed', $this->countByStatuses([
    //             'received_by_requester',
    //         ]))
    //             ->description('Already received by requester / done')
    //             ->color('success'),
    //     ];
    // }

    // protected function countByStatuses(array $statuses): int
    // {
    //     return PurchaseRequest::query()
    //         ->whereIn('status', $statuses)
    //         ->count();
    // }
}
