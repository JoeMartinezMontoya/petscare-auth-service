<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthController extends AbstractController
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('api/auth/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password']) || empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['error' => 'Un email et un mot de passe sont requis'], 400);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Adresse email invalide'], 400);
        }

        if (strlen($data['password']) < 8) {
            return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 8 caractères'], 400);
        }

        try {
            $usersServiceUrl = $this->getParameter('users_service_base_url') . '/api/users';
            $response = $this->httpClient->request('POST', $usersServiceUrl, [
                'json' => [
                    'email' => $data['email'],
                    'password' => $data['password'],
                ],
                'timeout' => 10, // Timeout de 10 secondes
            ]);

            if ($response->getStatusCode() === 201) {
                return new JsonResponse(['message' => 'Inscription effectuée'], 201);
            }

            if ($response->getStatusCode() === 409) {
                return new JsonResponse(['error' => 'Un utilisateur avec cet email existe déjà'], 409);
            }

            return new JsonResponse([
                'error' => 'L\'inscription a échoué',
                'details' => $response->toArray(false),
            ], $response->getStatusCode());

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Le service users n\'a pas pu être atteint',
                'details' => $e->getMessage(),
                'raw_content' => $request->getContent(),
                'decoded_data' => json_decode($request->getContent(), true)
            ], 500);
        }
    }

    #[Route('api/auth/debug', name: 'auth_debug', methods: ['GET'])]
    public function debug(): JsonResponse
    {
        $resolvedUrl = $this->getParameter('users_service_base_url');
        return new JsonResponse(['resolved_url' => $resolvedUrl]);
    }
}
