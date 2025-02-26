<?php
namespace App\Service;

use App\Exception\ApiException;
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
        $token        = $this->jwtService->generateToken($responseData['email'] ?? '');
        return array_merge($responseData, ["token" => $token]);
    }

    private function makeRequest(string $method, string $endpoint, array $data, int $expectedStatus, string $source): array
    {
        try {
            $response     = $this->httpClient->request($method, $this->usersServiceUrl . $endpoint, ['json' => $data]);
            $responseData = json_decode($response->getContent(false), true);
            return array_merge($this->validateApiResponse($source, $responseData, $expectedStatus), $responseData);
        } catch (\Exception $e) {
            throw new ApiException(
                title: "Service Unavailable",
                detail: "Error communicating with User Service.",
                message: $e->getMessage(),
                status: HttpStatusCodes::SERVICE_UNAVAILABLE
            );
        }
    }

    private function validateApiResponse(string $source, array $data, int $expectedStatus): array
    {
        $validStatuses = [$expectedStatus, HttpStatusCodes::SUCCESS];
        if (! isset($data['status']) || ! in_array($data['status'], $validStatuses, true)) {
            throw new ApiException(
                title: $data['title'] ?? "Unknown Error",
                detail: $data['detail'] ?? "No details provided.",
                message: $data['message'] ?? "Something went wrong.",
                status: $data['status'] ?? HttpStatusCodes::SERVER_ERROR
            );
        }

        return [
            "source"  => $source,
            "type"    => $data['type'] ?? "https://example.com/probs/unknown",
            "title"   => $data['title'] ?? "Success",
            "status"  => $data['status'],
            "detail"  => $data['detail'] ?? "No details available.",
            "message" => $data['message'] ?? "Operation completed successfully.",
        ];
    }
}
