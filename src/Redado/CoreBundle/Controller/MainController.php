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

namespace Redado\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Redado\CoreBundle\Entity\User;
use Redado\CoreBundle\Entity\Group;
use Redado\CoreBundle\Entity\GroupType;
use Redado\CoreBundle\Form\UserType;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class MainController extends Controller
{
    public function indexAction()
    {
        $user = $this->get('guilro.protection_proxy')->getProxy($this->getUser());
		return $this->forward('RedadoCoreBundle:User:show', array('id' => $user->getId()));
	}

	public function loginAction()
	{
		$request = $this->getRequest();
		$session = $request->getSession();

        $em = $this->getDoctrine()->getManager();

        if(!$em->getRepository('RedadoCoreBundle:Group')->findOneBySysname('global')) {
            return $this->redirect($this->generateUrl('install'));
        }

		if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
			$error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
		} else {
			$error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
			$session->remove(SecurityContext::AUTHENTICATION_ERROR);
		}

		return $this->render(
			'RedadoCoreBundle:Login:login.html.twig',
			array(
				'last_username' => $session->get(SecurityContext::LAST_USERNAME),
		                'error'         => $error,
			)
		);
    }

    public function installAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $group_global = $em->getRepository('RedadoCoreBundle:Group')->findOneByName('Global');

        if($group_global) {

            $group_global_exist = true;

            $group_admin = $em->getRepository('RedadoCoreBundle:Group')->findOneByName('Administrator');

            if(in_array($group_admin, $group_global->getChildren()->toArray())) {

                $group_admin_exist = true;

                $users = $group_admin->getUsers();

                if($users)
                    $users_exist = true;
                else
                    $users_exist = false;

            } else {
                $group_admin_exist = false;
                $users_exist = false;
            }

        } else {
            $group_global_exist = false;
            $group_admin_exist = false;
            $users_exist = false;
        }

        $new_user  = new User();
        $form = $this->createFormbuilder($new_user, array(
            'validation_groups' => array('registration'))
        )
            ->add('first_name')
            ->add('last_name')
            ->add('email')
            ->add('password', 'repeated', array(
                'first_name' => 'password',
                'second_name' => 'confirm',
                'type' => 'password'
            ))
            ->getForm();

        if(!$users_exist) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $new_user = $form->getData();
                $password_clear = $new_user->getPassword();
                $password = $this->get('security.encoder_factory')
                             ->getEncoder($new_user)
                             ->encodePassword($password_clear, $new_user->getSalt());

                $new_user->setPassword($password);
                $new_user->setEnabled(true);

                $em = $this->getDoctrine()->getManager();
                $em->persist($new_user);
                $em->flush();

                $created_user = true;
            } else {
                $created_user = false;
            }
        } else {
            $created_user = false;
        }


        if(!$group_global_exist && $created_user) {
            $group_global = new Group(new GroupType());
            $group_global
                ->setName('Global')
                ->setSysname('global')
                ->setDescription('This is the global group, parent of all other groups. Do not delete it !');
            $em->persist($group_global);

            $created_group_global = true;
        } else {
            $created_group_global = false;
        }



        if(!$group_admin_exist && $created_user) {
            $group_admin = new Group(new GroupType());
            $group_admin
                ->setName('Administrator')
                ->setSysname('admin')
                ->setDescription("This is the main admin group. User in this group have all right, they are \"superuser\" and can do anything. Typically, there should be a very few users in this group, and their account should not be used like normal accounts.
To give permissions to people, you would better manage their rights carefully with different groups.");
            $em->persist($group_admin);

            $created_group_admin = true;
        } else {
            $created_group_admin = false;
        }

        if($created_user) {
            $group_admin->addUser($new_user);
            $group_admin->addParent($group_global);
            $group_admin->grantPermission($group_admin, 'all');
            $group_global->grantPermission($group_admin, 'all');
        }

        $em->flush();

        return $this->render('RedadoCoreBundle:Main:install.html.twig', array(
            'form'   => $form->createView(),
            'users_exist' => $users_exist,
            'created_user' => $created_user,
            'created_group_admin' => $created_group_admin,
            'created_group_global' => $created_group_global,
            'user' => $new_user
        ));
    }

    public function accountAction(Request $request)
    {
        $user = $this->getUser();

        $form = $this->createForm(new UserType(), $user);

        $form->handleRequest($request);

        if($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->get('session')->getFlashBag()->add('message', 'Profile updated');
        }


        $password_form = $this->createFormBuilder()
            ->add('old_password')
            ->add('password', 'repeated',
                array('first_name' =>'password',
                'second_name' => 'confirm',
                'type' => 'password'
            ))
            ->getForm();

        return $this->render('RedadoCoreBundle:Main:account.html.twig',
            array('form' => $form->createView())
        );
    }

    public function testAction(Request $request)
    {
        $acl_manager = $this->get('problematic.acl_manager');

        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('RedadoCoreBundle:Group')->find(4);
        $group_ = $em->getRepository('RedadoCoreBundle:Group')->find(3);

        return new \Symfony\Component\HttpFoundation\Response('COOL !');
    }
}
