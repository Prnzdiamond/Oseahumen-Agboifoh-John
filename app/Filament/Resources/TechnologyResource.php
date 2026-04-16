<?php

namespace App\Filament\Resources;

use App\Models\Technology;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Artisan;
use App\Filament\Resources\TechnologyResource\Pages;

class TechnologyResource extends Resource
{
    protected static ?string $model = Technology::class;

    protected static ?string $navigationIcon  = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Technology Catalog';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int    $navigationSort  = 10;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Identity')
                ->description('The name and slug are what get stored in project tech stacks.')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('e.g. Vue.js, FastAPI, Rust')
                        ->helperText('Canonical display name — this is what the admin types and what appears on the frontend.'),

                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('e.g. vuejs, fastapi, rust')
                        ->helperText('URL-safe identifier — used in /laravel, ?tech=python filters. Auto-generated from name on create.'),

                    Select::make('category')
                        ->required()
                        ->searchable()
                        ->options([
                            'php'        => 'PHP Ecosystem',
                            'python'     => 'Python Ecosystem',
                            'javascript' => 'JavaScript Ecosystem',
                            'css'        => 'CSS / Styling',
                            'html'       => 'HTML',
                            'database'   => 'Databases',
                            'devops'     => 'DevOps / Cloud',
                            'mobile'     => 'Mobile',
                            'systems'    => 'Systems Languages',
                            'data'       => 'Data / ML / AI',
                            'other'      => 'Other',
                        ])
                        ->helperText('This powers ecosystem filtering. /python shows ALL techs whose category is "python" (FastAPI, Django, Flask etc.)'),

                    TextInput::make('color')
                        ->nullable()
                        ->placeholder('#FF2D20')
                        ->helperText('Brand hex color — shown in filter pills and tech badges.'),
                ])
                ->columns(2),

            Section::make('Icon')
                ->description('Devicon is the primary source. Custom URL is the fallback for techs not in Devicon.')
                ->schema([
                    TextInput::make('devicon_name')
                        ->nullable()
                        ->placeholder('laravel')
                        ->helperText('Devicon identifier (lowercase, no dots). Find it at devicon.dev'),

                    Select::make('devicon_version')
                        ->options([
                            'plain'             => 'plain',
                            'original'          => 'original',
                            'plain-wordmark'    => 'plain-wordmark',
                            'original-wordmark' => 'original-wordmark',
                            'line'              => 'line',
                            'line-wordmark'     => 'line-wordmark',
                        ])
                        ->default('plain'),

                    Toggle::make('devicon_colored')
                        ->label('Use colored variant')
                        ->default(true),

                    TextInput::make('custom_icon_url')
                        ->url()
                        ->nullable()
                        ->label('Custom Icon URL')
                        ->placeholder('https://example.com/logo.svg')
                        ->helperText('Used when devicon_name is empty.'),
                ])
                ->columns(2),

            Section::make('Aliases')
                ->description('All the ways someone might type this technology. The resolver matches any alias.')
                ->schema([
                    TagsInput::make('aliases')
                        ->placeholder('Type an alias and press Enter')
                        ->helperText('e.g. for Vue.js: vue, vuejs, vue.js, vue js')
                        ->columnSpanFull(),
                ]),

            Section::make('Status')
                ->schema([
                    Toggle::make('is_active')
                        ->label('Active (visible in catalog)')
                        ->default(true),
                    Toggle::make('is_manual')
                        ->label('Manually managed — daily sync will not overwrite this record')
                        ->default(false),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('slug')->searchable()->toggleable(),
                TextColumn::make('category')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'php'        => 'indigo',
                        'python'     => 'warning',
                        'javascript' => 'success',
                        'database'   => 'info',
                        'devops'     => 'gray',
                        'mobile'     => 'purple',
                        'systems'    => 'danger',
                        'data'       => 'pink',
                        default      => 'gray',
                    }),
                TextColumn::make('devicon_name')->searchable()->placeholder('—')->toggleable(),
                IconColumn::make('devicon_colored')->boolean()->label('Colored'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                IconColumn::make('is_manual')->boolean()->label('Manual'),
            ])
            ->filters([
                SelectFilter::make('category')->options([
                    'php'        => 'PHP',
                    'python'     => 'Python',
                    'javascript' => 'JavaScript',
                    'css'        => 'CSS',
                    'database'   => 'Database',
                    'devops'     => 'DevOps',
                    'mobile'     => 'Mobile',
                    'systems'    => 'Systems',
                    'data'       => 'Data',
                    'other'      => 'Other',
                ]),
            ])
            ->headerActions([
                // Trigger a Devicon sync right from the admin panel
                Tables\Actions\Action::make('sync_devicon')
                    ->label('Sync from Devicon')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Sync Technology Catalog')
                    ->modalDescription('This will pull the latest Devicon JSON and update non-manual records. Manual entries will not be overwritten.')
                    ->action(function () {
                        Artisan::call('technologies:sync');
                    })
                    ->successNotificationTitle('Technology catalog synced!'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->defaultPaginationPageOption(20);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTechnologies::route('/'),
            'create' => Pages\CreateTechnology::route('/create'),
            'edit'   => Pages\EditTechnology::route('/{record}/edit'),
        ];
    }
}
