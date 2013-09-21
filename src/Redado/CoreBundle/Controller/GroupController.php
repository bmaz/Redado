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

/**
 * Group controller.
 *
 */
class GroupController extends Controller
{
    /**
     * Finds and displays a Group entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RedadoCoreBundle:Group')->findNoLazy($id);
        $group_protected = $this->get('guilro.protection_proxy')->getProxy($entity);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Group entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('RedadoCoreBundle:Group:show.html.twig', array(
            'group'      => $group_protected,
            'delete_form' => $deleteForm->createView()
            ));
    }

    /**
     * Displays a form to create a new Group entity.
     *
     */
    public function newAction(Request $request, $parent_id)
    {
        $em = $this->getDoctrine()->getManager();
        $parent = $em->getRepository('RedadoCoreBundle:Group')->find($parent_id);
        $group = $parent->createChild(array($this->getUser()));

        $builder = $this->createFormBuilder($group, array(
            'validation_groups' => array('new')
        ));

        $builder
            ->add('inherit_members', 'checkbox', array('required' => false, 'mapped' => false))
            ->add('name')
            ->add('sysname')
            ->add('description');


        $form = $builder->getForm();

        $form->handleRequest($request);
        $group->addParent($parent);

        if ($form->isValid()) {

            $group->addUser($this->getUser());

            $em->persist($group);

            $this->createAdminGroup($group, $this->getUser());

            $em->flush();

            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        return $this->render('RedadoCoreBundle:Group:new.html.twig', array(
            'group' => $group,
            'parent' => $parent,
            'form'   => $form->createView(),
        ));
    }

       /**
     * Displays a form to edit an existing Group entity.
     *
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $group = $em->getRepository('RedadoCoreBundle:Group')->find($id);

        if (!$group) {
            throw $this->createNotFoundException('Unable to find Group entity.');
        }

        $editForm = $this->createForm(new GroupType(), $group, array('em' => $em));
        $deleteForm = $this->createDeleteForm($id);

        $editForm->handleRequest($request);

        if ($editForm->isValid()) {

            $em->persist($group);
            $em->flush();

            return $this->redirect($this->generateUrl('group_show', array('id' => $id)));
        }

        return $this->render('RedadoCoreBundle:Group:edit.html.twig', array(
            'group'      => $group,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Group entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('RedadoCoreBundle:Group')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Group entity.');
            }

            foreach ($entity->getParents() as $parent) {
                foreach($entity-> getUsers() as $user) {
                    $parent->autoRemoveUser($user);
                }
            }


            $em->remove($entity);
            $em->flush();
        }

        return $this->forward('RedadoCoreBundle:Main:index');
    }

    public function addUserAction(Request $request, $id)
    {
		$em = $this->getDoctrine()->getManager();
	    $group = $em->getRepository('RedadoCoreBundle:Group')->find($id);

		if (!$group) {
			throw $this->createNotFoundException('Unable to find Group.');
		}

        $form = $this->createFormBuilder()
            ->add('user_email',
                new \Redado\CoreBundle\Form\Type\UserTypeaheadType()
                  )
            ->add('Save', 'submit')
            ->getForm();

		$form->handleRequest($request);

        $form_new_user = $this->createFormBUilder()
            ->add('email', 'email')
            ->add('activate', 'checkbox', array('required' => false))
            ->add('save', 'submit')
            ->getForm();

        $form_new_user->handleRequest($request);


		if ($form->isValid()) {
            $data = $form->getData();
            $user_email = $data['user_email'];

            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('RedadoCoreBundle:User')->findOneByEmail($user_email);


            $group->addUser($user);

			$em->flush();

			return $this->redirect($this->generateUrl('group_show', array('id' => $id)));
		}

        if ($form_new_user->isValid()) {
            $data = $form_new_user->getData();

            $user = $this->get('redado.manager')->createUser($data['email'],
                    $group);

            if ($data['activate']) {
                $this->get('redado.manager')->enableUser($user->getId());
            }

            if ($user) {
                return $this->redirect(
                    $this->generateUrl('user_show', array('id'=> $user->getId()))
                );
            }
        }

		return $this->render('RedadoCoreBundle:Group:addUser.html.twig', array(
            'form_registered'   => $form->createView(),
            'form_new' => $form_new_user->createView(),
			'group'  => $group,
	        ));
	}

    public function removeUserAction(Request $request, $id, $user_id)
    {
		$em = $this->getDoctrine()->getManager();
	    $group = $em->getRepository('RedadoCoreBundle:Group')->find($id);
        $user = $em->getRepository('RedadoCoreBundle:User')->find($user_id);

		if (!$group) {
			throw $this->createNotFoundException('Unable to find entity.');
		}

        $form = $this->createRemoveUserForm($id, $user_id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            if ($id == $data['id'] && $user_id == $data['user_id']);
                $group->removeUser($user);

            $em->flush();

            return $this->redirect($this->generateUrl('group_show', array('id' => $id)));
        }

        return $this->render('RedadoCoreBundle:Group:removeUser.html.twig',
            array(
                'user' => $user,
                'group' => $group,
                'form' => $form->createView()
            )
        );
    }

    public function editStructureAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('RedadoCoreBundle:Group')->findNoLazy($id);

        if (!$group) {
            throw $this->createNotFoundException('Unable to find Group entity.');
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

        return $this->render('RedadoCoreBundle:Group:editStructure.html.twig',
            array(
                'form_child' => $form_child->createView(),
                'form_parent' => $form_parent->createView(),
                'group' => $group,
            )
        );
    }

    public function removeParentAction(Request $request, $id, $parent_id)
    {
	    $em = $this->getDoctrine()->getManager();
	    $group = $em->getRepository('RedadoCoreBundle:Group')->find($id);
        $parent_group = $em->getRepository('RedadoCoreBundle:Group')->find($parent_id);

		if (!$group) {
			throw $this->createNotFoundException('Unable to find entity.');
		}

        if(!$this->checkLastParent($parent_group, $group)) {
            return $this->redirect(
                $this->generateUrl('group_editstructure', array('id' => $id,))
            );
        }

        $form = $this->createRemoveStructureForm($id, $parent_id, true);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            if ($id == $data['id'] && $parent_id == $data['parent_id']) {
                $group->removeParent($parent_group);
            }

            $em->flush();

            return $this->redirect($this->generateUrl('group_editstructure', array('id' => $id)));
        }

        return $this->render('RedadoCoreBundle:Group:removeParent.html.twig',
            array(
                'group' => $group,
                'parent_group' => $parent_group,
                'form' => $form->createView()
            )
        );

    }

    public function removeChildAction(Request $request, $id, $child_id)
    {
	    $em = $this->getDoctrine()->getManager();
	    $group = $em->getRepository('RedadoCoreBundle:Group')->find($id);
        $child_group = $em->getRepository('RedadoCoreBundle:Group')->find($child_id);

		if (!$group) {
			throw $this->createNotFoundException('Unable to find entity.');
		}

        $form = $this->createRemoveStructureForm($id, $child_id, false);
        $form->handleRequest($request);

        if(!$this->checkLastParent($group, $child_group)) {
            return $this->redirect(
                $this->generateUrl('group_editstructure', array('id' => $id,))
            );
        }

        if ($form->isValid()) {
            $data = $form->getData();

            if ($id == $data['id'] && $child_id == $data['child_id']);
                $group->removeChild($child_group);

            $em->flush();

            return $this->redirect($this->generateUrl('group_editstructure', array('id' => $id)));
        }

        return $this->render('RedadoCoreBundle:Group:removeChild.html.twig',
            array(
                'group' => $group,
                'child_group' => $child_group,
                'form' => $form->createView()
            )
        );
    }

    public function editPermissionAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $group = $em->getRepository('RedadoCoreBundle:Group')->find($id);

        if(!$group) {
            throw $this->createNotFoundException();
        }

        $transformer = new GroupArrayToSysnameListTransformer($em);

        $module_permissions = $this->get('redado.manager')->getGroupPermissionList($group);

        $builder = $this->createFormBuilder();

        foreach($module_permissions as $module_name => $permissions) {
            foreach ($permissions as $permission) {
                $builder->add(
                    $builder->create(
                        $permission['name'],
                        new MultipleGroupSelectType(),
                        array('label' => $permission['description'])
                    )->addViewTransformer($transformer)
                );
                $groups = $group->getGrantedGroups($permission['name']);
                $groups = array_unique(array_merge($groups, $group->getGrantedGroups('all')));
                $granted_groups[$permission['name']] = $groups;
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
                    $revoked_groups = array_diff($granted_groups[$permission['name']], $data[$permission['name']]);
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

        return $this->render('RedadoCoreBundle:Group:editPermissions.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }
   /**
     * Creates a form to delete a Group entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->setAction($this->generateUrl('group_delete',
                array('id' => $id)
            ))
            ->getForm()
        ;
    }

    private function createRemoveStructureForm($id, $structure_id, $parent)
    {
        if($parent)
            $structure = 'parent';
        else
            $structure = 'child';

        return $this->createFormBuilder(array('id' => $id, $structure . '_id' => $structure_id))
            ->add('id', 'hidden')
            ->add($structure . '_id', 'hidden')
            ->setAction($this->generateUrl('group_remove' . $structure,
                array('id' => $id, $structure . '_id' => $structure_id)
            ))
            ->getForm();
    }

    private function createAdminGroup(Group $group, User $user)
    {
        $em = $this->getDoctrine()->getManager();

        $sysname = $group->getSysname();

        $admin_group = new Group(new GroupType());
        $admin_group->setSysname($group->getSysname() . '-admin');
        $admin_group->setName($group->getName() . ' administrators');
        $admin_group->setDescription($group->getName() . ' administrators');
        $admin_group->addParent($group);
        $admin_group->addUser($user);

        $admin_group->grantPermission($admin_group, 'all');

        $em->persist($admin_group);

        $group->grantPermission($admin_group, 'all');

        $em->flush();
    }

    private function createRemoveUserForm($id, $user_id)
    {
        return $this->createFormBuilder(array('id' => $id, 'user_id' => $user_id))
            ->add('id', 'hidden')
            ->add('user_id', 'hidden')
            ->setAction($this->generateUrl('group_removeuser',
                array('id' => $id, 'user_id' => $user_id)
            ))
            ->getForm();
    }
}
