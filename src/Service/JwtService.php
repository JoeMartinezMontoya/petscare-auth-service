<?php
namespace App\Service;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JwtService
{
    private Configuration $config;

    public function __construct(ParameterBagInterface $parameterBagInterface)
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::file($parameterBagInterface->get('jwt_secret_key'))
        );
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
}
