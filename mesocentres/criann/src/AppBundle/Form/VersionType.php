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

class VersionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        	->add('criannTag')
            ->add('etatVersion')
            ->add('prjLLabo')
            ->add('prjTitre')
            ->add('demHeures')
            ->add('attrHeures')
            ->add('consHeures')
            ->add('prjSousThematique')
            ->add('prjFinancement')
            ->add('prjGenciMachines')
            ->add('prjGenciCentre')
            ->add('prjGenciHeures')
            ->add('prjResume')
            ->add('prjExpose')
            ->add('prjJustifRenouv')
            ->add('prjAlgorithme')
            ->add('prjConception')
            ->add('prjDeveloppement')
            ->add('prjParallelisation')
            ->add('prjUtilisation')
            ->add('prjFiche')
            ->add('prjFicheVal')
            ->add('codeNom')
            ->add('codeLangage')
            ->add('codeLicence')
            ->add('codeUtilSurMach')
            ->add('codeHeuresPJob')
            ->add('codeRamPCoeur')
            ->add('codeRamPart')
            ->add('codeEffParal')
            ->add('codeVolDonnTmp')
            ->add('demLogiciels')
            ->add('demBib')
            ->add('demPostTrait')
            ->add('demFormMaison')
            ->add('demFormAutres')
            ->add('libelleThematique')
            ->add('attrAccept')
            ->add('rapConf')
            ->add('majInd')
            ->add('majStamp')
            ->add('sondVolDonnPerm')
            ->add('sondDureeDonnPerm')
            ->add('prjFicheLen')
            ->add('penalHeures')
            ->add('attrHeuresEte')
            ->add('sondJustifDonnPerm')
            ->add('demFormAutresAutres')
            ->add('idVersion')
            ->add('prjThematique')
            ->add('session')
            ->add('projet')
            ->add('politique');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Version'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_version';
    }


}
