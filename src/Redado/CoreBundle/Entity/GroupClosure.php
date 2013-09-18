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

namespace Redado\CoreBundle\Entity;

/**
 * GroupClosure
 * @internal
 * @ignore
 */
class GroupClosure
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $modified;

    /**
     * @var \Redado\CoreBundle\Entity\Group
     */
    private $parent;

    /**
     * @var \Redado\CoreBundle\Entity\Group
     */
    private $child;

    /**
     * @var boolean
     */
    private $inherit_members;

    /**
     * @var boolean
     */
    private $hard;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    public function __construct(
        \Redado\CoreBundle\Entity\Group $parent,
        \Redado\CoreBundle\Entity\Group $child,
        $inherit_members = true
    )
    {
        $this->parent = $parent;
        $this->child = $child;
        $this->inherit_members = $inherit_members;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get modified
     *
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }


    /**
     * Set inherit_members
     *
     * @param boolean $inherit_members
     * @return GroupClosure
     */
    public function setInheritMembers($inherit_members)
    {
        $this->inherit_members = $inherit_members;

        return $this;
    }

    /**
     * Get inherit_members
     *
     * @return boolean
     */
    public function getInheritMembers()
    {
        return $this->inherit_members;
    }

    /**
     * Get parent
     *
     * @return \Redado\CoreBundle\Entity\Group
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get child
     *
     * @return \Redado\CoreBundle\Entity\Group
     */
    public function getChild()
    {
        return $this->child;
    }

}
