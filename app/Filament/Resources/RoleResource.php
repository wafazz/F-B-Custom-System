<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Role')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(125)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Role $record) => $record && in_array($record->name, ['super_admin', 'hq_admin'], true))
                        ->helperText('Snake_case identifier — e.g. branch_manager. Cannot rename core roles.'),
                    Forms\Components\Hidden::make('guard_name')->default('web'),
                ])
                ->columns(1),

            Forms\Components\Section::make('Permissions')
                ->description('Tick the actions this role is allowed to perform. Grouped by resource.')
                ->schema(self::permissionGroups()),
        ]);
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function permissionGroups(): array
    {
        $grouped = Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $p) => self::resourceFromSlug($p->name));

        $sections = [];
        foreach ($grouped as $resource => $perms) {
            $options = $perms->mapWithKeys(fn (Permission $p) => [
                $p->id => self::actionLabel(self::actionFromSlug($p->name)),
            ])->all();

            $sections[] = Forms\Components\Fieldset::make(self::resourceLabel($resource))
                ->schema([
                    Forms\Components\CheckboxList::make("perm_group_{$resource}")
                        ->label('')
                        ->options($options)
                        ->columns(4)
                        ->bulkToggleable()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?Role $record) use ($perms) {
                            if (! $record) {
                                $component->state([]);

                                return;
                            }
                            $component->state(
                                $perms->whereIn('id', $record->permissions->pluck('id'))->pluck('id')->all()
                            );
                        }),
                ])
                ->columnSpanFull();
        }

        return $sections;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (Role $r) => match ($r->name) {
                        'super_admin' => 'danger',
                        'hq_admin' => 'warning',
                        'customer' => 'gray',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions')
                    ->badge(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view_any_role') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any_role') ?? false;
    }

    /**
     * Slugs look like `view_any_branch`, `force_delete_modifier::group`. The
     * resource is the final underscore-separated segment (resources may
     * contain `::` but never `_`).
     */
    protected static function resourceFromSlug(string $slug): string
    {
        $parts = explode('_', $slug);

        return (string) end($parts);
    }

    protected static function actionFromSlug(string $slug): string
    {
        $parts = explode('_', $slug);
        array_pop($parts);

        return implode('_', $parts);
    }

    protected static function resourceLabel(string $resource): string
    {
        return Str::of($resource)->replace('::', ' ')->headline()->toString();
    }

    protected static function actionLabel(string $action): string
    {
        return match ($action) {
            'view' => 'View',
            'view_any' => 'View any',
            'create' => 'Create',
            'update' => 'Update',
            'delete' => 'Delete',
            'delete_any' => 'Delete any',
            'force_delete' => 'Force delete',
            'force_delete_any' => 'Force delete any',
            'restore' => 'Restore',
            'restore_any' => 'Restore any',
            'replicate' => 'Replicate',
            'reorder' => 'Reorder',
            default => Str::headline($action),
        };
    }
}
