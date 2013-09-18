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

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\Util\SecureRandom;
use Doctrine\ORM\Mapping as ORM;

/**
 * User
 */
class User implements AdvancedUserInterface, \Serializable
{
    /**
     * @var integer
     * @internal
     */
    protected $id;

    /**
     * @var string
     * @internal
     */
    private $email;

    /**
     * @var string
     * @internal
     */
    private $first_name;

    /**
     * @var string
     * @internal
     */
    private $last_name;

    /**
     * @var string
     * @internal
     */
    private $password;

    /**
     * @var string
     * @internal
     */
    private $salt;

    /**
     * @var boolean
     * @internal
     */
    private $enabled;

    /**
     * @var string
     * @internal
     */
    private $token;

    /**
     * @var \DateTime
     * @internal
     */
    private $created;

    /**
     * @var \DateTime
     * @internal
     */
    private $modified;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @internal
     */
    protected $memberships;

    /**
     * Constructor
     *
     * Do the initialization job and create a new salt.
     */
    public function __construct()
    {
        $this->memberships = new \Doctrine\Common\Collections\ArrayCollection();

	    $generator = new SecureRandom('/dev/urandom');
	    $this->salt = md5($generator->nextBytes(10));
        $this->enabled = false;
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
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function getName()
    {
        if(isset($this->first_name) && isset($this->last_name)) {
            return $this->getFirstName() . ' ' . $this->getLastName();
        } else {
            return $this->getEmail();
        }
    }

    /**
     * Set password
     *
     * This function set the password for the user. It should be given encrypted,
     * as the entity cannot do the job by itself.
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * It is return encrypted.
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Get salt
     *
     * @return string 
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Get created
     *
     * Return the date and time when the User was created.
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
     * Return the date and time when any property of the user was created in the datebase.
     *
     * @return \DateTime 
     */
    public function getModified()
    {
        return $this->modified;
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Add user to a group
     *
     * Internaly it creates a Membership object.
     * Calling $user->addGroup($group) is equivalent to
     * $group->addUser($user).
     *
     * @param \Redado\CoreBundle\Entity\Group $group
     * @return User
     */
    public function addGroup(\Redado\CoreBundle\Entity\Group $group)
    {
        $group->addUser($this);

        return $this;
    }

    /**
     * Remove a user from a group.
     *
     * Calling $user->removeGroup($group) is equivalent to
     * $group->removeUser($user)
     *
     * @param \Guilr\CoreBundle\Entity\Basegroup $group
     * @return User
     */
    public function removeGroup(\Redado\CoreBundle\Entity\Group $group)
    {
        $group->removeUser($this);

        return $this;
    }


    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getGroups()
    {
        $groups = new \Doctrine\Common\Collections\ArrayCollection();

        foreach($this->memberships as $membership) {
            $groups[] = $membership->getGroup();
        }

        return $groups;
    }


    /**
     * Add memberships
     *
     * @param \Redado\CoreBundle\Entity\Membership $memberships
     * @return User
     */
    public function addMembership(\Redado\CoreBundle\Entity\Membership $memberships)
    {
        $this->memberships[] = $memberships;

        return $this;
    }

    /**
     * Remove memberships
     *
     * @param \Redado\CoreBundle\Entity\Membership $memberships
     */
    public function removeMembership(\Redado\CoreBundle\Entity\Membership $memberships)
    {
        $this->memberships->removeElement($memberships);
    }

    /**
     * Get memberships
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMemberships()
    {
        return $this->memberships;
    }

     /**
     * Get username, here it is email.
     *
     * This function is only here for compatibility
     * with Symfony, and UserInterface implementation.
     *
     * @return string
     */
    public function getUsername() { return $this->getEmail(); }

    /**
     * Return the email.
     *
     * @return string
     */
    public function __toString() { return $this->getName(); }

    /**
     * UserInterface implementation.
     * @internal
     */
    public function eraseCredentials() {}

    /**
     * UserInterface implementation.
     * @internal
     */
    public function getRoles()
    {
        $groups = $this->getGroups();

        $roles = array('ROLE_USER');

        foreach($groups as $group) {
            if ($group->getName() == 'Administrator')
                $roles[] = 'ROLE_ADMIN';

            $roles[] = $group->getRole();
        }

        return $roles;
    }

    /**
     * @internal
     */
    public function serialize() {
        return \json_encode(
            array($this->id)
        );
    }

    /**
     * @internal
     */
    public function unserialize($serialized) {
        list (
            $this->id
        ) = \json_decode($serialized);
    }

    /**
     * Set first_name
     *
     * @param string $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->first_name = $firstName;

        return $this;
    }

    /**
     * Get first_name
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set last_name
     *
     * @param string $lastName
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->last_name = $lastName;

        return $this;
    }

    /**
     * Get last_name
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Get token for activation or new password
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * set token for activation or new password. Should be provided encrypted.
     * @param string $token The token
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }
}
