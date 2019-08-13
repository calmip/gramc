<?php

namespace AppBundle\Controller;

use AppBundle\Entity\CommentaireExpert;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Commentaireexpert controller.
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
}
