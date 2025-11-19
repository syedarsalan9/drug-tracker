<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RxNormService
{
    private $baseUrl = 'https://rxnav.nlm.nih.gov/REST';
    private $cacheTime = 3600; // 1 hour cache

    /**
     * Search for drugs by name
     * Uses getDrugs endpoint with tty=SBD
     */
    public function searchDrugs(string $drugName)
    {
        // Create cache key based on search term
        $cacheKey = 'drug_search_' . md5(strtolower($drugName));

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($drugName) {
            try {
                $response = Http::timeout(10)->get("{$this->baseUrl}/drugs.json", [
                    'name' => $drugName
                ]);

                if (!$response->successful()) {
                    Log::error('RxNorm API error', ['status' => $response->status()]);
                    return null;
                }

                $data = $response->json();
                
                // Extract SBD (Semantic Branded Drug) entries
                $drugs = $data['drugGroup']['conceptGroup'] ?? [];
                
                foreach ($drugs as $group) {
                    if (isset($group['tty']) && $group['tty'] === 'SBD') {
                        // Get top 5 results
                        $concepts = array_slice($group['conceptProperties'] ?? [], 0, 5);
                        
                        // Fetch detailed info for each drug
                        $results = [];
                        foreach ($concepts as $concept) {
                            $details = $this->getDrugDetails($concept['rxcui']);
                            if ($details) {
                                $results[] = $details;
                            }
                        }
                        
                        return $results;
                    }
                }

                return [];

            } catch (\Exception $e) {
                Log::error('Drug search failed', [
                    'drug_name' => $drugName,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Get detailed drug information including ingredients and dosage forms
     */
    public function getDrugDetails(string $rxcui)
    {
        $cacheKey = 'drug_details_' . $rxcui;

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($rxcui) {
            try {
                $response = Http::timeout(10)->get("{$this->baseUrl}/rxcui/{$rxcui}/historystatus.json");

                if (!$response->successful()) {
                    return null;
                }

                $data = $response->json();
                $attributes = $data['rxcuiStatusHistory']['attributes'] ?? null;

                if (!$attributes) {
                    return null;
                }

                // Extract base names from ingredients
                $baseNames = [];
                $ingredientAndStrength = $attributes['ingredientAndStrength'] ?? [];
                
                foreach ($ingredientAndStrength as $ingredient) {
                    if (isset($ingredient['baseName'])) {
                        $baseNames[] = $ingredient['baseName'];
                    }
                }

                // Extract dosage form names
                $dosageForms = [];
                $doseFormGroups = $attributes['doseFormGroupConcept'] ?? [];
                
                foreach ($doseFormGroups as $doseForm) {
                    if (isset($doseForm['doseFormGroupName'])) {
                        $dosageForms[] = $doseForm['doseFormGroupName'];
                    }
                }

                return [
                    'rxcui' => $rxcui,
                    'name' => $attributes['name'] ?? 'Unknown',
                    'base_names' => array_unique($baseNames),
                    'dosage_forms' => array_unique($dosageForms)
                ];

            } catch (\Exception $e) {
                Log::error('Drug details fetch failed', [
                    'rxcui' => $rxcui,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Verify if an RXCUI is valid
     */
    public function verifyRxcui(string $rxcui): bool
    {
        $cacheKey = 'rxcui_valid_' . $rxcui;

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($rxcui) {
            try {
                $response = Http::timeout(5)->get("{$this->baseUrl}/rxcui/{$rxcui}/status.json");
                
                if (!$response->successful()) {
                    return false;
                }

                $data = $response->json();
                return isset($data['rxcuiStatus']['status']) && 
                       $data['rxcuiStatus']['status'] !== 'NotCurrent';

            } catch (\Exception $e) {
                Log::error('RXCUI verification failed', [
                    'rxcui' => $rxcui,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        });
    }
}