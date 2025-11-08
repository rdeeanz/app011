<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;

use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Forms\Set;
use Filament\Support\Colors\Color;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Content Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Category::class, 'slug', ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('parent_id')
                            ->label('Parent Category')
                            ->relationship('parent', 'name', function (Builder $query, ?Category $record) {
                                if ($record && $record->exists) {
                                    // Exclude current record to prevent self-reference
                                    $query->where('id', '!=', $record->id);
                                    
                                    // Exclude descendants to prevent circular references
                                    $descendantIds = $record->getAllDescendantIds();
                                    if (!empty($descendantIds)) {
                                        $query->whereNotIn('id', $descendantIds);
                                    }
                                }
                                return $query;
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty for top-level category')
                            ->rules([
                                function (Category|null $record = null) {
                                    return function (string $attribute, $value, \Closure $fail) use ($record) {
                                        if (!$value || !$record || !$record->exists) {
                                            return;
                                        }
                                        
                                        // Check if parent would create circular reference
                                        $parent = Category::find($value);
                                        if (!$parent) {
                                            return;
                                        }
                                        
                                        // Walk up the parent chain to detect cycles
                                        $currentParent = $parent;
                                        $visitedIds = [];
                                        $maxDepth = 100; // Prevent infinite loops
                                        $depth = 0;
                                        
                                        while ($currentParent && $depth < $maxDepth) {
                                            if ($currentParent->id === $record->id) {
                                                $fail('Category cannot be a descendant of itself.');
                                                return;
                                            }
                                            
                                            if (in_array($currentParent->id, $visitedIds)) {
                                                // Found a cycle, but not involving current record
                                                break;
                                            }
                                            
                                            $visitedIds[] = $currentParent->id;
                                            $currentParent = $currentParent->parent;
                                            $depth++;
                                        }
                                    };
                                }
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Display Options')
                    ->schema([
                        Forms\Components\ColorPicker::make('color')
                            ->hex()
                            ->default('#3B82F6'),

                        Forms\Components\FileUpload::make('icon')
                            ->image()
                            ->imageEditor()
                            ->directory('categories/icons')
                            ->visibility('public')
                            ->helperText('Optional category icon'),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Higher numbers appear first'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured Category')
                            ->helperText('Show in featured categories section'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive categories are hidden from frontend'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('SEO & Meta')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->maxLength(60)
                            ->helperText('Recommended: 50-60 characters'),

                        Forms\Components\Textarea::make('meta_description')
                            ->maxLength(160)
                            ->rows(3)
                            ->helperText('Recommended: 120-160 characters')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->width(40)
                    ->height(40)
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Top Level')
                    ->toggleable(),

                Tables\Columns\ColorColumn::make('color')
                    ->copyable(),

                Tables\Columns\TextColumn::make('articles_count')
                    ->label('Articles')
                    ->counts('articles')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent')
                    ->relationship('parent', 'name')
                    ->preload(),

                Tables\Filters\Filter::make('top_level')
                    ->label('Top Level Categories')
                    ->query(fn (Builder $query): Builder => $query->whereNull('parent_id'))
                    ->toggle(),

                Tables\Filters\Filter::make('featured')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true))
                    ->toggle(),

                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('toggle_featured')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(function (Category $record) {
                        $record->update(['is_featured' => !$record->is_featured]);
                    })
                    ->label(fn (Category $record) => $record->is_featured ? 'Remove Featured' : 'Make Featured'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggle_active')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => !$record->is_active]);
                            });
                        })
                        ->label('Toggle Active Status'),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc');
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withCount('articles');
    }
}
