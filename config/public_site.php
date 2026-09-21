<?php

return [
    /*
     * Coordonnées affichées dans le pied de page et la page contact.
     * MODIFICATION : renseignez-les dans .env, jamais directement ici.
     */
    'phone' => env('PUBLIC_PHONE'),
    'email' => env('PUBLIC_CONTACT_EMAIL'),
    'address' => env('PUBLIC_ADDRESS'),
    'business_hours' => env('PUBLIC_BUSINESS_HOURS'),

    /* Numéro WhatsApp international, sans espace ni signe "+" ; vide = bouton masqué. */
    'whatsapp' => env('PUBLIC_WHATSAPP'),
];
