<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\SaveExpenseRequest;
use App\Models\Expense;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Gère le registre traçable des sorties de caisse. */
class ExpenseController extends Controller
{
    /** Affiche les dépenses récentes et leur total, avec une recherche libre. */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('recherche', ''));
        $baseQuery = Expense::query();
        $totalExpenses = (clone $baseQuery)->sum('amount');

        $expenses = $baseQuery
            ->with('recordedBy')
            ->when($search !== '', fn ($query) => $query
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"))
            ->latest('spent_on')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('commerce.expenses.index', compact('expenses', 'search', 'totalExpenses'));
    }

    /** Ouvre le formulaire d'une nouvelle sortie de caisse. */
    public function create(): View
    {
        return view('commerce.expenses.create');
    }

    /** Enregistre la dépense et l'identité de son auteur dans une transaction. */
    public function store(SaveExpenseRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            Expense::query()->create([
                ...$request->validated(),
                'reference' => $this->uniqueReference(),
                'recorded_by' => $request->user()->id,
            ]);
        });

        return to_route('expenses.index')->with('status', 'La dépense a été enregistrée.');
    }

    /** Ouvre les informations modifiables d'une dépense existante. */
    public function edit(Expense $expense): View
    {
        return view('commerce.expenses.edit', compact('expense'));
    }

    /** Corrige la catégorie, le motif, le montant ou la date sans remplacer l'auteur initial. */
    public function update(SaveExpenseRequest $request, Expense $expense): RedirectResponse
    {
        DB::transaction(fn () => $expense->update($request->validated()));

        return to_route('expenses.index')->with('status', 'La dépense a été mise à jour.');
    }

    /** Construit une référence DEP unique et facile à retrouver dans le registre. */
    private function uniqueReference(): string
    {
        do {
            $reference = 'DEP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Expense::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
