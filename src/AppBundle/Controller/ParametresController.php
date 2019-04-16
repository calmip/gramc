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

use AppBundle\Entity\Parametres;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Parametre controller.
 *
 * @Security("has_role('ROLE_ADMIN')") 
 * @Route("parametres")
 */
class ParametresController extends Controller
{
    /**
     * Lists all parametre entities.
     *
     * @Route("/", name="parametres_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $parametres = $em->getRepository('AppBundle:Parametres')->findAll();

        return $this->render('parametres/index.html.twig', array(
            'parametres' => $parametres,
        ));
    }

    /**
     * Creates a new parametre entity.
     *
     * @Route("/new", name="parametres_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $parametre = new Parametre();
        $form = $this->createForm('AppBundle\Form\ParametresType', $parametre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($parametre);
            $em->flush($parametre);

            return $this->redirectToRoute('parametres_show', array('id' => $parametre->getId()));
        }

        return $this->render('parametres/new.html.twig', array(
            'parametre' => $parametre,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a parametre entity.
     *
     * @Route("/{id}", name="parametres_show")
     * @Method("GET")
     */
    public function showAction(Parametres $parametre)
    {
        $deleteForm = $this->createDeleteForm($parametre);

        return $this->render('parametres/show.html.twig', array(
            'parametre' => $parametre,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing parametre entity.
     *
     * @Route("/{id}/edit", name="parametres_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Parametres $parametre)
    {
        $deleteForm = $this->createDeleteForm($parametre);
        $editForm = $this->createForm('AppBundle\Form\ParametresType', $parametre);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('parametres_edit', array('id' => $parametre->getId()));
        }

        return $this->render('parametres/edit.html.twig', array(
            'parametre' => $parametre,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a parametre entity.
     *
     * @Route("/{id}", name="parametres_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Parametres $parametre)
    {
        $form = $this->createDeleteForm($parametre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($parametre);
            $em->flush($parametre);
        }

        return $this->redirectToRoute('parametres_index');
    }

    /**
     * Creates a form to delete a parametre entity.
     *
     * @param Parametres $parametre The parametre entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Parametres $parametre)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('parametres_delete', array('id' => $parametre->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
