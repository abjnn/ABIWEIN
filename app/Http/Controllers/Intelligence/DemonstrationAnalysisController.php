<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Services\Intelligence\FarmDemonstrationAnalysisService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Affiche l'aide à la décision locale sans modifier les données analysées. */
final class DemonstrationAnalysisController extends Controller
{
    /** Construit une analyse adaptée aux droits du compte connecté. */
    public function __invoke(Request $request, FarmDemonstrationAnalysisService $analysisService): View
    {
        return view('intelligence.demo', $analysisService->analyzeFor($request->user()));
    }
}
