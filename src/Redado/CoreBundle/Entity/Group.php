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

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\DependencyInjection\ContainerAware;
use Redado\CoreBundle\Model\GroupModuleInterface;

/**
 * Group
 */
class Group extends Role
{
    /**
     * @var integer $id
     * @internal
     */
    private $id;

    /**
     * @var string Unique sys name
     * @internal
     */
    private $sysname;

    /**
     * @var string Full name $name
     * @internal
     */
    private $name;

    /**
     * @var string Description
     * @internal
     */
    private $description;

    /**
     * @var \DateTime Auto-update by Gedmo.
     * @internal
     */
    private $created;

    /**
     * @var \DateTime Auto-updated by Gedmo.
     * @internal
     */
    private $modified;

    /**
     * @var \Doctrine\Common\Collections\Collection Array of memberships.
     * @internal
     */
    private $memberships;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @internal
     */
    private $closuresParents;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @internal
     */
    private $closuresChildren;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @internal
     */
    private $permissions;
    /**
     * @var \Redado\CoreBundle\Entity\GroupType
     * @internal
     */
    private $type;

    /**
     * Constructor
     */
    public function __construct($type)
    {
        $this->memberships = new \Doctrine\Common\Collections\ArrayCollection();
        $this->closuresParents = new \Doctrine\Common\Collections\ArrayCollection();
        $this->closuresChildren = new \Doctrine\Common\Collections\ArrayCollection();
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->type = $type;
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
     * Set name
     *
     * @param string $name
     * @return Group
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
     * Set sysname
     *
     * @param string $sysname
     * @return Group
     */
    public function setSysname($sysname)
    {
        $this->sysname = $sysname;

        return $this;
    }

    /**
     * Get sysname
     *
     * @return string
     */
    public function getSysname()
    {
        return $this->sysname;
    }
    /**
     * Set description
     *
     * @param string $description
     * @return Group
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * Add user to the group.
     *
     * Internaly it creates a Membership object.
     * Calling $group->addUser($user) is equivalent to
     * $user->addGroup($group).
     *
     * @param \Redado\CoreBundle\Entity\User $user
     * @param bool $direct
     * @return Group
     */
    public function addUser(\Redado\CoreBundle\Entity\User $user, $direct = true)
    {
        if (in_array($user, $this->getUsers()->toArray()) && $direct == true) {
            foreach($this->memberships as $membership) {
                if($membership->getUser() == $user) {
                    $membership->setDirect(true);
                }
            }
        } else if (!in_array($user, $this->getUsers()->toArray())) {
            $membership = new Membership($this, $user, $direct);
            $this->memberships[] = $membership;
            $user->addMembership($membership);
            foreach($this->getClosuresParents() as $closure_parent) {
                if($closure_parent->getInheritMembers()) {
                    $closure_parent->getParent()->addUser($user, false);
                }
            }
        }

        return $this;
    }

    /**
     * Remove a user from the group.
     *
     * Calling $group->removeUser($user) is equivalent to
     * $user->removeGroup($group).
     *
     * @param \Redado\CoreBundle\Entity\User $user
     * @return Group
     */
    public function removeUser(\Redado\CoreBundle\Entity\User $user)
    {
        foreach($this->memberships as $membership) {
            if($membership->getUser() == $user) {
                $this->memberships->removeElement($membership);
                $user->removeMembership($membership);

                foreach($this->getParents() as $parent) {
                    $parent->autoRemoveUser($user);
                }
            }
        }

        return $this;
    }


    /**
     * Autoremove user if is not member of child groups
     *
     * @param \Redado\CoreBundle\Entity\User $user
     * @return Group
     */
    public function autoRemoveUser(\Redado\CoreBundle\Entity\User $user)
    {
        foreach($this->memberships as $membership) {
            if($membership->getUser() == $user && $membership->getDirect() == false) {
                $users = array();

                foreach($this->getClosuresChildren() as $closure_child) {
                    if($closure_child->getInheritMembers()) {
                        $users = array_merge($users, $closure_child->getChild()->getUsers()->toArray());
                    }
                }

                if(!in_array($user, $users)) {
                    $this->memberships->removeElement($membership);
                    $user->removeMembership($membership);

                    foreach($this->getParents() as $parent) {
                        $parent->autoRemoveUser($user);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        $users = new \Doctrine\Common\Collections\ArrayCollection();

        foreach($this->memberships as $membership) {
            $users[] = $membership->getUser();
        }

        return $users;
    }


    /**
     * Get Memberships
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMemberships()
    {
        return $this->memberships;
    }

    /**
     * Add parent to the group.
     *
     * @param \Redado\CoreBundle\Entity\Group $parent
     * @return Group
     */
    public function addParent(\Redado\CoreBundle\Entity\Group $parent, $inherit_members = true)
    {
        if (!in_array($parent, $this->getParents()->toArray()) && $parent != $this) {
            $closure = new GroupClosure($parent, $this, $inherit_members);
            $this->closuresParents[] = $closure;

            if($inherit_members) {
                foreach($this->getUsers() as $user) {
                    $parent->addUser($user, false);
                }
            }
        }

        return $this;
    }
    /**
     * Remove a parent from the group.
     *
     * @param \Redado\CoreBundle\Entity\Group $parent
     * @return Group
     */
    public function removeParent(\Redado\CoreBundle\Entity\Group $parent)
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
     * Add child to the group.
     *
     * @param \Redado\CoreBundle\Entity\Group $child
     * @return Group
     */
    public function addChild(\Redado\CoreBundle\Entity\Group $child, $inherit_members = true)
    {
        $child->addParent($this, $inherit_members);

        return $this;
    }


    /**
     * Remove a child from the group.
     *
     * @param \Redado\CoreBundle\Entity\Group $child
     * @return Group
     */
    public function removeChild(\Redado\CoreBundle\Entity\Group $child)
    {
        $child->removeParent($this);

        return $this;
    }

    /**
     * Get parents
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

    public function getClosuresParents()
    {
        return $this->closuresParents;
    }

    public function getClosuresChildren()
    {
        return $this->closuresChildren;
    }

    /**
     * Convert the group to a Role identifier (string)
     * @return string
     */
    public function getRole()
    {
        return 'ROLE_GROUP_' . $this->id;
    }

    /**
     * Give a permission on this group to another group.
     *
     * @param Group $group The group to grant the permission.
     * @param string $name The name of the permission.
     */
    public function grantPermission(\Redado\CoreBundle\Entity\Group $group, $name)
    {
        if (!in_array($group, $this->getGrantedGroups($name))) {
            $permission = new Permission();
            $permission->setName($name);
            $permission->setObject($this);
            $permission->setSubject($group);
            $this->permissions[] = $permission;
        }

        return $this;
    }

    /**
     * Remove permission from a group.
     */
    public function removePermission(Group $group, $name)
    {
        foreach($this->permissions as $permission) {
            if ($permission->getName() === $name && $permission->getSubject() == $group) {
                $this->permissions->removeElement($permission);
            }
        }
    }

    /**
     */
    public function getGrantedGroups($name)
    {
        $return = array();

        foreach($this->permissions as $permission) {
            if ($permission->getName() == $name) {
                $return[] = $permission->getSubject();
            }
        }

        return $return;
    }

    public function getGroup()
    {
        return $this;
    }

    public function getAdminGroup()
    {
        foreach ($this->closuresChildren as $closures) {
            if ($closures->getChild()->getSysname() == $this->getSysname() + '-admin') {
                return $closures->getChild();
            }
        }
    }

    public function __toString() {
        return $this->sysname;
    }
}
