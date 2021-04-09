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
class UserRole
{
    use UlidIdentifiedEntity;
    use TimestampedEntity;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userRoles")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(name="role", type="string")
     */
    protected $role;

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return UserRole
     */
    public function setUser(User $user): UserRole
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @param string $role
     * @return UserRole
     */
    public function setRole(string $role): UserRole
    {
        $this->role = $role;
        return $this;
    }
}
