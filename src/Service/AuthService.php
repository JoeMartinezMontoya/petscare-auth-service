<?php
namespace App\Service;

use App\Utils\HttpStatusCodes;
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
        return $this->makeRequest('POST', '/api/users/create-user', $data, HttpStatusCodes::CREATED, "AuthService::registerUser");
    }

    public function loginUser(array $data): array
    {
        $responseData = $this->makeRequest('POST', '/api/users/check-user-credentials', $data, HttpStatusCodes::SUCCESS, "AuthService::loginUser");
        $token        = $this->jwtService->generateToken($responseData['mail']);
        return array_merge($responseData, ["token" => $token]);
    }

    private function makeRequest(string $method, string $endpoint, array $data, int $expectedStatus, string $source): array
    {
        try {
            $response     = $this->httpClient->request($method, $this->usersServiceUrl . $endpoint, ['json' => $data]);
            $responseData = json_decode($response->getContent(false), true);
            return $responseData;
        } catch (\Exception $e) {
            return [
                "source"  => "AuthService::$source",
                "type"    => "https://example.com/probs/service-unavailable",
                "title"   => "Service unavailable",
                "status"  => HttpStatusCodes::SERVICE_UNAVAILABLE,
                "detail"  => "An error occured while trying to reach users-service",
                "message" => $e->getMessage(),
            ];
        }
    }
}
