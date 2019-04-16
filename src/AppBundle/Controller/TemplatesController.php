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

use AppBundle\Entity\Templates;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Template controller.
 *
 * @Security("has_role('ROLE_ADMIN')")
 * @Route("templates")
 */
class TemplatesController extends Controller
{
    /**
     * Lists all template entities.
     *
     * @Route("/", name="templates_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $templates = $em->getRepository('AppBundle:Templates')->findAll();

        return $this->render('templates/index.html.twig', array(
            'templates' => $templates,
        ));
    }

    /**
     * Creates a new template entity.
     *
     * @Route("/new", name="templates_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $template = new Template();
        $form = $this->createForm('AppBundle\Form\TemplatesType', $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($template);
            $em->flush($template);

            return $this->redirectToRoute('templates_show', array('id' => $template->getId()));
        }

        return $this->render('templates/new.html.twig', array(
            'template' => $template,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a template entity.
     *
     * @Route("/{id}", name="templates_show")
     * @Method("GET")
     */
    public function showAction(Templates $template)
    {
        $deleteForm = $this->createDeleteForm($template);

        return $this->render('templates/show.html.twig', array(
            'template' => $template,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing template entity.
     *
     * @Route("/{id}/edit", name="templates_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Templates $template)
    {
        $deleteForm = $this->createDeleteForm($template);
        $editForm = $this->createForm('AppBundle\Form\TemplatesType', $template);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('templates_edit', array('id' => $template->getId()));
        }

        return $this->render('templates/edit.html.twig', array(
            'template' => $template,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a template entity.
     *
     * @Route("/{id}", name="templates_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Templates $template)
    {
        $form = $this->createDeleteForm($template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($template);
            $em->flush($template);
        }

        return $this->redirectToRoute('templates_index');
    }

    /**
     * Creates a form to delete a template entity.
     *
     * @param Templates $template The template entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Templates $template)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('templates_delete', array('id' => $template->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
