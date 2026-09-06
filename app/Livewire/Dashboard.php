<?php

namespace App\Livewire;

use App\Models\Outlet;
use Livewire\Component;

class Dashboard extends Component
{
    public $outlets = [];

    public $lastUpdate = null;

    public $refreshInterval = 5;

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->outlets = Outlet::withCount(['staff'])
            ->get()
            ->map(fn ($outlet) => [
                'id' => $outlet->id,
                'name' => $outlet->name,
                'code' => $outlet->code,
                'staff_count' => $outlet->staff_count,
            ])
            ->toArray();

        $this->lastUpdate = now()->format('H:i:s');
    }

    public function render()
    {
        return view('livewire.dashboard');
    }

    public function getIntervalProperty(): int
    {
        return $this->refreshInterval;
    }
}
