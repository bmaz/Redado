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

namespace Redado\CoreBundle\Manager;

use Symfony\Component\Security\Core\Util\SecureRandom;

use Redado\CoreBundle\Entity\User;

class Manager {
    private $services;

    private $group_extensions;

    private $membership_extensions;


    public function __construct ($services)
    {
        $this->services = $services;
    }


    public function createUser($email, $group)
    {
        $user = new User();
        $user->setEmail($email);
        $user->addGroup($group);

        $errors = ($this->services['validator']->validate($user));


        if (count($errors) == 0) {

            $this->services['doctrine']->getManager()->persist($user);

            $this->services['doctrine']->getManager()->flush();

            return $user;
        } else {
            $this->services['doctrine']->getManager()->remove($user);

            foreach($errors as $error) {
                $this->services['session']->getFlashBag()->add('error', 'User not valid.' . $error);
            }
            return false;
        }

    }

    public function enableUser($id)
    {
        $em = $this->services['doctrine']->getManager();
        $user = $em->getRepository('RedadoCoreBundle:User')->find($id);

        if(!$user) {
            return;
        }

        if($user->isEnabled()) {
            return;
            //TODO real handling
        }

        $generator = new SecureRandom('/dev/urandom');
        $password_clear = "";

        for($i = 0; $i < 10; $i++) {
            $password_clear = $password_clear . chr(rand(33, 126)) ;
        }

        $password = $this->services['security.encoder_factory']
                             ->getEncoder($user)
                             ->encodePassword($password_clear, $user->getSalt());

        $user->setPassword($password);
        $user->setEnabled(true);

        $em->flush();

        $message = \Swift_Message::newInstance()
            ->setSubject('Activate your account on' . $this->services['redado.settings']->get('site_name'))
            ->setFrom($this->services['redado.settings']->get('email_adress'))
            ->setTo($user->getEmail())
            ->setBody($this->services['templating']->render(
                'RedadoCoreBundle:Main:mail.txt.twig',
                array(
                    'site_name' => $this->services['redado.settings']->get('site_name'),
                    'password' => $password_clear
                )
            ))
        ;

        if($this->services['mailer']->send($message)) {
            return true;
        } else {
            $this
                ->services['session']
                ->getFlashBag()
                ->add('error', 'Could not send mail to' . $user->getEmail())
            ;
            return false;
        }
    }

   public function getGroupPermissionList($group)
    {
        return array(
            'Group' => array(
                array(
                    'name' => 'view_group',
                    'description' => 'View that the group exists.'
                ),
                array(
                    'name' => 'get_users',
                    'description' => 'See users in the group.'
                ),
                array(
                    'name' => 'add_user',
                    'description' => 'Add users to the group.'
                ),
                array(
                    'name' => 'remove_user',
                    'description' => 'Remove users from the group.'
                ),
                array(
                    'name' => 'get_children',
                    'description' => 'See the sub-groups of the group.'
                ),
                array(
                    'name' => 'get_parents',
                    'description' => 'See the parent groups of the group.'
                ),
                array(
                    'name' => 'edit_network',
                    'description' => 'Add or remove parent and child groups to the group.'
                )
            )
        );
    }

    private function checkLastParent($parent, $child)
    {
        if (count($child->getParents()) == 1) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this
                    ->service['translator']
                    ->trans(
                        'Each group must have at least one parent. Group %parent% is the last parent of group %child%.',
                        array('%parent%' => $parent->getName(), '%child%' => $child->getName())
                    )
            );
            return false;
        } else {
            return true;
        }
    }
}
