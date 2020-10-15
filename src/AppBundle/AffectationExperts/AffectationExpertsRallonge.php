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

namespace AppBundle\AffectationExperts;


use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Entity\CollaborateurVersion;
use AppBundle\AffectationExperts\AffectationExperts;
use AppBundle\Interfaces\Demande;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


use AppBundle\Form\ChoiceList\ExpertChoiceLoader;

use AppBundle\Entity\Thematique;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;



class AffectationExpertsRallonge extends AffectationExperts
{
	protected function getExpertForms(Demande $rallonge)
	{

		// Liste d'exclusion = Les collaborateurs + de la version
	    $exclus = AppBundle::getRepository(CollaborateurVersion::class)->getCollaborateurs($rallonge->getVersion()->getProjet());

		$expert = $rallonge->getExpert();
		if ($expert == null)
		{
			$expert = $rallonge->getVersion()->getExpert();
		}

		// Nom du formulaire
		$nom = 'expert'.$rallonge->getId();

	    $choice = new ExpertChoiceLoader($exclus);

		// Important de passer par un tableau pour garder la même valeur de retour pour tous les getExpertForms()
		// cf AffectationExperts::getExpertForms
		//
		$forms = [];
		$forms[] = $this->formFactory->createNamedBuilder($nom, FormType::class, null  ,  ['csrf_protection' => false ])
		                ->add('expert', ChoiceType::class,
		                    [
			                'multiple'  =>  false,
			                'required'  =>  false,
			                //'choices'       => $choices, // cela ne marche pas à cause d'un bogue de symfony
			                'choice_loader' => $choice, // nécessaire pour contourner le bogue de symfony
			                'data'          => $expert,
			                //'choice_value' => function (Individu $entity = null) { return $entity->getIdIndividu(); },
			                'choice_label'  => function ($individu) { return $individu->__toString(); },
		                    ])
	                    ->getForm();
		return $forms;
	}
	
	/**
	 * Sauvegarde les experts associés à une rallonge
	 *
	 ***/
	protected function affecterExpertsToDemande($experts, Demande $rallonge)
	{
		$em = $this->doctrine->getManager();
		$rallonge->setExpert($experts[0]);
		$em->persist($rallonge);
		$em->flush();
	}

	
	/******
	 * Ajoute des données dans le tableau notifications
	 * 
	 * notifications = tableau associatif
	 *                 clé = $expert
	 *                 val = Liste de $demandes
	 * 
	 * params $demande La demande (=version) correspondante
	 *****/
	protected function addNotification($demande)
	{
		//$notifications = $this    -> notifications;
		$expert = $demande -> getExpert();
		$exp_mail = $expert -> getMail();
		if (!array_key_exists($exp_mail, $this->notifications))
		{
			$this->notifications[$exp_mail] = [];
		}
		$this->notifications[$exp_mail][] = $demande;
	}
	
	/******
	* Appelée quand on clique sur Notifier les experts
	* Envoie une notification aux experts du tableau notifications
	*
	*****/
	protected function notifierExperts()
	{
		$notifications = $this->notifications;
		
		Functions::debugMessage( __METHOD__ . count($notifications) . " notifications à envoyer");

		foreach ($notifications as $e => $liste_d)
		{
			$dest   = [ $e ];
			$params = [ 'object' => $liste_d ];
			//Functions::debugMessage( __METHOD__ . "Envoi d'un message à " . join(',',$dest) . " - " . Functions::show($liste_d) );

			Functions::sendMessage ('notification/affectation_expert_rallonge-sujet.html.twig',
									'notification/affectation_expert_rallonge-contenu.html.twig',
									$params,
									$dest);
		}
	}
}

