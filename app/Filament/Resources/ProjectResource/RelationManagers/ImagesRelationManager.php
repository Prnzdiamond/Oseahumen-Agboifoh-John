<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public function form(Form $form): Form
    {
        return $form->schema([
            FileUpload::make('image')
                ->label('Upload Image(s)')
                ->image()
                ->disk('public')
                ->directory('project-images')
                ->preserveFilenames()
                ->multiple()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->disk('cloudinary')
                    ->label('Image'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),

                // ✅ Bulk upload custom action
                Action::make('bulkUpload')
                    ->label('Bulk Upload')
                    ->form([
                        FileUpload::make('images')
                            ->label('Upload Multiple Images')
                            ->multiple()
                            ->image()
                            ->disk('cloudinary')
                            ->directory('project-images')
                            ->preserveFilenames()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        foreach ($data['images'] as $path) {
                            $this->getRelationship()->create([
                                'image' => $path,
                            ]);
                        }
                    })
                    ->color('success')
                    ->icon('heroicon-o-arrow-up-on-square'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
