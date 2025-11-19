<?php

namespace App\Services;

use App\Models\UserMedication;
use App\Repositories\UserMedicationRepository;

class MedicationService
{
    private $repository;
    private $rxNormService;

    public function __construct(
        UserMedicationRepository $repository,
        RxNormService $rxNormService
    ) {
        $this->repository = $repository;
        $this->rxNormService = $rxNormService;
    }

    /**
     * Add medication to user's list
     */
    public function addMedication(int $userId, string $rxcui)
    {
        // First verify RXCUI is valid
        if (!$this->rxNormService->verifyRxcui($rxcui)) {
            throw new \Exception('Invalid RXCUI provided');
        }

        // Get drug details
        $drugDetails = $this->rxNormService->getDrugDetails($rxcui);
        
        if (!$drugDetails) {
            throw new \Exception('Unable to fetch drug details');
        }

        // Save to database
        return $this->repository->create([
            'user_id' => $userId,
            'rxcui' => $rxcui,
            'drug_name' => $drugDetails['name'],
            'base_names' => $drugDetails['base_names'],
            'dosage_forms' => $drugDetails['dosage_forms']
        ]);
    }

    /**
     * Remove medication from user's list
     */
    public function removeMedication(int $userId, string $rxcui)
    {
        $medication = $this->repository->findByUserAndRxcui($userId, $rxcui);

        if (!$medication) {
            throw new \Exception('Medication not found in your list');
        }

        return $this->repository->delete($medication->id);
    }

    /**
     * Get all user medications
     */
    public function getUserMedications(int $userId)
    {
        return $this->repository->getUserMedications($userId);
    }
}