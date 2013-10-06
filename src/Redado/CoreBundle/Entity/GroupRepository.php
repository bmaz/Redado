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

use Doctrine\ORM\EntityRepository;

class GroupRepository extends EntityRepository
{
    public function findLikeName($name)
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT g
                FROM RedadoCoreBundle:Group g
                WHERE g.name LIKE :name
                ')
            ->setParameter('name', '%'. $name . '%')
            ->setMaxResults(8)
            ->getResult();
    }

    public function findLikeSysname($sysname)
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT g
                FROM RedadoCoreBundle:Group g
                WHERE g.sysname LIKE :sysname
                ')
            ->setParameter('sysname', '%'. $sysname . '%')
            ->setMaxResults(8)
            ->getResult();
    }

    public function findNoLazy($id)
    {
        $q = $this
            ->createQueryBuilder('groups')
            ->select('groups', 'memberships', 'users', 'cclosures', 'pclosures', 'children', 'parents')
            ->leftJoin('groups.memberships', 'memberships')
            ->leftJoin('memberships.user', 'users')
            ->leftJoin('groups.closuresParents', 'pclosures')
            ->leftJoin('pclosures.parent', 'parents')
            ->leftJoin('groups.closuresChildren', 'cclosures')
            ->leftJoin('cclosures.child', 'children')
            ->where('groups.id = :id')
            ->setParameter('id', $id)
            ->getQuery();

        try {
            return $q->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function findByAdmin($user)
    {
        $q = $this
            ->createQueryBuilder('groups')
            ->select('groups')
            ->leftJoin('groups.permissions', 'permissions')
            ->leftJoin('permissions.subject', 'admin_groups')
            ->leftJoin('admin_groups.memberships', 'memberships')
            ->leftJoin('memberships.user', 'admin')
            ->where('admin.id = :admin_id AND permissions.name = :perm_name')
            ->setParameter('admin_id', $user->getId())
            ->setParameter('perm_name', 'admin')
            ->getQuery();

        try {
            return $q->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function findNoLazyOneBySysname($sysname)
    {
        $q = $this
            ->createQueryBuilder('groups')
            ->select('groups', 'memberships', 'users', 'cclosures', 'pclosures', 'children', 'parents', 'ccclosures')
            ->leftJoin('groups.memberships', 'memberships')
            ->leftJoin('memberships.user', 'users')
            ->leftJoin('groups.closuresParents', 'pclosures')
            ->leftJoin('pclosures.parent', 'parents')
            ->leftJoin('groups.closuresChildren', 'cclosures')
            ->leftJoin('cclosures.child', 'children')
            ->leftJoin('children.closuresChildren', 'ccclosures')
            ->where('groups.sysname = :sysname')
            ->setParameter('sysname', $sysname)
            ->getQuery();

        try {
            return $q->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }
}
