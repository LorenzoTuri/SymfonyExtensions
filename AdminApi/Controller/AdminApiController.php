<?php

namespace Lturi\SymfonyExtensions\AdminApi\Controller;

use Lturi\SymfonyExtensions\AdminApi\Security\AdminAuthenticator;
use Lturi\SymfonyExtensions\Framework\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Guard\Token\PreAuthenticationGuardToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AdminApiController extends AbstractController
{
    protected $container;
    protected $authenticator;
    protected $eventDispatcher;

    public function __construct(
        $container,
        AdminAuthenticator $authenticator,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->container = $container;
        $this->authenticator = $authenticator;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Login/Logout page. Also log-in/out the user, is not only template
     * @param Request $request
     * @param $action
     * @return Response
     */
    public function loginLogout(Request $request, $action): Response
    {
        $user = $this->getAdminUser();
        if ($user) return $this->redirectToRoute("lturi_symfonyextensions_adminapi_page");

        if ($action === "login") {
            // On login, either try logging the user or supply the login template
            if ($request->getMethod() === $request::METHOD_POST) {
                try {
                    $passport = $this->authenticator->authenticate($request);
                    if ($passport) {
                        // Try logging the user, then redirect again to login... the page takes care to redirect correctly
                        $credentials = $this->authenticator->getCredentials($request);
                        $token = new PreAuthenticationGuardToken($credentials, "main");
                        $this->get("security.token_storage")->setToken($token);
                        $loginEvent = new InteractiveLoginEvent($request, $token);
                        $this->eventDispatcher->dispatch($loginEvent, SecurityEvents::INTERACTIVE_LOGIN);
                        $user = $this->authenticator->getUser($credentials);
                        $token = new UsernamePasswordToken($user, $credentials, "main", $user->getRoles());
                        $this->get("security.token_storage")->setToken($token);
                    }
                    return $this->redirectToRoute("lturi_symfonyextensions_adminapi_page");
                } catch (\Exception $exception) {
                    return $this->redirectToRoute("lturi_symfonyextensions_adminapi_loginlogout", [
                        "action" => "login"
                    ]);
                }
            } else {
                return $this->render("@LturiSymfonyExtensions/admin-api/login.html.twig");
            }
        } else {
            // Otherwise logout the user and redirect again to login
            $this->get("security.token_storage")->setToken(null);
            return $this->redirectToRoute(
                "lturi_symfonyextensions_adminapi_loginlogout", [
                    "action" => "login"
                ]
            );
        }
    }

    public function page(Request $request): Response {
        $user = $this->getAdminUser();
        if (!$user) return $this->redirectToRoute("lturi_symfonyextensions_adminapi_loginlogout", [
            "action" => "login"
        ]);

        return $this->render(
            "@LturiSymfonyExtensions/admin-api/page.html.twig", [
                "user" => $user
            ]
        );
    }


    protected function getAdminUser(): User|null
    {
        $user = $this->getUser();
        if ($user && ($user instanceof User || is_subclass_of($user, User::class))) {
            return $user->isSuperuser() ? $user : null;
        }
        return null;
    }
}
