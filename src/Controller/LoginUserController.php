<?php
namespace App\Controller;

use App\Service\AuthService;
use App\Utils\HttpStatusCodes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LoginUserController extends AbstractController
{
    #[Route('/api/auth/login-user', name: 'login_user', methods: ['POST'])]
    public function __invoke(Request $request, AuthService $authService): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $response = $authService->loginUser($data);
        return new JsonResponse(["token" => $response['token']], HttpStatusCodes::SUCCESS);
    }
}
