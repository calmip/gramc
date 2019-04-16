<?php

/**
 * This file is part of GRAMC (Computing Ressource Granting Software)
 * GRAMC stands for : Gestion des Ressources et de leurs Attributions pour Mésocentre de Calcul
 *
 * GRAMC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 *  GRAMC is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with GRAMC.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  authors : Miloslav Grundmann - C.N.R.S. - UMS 3667 - CALMIP
 *            Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace AppBundle\Controller;

use AppBundle\Entity\Sso;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Sso controller.
 * 
 * @Security("has_role('ROLE_ADMIN')") 
 * @Route("sso")
 */
class SsoController extends Controller
{
    /**
     * Lists all sso entities.
     *
     * @Route("/", name="sso_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $ssos = $em->getRepository('AppBundle:Sso')->findAll();

        return $this->render('sso/index.html.twig', array(
            'ssos' => $ssos,
        ));
    }

    /**
     * Creates a new sso entity.
     *
     * @Route("/new", name="sso_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $sso = new Sso();
        $form = $this->createForm('AppBundle\Form\SsoType', $sso);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($sso);
            $em->flush($sso);

            return $this->redirectToRoute('sso_show', array('id' => $sso->getId()));
        }

        return $this->render('sso/new.html.twig', array(
            'sso' => $sso,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a sso entity.
     *
     * @Route("/{id}", name="sso_show")
     * @Method("GET")
     */
    public function showAction(Sso $sso)
    {
        $deleteForm = $this->createDeleteForm($sso);

        return $this->render('sso/show.html.twig', array(
            'sso' => $sso,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing sso entity.
     *
     * @Route("/{id}/edit", name="sso_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Sso $sso)
    {
        $deleteForm = $this->createDeleteForm($sso);
        $editForm = $this->createForm('AppBundle\Form\SsoType', $sso);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('sso_edit', array('id' => $sso->getId()));
        }

        return $this->render('sso/edit.html.twig', array(
            'sso' => $sso,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a sso entity.
     *
     * @Route("/{id}", name="sso_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Sso $sso)
    {
        $form = $this->createDeleteForm($sso);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($sso);
            $em->flush($sso);
        }

        return $this->redirectToRoute('sso_index');
    }

    /**
     * Creates a form to delete a sso entity.
     *
     * @param Sso $sso The sso entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Sso $sso)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('sso_delete', array('id' => $sso->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
