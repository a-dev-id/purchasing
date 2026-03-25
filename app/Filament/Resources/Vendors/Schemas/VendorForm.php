<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vendor Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Vendor Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('category')
                            ->label('Category')
                            ->maxLength(100),

                        TextInput::make('contact_person')
                            ->label('Contact Person')
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        Textarea::make('address')
                            ->label('Address')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Notes')
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
