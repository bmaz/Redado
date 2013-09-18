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

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupType
 */
class GroupType
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $modified;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $closuresParents;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $closuresChildren;

   /**
     * Constructor
     */
    public function __construct()
    {
        $this->closuresParents = new \Doctrine\Common\Collections\ArrayCollection();
        $this->closuresChildren = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return GroupType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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


    public function __toString()
    {
        return $this->name;
    }


    /**
     * Add parent to the group type.
     *
     * @param \Redado\CoreBundle\Entity\GroupType $parent
     * @return GroupType
     */
    public function addParent(\Redado\CoreBundle\Entity\GroupType $parent)
    {
        $closure = new GroupTypeClosure($parent, $this);
        $this->closuresParents[] = $closure;

        return $this;
    }


    /**
     * Remove a parent from the group type.
     *
     * @param \Redado\CoreBundle\Entity\GroupType $parent
     * @return GroupType
     */
    public function removeParent(\Redado\CoreBundle\Entity\GroupType $parent)
    {
        foreach($this->closuresParents as $closure) {
            if($closure->getParent() == $parent) {
                $this->closuresParents->removeElement($closure);
            }
        }

        return $this;
    }

    /**
     * Get parents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParents()
    {
        $parents = new \Doctrine\Common\Collections\ArrayCollection();

        foreach($this->closuresParents as $closure) {
            $parents[] = $closure->getParent();
        }

        return $parents;
    }

    /**
     * Add child group type to the group type.
     *
     * @param \Redado\CoreBundle\Entity\GroupType $child
     * @return GroupType
     */
    public function addChild(\Redado\CoreBundle\Entity\GroupType $child)
    {
        $closure = new GroupTypeClosure($this, $child);
        $this->closuresChildren[] = $closure;

        return $this;
    }


    /**
     * Remove a child from the group.
     *
     * @param \Redado\CoreBundle\Entity\GroupType $child
     * @return GroupType
     */
    public function removeChild(\Redado\CoreBundle\Entity\GroupType $child)
    {
        foreach($this->closuresChildren as $closure) {
            if($closure->getChild() == $child) {
                $this->closuresChildren->removeElement($closure);
            }
        }

        return $this;
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        $children = new \Doctrine\Common\Collections\ArrayCollection();

        foreach($this->closuresChildren as $closure) {
            $children[] = $closure->getChild();
        }

        return $children;
    }



}
