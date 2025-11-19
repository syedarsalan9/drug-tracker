<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RxNormService;

class DrugSearchController extends Controller
{
    private $rxNormService;

    public function __construct(RxNormService $rxNormService)
    {
        $this->rxNormService = $rxNormService;
    }

    /**
     * Public endpoint to search drugs
     * No authentication required
     */
    public function search(Request $request)
    {
        $request->validate([
            'drug_name' => 'required|string|min:2'
        ]);

        $drugName = $request->input('drug_name');
        
        $results = $this->rxNormService->searchDrugs($drugName);

        if ($results === null) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch drug information. Please try again later.'
            ], 503);
        }

        if (empty($results)) {
            return response()->json([
                'success' => true,
                'message' => 'No drugs found matching your search',
                'data' => []
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Drugs found successfully',
            'data' => $results,
            'count' => count($results)
        ]);
    }
}