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

use AppBundle\Entity\Param;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

use AppBundle\AppBundle;

/**
 * Param controller.
 * @Security("has_role('ROLE_ADMIN')")
 * @Route("param")
 */
class ParamController extends Controller
{
    /**
     * Lists all param entities.
     *
     * @Route("/", name="param_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $params = $em->getRepository('AppBundle:Param')->findAll();

        return $this->render('param/index.html.twig', array(
            'params' => $params,
        ));
    }

    /**
     * Creates a new param entity.
     *
     * @Route("/new", name="param_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $param = new Param();
        $form = $this->createForm('AppBundle\Form\ParamType', $param);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($param);
            $em->flush($param);

            return $this->redirectToRoute('param_show', array('id' => $param->getId()));
        }

        return $this->render('param/new.html.twig', array(
            'param' => $param,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a param entity.
     *
     * @Route("/{id}/show", name="param_show")
     * @Method("GET")
     */
    public function showAction(Param $param)
    {
        $deleteForm = $this->createDeleteForm($param);

        return $this->render('param/show.html.twig', array(
            'param' => $param,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing param entity.
     *
     * @Route("/{id}/edit", name="param_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Param $param)
    {
        $deleteForm = $this->createDeleteForm($param);
        $editForm = $this->createForm('AppBundle\Form\ParamType', $param);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('param_edit', array('id' => $param->getId()));
        }

        return $this->render('param/edit.html.twig', array(
            'param' => $param,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing param entity.
     *
     * @Route("/avancer", name="param_avancer")
     * @Method({"GET", "POST"})
     */
    public function avancerAction(Request $request)
    {

        
       $now =   AppBundle::getRepository(Param::class)->findOneBy(['cle' => 'now']);
       $em  =   AppBundle::getManager();
          
       if( $now == null )
            {
            $now = new Param();
            $now->setCle('now');
            //$em->persist( $now );
            }

        if( $now->getVal() == null)
            $date = null;
        else
            $date = new \DateTime( $now->getVal() );
             
         $editForm = AppBundle::createFormBuilder( [ 'date' => $date] )
        ->add('date',   DateType::class, [ 'label' => " " ] )
        ->add('submit', SubmitType::class, ['label' => 'Fixer la date'])
        ->add('supprimer', SubmitType::class, ['label' => "Fin de la modification de la date"])
        ->getForm();
    
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid())
            {
            $date = $editForm->getData()['date'];
          
         
                
            $now->setCle('now');
            $now->setVal( $date->format("Y-m-d") );
            $em->persist( $now );
            if( $editForm->get('supprimer')->isClicked() ) $em->remove( $now );
            
            $em->flush();
        }

        return $this->render('param/avancer.html.twig',
            [
            'edit_form' => $editForm->createView(),
            ]);
    }

    /**
     * Deletes a param entity.
     *
     * @Route("/{id}", name="param_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Param $param)
    {
        $form = $this->createDeleteForm($param);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($param);
            $em->flush($param);
        }

        return $this->redirectToRoute('param_index');
    }

    /**
     * Creates a form to delete a param entity.
     *
     * @param Param $param The param entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Param $param)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('param_delete', array('id' => $param->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
