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

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use AppBundle\AppBundle;
use AppBundle\Utils\Etat;
use AppBundle\Utils\Functions;
use AppBundle\Entity\Version;
use AppBundle\Entity\Expertise;
use AppBundle\Entity\CollaborateurVersion;
use AppBundle\Utils\GramcDate;

use AppBundle\Form\ChoiceList\ExpertChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Projet
 *
 * @ORM\Table(name="projet", indexes={@ORM\Index(name="etat_projet", columns={"etat_projet"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProjetRepository")
 */
class Projet
{
    /**
     * @var integer
     *
     * @ORM\Column(name="etat_projet", type="integer", nullable=false)
     */
    private $etatProjet;

    /**
     * @var string
     *
     * @ORM\Column(name="id_projet", type="string", length=6)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idProjet;

    /**
     * @var \AppBundle\Entity\Version
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Version")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_veract", referencedColumnName="id_version", onDelete="SET NULL", nullable=true)
     * })
     *
     * la version active actuelle ou la dernière version active si aucune n'est active actuellement
     *
     */
    private $versionActive;

    /**
     * @var \AppBundle\Entity\Version
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Version")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_verder", referencedColumnName="id_version", onDelete="SET NULL", nullable=true )
     * })
     *
     *  la version qui correspond  à la dernière session
     *  cette clé est fixée au moment de la création de la version
     *  si la session d'une version change après sa création il faut le modifier manuellement
     */
    private $versionDerniere;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Publication", inversedBy="projet")
     * @ORM\JoinTable(name="publicationProjet",
     *   joinColumns={
     *     @ORM\JoinColumn(name="id_projet", referencedColumnName="id_projet")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="id_publi", referencedColumnName="id_publi")
     *   }
     * )
     */
    private $publi;

    ////////////////////////////////////////////////////////////////////////////////

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Version", mappedBy="projet")
     */
    private $version;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\RapportActivite", mappedBy="projet")
     */
    private $rapportActivite;

    public function getId(){ return $this->getIdProjet(); }
    public function __toString(){ return $this->getIdProjet(); }


    /**
     * Constructor
     */
    public function __construct( $type = 'P' )
    {
        $this->publi        = new \Doctrine\Common\Collections\ArrayCollection();
        $this->version      = new \Doctrine\Common\Collections\ArrayCollection();
        $this->rapportActivite = new \Doctrine\Common\Collections\ArrayCollection();
        $this->etatProjet   = Etat::EDITION_DEMANDE;
        $this->idProjet     = AppBundle::getRepository(Projet::class)->nextId( Functions::getSessionCourante(), $type );
    }

    /**
     * Set etatProjet
     *
     * @param integer $etatProjet
     *
     * @return Projet
     */
    public function setEtatProjet($etatProjet)
    {
        $this->etatProjet = $etatProjet;

        return $this;
    }

    /**
     * Get etatProjet
     *
     * @return integer
     */
    public function getEtatProjet()
    {
        return $this->etatProjet;
    }

    /**
     * Set idProjet
     *
     * @param string $idProjet
     *
     * @return Projet
     */
    public function setIdProjet($idProjet)
    {
        $this->idProjet = $idProjet;

        return $this;
    }

    /**
     * Get idProjet
     *
     * @return string
     */
    public function getIdProjet()
    {
        return $this->idProjet;
    }

    /**
     * Set versionActive
     *
     * @param \AppBundle\Entity\Version $version
     *
     * @return Projet
     */
    public function setVersionActive(\AppBundle\Entity\Version $version = null)
    {
        $this->versionActive = $version;

        return $this;
    }

    /**
     * Get versionActive
     *
     * @return \AppBundle\Entity\Version
     */
    public function getVersionActive()
    {
        return $this->versionActive;
    }

    /**
     * Set versionDerniere
     *
     * @param \AppBundle\Entity\Version $version
     *
     * @return Projet
     */
    public function setVersionDerniere(Version $version = null)
    {
        $this->versionDerniere = $version;

        return $this;
    }

    /**
     * Get versionDerniere
     *
     * @return \AppBundle\Entity\Version
     */
    public function getVersionDerniere()
    {
        return $this->versionDerniere;
    }

    /**
     * Add publi
     *
     * @param \AppBundle\Entity\Publication $publi
     *
     * @return Projet
     */
    public function addPubli(\AppBundle\Entity\Publication $publi)
    {
        if( ! $this->publi->contains( $publi ) )
            $this->publi[] = $publi;

        return $this;
    }

    /**
     * Remove publi
     *
     * @param \AppBundle\Entity\Publication $publi
     */
    public function removePubli(\AppBundle\Entity\Publication $publi)
    {
        $this->publi->removeElement($publi);
    }

    /**
     * Get publi
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPubli()
    {
        return $this->publi;
    }

    /**
     * Add version
     *
     * @param \AppBundle\Entity\Version $version
     *
     * @return Projet
     */
    public function addVersion(\AppBundle\Entity\Version $version)
    {
        $this->version[] = $version;

        return $this;
    }

    /**
     * Remove version
     *
     * @param \AppBundle\Entity\Version $version
     */
    public function removeVersion(\AppBundle\Entity\Version $version)
    {
        $this->version->removeElement($version);
    }

    /**
     * Get version
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Add rapportActivite
     *
     * @param \AppBundle\Entity\RapportActivite $rapportActivite
     *
     * @return Projet
     */
    public function addRapportActivite(\AppBundle\Entity\RapportActivite $rapportActivite)
    {
        $this->rapportActivite[] = $rapportActivite;

        return $this;
    }

    /**
     * Remove rapportActivite
     *
     * @param \AppBundle\Entity\RapportActivite $rapportActivite
     */
    public function removeRapportActivite(\AppBundle\Entity\RapportActivite $rapportActivite)
    {
        $this->rapportActivite->removeElement($rapportActivite);
    }

    /**
     * Get rapportActivite
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRapportActivite()
    {
        return $this->rapportActivite;
    }

    /////////////////////////////////

    // pour workflow

    public function getObjectState()
    {
        return $this->getEtatProjet();
    }

    public function setObjectState($state)
    {
        $this->setEtatProjet($state);
        AppBundle::getManager()->flush();
        return $this;
    }

    public function getSubWorkflow()    { return new \AppBundle\Workflow\VersionWorkflow(); }

    public function getSubObjects()
        {
            $versions = $this->getVersion();
            $my_versions = new \Doctrine\Common\Collections\ArrayCollection();

            foreach( $versions as $version )
                {
                    $etat   =   $version->getEtatVersion();
                    if( $etat != Etat::TERMINE && $etat != Etat::ANNULE )
                        $my_versions[]  = $version;
                }
            return $my_versions;
        }

    ////////////////////////////////////////

    // pour twig

    public function getLibelleEtat()
    {
        return Etat::getLibelle( $this->getEtatProjet() );
    }

    public function getTitre()
    {
        if( $this->derniereVersion() != null )
            return $this->derniereVersion()->getPrjTitre();
        else
            return null;
    }

    public function getThematique()
    {
        if( $this->derniereVersion() != null )
            return $this->derniereVersion()->getPrjThematique();
        else
            return null;
    }

    public function getLaboratoire()
    {
        if( $this->derniereVersion() != null )
            return $this->derniereVersion()->getPrjLLabo();
        else
            return null;
    }

    public function countVersions()
    {
        return AppBundle::getRepository(Version::class)->countVersions($this);
    }

    public function derniereSession()
    {
        if( $this->derniereVersion() != null )
            return $this->derniereVersion()->getSession();
        else
           return null;
    }

    public function getResponsable()
    {
        if( $this->derniereVersion() != null )
            return $this->derniereVersion()->getResponsable();
        else
            return null;
    }

    public function isProjetTest()
    {
    $idProjet   =  $this->getIdProjet();
    if( is_string($idProjet ) )
        {
        $type = substr( $idProjet , 0, 1 );
        if( $type == 'T' )
            return true;
        elseif( $type == 'P' )
            return false;
        }

    Functions::createException(__METHOD__ . ":" . __LINE__ . " mauvais type de projet " . Functions::show( $type) );
    }

    // Le Meta-état des projets, pour affichage
    // Renvoie une chaine de caractère décrivant l'état du projet, pour que ce soit compréhensible par les utilisateurs
    // Note - Le méta-état du projet dépend de l'état de la dernière version !
    public function getMetaEtat()
    {
        $etat_projet = $this->getEtatProjet();

        if      (   $etat_projet    ==  Etat::EN_STANDBY    )   return 'STANDBY'; //En attente, renouvellement possible
        elseif  (   $etat_projet    ==  Etat::TERMINE       )   return 'TERMINE';

        $version        =   $this->derniereVersion();
        if($version == null)    return 'SANS VERSION';
        $etat_version   =   $version->getEtatVersion();

        if      (   $etat_version   ==  Etat::ANNULE                )   return 'ANNULE';
        elseif  (   $etat_version   ==  Etat::EDITION_DEMANDE       )   return 'EDITION';
        elseif  (   $etat_version   ==  Etat::EDITION_EXPERTISE     )   return 'EXPERTISE';
        elseif  (   $etat_version   ==  Etat::EDITION_TEST          )   return 'EDITION';
        elseif  (   $etat_version   ==  Etat::EXPERTISE_TEST        )   return 'EXPERTISE';
        elseif  (   $version->getAttrAccept()   ==  true            )
        {
            $session = Functions::getSessionCourante();
            if( $session->getEtatSession() == Etat::EDITION_DEMANDE &&  $session->getLibelleTypeSession() === 'A' )
                return 'NONRENOUVELE'; // Non renouvelé
            else
                return 'ACCEPTE'; // Projet ou rallonge accepté par le comité d'attribution
        }
        else    return 'REFUSE';
    }

     /**
     * derniereVersion
     *
     * @return \AppBundle\Entity\Version
     */
    public function derniereVersion()
    {
        // si la clé étrangère est correcte return cette clé sinon on la calcule
        $derniereVersion    =   $this->getVersionDerniere();
        if( $derniereVersion != null )
            return $derniereVersion;
        else
            return $this->calculDerniereVersion();

    }

     /**
     * calculDerniereVersion
     *
     * @return \AppBundle\Entity\Version
     */
    public function calculDerniereVersion()
    {
        if( $this->getVersion() == null ) return null;

        $iterator = $this->getVersion()->getIterator();
        $iterator->uasort(function ($a, $b)
            {
                if( $a->getSession() == null )
                    return true;
                elseif( $b->getSession() == null )
                    return false;
                else
                    return strcmp($a->getSession()->getIdSession(), $b->getSession()->getIdSession());
            } );
        $sortedVersions =  iterator_to_array($iterator) ;

        $result = end( $sortedVersions );
        if( ! $result instanceof Version ) return null;

        // update BD
        $this->setVersionDerniere($result);
        $em = AppBundle::getManager();
        $em->persist($this);
        $em->flush();
        return $result;
    }

    /**
     * versionActive
     *
     * @return \AppBundle\Entity\Version
     */
    public function versionActive()
    {
        // si la clé étrangère est correcte return cette clé sinon on la calcule
        $versionActive    =   $this->getVersionActive();

        if( $versionActive != null &&
                ( $versionActive->getEtatVersion() == Etat::ACTIF || $versionActive->getEtatVersion()  == Etat::NOUVELLE_VERSION_DEMANDEE )
            )
            return $versionActive;

       $result     =   null;

      foreach( $this->getVersion() as $version )
            if( $version->getEtatVersion() == Etat::ACTIF ||  $version->getEtatVersion() == Etat::NOUVELLE_VERSION_DEMANDEE )
                $result = $version;

        // update BD
        if( $versionActive != null || $result != null ) // seulement s'il y a un changement
            {
            $this->setVersionActive( $result );
            $em = AppBundle::getManager();
            $em->persist($this);
            $em->flush();
            }
        return $result;
    }

    //////////////////////////////////////////////

    public function isCollaborateur(Individu $individu = null )
    {
        foreach( $this->getVersion() as $version )
            if( $version->isCollaborateur($individu) == true ) return true;
        return false;
    }

    ////////////////////////////////////////////////////

    public function getCollaborateurs( $versions = [] )
    {
    if( $versions == [] ) $versions = AppBundle::getRepository(Version::class)->findVersions( $this );

    $collaborateurs = [];
    foreach( $versions as $version )
        foreach( $version->getCollaborateurs() as $collaborateur )
            $collaborateurs[ $collaborateur->getIdIndividu() ] = $collaborateur;

    return $collaborateurs;
    }

    /////////////////////////////////////////////////////

    public function proposeExpert( $collaborateurs = [] )
    {
    if( $this->isProjetTest() )
        {
        $presidents = AppBundle::getRepository(Individu::class)->findBy(['president'=>true]);
        if( $presidents != null ) return $presidents[0];
        }

    if( $collaborateurs == [] )
        $collaborateurs = AppBundle::getRepository(CollaborateurVersion::class)->getCollaborateurs( $this );
    
    $derniereVersion = $this->derniereVersion();
    if( $derniereVersion == null )
        {
        Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " projet " . $this->getIdProjet() . " n'a pas de dernière version !");
        return null;
        }

    // on veut garder l'expert de la version précédente

    $versionPrecedente  =  $derniereVersion->versionPrecedente ();
    if( $versionPrecedente == null )
        $derniereExpertise = null;
    else
        $derniereExpertise  =   $versionPrecedente->getOneExpertise();

    if( $derniereExpertise != null  )
        {
        $expert = $derniereExpertise->getExpert();
        if( $expert == null )
            Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " L'expertise de la version précédente " .
                    $derniereVersion->getIdVersion(). "(" .$derniereExpertise->getId() . ") n'a pas d'expert !");
        elseif( $expert->isExpert() == false )
            Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " L'expert de la version précédente " .
                    $derniereVersion->getIdVersion(). "(" .$derniereExpertise->getId() . ") " . $expert . " n'est plus un expert");
        elseif( array_key_exists( $expert->getIdIndividu(), $collaborateurs ) )
            Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " L'expert de la version précédente  " .
                    $derniereVersion->getIdVersion(). "(" .$derniereExpertise->getId() . ") " . $expert . " est un collaborateur");
        else
            return $expert;
        }
    elseif( $versionPrecedente != null )
        Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " La version précédente " . $versionPrecedente . " n'a pas d'expertise !");

    $versions = AppBundle::getRepository(Version::class)->findVersions( $this );

    // sinon on cherche des experts des expertises précédentes
    $dernierIdVersion = $derniereVersion->getIdVersion();
    foreach( $versions as $version )
        {
        $expertises = $version->getExpertise();
        if( $expertises != null && isset($expertises[0]) && $version->getIdVersion() != $dernierIdVersion )
            {
            $expertise = $expertises[0];
            $expert = $expertise->getExpert();
            if ( $expert == null )
                Functions::noticeMessage(__METHOD__ .  ":" . __LINE__ . " L'expertise de la version " .  $version->getIdVersion(). "(" .$expertise->getId() . ") n'a pas d'expert !");
            elseif(  $expert->isExpert() == false  )
                Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " L'expert de la version " .
                    $version->getIdVersion(). "(" .$expertise->getId() . ") " . $expert . " n'est plus un expert");
            elseif( array_key_exists( $expert->getIdIndividu(), $collaborateurs ) )
                Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " L'expert de la version " .
                    $version->getIdVersion(). "(" .$expertise->getId() . ") " . $expert . " est un collaborateur");
            else
                return $expert;
            }
        else
            Functions::noticeMessage(__METHOD__ .  ":" . __LINE__ . " II version " . $version->getIdVersion() . " n'a pas d'expertise !");
        }
    //Functions::debugMessage(__METHOD__ ." après II " );
    $thematique = $derniereVersion->getPrjThematique();
    if( $thematique == null )
        {
        Functions::errorMessage(__METHOD__ ." version " . $derniereVersion->getIdVersion() . " n'a pas de thématique !" );
        return null;
        }

    $experts = $thematique->getExpert();
    if( $experts == null  )
        {
        Functions::warningMessage(__METHOD__  .  ":" . __LINE__ ." thematique " . $thematique . " n'a pas d'expert !" );
        return null;
        }

    // on cherche un expert de la thématique le moins sollicité

   // Functions::debugMessage(__METHOD__ ." thematique " . $thematique . " expert le moins sollicité " );
    $expertises = [];
    foreach( $experts as $expert )
        if( $expert->isExpert() &&  ! array_key_exists( $expert->getIdIndividu(), $collaborateurs ) )
            $expertises[ AppBundle::getRepository(Expertise::class)->countExpertises($expert) ] = $expert;
        elseif( ! $expert->isExpert() )
            {
            Functions::errorMessage(__METHOD__  .  ":" . __LINE__ . " " .  $expert . " est un expert de la thématique " . $thematique . " sans être un expert !");
            Functions::noThematique( $expert );
            }

    if( $expertises != null )
        {
        ksort( $expertises );
        return  reset($expertises);
        }
    else
        return null;
    }

    ////////////////////////////////////////////

    public function getExpert(Session $session = null)
    {
    if( $session == null ) $session = Functions::getSessionCourante();

    $expertise  = $this->getOneExpertise($session);
    if( $expertise  == null )
        return null;
    else
        return $expertise->getExpert();
    }

    ///////////////////////////////////////////////

    public function getOneExpertise(Session $session)
    {
    if( $this->isProjetTest() )
        $version    =   $this->derniereVersion();
    else
        $version    =   AppBundle::getRepository(Version::class)->findOneBy(['session' => $session, 'projet' => $this]);
        
    if( $version != null )
        {
        $expertises =   $version->getExpertise()->toArray();
        if( $expertises !=  null )
            {
            $expertise  =   current( $expertises );
            //Functions::debugMessage(__METHOD__ . " expertise = " . Functions::show( $expertise )
            //    . " expertises = " . Functions::show( $expertises ));
            return $expertise;
            }
        else
            Functions::noticeMessage(__METHOD__ . " version " . $version . " n'a pas d'expertise !");
        }
    else
        Functions::noticeMessage(__METHOD__ . " projet " . $this . " n'a pas de version pour la session " . $session . " !");

    return null;
    }

    // calcul de la consommation et du quota d'une ressource à partir de la table compta
    // Si $annee==null -> On prend l'annee courante
    // Si $annee==annee courante -> On prend l'info à la DATE DU JOUR
    // Si $annee==autre année    -> On prend l'info au 31 Décembre
    // Renvoie [ $conso, $quota ]
    // NOTE - Si la table est chargée à 8h00 du matin, toutes les consos de l'année courante seront = 0 avant 8h00
    public function getConsoRessource($ressource, $annee=null)
    {
        $annee_courante = GramcDate::get()->showYear();
        if ($annee==null) $annee = $annee_courante;
        if ($annee==$annee_courante) 
        {
            $date = new \DateTime();
        } 
        else 
        {
            $date = new \DateTime( $annee . '-12-30');
        }
        $loginName = strtolower($this->getIdProjet());
        $conso     = 0;
        $quota     = 0;
        $consop    = 0;
        $compta    = AppBundle::getRepository(Compta::class)->findOneBy(
                                                [
                                                    'date'      => $date,
                                                    'ressource' => $ressource,
                                                    'loginname' => $loginName,
                                                    'type'      => 2
                                                ]);
        if ($compta != null)
        {
            $conso = $compta->getConso();
            $quota = $compta->getQuota();
        }
        
        return [$conso, $quota];
    }
            
    // TODOCONSOMMATION - calcul de la consommation à partir de la table Consommation
    public function getConso($annee)
    {
        $consommation   =   $this->getConsommation($annee);
        $conso          =   0;

        if( $consommation != null )
        {
            for ($i = 1; $i <= 12; $i++)
            {
                if( $i < 10 )
                    $methodName = 'getM0'.$i;
                else
                    $methodName ='getM'.$i;
                $c = $consommation->$methodName();
                if( $c != null && $c > $conso ) $conso  =   $c;
            }
        }
        return $conso;
    }
    
    public function getConsommation($annee)
    {
        return AppBundle::getRepository(Consommation::class)->findOneBy(
                                                        [
                                                        'annee'     => $annee,
                                                        'projet'    => $this->getIdProjet()
                                                        ]);
    }

    ///////////////////////////////////////////////////////////////////////////////
    //
    // préparation du formulaire du choix d'expert
    //

    public function getExpertForm(Session $session, $hash = "")
    {

    $expert = $this->getExpert($session);
    $collaborateurs = AppBundle::getRepository(CollaborateurVersion::class)->getCollaborateurs($this);

    if( $expert ==  null )
        {
        $expert  =  $this->proposeExpert( $collaborateurs );
        Functions::debugMessage(__METHOD__ . ":" . __LINE__ ." nouvel expert proposé du projet " . $this . " : " . Functions::show( $expert ) );
        }


    return AppBundle::getContainer()->get( 'form.factory')
            ->createNamedBuilder(   'expert'.$this->getIdProjet().$hash , FormType::class, null  ,  ['csrf_protection' => false ])
                ->add('expert', ChoiceType::class,
                    [
                'multiple'  =>  false,
                'required'  =>  false,
                'label'     => '',
                //'choices'       => $choices, // cela ne marche pas à cause d'un bogue de symfony
                'choice_loader' => new ExpertChoiceLoader($collaborateurs), // nécessaire pour contourner le bogue de symfony
                'data'          => $expert,
                //'choice_value' => function (Individu $entity = null) { return $entity->getIdIndividu(); },
                'choice_label' => function ($individu)
                   { return $individu->__toString(); },
                    ])
                    ->getForm();
    }


    /////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////

    public function getVersionsAnnee($annee)
    {
    $subAnnee   = substr( strval($annee), -2 );
    $repository = AppBundle::getRepository(Version::class);
    $versionA   = AppBundle::getRepository(Version::class)->findBy( [ 'idVersion' => $subAnnee . 'A' . $this->getIdProjet(), 'projet' => $this ] );
    $versionB   = AppBundle::getRepository(Version::class)->findBy( [ 'idVersion' => $subAnnee . 'B' . $this->getIdProjet(), 'projet' => $this ] );

    $versions = [];
    if( $versionA != null ) $versions['A'] = $versionA;
    if( $versionB != null ) $versions['B'] = $versionB;
    return $versions; 
    }

}

