<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MetabaseService
{
    protected $client;
    protected $metabaseUrl;
    protected $sessionToken;

    protected $grantsDB = 'Grants DB';
    protected $database = []; // Hold a reference to the database details

    public function __construct()
    {
        $this->metabaseUrl = env('METABASE_REGENDATA_URL');
        $this->client = new Client();

        $this->getSessionToken();

        $this->database[$this->grantsDB] = [];
        $this->database[$this->grantsDB]['id'] = $this->getDatabaseIdByName($this->grantsDB);
        $this->database[$this->grantsDB]['tables'] = $this->getTablesInDatabase($this->grantsDB);

        if (isset($this->database[$this->grantsDB]['tables'])) {
            foreach ($this->database[$this->grantsDB]['tables'] as $key => $value) {
                $this->database[$this->grantsDB]['tables'][$key]['fields'] = $this->getFieldsInTable($this->grantsDB, $key);
            }
        }
    }

    /**
     * Pass in columns and a name and return the index of the column name
     */
    private function findColumnIndex($cols, $columnName)
    {
        foreach ($cols as $key => $value) {
            if ($value['name'] == $columnName) {
                return $key;
            }
        }
    }

    /**
     * Get the total donor amount in usd for a project and application
     */
    public function getDonorAmountUSD($roundId, $applicationId)
    {
        $cacheName = 'MetabaseService::getDonorAmountUSD(' . $roundId . ', ' . $applicationId . ')';

        // Check if the donor amount is already cached
        if (Cache::has($cacheName)) {
            return Cache::get($cacheName);
        }

        try {
            $response = $this->client->request('POST', $this->metabaseUrl . '/api/dataset', [
                'headers' => [
                    'X-Metabase-Session' => $this->sessionToken
                ],
                'json' => [
                    'database' => $this->database[$this->grantsDB]['id'],
                    'query' => [
                        'source-table' => $this->database[$this->grantsDB]['tables']['ApplicationsInRounds']['id'],
                        'filter' => [
                            'and',
                            ['=', ["field", $this->database[$this->grantsDB]['tables']['ApplicationsInRounds']['fields']['applicationMetadata → application → round']['id'], null], $roundId],
                            ['=', ["field", $this->database[$this->grantsDB]['tables']['ApplicationsInRounds']['fields']['applicationId']['id'], null], $applicationId]
                        ],
                    ],
                    'type' => 'query',
                ],
            ]);

            if ($response->getStatusCode() == 202) {
                $data = json_decode((string) $response->getBody(), true)['data'];

                $amountUSDIndex = $this->findColumnIndex($data['cols'], 'amountUSD');

                $donorAmountUSD = 0;
                foreach ($data['rows'] as $key => $value) {
                    $donorAmountUSD += $value[$amountUSDIndex];
                }

                $return = $donorAmountUSD;
                Cache::put($cacheName, $return, 60 * 24);

                return $return;
            } else {
                Log::error("Metabase query error, status code: " . $response->getStatusCode());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Error fetching donor amount: " . $e->getMessage());
            echo "Error fetching donor amount: " . $e->getMessage();
            return null;
        }
    }


    /**
     * Get the matching distribution for a project and application
     */
    public function getMatchingDistribution($chainId, $projectId, $applicationId)
    {
        $cacheName = 'MetabaseService::getMatchingDistribution1(' . $chainId . ', ' . $projectId . ', ' . $applicationId . ')';

        // Check if the matching distribution is already cached
        if (Cache::has($cacheName)) {
            return Cache::get($cacheName);
        }

        try {
            $response = $this->client->request('POST', $this->metabaseUrl . '/api/dataset', [
                'headers' => [
                    'X-Metabase-Session' => $this->sessionToken
                ],
                'json' => [
                    'database' => $this->database[$this->grantsDB]['id'],
                    'query' => [
                        'source-table' => $this->database[$this->grantsDB]['tables']['MatchingDistribution']['id'],
                        'filter' => [
                            'and',
                            ['=', ["field", $this->database[$this->grantsDB]['tables']['MatchingDistribution']['fields']['chainId']['id'], null], $chainId],
                            ['=', ["field", $this->database[$this->grantsDB]['tables']['MatchingDistribution']['fields']['projectId']['id'], null], $projectId],
                            ['=', ["field", $this->database[$this->grantsDB]['tables']['MatchingDistribution']['fields']['applicationId']['id'], null], $applicationId]
                        ]
                    ],
                    'type' => 'query',
                ],
            ]);

            if ($response->getStatusCode() == 202) {
                $data = json_decode((string) $response->getBody(), true)['data'];

                $return = [];
                $return['matchAmountUSD'] = 0;
                $return['originalMatchAmountUSD'] = 0;
                $return['donorAmountUSD'] = 0;
                $return['contributionsCount'] = 0;

                $matchAmountUSDIndex = $this->findColumnIndex($data['cols'], 'matchAmountUSD');
                $originalMatchAmountUSDIndex = $this->findColumnIndex($data['cols'], 'originalMatchAmountUSD');
                $contributionsCountIndex = $this->findColumnIndex($data['cols'], 'contributionsCount');

                foreach ($data['rows'] as $key => $value) {
                    $return['matchAmountUSD'] += $value[$matchAmountUSDIndex];
                    $return['originalMatchAmountUSD'] += $value[$originalMatchAmountUSDIndex];
                    $return['donorAmountUSD'] = $return['originalMatchAmountUSD'] - $return['matchAmountUSD'];
                    $return['contributionsCount'] += $value[$contributionsCountIndex];
                }

                // Cache the matching distribution for 1 day
                Cache::put($cacheName, $return, 60 * 24);

                return $return;
            } else {
                Log::error("Metabase query error, status code: " . $response->getStatusCode());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Error fetching matching distribution: " . $e->getMessage());
            echo "Error fetching matching distribution: " . $e->getMessage();
            return null;
        }
    }

    public function getDatabaseIdByName($databaseName)
    {
        $cacheName = 'MetabaseService::getDatabaseIdByName(' . $databaseName . ')';
        // Check if the database id is already cached
        if (Cache::has($cacheName)) {
            return Cache::get($cacheName);
        }

        try {
            // Fetch all databases
            $response = $this->client->request('GET', $this->metabaseUrl . '/api/database', [
                'headers' => [
                    'X-Metabase-Session' => $this->sessionToken
                ]
            ]);

            $databases = json_decode((string) $response->getBody(), true);

            // Search for the database by name
            foreach ($databases['data'] as $database) {
                if ($database['name'] === $databaseName) {
                    // Cache the database id for 1 day
                    Cache::put($cacheName, $database['id'], 60 * 24);
                    return $database['id'];
                }
            }
        } catch (\Exception $e) {
            // Handle exception (e.g., log error)
            Log::error("Error fetching databases: " . $e->getMessage());
        }

        return null;
    }


    public function getTablesInDatabase($databaseName)
    {
        $cacheName = 'MetabaseService::getTablesInDatabase(' . $databaseName . ')';

        // Check if the tables are already cached
        if (Cache::has($cacheName)) {
            return Cache::get($cacheName);
        }

        $databaseId = $this->getDatabaseIdByName($databaseName);
        if (!$databaseId) {
            return null;
        }

        $response = $this->client->request('GET', $this->metabaseUrl . '/api/database/' . $databaseId . '/schema/public', [
            'headers' => [
                'X-Metabase-Session' => $this->sessionToken
            ]
        ]);

        $tables = json_decode((string) $response->getBody(), true);

        $return = [];

        foreach ($tables as $key => $value) {
            $return[$value['name']] = [
                'id' => $value['id'],
                'fields' => null
            ];
        }

        // Cache the tables for 1 day
        Cache::put($cacheName, $return, 60 * 24);

        return $return;
    }



    public function getTableIdByName($databaseName, $tableName)
    {
        if (!isset($this->database[$databaseName]['tables'][$tableName])) {
            return null;
        }

        return $this->database[$databaseName]['tables'][$tableName]['id'];
    }


    public function getFieldsInTable($databaseName, $tableName)
    {
        $cacheName = 'MetabaseService::getFieldsInTable(' . $databaseName . ', ' . $tableName . ')';

        // Check if the fields are already cached
        if (Cache::has($cacheName)) {
            return Cache::get($cacheName);
        }

        $tableId = $this->getTableIdByName($databaseName, $tableName);
        if (!$tableId) {
            return null;
        }

        $response = $this->client->request('GET', $this->metabaseUrl . '/api/table/' . $tableId . '/query_metadata', [
            'headers' => [
                'X-Metabase-Session' => $this->sessionToken
            ]
        ]);

        $fields = json_decode((string) $response->getBody(), true)['fields'];

        $return = [];
        foreach ($fields as $key => $value) {
            $return[$value['name']] = ['id' => $value['id']];
        }

        // Cache the fields for 1 day
        Cache::put($cacheName, $return, 60 * 24);

        return $return;
    }




    public function getSessionToken()
    {
        if (!Cache::has('metabase_session_token')) {
            try {
                $response = $this->client->request('POST', $this->metabaseUrl . '/api/session', [ // Fixed endpoint
                    'json' => [
                        'username' => env('METABASE_REGENDATA_USERNAME'),
                        'password' => env('METABASE_REGENDATA_PASSWORD')
                    ]
                ]);

                $this->sessionToken = json_decode((string) $response->getBody(), true)['id'];
                Cache::put('metabase_session_token', $this->sessionToken, now()->addDays(14));
            } catch (\Exception $e) {
                Log::error("Error getting session token: " . $e->getMessage());
                // Handle the exception appropriately
            }
        } else {
            $this->sessionToken = Cache::get('metabase_session_token');
        }
    }
}
