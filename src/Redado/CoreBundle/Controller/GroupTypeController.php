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

use Redado\CoreBundle\Entity\GroupType;
use Redado\CoreBundle\Form\GroupTypeType;

/**
 * GroupType controller.
 *
 */
class GroupTypeController extends Controller
{

    /**
     * Lists all GroupType entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('RedadoCoreBundle:GroupType')->findAll();

        return $this->render('RedadoCoreBundle:GroupType:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new GroupType entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity  = new GroupType();
        $form = $this->createForm(new GroupTypeType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('grouptype_show', array('id' => $entity->getId())));
        }

        return $this->render('RedadoCoreBundle:GroupType:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new GroupType entity.
     *
     */
    public function newAction()
    {
        $entity = new GroupType();
        $form   = $this->createForm(new GroupTypeType(), $entity);

        return $this->render('RedadoCoreBundle:GroupType:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a GroupType entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RedadoCoreBundle:GroupType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GroupType entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('RedadoCoreBundle:GroupType:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),        ));
    }

    /**
     * Displays a form to edit an existing GroupType entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RedadoCoreBundle:GroupType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GroupType entity.');
        }

        $editForm = $this->createForm(new GroupTypeType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('RedadoCoreBundle:GroupType:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing GroupType entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RedadoCoreBundle:GroupType')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GroupType entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new GroupTypeType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('grouptype_edit', array('id' => $id)));
        }

        return $this->render('RedadoCoreBundle:GroupType:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a GroupType entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('RedadoCoreBundle:GroupType')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find GroupType entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('grouptype'));
    }

    /**
     * Creates a form to delete a GroupType entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
