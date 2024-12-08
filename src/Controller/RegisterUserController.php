<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\AuthService;

class RegisterUserController extends AbstractController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    #[Route('/api/auth/register-user', name: 'register_user', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        // On récupère les données qui viennent du front
        $data = json_decode($request->getContent(), true);

        // On gère les erreurs
        if (!$data || !isset($data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'Les données ne sont pas valides'], 400);
        }

        // On appelle le service pour la logique métier
        $result = $this->authService->registerUser($data['email'], $data['password']);

        if ($result['success']) {
            return new JsonResponse(['message' => 'Vous êtes inscrit!'], 201);
        }

        return new JsonResponse(['error' => $result['message']], $result['status']);
    }
}