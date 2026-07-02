<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuDisplayResource\Pages;
use App\Models\MenuDisplay;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MenuDisplayResource extends Resource
{
    protected static ?string $model = MenuDisplay::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Menu Board';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(60)
                ->placeholder('Front Counter TV / Drive-thru'),
            Forms\Components\TextInput::make('heading')
                ->maxLength(80)
                ->placeholder('Optional big title (defaults to branch name)'),
            Forms\Components\Select::make('branch_id')
                ->label('Branch')
                ->relationship('branch', 'name')
                ->searchable()
                ->placeholder('None (logo/name only)'),
            Forms\Components\Select::make('categories')
                ->label('Categories to show')
                ->relationship('categories', 'name')
                ->multiple()
                ->preload()
                ->required()
                ->helperText('Each category becomes a rotating slide with its products.'),
            Forms\Components\Select::make('layout')
                ->options([
                    'grid' => 'Grid — one category per slide',
                    'single' => 'Single — one product per slide',
                ])
                ->default('grid')
                ->required(),
            Forms\Components\TextInput::make('seconds_per_slide')
                ->numeric()
                ->minValue(3)
                ->maxValue(120)
                ->default(8)
                ->suffix('sec')
                ->required(),
            Forms\Components\Toggle::make('show_price')->default(true),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\FileUpload::make('posters')
                ->label('Full-screen poster slides')
                ->image()
                ->multiple()
                ->reorderable()
                ->directory('menu-posters')
                ->disk('public')
                ->visibility('public')
                ->maxFiles(10)
                ->maxSize(4096)
                ->imagePreviewHeight('120')
                ->columnSpanFull()
                ->helperText('Optional promo images shown full-screen in the rotation. Recommended 1920×1080 (16:9).'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('branch.code')->badge()->color('primary')->placeholder('—'),
                Tables\Columns\TextColumn::make('categories.name')->badge()->limitList(3)->label('Categories'),
                Tables\Columns\TextColumn::make('layout')->badge(),
                Tables\Columns\TextColumn::make('seconds_per_slide')->suffix('s')->label('Per slide'),
                Tables\Columns\IconColumn::make('show_price')->boolean()->label('Price'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('last_seen_at')->dateTime()->since()->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\Action::make('copyUrl')
                    ->label('Copy URL')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->action(fn (MenuDisplay $r) => Notification::make()
                        ->title('URL ready to copy')
                        ->body(route('menu-display.show', ['token' => $r->token]))
                        ->persistent()
                        ->send()),
                Tables\Actions\Action::make('regenerate')
                    ->label('Regenerate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (MenuDisplay $r) {
                        $r->update(['token' => Str::random(48)]);
                        Notification::make()->title('Token regenerated — old URL revoked')->success()->send();
                    }),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (MenuDisplay $r) => $r->is_active ? 'Disable' : 'Enable')
                    ->icon('heroicon-o-power')
                    ->color(fn (MenuDisplay $r) => $r->is_active ? 'danger' : 'success')
                    ->action(fn (MenuDisplay $r) => $r->update(['is_active' => ! $r->is_active])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuDisplays::route('/'),
            'create' => Pages\CreateMenuDisplay::route('/create'),
            'edit' => Pages\EditMenuDisplay::route('/{record}/edit'),
        ];
    }
}
