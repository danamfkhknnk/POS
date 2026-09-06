<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Sales Report';

    protected ?string $description = 'Transaction performance across all outlets';

    protected function getStats(): array
    {
        $revenueToday = (float) Sale::query()->whereDate('sold_at', today())->sum('total');
        $salesToday = (int) Sale::query()->whereDate('sold_at', today())->count();
        $unitsToday = (int) Sale::query()->whereDate('sold_at', today())->sum('quantity');

        $revenueYesterday = (float) Sale::query()->whereDate('sold_at', today()->subDay())->sum('total');

        $revenueLast7Days = (float) Sale::query()
            ->whereBetween('sold_at', [today()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->sum('total');
        $revenuePrevious7Days = (float) Sale::query()
            ->whereBetween('sold_at', [today()->subDays(13)->startOfDay(), today()->subDays(7)->endOfDay()])
            ->sum('total');

        $revenueAllTime = (float) Sale::query()->sum('total');
        $transactionsAllTime = (int) Sale::query()->count();

        return [
            Stat::make('Revenue Today', 'Rp '.number_format($revenueToday, 0, ',', '.'))
                ->description($this->trendDescription($revenueToday, $revenueYesterday))
                ->descriptionIcon($revenueToday >= $revenueYesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueToday >= $revenueYesterday ? 'success' : 'danger')
                ->chart($this->dailyRevenueChart()),
            Stat::make('Transactions Today', number_format($salesToday))
                ->description($unitsToday.' units sold')
                ->color('info'),
            Stat::make('Revenue Last 7 Days', 'Rp '.number_format($revenueLast7Days, 0, ',', '.'))
                ->description($this->trendDescription($revenueLast7Days, $revenuePrevious7Days).' vs previous 7 days')
                ->descriptionIcon($revenueLast7Days >= $revenuePrevious7Days ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueLast7Days >= $revenuePrevious7Days ? 'success' : 'danger'),
            Stat::make('All-Time Revenue', 'Rp '.number_format($revenueAllTime, 0, ',', '.'))
                ->description(number_format($transactionsAllTime).' transactions recorded')
                ->color('primary'),
        ];
    }

    protected function trendDescription(float $current, float $previous): string
    {
        if ($previous <= 0.0) {
            return $current > 0.0 ? 'New activity' : 'No data';
        }

        $percentage = (($current - $previous) / $previous) * 100;

        return sprintf('%+.1f%%', $percentage);
    }

    /**
     * @return list<float>
     */
    protected function dailyRevenueChart(): array
    {
        return Sale::query()
            ->whereBetween('sold_at', [today()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->selectRaw('DATE(sold_at) as day, SUM(total) as revenue')
            ->groupBy('day')
            ->pluck('revenue', 'day')
            ->map(fn ($revenue): float => (float) $revenue)
            ->values()
            ->all();
    }
}
