<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Ouvre et ferme les sessions privées ABIWEIN. */
class AuthenticatedSessionController extends Controller
{
    /** Vérifie les identifiants, l’état du compte puis crée une session sécurisée. */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Le même message est utilisé pour éviter de révéler si un compte existe ou est bloqué.
        $credentials = [
            'email' => $request->string('email')->lower()->toString(),
            'password' => $request->string('password')->toString(),
            'is_active' => true,
        ];

        // MODIFICATION : le booléen remember contrôle la durée de conservation de la session.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Les identifiants sont incorrects ou le compte est bloqué.',
            ]);
        }

        // La régénération protège contre la fixation d’identifiant de session.
        $request->session()->regenerate();

        // La date sert au suivi administratif des connexions réussies.
        $request->user()->forceFill(['last_login_at' => now()])->save();

        // intended() ramène l’utilisateur vers la page privée demandée avant connexion.
        return redirect()->intended(route('dashboard'));
    }

    /** Détruit la session et renouvelle le jeton CSRF. */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home')->with('status', 'Vous êtes maintenant déconnecté.');
    }
}
