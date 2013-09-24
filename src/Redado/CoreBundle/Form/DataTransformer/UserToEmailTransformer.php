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
namespace Redado\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\Common\Persistence\ObjectManager;
use Redado\CoreBundle\Entity\User;

class UserToEmailTransformer implements DataTransformerInterface
{
    private $om;

    public function __construct (ObjectManager $om)
    {
        $this->om = $om;
    }

    public function transform($user)
    {
        if (null === $user)
            return "";

        return $user->getEmail();
    }

    public function reverseTransform($email)
    {
        if (!$email)
            return null;

        $user = $this->om->getRepository('RedadoCoreBundle:User')->findOneBy(array('email' => $email));

        if (null === $user) {
            throw new TransformationFailedException(sprintf('No user with this email'));
        }

        return $user;
    }
}
