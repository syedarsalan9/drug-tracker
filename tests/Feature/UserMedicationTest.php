<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserMedication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class UserMedicationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
        
        Cache::flush();
    }

    /**
     * Mock valid RXCUI response
     */
    protected function mockValidRxcui()
    {
        Http::fake([
            '*rxnav.nlm.nih.gov/REST/rxcui/243670/historystatus.json' => Http::response([
                'rxcuiStatusHistory' => [
                    'metaData' => [
                        'status' => 'Active'
                    ],
                    'attributes' => [
                        'name' => 'Aspirin 81 MG Oral Tablet',
                        'rxcui' => '243670',
                        'tty' => 'SCD'
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
                            ['doseFormGroupName' => 'Oral Product'],
                            ['doseFormGroupName' => 'Pill']
                        ]
                    ]
                ]
            ], 200),
            
            // Mock for other valid RXCUIs
            '*rxnav.nlm.nih.gov/REST/rxcui/310965/historystatus.json' => Http::response([
                'rxcuiStatusHistory' => [
                    'metaData' => ['status' => 'Active'],
                    'attributes' => [
                        'name' => 'Ibuprofen 200 MG Oral Tablet',
                        'rxcui' => '310965'
                    ],
                    'definitionalFeatures' => [
                        'ingredientAndStrength' => [['baseName' => 'Ibuprofen']],
                        'doseFormGroupConcept' => [['doseFormGroupName' => 'Oral Product']]
                    ]
                ]
            ], 200)
        ]);
    }

    /**
     * Mock invalid RXCUI response
     */
    protected function mockInvalidRxcui()
    {
        Http::fake([
            '*rxnav.nlm.nih.gov/REST/rxcui/999999999/historystatus.json' => Http::response([
                'rxcuiStatusHistory' => [
                    // Empty or no attributes - invalid RXCUI
                ]
            ], 200)
        ]);
    }

    /**
     * Test user can add medication with valid RXCUI
     */
    public function test_user_can_add_medication_with_valid_rxcui()
    {
        $this->mockValidRxcui();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/medications', [
            'rxcui' => '243670'
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'user_id',
                         'rxcui',
                         'drug_name',
                         'base_names',
                         'dosage_forms',
                         'created_at',
                         'updated_at'
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'Medication added successfully'
                 ]);

        $this->assertDatabaseHas('user_medications', [
            'user_id' => $this->user->id,
            'rxcui' => '243670'
        ]);

        $medication = UserMedication::where('rxcui', '243670')->first();
        $this->assertIsArray($medication->base_names);
        $this->assertIsArray($medication->dosage_forms);
        $this->assertNotEmpty($medication->base_names);
        $this->assertNotEmpty($medication->dosage_forms);
    }

    /**
     * Test cannot add medication with invalid RXCUI
     */
    public function test_cannot_add_medication_with_invalid_rxcui()
    {
        $this->mockInvalidRxcui();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/medications', [
            'rxcui' => '999999999'
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false
                 ]);
    }

    /**
     * Test cannot add medication without RXCUI
     */
    public function test_cannot_add_medication_without_rxcui()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/medications', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['rxcui']);
    }

    /**
     * Test cannot add duplicate medication
     */
    public function test_cannot_add_duplicate_medication()
    {
        $this->mockValidRxcui();

        // Add first time
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/medications', [
            'rxcui' => '243670'
        ]);

        // Clear HTTP mock and set it up again
        Http::fake([
            '*rxnav.nlm.nih.gov/REST/rxcui/243670/historystatus.json' => Http::response([
                'rxcuiStatusHistory' => [
                    'metaData' => ['status' => 'Active'],
                    'attributes' => [
                        'name' => 'Aspirin 81 MG Oral Tablet',
                        'rxcui' => '243670'
                    ],
                    'definitionalFeatures' => [
                        'ingredientAndStrength' => [['baseName' => 'Aspirin']],
                        'doseFormGroupConcept' => [['doseFormGroupName' => 'Oral Product']]
                    ]
                ]
            ], 200)
        ]);

        // Try to add again
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/medications', [
            'rxcui' => '243670'
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false
                 ]);
    }

    /**
     * Test user can get their medications
     */
    public function test_user_can_get_their_medications()
    {
        UserMedication::factory()->create([
            'user_id' => $this->user->id,
            'rxcui' => '243670',
            'drug_name' => 'Aspirin 81 MG Oral Tablet',
            'base_names' => ['Aspirin'],
            'dosage_forms' => ['Oral Product', 'Pill']
        ]);

        UserMedication::factory()->create([
            'user_id' => $this->user->id,
            'rxcui' => '310965',
            'drug_name' => 'Ibuprofen 200 MG Oral Tablet',
            'base_names' => ['Ibuprofen'],
            'dosage_forms' => ['Oral Product']
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/medications');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'rxcui',
                             'drug_name',
                             'base_names',
                             'dosage_forms'
                         ]
                     ],
                     'count'
                 ])
                 ->assertJson([
                     'success' => true,
                     'count' => 2
                 ]);
    }

    /**
     * Test user can only see their own medications
     */
    public function test_user_can_only_see_their_own_medications()
    {
        UserMedication::factory()->create([
            'user_id' => $this->user->id,
            'rxcui' => '243670'
        ]);

        $otherUser = User::factory()->create();
        UserMedication::factory()->create([
            'user_id' => $otherUser->id,
            'rxcui' => '310965'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/medications');

        $response->assertStatus(200)
                 ->assertJson([
                     'count' => 1
                 ]);

        $data = $response->json('data');
        $this->assertEquals('243670', $data[0]['rxcui']);
    }

    /**
     * Test user can delete their medication
     */
    public function test_user_can_delete_their_medication()
    {
        $medication = UserMedication::factory()->create([
            'user_id' => $this->user->id,
            'rxcui' => '243670'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson('/api/medications/243670');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Medication removed successfully'
                 ]);

        $this->assertDatabaseMissing('user_medications', [
            'id' => $medication->id
        ]);
    }

    /**
     * Test user cannot delete non-existent medication
     */
    public function test_user_cannot_delete_nonexistent_medication()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson('/api/medications/999999');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false
                 ]);
    }

    /**
     * Test user cannot delete another user's medication
     */
    public function test_user_cannot_delete_another_users_medication()
    {
        $otherUser = User::factory()->create();
        $medication = UserMedication::factory()->create([
            'user_id' => $otherUser->id,
            'rxcui' => '243670'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson('/api/medications/243670');

        $response->assertStatus(404);

        $this->assertDatabaseHas('user_medications', [
            'id' => $medication->id
        ]);
    }

    /**
     * Test unauthenticated user cannot access medications
     */
    public function test_unauthenticated_user_cannot_access_medications()
    {
        $response = $this->getJson('/api/medications');
        $response->assertStatus(401);
    }

    /**
     * Test unauthenticated user cannot add medication
     */
    public function test_unauthenticated_user_cannot_add_medication()
    {
        $response = $this->postJson('/api/medications', [
            'rxcui' => '243670'
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test unauthenticated user cannot delete medication
     */
    public function test_unauthenticated_user_cannot_delete_medication()
    {
        $response = $this->deleteJson('/api/medications/243670');
        $response->assertStatus(401);
    }
}