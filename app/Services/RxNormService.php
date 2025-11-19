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
     */
    public function searchDrugs(string $drugName)
    {
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
                
                // Extract SBD entries
                $drugs = $data['drugGroup']['conceptGroup'] ?? [];
                
                foreach ($drugs as $group) {
                    if (isset($group['tty']) && $group['tty'] === 'SBD') {
                        // Get top 5 results
                        $concepts = array_slice($group['conceptProperties'] ?? [], 0, 5);
                        
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
     * Get detailed drug information
     */
    public function getDrugDetails(string $rxcui)
    {
        $cacheKey = 'drug_details_' . $rxcui;

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($rxcui) {
            try {
                $url = "{$this->baseUrl}/rxcui/{$rxcui}/historystatus.json";
                
                Log::info('Fetching drug details', ['url' => $url]);
                
                $response = Http::timeout(10)->get($url);

                if (!$response->successful()) {
                    Log::warning('Drug details fetch failed', [
                        'rxcui' => $rxcui,
                        'status' => $response->status()
                    ]);
                    return null;
                }

                $data = $response->json();
                
                // Get attributes (for drug name)
                $attributes = $data['rxcuiStatusHistory']['attributes'] ?? null;
                
                // Get definitional features (for ingredients and dosage forms)
                $definitionalFeatures = $data['rxcuiStatusHistory']['definitionalFeatures'] ?? null;

                if (!$attributes) {
                    Log::warning('No attributes found', ['rxcui' => $rxcui]);
                    return null;
                }

                Log::info('Definitional Features', [
                    'rxcui' => $rxcui,
                    'features' => $definitionalFeatures
                ]);

                // Extract base names from ingredientAndStrength
                $baseNames = [];
                if (isset($definitionalFeatures['ingredientAndStrength'])) {
                    foreach ($definitionalFeatures['ingredientAndStrength'] as $ingredient) {
                        if (isset($ingredient['baseName'])) {
                            $baseNames[] = $ingredient['baseName'];
                        }
                    }
                }

                // Extract dosage forms from doseFormGroupConcept
                $dosageForms = [];
                if (isset($definitionalFeatures['doseFormGroupConcept'])) {
                    foreach ($definitionalFeatures['doseFormGroupConcept'] as $doseForm) {
                        if (isset($doseForm['doseFormGroupName'])) {
                            $dosageForms[] = $doseForm['doseFormGroupName'];
                        }
                    }
                }

                $result = [
                    'rxcui' => $rxcui,
                    'name' => $attributes['name'] ?? 'Unknown',
                    'base_names' => array_values(array_unique($baseNames)),
                    'dosage_forms' => array_values(array_unique($dosageForms))
                ];

                Log::info('Drug details extracted', [
                    'rxcui' => $rxcui,
                    'result' => $result
                ]);

                return $result;

            } catch (\Exception $e) {
                Log::error('Drug details exception', [
                    'rxcui' => $rxcui,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return null;
            }
        });
    }

    /**
     * Verify if RXCUI is valid
     */
    public function verifyRxcui(string $rxcui): bool
    {
        $cacheKey = 'rxcui_valid_' . $rxcui;

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($rxcui) {
            try {
                $url = "{$this->baseUrl}/rxcui/{$rxcui}/historystatus.json";
                
                Log::info('Verifying RXCUI', ['rxcui' => $rxcui, 'url' => $url]);
                
                $response = Http::timeout(10)->get($url);
                
                if (!$response->successful()) {
                    Log::warning('RXCUI verification failed', [
                        'rxcui' => $rxcui,
                        'status' => $response->status()
                    ]);
                    return false;
                }

                $data = $response->json();
                
                // Check if rxcuiStatusHistory exists
                if (!isset($data['rxcuiStatusHistory'])) {
                    Log::warning('No status history found', ['rxcui' => $rxcui]);
                    return false;
                }

                // Check if attributes exist
                $attributes = $data['rxcuiStatusHistory']['attributes'] ?? null;
                
                if (!$attributes || !isset($attributes['name'])) {
                    Log::warning('No attributes or name found', ['rxcui' => $rxcui]);
                    return false;
                }

                // Check status
                $metaData = $data['rxcuiStatusHistory']['metaData'] ?? null;
                $status = $metaData['status'] ?? null;

                Log::info('RXCUI verification result', [
                    'rxcui' => $rxcui,
                    'has_attributes' => !empty($attributes),
                    'status' => $status,
                    'name' => $attributes['name'] ?? null
                ]);

                return true;

            } catch (\Exception $e) {
                Log::error('RXCUI verification exception', [
                    'rxcui' => $rxcui,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        });
    }

    /**
     * Alternative verification using direct lookup
     */
    public function verifyRxcuiAlternative(string $rxcui): bool
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/rxcui/{$rxcui}.json");
            
            if (!$response->successful()) {
                return false;
            }

            $data = $response->json();
            
            return isset($data['idGroup']['rxnormId']) 
                && !empty($data['idGroup']['rxnormId']);

        } catch (\Exception $e) {
            Log::error('Alternative verification failed', [
                'rxcui' => $rxcui,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}