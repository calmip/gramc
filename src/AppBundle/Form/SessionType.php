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

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use AppBundle\Entity\Journal;
use AppBundle\Entity\Individu;

use AppBundle\Utils\Functions;
use AppBundle\Utils\GramcDate;
use AppBundle\Utils\Etat;
use AppBundle\AppBundle;


class SessionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if( $options['commentaire'] )
        {
            $builder->add('commGlobal');
            return;
        }
        if( $options['all'] )
        {
            $builder->add('typeSession')->add('commGlobal');
        }

        $builder
            ->add('dateDebutSession', DateType::class,
                    [
                    //'data'          => $options['from'], // valeur par défaut
                    'label'         => 'Date de début des saisies:',
                    'years'         => Functions::years( new \DateTime(), new GramcDate() ),
                    ])
            ->add('dateFinSession', DateType::class,
                    [
                    //'data'          => $options['from'], // valeur par défaut
                    'label'         => 'Date de fin des saisies:',
                    'years'         => Functions::years( new \DateTime(), new GramcDate() ),
                    ])
            ->add('hParAnnee', IntegerType::class,
                    [
                    //'data'          => $options['from'], // valeur par défaut
                    'label'         => 'Heures disponibles (par année):',
                    ])           
            ->add('president',  EntityType::class,
                        [
                    'multiple' => false,
                    'class' => 'AppBundle:Individu',
                    'required'  =>  false,
                    'label'     => 'Président:',
                    'choices' =>  AppBundle::getRepository(Individu::class)->findBy(['expert' => true]),
                ]);

         if(  $options['all'] == true )
            $builder->add('etatSession',   ChoiceType::class,
                    [
                        'choices'           =>
                                            [
                                            'CREE_ATTENTE'          =>  Etat::CREE_ATTENTE,
                                            'EDITION_DEMANDE'       =>  Etat::EDITION_DEMANDE,
                                            'EDITION_EXPERTISE'     =>  Etat::EDITION_EXPERTISE,
                                            'EN_ATTENTE'            =>  Etat::EN_ATTENTE,
                                            'ACTIF'                 =>  Etat::ACTIF,
                                            'TERMINE'               =>  Etat::TERMINE,
                                            ],
                        'label'             => 'Etat',
                        'choices_as_values' => true, // cette option devra être supprimée à partir de symfony 3
                    ])
                    ->add('idSession');

        if( $options['buttons'] == true )
            $builder
                ->add('submit',SubmitType::class, ['label' => $options['submit_label']  ])
                ->add('reset',   ResetType::class, ['label' => 'reset' ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
            'data_class'    => 'AppBundle\Entity\Session',
            'all'           =>  false,
            'buttons'        => false,
            'submit_label'  =>  'modifier',
            'commentaire'   =>  false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_session';
    }


}
