<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;


class CommentaireExpertType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		if ( $options['only_comment']==true)
		{
	        $builder
	        	->add('commentaire')
	        	->add('submit',SubmitType::class, ['label' => 'Valider' ])
                ->add('reset',ResetType::class, ['label' => 'reset' ]);
		}
		else
		{
	        $builder->add('commentaire')->add('annee')->add('majStamp')->add('expert');
		}
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'   => 'AppBundle\Entity\CommentaireExpert',
            'only_comment' => false
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_commentaireexpert';
    }


}
