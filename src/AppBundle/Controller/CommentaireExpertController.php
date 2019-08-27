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
 *  authors : Thierry Jouve      - C.N.R.S. - UMS 3667 - CALMIP
 *            Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/


namespace AppBundle\Controller;

use AppBundle\Entity\CommentaireExpert;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use AppBundle\AppBundle;

/****
* Fichier généré automatiquement et modifié par E.Courcelle
*
*************/

/**
 * Commentaireexpert controller.
 *
 *
 */
class CommentaireExpertController extends Controller
{
    /**
     * Lists all commentaireExpert entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $commentaireExperts = $em->getRepository('AppBundle:CommentaireExpert')->findAll();

        return $this->render('commentaireexpert/index.html.twig', array(
            'commentaireExperts' => $commentaireExperts,
        ));
    }

    /**
     * Creates a new commentaireExpert entity.
     *
     */
    public function newAction(Request $request)
    {
        $commentaireExpert = new Commentaireexpert();
        $form = $this->createForm('AppBundle\Form\CommentaireExpertType', $commentaireExpert);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($commentaireExpert);
            $em->flush();

            return $this->redirectToRoute('commentaireexpert_show', array('id' => $commentaireExpert->getId()));
        }

        return $this->render('commentaireexpert/new.html.twig', array(
            'commentaireExpert' => $commentaireExpert,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a commentaireExpert entity.
     *
     */
    public function showAction(CommentaireExpert $commentaireExpert)
    {
        $deleteForm = $this->createDeleteForm($commentaireExpert);

        return $this->render('commentaireexpert/show.html.twig', array(
            'commentaireExpert' => $commentaireExpert,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing commentaireExpert entity.
     *
     * @Route("/{id}/edit", name="commentaireexpert_edit")

     */
    public function editAction(Request $request, CommentaireExpert $commentaireExpert)
    {
        $deleteForm = $this->createDeleteForm($commentaireExpert);
        $editForm = $this->createForm('AppBundle\Form\CommentaireExpertType', $commentaireExpert);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('commentaireexpert_edit', array('id' => $commentaireExpert->getId()));
        }

        return $this->render('commentaireexpert/edit.html.twig', array(
            'commentaireExpert' => $commentaireExpert,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a commentaireExpert entity.
     *
     */
    public function deleteAction(Request $request, CommentaireExpert $commentaireExpert)
    {
        $form = $this->createDeleteForm($commentaireExpert);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($commentaireExpert);
            $em->flush();
        }

        return $this->redirectToRoute('commentaireexpert_index');
    }

    /**
     * Creates a form to delete a commentaireExpert entity.
     *
     * @param CommentaireExpert $commentaireExpert The commentaireExpert entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(CommentaireExpert $commentaireExpert)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('commentaireexpert_delete', array('id' => $commentaireExpert->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
    * Modification ou Création d'un commentaire par l'utilisateur connecté
    *
    * Vérifie que le commentaire de l'année passée en paramètre et de la personne connectée
    * existe, et sinon le crée. Ensuite redirige vers le contrôleur de modification
    *
    * @Route("/{annee}/cree-ou-modif", name="cree_ou_modif")
    *
    * @Method({"GET", "POST"})
    **********/
    public function creeOuModifAction(Request $request, $annee)
    {
		$em = $this->getDoctrine()->getManager();
		$moi = AppBundle::getUser();
		$commentaireExpert = $em->getRepository('AppBundle:CommentaireExpert')->findOneBy( ['expert' => $moi, 'annee' => $annee ] );
		if ($commentaireExpert==null)
		{
			$commentaireExpert = new Commentaireexpert();
			$commentaireExpert->setAnnee($annee);
			$commentaireExpert->setExpert($moi);
			$commentaireExpert->setMajStamp(new \DateTime());
			$em->persist($commentaireExpert);
			$em->flush();
		}

		return $this->redirectToRoute('commentaireexpert_modify', array('id' => $commentaireExpert->getId()));
    }

    /**
    * Modification d'un commentaire par l'utilisateur connecté
    *
    * @Route("/{id}/modif", name="commentaireexpert_modify")
    * @Method({"GET", "POST"})
    **********/
    public function modifyAction(Request $request, CommentaireExpert $commentaireExpert)
    {
		$em = $this->getDoctrine()->getManager();
		$editForm = $this->createForm('AppBundle\Form\CommentaireExpertType', $commentaireExpert, ["only_comment" => true]);
		$editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
			$commentaireExpert->setMajStamp(new \DateTime());
            $em->flush();
            return $this->redirectToRoute('commentaireexpert_modify', array('id' => $commentaireExpert->getId()));
        }

		$menu = [];
        return $this->render('commentaireexpert/modify.html.twig', array(
        	'menu'              => $menu,
            'commentaireExpert' => $commentaireExpert,
            'edit_form'         => $editForm->createView(),
        ));
	}
}
