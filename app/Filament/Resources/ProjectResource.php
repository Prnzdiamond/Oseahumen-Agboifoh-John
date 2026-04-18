<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Project;
use App\Models\Technology;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Forms\Components\IconPicker;
use App\Filament\Resources\ProjectResource\Pages;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                fn (string $context, $state, callable $set) =>
                                $context === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null
                            ),
                        Textarea::make('description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Select::make('status')
                            ->options([
                                'planning'    => 'Planning',
                                'in_progress' => 'In Progress',
                                'completed'   => 'Completed',
                                'on_hold'     => 'On Hold',
                                'cancelled'   => 'Cancelled',
                            ])
                            ->default('planning')
                            ->required(),

                        Select::make('type')
                            ->options([
                                'web_application' => 'Web Application',
                                'mobile_app'      => 'Mobile App',
                                'desktop_app'     => 'Desktop App',
                                'api'             => 'API',
                                'library'         => 'Library',
                                'tool'            => 'Tool',
                                'game'            => 'Game',
                                'other'           => 'Other',
                            ])
                            ->default('web_application')
                            ->required(),

                        Toggle::make('is_featured')
                            ->label('Featured Project')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Media')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Main Image')
                            ->image()
                            ->directory('project-main-images')
                            ->disk('cloudinary')
                            ->nullable()
                            ->extraAttributes(['loading' => 'lazy']),

                        FileUpload::make('cover_image')
                            ->label('Cover Image')
                            ->image()
                            ->directory('project-covers')
                            ->disk('cloudinary')
                            ->nullable()
                            ->extraAttributes(['loading' => 'lazy']),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Project Details')
                    ->schema([
                        TextInput::make('demo_url')
                            ->label('Demo URL')
                            ->url()
                            ->nullable()
                            ->prefixIcon('heroicon-m-globe-alt'),

                        DatePicker::make('completion_date')
                            ->label('Completion Date')
                            ->nullable(),

                        TextInput::make('duration')
                            ->label('Duration (in days)')
                            ->numeric()
                            ->nullable()
                            ->helperText('Enter the project duration in days'),
                    ])
                    ->columns(3),

                // ── Categorisation — new section ──────────────────────────────
                // Industry tells the world what domain this project serves.
                // Tags tell the world what features/capabilities it has.
                // Both are used as the context axis in URL filtering:
                //   /laravel/real-estate  →  Laravel projects in real estate
                //   /python/live-chat     →  Python projects with live chat
                Forms\Components\Section::make('Categorisation')
                    ->description('Used for the context filter axis: /laravel/real-estate, /python/live-chat etc.')
                    ->schema([
                        Select::make('industry')
                            ->label('Industry / Domain')
                            ->nullable()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('industry')
                                    ->required()
                                    ->placeholder('e.g. prop-tech, climate-tech, logistics'),
                            ])
                            ->createOptionUsing(fn (array $data): string => \Illuminate\Support\Str::slug($data['industry']))
                            ->options(fn () => \App\Models\Project::select('industry')
                                ->whereNotNull('industry')
                                ->distinct()
                                ->orderBy('industry')
                                ->pluck('industry', 'industry')
                                ->toArray()
                                // Merge with a sensible default list so new installs
                                // have options without requiring existing projects
                                + [
                                    'e-commerce'    => 'E-Commerce',
                                    'real-estate'   => 'Real Estate',
                                    'fintech'       => 'Fintech',
                                    'healthcare'    => 'Healthcare',
                                    'education'     => 'Education',
                                    'saas'          => 'SaaS',
                                    'ai-ml'         => 'AI / ML',
                                    'gaming'        => 'Gaming',
                                    'social'        => 'Social',
                                    'productivity'  => 'Productivity',
                                    'entertainment' => 'Entertainment',
                                    'other'         => 'Other',
                                ]
                            )
                            ->helperText('The primary domain this project lives in. Can add new ones with "Create".'),

                        TagsInput::make('tags')
                            ->label('Feature Tags')
                            ->nullable()
                            ->placeholder('Type a tag and press Enter  e.g. live-chat')
                            ->suggestions([
                                'live-chat',
                                'websockets',
                                'real-time',
                                'payment',
                                'authentication',
                                'dashboard',
                                'donation',
                                'notifications',
                                'map',
                                'ai',
                                'api-integration',
                                'file-upload',
                                'search',
                                'multi-tenant',
                                'subscriptions',
                                'admin-panel',
                                'mobile-responsive',
                                'pwa',
                                'dark-mode',
                                'email',
                                'sms',
                                'oauth',
                                'two-factor',
                                'analytics',
                            ])
                            ->columnSpanFull()
                            ->helperText(
                                'Short URL-safe slugs describing key capabilities. ' .
                                'Used in filter URLs: /laravel/live-chat,payment. ' .
                                'Use lowercase with hyphens — no spaces.'
                            ),
                    ])
                    ->columns(2),

                // ── Technologies ──────────────────────────────────────────────
                Forms\Components\Section::make('Technologies')
                    ->schema([
                        Select::make('technologies')
                            ->label('Technologies Used')
                            ->multiple()
                            ->searchable()
                            ->columnSpanFull()
                            ->getSearchResultsUsing(function (string $search) {
                                return Technology::active()
                                    ->where(function ($q) use ($search) {
                                        $q->where('name', 'ilike', "%{$search}%")
                                          ->orWhere('category', 'ilike', "%{$search}%");
                                    })
                                    ->orderBy('name')
                                    ->limit(30)
                                    ->pluck('name', 'name')
                                    ->toArray();
                            })
                            ->getOptionLabelsUsing(fn (array $values) =>
                                collect($values)->mapWithKeys(fn ($v) => [$v => $v])->toArray()
                            )
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->label('Technology Name')
                                    ->placeholder('e.g. SolidJS, Bun, Zig'),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                Technology::firstOrCreate(
                                    ['slug' => \Illuminate\Support\Str::slug(
                                        preg_replace('/[\.\+#]/', '', $data['name'])
                                    )],
                                    [
                                        'name'      => $data['name'],
                                        'category'  => 'other',
                                        'is_manual' => true,
                                        'is_active' => true,
                                    ]
                                );
                                return $data['name'];
                            })
                            ->helperText('Search by name (e.g. "laravel") or category (e.g. "python"). Can\'t find it? Use "Create new".'),
                    ]),

                // ── Key Features ──────────────────────────────────────────────
                Forms\Components\Section::make('Key Features')
                    ->schema([
                        Repeater::make('key_features')
                            ->label('Key Features')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Feature Title')
                                    ->required()
                                    ->placeholder('e.g., Real-time notifications'),
                                Textarea::make('description')
                                    ->label('Feature Description')
                                    ->placeholder('Describe this feature in detail')
                                    ->rows(2),
                                IconPicker::make('icon')
                                    ->label('Feature Icon')
                                    ->helperText('Search by keyword e.g. "bell", "chart", "shield"'),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Feature')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(1)
                            ->columnSpanFull(),
                    ]),

                // ── Source Code ───────────────────────────────────────────────
                Forms\Components\Section::make('Source Code')
                    ->schema([
                        Repeater::make('source_code')
                            ->label('Source Code Repositories')
                            ->schema([
                                Select::make('platform')
                                    ->label('Platform')
                                    ->options([
                                        'github'    => 'GitHub',
                                        'gitlab'    => 'GitLab',
                                        'bitbucket' => 'Bitbucket',
                                        'codeberg'  => 'Codeberg',
                                        'other'     => 'Other',
                                    ])
                                    ->default('github')
                                    ->required(),
                                TextInput::make('url')
                                    ->label('Repository URL')
                                    ->url()
                                    ->required()
                                    ->placeholder('https://github.com/username/repo'),
                                TextInput::make('label')
                                    ->label('Custom Label (optional)')
                                    ->placeholder('e.g., Frontend, Backend'),
                                TextInput::make('branch')
                                    ->label('Default Branch')
                                    ->default('main')
                                    ->placeholder('main, master, develop'),
                                Toggle::make('is_public')
                                    ->label('Public Repository')
                                    ->default(true),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Repository')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->disk('cloudinary')
                    ->label('Image')
                    ->circular()
                    ->size(50),

                TextColumn::make('title')
                    ->label('Project Title')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                BadgeColumn::make('status')
                    ->colors([
                        'success'   => 'completed',
                        'primary'   => 'in_progress',
                        'warning'   => 'planning',
                        'secondary' => 'on_hold',
                        'danger'    => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                BadgeColumn::make('type')
                    ->colors([
                        'primary'   => 'web_application',
                        'secondary' => 'mobile_app',
                        'success'   => 'api',
                        'warning'   => 'library',
                        'info'      => 'tool',
                        'pink'      => 'game',
                        'gray'      => 'other',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                // ── New: industry column ──────────────────────────────────────
                TextColumn::make('industry')
                    ->label('Industry')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state ? ucwords(str_replace('-', ' ', $state)) : '—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('technologies')
                    ->label('Tech Stack')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state) || empty($state)) return 'No technologies';
                        $count = count($state);
                        return $state[0] . ($count > 1 ? " (+{$count} more)" : '');
                    })
                    ->tooltip(fn ($record) => is_array($record->technologies)
                        ? implode(', ', $record->technologies)
                        : null
                    ),

                BooleanColumn::make('is_featured')
                    ->label('Featured')
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('completion_date')
                    ->label('Completed')
                    ->date()
                    ->sortable()
                    ->placeholder('In Progress'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'planning'    => 'Planning',
                    'in_progress' => 'In Progress',
                    'completed'   => 'Completed',
                    'on_hold'     => 'On Hold',
                    'cancelled'   => 'Cancelled',
                ]),
                SelectFilter::make('type')->options([
                    'web_application' => 'Web Application',
                    'mobile_app'      => 'Mobile App',
                    'desktop_app'     => 'Desktop App',
                    'api'             => 'API',
                    'library'         => 'Library',
                    'tool'            => 'Tool',
                    'game'            => 'Game',
                    'other'           => 'Other',
                ]),
                // ── New: filter by industry ───────────────────────────────────
                SelectFilter::make('industry')
                    ->label('Industry')
                    ->options(fn () => \App\Models\Project::select('industry')
                        ->whereNotNull('industry')
                        ->distinct()
                        ->orderBy('industry')
                        ->pluck('industry', 'industry')
                        ->map(fn ($v) => ucwords(str_replace('-', ' ', $v)))
                        ->toArray()
                    )
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured Projects')
                    ->placeholder('All projects')
                    ->trueLabel('Featured only')
                    ->falseLabel('Non-featured only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ProjectResource\RelationManagers\ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('images');
    }
}
