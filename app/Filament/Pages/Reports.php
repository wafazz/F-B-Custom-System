<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\User;
use App\Services\Reports\SalesReportExporter;
use App\Services\Reports\SalesReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property Form $form
 */
class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.reports';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'period' => 'daily',
            'anchor' => now()->toDateString(),
            'branch_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Group::make([
                    Select::make('period')
                        ->options([
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                            'yearly' => 'Yearly',
                        ])
                        ->default('daily')
                        ->live()
                        ->required(),
                    DatePicker::make('anchor')
                        ->label('Anchor date')
                        ->helperText('Daily = this day · Weekly = this week · Monthly = this month · Yearly = this year')
                        ->default(now())
                        ->native(false)
                        ->live()
                        ->required(),
                    Select::make('branch_id')
                        ->label('Branch')
                        ->options(Branch::query()->orderBy('name')->pluck('name', 'id'))
                        ->placeholder('All branches')
                        ->live()
                        ->visible(fn (): bool => $this->canSeeAllBranches()),
                ])->columns(3),
            ]);
    }

    /** @return array<string, mixed> */
    public function getViewData(): array
    {
        $period = (string) ($this->data['period'] ?? 'daily');
        $anchor = (string) ($this->data['anchor'] ?? now()->toDateString());
        $branchId = $this->effectiveBranchId();

        /** @var SalesReportService $reports */
        $reports = app(SalesReportService::class);
        [$from, $to] = $reports->range($period, $anchor);

        $screenCap = 100;
        $orders = $reports->orders($from, $to, $branchId, $screenCap + 1);
        $truncated = count($orders) > $screenCap;
        if ($truncated) {
            $orders = array_slice($orders, 0, $screenCap);
        }

        return [
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'branch_id' => $branchId,
            'branch_name' => $branchId ? Branch::query()->whereKey($branchId)->value('name') : null,
            'summary' => $reports->summary($from, $to, $branchId),
            'by_branch' => $reports->byBranch($from, $to, $branchId),
            'top_products' => $reports->topProducts($from, $to, $branchId),
            'series' => $reports->timeSeries($from, $to, $branchId),
            'orders' => $orders,
            'orders_truncated' => $truncated,
            'orders_cap' => $screenCap,
        ];
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadExcel')
                ->label('Download Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->downloadExcel()),
        ];
    }

    public function downloadExcel(): StreamedResponse
    {
        $period = (string) ($this->data['period'] ?? 'daily');
        $anchor = (string) ($this->data['anchor'] ?? now()->toDateString());
        $branchId = $this->effectiveBranchId();

        /** @var SalesReportService $reports */
        $reports = app(SalesReportService::class);
        [$from, $to] = $reports->range($period, $anchor);

        $branchName = $branchId ? Branch::query()->whereKey($branchId)->value('name') : null;

        return app(SalesReportExporter::class)->stream($period, $from, $to, $branchId, $branchName);
    }

    protected function effectiveBranchId(): ?int
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user && $user->hasRole('branch_manager') && ! $user->hasAnyRole(['super_admin', 'hq_admin', 'ops_manager'])) {
            return (int) $user->branches()->value('branches.id');
        }

        $branchId = $this->data['branch_id'] ?? null;

        return $branchId ? (int) $branchId : null;
    }

    protected function canSeeAllBranches(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->hasAnyRole(['super_admin', 'hq_admin', 'ops_manager']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'hq_admin', 'ops_manager', 'branch_manager']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'hq_admin', 'ops_manager', 'branch_manager']) ?? false;
    }
}
