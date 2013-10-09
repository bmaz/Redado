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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Redado\CoreBundle\Entity\User;
use Redado\CoreBundle\Form\UserType;

/**
 * User controller.
 *
 */
class UserController extends Controller
{
    /**
     * Finds and displays a User entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RedadoCoreBundle:User')->findNoLazy($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        if ($entity == $this->getUser()) {
            $admined_groups = $em->getRepository('RedadoCoreBundle:Group')->findByAdmin($entity);
        } else { $admined_groups = array(); }

        return $this->render('RedadoCoreBundle:User:show.html.twig', array(
            'user'      => $this->get('guilro.protection_proxy')->getProxy($entity),
            'admined_groups' => $admined_groups
        ));
    }

    /**
     * Edits an existing User entity.
     *
     */
    public function editAction(Request $request, $id, $group_id = null)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RedadoCoreBundle:User')->find($id);

        if (!$entity || !$this->get('security.context')->isGranted('edit', $entity)) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createForm(new UserType(), $entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            if ($group_id) {
                return $this->redirect($this->generateUrl('group_settings_users', array('id' => $group_id)));
            }
            return $this->redirect($this->generateUrl('user_show', array('id' => $id)));
        }

        return $this->render('RedadoCoreBundle:User:edit.html.twig', array(
            'user'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

    public function enableAction(Request $request, $id, $group_id = null)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('RedadoCoreBundle:User')->find($id);
        if (!$user ) {
            return $this->createNotFoundException();
        } elseif (!$this->get('security.context')->isGranted(array('enable'), $user)) {
            return $this->createAcessDeniedException();
        }
        $enableForm = $this->createFormBuilder()->getForm();
        $enableForm->handleRequest($request);
        if ($enableForm->isValid()) {
            $this->get('redado.manager')->enableUser($user);
            $em->flush();
        } else {
            foreach ($enableForm->getErrors() as $error) {
                $this->getRequest()->getSession()->getFlashBag()->add('error', $error);
            }
        }
        if ($group_id) {
            return $this->redirect($this->generateUrl('group_settings_users', array('id' => $group_id)));
        }
        return $this->redirect($this->generateUrl('user_show', array('id' => $id)));
    }

    /**
     * Deletes a User entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('RedadoCoreBundle:User')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find User entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('user_show', array('id' => $this->getUser()->getId())));
    }

    /**
     * Creates a form to delete a User entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->setAction($this->generateUrl('user_delete',
                array('id' => $id)
            ))

            ->getForm()
        ;
    }


}
