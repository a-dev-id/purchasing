<?php

namespace App\Filament\Resources\Items\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Item Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('category')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('default_unit')
                    ->label('Unit')
                    ->sortable(),

                TextColumn::make('last_price')
                    ->label('Last Price')
                    ->money(fn($record) => $record->currency ?: 'IDR')
                    ->sortable(),

                TextColumn::make('currency')
                    ->label('Currency')
                    ->badge()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
