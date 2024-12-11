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

    public function registerUser($data)
    {
        $response = $this->httpClientInterface->request('POST', $_ENV['USERS_SERVICE_BASE_URL'] . '/api/users/create-user', [
            'json' => $data
        ]);

        $responseContent = $response->toArray();

        return [
            "source" => 'UserService::createUser',
            "type" => "https://example.com/probs/invalid-data",
            "title" => $responseContent['title'],
            "status" => $responseContent['status'],
            "detail" => $responseContent['detail'],
            "message" => $responseContent['message']
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