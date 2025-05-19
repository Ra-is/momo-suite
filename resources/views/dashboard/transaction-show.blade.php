<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <div class="mb-6 flex items-center space-x-2 text-sm">
                <a href="{{ route('momo.dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <a href="{{ route('momo.transactions') }}" class="text-gray-500 hover:text-gray-700">Transactions</a>
                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="text-gray-500">Transaction Details</span>
            </div>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Transaction Details</h1>
                        <p class="mt-1 text-sm text-gray-500">Transaction ID: {{ $transaction->transaction_id }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            @if($transaction->status === 'success') bg-green-100 text-green-800
                            @elseif($transaction->status === 'pending') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ ucfirst($transaction->status) }}
                        </span>
                        @if($transaction->status === 'pending')
                            <button
                                type="button"
                                onclick="checkTransactionStatus()"
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Check Status
                            </button>
                        @endif
                        @if(auth()->user()->isAdmin || auth()->user()->hasPermission('transactions.update'))
                            <button
                                type="button"
                                onclick="openUpdateStatusModal()"
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Update Status
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Transaction Information -->
                <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Basic Details -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-900">Basic Details</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Amount</label>
                                <p class="mt-1 text-lg font-semibold text-gray-900">
                                    {{ $transaction->currency }} {{ number_format($transaction->amount, 2) }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Phone Number</label>
                                <p class="mt-1 text-gray-900">{{ $transaction->phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Network</label>
                                <p class="mt-1 text-gray-900">{{ strtoupper($transaction->network) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Type</label>
                                <p class="mt-1 text-gray-900">{{ ucfirst($transaction->type) }}</p>
                            </div>
                            @if($transaction->meta && is_array($transaction->meta) && count($transaction->meta))
                            <div class="pt-2">
                                <div class="flex items-center mb-1">
                                    <svg class="w-4 h-4 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm font-medium text-indigo-700">Meta</span>
                                </div>
                                <div class="space-y-1 pl-5">
                                    @foreach($transaction->meta as $key => $value)
                                        <div>
                                            <span class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                            @if(is_array($value) || is_object($value))
                                                <span class="text-gray-900">
                                                    <pre class="bg-gray-50 rounded p-2 text-xs text-gray-600 whitespace-pre-wrap break-words inline-block align-middle">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                </span>
                                            @elseif(is_bool($value))
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ $value ? 'Yes' : 'No' }}</span>
                                            @else
                                                <span class="text-gray-900">{{ $value }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-900">Timeline</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Created At</label>
                                <p class="mt-1 text-gray-900">{{ $transaction->created_at->format('M d, Y H:i:s') }}</p>
                            </div>
                            @if($transaction->callback_received_at)
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Callback Received</label>
                                <p class="mt-1 text-gray-900">{{ $transaction->callback_received_at->format('M d, Y H:i:s') }}</p>
                            </div>
                            @endif
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Processing Time</label>
                                <p class="mt-1 text-gray-900">
                                    @if($transaction->callback_received_at)
                                        {{ $transaction->callback_received_at->diffForHumans($transaction->created_at, true) }}
                                    @else
                                        Still Processing
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-900">Additional Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Provider</label>
                                <p class="mt-1 text-gray-900">{{ ucfirst($transaction->provider) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Reference</label>
                                <p class="mt-1 text-gray-900">{{ $transaction->reference ?? 'N/A' }}</p>
                            </div>
                            @if($transaction->error_message)
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Error Message</label>
                                <p class="mt-1 text-sm text-red-600">{{ $transaction->error_message }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Technical Details (Collapsible) -->
                <div class="px-6 py-4 border-t border-gray-200">
                    <div x-data="{ open: false }" class="space-y-4">
                        <button @click="open = !open" class="flex items-center text-sm text-gray-500 hover:text-gray-700">
                            <svg :class="{'rotate-90': open}" class="h-5 w-5 transform transition-transform duration-200" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="ml-2">Technical Details</span>
                        </button>

                        <div x-show="open" class="space-y-4" style="display: none;">
                            @if($transaction->meta)
                            <div>
                                <label class="block text-sm font-medium text-gray-500 flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Meta Data
                                </label>
                                <div class="mt-2 bg-white border border-gray-200 rounded-lg overflow-hidden">
                                    <table class="min-w-full divide-y divide-gray-100">
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($transaction->meta as $key => $value)
                                                <tr>
                                                    <td class="px-4 py-2 font-medium text-gray-700 whitespace-nowrap w-1/3">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                    <td class="px-4 py-2 text-gray-900">
                                                        @if(is_array($value) || is_object($value))
                                                            <pre class="bg-gray-50 rounded p-2 text-xs text-gray-600 whitespace-pre-wrap break-words">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                        @elseif(is_bool($value))
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ $value ? 'Yes' : 'No' }}</span>
                                                        @else
                                                            {{ $value }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif

                            @if($transaction->request)
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Request Data</label>
                                <pre class="mt-1 p-4 bg-gray-50 rounded-lg text-sm whitespace-pre overflow-auto max-h-[300px] scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">{{ json_encode($transaction->request, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            @endif

                            @if($transaction->response)
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Response Data</label>
                                <pre class="mt-1 p-4 bg-gray-50 rounded-lg text-sm whitespace-pre overflow-auto max-h-[300px] scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">{{ json_encode($transaction->response, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            @endif

                            @if($transaction->callback_data)
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Callback Data</label>
                                <pre class="mt-1 p-4 bg-gray-50 rounded-lg text-sm whitespace-pre overflow-auto max-h-[300px] scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">{{ json_encode($transaction->callback_data, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Transaction Logs -->
                @if($transaction->logs->count() > 0)
                <div class="px-6 py-4 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Transaction Timeline</h3>
                    <div class="space-y-6">
                        @foreach($transaction->logs as $log)
                        <div class="relative">
                            <!-- Timeline line -->
                            @if(!$loop->last)
                                <div class="absolute left-4 top-8 bottom-0 w-0.5 bg-gray-200"></div>
                            @endif

                            <div class="relative flex items-start space-x-3">
                                <!-- Event indicator -->
                                <div class="relative">
                                    <div class="h-8 w-8 rounded-full border-2 flex items-center justify-center
                                        @if($log->event === 'api_response') bg-blue-50 border-blue-500
                                        @elseif($log->event === 'callback_received') bg-green-50 border-green-500
                                        @elseif($log->event === 'subsequent_callback_received') bg-purple-50 border-purple-500
                                        @elseif($log->event === 'manual_status_update') bg-orange-50 border-orange-500
                                        @elseif($log->event === 'api_error') bg-red-50 border-red-500
                                        @elseif($log->event === 'otp_verification') bg-teal-50 border-teal-500
                                        @else bg-gray-50 border-gray-500 @endif">
                                        @if($log->event === 'api_response')
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        @elseif($log->event === 'callback_received')
                                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @elseif($log->event === 'subsequent_callback_received')
                                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @elseif($log->event === 'manual_status_update')
                                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        @elseif($log->event === 'api_error')
                                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @elseif($log->event === 'otp_verification')
                                            <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354l6 2.149V11c0 5.25-3.75 9.75-6 10-2.25-.25-6-4.75-6-10V6.503l6-2.149z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                                        <!-- Event Header -->
                                        <div class="px-4 py-3 border-b border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <span class="text-sm font-medium capitalize
                                                        @if($log->event === 'api_response') text-blue-600
                                                        @elseif($log->event === 'callback_received') text-green-600
                                                        @elseif($log->event === 'subsequent_callback_received') text-purple-600
                                                        @elseif($log->event === 'manual_status_update') text-orange-600
                                                        @elseif($log->event === 'api_error') text-red-600
                                                        @elseif($log->event === 'otp_verification') text-teal-600
                                                        @else text-gray-900 @endif">
                                                        {{ str_replace('_', ' ', $log->event) }}
                                                    </span>
                                                    <span class="text-sm text-gray-500">{{ $log->created_at->format('M d, Y H:i:s') }}</span>
                                                </div>
                                                <div class="flex items-center space-x-3 text-sm text-gray-500">
                                                    @if($log->ip_address)
                                                        <span class="flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                                            </svg>
                                                            {{ $log->ip_address }}
                                                        </span>
                                                    @endif
                                                    @if($log->user_agent)
                                                        <span class="hidden md:flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                            </svg>
                                                            {{ Str::before($log->user_agent, ' AppleWebKit') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Log Data -->
                                        @if($log->data)
                                        <div class="divide-y divide-gray-200">
                                            @foreach($log->data as $key => $value)
                                            <div class="hover:bg-gray-50 transition-colors duration-150">
                                                <div class="px-4 py-3">
                                                    <div class="mb-1">
                                                        <span class="text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                                    </div>
                                                    @if(is_array($value) || is_object($value))
                                                        <pre class="text-sm font-mono text-gray-600 whitespace-pre-wrap break-words bg-gray-50 rounded p-2 mt-1">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                    @elseif(is_bool($value))
                                                        <div class="text-sm text-gray-600">{{ $value ? 'Yes' : 'No' }}</div>
                                                    @else
                                                        <div class="text-sm text-gray-600">{{ $value }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div
        x-data="{ 
            open: false,
            status: '{{ $transaction->status }}',
            reason: '',
            init() {
                window.openUpdateStatusModal = () => {
                    this.open = true;
                }
            }
        }"
        x-show="open"
        class="fixed z-10 inset-0 overflow-y-auto"
        x-cloak
        style="display: none;"
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity"
                aria-hidden="true"
            >
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <div
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
            >
                <form id="updateStatusForm" onsubmit="updateTransactionStatus(event)">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Update Transaction Status
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">New Status</label>
                                        <select
                                            x-model="status"
                                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                        >
                                            <option value="success">Success</option>
                                            <option value="failed">Failed</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Reason for Change</label>
                                        <textarea
                                            x-model="reason"
                                            rows="3"
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            placeholder="Please provide a reason for this status change..."
                                            required
                                        ></textarea>
                                    </div>
                                    <div id="status-error" class="text-sm text-red-600 mb-2" style="display:none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Update Status
                        </button>
                        <button
                            type="button"
                            @click="open = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function checkTransactionStatus() {
            const button = event.target.closest('button');
            const originalContent = button.innerHTML;
            
            // Disable button and show loading state
            button.disabled = true;
            button.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Checking...
            `;

            // Make API call
            fetch(`{{ route('momo.transactions.check-status', $transaction->transaction_id) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show updated status
                    window.location.reload();
                } else {
                    // Show error and reset button
                    alert(data.message);
                    button.disabled = false;
                    button.innerHTML = originalContent;
                }
            })
            .catch(error => {
                // Show error and reset button
                alert('Failed to check transaction status');
                button.disabled = false;
                button.innerHTML = originalContent;
            });
        }

        async function updateTransactionStatus(event) {
            event.preventDefault();
            const form = event.target;
            const status = form.querySelector('select').value;
            const reason = form.querySelector('textarea').value;

            // Hide previous error
            showStatusError('');

            try {
                const response = await fetch(`{{ route('momo.transactions.update-status', $transaction->transaction_id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status, reason })
                });

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else if (response.status === 422 && data.errors) {
                    // Show the first validation error (for reason)
                    const errorMsg = data.errors.reason ? data.errors.reason[0] : 'Validation error';
                    showStatusError(errorMsg);
                } else {
                    // Show any error message in the modal, not as alert
                    showStatusError(data.message || 'Failed to update status');
                }
            } catch (error) {
                showStatusError('Failed to update status');
            }
        }

        function showStatusError(message) {
            const errorDiv = document.getElementById('status-error');
            errorDiv.textContent = message;
            errorDiv.style.display = message ? 'block' : 'none';
        }
    </script>
    @endpush
</x-app-layout> 