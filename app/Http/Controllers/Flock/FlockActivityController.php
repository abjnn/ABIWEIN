<?php

namespace App\Http\Controllers\Flock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flock\StoreFlockActivityRequest;
use App\Models\Flock;
use App\Services\Flock\FlockOperationService;
use Illuminate\Http\RedirectResponse;

/** Ajoute les actions horodatées au journal quotidien d'une bande. */
class FlockActivityController extends Controller
{
    public function __construct(private readonly FlockOperationService $operations) {}

    /** Enregistre l'action et synchronise ses éventuels totaux techniques. */
    public function store(StoreFlockActivityRequest $request, Flock $flock): RedirectResponse
    {
        $this->operations->addActivity($flock, $request->validated(), $request->user());

        return to_route('flocks.show', $flock)->with('status', 'L’action a été ajoutée au journal de la bande.');
    }
}
