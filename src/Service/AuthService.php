<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthService
{
    private HttpClientInterface $httpClientInterface;

    public function __construct(HttpClientInterface $httpClientInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
    }

    public function registerUser(string $email, string $password)
    {
        // Checking if user exists
        $response = $this->httpClientInterface->request('GET', $_ENV['USERS_SERVICE_BASE_URL'] . '/api/users/check-user', [
            'query' => ['email' => $email]
        ]);

        if ($response->getStatusCode() === 200 && !empty($response->toArray())) {
            return [
                'success' => false,
                'message' => "L'email $email est déjà utilisé",
                'source' => 'AuthService::RegisterUser',
                'status' => 409
            ];
        }

        $response = $this->httpClientInterface->request('POST', $_ENV['USERS_SERVICE_BASE_URL'] . '/api/users/create-user', [
            'json' => [
                'email' => $email,
                'password' => $password
            ]
        ]);

        if ($response->getStatusCode() !== 201) {
            return [
                'success' => false,
                'message' => 'Problème lors de la création',
                'source' => 'AuthService::RegisterUser',
                'status' => $response->getStatusCode()
            ];
        }

        return [
            'success' => true,
            'message' => 'Utilisateur crée',
            'source' => 'AuthService::RegisterUser',
            'status' => 201
        ];
    }
}