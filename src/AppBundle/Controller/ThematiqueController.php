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

use AppBundle\Entity\Thematique;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use AppBundle\AppBundle;
use AppBundle\Entity\Individu;
use Symfony\Component\HttpFoundation\Request;

/**
 * Thematique controller.
 *
 * @Security("has_role('ROLE_ADMIN')")
 * @Route("thematique")
 */
class ThematiqueController extends Controller
{
    /**
     * Lists all thematique entities.
     *
     * @Route("/", name="thematique_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $thematiques = $em->getRepository('AppBundle:Thematique')->findAll();

        return $this->render('thematique/index.html.twig', array(
            'thematiques' => $thematiques,
        ));
    }
    
    /**
     * @Route("/gerer",name="gerer_thematiques" )
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function gererAction()
        {
        $menu = [
                    ['ok' => true,'name' => 'ajouter_thematique' ,'lien' => 'Ajouter une thématique','commentaire'=> 'Ajouter une thématique']
                ];
       
        return $this->render( 'thematique/liste.html.twig',
            [
            'menu' => $menu,
            'thematiques' => AppBundle::getRepository('AppBundle:Thematique')->findBy( [],['libelleThematique' => 'ASC'])
            ]
            );
        }
    /**
     * Creates a new thematique entity.
     *
     * @Route("/new", name="thematique_new")
     * @Route("/ajouter", name="ajouter_thematique")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $thematique = new Thematique();
        $form = $this->createForm('AppBundle\Form\ThematiqueType', $thematique,
            [
            'ajouter' => true,
            'experts'   => AppBundle::getRepository(Individu::class)->findBy(['expert' => true ] ),
            ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($thematique);
            $em->flush($thematique);

            return $this->redirectToRoute('gerer_thematiques');
        }

        return $this->render('thematique/ajouter.html.twig',
            [
            'menu' => [ [
                        'ok' => true,
                        'name' => 'gerer_thematiques',
                        'lien' => 'Retour vers la liste des thématiques',
                        'commentaire'=> 'Retour vers la liste des thématiques'
                        ] ],
            'thematique' => $thematique,
            'edit_form' => $form->createView(),
            ]);
    }
    
    /**
     * Deletes a thematique entity.
     *
     * @Route("/{id}/supprimer", name="supprimer_thematique")
     * @Method("GET")
     */
    public function supprimerAction(Request $request, Thematique $thematique)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($thematique);
        $em->flush($thematique);
        return $this->redirectToRoute('gerer_thematiques');
    }
    
    /**
     * Displays a form to edit an existing laboratoire entity.
     *
     * @Route("/{id}/modify", name="modifier_thematique")
     * @Method({"GET", "POST"})
     */
    public function modifyAction(Request $request, Thematique $thematique)
    {
        $editForm = $this->createForm('AppBundle\Form\ThematiqueType', $thematique,
            [
            'modifier'  => true,
            'experts'   => AppBundle::getRepository(Individu::class)->findBy(['expert' => true ] ),
            ]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('gerer_thematiques');
        }

        return $this->render('thematique/modif.html.twig',
            [
            'menu' => [ [
                        'ok' => true,
                        'name' => 'gerer_thematiques',
                        'lien' => 'Retour vers la liste des thématiques',
                        'commentaire'=> 'Retour vers la liste des thématiques'
                        ] ],
            'thematique' => $thematique,
            'edit_form' => $editForm->createView(),
            ]);
    }
    /**
     * Finds and displays a thematique entity.
     *
     * @Route("/{id}", name="thematique_show")
     * @Method("GET")
     */
    public function showAction(Thematique $thematique)
    {
        $deleteForm = $this->createDeleteForm($thematique);

        return $this->render('thematique/show.html.twig', array(
            'thematique' => $thematique,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing thematique entity.
     *
     * @Route("/{id}/edit", name="thematique_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Thematique $thematique)
    {
        $deleteForm = $this->createDeleteForm($thematique);
        $editForm = $this->createForm('AppBundle\Form\ThematiqueType', $thematique);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('thematique_edit', array('id' => $thematique->getId()));
        }

        return $this->render('thematique/edit.html.twig', array(
            'thematique' => $thematique,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a thematique entity.
     *
     * @Route("/{id}", name="thematique_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Thematique $thematique)
    {
        $form = $this->createDeleteForm($thematique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($thematique);
            $em->flush($thematique);
        }

        return $this->redirectToRoute('thematique_index');
    }

    /**
     * Creates a form to delete a thematique entity.
     *
     * @param Thematique $thematique The thematique entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Thematique $thematique)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('thematique_delete', array('id' => $thematique->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
