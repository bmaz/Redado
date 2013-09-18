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

use Redado\CoreBundle\Model\GroupModuleInterface;

/**
 * Membership class
 * @internal
 * @ignore (because interal keyword does not work)
 */
class Membership implements GroupModuleInterface
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $modified;

    /**
     * @var \Redado\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Redado\CoreBundle\Entity\Group
     */
    private $group;

    /**
     * @var bool
     */
    private $direct;

    public function __construct(
        \Redado\CoreBundle\Entity\Group $group,
        \Redado\CoreBundle\Entity\User $user,
        $direct
    )
    {
        $this->user = $user;
        $this->group = $group;
        $this->direct = $direct;
    }


    /**
     * Get id
     *
     * @return integer
     * @internal
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
     * Get user
     *
     * @return \Redado\CoreBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get group
     *
     * @return \Redado\CoreBundle\Entity\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set direct
     *
     * @return \Redado\CoreBundle\Entity\Group
     */
    public function setDirect($direct)
    {
        $this->direct = $direct;

        return $this;
    }

    /**
     * Get direct
     *
     * @return bool
     */
    public function getDirect()
    {
        return $this->direct;
    }

    public function getModuleName()
    {
        return 'redado_membership';
    }

    public function getPermissionList()
    {
        return array(
            array(
                'name' => 'view_created',
                'description' => 'View creation date'
            )
        );
    }
}
