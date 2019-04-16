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
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use AppBundle\Utils\IndividuForm;

class IndividuFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    $builder->add('login', CheckboxType::class,
            [
                'label'     =>  'login',
                'required'  =>  false,
            ])
        ->add('mail', TextType::class,
            [
                'label'     =>  'email',
                'attr'      =>  [ 'size' => '50' ],
                'required'  =>  false,
            ])
        ->add('prenom', TextType::class,
            [
                'label'     =>  'prénom',
                'attr'      =>  [ 'size' => '50' ],
                'required'  =>  false,
            ])
        ->add('nom', TextType::class,
            [
                'label'     =>  'nom',
                'attr'      =>  [ 'size' => '50' ],
                'required'  =>  false,
            ])
        ->add('statut', EntityType ::class,
            [
                'label'      => 'statut',
                'multiple'   => false,
                'expanded'   => false,
                'required'   => false,
                'class'      => 'AppBundle:Statut',
                'placeholder' => '-- Indiquez le statut',
            ])
        ->add('laboratoire', EntityType ::class,
            [
                'label'     => 'laboratoire',
                'multiple'  => false,
                'expanded'  => false,
                'required'   => false,
                'class'     => 'AppBundle:Laboratoire',
                'placeholder' => '-- Indiquez le laboratoire',
            ])
        ->add('etablissement', EntityType ::class,
            [
                'label'     => 'établissement',
                'multiple'  => false,
                'expanded'  => false,
                'required'   => false,
                'class'     => 'AppBundle:Etablissement',
                'placeholder' => "-- Indiquez l'établissment",
            ])
        ->add('delete', CheckboxType::class,
            [
                'label'     =>  'supprimer',
                'required'  =>  false,
            ])
        ->add('id', HiddenType::class,
            [
               
            ])
        ;    
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
            'data_class' => 'AppBundle\Utils\IndividuForm',
            ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'Individu';
    }

}
