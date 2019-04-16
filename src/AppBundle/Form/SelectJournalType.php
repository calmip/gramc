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

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use AppBundle\Utils\Functions;
use AppBundle\Entity\Journal;
use AppBundle\Utils\GramcDate;

class SelectJournalType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('dateDebut', DateTimeType::class,
                    [
                    'data'          => $options['from'], // valeur par défaut
                    'label'         => 'Heure de début d\'affichage',
                    'with_seconds'  => true,
                    'years'         => Functions::years( $options['from'], new \DateTime()   ),
                    ])
                ->add('dateFin', DateTimeType::class,
                    [
                    'data'          => $options['untill'],
                    'label'         => 'Heure de fin d\'affichage',
                    'with_seconds'  => true,
                    'years'         => Functions::years($options['untill'] , new \DateTime() ),
                    ])
                ->add('niveau',     ChoiceType::class,
                    [
                        'choices'           =>  array_flip( Journal::LIBELLE ),
                        'data'              =>  Journal::INFO , // valeur par défaut
                        'label'             => 'Niveau de log',
                        'choices_as_values' => true, // cette option devra être supprimée à partir de symfony 3
                    ])
                ->add('submit',     SubmitType::class,
                    [
                        'label'         => 'chercher',
                    ])
                ->add('all',     SubmitType::class,
                    [
                        'label'         => 'AFFICHER TOUT',
                    ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
        [
        'from'    => GramcDate::get()->sub( \DateInterval::createFromDateString( '3 months' ) ),
        'untill'    => GramcDate::get()->add( \DateInterval::createFromDateString( '1 day' ) ),
        ]);
    }
}
