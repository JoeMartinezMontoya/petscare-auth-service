<?php
namespace App\Service;

use App\Exception\ApiException;
use App\Utils\HttpStatusCodes;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JwtService
{
    private Validator $validator;
    private SignedWith $signedWith;
    private Configuration $config;

    public function __construct(ParameterBagInterface $params, Validator $validator, SignedWith $signedWith)
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::file($params->get('jwt_secret_key'))
        );
        $this->validator  = $validator;
        $this->signedWith = $signedWith;
    }

    public function generateToken(string $email): string
    {
        return $this->config->builder()
            ->issuedBy('http://auth-service')
            ->permittedFor('http://frontend')
            ->issuedAt(new \DateTimeImmutable())
            ->expiresAt((new \DateTimeImmutable())->modify('+1 day'))
            ->withClaim('email', $email)
            ->getToken($this->config->signer(), $this->config->signingKey())
            ->toString();
    }

    public function validateToken(string $token): mixed
    {
        $parser      = new Parser(new JoseEncoder());
        $tokenString = $parser->parse($token);

        if (! $this->validator->validate($tokenString, $this->signedWith)) {
            throw new ApiException(
                "Invalid Token",
                "Invalid bearer token",
                "Invalid bearer token",
                HttpStatusCodes::UNAUTHORIZED
            );
        }

        /** @var \Lcobucci\JWT\Token\Plain $tokenString */
        $email = $tokenString->claims()->get('email', null);
        return $email;
    }
}
