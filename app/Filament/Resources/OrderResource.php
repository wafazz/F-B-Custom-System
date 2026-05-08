<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Order')
                ->schema([
                    Forms\Components\TextInput::make('number')->disabled(),
                    Forms\Components\Select::make('branch_id')
                        ->relationship('branch', 'name')
                        ->disabled(),
                    Forms\Components\Select::make('status')
                        ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\Select::make('order_type')
                        ->options(collect(OrderType::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                        ->disabled(),
                    Forms\Components\TextInput::make('dine_in_table')->disabled(),
                    Forms\Components\TextInput::make('total')->prefix('RM')->disabled(),
                    Forms\Components\Select::make('payment_status')
                        ->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->name]))
                        ->disabled(),
                    Forms\Components\TextInput::make('payment_reference')->disabled(),
                    Forms\Components\Textarea::make('notes')->disabled(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('branch.code')->badge()->color('primary')->sortable(),
                Tables\Columns\TextColumn::make('order_type')
                    ->badge()
                    ->formatStateUsing(fn (OrderType $state) => $state->label()),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state) => match ($state) {
                        OrderStatus::Pending => 'gray',
                        OrderStatus::Preparing => 'warning',
                        OrderStatus::Ready => 'info',
                        OrderStatus::Completed => 'success',
                        OrderStatus::Cancelled, OrderStatus::Refunded => 'danger',
                    }),
                Tables\Columns\TextColumn::make('payment_status')->badge(),
                Tables\Columns\TextColumn::make('total')->money('MYR')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('branch')->relationship('branch', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('advance')
                    ->label(fn (Order $r) => match ($r->status) {
                        OrderStatus::Pending => 'Start Preparing',
                        OrderStatus::Preparing => 'Mark Ready',
                        OrderStatus::Ready => 'Complete',
                        default => '—',
                    })
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn (Order $r) => ! $r->status->isTerminal() && $r->status !== OrderStatus::Cancelled)
                    ->requiresConfirmation()
                    ->action(function (Order $r, OrderService $service) {
                        $next = match ($r->status) {
                            OrderStatus::Pending => OrderStatus::Preparing,
                            OrderStatus::Preparing => OrderStatus::Ready,
                            OrderStatus::Ready => OrderStatus::Completed,
                            default => null,
                        };
                        if ($next === null) {
                            return;
                        }
                        try {
                            $service->transition($r, $next);
                            Notification::make()->title("→ {$next->label()}")->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $r) => ! $r->status->isTerminal())
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')->required(),
                    ])
                    ->action(function (Order $r, array $data, OrderService $service) {
                        $r->update(['cancellation_reason' => $data['reason']]);
                        try {
                            $service->transition($r->fresh() ?? $r, OrderStatus::Cancelled);
                            Notification::make()->title('Order cancelled')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var User|null $user */
        $user = auth()->user();
        if ($user?->hasRole('branch_manager') && ! $user->hasAnyRole(['super_admin', 'hq_admin', 'ops_manager'])) {
            $query->whereIn('branch_id', $user->branches()->pluck('branches.id'));
        }

        return $query;
    }
}
