<?php
// app/Http/Controllers/UserMedicationController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MedicationService;
use App\Http\Requests\AddDrugRequest;

class UserMedicationController extends Controller
{
    private $medicationService;

    public function __construct(MedicationService $medicationService)
    {
        $this->medicationService = $medicationService;
    }

    /**
     * Get all medications for authenticated user
     */
    public function index(Request $request)
    {
        $medications = $this->medicationService->getUserMedications($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Medications retrieved successfully',
            'data' => $medications,
            'count' => $medications->count()
        ]);
    }

    /**
     * Add a new medication to user's list
     */
    public function store(AddDrugRequest $request)
    {
        try {
            $medication = $this->medicationService->addMedication(
                $request->user()->id,
                $request->rxcui
            );

            return response()->json([
                'success' => true,
                'message' => 'Medication added successfully',
                'data' => $medication
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove a medication from user's list
     */
    public function destroy(Request $request, string $rxcui)
    {
        try {
            $this->medicationService->removeMedication(
                $request->user()->id,
                $rxcui
            );

            return response()->json([
                'success' => true,
                'message' => 'Medication removed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }
}