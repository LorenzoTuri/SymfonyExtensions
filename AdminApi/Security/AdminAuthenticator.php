<?php

namespace Lturi\SymfonyExtensions\AdminApi\Security;

use Doctrine\ORM\EntityManagerInterface;
use Lturi\SymfonyExtensions\Framework\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;

class AdminAuthenticator extends AbstractLoginFormAuthenticator
{
    private $entityManager;
    private $passwordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }


    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    protected function getLoginUrl(Request $request): string
    {
        return "/entity-admin/login";
    }

    public function authenticate(Request $request): PassportInterface
    {
        $credentials = $this->getCredentials($request);
        $user = $this->getUser($credentials);

        if (!$user || !$this->passwordEncoder->isPasswordValid($user, $credentials["password"])) {
            // TODO: true exception, then catch it into controller
            die("nope");
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Email could not be found.');
        }
        return new Passport(
            new UserBadge($credentials['username']),
            new PasswordCredentials($credentials['password'])
        );
    }

    public function getUser($credentials) {
        return $this->entityManager->getRepository(User::class)->findOneBy(['username' => $credentials['username']]);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // TODO: Implement onAuthenticationSuccess() method.
    }
}