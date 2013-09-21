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

namespace Redado\CoreBundle\Test\Entity;

use Redado\CoreBundle\Entity\Group;
use Redado\CoreBundle\Entity\User;

class GroupTest extends\PHPUnit_Framework_TestCase
{
    public function testGetUsers()
    {
        $group = new Group();
        $users = $group->getUsers();
        $this->assertEmpty($users);
    }

    public function testAddUser()
    {
        $group = new Group();
        // add one user
        $user = new User();
        $group->addUser($user);
        $users = $group->getUsers();
        $this->assertContains($user, $users);

        // add multiple users
        $user2 = new User();
        $group->addUser($user2);
        $users = $group->getUsers();
        $this->assertContains($user2, $users);
        $this->assertContains($user, $users);

        return $group;
    }

    /**
     * @depends testAddUser
     */
    public function testRemoveUser(Group $group)
    {
        $user = new User();

        //add a user and remove it
        $group->addUser($user);
        $group->removeUser($user);
        $users = $group->getUsers();
        $this->assertNotContains($user, $users);

        return $group;
    }


    /**
     * @depends testRemoveUser
     */
    public function testCreateChild(Group $group)
    {
        $users = $group->getUsers();
        $this->assertNotEmpty($users);
        $child = $group->createChild(array(end($users)));

        $children = $group->getChildren();
        $this->assertContains($child, $children);
    }

}
