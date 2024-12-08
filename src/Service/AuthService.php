<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Configuration;

class AuthService
{
    private HttpClientInterface $httpClientInterface;
    private string $jwtSecretKey;

    public function __construct(HttpClientInterface $httpClientInterface, ParameterBagInterface $parameterBagInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->jwtSecretKey = $parameterBagInterface->get('jwt_secret_key');
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

    public function loginUser(string $email, string $password)
    {
        $response = $this->httpClientInterface->request('POST', $_ENV['USERS_SERVICE_BASE_URL'] . '/api/users/check-user-credentials', [
            'json' => [
                'email' => $email,
                'password' => $password
            ]
        ]);

        $data = json_decode($response->getContent(), true);

        if ($data['success']) {
            $config = Configuration::forSymmetricSigner(
                new Sha256(),
                InMemory::file($this->jwtSecretKey)
            );

            $token = $config->builder()
                ->issuedBy('http://auth-service')   // Service émetteur
                ->permittedFor('http://frontend')  // Destinataire
                ->issuedAt(new \DateTimeImmutable()) // Date d'émission
                ->expiresAt((new \DateTimeImmutable())->modify('+1 day')) // Expiration
                ->withClaim('email', $email) // Données personnalisées
                ->getToken($config->signer(), $config->signingKey());

            return [
                'success' => true,
                'token' => $token,
                'code' => 200
            ];
        }

        return $data['message'];
    }
}