<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthService
{
    private HttpClientInterface $httpClient;
    private string $usersServiceUrl;
    private JwtService $jwtService;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $params, JwtService $jwtService)
    {
        $this->httpClient      = $httpClient;
        $this->usersServiceUrl = $params->get('users_service_url');
        $this->jwtService      = $jwtService;
    }

    public function registerUser(array $data): array
    {
        return $this->makeRequest('POST', '/api/users/create-user', $data);
    }

    public function loginUser(array $data): array
    {
        $responseData = $this->makeRequest('POST', '/api/users/check-user-credentials', $data);
        $token        = $this->jwtService->generateToken($responseData['email']);
        $fullResponse = array_merge($responseData, ["token" => $token]);
        return $fullResponse;
    }

    private function makeRequest(string $method, string $endpoint, array $data): array
    {
        $response     = $this->httpClient->request($method, $this->usersServiceUrl . $endpoint, ['json' => $data]);
        $responseData = json_decode($response->getContent(false), true);
        return $responseData;
    }
}
