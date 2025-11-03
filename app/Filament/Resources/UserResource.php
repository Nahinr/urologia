<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Filament\Forms\Fields\PhoneField;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Filament\Forms\Components\Grid as FormGrid;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Usuarios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Perfil')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombres')
                            ->required()
                            ->maxLength(100)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellidos')
                            ->maxLength(100)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('specialty')
                            ->label('Especialidad')
                            ->maxLength(150)
                            ->columnSpanFull()
                            ->required(fn (Get $get): bool => static::doctorRoleSelected($get('roles'), $get('id')))
                            ->hidden(fn (Get $get): bool => ! static::doctorRoleSelected($get('roles'), $get('id'))),

                        // Teléfono (país + número) en su propia fila:
                        FormGrid::make(12)->schema([
                            ...PhoneField::schema(
                                countryField: 'phone_country',
                                nationalField: 'phone_national',
                                e164Field: 'phone',
                                countrySpan: 3,   // país
                                numberSpan: 9     // número
                            ),
                        ]),
                    ])
                    ->columns(12),

                Forms\Components\Section::make('Acceso')
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(150),

                                Forms\Components\TextInput::make('password')
                                    ->label('Contraseña')
                                    ->password()
                                    ->revealable()
                                    // encripta solo si envías algo
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                    // requerida solo al crear
                                    ->required(fn (string $context) => $context === 'create')
                                    // no sobrescribe si la dejas vacía al editar
                                    ->dehydrated(fn ($state) => filled($state)),

                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'active' => 'Activo',
                                        'inactive' => 'Inactivo',
                                    ])
                                    ->required()
                                    ->native(false),
                ])->columns(3),


                    Forms\Components\Section::make('Roles')
                        ->schema([
                            Select::make('roles')
                                ->label('Asignar rol')
                                ->relationship('roles', 'name') // Spatie HasRoles
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->live() // ← para que afterStateUpdated dispare al quitar el chip
                                // 1) Deshabilita elegir "Administrator" en el desplegable si te editas a ti mismo
                                ->disableOptionWhen(function (string $value, $state, callable $get) {
                                    $record = $get('id') ? User::find($get('id')) : null;
                                    $roleName = Role::query()->whereKey($value)->value('name'); // $value es ID

                                    return $roleName === 'Administrator'
                                        && $record
                                        && $record->id === Filament::auth()->id();
                                })
                                // 2) Si intentan quitar el chip de "Administrator", lo volvemos a poner
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    // $state es array de IDs (o null)
                                    $record = $get('id') ? User::find($get('id')) : null;
                                    if (! $record) return;

                                    $adminRoleId = Role::query()->where('name', 'Administrator')->value('id');

                                    // Si me edito a mí mismo, no me puedo quitar "Administrator"
                                    if ($record->id === Filament::auth()->id()) {
                                        if (is_array($state) && ! in_array($adminRoleId, $state, true)) {
                                            $state[] = $adminRoleId;
                                            $set('roles', array_values(array_unique($state)));
                                            Notification::make()
                                                ->title('No puedes quitarte tu propio rol "Administrator".')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                    }

                                    // (Opcional) Bloquea quitar "Administrator" al ÚNICO admin ACTIVO
                                    // Si este usuario tenía Admin y el nuevo estado lo elimina:
                                    $teniaAdmin = $record->hasRole('Administrator');
                                    $tieneAdminAhora = is_array($state) && in_array($adminRoleId, $state, true);

                                    if ($teniaAdmin && ! $tieneAdminAhora) {
                                        $adminsActivos = User::role('Administrator')->where('status', 'active')->count();
                                        if ($adminsActivos <= 1) {
                                            // Reponerlo y avisar
                                            $state = is_array($state) ? $state : [];
                                            $state[] = $adminRoleId;
                                            $set('roles', array_values(array_unique($state)));
                                            Notification::make()
                                                ->title('No puedes quitar el rol "Administrator" del único Administrador activo.')
                                                ->danger()
                                                ->send();
                                        }
                                    }
                                })
                        ]),
             ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('last_name')->label('Apellido')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('roles.name')->label('Roles')->badge()->separator(', '),
                    Tables\Columns\IconColumn::make('status')
                        ->label('Estado')
                        ->icon(fn (string $state): string => match ($state) {
                            'active' => 'heroicon-o-check-circle',
                            'inactive' => 'heroicon-o-x-circle',
                            default => 'heroicon-o-question-mark-circle',
                        })
                        ->color(fn (string $state): string => match ($state) {
                            'active' => 'success',
                            'inactive' => 'danger',
                            default => 'gray',
                        }),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => Filament::auth()->user()?->can('user.view')),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => Filament::auth()->user()?->can('user.update')),

                Tables\Actions\Action::make('inactivate')
                    ->label('Inactivar')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $record) =>
                        $record->status === 'active' &&
                        Filament::auth()->user()?->can('user.update')
                    )
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        // 1) Bloquea auto-inactivarse
                        if (Auth::id() === $record->id) {
                            Notification::make()->title('No puedes inactivarte a ti mismo.')->danger()->send();
                            return;
                        }

                        // 2) Bloquea inactivar al ÚNICO admin activo
                        $adminsActivos = User::role('Administrator')->where('status', 'active')->count();
                        if ($record->hasRole('Administrator') && $adminsActivos <= 1) {
                            Notification::make()->title('No puedes inactivar al único Administrador activo.')->danger()->send();
                            return;
                        }

                        // 3) Ok, inactivar
                        $record->update(['status' => 'inactive']);
                        Notification::make()->title('Usuario inactivado.')->success()->send();
                    }),

                    // Activar (solo si está inactivo)
                    Tables\Actions\Action::make('activate')
                        ->label('Activar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (User $record) =>
                            $record->status === 'inactive' &&
                            Filament::auth()->user()?->can('user.update')
                        )
                        ->requiresConfirmation()
                        ->action(function (User $record) {
                            $record->update(['status' => 'active']);
                            Notification::make()->title('Usuario activado.')->success()->send();
                        }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    protected static ?int $doctorRoleIdCache = null;

    protected static function doctorRoleId(): ?int
    {
        if (static::$doctorRoleIdCache === null) {
            static::$doctorRoleIdCache = Role::query()
                ->where('name', 'Doctor')
                ->value('id');
        }

        return static::$doctorRoleIdCache;
    }

    protected static function doctorRoleSelected(mixed $roleIds, mixed $recordId = null): bool
    {
        $doctorRoleId = static::doctorRoleId();

        if (! $doctorRoleId) {
            return false;
        }

        if ($roleIds instanceof \Illuminate\Support\Collection) {
            $roleIds = $roleIds->all();
        }

        if (is_array($roleIds)) {
            foreach ($roleIds as $role) {
                if (is_numeric($role) && (int) $role === (int) $doctorRoleId) {
                    return true;
                }

                if ($role instanceof Role && (int) $role->getKey() === (int) $doctorRoleId) {
                    return true;
                }

                if (is_array($role)) {
                    if (isset($role['id']) && (int) $role['id'] === (int) $doctorRoleId) {
                        return true;
                    }

                    if (isset($role['name']) && $role['name'] === 'Doctor') {
                        return true;
                    }
                }

                if (is_string($role) && $role === 'Doctor') {
                    return true;
                }
            }

            return false;
        }

        if ($roleIds instanceof \Traversable) {
            foreach ($roleIds as $roleId) {
                if (is_numeric($roleId) && (int) $roleId === (int) $doctorRoleId) {
                    return true;
                }

                if ($roleId instanceof Role && (int) $roleId->getKey() === (int) $doctorRoleId) {
                    return true;
                }

                if (is_array($roleId) && isset($roleId['id']) && (int) $roleId['id'] === (int) $doctorRoleId) {
                    return true;
                }

                if (is_string($roleId) && $roleId === 'Doctor') {
                    return true;
                }
            }

            return false;
        }

        if ($recordId) {
            $user = User::query()->find($recordId);

            if ($user && $user->hasRole('Doctor')) {
                return true;
            }
        }

        return false;
    }
}
