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

use AppBundle\Entity\CompteActivation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


use Symfony\Component\HttpFoundation\Request;

/**
 * Compteactivation controller.
 *
 * @Security("has_role('ROLE_ADMIN')")
 * @Route("compteactivation")
 */
class CompteActivationController extends Controller
{
    /**
     * Lists all compteActivation entities.
     *
     * @Route("/", name="compteactivation_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $compteActivations = $em->getRepository('AppBundle:CompteActivation')->findAll();

        return $this->render('compteactivation/index.html.twig', array(
            'compteActivations' => $compteActivations,
        ));
    }

    /**
     * Creates a new compteActivation entity.
     *
     * @Route("/new", name="compteactivation_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $compteActivation = new Compteactivation();
        $form = $this->createForm('AppBundle\Form\CompteActivationType', $compteActivation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($compteActivation);
            $em->flush($compteActivation);

            return $this->redirectToRoute('compteactivation_show', array('id' => $compteActivation->getId()));
        }

        return $this->render('compteactivation/new.html.twig', array(
            'compteActivation' => $compteActivation,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a compteActivation entity.
     *
     * @Route("/{id}", name="compteactivation_show")
     * @Method("GET")
     */
    public function showAction(CompteActivation $compteActivation)
    {
        $deleteForm = $this->createDeleteForm($compteActivation);

        return $this->render('compteactivation/show.html.twig', array(
            'compteActivation' => $compteActivation,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing compteActivation entity.
     *
     * @Route("/{id}/edit", name="compteactivation_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, CompteActivation $compteActivation)
    {
        $deleteForm = $this->createDeleteForm($compteActivation);
        $editForm = $this->createForm('AppBundle\Form\CompteActivationType', $compteActivation);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('compteactivation_edit', array('id' => $compteActivation->getId()));
        }

        return $this->render('compteactivation/edit.html.twig', array(
            'compteActivation' => $compteActivation,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a compteActivation entity.
     *
     * @Route("/{id}", name="compteactivation_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, CompteActivation $compteActivation)
    {
        $form = $this->createDeleteForm($compteActivation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($compteActivation);
            $em->flush($compteActivation);
        }

        return $this->redirectToRoute('compteactivation_index');
    }

    /**
     * Creates a form to delete a compteActivation entity.
     *
     * @param CompteActivation $compteActivation The compteActivation entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(CompteActivation $compteActivation)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('compteactivation_delete', array('id' => $compteActivation->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
