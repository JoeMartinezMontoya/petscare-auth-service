<?php

namespace App\Controller;

use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class LoginUserController extends AbstractController
{
    private AuthService $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    #[Route('/api/auth/login-user', name: 'login_user', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $response = $this->authService->loginUser($data['email'], $data['password']);

        if ($response['success']) {
            return new JsonResponse($response['token']->toString(), 200);
        }

        return new JsonResponse($response['message'], 409);
    }
}