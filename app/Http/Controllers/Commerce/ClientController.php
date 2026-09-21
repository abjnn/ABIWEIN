<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\SaveClientRequest;
use App\Models\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Gère le répertoire des acheteurs particuliers et professionnels. */
class ClientController extends Controller
{
    /** Affiche les clients avec une recherche facultative sur le nom, le téléphone ou l'e-mail. */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('recherche', ''));

        $clients = Client::query()
            ->withCount('sales')
            ->withSum('sales', 'balance_due')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('commerce.clients.index', compact('clients', 'search'));
    }

    /** Ouvre le formulaire vide d'un nouveau client. */
    public function create(): View
    {
        return view('commerce.clients.create');
    }

    /** Enregistre un client validé dans une transaction courte. */
    public function store(SaveClientRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        DB::transaction(fn () => Client::query()->create($data));

        return to_route('clients.index')->with('status', 'Le client a été créé.');
    }

    /** Ouvre le formulaire prérempli du client demandé. */
    public function edit(Client $client): View
    {
        return view('commerce.clients.edit', compact('client'));
    }

    /** Met à jour les coordonnées sans toucher à l'historique des ventes. */
    public function update(SaveClientRequest $request, Client $client): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        DB::transaction(fn () => $client->update($data));

        return to_route('clients.index')->with('status', 'Les informations du client ont été mises à jour.');
    }

    /** Active ou désactive un client tout en conservant ses ventes et ses créances. */
    public function toggleStatus(Client $client): RedirectResponse
    {
        DB::transaction(fn () => $client->update(['is_active' => ! $client->is_active]));

        return back()->with('status', $client->fresh()->is_active ? 'Le client a été activé.' : 'Le client a été désactivé.');
    }
}
