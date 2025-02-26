<?php
namespace App\Controller;

use App\Exception\ApiException;
use App\Service\JwtService;
use App\Utils\ApiResponse;
use App\Utils\HttpStatusCodes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ValidateTokenController extends AbstractController
{
    #[Route('/api/validate-token', name: 'validate_token', methods: ['POST'])]
    public function __invoke(Request $request, JwtService $jwtService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (! $data) {
            return ApiResponse::error([
                "title"   => "Token Missing",
                "detail"  => "Token missing",
                "message" => "No token received",
            ], HttpStatusCodes::BAD_REQUEST);
        }

        try {
            $response = $jwtService->validateToken($data['token']);
            return ApiResponse::success([
                "detail"  => "Validation successful",
                "message" => "Token valid",
                "email"   => $response,
            ], HttpStatusCodes::SUCCESS);

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
                "detail"  => "An unexpected error occurred while checking the user's credentials",
                "message" => $e->getMessage(),
            ], HttpStatusCodes::SERVER_ERROR);
        }
    }
}
