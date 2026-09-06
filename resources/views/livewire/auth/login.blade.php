<div class="w-full max-w-md">
    <div class="rounded-2xl bg-white p-8 shadow-lg">
        <div class="mb-6 text-center">
            <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                </svg>
            </span>
            <h1 class="text-xl font-bold text-gray-900">Sign in to Cashier</h1>
            <p class="mt-1 text-sm text-gray-500">Use your staff or admin account</p>
        </div>

        <form wire:submit="authenticate" class="space-y-4">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input
                    wire:model="email"
                    type="email"
                    id="email"
                    autocomplete="email"
                    required
                    class="block w-full rounded-lg border-gray-300 py-2 px-3 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                    placeholder="email@store.com"
                >
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                <input
                    wire:model="password"
                    type="password"
                    id="password"
                    autocomplete="current-password"
                    required
                    class="block w-full rounded-lg border-gray-300 shadow-sm py-2 px-3 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="••••••••"
                >
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input wire:model="remember" type="checkbox" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                Remember me
            </label>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600 disabled:opacity-50"
            >
                Sign in
            </button>
        </form>
    </div>
</div>
