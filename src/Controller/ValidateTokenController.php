<?php
namespace App\Controller;

use App\Utils\HttpStatusCodes;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ValidateTokenController extends AbstractController
{
    private Validator $validator;
    private SignedWith $signedWith;

    public function __construct(Validator $validator, SignedWith $signedWith)
    {
        $this->validator  = $validator;
        $this->signedWith = $signedWith;
    }

    #[Route('/api/validate-token', name: 'validate_token', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $content     = json_decode($request->getContent(), true);
        $tokenString = $content['token'] ?? null;

        if (! $tokenString) {
            return new JsonResponse(['error' => 'Token missing.'], HttpStatusCodes::BAD_REQUEST);
        }

        try {
            $parser = new Parser(new JoseEncoder());
            $token  = $parser->parse($tokenString);

            if (! $this->validator->validate($token, $this->signedWith)) {
                return new JsonResponse(['error' => 'Invalid token.'], HttpStatusCodes::UNAUTHORIZED);
            }

            /** @var \Lcobucci\JWT\Token\Plain $token */
            $userEmail = $token->claims()->get('email', null);
            return new JsonResponse(['email' => $userEmail], HttpStatusCodes::SUCCESS);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error validating token.'], HttpStatusCodes::UNAUTHORIZED);
        }
    }
}
