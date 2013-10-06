<?php

namespace Redado\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends Controller
{
    public function searchGroupAction(Request $request)
    {
        $q = $request->get('term');

        $em = $this->getDoctrine()->getManager();

        $results = array();
        $results = array_merge(
            $results,
            $em->getRepository('RedadoCoreBundle:Group')->findLikeName($q)
        );
        $results = array_merge(
            $results,
            $em->getRepository('RedadoCoreBundle:Group')->findLikeSysname($q)
        );

        $results = array_unique($results, SORT_REGULAR);

        return $this->render('RedadoCoreBundle:Ajax:searchGroup.json.twig', array(
            'results' => $this->get('guilro.protection_proxy')->getProxies($results))
        );
    }

    public function searchUserAction(Request $request)
    {
        $q = $request->get('term');
        $q = explode(' ', $q);
        $results = array();

        $em = $this->getDoctrine()->getManager();

        foreach ($q as $q_word) {
            $results = array_merge(
                $results,
                $em->getRepository('RedadoCoreBundle:User')->findLikeFirstName($q_word)
            );
            $results = array_merge(
                $results,
                $em->getRepository('RedadoCoreBundle:User')->findLikeLastName($q_word)
            );
            $results = array_merge(
                $results,
                $em->getRepository('RedadoCoreBundle:User')->findLikeEmail($q_word)
            );
        }

        $results = array_unique($results, SORT_REGULAR);

        return $this->render('RedadoCoreBundle:Ajax:searchUser.json.twig',
            array(
                'results' => $this->get('protection_proxy')->getProxies($results)
            )
        );
    }

    public function getGroupChildrenAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $group = $em->getRepository('RedadoCoreBundle:Group')->findNoLazy($id);

        if(!$group || !$this->get('security.context')->isGranted('get_children', $group)) {
            throw $this->createNotFoundException();
        }

        return $this->render('RedadoCoreBundle:Ajax:getGroupChildren.html.twig',
            array(
                'group' => $this->get('guilro.protection_proxy')->getProxy($group)
            )
        );
    }

}
