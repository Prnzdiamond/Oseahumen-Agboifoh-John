<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OwnerProfileResource\Pages;
use App\Filament\Resources\OwnerProfileResource\RelationManagers;

class OwnerProfileResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Owner Profile';

    protected static ?string $modelLabel = 'Owner Profile';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        FileUpload::make('avatar')
                            ->disk('cloudinary')
                            ->directory('avatars')
                            ->image()
                            ->preserveFilenames()
                            ->columnSpanFull(),

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
                        // Tech Stack with ratings and experience
                        Repeater::make('tech_stack')
                            ->label('Tech Stack')
                            ->schema([
                                TextInput::make('technology')
                                    ->required()
                                    ->placeholder('e.g., PHP, Laravel, React')
                                    ->datalist([
                                        'PHP',
                                        'Laravel',
                                        'JavaScript',
                                        'React',
                                        'Vue.js',
                                        'Node.js',
                                        'Python',
                                        'Django',
                                        'MySQL',
                                        'PostgreSQL',
                                        'MongoDB',
                                        'Docker',
                                        'AWS',
                                        'Git',
                                        'HTML',
                                        'CSS',
                                        'Tailwind CSS',
                                        'Bootstrap'
                                    ]),
                                Select::make('rating')
                                    ->label('Skill Level')
                                    ->options([
                                        1 => 'Beginner',
                                        2 => 'Basic',
                                        3 => 'Intermediate',
                                        4 => 'Advanced',
                                        5 => 'Expert'
                                    ])
                                    ->placeholder('Select skill level'),
                                TextInput::make('years_experience')
                                    ->label('Years of Experience')
                                    ->numeric()
                                    ->placeholder('e.g., 3')
                                    ->suffix('years')
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Technology')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(3),

                        // Expertise areas
                        Repeater::make('expertise')
                            ->label('Expertise Areas')
                            ->schema([
                                TextInput::make('area')
                                    ->required()
                                    ->placeholder('e.g., Full Stack Development, API Design')
                                    ->datalist([
                                        'Full Stack Development',
                                        'Frontend Development',
                                        'Backend Development',
                                        'API Development',
                                        'Database Design',
                                        'DevOps',
                                        'Mobile Development',
                                        'UI/UX Design',
                                        'Project Management',
                                        'System Architecture',
                                        'Cloud Computing',
                                        'Microservices'
                                    ])
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
                                TextInput::make('position')
                                    ->required()
                                    ->placeholder('e.g., Senior Developer'),
                                TextInput::make('company')
                                    ->required()
                                    ->placeholder('e.g., Tech Company Inc.'),
                                TextInput::make('period')
                                    ->required()
                                    ->placeholder('e.g., 2020 - Present'),
                                Textarea::make('description')
                                    ->rows(3)
                                    ->placeholder('Describe your role and achievements...'),
                                Toggle::make('is_current')
                                    ->label('Current Position')
                                    ->default(false)
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
                                        'github' => 'GitHub',
                                        'linkedin' => 'LinkedIn',
                                        'twitter' => 'Twitter/X',
                                        'instagram' => 'Instagram',
                                        'facebook' => 'Facebook',
                                        'youtube' => 'YouTube',
                                        'tiktok' => 'TikTok',
                                        'snapchat' => 'Snapchat',
                                        'pinterest' => 'Pinterest',
                                        'reddit' => 'Reddit',
                                        'medium' => 'Medium',
                                        'behance' => 'Behance',
                                        'dribbble' => 'Dribbble',
                                        'discord' => 'Discord',
                                        'telegram' => 'Telegram',
                                        'whatsapp' => 'WhatsApp',
                                        'skype' => 'Skype',
                                        'website' => 'Personal Website',
                                        'portfolio' => 'Portfolio',
                                        'blog' => 'Blog',
                                        'other' => 'Other'
                                    ])
                                    ->required()
                                    ->searchable(),
                                TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required()
                                    ->placeholder('https://...')
                                    ->prefixIcon('heroicon-m-link')
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
                        // Hobbies
                        Repeater::make('hobbies')
                            ->label('Hobbies & Interests')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->placeholder('e.g., Photography, Gaming'),
                                Textarea::make('description')
                                    ->rows(2)
                                    ->placeholder('Optional description...'),
                                TextInput::make('icon')
                                    ->placeholder('📸 (optional emoji or icon)')
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Hobby')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->columns(3),

                        // Languages
                        Repeater::make('languages')
                            ->label('Languages')
                            ->schema([
                                TextInput::make('language')
                                    ->required()
                                    ->placeholder('e.g., English, Spanish')
                                    ->datalist([
                                        'English',
                                        'Spanish',
                                        'French',
                                        'German',
                                        'Portuguese',
                                        'Italian',
                                        'Chinese',
                                        'Japanese',
                                        'Arabic',
                                        'Russian'
                                    ]),
                                Select::make('proficiency')
                                    ->label('Proficiency Level')
                                    ->options([
                                        'basic' => 'Basic',
                                        'conversational' => 'Conversational',
                                        'fluent' => 'Fluent',
                                        'native' => 'Native'
                                    ])
                                    ->default('conversational')
                                    ->required(),
                                Toggle::make('is_native')
                                    ->label('Native Language')
                                    ->default(false)
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
                                        'email' => 'Email',
                                        'phone' => 'Phone',
                                        'whatsapp' => 'WhatsApp',
                                        'telegram' => 'Telegram',
                                        'skype' => 'Skype',
                                        'discord' => 'Discord',
                                        'other' => 'Other'
                                    ])
                                    ->required(),
                                TextInput::make('value')
                                    ->required()
                                    ->placeholder('Contact value...'),
                                TextInput::make('label')
                                    ->placeholder('Custom label (optional)'),
                                Toggle::make('is_primary')
                                    ->label('Primary Contact')
                                    ->default(false),
                                Toggle::make('is_public')
                                    ->label('Show Publicly')
                                    ->default(true)
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

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('headline')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                // Tech Stack with improved display
                TextColumn::make('tech_stack')
                    ->label('Tech Stack')
                    ->formatStateUsing(function ($state, $record) {
                        $techStackData = $record->tech_stack;

                        if (!is_array($techStackData) || empty($techStackData)) {
                            return 'No technologies';
                        }

                        $technologies = collect($techStackData)->map(function ($item) {
                            if (is_array($item) && isset($item['technology']) && !empty($item['technology'])) {
                                $tech = $item['technology'];
                                if (isset($item['rating']) && $item['rating']) {
                                    $stars = str_repeat('⭐', $item['rating']);
                                    return $tech . ' ' . $stars;
                                }
                                return $tech;
                            }
                            return null;
                        })->filter()->values();

                        if ($technologies->isEmpty()) {
                            return 'No technologies';
                        }

                        $first = $technologies->first();
                        $count = $technologies->count();

                        if ($count === 1) {
                            return $first;
                        }

                        return $first . " (+" . ($count - 1) . " more)";
                    })
                    ->tooltip(function ($record) {
                        if (!is_array($record->tech_stack) || empty($record->tech_stack)) {
                            return null;
                        }

                        $technologies = collect($record->tech_stack)->map(function ($item) {
                            if (is_array($item) && isset($item['technology']) && !empty($item['technology'])) {
                                $tech = $item['technology'];
                                $details = [];
                                if (isset($item['rating']) && $item['rating']) {
                                    $details[] = 'Level: ' . $item['rating'] . '/5';
                                }
                                if (isset($item['years_experience']) && $item['years_experience']) {
                                    $details[] = 'Experience: ' . $item['years_experience'] . ' years';
                                }
                                return $tech . (!empty($details) ? ' (' . implode(', ', $details) . ')' : '');
                            }
                            return null;
                        })->filter()->values();

                        return $technologies->join("\n");
                    })
                    ->searchable(),

                // Professional Journey
                TextColumn::make('professional_journey')
                    ->label('Current Role')
                    ->formatStateUsing(function ($state, $record) {
                        $journey = $record->professional_journey;

                        if (!is_array($journey) || empty($journey)) {
                            return 'No experience listed';
                        }

                        // Find current position or latest position
                        $current = collect($journey)->first(function ($item) {
                            return is_array($item) && ($item['is_current'] ?? false);
                        });

                        if (!$current) {
                            $current = collect($journey)->first();
                        }

                        if (!$current) {
                            return 'No experience listed';
                        }

                        $position = $current['position'] ?? '';
                        $company = $current['company'] ?? '';

                        if ($position && $company) {
                            return $position . ' at ' . $company;
                        }

                        return $position ?: $company ?: 'No position listed';
                    })
                    ->tooltip(function ($record) {
                        if (!is_array($record->professional_journey) || empty($record->professional_journey)) {
                            return null;
                        }

                        $positions = collect($record->professional_journey)->map(function ($item) {
                            if (!is_array($item))
                                return null;

                            $position = $item['position'] ?? '';
                            $company = $item['company'] ?? '';
                            $period = $item['period'] ?? '';
                            $current = $item['is_current'] ?? false;

                            $line = '';
                            if ($position)
                                $line .= $position;
                            if ($company)
                                $line .= ($line ? ' at ' : '') . $company;
                            if ($period)
                                $line .= ($line ? ' (' : '(') . $period . ')';
                            if ($current)
                                $line .= ' - Current';

                            return $line ?: null;
                        })->filter()->values();

                        return $positions->join("\n");
                    }),

                // Languages
                TextColumn::make('languages')
                    ->label('Languages')
                    ->formatStateUsing(function ($state, $record) {
                        $languages = $record->languages;

                        if (!is_array($languages) || empty($languages)) {
                            return 'Not specified';
                        }

                        $langList = collect($languages)->map(function ($item) {
                            if (is_array($item) && isset($item['language']) && !empty($item['language'])) {
                                $lang = $item['language'];
                                if ($item['is_native'] ?? false) {
                                    return $lang . ' (Native)';
                                }
                                return $lang;
                            }
                            return null;
                        })->filter()->values();

                        if ($langList->isEmpty()) {
                            return 'Not specified';
                        }

                        $first = $langList->first();
                        $count = $langList->count();

                        if ($count === 1) {
                            return $first;
                        }

                        return $first . " (+" . ($count - 1) . " more)";
                    })
                    ->tooltip(function ($record) {
                        if (!is_array($record->languages) || empty($record->languages)) {
                            return null;
                        }

                        $languages = collect($record->languages)->map(function ($item) {
                            if (is_array($item) && isset($item['language']) && !empty($item['language'])) {
                                $lang = $item['language'];
                                $proficiency = $item['proficiency'] ?? 'conversational';
                                $native = $item['is_native'] ?? false;

                                return $lang . ' (' . ucfirst($proficiency) . ($native ? ', Native' : '') . ')';
                            }
                            return null;
                        })->filter()->values();

                        return $languages->join("\n");
                    }),

                // URLs with platform icons
                TextColumn::make('urls')
                    ->label('Links')
                    ->formatStateUsing(function ($state, $record) {
                        $urlsData = $record->urls;

                        if (!is_array($urlsData) || empty($urlsData)) {
                            return 'No links';
                        }

                        $icons = [
                            'github' => '🐙',
                            'linkedin' => '💼',
                            'twitter' => '🐦',
                            'website' => '🌐',
                            'portfolio' => '💼',
                            'blog' => '📝',
                            'youtube' => '📺',
                            'instagram' => '📷',
                            'other' => '🔗'
                        ];

                        $links = collect($urlsData)->filter(function ($item) {
                            return is_array($item) && isset($item['platform']) && !empty($item['platform']) && isset($item['url']) && !empty($item['url']);
                        });

                        if ($links->isEmpty()) {
                            return 'No links';
                        }

                        $first = $links->first();
                        $platform = $first['platform'];
                        $icon = $icons[$platform] ?? '🔗';
                        $displayText = $icon . ' ' . ucfirst($platform);

                        $count = $links->count();
                        if ($count === 1) {
                            return $displayText;
                        }

                        return $displayText . " (+" . ($count - 1) . " more)";
                    })
                    ->tooltip(function ($record) {
                        if (!is_array($record->urls) || empty($record->urls)) {
                            return null;
                        }

                        $links = collect($record->urls)
                            ->filter(function ($item) {
                                return is_array($item) && isset($item['platform']) && !empty($item['platform']) && isset($item['url']) && !empty($item['url']);
                            })
                            ->map(function ($item) {
                                $platform = $item['platform'];
                                return ucfirst($platform) . ': ' . $item['url'];
                            });

                        return $links->join("\n");
                    })
                    ->html(),
            ])
            ->filters([
                // Add filters for tech stack, expertise, etc.
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
            'index' => Pages\ListOwnerProfiles::route('/'),
            'create' => Pages\CreateOwnerProfile::route('/create'),
            'edit' => Pages\EditOwnerProfile::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_owner', true);
    }
}
