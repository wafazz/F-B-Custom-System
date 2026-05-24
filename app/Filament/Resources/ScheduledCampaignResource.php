<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduledCampaignResource\Pages;
use App\Jobs\SendScheduledCampaign;
use App\Models\ScheduledCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduledCampaignResource extends Resource
{
    protected static ?string $model = ScheduledCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Scheduled Push';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    // Restricted to super admin for now — hidden from the nav AND blocked at
    // the route level for everyone else.
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Notification')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Campaign name (internal)')
                        ->required()
                        ->maxLength(120),
                    Forms\Components\TextInput::make('title')
                        ->label('Push title')
                        ->required()
                        ->maxLength(80),
                    Forms\Components\Textarea::make('body')
                        ->label('Push message')
                        ->required()
                        ->maxLength(180)
                        ->rows(2)
                        ->helperText('Use {name} to insert the customer\'s first name.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('url')
                        ->label('Deep-link URL')
                        ->default('/')
                        ->maxLength(200)
                        ->helperText('Where the customer lands when they tap it.'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Schedule')
                ->schema([
                    Forms\Components\Select::make('frequency')
                        ->required()
                        ->default('once')
                        ->live()
                        ->options([
                            'once' => 'Once — on a specific date & time',
                            'daily' => 'Daily — every day at a set time',
                        ]),
                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Send at')
                        ->seconds(false)
                        ->required(fn (Forms\Get $get) => $get('frequency') === 'once')
                        ->visible(fn (Forms\Get $get) => $get('frequency') === 'once'),
                    Forms\Components\TimePicker::make('run_time')
                        ->label('Time of day')
                        ->seconds(false)
                        ->required(fn (Forms\Get $get) => $get('frequency') === 'daily')
                        ->visible(fn (Forms\Get $get) => $get('frequency') === 'daily'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('frequency')
                    ->badge()
                    ->color(fn (string $state) => $state === 'daily' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('schedule')
                    ->label('When')
                    ->state(fn (ScheduledCampaign $r) => $r->frequency === 'daily'
                        ? 'Daily · '.\Illuminate\Support\Str::of((string) $r->run_time)->substr(0, 5)
                        : ($r->scheduled_at?->format('d M Y, H:i') ?? '—')),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('last_sent_at')
                    ->label('Last sent')
                    ->dateTime('d M, H:i')
                    ->placeholder('Never'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('sendNow')
                    ->label('Send now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Send this push to all opted-in customers right now?')
                    ->action(function (ScheduledCampaign $record): void {
                        SendScheduledCampaign::dispatch($record->id);
                        $record->forceFill(['last_sent_at' => now()])->save();
                        Notification::make()
                            ->title('Campaign queued')
                            ->body('It\'s being sent to opted-in customers now.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduledCampaigns::route('/'),
            'create' => Pages\CreateScheduledCampaign::route('/create'),
            'edit' => Pages\EditScheduledCampaign::route('/{record}/edit'),
        ];
    }
}
