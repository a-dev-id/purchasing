<?php

namespace App\Filament\Resources\Vendors\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Vendor Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('contact_person')
                    ->label('Contact Person')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
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
