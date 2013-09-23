<?php
/*
 * This file is part of Redado.
 *
 * Copyright (C) 2013 Guillaume Royer
 *
 * Redado is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Redado is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Redado.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Redado\CoreBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Redado\CoreBundle\Entity\Group;

class MembershipVoter implements VoterInterface
{
    private $container;

    public function __construct($service_container)
    {
        $this->container = $service_container;
    }

    public function supportsAttribute($attribute)
    {
        return $attribute === 'view_membership';
    }

    public function supportsClass($class) {
        return is_a($class, 'Redado\CoreBundle\Entity\Membership', true);
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $result = VoterInterface::ACCESS_ABSTAIN;

        if (!$this->supportsClass(get_class($object))) {
            return $result;
        }

        foreach ($attributes as $attribute) {
            if ($attribute == '') {
                return VoterInterface::ACCESS_GRANTED;
            }
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $result = VoterInterface::ACCESS_DENIED;

            if ($this->container->get('security.context')->isGranted('view_group', $object->getGroup())
                && $this->container->get('security.context')->isGranted('view_user', $object->getUser())) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }
        return $result;
    }
}
