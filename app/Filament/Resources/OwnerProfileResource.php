<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use App\Models\Technology;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Forms\Components\IconPicker;
use App\Filament\Resources\OwnerProfileResource\Pages;

class OwnerProfileResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon  = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Owner Profile';
    protected static ?string $modelLabel      = 'Owner Profile';
    // Speed fix
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        // Speed fix: lazy load prevents Cloudinary blocking the page
                        FileUpload::make('avatar')
                            ->disk('cloudinary')
                            ->directory('avatars')
                            ->image()
                            ->preserveFilenames()
                            ->columnSpanFull()
                            ->extraAttributes(['loading' => 'lazy']),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(50),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('headline')
                            ->maxLength(120)
                            ->placeholder('e.g., Full Stack Developer | Laravel Enthusiast'),

                        Textarea::make('bio')
                            ->rows(4)
                            ->placeholder('Tell us about yourself...'),
                    ])
                    ->columns(2),

                Section::make('Technical Skills')
                    ->schema([
                        // ── Tech Stack — now DB-driven ────────────────────────
                        Repeater::make('tech_stack')
                            ->label('Tech Stack')
                            ->schema([
                                // Live search against the technologies table
                                // Type "rust" → Rust appears immediately
                                // Type "python" → Python + all ecosystem techs
                                Select::make('technology')
                                    ->required()
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search) {
                                        return Technology::active()
                                            ->where('name', 'ilike', "%{$search}%")
                                            ->orderBy('name')
                                            ->limit(20)
                                            ->pluck('name', 'name')
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(fn ($value) => $value)
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->placeholder('e.g. Zig, SolidJS'),
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
                                    ->helperText('Start typing e.g. "rust", "laravel", "python"'),

                                Select::make('rating')
                                    ->label('Skill Level')
                                    ->options([
                                        1 => 'Beginner',
                                        2 => 'Basic',
                                        3 => 'Intermediate',
                                        4 => 'Advanced',
                                        5 => 'Expert',
                                    ])
                                    ->placeholder('Select skill level'),

                                TextInput::make('years_experience')
                                    ->label('Years of Experience')
                                    ->numeric()
                                    ->placeholder('e.g., 3')
                                    ->suffix('years'),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Technology')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(3),

                        Repeater::make('expertise')
                            ->label('Expertise Areas')
                            ->schema([
                                TextInput::make('area')
                                    ->required()
                                    ->placeholder('e.g., Full Stack Development, API Design'),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Expertise Area')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(1),
                    ]),

                Section::make('Professional Experience')
                    ->schema([
                        Repeater::make('professional_journey')
                            ->label('Professional Journey')
                            ->schema([
                                TextInput::make('position')->required()->placeholder('e.g., Senior Developer'),
                                TextInput::make('company')->required()->placeholder('e.g., Tech Company Inc.'),
                                TextInput::make('period')->required()->placeholder('e.g., 2020 - Present'),
                                Textarea::make('description')->rows(3)->placeholder('Describe your role and achievements...'),
                                Toggle::make('is_current')->label('Current Position')->default(false),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Position')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(2),
                    ]),

                Section::make('Social & Professional Links')
                    ->schema([
                        Repeater::make('urls')
                            ->label('URLs')
                            ->schema([
                                Select::make('platform')
                                    ->label('Platform')
                                    ->options([
                                        'github'    => 'GitHub',
                                        'linkedin'  => 'LinkedIn',
                                        'twitter'   => 'Twitter/X',
                                        'instagram' => 'Instagram',
                                        'facebook'  => 'Facebook',
                                        'youtube'   => 'YouTube',
                                        'tiktok'    => 'TikTok',
                                        'snapchat'  => 'Snapchat',
                                        'pinterest' => 'Pinterest',
                                        'reddit'    => 'Reddit',
                                        'medium'    => 'Medium',
                                        'behance'   => 'Behance',
                                        'dribbble'  => 'Dribbble',
                                        'discord'   => 'Discord',
                                        'telegram'  => 'Telegram',
                                        'whatsapp'  => 'WhatsApp',
                                        'skype'     => 'Skype',
                                        'website'   => 'Personal Website',
                                        'portfolio' => 'Portfolio',
                                        'blog'      => 'Blog',
                                        'other'     => 'Other',
                                    ])
                                    ->required()
                                    ->searchable(),
                                TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required()
                                    ->placeholder('https://...')
                                    ->prefixIcon('heroicon-m-link'),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add URL')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(2),
                    ]),

                Section::make('Personal Information')
                    ->schema([
                        // ── Hobbies — now with visual icon picker ─────────────
                        Repeater::make('hobbies')
                            ->label('Hobbies & Interests')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->placeholder('e.g., Photography, Gaming'),
                                Textarea::make('description')
                                    ->rows(2)
                                    ->placeholder('Optional description...'),
                                // Visual icon picker instead of free-text emoji field
                                IconPicker::make('icon')
                                    ->label('Icon')
                                    ->helperText('Search e.g. "camera", "game", "music", "book"'),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Hobby')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(3),

                        Repeater::make('languages')
                            ->label('Languages')
                            ->schema([
                                TextInput::make('language')
                                    ->required()
                                    ->placeholder('e.g., English, Spanish'),
                                Select::make('proficiency')
                                    ->label('Proficiency Level')
                                    ->options([
                                        'basic'          => 'Basic',
                                        'conversational' => 'Conversational',
                                        'fluent'         => 'Fluent',
                                        'native'         => 'Native',
                                    ])
                                    ->default('conversational')
                                    ->required(),
                                Toggle::make('is_native')->label('Native Language')->default(false),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Language')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(3),
                    ]),

                Section::make('Contact Information')
                    ->schema([
                        Repeater::make('contact_info')
                            ->label('Contact Information')
                            ->schema([
                                Select::make('type')
                                    ->label('Contact Type')
                                    ->options([
                                        'email'    => 'Email',
                                        'phone'    => 'Phone',
                                        'whatsapp' => 'WhatsApp',
                                        'telegram' => 'Telegram',
                                        'skype'    => 'Skype',
                                        'discord'  => 'Discord',
                                        'other'    => 'Other',
                                    ])
                                    ->required(),
                                TextInput::make('value')->required()->placeholder('Contact value...'),
                                TextInput::make('label')->placeholder('Custom label (optional)'),
                                Toggle::make('is_primary')->label('Primary Contact')->default(false),
                                Toggle::make('is_public')->label('Show Publicly')->default(true),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Contact Info')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->disk('cloudinary')
                    ->circular()
                    ->label('Avatar'),

                TextColumn::make('name')->searchable()->sortable(),

                TextColumn::make('headline')
                    ->limit(50)
                    ->tooltip(fn (TextColumn $column): ?string =>
                        strlen($column->getState()) > 50 ? $column->getState() : null
                    ),

                TextColumn::make('tech_stack')
                    ->label('Tech Stack')
                    ->formatStateUsing(function ($state, $record) {
                        $stack = $record->tech_stack;
                        if (!is_array($stack) || empty($stack)) return 'No technologies';
                        $techs = collect($stack)
                            ->map(fn ($item) => is_array($item) ? ($item['technology'] ?? null) : $item)
                            ->filter()->values();
                        if ($techs->isEmpty()) return 'No technologies';
                        return $techs->first() . ($techs->count() > 1 ? " (+{$techs->count()} more)" : '');
                    })
                    ->tooltip(fn ($record) => is_array($record->tech_stack)
                        ? collect($record->tech_stack)
                            ->map(fn ($item) => is_array($item) ? ($item['technology'] ?? '') : $item)
                            ->filter()->join(', ')
                        : null
                    )
                    ->searchable(),
            ])
            ->filters([])
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
            // Speed fix: explicit pagination
            ->defaultPaginationPageOption(10);  
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOwnerProfiles::route('/'),
            'create' => Pages\CreateOwnerProfile::route('/create'),
            'edit'   => Pages\EditOwnerProfile::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_owner', true);
    }
}
