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
                    ->columns(2),

                Section::make('Requested Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('Items')
                            ->defaultItems(1)
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
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
                                    ->maxLength(255),

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

                                TextInput::make('qty')
                                    ->numeric()
                                    ->required()
                                    ->default(1),

                                TextInput::make('unit')
                                    ->placeholder('pcs, box, liter, set')
                                    ->maxLength(100),

                                DatePicker::make('needed_by')
                                    ->label('Needed By'),

                                RichEditor::make('purpose')
                                    ->label('Purpose')
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
                                            ->maxLength(255),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Vendor Offers')
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
                            ->schema([
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
                                    ->maxLength(191),

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
                                    ->prefix('IDR'),

                                TextInput::make('currency')
                                    ->label('Currency')
                                    ->default('IDR')
                                    ->required()
                                    ->maxLength(10),

                                TextInput::make('lead_time_days')
                                    ->label('Lead Time (Days)')
                                    ->numeric()
                                    ->minValue(0),

                                TextInput::make('offer_rank')
                                    ->label('Offer Rank')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(3),

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
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn(?PurchaseRequest $record): bool => static::canShowVendorOffers($record))
                    ->columnSpanFull(),
            ]);
    }
}
