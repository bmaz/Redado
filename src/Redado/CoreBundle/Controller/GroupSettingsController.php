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
use Redado\CoreBundle\Entity\Group;
use Redado\CoreBundle\Entity\Membership;
use Redado\CoreBundle\Entity\User;
use Redado\CoreBundle\Security\GroupProtectionProxy;
use Redado\CoreBundle\Form\GroupType;
use Redado\CoreBundle\Form\AddUserToGroupType;
use Redado\CoreBundle\Form\Type\MultipleGroupSelectType;
use Redado\CoreBundle\Form\DataTransformer\GroupToSysnameTransformer;
use Redado\CoreBundle\Form\DataTransformer\GroupArrayToSysnameListTransformer;


class GroupSettingsController extends Controller
{
    public function indexAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('RedadoCoreBundle:Group')->findOneBySysname($id);
        if(!$group) {
            throw $this->createNotFoundException('User does not exist');
        }

        if(!$this->get('security.context')->isGranted('admin', $group)) {
            throw $this->createNotFoundException();
        }

        $editForm = $this->createForm(new GroupType(), $group, array('em' => $em));

        $editForm->handleRequest($request);

        if ($editForm->isValid()) {

            $em->persist($group);
            $em->flush();

            return $this->redirect($this->generateUrl('group_show', array('id' => $id)));
        }

        return $this->render('RedadoCoreBundle:GroupSettings:index.html.twig',
            array(
                'group' => $this->get('guilro.protection_proxy')->getProxy($group),
                'edit_form'   => $editForm->createView(),
            )
        );
    }

    public function permissionsAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $group = $em->getRepository('RedadoCoreBundle:Group')->findOneBySysname($id);

        if(!$group) {
            throw $this->createNotFoundException();
        }

        if(!$this->get('security.context')->isGranted('admin', $group)) {
            throw $this->createNotFoundException();
        }

        $transformer = new GroupArrayToSysnameListTransformer($em);

        $module_permissions = $this->get('redado.manager')->getGroupPermissionList($group);

        $builder = $this->createFormBuilder();

        foreach($module_permissions as $module_name => $permissions) {
            foreach ($permissions as $permission) {
                $builder
                    ->add($permission['name'] . '_self', 'checkbox', array('required' => false))
                    ->add(
                        $builder->create(
                            $permission['name'],
                            new MultipleGroupSelectType(),
                            array('label' => $permission['description'])
                        )->addViewTransformer($transformer)
                    );
                $groups = $group->getGrantedGroups($permission['name']);
                $groups = array_unique(array_merge($groups, $group->getGrantedGroups('all')));
                $granted_groups[$permission['name']] = $groups;
                if (in_array($group, $groups)) {
                    $granted_groups[$permission['name']] =
                        array_diff($granted_groups[$permission['name']], array($group));
                    $granted_groups[$permission['name'] . '_self'] = true;
                } else {
                    $granted_groups[$permission['name'] . '_self'] = false;
                }
            }
        }

        $builder->add('Submit', 'submit');

        $form = $builder->getForm();

        $form->setData($granted_groups);

        $form->handleRequest($request);

        if($form->isValid()) {
            $data = $form->getData();
            foreach($module_permissions as $module_name => $permissions) {
                foreach ($permissions as $permission) {
                    if(!is_null($data[$permission['name']])) {
                        foreach ($data[$permission['name']] as $granted_group) {
                            $group->grantPermission($granted_group, $permission['name']);
                        }
                    } else {
                        $data[$permission['name']] = array();
                    }
                    if($data[$permission['name'] . '_self']) {
                        $group->grantPermission($group, $permission['name']);
                    }
                    $revoked_groups = array_diff($granted_groups[$permission['name']], $data[$permission['name']]);
                    if($granted_groups[$permission['name'] . '_self']
                        && !$data[$permission['name'] . '_self']) {
                        $revoked_groups[] = $group;
                    }
                    foreach ($revoked_groups as $revoked_group) {
                        if (in_array($revoked_group, $group->getGrantedGroups('all'))) {
                            $group->removePermission($revoked_group, 'all');
                            foreach ($permissions as $permission_) {
                                $group->grantPermission($revoked_group, $permission_['name']);
                            }
                        }
                        $group->removePermission($revoked_group, $permission['name']);
                    }
                }
            }
            $em->flush();
        }

        return $this->render('RedadoCoreBundle:GroupSettings:permissions.html.twig',
            array(
                'group' => $this->get('guilro.protection_proxy')->getProxy($group),
                'form' => $form->createView(),
                'module_permissions' => $module_permissions
            )
        );
    }

    public function structureAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('RedadoCoreBundle:Group')->findNoLazyOneBySysname($id);

        if (!$group) {
            throw $this->createNotFoundException('Unable to find Group entity.');
        }

        if(!$this->get('security.context')->isGranted('admin', $group)) {
            throw $this->createNotFoundException();
        }

        $builder = $this->container->get('form.factory')->createNamedBuilder('form_child', 'form', array('id' => $id));
        $transformer = new GroupToSysnameTransformer($em);

        $form_child = $builder
            ->add(
                $builder->create(
                    'child',
                    new \Redado\CoreBundle\Form\Type\GroupTypeaheadType()
                )->addViewTransformer($transformer)
            )
            ->add('inherit_members', 'checkbox', array('required' => false))
            ->add('id', 'hidden')
            ->getForm();


        $builder = $this->container->get('form.factory')->createNamedBuilder('form_parent', 'form', array('id' => $id));

        $form_parent = $builder
            ->add(
                $builder->create(
                    'parent',
                    new \Redado\CoreBundle\Form\Type\GroupTypeaheadType()
                )->addViewTransformer($transformer)
            )
            ->add('inherit_members', 'checkbox', array('required' => false))
            ->add('id', 'hidden')
            ->getForm();

        $form_child->handleRequest($request);
        $form_parent->handleRequest($request);

        if($form_child->isValid()) {
            $data = $form_child->getData();
            $child = $data['child'];

            $group->addChild($child, $data['inherit_members']);

            $em->flush();
        }

        if($form_parent->isValid()) {
            $data = $form_parent->getData();
            $parent = $data['parent'];

            $group->addParent($parent, $data['inherit_members']);

            $em->flush();
        }

        return $this->render('RedadoCoreBundle:GroupSettings:structure.html.twig',
            array(
                'form_child' => $form_child->createView(),
                'form_parent' => $form_parent->createView(),
                'group' => $group,
            )
        );
    }

    public function usersAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('RedadoCoreBundle:Group')->findNoLazyOneBySysname($id);

        if (!$group || !$this->get('security.context')->isGranted('admin', $group)) {
            return $this->createNotFoundException();
        }

        return $this->render('RedadoCoreBundle:GroupSettings:users.html.twig',
            array(
                'group' => $group,
            )
        );
    }
}
