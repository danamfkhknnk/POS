<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesChartWidget extends ChartWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Revenue — Last 14 Days';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $revenuePerDay = Sale::query()
            ->whereBetween('sold_at', [today()->subDays(13)->startOfDay(), now()->endOfDay()])
            ->selectRaw('DATE(sold_at) as day, SUM(total) as revenue')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $days = collect(range(13, 0))->map(fn (int $offset): string => today()->subDays($offset)->format('Y-m-d'));

        $data = $days->map(fn (string $day): float => (float) ($revenuePerDay[$day] ?? 0))->all();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (Rp)',
                    'data' => $data,
                    'fill' => true,
                    'tension' => 0.3,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'borderColor' => 'rgb(245, 158, 11)',
                ],
            ],
            'labels' => $days->map(fn (string $day): string => Carbon::parse($day)->translatedFormat('d M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
