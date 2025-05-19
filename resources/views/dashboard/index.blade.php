@php
    $successRate = $stats['total_transactions'] > 0 
        ? round(($stats['successful_transactions'] / $stats['total_transactions']) * 100, 1) 
        : 0;
    
    $failureRate = $stats['total_transactions'] > 0 
        ? round(($stats['failed_transactions'] / $stats['total_transactions']) * 100, 1) 
        : 0;

    $avgProcessingTime = round($stats['avg_processing_time'] ?? 0);
@endphp

<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header with Date Range -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold text-gray-900">Transaction Overview</h2>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">Date Range:</span>
                    <input type="text" id="mainDateRange" 
                        class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        value="{{ $stats['date_range']['start'] }} to {{ $stats['date_range']['end'] }}"
                        placeholder="Select date range">
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Total Volume -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 bg-opacity-50">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Volume</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['currency'] }} {{ number_format($stats['total_amount'], 2) }}</p>
                            <p class="text-sm text-gray-500">{{ $stats['total_transactions'] }} transactions</p>
                        </div>
                    </div>
                </div>

                <!-- Success Rate -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 bg-opacity-50">
                            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Success Rate</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ number_format(($stats['successful_transactions'] / max($stats['total_transactions'], 1)) * 100, 1) }}%</p>
                            <p class="text-sm {{ $stats['week_over_week_change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $stats['week_over_week_change'] >= 0 ? '+' : '' }}{{ number_format($stats['week_over_week_change'], 1) }}% vs last week
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pending -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 bg-opacity-50">
                            <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Pending</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['pending_transactions'] }}</p>
                            <p class="text-sm text-gray-500">Avg. wait: {{ $stats['avg_processing_time'] }}m</p>
                        </div>
                    </div>
                </div>

                <!-- Failed Rate -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 bg-opacity-50">
                            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Failed Rate</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ number_format(($stats['failed_transactions'] / max($stats['total_transactions'], 1)) * 100, 1) }}%</p>
                            <p class="text-sm text-gray-500">{{ $stats['failed_transactions'] }} failed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Transaction Volume Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Transaction Volume</h3>
                        <div class="flex items-center space-x-2">
                            <select id="volumeChartType" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="total">Total</option>
                                <option value="receive">Receive</option>
                                <option value="send">Send</option>
                            </select>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="volumeChart"></canvas>
                    </div>
                </div>

                <!-- Network Distribution Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Network Distribution</h3>
                        <select id="networkChartType" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="total">All Types</option>
                            <option value="receive">Receive Only</option>
                            <option value="send">Send Only</option>
                        </select>
                    </div>
                    <div class="h-64">
                        <canvas id="networkChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Transaction Type Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Receive Stats -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Receive Transactions</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Volume</p>
                            <p class="text-xl font-semibold text-gray-900">{{ $stats['currency'] }} {{ number_format($stats['receive_amount'], 2) }}</p>
                            <p class="text-sm text-gray-500">{{ $stats['receive_total'] }} transactions</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Success Rate</p>
                            <p class="text-xl font-semibold text-gray-900">
                                {{ number_format(($stats['receive_success'] / max($stats['receive_total'], 1)) * 100, 1) }}%
                            </p>
                            <p class="text-sm text-gray-500">{{ $stats['receive_success'] }} successful</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Failed</p>
                            <p class="text-xl font-semibold text-gray-900">{{ $stats['receive_failed'] }}</p>
                            <p class="text-sm text-gray-500">{{ $stats['receive_pending'] }} pending</p>
                        </div>
                    </div>
                </div>

                <!-- Send Stats -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Send Transactions</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Volume</p>
                            <p class="text-xl font-semibold text-gray-900">{{ $stats['currency'] }} {{ number_format($stats['send_amount'], 2) }}</p>
                            <p class="text-sm text-gray-500">{{ $stats['send_total'] }} transactions</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Success Rate</p>
                            <p class="text-xl font-semibold text-gray-900">
                                {{ number_format(($stats['send_success'] / max($stats['send_total'], 1)) * 100, 1) }}%
                            </p>
                            <p class="text-sm text-gray-500">{{ $stats['send_success'] }} successful</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Failed</p>
                            <p class="text-xl font-semibold text-gray-900">{{ $stats['send_failed'] }}</p>
                            <p class="text-sm text-gray-500">{{ $stats['send_pending'] }} pending</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Recent Transactions</h3>
                        <a href="{{ route('momo.transactions') }}" class="text-sm text-blue-600 hover:text-blue-900">View all transactions →</a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Network</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($recentTransactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $transaction->transaction_id }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->provider }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $stats['currency'] }} {{ number_format($transaction->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->phone }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->network }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->type === 'receive' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $transaction->status === 'success' ? 'bg-green-100 text-green-800' : 
                                               ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <a href="{{ route('momo.transactions.show', $transaction) }}" class="text-blue-600 hover:text-blue-900">View Details</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        No transactions found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize main date range picker
            const mainDateRangePicker = flatpickr("#mainDateRange", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: ["{{ $stats['date_range']['start'] }}", "{{ $stats['date_range']['end'] }}"],
                onChange: function(selectedDates) {
                    if (selectedDates.length === 2) {
                        window.location.href = "{{ route('momo.dashboard') }}?" + new URLSearchParams({
                            start_date: selectedDates[0].toISOString().split('T')[0],
                            end_date: selectedDates[1].toISOString().split('T')[0]
                        });
                    }
                }
            });

            // Volume Chart
            const volumeChart = new Chart(document.getElementById('volumeChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($stats['daily_labels']) !!},
                    datasets: [
                        {
                            label: 'Total Volume',
                            data: {!! json_encode($stats['daily_volumes']['total']) !!},
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Receive Volume',
                            data: {!! json_encode($stats['daily_volumes']['receive']) !!},
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true,
                            hidden: true
                        },
                        {
                            label: 'Send Volume',
                            data: {!! json_encode($stats['daily_volumes']['send']) !!},
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            fill: true,
                            hidden: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': {{ $stats['currency'] }} ' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '{{ $stats['currency'] }} ' + value;
                                }
                            }
                        }
                    }
                }
            });

            // Handle volume chart type changes
            document.getElementById('volumeChartType').addEventListener('change', function(e) {
                const type = e.target.value;
                volumeChart.data.datasets.forEach(dataset => {
                    if (type === 'total') {
                        dataset.hidden = dataset.label !== 'Total Volume';
                    } else if (type === 'receive') {
                        dataset.hidden = dataset.label !== 'Receive Volume';
                    } else {
                        dataset.hidden = dataset.label !== 'Send Volume';
                    }
                });
                volumeChart.update();
            });

            // Network Distribution Chart Data
            const networkData = {
                total: {
                    labels: {!! json_encode(array_keys($stats['network_distribution'])) !!},
                    data: {!! json_encode(array_values($stats['network_distribution'])) !!}
                },
                receive: {
                    labels: {!! json_encode(array_keys($stats['network_distribution_receive'] ?? [])) !!},
                    data: {!! json_encode(array_values($stats['network_distribution_receive'] ?? [])) !!}
                },
                send: {
                    labels: {!! json_encode(array_keys($stats['network_distribution_send'] ?? [])) !!},
                    data: {!! json_encode(array_values($stats['network_distribution_send'] ?? [])) !!}
                }
            };

            // Network Distribution Chart
            const networkChart = new Chart(document.getElementById('networkChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: networkData.total.labels.length > 0 ? networkData.total.labels : [],
                    datasets: [{
                        data: networkData.total.data.length > 0 ? networkData.total.data : [],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',  // MTN - Blue
                            'rgba(239, 68, 68, 0.8)',   // Telecel - Red
                            'rgba(16, 185, 129, 0.8)',  // AirtelTigo - Green
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: networkData.total.labels.length === 0 ? 'No data available for the selected period' : ''
                        }
                    }
                }
            });

            // Handle network chart type changes
            document.getElementById('networkChartType').addEventListener('change', function(e) {
                const type = e.target.value;
                const data = networkData[type];
                
                if (data && data.labels.length > 0) {
                    networkChart.data.labels = data.labels;
                    networkChart.data.datasets[0].data = data.data;
                    networkChart.options.plugins.title.text = '';
                } else {
                    networkChart.data.labels = [];
                    networkChart.data.datasets[0].data = [];
                    networkChart.options.plugins.title.text = 'No data available for the selected period';
                }
                networkChart.update();
            });
        });
    </script>
    @endpush
</x-app-layout> 