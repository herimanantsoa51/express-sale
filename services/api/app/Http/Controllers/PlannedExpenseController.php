<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExpenseTransactionListResource;
use App\Http\Resources\PlannedExpenseResource;
use App\Models\AccountTransaction;
use App\Models\PlannedExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlannedExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = PlannedExpense::with(['expenseCategory']);

        if ($request->filled('active_only')) {
            $query->active();
        }

        if ($request->filled('frequency')) {
            $query->byFrequency($request->frequency);
        }

        if ($request->filled('overdue_only')) {
            $query->overdue();
        }

        $expenses = $query->latest('next_due_date')->paginate($request->per_page ?? 20);

        return PlannedExpenseResource::collection($expenses);
    }

    public function stats()
    {
        return response()->json(PlannedExpense::getStats());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'estimated_amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'recipient_name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean', // ✅ AJOUTÉ
        ]);

        $expense = PlannedExpense::create([
            'expense_category_id' => $validated['expense_category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'estimated_amount' => $validated['estimated_amount'],
            'frequency' => $validated['frequency'],
            'day_of_week' => $validated['day_of_week'] ?? null,
            'day_of_month' => $validated['day_of_month'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'recipient_name' => $validated['recipient_name'] ?? null,
            'next_due_date' => $validated['start_date'],
            'is_active' => $validated['is_active'] ?? true, // ✅ AJOUTÉ avec valeur par défaut
        ]);

        return response()->json([
            'message' => 'Charge planifiée créée avec succès',
            'data' => new PlannedExpenseResource($expense->load('expenseCategory')),
        ], 201);
    }

    public function show(PlannedExpense $plannedExpense)
    {
        return new PlannedExpenseResource($plannedExpense->load('expenseCategory'));
    }

    public function update(Request $request, PlannedExpense $plannedExpense)
    {
        $validated = $request->validate([
            'expense_category_id' => 'sometimes|exists:expense_categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'estimated_amount' => 'sometimes|numeric|min:0',
            'frequency' => 'sometimes|in:daily,weekly,monthly,yearly',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'end_date' => 'nullable|date',
            'recipient_name' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean', // ✅ DÉJÀ PRÉSENT
        ]);

        $plannedExpense->update($validated);

        return response()->json([
            'message' => 'Charge planifiée mise à jour',
            'data' => new PlannedExpenseResource($plannedExpense->fresh()->load('expenseCategory')),
        ]);
    }

    public function markPaid(PlannedExpense $plannedExpense)
    {
        $lastPaymentDate = $plannedExpense->relatedTransactions()
            ->latest('created_at')
            ->value('created_at');

        if (! $lastPaymentDate) {
            return response()->json([
                'message' => 'Aucun paiement trouvé',
            ], 422);
        }

        $plannedExpense->next_due_date = $plannedExpense->calculateNextDueDate(
            Carbon::parse($lastPaymentDate)->startOfDay()
        );

        $plannedExpense->save();

        return response()->json([
            'message' => 'Prochaine échéance mise à jour',
            'data' => new PlannedExpenseResource(
                $plannedExpense->load('expenseCategory')
            ),
        ]);
    }

    public function destroy(PlannedExpense $plannedExpense)
    {
        $plannedExpense->delete();

        return response()->json(['message' => 'Charge planifiée supprimée']);
    }

    public function transactions(Request $request, PlannedExpense $plannedExpense)
    {
        $perPage = (int) $request->get('per_page', 15);
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $baseQuery = AccountTransaction::query()
            ->with([
                'account:id,name,account_number',
                'plannedExpense:id,name',
            ])
            ->where('planned_expense_id', $plannedExpense->id)
            ->whereHas('transactionType', fn ($q) => $q->where('category', 'expense')
                ->where('code', '!=', 'REVERSAL')
            )
            ->whereNotIn('id', function ($query) {
                $query->select('reversed_transaction_id')
                    ->from('account_transactions')
                    ->whereNotNull('reversed_transaction_id');
            });

        if ($startDate && $endDate) {
            $baseQuery->whereBetween('transaction_date', [$startDate, $endDate]);
        }

        $transactions = (clone $baseQuery)
            ->latest('transaction_date')
            ->paginate($perPage);

        $totalPaid = (clone $baseQuery)
            ->sum(DB::raw('ABS(amount)'));

        $transactionsCount = (clone $baseQuery)->count();

        return response()->json([
            'planned_expense' => new PlannedExpenseResource($plannedExpense->load('expenseCategory')),

            'summary' => [
                'total_paid' => (float) $totalPaid,
                'transactions_count' => $transactionsCount,
                'estimated_amount' => (float) $plannedExpense->estimated_amount,
                'difference' => (float) ($totalPaid - $plannedExpense->estimated_amount),
            ],

            'data' => ExpenseTransactionListResource::collection($transactions),

            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }
}
