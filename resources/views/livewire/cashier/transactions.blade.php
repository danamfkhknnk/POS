<div>
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Search TRX ID or product..."
            class="w-full max-w-sm rounded-xl border-gray-300 px-3 py-2 shadow-sm focus:border-amber-500 focus:ring-amber-500"
        >
        <input
            wire:model.live.debounce.300ms="dateFrom"
            type="date"
            class="rounded-xl border-gray-300 px-3 py-2 shadow-sm focus:border-amber-500 focus:ring-amber-500"
        >
        <input
            wire:model.live.debounce.300ms="dateUntil"
            type="date"
            class="rounded-xl border-gray-300 px-3 py-2 shadow-sm focus:border-amber-500 focus:ring-amber-500"
        >
        <a
            href="{{ route('cashier.index') }}"
            class="ml-auto rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600"
        >
            + New Sale
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Trx ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Items</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Units</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Total</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($transactions as $trx)
                    <tr wire:key="{{ $trx->trx_id }}" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono font-semibold text-gray-900">{{ $trx->trx_id }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $trx->sold_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $trx->items }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $trx->units }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">IDR {{ number_format($trx->total, 0) }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button wire:click="openDetail('{{ $trx->trx_id }}')" class="font-medium text-amber-600 hover:text-amber-700">Detail</button>
                            <button wire:click="printReceipt('{{ $trx->trx_id }}')" class="ml-3 font-medium text-gray-500 hover:text-gray-700">Print</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                            @if (trim($search) !== '')
                                No transactions found for "{{ $search }}".
                            @else
                                No transactions recorded yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Detail modal --}}
    @if ($this->detailTrx)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:key="detail-modal">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Transaction Detail</h2>
                        <p class="font-mono text-sm text-gray-500">{{ $this->detailTrx->trx_id }}</p>
                    </div>
                    <button wire:click="closeDetail" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <div class="mb-3 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <p class="text-xs text-gray-500">Date</p>
                        <p class="font-semibold text-gray-900">{{ $this->detailTrx->sold_at->format('d M Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Cashier</p>
                        <p class="font-semibold text-gray-900">{{ $this->detailTrx->user->name }}</p>
                    </div>
                </div>

                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">                            @foreach ($this->detailTrx->lines as $line)
                            <tr wire:key="line-{{ $line->id }}">
                                <td class="py-2">{{ $line->product->name }}</td>
                                <td class="py-2 text-center text-gray-500">× {{ $line->quantity }}</td>
                                <td class="py-2 text-right font-semibold whitespace-nowrap">IDR {{ number_format($line->total, 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-3 text-base font-bold text-gray-900">
                    <span>Total</span>
                    <span>IDR {{ number_format($this->detailTrx->lines->sum('total'), 0) }}</span>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button wire:click="closeDetail" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Close</button>
                    <button wire:click="printReceipt('{{ $this->detailTrx->trx_id }}')" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">🖨 Print Receipt</button>
                </div>
            </div>
        </div>
    @endif

    @if ($this->detailTrx)
        <a
            href="{{ $this->receiptUrl }}"
            target="_blank"
            class="mt-2 inline-block w-full text-center text-xs text-gray-400 hover:text-gray-600"
        >
            Open receipt in new tab
        </a>
    @endif
</div>
