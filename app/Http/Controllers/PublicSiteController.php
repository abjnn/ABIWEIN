<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/** Affiche les pages accessibles aux visiteurs sans compte ABIWEIN. */
class PublicSiteController extends Controller
{
    /** Affiche la page d'accueil publique. */
    public function home(): View
    {
        return view('pages.home');
    }

    /** Affiche la présentation de la ferme et de la plateforme. */
    public function about(): View
    {
        return view('pages.about');
    }

    /** Affiche les coordonnées et le formulaire de contact. */
    public function contact(): View
    {
        return view('pages.contact');
    }

    /** Affiche l'entrée de l'espace privé, sans simuler l'authentification. */
    public function login(): View|RedirectResponse
    {
        // Un utilisateur déjà connecté retourne directement dans son espace privé.
        if (Auth::check()) {
            return to_route('dashboard');
        }

        return view('auth.login');
    }
}
