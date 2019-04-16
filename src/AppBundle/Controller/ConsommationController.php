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

use AppBundle\Entity\Consommation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


use Symfony\Component\HttpFoundation\Request;

/**
 * Consommation controller.
 *
 * @Route("consommation")
 * @Security("has_role('ROLE_ADMIN')") 
 */
class ConsommationController extends Controller 
{
    /**
     * Lists all consommation entities.
     *
     * @Route("/", name="consommation_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $consommations = $em->getRepository('AppBundle:Consommation')->findAll();

        return $this->render('consommation/index.html.twig', array(
            'consommations' => $consommations,
        ));
    }

    /**
     * Creates a new consommation entity.
     *
     * @Route("/new", name="consommation_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $consommation = new Consommation();
        $form = $this->createForm('AppBundle\Form\ConsommationType', $consommation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($consommation);
            $em->flush($consommation);

            return $this->redirectToRoute('consommation_show', array('id' => $consommation->getId()));
        }

        return $this->render('consommation/new.html.twig', array(
            'consommation' => $consommation,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a consommation entity.
     *
     * @Route("/{id}", name="consommation_show")
     * @Method("GET")
     */
    public function showAction(Consommation $consommation)
    {
        $deleteForm = $this->createDeleteForm($consommation);

        return $this->render('consommation/show.html.twig', array(
            'consommation' => $consommation,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing consommation entity.
     *
     * @Route("/{id}/edit", name="consommation_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Consommation $consommation)
    {
        $deleteForm = $this->createDeleteForm($consommation);
        $editForm = $this->createForm('AppBundle\Form\ConsommationType', $consommation);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('consommation_edit', array('id' => $consommation->getId()));
        }

        return $this->render('consommation/edit.html.twig', array(
            'consommation' => $consommation,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a consommation entity.
     *
     * @Route("/{id}", name="consommation_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Consommation $consommation)
    {
        $form = $this->createDeleteForm($consommation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($consommation);
            $em->flush($consommation);
        }

        return $this->redirectToRoute('consommation_index');
    }

    /**
     * Creates a form to delete a consommation entity.
     *
     * @param Consommation $consommation The consommation entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Consommation $consommation)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('consommation_delete', array('id' => $consommation->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
