<?php

namespace App\Repositories;

use App\Models\UserMedication;

class UserMedicationRepository
{
    public function create(array $data)
    {
        // Using firstOrCreate to prevent duplicates
        return UserMedication::firstOrCreate(
            [
                'user_id' => $data['user_id'],
                'rxcui' => $data['rxcui']
            ],
            $data
        );
    }

    public function findByUserAndRxcui(int $userId, string $rxcui)
    {
        return UserMedication::where('user_id', $userId)
            ->where('rxcui', $rxcui)
            ->first();
    }

    public function getUserMedications(int $userId)
    {
        return UserMedication::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function delete(int $id)
    {
        return UserMedication::destroy($id);
    }
}