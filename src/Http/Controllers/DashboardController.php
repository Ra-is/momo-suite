<?php

namespace Rais\MomoSuite\Http\Controllers;

use Rais\MomoSuite\Models\Transaction;
use Rais\MomoSuite\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Rais\MomoSuite\Events\TransactionStatusUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get date range from request or use current month
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $dateRange = [$startDate, $endDate];

        // Get all transactions for the period
        $transactions = Transaction::whereBetween('created_at', $dateRange)->get();

        // Calculate daily volumes with type separation
        $dailyVolumes = [
            'receive' => [],
            'send' => [],
            'total' => []
        ];
        $dailyLabels = [];

        // Get all dates in the range
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dailyLabels[] = $date->format('M d');
            $dayTransactions = $transactions->filter(function ($t) use ($date) {
                return $t->created_at->format('Y-m-d') === $date->format('Y-m-d');
            });

            $dailyVolumes['receive'][] = $dayTransactions->where('type', 'receive')->sum('amount');
            $dailyVolumes['send'][] = $dayTransactions->where('type', 'send')->sum('amount');
            $dailyVolumes['total'][] = $dayTransactions->sum('amount');
        }

        // Calculate week-over-week change
        $previousWeekTransactions = Transaction::whereBetween('created_at', [
            now()->subDays(13)->startOfDay(),
            now()->subDays(7)->endOfDay()
        ])->get();

        $currentWeekSuccess = $transactions->where('status', 'success')->count();
        $previousWeekSuccess = $previousWeekTransactions->where('status', 'success')->count();

        $weekOverWeekChange = $previousWeekSuccess > 0
            ? (($currentWeekSuccess - $previousWeekSuccess) / $previousWeekSuccess) * 100
            : 0;

        // Calculate average processing time for pending transactions
        $avgProcessingTime = $transactions->where('status', 'pending')
            ->avg(function ($transaction) {
                return $transaction->created_at->diffInMinutes(now());
            }) ?? 0;

        // Separate stats by transaction type
        $receiveTransactions = $transactions->where('type', 'receive');
        $sendTransactions = $transactions->where('type', 'send');

        // Network distribution for all transactions
        $networkDistribution = Transaction::query()
            ->when($dateRange, function ($query, $dateRange) {
                return $query->whereBetween('created_at', $dateRange);
            })
            ->groupBy('network')
            ->selectRaw('network, count(*) as count')
            ->pluck('count', 'network')
            ->toArray();

        // Network distribution for receive transactions
        $networkDistributionReceive = Transaction::query()
            ->where('type', 'receive')
            ->when($dateRange, function ($query, $dateRange) {
                return $query->whereBetween('created_at', $dateRange);
            })
            ->groupBy('network')
            ->selectRaw('network, count(*) as count')
            ->pluck('count', 'network')
            ->toArray();

        // Network distribution for send transactions
        $networkDistributionSend = Transaction::query()
            ->where('type', 'send')
            ->when($dateRange, function ($query, $dateRange) {
                return $query->whereBetween('created_at', $dateRange);
            })
            ->groupBy('network')
            ->selectRaw('network, count(*) as count')
            ->pluck('count', 'network')
            ->toArray();

        $recentTransactions = Transaction::latest()->take(5)->get();

        return view('momo-suite::dashboard.index', [
            'stats' => [
                'total_transactions' => $transactions->count(),
                'successful_transactions' => $transactions->where('status', 'success')->count(),
                'pending_transactions' => $transactions->where('status', 'pending')->count(),
                'failed_transactions' => $transactions->where('status', 'failed')->count(),
                'total_amount' => $transactions->sum('amount'),
                'currency' => 'GHS',
                'week_over_week_change' => round($weekOverWeekChange, 1),
                'avg_processing_time' => round($avgProcessingTime),

                // Receive metrics
                'receive_total' => $receiveTransactions->count(),
                'receive_amount' => $receiveTransactions->sum('amount'),
                'receive_success' => $receiveTransactions->where('status', 'success')->count(),
                'receive_pending' => $receiveTransactions->where('status', 'pending')->count(),
                'receive_failed' => $receiveTransactions->where('status', 'failed')->count(),

                // Send metrics
                'send_total' => $sendTransactions->count(),
                'send_amount' => $sendTransactions->sum('amount'),
                'send_success' => $sendTransactions->where('status', 'success')->count(),
                'send_pending' => $sendTransactions->where('status', 'pending')->count(),
                'send_failed' => $sendTransactions->where('status', 'failed')->count(),

                // Chart data
                'network_distribution' => $networkDistribution,
                'network_distribution_receive' => $networkDistributionReceive,
                'network_distribution_send' => $networkDistributionSend,
                'daily_labels' => $dailyLabels,
                'daily_volumes' => $dailyVolumes,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ],
            'recentTransactions' => $recentTransactions,
        ]);
    }

    public function transactions(Request $request)
    {
        $query = Transaction::query();

        // Apply status filter
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Apply type filter
        if ($request->type && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Apply network filter
        if ($request->network && $request->network !== 'all') {
            $query->where('network', $request->network);
        }

        // Apply search filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('transaction_id', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%')
                    ->orWhere('reference', 'like', '%' . $request->search . '%');
            });
        }

        // Apply date range filter
        if ($request->date_range) {
            $dates = explode(' to ', $request->date_range);
            if (count($dates) === 2) {
                $query->whereBetween('created_at', [
                    Carbon::parse($dates[0])->startOfDay(),
                    Carbon::parse($dates[1])->endOfDay()
                ]);
            }
        }

        // Order by latest first
        $query->latest();

        // Get paginated results
        $transactions = $query->paginate(15)->withQueryString();

        return view('momo-suite::dashboard.transactions', compact('transactions'));
    }

    public function showTransaction(Transaction $transaction)
    {
        return view('momo-suite::dashboard.transaction-show', compact('transaction'));
    }

    public function checkTransactionStatus($transactionId)
    {
        $transaction = Transaction::where('transaction_id', $transactionId)->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        try {
            // Get the provider service
            $momoService = app('momo-suite');
            $provider = $momoService->provider($transaction->provider);

            // Check status
            $result = $provider->checkStatus($transaction->transaction_id);

            // Update transaction status if check was successful
            if ($result['success']) {
                $oldStatus = $transaction->status;
                $newStatus = $result['status'];

                $transaction->update([
                    'status' => $newStatus,
                    'error_message' => $newStatus === 'failed' ? $result['message'] : null,
                    'callback_received_at' => $transaction->callback_received_at ?? now(),
                ]);

                // Add log entry for status check
                $transaction->addLog(
                    'status_check',
                    $newStatus,
                    [
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'message' => $result['message'],
                        'provider_transaction_id' => $result['provider_transaction_id'] ?? null,
                        'raw_response' => $result['raw_response'] ?? null,
                    ]
                );

                event(new TransactionStatusUpdated($transaction, $oldStatus, $newStatus));

                return response()->json([
                    'success' => true,
                    'message' => 'Transaction status updated successfully',
                    'status' => $newStatus,
                ]);
            }

            // Log failed check attempt
            $transaction->addLog(
                'status_check_failed',
                'failed',
                [
                    'message' => $result['message'],
                    'raw_response' => $result['raw_response'] ?? null,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        } catch (\Exception $e) {
            // Log error
            $transaction->addLog(
                'status_check_error',
                'error',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to check transaction status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateTransactionStatus(Request $request, $transactionId)
    {
        // Check if user is admin OR has the transactions.update permission
        if (!auth()->user()->isAdmin && !auth()->user()->hasPermission('transactions.update')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update transaction status.'
            ], 403);
        }

        $transaction = Transaction::where('transaction_id', $transactionId)->first();
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.'
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:success,failed',
            'reason' => 'required|string|min:10'
        ]);

        $oldStatus = $transaction->status;
        $transaction->status = $validated['status'];
        $transaction->callback_received_at = $transaction->callback_received_at ?? now();
        $transaction->error_message = $validated['status'] === 'failed' ? $validated['reason'] : null;
        $transaction->save();

        // Log the status change
        $transaction->logs()->create([
            'event' => 'manual_status_update',
            'data' => [
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'reason' => $validated['reason'],
                'updated_by' => auth()->user()->name
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction status updated successfully.'
        ]);
    }

    public function users()
    {
        $users = User::latest()->paginate(10);
        return view('momo-suite::dashboard.users', compact('users'));
    }
}
