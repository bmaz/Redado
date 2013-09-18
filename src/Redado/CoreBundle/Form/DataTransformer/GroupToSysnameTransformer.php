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
use Redado\CoreBundle\Entity\Group;

class GroupToSysnameTransformer implements DataTransformerInterface
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @param ObjectManager $om
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * Transform an object Group to a string (name).
     *
     * @param Group|null $group
     * @return string
     */
    public function transform($group)
    {
        if (null === $group) {
            return "";
        }

        return $group->getSysname();
    }

    /**
     * Transform a string (name) to an object (Group).
     *
     * @param string $number
     * @return Group|null
     * @throws TransformationFailedException if not found.
     */
    public function reverseTransform($name)
    {
        if(!$name) {
            return null;
        }

        $group = $this->om
            ->getRepository('RedadoCoreBundle:Group')
            ->findOneBySysname($name);

        if (null === $group) {
            throw new TransformationFailedException();
        }

        return $group;
    }
}
