<div class="grid gap-6 lg:grid-cols-5" wire:poll.5s>
    {{-- Product search & grid --}}
    <section class="lg:col-span-3">
        <div class="mb-4">
            <input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Search products or SKU..."
                class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
            >
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($results as $stock)
                <button
                    wire:click="addToCart({{ $stock->id }})"
                    data-product-id="{{ $stock->product_id }}"
                    @if ($stock->quantity < 1) disabled @endif
                    class="product-card rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-amber-400 hover:shadow disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <p class="font-semibold text-gray-900">{{ $stock->product->name }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">{{ $stock->product->sku }}</p>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-sm font-bold text-amber-600">IDR {{ number_format($stock->product->price, 0) }}</span>
                        <span
                            id="stock-badge-{{ $stock->product_id }}"
                            class="rounded-full px-2 py-0.5 text-xs font-medium {{ $stock->quantity <= 5 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}"
                        >
                            Stock: {{ $stock->quantity }}
                        </span>
                    </div>
                </button>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                    @if (trim($search) !== '')
                        No products found for "{{ $search }}".
                    @else
                        No products are available at this outlet yet.
                    @endif
                </div>
            @endforelse
        </div>
    </section>

    {{-- Cart --}}
    <aside class="lg:col-span-2">
        <div class="sticky top-6 rounded-2xl bg-white p-5 shadow-lg">
            <h2 class="flex items-center justify-between text-lg font-bold text-gray-900">
                Cart
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-sm text-amber-700">
                    {{ collect($cart)->sum('quantity') }} items
                </span>
            </h2>

            @error('cart')
                <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
            @enderror

            <div class="mt-4 space-y-2">
                @forelse($cart as $line)
                    <div class="flex items-center gap-3 rounded-xl bg-gray-50 p-3" wire:key="cart-{{ $line['id'] }}">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $line['name'] }}</p>
                            <p class="text-xs text-gray-500">
                                IDR {{ number_format($line['price'], 0) }} × {{ $line['quantity'] }}
                                =
                                <span class="font-semibold text-gray-800">IDR {{ number_format($line['price'] * $line['quantity'], 0) }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="decrementLine({{ $line['id'] }})" class="h-7 w-7 rounded-lg bg-gray-200 font-bold text-gray-700 hover:bg-gray-300">−</button>
                            <span class="w-8 text-center text-sm font-semibold">{{ $line['quantity'] }}</span>
                            <button wire:click="incrementLine({{ $line['id'] }})" class="h-7 w-7 rounded-lg bg-amber-500 font-bold text-white hover:bg-amber-600">+</button>
                            <button wire:click="removeLine({{ $line['id'] }})" class="ml-1 text-gray-400 hover:text-red-500" title="Remove">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-400">
                        Click a product on the left to add it to the cart.
                    </p>
                @endforelse
            </div>

            @if (isset($lastSaleSummary))
                <div class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700" wire:key="success-{{ $lastSaleSummary }}">
                    ✓ Transaction {{ $printTrxId }} completed — {{ $lastSaleSummary }}
                </div>

                <button
                    wire:click="printReceipt('{{ $printTrxId }}')"
                    class="mt-2 w-full rounded-lg border border-amber-500 px-4 py-2 text-sm font-semibold text-amber-600 transition hover:bg-amber-50"
                >
                    🖨 Print Receipt
                </button>
            @endif

            <div class="mt-4 border-t border-gray-200 pt-4">
                <div class="flex items-center justify-between text-lg font-bold text-gray-900">
                    <span>Total</span>
                    <span>IDR {{ number_format($this->cartTotal, 0) }}</span>
                </div>

                <button
                    wire:click="checkout"
                    wire:loading.attr="disabled"
                    @if (count($cart) === 0) disabled @endif
                    class="mt-3 w-full rounded-xl bg-amber-500 px-4 py-3 text-base font-bold text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Pay & Complete
                </button>
            </div>
        </div>
    </aside>
</div>

@script
    const outletId = @js(auth()->user()->outlet_id);

    if (window.Echo) {
        window.Echo.private(`outlets.${outletId}`)
            .listen('.StockSynchronized', (e) => {
                // Update stock badge
                const badge = document.getElementById(`stock-badge-${e.product_id}`);
                if (badge) {
                    badge.textContent = `Stock: ${e.quantity}`;
                    badge.className = `rounded-full px-2 py-0.5 text-xs font-medium ${e.quantity <= 5 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`;
                }

                // Disable/enable product button
                const btn = document.querySelector(`button[data-product-id="${e.product_id}"]`);
                if (btn) {
                    btn.disabled = e.quantity <= 0;
                }

                // Sync cart via Livewire
                @this.call('syncCartOnStockChange', e.product_id, e.quantity);
            });
    }
@endscript
