<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('username')
                            ->unique(User::class, 'username', ignoreRecord: true)
                            ->maxLength(255)
                            ->alphaDash()
                            ->helperText('Optional unique username'),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->helperText('Leave empty to keep current password'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Profile Information')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->directory('avatars')
                            ->visibility('public')
                            ->maxSize(2048),

                        Forms\Components\Textarea::make('bio')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\DatePicker::make('date_of_birth')
                            ->displayFormat('M j, Y')
                            ->maxDate(now()->subYears(13)),

                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ])
                            ->placeholder('Select gender'),

                        Forms\Components\TextInput::make('location')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Professional Information')
                    ->schema([
                        Forms\Components\TextInput::make('job_title')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('company')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('expertise')
                            ->maxLength(500)
                            ->rows(2)
                            ->helperText('Areas of expertise or specialization')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Social Media')
                    ->schema([
                        Forms\Components\TextInput::make('twitter_url')
                            ->url()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-at-symbol'),

                        Forms\Components\TextInput::make('facebook_url')
                            ->url()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-at-symbol'),

                        Forms\Components\TextInput::make('linkedin_url')
                            ->url()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-at-symbol'),

                        Forms\Components\TextInput::make('instagram_url')
                            ->url()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-at-symbol'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Account Settings')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Administrator',
                                'editor' => 'Editor',
                                'author' => 'Author',
                                'subscriber' => 'Subscriber',
                            ])
                            ->default('subscriber')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Account')
                            ->default(true)
                            ->helperText('Inactive users cannot login'),

                        Forms\Components\Toggle::make('email_verified')
                            ->label('Email Verified')
                            ->helperText('Mark email as verified'),

                        Forms\Components\Toggle::make('can_comment')
                            ->label('Can Comment')
                            ->default(true)
                            ->helperText('Allow user to post comments'),

                        Forms\Components\Toggle::make('newsletter_subscribed')
                            ->label('Newsletter Subscription')
                            ->default(false),

                        Forms\Components\DateTimePicker::make('last_login_at')
                            ->label('Last Login')
                            ->displayFormat('M j, Y H:i')
                            ->disabled(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->width(50)
                    ->height(50),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No username')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'editor' => 'warning',
                        'author' => 'success',
                        'subscriber' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('articles_count')
                    ->label('Articles')
                    ->counts('articles')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('comments_count')
                    ->label('Comments')
                    ->counts('comments')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\IconColumn::make('email_verified')
                    ->boolean()
                    ->label('Verified')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->placeholder('Never')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Administrator',
                        'editor' => 'Editor',
                        'author' => 'Author',
                        'subscriber' => 'Subscriber',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->toggle(),

                Tables\Filters\Filter::make('verified')
                    ->query(fn (Builder $query): Builder => $query->where('email_verified', true))
                    ->toggle(),

                Tables\Filters\Filter::make('newsletter_subscribers')
                    ->query(fn (Builder $query): Builder => $query->where('newsletter_subscribed', true))
                    ->toggle(),

                Tables\Filters\Filter::make('recent_login')
                    ->query(fn (Builder $query): Builder => $query->where('last_login_at', '>=', now()->subDays(30)))
                    ->label('Active in last 30 days')
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->action(function (User $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->label(fn (User $record) => $record->is_active ? 'Deactivate' : 'Activate'),
                Tables\Actions\Action::make('verify_email')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->action(function (User $record) {
                        $record->update(['email_verified' => true]);
                    })
                    ->visible(fn (User $record) => !$record->email_verified),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('verify_email')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['email_verified' => true]);
                        })
                        ->label('Verify Emails'),
                    Tables\Actions\BulkAction::make('toggle_active')
                        ->icon('heroicon-o-user-minus')
                        ->color('warning')
                        ->action(function ($records) {
                            $currentUser = Auth::user();
                            $maxSelection = 50; // Reasonable limit
                            
                            // Filter out protected users
                            $filteredRecords = $records->filter(function ($record) use ($currentUser) {
                                // Don't toggle current user
                                if ($record->id === $currentUser->id) {
                                    return false;
                                }
                                
                                // Don't toggle users with protected roles (admins, super-admins)
                                if (in_array($record->role, ['admin', 'super-admin'])) {
                                    return false;
                                }
                                
                                return true;
                            });
                            
                            // Check selection size
                            if ($filteredRecords->count() > $maxSelection) {
                                Notification::make()
                                    ->title('Selection too large')
                                    ->body("Cannot toggle more than {$maxSelection} users at once. Please select fewer users.")
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            if ($filteredRecords->isEmpty()) {
                                Notification::make()
                                    ->title('No users to toggle')
                                    ->body('Selected users cannot be toggled (protected accounts or current user).')
                                    ->warning()
                                    ->send();
                                return;
                            }
                            
                            // Collect filtered record IDs
                            $filteredIds = $filteredRecords->pluck('id')->toArray();
                            $totalIds = count($filteredIds);
                            $successCount = 0;
                            $failureCount = 0;
                            
                            // Perform bulk update in transaction
                            try {
                                DB::transaction(function () use ($filteredIds, &$successCount) {
                                    $successCount = User::whereIn('id', $filteredIds)
                                        ->update(['is_active' => DB::raw('NOT is_active')]);
                                });
                                $failureCount = $totalIds - $successCount;
                            } catch (\Exception $e) {
                                $failureCount = $totalIds;
                                $successCount = 0;
                                \Log::error("Failed to toggle users: " . $e->getMessage());
                            }
                            
                            // Send notifications outside transaction
                            if ($successCount > 0) {
                                Notification::make()
                                    ->title('Users toggled')
                                    ->body("Successfully toggled {$successCount} users" . 
                                          ($failureCount > 0 ? ", {$failureCount} failed" : ""))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($failureCount > 0 && $successCount === 0) {
                                Notification::make()
                                    ->title('Toggle failed')
                                    ->body("Failed to toggle {$failureCount} users. Check logs for details.")
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Toggle User Status')
                        ->modalDescription('This will toggle the active status of the selected users. Protected accounts (admins, current user) will be skipped.')
                        ->label('Toggle Active Status'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withCount(['articles', 'comments']);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['articles', 'comments'])
            ->withCount(['articles', 'comments']);
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Email' => $record->email,
            'Role' => ucfirst($record->role),
            'Articles' => $record->articles_count ?? 0,
            'Joined' => $record->created_at->format('M j, Y'),
        ];
    }
}