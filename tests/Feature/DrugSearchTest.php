<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class DrugSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // Clear cache before each test
    }

    /**
     * Test can search drugs successfully
     */
    public function test_can_search_drugs_successfully()
    {
        // Mock the external RxNorm API
        Http::fake([
            '*rxnav.nlm.nih.gov/REST/drugs.json*' => Http::response([
                'drugGroup' => [
                    'conceptGroup' => [
                        [
                            'tty' => 'SBD',
                            'conceptProperties' => [
                                [
                                    'rxcui' => '243670',
                                    'name' => 'Aspirin 81 MG Oral Tablet'
                                ],
                                [
                                    'rxcui' => '198467',
                                    'name' => 'Aspirin 325 MG Oral Tablet'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
            
            '*rxnav.nlm.nih.gov/REST/rxcui/*/historystatus.json*' => Http::response([
                'rxcuiStatusHistory' => [
                    'metaData' => [
                        'status' => 'Active'
                    ],
                    'attributes' => [
                        'name' => 'Aspirin 81 MG Oral Tablet',
                        'rxcui' => '243670'
                    ],
                    'definitionalFeatures' => [
                        'ingredientAndStrength' => [
                            [
                                'baseName' => 'Aspirin',
                                'numeratorValue' => '81',
                                'numeratorUnit' => 'MG'
                            ]
                        ],
                        'doseFormGroupConcept' => [
                            [
                                'doseFormGroupName' => 'Oral Product'
                            ],
                            [
                                'doseFormGroupName' => 'Pill'
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->getJson('/api/drugs/search?drug_name=aspirin');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'rxcui',
                             'name',
                             'base_names',
                             'dosage_forms'
                         ]
                     ],
                     'count'
                 ])
                 ->assertJson([
                     'success' => true
                 ]);

        // Verify data structure
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        
        if (count($data) > 0) {
            $this->assertArrayHasKey('rxcui', $data[0]);
            $this->assertArrayHasKey('base_names', $data[0]);
            $this->assertArrayHasKey('dosage_forms', $data[0]);
        }
    }

    /**
     * Test drug search requires drug_name parameter
     */
    public function test_drug_search_requires_drug_name()
    {
        $response = $this->getJson('/api/drugs/search');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['drug_name']);
    }

    /**
     * Test drug search with minimum 2 characters
     */
    public function test_drug_search_requires_minimum_characters()
    {
        $response = $this->getJson('/api/drugs/search?drug_name=a');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['drug_name']);
    }

    /**
     * Test drug search returns empty array for no results
     */
    public function test_drug_search_returns_empty_for_no_results()
    {
        Http::fake([
            '*rxnav.nlm.nih.gov/REST/drugs.json*' => Http::response([
                'drugGroup' => [
                    'conceptGroup' => []
                ]
            ], 200)
        ]);

        $response = $this->getJson('/api/drugs/search?drug_name=xyznonexistent');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => []
                 ]);
    }

    /**
     * Test drug search results are cached
     */
    public function test_drug_search_results_are_cached()
    {
        Http::fake([
            '*rxnav.nlm.nih.gov/*' => Http::response([
                'drugGroup' => ['conceptGroup' => []]
            ], 200)
        ]);

        // First request
        $this->getJson('/api/drugs/search?drug_name=test');
        
        // Second request should use cache
        $this->getJson('/api/drugs/search?drug_name=test');

        // Should only make requests once due to caching
        Http::assertSentCount(1);
    }

    /**
     * Test handles API errors gracefully
     */
    public function test_handles_api_errors_gracefully()
    {
        Http::fake([
            '*rxnav.nlm.nih.gov/*' => Http::response([], 503)
        ]);

        $response = $this->getJson('/api/drugs/search?drug_name=aspirin');

        $response->assertStatus(503)
                 ->assertJson([
                     'success' => false
                 ]);
    }
}