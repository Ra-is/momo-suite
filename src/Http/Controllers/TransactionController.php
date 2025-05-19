<?php

namespace Rais\MomoSuite\Http\Controllers;

use App\Http\Controllers\Controller;
use Rais\MomoSuite\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function show($id)
    {
        $transaction = Transaction::with(['logs' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])
            ->where('transaction_id', $id)
            ->firstOrFail();

        return view('momo-suite::dashboard.transactions.show', compact('transaction'));
    }
}
