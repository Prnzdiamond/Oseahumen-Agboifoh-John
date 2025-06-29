<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Project;
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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProjectResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProjectResource\RelationManagers;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                                fn(string $context, $state, callable $set) =>
                                $context === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null
                            ),
                        Textarea::make('description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Select::make('status')
                            ->options([
                                'planning' => 'Planning',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'on_hold' => 'On Hold',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('planning')
                            ->required(),

                        Select::make('type')
                            ->options([
                                'web_application' => 'Web Application',
                                'mobile_app' => 'Mobile App',
                                'desktop_app' => 'Desktop App',
                                'api' => 'API',
                                'library' => 'Library',
                                'tool' => 'Tool',
                                'game' => 'Game',
                                'other' => 'Other',
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
                            ->disk('public')
                            ->nullable(),

                        FileUpload::make('cover_image')
                            ->label('Cover Image')
                            ->image()
                            ->directory('project-covers')
                            ->disk('public')
                            ->nullable(),
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

                Forms\Components\Section::make('Technologies')
                    ->schema([
                        Select::make('technologies')
                            ->label('Technologies Used')
                            ->multiple()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('technology')
                                    ->required()
                                    ->placeholder('e.g., Laravel, React, Vue.js')
                            ])
                            ->createOptionUsing(function (array $data): string {
                                return $data['technology'];
                            })
                            ->options([
                                'PHP' => 'PHP',
                                'Laravel' => 'Laravel',
                                'JavaScript' => 'JavaScript',
                                'React' => 'React',
                                'Vue.js' => 'Vue.js',
                                'Angular' => 'Angular',
                                'Node.js' => 'Node.js',
                                'Python' => 'Python',
                                'Django' => 'Django',
                                'Flask' => 'Flask',
                                'MySQL' => 'MySQL',
                                'PostgreSQL' => 'PostgreSQL',
                                'MongoDB' => 'MongoDB',
                                'Redis' => 'Redis',
                                'Docker' => 'Docker',
                                'AWS' => 'AWS',
                                'Git' => 'Git',
                                'HTML' => 'HTML',
                                'CSS' => 'CSS',
                                'Tailwind CSS' => 'Tailwind CSS',
                                'Bootstrap' => 'Bootstrap',
                                'SASS' => 'SASS',
                                'TypeScript' => 'TypeScript',
                                'Next.js' => 'Next.js',
                                'Nuxt.js' => 'Nuxt.js',
                            ])
                            ->preload()
                            ->columnSpanFull(),
                    ]),

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

                                TextInput::make('icon')
                                    ->label('Icon (optional)')
                                    ->placeholder('e.g., heroicon-o-bell, fas fa-bell')
                                    ->helperText('Icon class name or emoji'),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Feature')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(1)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Source Code')
                    ->schema([
                        Repeater::make('source_code')
                            ->label('Source Code Repositories')
                            ->schema([
                                Select::make('platform')
                                    ->label('Platform')
                                    ->options([
                                        'github' => 'GitHub',
                                        'gitlab' => 'GitLab',
                                        'bitbucket' => 'Bitbucket',
                                        'codeberg' => 'Codeberg',
                                        'other' => 'Other',
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
                                    ->placeholder('e.g., Frontend, Backend, Main Repository'),

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
                    ->disk('public')
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
                        'success' => 'completed',
                        'primary' => 'in_progress',
                        'warning' => 'planning',
                        'secondary' => 'on_hold',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'web_application',
                        'secondary' => 'mobile_app',
                        'success' => 'api',
                        'warning' => 'library',
                        'info' => 'tool',
                        'pink' => 'game',
                        'gray' => 'other',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                TextColumn::make('technologies')
                    ->label('Tech Stack')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state) || empty($state)) {
                            return 'No technologies';
                        }

                        $count = count($state);
                        if ($count === 1) {
                            return $state[0];
                        }

                        return $state[0] . " (+" . ($count - 1) . " more)";
                    })
                    ->tooltip(function ($record) {
                        if (!is_array($record->technologies) || empty($record->technologies)) {
                            return null;
                        }
                        return implode(', ', $record->technologies);
                    }),

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
                SelectFilter::make('status')
                    ->options([
                        'planning' => 'Planning',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'on_hold' => 'On Hold',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('type')
                    ->options([
                        'web_application' => 'Web Application',
                        'mobile_app' => 'Mobile App',
                        'desktop_app' => 'Desktop App',
                        'api' => 'API',
                        'library' => 'Library',
                        'tool' => 'Tool',
                        'game' => 'Game',
                        'other' => 'Other',
                    ]),

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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}