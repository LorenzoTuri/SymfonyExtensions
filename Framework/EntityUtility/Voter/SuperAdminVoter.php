<?php

namespace Lturi\SymfonyExtensions\Framework\EntityUtility\Voter;

use Lturi\SymfonyExtensions\Framework\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/*
 * Basing on decision strategy, we must perform different things...
 * If we're the only supporting Voter, we have the absolute choice.
 * Else if there is another voter, then we MUST pass the decision to him
 * and leave the full responsibility to the developer.
 * If there are other Voters we must return one of the following:
 * - affirmative: don't vote, access right is up to the developer
 * - consensus: express a Vote. See below.
 * - unanimous: don't vote, access right is up to the developer
 * - priority: express a Vote. See below.
 * In consensus case, the developer must provide voters, taking in consideration
 *      that our voter results in true only if SUPER_ADMIN or correct role.
 * In priority case, the developer must take in consideration that surpassing our
 *      voter priority results in his decision, else our decision.
 *
 * WARNING: we check only on Voters of Voter's subclasses. Voters only implementing
 * VoterInterface doesn't count to us.
 */
class SuperAdminVoter extends Voter implements VoterInterface{
    protected $voters;
    protected $strategy;

    /**
     * @param AccessDecisionManagerInterface $accessDecisionManager
     */
    public function __construct(
        AccessDecisionManagerInterface $accessDecisionManager
    ) {
        $this->voters = $accessDecisionManager->getVoters();
        $this->strategy = $accessDecisionManager->getStrategy();
    }

    protected function supports(string $attribute, $subject): bool
    {
        $supportingCount = array_reduce(
            iterator_to_array($this->voters),
            function($carry, VoterInterface $voter) use ($subject, $attribute) {
                if ((
                    $voter instanceof Voter ||
                    is_subclass_of($voter, Voter::class)
                )) {
                    if (
                        !$voter instanceof SuperAdminVoter &&
                        $voter->supports($attribute, $subject)
                    ) $carry++;
                    dump($voter->supports($attribute, $subject));
                }
                return $carry;
            },
            0
        );
        // Only voter? Decision is up to us!
        if ($supportingCount == 0) return true;

        switch ($this->strategy) {
            // Interact in vote process
            case AccessDecisionManager::STRATEGY_CONSENSUS:
            case AccessDecisionManager::STRATEGY_PRIORITY:
                return true;
            // Do not interact in vote process or access rights are not supported
            case AccessDecisionManager::STRATEGY_AFFIRMATIVE:
            case AccessDecisionManager::STRATEGY_UNANIMOUS:
            default:
                return false;
        }
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
       // the user must be logged in; if not, deny access
        if (!$user) { return false; }

        if (in_array(User::ROLE_SUPER_ADMIN, $user->getRoles())) return true;

        if (in_array($attribute, $user->getRoles()))
            return true;

        return false;
    }
}