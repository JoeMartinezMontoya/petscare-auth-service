<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;


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
                'timeout' => 10,
            ]);

            #TODO: Manage those responses in users service

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

    #[Route('/api/auth/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['error' => 'Email et mot de passe requis'], 400);
        }

        $usersServiceUrl = $this->getParameter('users_service_base_url') . '/api/user-repo';
        $response = $this->httpClient->request('POST', $usersServiceUrl, [
            'json' => [
                'email' => $data['email'],
                'password' => $data['password'],
            ],
            'timeout' => 10,
        ]);

        if ($response->getStatusCode() !== 200) {
            return new JsonResponse(['error' => 'Identifiants incorrects'], 401);
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::file($this->getParameter('jwt_secret_key'))
        );

        $token = $config->builder()
            ->issuedBy('http://auth-service')   // Service émetteur
            ->permittedFor('http://frontend')  // Destinataire
            ->issuedAt(new \DateTimeImmutable()) // Date d'émission
            ->expiresAt((new \DateTimeImmutable())->modify('+1 day')) // Expiration
            ->withClaim('email', $data['email']) // Données personnalisées
            ->getToken($config->signer(), $config->signingKey());

        return new JsonResponse([
            'token' => $token->toString(),
        ], 200);
    }

    #[Route('/api/auth/validate', name: 'validate_token', methods: ['POST'])]
    public function validateToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['token'])) {
            return new JsonResponse(['error' => 'Token requis'], 400);
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::file($this->getParameter('jwt_public_key'))
        );

        try {
            $token = $config->parser()->parse($data['token']);
            $constraints = $config->validationConstraints();

            if (!$config->validator()->validate($token, ...$constraints)) {
                return new JsonResponse(['error' => 'Token invalide'], 401);
            }

            return new JsonResponse(['message' => 'Token valide'], 200);
        } catch (RequiredConstraintsViolated $e) {
            return new JsonResponse(['error' => 'Token invalide'], 401);
        }
    }

    #[Route('api/auth/debug', name: 'auth_debug', methods: ['GET'])]
    public function debug(): JsonResponse
    {
        $data = ['test' => 'test1'];
        return new JsonResponse($data);
    }
}
