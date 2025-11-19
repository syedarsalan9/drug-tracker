<?php
namespace App\Repositories;

use App\Models\UserMedication;

class UserMedicationRepository
{
    public function create(array $data)
    {
        // Check if already exists
        $existing = UserMedication::where('user_id', $data['user_id'])
            ->where('rxcui', $data['rxcui'])
            ->first();
        
        if ($existing) {
            throw new \Exception('This medication is already in your list');
        }

        return UserMedication::create($data);
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