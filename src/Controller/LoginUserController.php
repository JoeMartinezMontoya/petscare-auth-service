<?php

namespace App\Controller;

use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

        if (!$data || !isset($data['email'], $data['password'])) {
            return new JsonResponse([
                "source"  => "LoginUserController",
                "type"    => "https://example.com/probs/invalid-data",
                "title"   => "Données invalide",
                "status"  => Response::HTTP_BAD_REQUEST,
                "detail"  => "Une adresse mail et un mot de passe sont requis",
                "message" => "Invalid input data for registration.",
            ], Response::HTTP_BAD_REQUEST);
        }

        $response = $this->authService->loginUser($data);

        $data = [
            "source"  => "LoginUserController",
            "type"    => "https://example.com/probs/invalid-data",
            "title"   => $response['title'],
            "status"  => $response['status'],
            "detail"  => $response['detail'],
            "message" => $response['message'],
        ];

        if (Response::HTTP_OK === $response['status']) {
            $data['token'] = $response['token']->toString();
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }
}
