<?php
namespace App\Controller;

use App\Service\AuthService;
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

        try {
            $response = $authService->registerUser($data);
            return new JsonResponse(["message" => $response['message']], HttpStatusCodes::SUCCESS);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => $e->getMessage()], HttpStatusCodes::SERVER_ERROR);
        }
    }
}
