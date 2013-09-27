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
    public function __construct() {
        $this->memberships = new \Doctrine\Common\Collections\ArrayCollection();
        $this->closuresParents = new \Doctrine\Common\Collections\ArrayCollection();
        $this->closuresChildren = new \Doctrine\Common\Collections\ArrayCollection();
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
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
        $admin_group = $this->getAdminGroup();
        $admin_group ? $admin_group->setSysname($sysname . '-admin') : null;

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
        if (in_array($user, $this->getUsers()) && $direct == true) {
            foreach($this->memberships as $membership) {
                if($membership->getUser() == $user) {
                    $membership->setDirect(true);
                }
            }
        } else if (!in_array($user, $this->getUsers())) {
            $parents = true;
            foreach($this->getClosuresParents() as $closure_parent) {
                if($closure_parent->getInheritMembers()) {
                    if (!$closure_parent->getParent()->addUser($user, false)) {
                        $parents = false;
                    }
                }
            }
            if ($parents) {
                $membership = new Membership($this, $user, $direct);
                $this->memberships[] = $membership;
                $user->addMembership($membership);
            } else {
                return false;
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
            if($membership->getUser() == $user && $membership->getDirect()) {
                $membership->setDirect(false);
                $this->autoRemoveUser($user);
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
                        $users = array_merge($users, $closure_child->getChild()->getUsers());
                    }
                }

                if(!in_array($user, $users)) {
                    $this->memberships->removeElement($membership);

                    $parents = true;
                    foreach($this->getParents() as $parent) {
                        if (!$parent->autoRemoveUser($user)) {
                            $parents = false;
                        }
                    }

                    $parents ? null : $this->memberships->addElement($membership);
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
        $users = array();

        $users = array_merge($this->getDirectUsers()->toArray(), $this->getIndirectUsers()->toArray());

        return $users;
    }

    //TODO optimize not to search everytime
    public function getDirectUsers()
    {
        $users = new \Doctrine\Common\Collections\ArrayCollection();

        foreach ($this->memberships as $membership) {
            $membership->getDirect() ? $users[] = $membership->getUser() : null;
        }

        return $users;
    }

    public function getIndirectUsers()
    {
        $users = new \Doctrine\Common\Collections\ArrayCollection();

        foreach ($this->memberships as $membership) {
            !$membership->getDirect() ? $users[] = $membership->getUser() : null ;
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
            $parent->closuresChildren[] = $closure;
            $parent_admin_groups = $parent->getGrantedGroups('admin');
            $admin_groups = $this->getGrantedGroups('admin');
            if (!empty($parent_admin_groups) && !empty($admin_groups)) {
                $admin_groups[0]->addChild($parent_admin_groups[0]);
            }

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
                $parent->closuresChildren->removeElement($closure);
                foreach ($parent->memberships as $membership) {
                    $parent->autoRemoveUser($membership->getUser());
                }
                $parent_admin_groups = $parent->getGrantedGroups('admin');
                $admin_groups = $this->getGrantedGroups('admin');
                if (!empty($parent_admin_groups) && !empty($admin_groups)) {
                    $admin_groups[0]->removeChild($parent_admin_groups[0]);
                }
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

    public function createChild(array $admins, $inherit_members = true, $name = null, $sysname = null)
    {
        $child = new Group();
        $child->setName($name);
        $child->grantPermission($child, 'view_group');
        $child->setSysname($sysname);
        $child->createAdminGroup($admins);
        $this->addChild($child, $inherit_members);
        return $child;
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
        $children = array();

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
        if  ($name === 'admin') {
            throw new \Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException('');
            return $this;
        }

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

    public function getAdmins()
    {
        $granted_groups = $this->getGrantedGroups('admin');
        return !empty($granted_groups) ? $granted_groups[0]->getDirectUsers() : null;
    }

    public function __toString() {
        return $this->sysname;
    }

    /**
     * Create an admin group for a group. You still have to persist it after.
     *
     * @param array $users An array of the users to add in the admin group.
     *
     * @return Group The admin group.
     */
    private function createAdminGroup(array $users)
    {
        $admin_group = new Group();
        $admin_group->grantPermission($admin_group, 'add_user');
        $admin_group->grantPermission($admin_group, 'remove_user');
        $admin_group->grantPermission($this, 'get_users');

        $permission = new Permission();
        $permission->setName('admin');
        $permission->setObject($this);
        $permission->setSubject($admin_group);
        $this->permissions[] = $permission;

        $admin_group->setName($this->getName() . ' - Administrators');
        $admin_group->setSysname($this->getSysname() . '-admin');
        foreach ($users as $user) {
            $admin_group->addUser($user);
        }

        return $this;
    }

    private function getAdminGroup()
    {
        foreach($this->permissions as $permission) {
            if ($permission->getName() == 'admin') {
                return $permission->getSubject();
            }
        }
        return false;
    }

}
