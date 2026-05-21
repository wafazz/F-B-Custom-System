<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductReviewResource\Pages;
use App\Models\ProductReview;
use App\Services\Reviews\ReviewService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Product Reviews';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('rating')->numeric()->disabled(),
            Forms\Components\Textarea::make('comment')->disabled()->columnSpanFull(),
            Forms\Components\Toggle::make('is_hidden')
                ->helperText('Hide this review from the storefront. Aggregate recalculates on save.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('user.name')->searchable()->limit(20),
                Tables\Columns\TextColumn::make('rating')->badge()->sortable()
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state)),
                Tables\Columns\TextColumn::make('comment')->limit(60)->wrap(),
                Tables\Columns\IconColumn::make('is_hidden')->boolean()->label('Hidden'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_hidden')->label('Hidden'),
                Tables\Filters\SelectFilter::make('rating')
                    ->options([1 => '1 star', 2 => '2 stars', 3 => '3 stars', 4 => '4 stars', 5 => '5 stars']),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleHidden')
                    ->label(fn (ProductReview $r) => $r->is_hidden ? 'Show' : 'Hide')
                    ->icon(fn (ProductReview $r) => $r->is_hidden ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn (ProductReview $r) => $r->is_hidden ? 'success' : 'warning')
                    ->action(function (ProductReview $r): void {
                        $r->update(['is_hidden' => ! $r->is_hidden]);
                        app(ReviewService::class)->recomputeProductAggregate($r->product_id);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(fn (ProductReview $r) => app(ReviewService::class)->recomputeProductAggregate($r->product_id)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductReviews::route('/'),
            'edit' => Pages\EditProductReview::route('/{record}/edit'),
        ];
    }
}
