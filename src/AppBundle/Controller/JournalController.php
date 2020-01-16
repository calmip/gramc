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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use AppBundle\Entity\Journal;
use AppBundle\AppBundle;
use AppBundle\Form\SelectJournalType;
/**
 * Journal controller.
 *
 * @Route("journal")
 * @Security("has_role('ROLE_ADMIN')")
 */
class JournalController extends Controller
{
    /**
     * Lists all Journal entities.
     *
     * @Route("/list", name="journal_list")
     * @Method({"GET", "POST"})
     */
    public function listAction(Request $request)
    {
        $data = self::index($request);

        // journal/list.html.twig

        return $this->render('journal/list.html.twig',
        [
            'journals'  => $data['journals'],
            'form'      => $data['form']->createView(),
        ]);
    }

    /**
     * Lists all Journal entities.
     * CRUD
     *
     * @Route("/", name="journal_index")
     * @Method({"GET", "POST"})
     */

    public function indexAction(Request $request)
    {
        $data = self::index($request);


        return self::render('journal/index.html.twig',
        [
            'journals'  => $data['journals'],
            'form'      => $data['form']->createView(),
        ]);
    }

    /**
     * Creates a new journal entity.
     *
     * @Route("/new", name="journal_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $journal = new Journal();
        $form = $this->createForm('AppBundle\Form\JournalType', $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($journal);
            $em->flush($journal);

            return $this->redirectToRoute('journal_show', array('id' => $journal->getId()));
        }

        return $this->render('journal/new.html.twig', array(
            'journal' => $journal,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a journal entity.
     *
     * @Route("/{id}", name="journal_show")
     * @Method("GET")
     */
    public function showAction(Journal $journal)
    {
        $deleteForm = $this->createDeleteForm($journal);

        return $this->render('journal/show.html.twig', array(
            'journal' => $journal,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing journal entity.
     *
     * @Route("/{id}/edit", name="journal_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Journal $journal)
    {
        $deleteForm = $this->createDeleteForm($journal);
        $editForm = $this->createForm('AppBundle\Form\JournalType', $journal);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('journal_edit', array('id' => $journal->getId()));
        }

        return $this->render('journal/edit.html.twig', array(
            'journal' => $journal,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a journal entity.
     *
     * @Route("/{id}", name="journal_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Journal $journal)
    {
        $form = $this->createDeleteForm($journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($journal);
            $em->flush($journal);
        }

        return $this->redirectToRoute('journal_index');
    }

    /**
     * Creates a form to delete a journal entity.
     *
     * @param Journal $journal The journal entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Journal $journal)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('journal_delete', array('id' => $journal->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

     private static function index(Request $request)
    {

        // quand on n'a pas de class on doit définir un nom du formulaire pour HTML
        $form = AppBundle::getFormBuilder('jnl_requetes', SelectJournalType::class, [] )->getForm();
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() )
        {
            // on récupère un array avec des données du formulaire [ 'debut' => ... , 'fin' => ... , 'niveau' => .....]
            $data = $form->getData();
        }
        else
        {
            // des valeurs par défaut
            $data['dateDebut']  = new \DateTime();  // attention, cette valeur remplacée par la valeur dans Form/SelectJournalType
            $data['dateFin'] = new \DateTime();
            $data['dateFin']->add( \DateInterval::createFromDateString( '1 day' ) ); // attention, cette valeur remplacée par la valeur dans Form/SelectJournalType
            $data['niveau'] = Journal::INFO; // attention, cette valeur remplacée par la valeur dans Form/SelectJournalType
        }

        // on regarde si le bouton 'chercher tout' défini dans SelectJournalType a été utilisé
        if( $form->get('all')->isClicked() )
            $journals =  AppBundle::getRepository('AppBundle:Journal')->findAll();

        else
           $journals =  AppBundle::getRepository('AppBundle:Journal')->findData( $data['dateDebut'], $data['dateFin'],  $data['niveau'] );
           //  findData est défini dans JournalRepository - modèle

        return [ 'journals' => $journals, 'form' => $form ];
    }
}
