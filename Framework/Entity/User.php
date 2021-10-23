<?php

namespace Lturi\SymfonyExtensions\Framework\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Lturi\SymfonyExtensions\Framework\EntityUtility\Traits\TimestampedEntity;
use Lturi\SymfonyExtensions\Framework\EntityUtility\Traits\UlidIdentifiedEntity;
use Lturi\SymfonyExtensions\Framework\Validators\SafeString;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\MappedSuperclass
 */
class User implements UserInterface
{
    const ROLE_USER = 'ROLE_USER';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    use UlidIdentifiedEntity;
    use TimestampedEntity;

    /**
     * @var string
     * @ORM\Column(name="username", type="string", unique=true)
     * @Assert\NotBlank()
     * @SafeString
     */
    protected $username;

    /**
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Length(max=4096)
     */
    protected $plainPassword;

    /**
     * @var string|null
     * @ORM\Column(name="password", type="string")
     */
    protected $password;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default":"0"}, nullable=false)
     */
    protected $isSuperuser;

    /**
     * @var Collection|UserRole[]
     * @ORM\OneToMany(targetEntity="UserRole", mappedBy="user", cascade={"persist", "remove"})
     */
    protected $userRoles;

    /**
     * @var Collection|UserApiToken[]
     * @ORM\OneToMany(targetEntity="UserApiToken", mappedBy="user", cascade={"persist", "remove"})
     */
    protected $userApiTokens;

    #[Pure]
    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
        $this->userApiTokens = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return User
     */
    public function setUsername(string $username): User
    {
        $this->username = $username;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $password): void
    {
        // forces the object to look "dirty" to Doctrine. Avoids
        // Doctrine *not* saving this entity, if only plainPassword changes
        $this->plainPassword = $password;
        $this->password = null;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return bool
     */
    public function isSuperuser(): bool
    {
        return $this->isSuperuser;
    }

    /**
     * @param bool $isSuperuser
     * @return User
     */
    public function setIsSuperuser(bool $isSuperuser): User
    {
        $this->isSuperuser = $isSuperuser;
        return $this;
    }

    /**
     * @return Collection|UserRole[]
     */
    public function getUserRoles(): array|Collection
    {
        return $this->userRoles;
    }

    public function addUserRoles(UserRole $userRole): User
    {
        if (!$this->userRoles->contains($userRole)) {
            $this->userRoles[] = $userRole;
            $userRole->setUser($this);
        }
        return $this;
    }

    /**
     * @return Collection|UserApiToken[]
     */
    public function getUserApiToken(): Collection|array
    {
        return $this->userApiTokens;
    }

    public function addUserApiToken(UserApiToken $userApiToken): User
    {
        if (!$this->userApiTokens->contains($userApiToken)) {
            $this->userApiTokens[] = $userApiToken;
            $userApiToken->setUser($this);
        }
        return $this;
    }






    /**
     * The bcrypt algorithm doesn't require a separate salt.
     * @return null
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        // Guarantee every user at least has ROLE_USER
        $roles = [self::ROLE_USER];
        foreach ($this->userRoles as $userRole) {
            $roles[] = $userRole->getRole();
        }
        // Guarantee the ROLE_SUPER_ADMIN only comes from the isSuperuser flag
        if ($this->isSuperuser) {
            $roles[] = self::ROLE_SUPER_ADMIN;
        } else {
            $roles = array_filter($roles, function($roleString) {
                return $roleString != self::ROLE_SUPER_ADMIN;
            });
        }
        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }
}
