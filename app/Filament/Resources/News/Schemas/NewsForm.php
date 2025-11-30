<?php

namespace App\Filament\Resources\News\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextInput::make('title')
                    ->label('Judul')
                    ->required(),

                Select::make('categoryId')
                ->label('Category')
                ->options(
                    Category::all()->pluck('name', 'id') // Mengambil semua data dan memformatnya menjadi [id => name]
                )
                ->searchable(),


                RichEditor::make('contents')
                    ->label('Deskripsi')
                    ->required()
                    ->columnSpanFull(), 

                FileUpload::make('gambar')
                    ->disk('public_folder')
                    ->directory(fn ($record) => $record?->id 
                        ? "media/gambar/{$record->id}" 
                        : "media/gambar/temp"
                    )
                    ->getUploadedFileNameForStorageUsing(function ($file, $record) {
                        $ext = $file->getClientOriginalExtension();
                        $datetime = now()->format('Ymd_His');
                        $id = $record?->id ?? 'new'; // fallback kalau belum ada id
                        return "gambar_{$datetime}_{$id}.{$ext}";
                    })
                    ->visibility('public')
                    ->preserveFilenames(false) // biar selalu generate nama sesuai fungsi di atas
                    ->deleteUploadedFileUsing(fn ($file) => Storage::disk('public_folder')->delete($file)),
            
                ]);
    }
}
