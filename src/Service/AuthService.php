<?php
namespace App\Service;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthService
{
    private HttpClientInterface $httpClientInterface;
    private string $jwtSecretKey;

    public function __construct(HttpClientInterface $httpClientInterface, ParameterBagInterface $parameterBagInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->jwtSecretKey        = $parameterBagInterface->get('jwt_secret_key');
    }

    public function registerUser(array $data): array
    {
        $response = $this->httpClientInterface->request('POST', $_ENV['USERS_SERVICE_BASE_URL'] . '/api/users/create-user', [
            'json' => $data,
        ]);

        $responseContent = $response->toArray();

        return [
            "source"  => "UserService::createUser",
            "type"    => "https://example.com/probs/invalid-data",
            "title"   => $responseContent['title'],
            "status"  => $responseContent['status'],
            "detail"  => $responseContent['detail'],
            "message" => $responseContent['message'],
        ];
    }

    public function loginUser(array $data): array
    {
        $response = $this->httpClientInterface->request('POST', $_ENV['USERS_SERVICE_BASE_URL'] . '/api/users/check-user-credentials', [
            'json' => $data,
        ]);

        $data = json_decode($response->getContent(), true);

        if (Response::HTTP_OK !== $data['status']) {
            return [
                "source"  => "AuthService::loginUser",
                "type"    => "https://example.com/probs/invalid-data",
                "title"   => $data['title'],
                "status"  => $data['status'],
                "detail"  => $data['detail'],
                "message" => $data['message'],
            ];
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::file($this->jwtSecretKey)
        );

        $token = $config->builder()
            ->issuedBy('http://auth-service')
            ->permittedFor('http://frontend')
            ->issuedAt(new \DateTimeImmutable())
            ->expiresAt((new \DateTimeImmutable())->modify('+1 day'))
            ->withClaim('email', $data['email'])
            ->getToken($config->signer(), $config->signingKey());

        return [
            "source"  => "AuthService::loginUser",
            "type"    => "https://example.com/probs/invalid-data",
            "title"   => $data['title'],
            "status"  => $data['status'],
            "detail"  => $data['detail'],
            "message" => $data['message'],
            "token"   => $token,
        ];
    }
}
