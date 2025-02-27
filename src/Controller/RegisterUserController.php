<?php
namespace App\Controller;

use App\Exception\ApiException;
use App\Service\AuthService;
use App\Utils\ApiResponse;
use App\Utils\HttpStatusCodes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RegisterUserController extends AbstractController
{
    #[Route('/api/auth/register-user', name: 'register_user', methods: ['POST'])]
    public function __invoke(Request $request, AuthService $authService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (! $data) {
            return ApiResponse::error([
                "title"   => "Invalid JSON Payload",
                "detail"  => "The request body is not a valid JSON",
                "message" => "Invalid data provided",
            ], HttpStatusCodes::BAD_REQUEST);
        }

        try {
            $response = $authService->registerUser($data);
            return ApiResponse::success($response, $response['response-status']);
        } catch (\Exception $e) {
            if ($e instanceof ApiException) {
                return ApiResponse::error([
                    "title"   => $e->getTitle(),
                    "detail"  => $e->getDetail(),
                    "message" => $e->getMessage(),
                ], $e->getStatusCode());
            }

            return ApiResponse::error([
                "title"   => "Unexpected Error",
                "detail"  => "An unexpected error occurred while trying to register the user",
                "message" => $e->getMessage(),
            ], HttpStatusCodes::SERVER_ERROR);
        }
    }
}
