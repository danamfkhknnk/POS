<div class="p-6 bg-white rounded-lg shadow">
    <h1 class="text-2xl font-bold mb-4">Dashboard</h1>
    <p class="text-gray-600 mb-4">
        Last update: <span class="font-mono">{{ $lastUpdate }}</span>
    </p>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($outlets as $outlet)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $outlet['code'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $outlet['name'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $outlet['staff_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@script
    let interval = @this.interval;
    setInterval(() => {
        @this.call('refreshData');
    }, interval * 1000);
@endscript
