<?php

namespace App\Security\Voter;

use App\Entity\Driver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

use function PHPUnit\Framework\containsOnly;

final class FactureVoter extends Voter
{
    public const EDIT = 'FACTURE_EDIT';
    public const VIEW = 'FACTURE_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof  \App\Entity\Facture;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::EDIT:
                if($subject->getClient() === $user){
                    return true;
                }
            case self::VIEW:
                if($subject->getClient() === $user || $user instanceof Driver){
                    return true;
                }
        }

        return false;
    }
}
