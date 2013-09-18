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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Redado\CoreBundle\Entity\User;

class AdminController extends Controller
{
	public function indexAction() {
        if ($this->get('security.context')->isGranted('ROLE_ADMIN') === false)
            throw new AccessDeniedException();

        $users_repo = $this->getDoctrine()->getManager()->getRepository('RedadoCoreBundle:User');
        $users = $users_repo->findAll();
        $user_q = count($users);


        $groups_repo = $this->getDoctrine()->getManager()->getRepository('RedadoCoreBundle:Group');
        $groups = $groups_repo->findAll();
        $group_q = count($groups);

        return $this->render('RedadoCoreBundle:Admin:index.html.twig', array(
            'user_q' => $user_q,
            'group_q' => $group_q
            )
        );

	}

    public function groupAction() {

    }


}
