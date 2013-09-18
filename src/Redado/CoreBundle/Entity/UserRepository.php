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
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserRepository extends EntityRepository implements UserProviderInterface
{
    public function loadUserByUsername($email)
    {
        $q = $this
            ->createQueryBuilder('users')
            ->select('users, memberships, groups')
            ->leftJoin('users.memberships', 'memberships')
            ->leftJoin('memberships.group', 'groups')
            ->where('users.email = :email')
            ->setParameter('email', $email)
            ->getQuery();

        try {
            $user =$q->getSingleResult();
        } catch (NoResultException $e) {
            throw new UsernameNotFoundException();
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->findNoLazy($user->getId());
    }

    public function supportsClass($class)
    {
        return $this->getEntityName() === $class
            || is_subclass_of($class, $this->getEntityName());
    }


    public function findLikeEmail($email)
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT user
                FROM RedadoCoreBundle:User user
                WHERE user.email LIKE :email
                ')
            ->setParameter('email', '%'. $email . '%')
            ->setMaxResults(8)
            ->getResult();
    }

    public function findLikeLastName($last_name)
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT user
                FROM RedadoCoreBundle:User user
                WHERE user.last_name LIKE :last_name
                ')
            ->setParameter('last_name', '%'. $last_name . '%')
            ->setMaxResults(8)
            ->getResult();
    }

    public function findLikeFirstName($first_name)
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT user
                FROM RedadoCoreBundle:User user
                WHERE user.first_name LIKE :first_name
                ')
            ->setParameter('first_name', '%'. $first_name . '%')
            ->setMaxResults(8)
            ->getResult();
    }

    public function findNoLazy($id)
    {
        $q = $this
            ->createQueryBuilder('users')
            ->select('users, memberships, groups')
            ->leftJoin('users.memberships', 'memberships')
            ->leftJoin('memberships.group', 'groups')
            ->where('users.id = :id')
            ->setParameter('id', $id)
            ->getQuery();

        try {
            return $q->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }
}
