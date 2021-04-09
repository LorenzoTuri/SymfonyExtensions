<?php

namespace Lturi\SymfonyExtensions\Framework\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Lturi\SymfonyExtensions\Framework\EntityUtility\Trait\TimestampedEntity;
use Lturi\SymfonyExtensions\Framework\EntityUtility\Trait\UlidIdentifiedEntity;
use Lturi\SymfonyExtensions\Framework\Validators\SafeString;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class UserApiToken
{
    use UlidIdentifiedEntity;
    use TimestampedEntity;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userApiTokens")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(name="token", type="string")
     */
    protected $token;

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return UserApiToken
     */
    public function setUser(User $user): UserApiToken
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return UserApiToken
     */
    public function setToken(string $token): UserApiToken
    {
        $this->token = $token;
        return $this;
    }
}
