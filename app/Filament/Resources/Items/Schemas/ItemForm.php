<?php

namespace App\Filament\Resources\Items\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Item Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(100),

                        TextInput::make('category')
                            ->label('Category')
                            ->maxLength(100),

                        TextInput::make('brand')
                            ->label('Brand')
                            ->maxLength(100),

                        TextInput::make('default_unit')
                            ->label('Default Unit')
                            ->placeholder('pcs, box, liter, set')
                            ->maxLength(50),

                        TextInput::make('last_price')
                            ->label('Last Price')
                            ->numeric()
                            ->prefix('IDR'),

                        TextInput::make('currency')
                            ->label('Currency')
                            ->default('IDR')
                            ->required()
                            ->maxLength(10),

                        Textarea::make('default_specification')
                            ->label('Default Specification')
                            ->rows(4)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
