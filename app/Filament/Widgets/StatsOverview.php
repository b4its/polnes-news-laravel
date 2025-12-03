<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\News;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    // 1. Perbaiki error P1077 dengan memberikan nilai default 'full'.
    // Ini akan membuat widget mengambil lebar penuh kolom.
    
    // 2. Properti opsional untuk loading yang lebih baik
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Kategori', Category::count())
                ->description('Jumlah seluruh Kategori')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Total Berita', News::count())
                ->description('Jumlah Seluruh Berita')
                ->icon('heroicon-o-rectangle-stack')
                ->color('success'),
        ];
    }
}