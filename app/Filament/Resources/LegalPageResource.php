<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalPageResource\Pages;
use App\Models\LegalPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LegalPageResource extends Resource
{
    protected static ?string $model = LegalPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Legal & FAQ';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Page')
                ->schema([
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Routes are fixed: terms, privacy, faq. Slugs are not editable.'),
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(120),
                    Forms\Components\TextInput::make('last_updated_label')
                        ->label('Last updated label')
                        ->maxLength(80)
                        ->placeholder('e.g. 2026-05-17')
                        ->helperText('Shown to customers under the title.'),
                ])
                ->columns(3),

            Forms\Components\Section::make('Content')
                ->schema([
                    Forms\Components\RichEditor::make('body')
                        ->label('Body')
                        ->required()
                        ->disableToolbarButtons(['attachFiles'])
                        ->fileAttachmentsDisk('public')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'terms' => 'Terms',
                        'privacy' => 'Privacy',
                        'faq' => 'FAQ',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('last_updated_label')->label('Last updated'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->since()->label('Saved'),
            ])
            ->defaultSort('slug')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegalPages::route('/'),
            'edit' => Pages\EditLegalPage::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'hq_admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'hq_admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        // Fixed set of 3 pages — seeded once, no new rows.
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
