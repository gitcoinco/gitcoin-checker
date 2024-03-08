<?php

/**
 * The BendeckDavid\GraphqlClient\Facades\GraphQL package only allows for one GraphQL endpoint, so build another service in order to query the Open Source Observer GraphQL endpoint.
 */

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GraphQLService
{
    /**
     * The GraphQL endpoint URL.
     *
     * @var string
     */
    protected $endpoint;

    /**
     * Headers to be sent with the request.
     *
     * @var array
     */
    protected $headers;

    /**
     * Constructor to set up the GraphQL service.
     *
     * @param string $endpoint
     * @param array $headers
     */
    public function __construct($endpoint, $headers = [], $token = null)
    {
        $this->endpoint = $endpoint;
        $this->headers = array_merge([
            'Content-Type' => 'application/json',
            'Authorization' => $token ? 'Bearer ' . $token : null,
        ], $headers);
    }

    /**
     * Execute a GraphQL query against the set endpoint.
     *
     * @param string $query
     * @param array $variables
     * @return array
     */
    public function query($query, $variables = null, $operationName = null)
    {
        $cacheName = 'GraphQLService::query(' . $query . ')';

        return Cache::remember($cacheName, now()->addMinutes(60), function () use ($query, $variables, $operationName) {
            $payload = json_encode([
                'query' => $query,
                'variables' => $variables, // This can be null or an associative array
                'operationName' => $operationName, // Optional, based on if your query needs it
            ]);

            $response = Http::withHeaders($this->headers)
                ->withBody($payload, 'application/json')
                ->post($this->endpoint);

            $response->throw();

            return $response->json();
        });
    }
}
