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

use AppBundle\Entity\MetaThematique;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;

use AppBundle\AppBundle;
use AppBundle\Entity\Thematique;

/**
 * Metathematique controller.
 * 
 * @Security("has_role('ROLE_ADMIN')")
 * @Route("metathematique")
 */
class MetaThematiqueController extends Controller
{
    /**
     * Lists all metaThematique entities.
     *
     * @Route("/", name="metathematique_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $metaThematiques = $em->getRepository('AppBundle:MetaThematique')->findAll();

        return $this->render('metathematique/index.html.twig', array(
            'metaThematiques' => $metaThematiques,
        ));
    }
    
    /**
     * @Route("/gerer",name="gerer_metaThematiques" )
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function gererAction()
        {
        $menu = [
                    ['ok' => true,'name' => 'ajouter_metaThematique' ,'lien' => 'Ajouter une metathématique','commentaire'=> 'Ajouter une metathématique']
                ];
       
        return $this->render( 'metathematique/liste.html.twig',
            [
            'menu' => $menu,
            'metathematiques' => AppBundle::getRepository('AppBundle:MetaThematique')->findBy( [],['libelle' => 'ASC'])
            ]
            );
        }
        
    /**
     * Creates a new metaThematique entity.
     *
     * @Route("/new", name="metathematique_new")
     * @Route("/ajouter", name="ajouter_metaThematique")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $metaThematique = new Metathematique();
        $form = $this->createForm('AppBundle\Form\MetaThematiqueType', $metaThematique, ['ajouter'  => true,]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($metaThematique);
            $em->flush($metaThematique);

            return $this->redirectToRoute('gerer_metaThematiques');
        }

        return $this->render('metathematique/ajouter.html.twig',
            [
            'menu' => [ [
                        'ok' => true,
                        'name' => 'gerer_metaThematiques',
                        'lien' => 'Retour vers la liste des metathématiques',
                        'commentaire'=> 'Retour vers la liste des metathématiques'
                        ] ],
            'metaThematique' => $metaThematique,
            'edit_form' => $form->createView(),
            ]);
    }
    
    /**
     * Deletes a thematique entity.
     *
     * @Route("/{id}/supprimer", name="supprimer_metaThematique")
     * @Method("GET")
     */
    public function supprimerAction(Request $request, MetaThematique $thematique)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($thematique);
        $em->flush($thematique);
        return $this->redirectToRoute('gerer_metaThematiques');
    }
    
    /**
     * Displays a form to edit an existing laboratoire entity.
     *
     * @Route("/{id}/modify", name="modifier_metaThematique")
     * @Method({"GET", "POST"})
     */
    public function modifyAction(Request $request, MetaThematique $thematique)
    {
        $editForm = $this->createForm('AppBundle\Form\MetaThematiqueType', $thematique,
            [
            'modifier'  => true,
            ]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('gerer_metaThematiques');
        }

        return $this->render('metathematique/modif.html.twig',
            [
            'menu' => [ [
                        'ok' => true,
                        'name' => 'gerer_metaThematiques',
                        'lien' => 'Retour vers la liste des metathématiques',
                        'commentaire'=> 'Retour vers la liste des metathématiques'
                        ] ],
            'metathematique' => $thematique,
            'edit_form' => $editForm->createView(),
            ]);
    }
    /**
     * Finds and displays a metaThematique entity.
     *
     * @Route("/{id}", name="metathematique_show")
     * @Method("GET")
     */
    public function showAction(MetaThematique $metaThematique)
    {
        $deleteForm = $this->createDeleteForm($metaThematique);

        return $this->render('metathematique/show.html.twig', array(
            'metaThematique' => $metaThematique,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing metaThematique entity.
     *
     * @Route("/{id}/edit", name="metathematique_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, MetaThematique $metaThematique)
    {
        $deleteForm = $this->createDeleteForm($metaThematique);
        $editForm = $this->createForm('AppBundle\Form\MetaThematiqueType', $metaThematique);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('metathematique_edit', array('id' => $metaThematique->getId()));
        }

        return $this->render('metathematique/edit.html.twig', array(
            'metaThematique' => $metaThematique,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a metaThematique entity.
     *
     * @Route("/{id}", name="metathematique_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, MetaThematique $metaThematique)
    {
        $form = $this->createDeleteForm($metaThematique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($metaThematique);
            $em->flush($metaThematique);
        }

        return $this->redirectToRoute('metathematique_index');
    }

    /**
     * Creates a form to delete a metaThematique entity.
     *
     * @param MetaThematique $metaThematique The metaThematique entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(MetaThematique $metaThematique)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('metathematique_delete', array('id' => $metaThematique->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
