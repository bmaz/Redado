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

        $group_root = $em->getRepository('RedadoCoreBundle:Group')->findBySysname('root');

        if($group_root) {
            $installed = true;
        } else {
            $installed = false;
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

        if(!$installed) {
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


        if(!$installed && $created_user) {
            $group_root = new Group();
            $group_root
                ->setName('Root')
                ->setSysname('root')
                ->setDescription('root');
            $em->persist($group_root);

            $created_group_root = true;
        } else {
            $created_group_root = false;
        }



        if(!$installed && $created_user) {
            $group_global = $group_root->createChild(array($new_user), false, 'Global', 'global');
            $group_global->addUser($new_user);
            $em->persist($group_global);

            $created_group_global = true;
        } else {
            $created_group_global = false;
        }

        if ($created_group_root && $created_group_global && $created_user) {
            $installed = true;
        }

        $em->flush();

        return $this->render('RedadoCoreBundle:Main:install.html.twig', array(
            'form'   => $form->createView(),
            'created_user' => $created_user,
            'installed' => $installed,
            'user' => $new_user
        ));
    }
}
