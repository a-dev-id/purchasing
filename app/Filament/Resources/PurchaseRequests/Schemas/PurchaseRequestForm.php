<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Vendor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestForm
{
    protected static function getCurrentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected static function isRequesterUser(): bool
    {
        return static::getCurrentUser()?->isRequester() ?? false;
    }

    protected static function isPurchasingUser(): bool
    {
        return static::getCurrentUser()?->isPurchasing() ?? false;
    }

    protected static function isAccountingUser(): bool
    {
        return static::getCurrentUser()?->isAccounting() ?? false;
    }

    protected static function isAdminUser(): bool
    {
        return static::getCurrentUser()?->isAdmin() ?? false;
    }

    protected static function getCurrentDepartmentName(): ?string
    {
        return static::getCurrentUser()?->department_name;
    }

    protected static function getLatestRevisionMessage(?PurchaseRequest $record): ?string
    {
        if (! $record) {
            return null;
        }

        $statusActionMap = [
            'revision_from_purchasing' => 'revision_from_purchasing',
            'revision_from_accounting' => 'revision_from_accounting',
            'revision_from_gm' => 'revision_from_gm',
            'revision_to_purchasing_from_accounting' => 'revision_to_purchasing_from_accounting',
            'revision_to_requester_from_accounting' => 'revision_to_requester_from_accounting',
            'revision_to_purchasing_from_gm' => 'revision_to_purchasing_from_gm',
            'revision_to_accounting_from_gm' => 'revision_to_accounting_from_gm',
            'revision_to_requester_from_gm' => 'revision_to_requester_from_gm',
        ];

        $currentAction = $statusActionMap[$record->status] ?? null;

        if ($currentAction) {
            $currentLog = $record->logs()
                ->where('action', $currentAction)
                ->whereNotNull('message')
                ->where('message', '!=', '')
                ->latest('acted_at')
                ->latest('id')
                ->first();

            if ($currentLog) {
                return $currentLog->message;
            }
        }

        $fallbackLog = $record->logs()
            ->whereIn('action', array_values($statusActionMap))
            ->whereNotNull('message')
            ->where('message', '!=', '')
            ->latest('acted_at')
            ->latest('id')
            ->first();

        return $fallbackLog?->message;
    }

    protected static function canShowVendorOffers(?PurchaseRequest $record): bool
    {
        $user = static::getCurrentUser();

        if (! $user || ! $record) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPurchasing()) {
            return in_array($record->status, [
                'submitted',
                'revision_from_purchasing',
                'submitted_to_accounting',
                'revision_from_accounting',
                'revision_to_purchasing_from_accounting',
                'on_hold_by_accounting',
                'submitted_to_gm',
                'on_hold_by_gm',
                'revision_from_gm',
                'revision_to_purchasing_from_gm',
                'revision_to_accounting_from_gm',
                'revision_to_requester_from_gm',
                'approved',
                'rejected',
            ], true);
        }

        if ($user->isAccounting()) {
            return in_array($record->status, [
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
                'approved',
                'rejected',
            ], true);
        }

        if ($user->isGm()) {
            return in_array($record->status, [
                'submitted_to_gm',
                'on_hold_by_gm',
                'revision_to_purchasing_from_gm',
                'revision_to_accounting_from_gm',
                'revision_to_requester_from_gm',
                'approved',
                'rejected',
            ], true);
        }

        return false;
    }

    protected static function vendorOfferSchema(): array
    {
        return [
            Select::make('vendor_id')
                ->label('Select Existing Vendor')
                ->searchable()
                ->preload()
                ->live()
                ->options(fn() => Vendor::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->limit(50)
                    ->pluck('name', 'id')
                    ->toArray())
                ->getSearchResultsUsing(fn(string $search): array => Vendor::query()
                    ->where('is_active', true)
                    ->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%")
                            ->orWhere('contact_person', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orderBy('name')
                    ->limit(20)
                    ->pluck('name', 'id')
                    ->toArray())
                ->getOptionLabelUsing(fn($value): ?string => Vendor::find($value)?->name)
                ->afterStateUpdated(function ($state, callable $set) {
                    if (blank($state)) {
                        return;
                    }

                    $vendor = Vendor::find($state);

                    if (! $vendor) {
                        return;
                    }

                    $set('vendor_name', $vendor->name);
                    $set('contact_person', $vendor->contact_person);
                    $set('phone', $vendor->phone);
                    $set('email', $vendor->email);
                })
                ->columnSpanFull(),

            TextInput::make('vendor_name')
                ->label('Vendor Name')
                ->required()
                ->maxLength(191)
                ->live(),

            TextInput::make('contact_person')
                ->label('Contact Person')
                ->maxLength(191),

            TextInput::make('phone')
                ->label('Phone')
                ->tel()
                ->maxLength(191),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(191),

            TextInput::make('offer_total')
                ->label('Offer Total')
                ->numeric()
                ->live(),

            TextInput::make('currency')
                ->label('Currency')
                ->default('IDR')
                ->required()
                ->maxLength(10)
                ->live(),

            Textarea::make('offer_notes')
                ->label('Offer Notes')
                ->rows(3)
                ->columnSpanFull(),

            FileUpload::make('quotation_file')
                ->label('Quotation File')
                ->disk('public')
                ->directory('purchase-request-quotations')
                ->visibility('public')
                ->downloadable()
                ->openable()
                ->columnSpanFull(),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Revision Notes')
                    ->schema([
                        Textarea::make('latest_revision_note')
                            ->label('Latest Revision Note')
                            ->rows(4)
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Textarea $component, ?PurchaseRequest $record) {
                                $component->state(static::getLatestRevisionMessage($record));
                            }),
                    ])
                    ->visible(fn(?PurchaseRequest $record): bool => filled(static::getLatestRevisionMessage($record)))
                    ->columnSpanFull(),

                Section::make('Request Information')
                    ->schema([
                        TextInput::make('requester_name')
                            ->label('Requester Name')
                            ->placeholder('Example: Made Suartana')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('department_name')
                            ->label('Department')
                            ->required()
                            ->maxLength(255)
                            ->default(fn() => static::getCurrentDepartmentName())
                            ->disabled(fn() => static::isRequesterUser())
                            ->dehydrated(),

                        TextInput::make('title')
                            ->label('Request Name')
                            ->placeholder('Example: Mouse and keyboard for Front Office')
                            ->required()
                            ->maxLength(255),

                        Select::make('priority')
                            ->options([
                                'urgent' => 'Urgent',
                                'normal' => 'Normal',
                            ])
                            ->default('normal')
                            ->required(),

                        DatePicker::make('date_needed')
                            ->label('Date Needed')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->required(),

                        Select::make('vendor_comparison_mode')
                            ->label('Vendor Comparison Mode')
                            ->options([
                                'item' => 'Mix item(s)',
                                'pr' => '1 PR, 3 Vendors',
                            ])
                            ->required()
                            ->default('item')
                            ->visible(function (): bool {
                                $user = Auth::user();

                                return $user instanceof User && ($user->isPurchasing() || $user->isAdmin());
                            })
                            ->dehydrated(function (): bool {
                                $user = Auth::user();

                                return $user instanceof User && ($user->isPurchasing() || $user->isAdmin());
                            }),

                        RichEditor::make('request_notes')
                            ->label('Request Description')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Repeater::make('items')
                    ->relationship()
                    ->label('Items')
                    ->defaultItems(1)
                    ->reorderable()
                    ->collapsible()
                    ->collapsed()
                    ->cloneable()
                    ->itemLabel(function (array $state): ?string {
                        $itemName = trim((string) ($state['item_name'] ?? ''));

                        if ($itemName === '') {
                            return 'New Item';
                        }

                        return \Illuminate\Support\Str::limit($itemName, 60);
                    })
                    ->schema([
                        Select::make('item_id')
                            ->label('Select Existing Item')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->options(fn() => Item::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->toArray())
                            ->getSearchResultsUsing(fn(string $search): array => Item::query()
                                ->where('is_active', true)
                                ->where(function ($query) use ($search) {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('sku', 'like', "%{$search}%")
                                        ->orWhere('brand', 'like', "%{$search}%")
                                        ->orWhere('category', 'like', "%{$search}%");
                                })
                                ->orderBy('name')
                                ->limit(20)
                                ->pluck('name', 'id')
                                ->toArray())
                            ->getOptionLabelUsing(fn($value): ?string => Item::find($value)?->name)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (blank($state)) {
                                    return;
                                }

                                $item = Item::find($state);

                                if (! $item) {
                                    return;
                                }

                                $set('item_name', $item->name);
                                $set('unit', $item->default_unit);
                                $set('specification', $item->default_specification);
                            })
                            ->columnSpanFull(),

                        TextInput::make('item_name')
                            ->label('Item Name')
                            ->required()
                            ->maxLength(255)
                            ->live()
                            ->columns(3),

                        TextInput::make('qty')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->columns(3),

                        TextInput::make('unit')
                            ->placeholder('pcs, box, liter, set')
                            ->maxLength(100)
                            ->columns(3),

                        RichEditor::make('specification')
                            ->label('Specification')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),

                        Repeater::make('photos')
                            ->relationship()
                            ->label('Item Photos')
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                FileUpload::make('file_path')
                                    ->label('Photo')
                                    ->image()
                                    ->disk('public')
                                    ->directory('purchase-request-items')
                                    ->visibility('public')
                                    ->required(),

                                TextInput::make('file_name')
                                    ->label('Photo Name')
                                    ->live()
                                    ->maxLength(255),
                            ])
                            ->itemLabel(function (array $state): string {
                                static $photoIndex = 0;
                                $photoIndex++;

                                $photoName = trim((string) ($state['file_name'] ?? ''));

                                if ($photoName !== '') {
                                    return \Illuminate\Support\Str::limit($photoName, 40);
                                }

                                return 'Photo ' . $photoIndex;
                            })
                            ->extraAttributes([
                                'class' => 'rounded-xl border border-gray-200 bg-gray-50 p-1',
                            ])
                            ->columns(2)
                            ->columnSpanFull(),

                        Section::make('Item Vendor Offers')
                            ->extraAttributes([
                                'class' => 'vendor-offers-section',
                            ])
                            ->schema([
                                Repeater::make('vendorOffers')
                                    ->relationship('vendorOffers')
                                    ->hiddenLabel()
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Vendor Offer')
                                    ->maxItems(3)
                                    ->reorderable()
                                    ->collapsible()
                                    ->collapsed()
                                    ->cloneable()
                                    ->itemLabel(function (array $state): ?string {
                                        $vendorName = trim((string) ($state['vendor_name'] ?? ''));
                                        $currency = trim((string) ($state['currency'] ?? 'IDR'));
                                        $offerTotal = $state['offer_total'] ?? null;

                                        $label = $vendorName !== '' ? $vendorName : 'New Vendor Offer';

                                        if ($offerTotal !== null && $offerTotal !== '') {
                                            $formattedTotal = number_format((float) $offerTotal, 0, ',', '.');
                                            $label .= ' - ' . $currency . ' ' . $formattedTotal;
                                        }

                                        return $label;
                                    })
                                    ->schema(static::vendorOfferSchema())
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->visible(function (Get $get, \Livewire\Component $livewire): bool {
                                        $purchaseRequest = method_exists($livewire, 'getRecord')
                                            ? $livewire->getRecord()
                                            : null;

                                        return (($get('../../vendor_comparison_mode') ?? $purchaseRequest?->vendor_comparison_mode ?? 'pr') === 'item')
                                            && static::canShowVendorOffers($purchaseRequest);
                                    }),
                            ])
                            ->compact()
                            ->extraAttributes([
                                'class' => 'rounded-xl border border-gray-200 bg-gray-50 p-1',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('PR Vendor Offers')
                    ->schema([
                        Repeater::make('vendorOffers')
                            ->relationship('vendorOffers')
                            ->label('Vendor Offers')
                            ->defaultItems(0)
                            ->addActionLabel('Add Vendor Offer')
                            ->maxItems(3)
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->schema(static::vendorOfferSchema())
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn(?PurchaseRequest $record, Get $get): bool => (
                        ($get('vendor_comparison_mode') ?? $record?->vendor_comparison_mode ?? 'pr') === 'pr'
                    ) && static::canShowVendorOffers($record))
                    ->columnSpanFull(),
            ]);
    }
}
