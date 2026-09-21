<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Permet à l’administrateur de créer, consulter et bloquer les comptes. */
class UserController extends Controller
{
    /** Affiche les comptes par ordre de rôle puis de nom. */
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->orderBy('role')->orderBy('name')->paginate(15),
        ]);
    }

    /** Affiche le formulaire de création avec la liste officielle des rôles. */
    public function create(): View
    {
        return view('admin.users.create', ['roles' => UserRole::cases()]);
    }

    /** Crée le compte après validation serveur des champs. */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // boolean() convertit correctement la case à cocher HTML en vrai booléen.
        $validated['is_active'] = $request->boolean('is_active');

        // Le modèle User chiffre automatiquement le mot de passe grâce à son cast.
        User::query()->create($validated);

        return to_route('admin.users.index')->with('status', 'Le compte utilisateur a été créé.');
    }

    /** Active ou bloque un compte, sans permettre à l’administrateur de se bloquer lui-même. */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['account' => 'Vous ne pouvez pas bloquer votre propre compte.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $message = $user->is_active ? 'Le compte a été activé.' : 'Le compte a été bloqué.';

        return back()->with('status', $message);
    }
}
