<?php

namespace App\Filament\Resources\Items\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
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

                        RichEditor::make('default_specification')
                            ->label('Default Specification')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpan(2),

                Section::make('Item Images')
                    ->schema([
                        Repeater::make('photos')
                            ->relationship()
                            ->hiddenLabel()
                            ->defaultItems(0)
                            ->collapsible()
                            ->reorderable()
                            ->itemLabel(function (array $state): ?string {
                                $fileName = trim((string) ($state['file_name'] ?? ''));

                                return $fileName !== '' ? $fileName : 'Image';
                            })
                            ->schema([
                                FileUpload::make('file_path')
                                    ->label('Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('item-photos')
                                    ->visibility('public')
                                    ->imagePreviewHeight('180')
                                    ->required(),

                                TextInput::make('file_name')
                                    ->label('Image Name')
                                    ->maxLength(255),
                            ])
                            ->columns(1)
                            ->addActionLabel('Add Image')
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }
}
