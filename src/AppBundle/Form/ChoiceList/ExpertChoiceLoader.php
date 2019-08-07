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

// src/AppBundle/Form/ChoiceList/ExpertChoiceLoader.php

namespace AppBundle\Form\ChoiceList;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use AppBundle\AppBundle;
use AppBundle\Utils\Functions;

use AppBundle\Entity\Individu;
use AppBundle\Entity\Thematique;

/***
* Cette classe permet de charger les listes d'experts pour les widgets de choix d'expert
* On passe une liste d'experts exclus (parce que collaborateurs, déjà affectés, etc) dans le constructeur
*/

class ExpertChoiceLoader implements ChoiceLoaderInterface
{
    private static  $global_choices    =   [];

    private                $choices    =   [];
    private                $expertToId =   [];
    private                $idToExpert =   [];

    private $exclus     =   [];

    public function __construct($exclus = [])
    {
	    Functions::debugMessage(__METHOD__ . "Experts exclus ".Functions::show( $exclus));
	    Functions::debugMessage(__METHOD__ . "Experts exclus ".Functions::show( array_keys($exclus)));

	    $this->exclus   =  $exclus;

	    if(  static::$global_choices == [] )
        {
	        $experts = [];

	        foreach( AppBundle::getRepository(Individu::class)->findBy(['president' =>  true ]) as $expert )
			{
	            static::$global_choices['Présidents'][$expert->getIdIndividu()]   =   $expert;
	            $experts[ $expert->getIdIndividu() ] =   $expert;
			}

	        foreach( AppBundle::getRepository(Thematique::class)->findAll() as $thematique )
			{
	            // nous vérifions que la liste contient vraiment des experts
	            foreach( $thematique->getExpert()->toArray() as $expert )
				{
	                if(  $expert->isExpert() )
					{
	                    static::$global_choices[ $thematique->getLibelleThematique() ][$expert->getIdIndividu()]   =   $expert;
	                    //static::$choices[ $thematique->getLibelleThematique() ][]   =   $expert;
	                    $experts[ $expert->getIdIndividu() ] =   $expert;
					}
				}

				//static::$choices[ $thematique->getLibelleThematique() ] = $experts_thematique;
			}

	        foreach( AppBundle::getRepository(Individu::class)->findBy(['expert' =>  true ]) as $expert )
	        {
	            if( ! array_key_exists( $expert->getIdIndividu(), $experts ) )
	            {
	                static::$global_choices['Experts hors thématique'][$expert->getIdIndividu()] = $expert;
				}
			}
        }

	    $this->expertToId =   [];
	    $this->idToExpert =   [];
	    $this->choices = [];

	    foreach(  static::$global_choices as $thematique_key => $thematique_list )
        {
	        // Functions::debugMessage( __METHOD__ . ' thematique_list ' . Functions::show( $thematique_list ) );
	        foreach( $thematique_list as $expert_id => $expert )
	        {
	            if( ! array_key_exists( $expert_id, $this->exclus ) )
                {
	                //static::$choices[$thematique_key][$expert_id]    =  $expert;
	                $this->choices[$thematique_key][]    =  $expert;

	                $this->expertToId[$expert_id]   =   count( $this->idToExpert );
	                $this->idToExpert[]             =  $expert;

                }
                else
                {
					Functions::debugMessage( __METHOD__ . " $expert_id,Vous êtes viré !");
				}
			}
        }

    }

    //////////////////////////////////////////////////

    public function loadChoiceList($value = null)
    {
	    //Functions::debugMessage(__METHOD__ . " choices : " . Functions::show( $this->choices ) );
	    return new ArrayChoiceList( $this->choices );
    }

    /////////////////////////////////////////////////////////

    public function loadChoicesForValues(array $values, $value = null)
    {
	    $result =   [];
	    //Functions::debugMessage(__METHOD__ . " values : " . Functions::show( $values ) );
	    //Functions::debugMessage(__METHOD__ . " idToExpert : " . Functions::show( $this->idToExpert ) );
	    //Functions::debugMessage(__METHOD__ . " expertToId : " . Functions::show( $this->expertToId ) );
	    foreach ($values as $id)
        {
	        if( isset( $this->idToExpert[$id] ) ) $result[]   =   $this->idToExpert[$id];
	        else $result[]   = null;
        }
	    return $result;
    }

    ////////////////////////////////////////////////////////////////

    public function loadValuesForChoices(array $choices, $value = null)
    {
	    $result = [ ];
	    //Functions::debugMessage(__METHOD__ . " choices : " . Functions::show( $choices ) );
	    //Functions::debugMessage(__METHOD__ . " expertToId : " . Functions::show( $this->expertToId ) );
	    //Functions::debugMessage(__METHOD__ . " idToExpert : " . Functions::show( $this->idToExpert ) );
	    foreach ($choices as $individu)
        {
	        if($individu != null &&  isset( $this->expertToId[$individu->getIdIndividu()] ) )
	            $result[]   =   $this->expertToId[$individu->getIdIndividu()];
	        else
	            $result[]   =   null;
        }
	    return $result;
    }
}
