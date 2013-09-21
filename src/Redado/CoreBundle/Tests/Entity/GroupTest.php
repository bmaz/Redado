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
        $child = $group->createChild(array(end($users)));

        // create the child, is it in it ?
        $children = $group->getChildren();
        $this->assertContains($child, $children);

        // is end($user) our admin ?
        $admins = $child->getAdmins();
        $this->assertContains(end($users), $admins);

        //create a child of the child
        $new_user = new User();
        $child2 = $child->createChild(array($new_user));

        // is it in it ?
        $children2 = $child->getChildren();
        $this->assertContains($child2, $children2);

        // is new_user our admin ?
        $admins2 = $child2->getAdmins();
        $this->assertContains($new_user, $admins2);

        // can we get the admin groups ?
        $admin_group2 = $child2->getGrantedGroups('admin')[0];
        $admin_group = $child->getGrantedGroups('admin')[0];
        $this->assertNotEmpty($admin_group2);
        $this->assertNotEmpty($admin_group);

        // is end($users) granted for 'admin' on $child2 as $child2 is child of $child
        // and end($user) is admin of $child ?

        // is the admin group of the parent child of the admin group of the child ?
        $admin_groups = $admin_group2->getChildren();
        $this->assertNotEmpty($admin_groups);
        $this->assertEquals($admin_groups[0], $admin_group);

        // does the admin group of the child contains all the users of the admin group
        // of the child and the parent ?
        $inherited_admins = $admin_group2->getUsers();
        $this->assertNotEmpty($inherited_admins);
        $this->assertContains($new_user, $inherited_admins);
        $this->assertContains(end($users), $inherited_admins);
    }

}
