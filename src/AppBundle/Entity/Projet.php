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
use AppBundle\Utils\NextProjetId;

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
	const PROJET_SESS = 1;		// Projet créé lors d'une session d'attribution
	const PROJET_TEST = 2;		// Projet test, créé au fil de l'eau, non renouvelable
	const PROJET_FIL  = 3;		// Projet créé au fil de l'eau, renouvelable lors des sessions

    /**
     * @var integer
     *
     * @ORM\Column(name="etat_projet", type="integer", nullable=false)
     */
    private $etatProjet;


    /**
     * @var integer
     *
     * @ORM\Column(name="type_projet", type="integer", nullable=false)
     */
    private $typeProjet;

    /**
     * @var string
     *
     * @ORM\Column(name="id_projet", type="string", length=10)
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
    public function __construct($type)
    {
        $this->publi        = new \Doctrine\Common\Collections\ArrayCollection();
        $this->version      = new \Doctrine\Common\Collections\ArrayCollection();
        $this->rapportActivite = new \Doctrine\Common\Collections\ArrayCollection();
        $this->etatProjet   = Etat::EDITION_DEMANDE;
        $this->typeProjet   = $type;
        $annee              = Functions::getSessionCourante()->getAnneeSession();
        $this->idProjet     = NextProjetId::NextProjetId($annee, $type);
        //$this->idProjet     = AppBundle::getRepository(Projet::class)->nextId( Functions::getSessionCourante(), $type );
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
     * Set typeProjet
     *
     * @param integer $typeProjet
     *
     * @return Projet
     */
    public function setTypeProjet($typeProjet)
    {
        $this->typeProjet = $typeProjet;

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
     * Get typeProjet
     *
     * @return integer
     */
    public function getTypeProjet()
    {
        return $this->typeProjet;
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

	/***************************************************
	 * Fonctions utiles pour la class Workflow
	 * Autre nom pour getEtatProjet/setEtatProjet !
	 ***************************************************/
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

    //public function getSubWorkflow()    { return new \AppBundle\Workflow\VersionWorkflow(); }

    //public function getSubObjects()
        //{
            //$versions = $this->getVersion();
            //$my_versions = new \Doctrine\Common\Collections\ArrayCollection();

            //foreach( $versions as $version )
                //{
                    //$etat   =   $version->getEtatVersion();
                    //if( $etat != Etat::TERMINE && $etat != Etat::ANNULE )
                        //$my_versions[]  = $version;
                //}
            //return $my_versions;
        //}

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

    public function getRattachement()
    {
        if( $this->derniereVersion() != null )
            return $this->derniereVersion()->getPrjRattachement();
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

	/*
	 * Renvoie true si le projet est un projet test, false sinon
	 *
	 */
    public function isProjetTest()
    {
		$type = $this->getTypeProjet();
		if ($this->getTypeProjet() === Projet::PROJET_TEST)
		{
			return true;
		}
		else
		{
			return false;
		}
    }

	/**************************************************************************
	 *
	 * Renvoie le "méta état" du projet, pour affichage
	 * c'est-à-dire une chaine de caractères décrivant l'état du projet, 
	 * qui doit être compréhensible par les utilisateurs
	 * 
	 * Peut renvoyer: TERMINE,REFUSE,ACCEPTE,STANDBY,EDITION,EXPERTISE,NONRENOUVELE
	 * 
	 **************************************************************************/
    
    public function getMetaEtat()
    {
        $etat_projet = $this->getEtatProjet();
        
        // Projet terminé
		if ($etat_projet == Etat::TERMINE) return 'TERMINE';
		
		// Projet non renouvelable:
		//    - Projet test   = toujours non renouvelable
		//    - Autres projets= sera bientôt terminé car expert a dit "refusé"
		//
		if ($etat_projet == Etat::NON_RENOUVELABLE && $this->getTypeProjet() != Projet::PROJET_TEST) return 'REFUSE';

        $veract      = $this->versionActive(); 
        $version        =   $this->derniereVersion();
        // Ne doit pas arriver: un projet a toujours une dernière version !
        if($version == null) {
			AppBundle::getLogger()->error(__METHOD__ . ":" . __LINE__ . "Incohérence dans la BD: le projet " . 
											$this->getIdProjet() . " version active: $veract n'a PAS de version dernière !");
			return 'STANDBY';
		}
        $etat_version   =   $version->getEtatVersion();

        if ( $etat_version ==  Etat::EDITION_DEMANDE       ) return 'EDITION';
        elseif ( $etat_version ==  Etat::EDITION_EXPERTISE ) return 'EXPERTISE';
        elseif ( $etat_version ==  Etat::EDITION_TEST      ) return 'EDITION';
        elseif ( $etat_version ==  Etat::EXPERTISE_TEST    ) return 'EXPERTISE';
        elseif ( $etat_version ==  Etat::ACTIF || $etat_version == Etat::ACTIF_TEST )
        {
			// Permet d'afficher un signe particulier pour les projets non renouvelés en période de demande pour une session A
            $session = Functions::getSessionCourante();
            if( $session->getEtatSession() == Etat::EDITION_DEMANDE &&  $session->getLibelleTypeSession() === 'A' )
                return 'NONRENOUVELE'; // Non renouvelé
            else
                return 'ACCEPTE'; // Projet ou rallonge accepté par le comité d'attribution
        }
        elseif ( $etat_version ==  Etat::EN_ATTENTE        ) return 'ACCEPTE';
        elseif ($veract == null                            ) return 'STANDBY';
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
		$em = AppBundle::getManager();

        $versionActive    =   $this->getVersionActive();
        
        // Si le projet est terminé = renvoyer null
        if ( $this->getEtatProjet() == Etat::TERMINE ) 
        {
			if ($versionActive != null)
			{
	            $this->setVersionActive( null );
	            $em->persist($this);
	            $em->flush();
			}
			return null;
		}
        
		// Vérifie que la version active est vraiment active
        if( $versionActive != null &&
          ( $versionActive->getEtatVersion() == Etat::ACTIF || $versionActive->getEtatVersion()  == Etat::NOUVELLE_VERSION_DEMANDEE )
          )
          {
	          return $versionActive;
		  }

		// Sinon on la recherche, on la garde, puis on la renvoie
		$result = null;
		foreach( array_reverse($this->getVersion()->toArray()) as $version )
		{
            if( $version->getEtatVersion() == Etat::ACTIF || 
                $version->getEtatVersion() == Etat::NOUVELLE_VERSION_DEMANDEE ||
                $version->getEtatVersion() == Etat::EN_ATTENTE ||
                $version->getEtatVersion() == Etat::ACTIF_TEST)
            {
                $result = $version;
                break;
			}
		}

        // update BD
        if( $versionActive != $result ) // seulement s'il y a un changement
		{
            $this->setVersionActive( $result );
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

	/**
    * Propose un expert pour l'expertise [0] de la dernière version du projet
    *         Si l'expert est déjà renseigné dans cette expertise, renvoie null
    *         TODO - Pour les projets à plusieurs expertises on pourrait faire mieux
    *
	* params = $exclus Un liste d'individus exclus du choix (par exemple les collaborateurs)
	*
	* return = l'expert proposé
	*
	******/
    public function proposeExpert( $exclus = [] )
    {
	    $version = $this->derniereVersion();
	    if( $version == null )
        {
	        Functions::errorMessage(__METHOD__ . ":" . __LINE__ . " projet " . $this->getIdProjet() . " n'a pas de dernière version !");
	        return null;
        }

		// Pas d'expertise associée à cette version, ou expert déjà attribué: return null
		//$expertise = $version->getOneExpertise();
		//if ($expertise == null || $expertise->getExpert() != null)
		//{
		//	return null;
		//}

		// Pour les projets de type PROJET_TEST et PROJET_FIL on propose le président[0]
		if ($this->getTypeProjet() == Projet::PROJET_TEST || $this->getTypeProjet() == Projet::PROJET_FIL)
		{
	        $presidents = AppBundle::getRepository(Individu::class)->findBy(['president'=>true]);
	        if( $presidents != null ) return $presidents[0];
	        return null;
        }

		// Par défaut les exclus sont les collaborateurs
	    if( $exclus == [] )
	    {
	        $exclus = AppBundle::getRepository(CollaborateurVersion::class)->getCollaborateurs( $this );
		}

	    // on veut garder l'expert de la version précédente

	    $versionPrecedente  =  $version->versionPrecedente ();
	    if( $versionPrecedente == null )
	        $derniereExpertise = null;
	    else
	        $derniereExpertise  =   $versionPrecedente->getOneExpertise();

	    if( $derniereExpertise != null  )
        {
	        $expert = $derniereExpertise->getExpert();
	        if( $expert == null )
	            Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " L'expertise de la version précédente " .
	                    $version->getIdVersion(). "(" .$derniereExpertise->getId() . ") n'a pas d'expert !");
	        elseif( $expert->isExpert() == false )
	            Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " L'expert de la version précédente " .
	                    $version->getIdVersion(). "(" .$derniereExpertise->getId() . ") " . $expert . " n'est plus un expert");
	        elseif( array_key_exists( $expert->getIdIndividu(), $exclus ) )
	            Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " L'expert de la version précédente  " .
	                    $version->getIdVersion(). "(" .$derniereExpertise->getId() . ") " . $expert . " est un collaborateur");
	        else
	            return $expert;
        }

	    elseif( $versionPrecedente != null )
	        Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " La version précédente " . $versionPrecedente . " n'a pas d'expertise !");

	    $versions = AppBundle::getRepository(Version::class)->findVersions( $this );

	    // sinon on cherche des experts des expertises précédentes
	    $dernierIdVersion = $version->getIdVersion();
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
	            elseif( array_key_exists( $expert->getIdIndividu(), $exclus ) )
	                Functions::noticeMessage(__METHOD__ . ":" . __LINE__ . " L'expert de la version " .
	                    $version->getIdVersion(). "(" .$expertise->getId() . ") " . $expert . " est un collaborateur");
	            else
	                return $expert;
            }
	        //else
	        //    Functions::noticeMessage(__METHOD__ .  ":" . __LINE__ . " II version " . $version->getIdVersion() . " n'a pas d'expertise !");
        }

	    //Functions::debugMessage(__METHOD__ ." après II " );
	    $thematique = $version->getPrjThematique();
	    if( $thematique == null )
        {
	        Functions::errorMessage(__METHOD__ ." version " . $version->getIdVersion() . " n'a pas de thématique !" );
	        return null;
        }

	    $experts = $thematique->getExpert();
	    if( $experts == null  )
        {
	        Functions::warningMessage(__METHOD__  .  ":" . __LINE__ ." thematique " . $thematique . " n'a pas d'expert !" );
	        return null;
        }

	    // on cherche un expert de la thématique le moins sollicité
		// $nb_expertises contient le nombre d'experties pour chaque expert: key = nombre d'expertises, val = expert 
		// Functions::debugMessage(__METHOD__ ." thematique " . $thematique . " expert le moins sollicité " );
	    $nb_expertises = [];
	    foreach( $experts as $expert )
	    {
	        if( $expert->isExpert() &&  ! array_key_exists( $expert->getIdIndividu(), $exclus ) )
	            $nb_expertises[ AppBundle::getRepository(Expertise::class)->countExpertises($expert) ] = $expert;
	        elseif( ! $expert->isExpert() )
            {
	            Functions::errorMessage(__METHOD__  .  ":" . __LINE__ . " " .  $expert . " est un expert de la thématique " . $thematique . " sans être un expert !");
	            Functions::noThematique( $expert );
            }
		}
		// Tri sur les clés et renvoie le premier élément
	    if( count($nb_expertises) != 0 )
        {
	        ksort( $nb_expertises );
	        return  reset($expertises);
        }
	    else
	        return null;
	}

    ////////////////////////////////////////////

    // TODO - Supprimer cette fonction, car maintenant on peut renvoyer PLUSIEURS experts
    //        Remplacée par Version::getExperts()

//    public function getExpert(Session $session = null)
//    {
//	    if( $session == null ) $session = Functions::getSessionCourante();
//
//	    $expertise  = $this->getOneExpertise($session);
//	    if( $expertise  == null )
//	        return null;
//	    else
//	        return $expertise->getExpert();
//    }

    ///////////////////////////////////////////////
    // TODO - Supprimer cette fonction, car maintenant on peut renvoyer PLUSIEURS exprites
    //        Remplacée par Version::getExpertise()
    //

//    public function getOneExpertise(Session $session)
//    {
//	    if( $this->isProjetTest() )
//	        $version    =   $this->derniereVersion();
//	    else
//	        $version    =   AppBundle::getRepository(Version::class)->findOneBy(['session' => $session, 'projet' => $this]);
//
//	    if( $version != null )
//        {
//	        $expertises =   $version->getExpertise()->toArray();
//	        if( $expertises !=  null )
//            {
//	            $expertise  =   current( $expertises );
	            //Functions::debugMessage(__METHOD__ . " expertise = " . Functions::show( $expertise )
	            //    . " expertises = " . Functions::show( $expertises ));
//	            return $expertise;
//            }
	        //else
	        //    Functions::noticeMessage(__METHOD__ . " version " . $version . " n'a pas d'expertise !");
//        }
//	    else
//	        Functions::noticeMessage(__METHOD__ . " projet " . $this . " n'a pas de version pour la session " . $session . " !");
//
//	    return null;
//    }

	/************************************
	* calcul de la consommation et du quota d'une ressource à une date donnée
	* N'est utilisée que par les méthodes de cette classe
	*
    * Renvoie [ $conso, $quota ]
    * NOTE - Si la table est chargée à 8h00 du matin, toutes les consos de l'année courante seront = 0 avant 8h00
    *
    ************/
	private function getConsoDate($ressource, \DateTime $date)
	{
        $loginName = strtolower($this->getIdProjet());
        $conso     = 0;
        $quota     = 0;
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

	/***********************
	* calcul de la consommation et du quota d'une ressource (cpu, gpu, work_space, etc.)
	*
	* param $ressource: La ressource
	* param $annee_ou_date    : L'année ou la date
	*       Si $annee_ou_date==null                -> On considère la date du jour
	*       Si $annee_ou_date est annee courante   -> On considère la date du jour
	*       Si $annee_ou_date est une autre année  -> On considère le 31 décembre de $annee_ou_date
	*       Si $annee_ou_date est une DateTime     -> On considère la date
	*       ATTENTION Si $annee_ou_date est un string qui représente une date... ça va merder !
	*
	* S'il n'y a pas de données à la date considérée (par exemple si c'est dans le futur), on renvoie [0,0]
	*
	* Renvoie [ $conso, $quota ]
	*
	* NOTE - Si la table est chargée à 8h00 du matin, toutes les consos seront mesurées à hier
	*        Si on utilise avant 8h00 du matin toutes les consos sont à 0 !
	*
	*******************/
    public function getConsoRessource($ressource, $annee_ou_date=null)
    {
		//return [0,0];
        $annee_ou_date_courante = GramcDate::get()->showYear();
        if ($annee_ou_date==$annee_ou_date_courante || $annee_ou_date===null)
        {
            $date  = GramcDate::get();
		}
		elseif (is_object($annee_ou_date))
		{
			$date = $annee_ou_date;
		}
		else
		{
            $date = new \DateTime( $annee_ou_date . '-12-31');
        }
        return $this->getConsoDate($ressource, $date);
    }

	/*******
	* calcul de la consommation cumulée d'une ou plusieurs ressources dans un intervalle de dates données
	*
	* params: $ressources -> Un tableau de ressources
	*         $dates      -> Un tableau de deux strings représentant des dates [debut,fin(
	*
	* Retourne: La somme de la consommation pour les deux ressources dans l'intervalle de dates considéré
	*
	* Prérequis: Il ne doit pas y avoir eu de remise à zéro dans l'intervalle
	*
	* TODO - Diminuer le nombre de requêtes SQL avec une seule requête plus complexe
	*
	***********************/
	public function getConsoIntervalle($ressources, $dates)
	{
		if ( ! is_array($ressources) || ! is_array($dates))
		{
			Functions::createException(__METHOD__ . ":" . __LINE__ . " Erreur interne - \$ressources ou \$dates n'est pas un array");
		}
		if (count( $ressources ) < 1 || count( $dates ) < 2)
		{
			Functions::createException(__METHOD__ . ":" . __LINE__ . " Erreur interne - \$ressources ou \$dates est un array trop petit");
		}

		$debut = new \DateTime($dates[0]);
		$fin   = new \DateTime($dates[1]);

		$conso_debut = 0;
		$conso_fin   = 0;
		foreach ($ressources as $r)
		{
			//Functions::debugMessage('koukou '.$r.' '.$dates[0].' '.print_r($this->getConsoDate($r,$debut),true).$dates[1].' '.print_r($this->getConsoDate($r,$fin),true));
			$conso_debut += $this->getConsoDate($r,$debut)[0];
			$conso_fin   += $this->getConsoDate($r,$fin)[0];
		}
		// $conso_fin peut être nulle si la date de fin est dans le futur !
		// Dans ce cas on renvoie 0
		return ($conso_fin)?$conso_fin-$conso_debut:0;
	}

	/*******************
	* calcul de la consommation "calcul" à une date donnée ou pour une année donnée
	* REMPLACE L'ANCIENNE FONCTION getConso()
	*
	* Retourne: la consommation cpu + gpu à la date ou pour l'année donnée
	*           Ne retourne pas le quota
	*
	*************************/
    public function getConsoCalcul($annee_ou_date)
    {
		$conso_gpu = $this->getConsoRessource('gpu',$annee_ou_date);
		$conso_cpu = $this->getConsoRessource('cpu',$annee_ou_date);
		return $conso_gpu[0] + $conso_cpu[0];
    }

	/*******************
	* calcul de la consommation "calcul" à une date donnée ou pour une année donnée, en pourcentage du quota
	* REMPLACE L'ANCIENNE FONCTION getConsoP()
	*
	* Retourne: la consommation cpu + gpu à la date ou pour l'année donnée
	*           en %age du quota cpu
	*
	*************************/
    public function getConsoCalculP($annee_ou_date=null)
    {
		$conso_gpu = $this->getConsoRessource('gpu',$annee_ou_date);
		$conso_cpu = $this->getConsoRessource('cpu',$annee_ou_date);
		if ( $conso_cpu[1] <= 0 )
		{
			return 0;
		}
		else
		{
			return 100.0*($conso_gpu[0] + $conso_cpu[0])/$conso_cpu[1];
		}
    }

	/***************
	* Renvoie la consommation calcul (getConsoCalcul() de l'année et du mois
	*
	* params: $annee (2019 ou 19)
	*         $mois (0..11)
	*
	* Retourne: La conso cpu+gpu, ou 0 si le mois se situe dans le futur
	*
	**************************/
	public function getConsoMois($annee,$mois)
	{
		$now = GramcDate::get();
		$annee_courante = $now->showYear();
		$mois_courant   = $now->showMonth();
		$mois += 1;	// 0..11 -> 1..12 !

		// 2019 - 2000 !
		if ( ($annee==$annee_courante || abs($annee-$annee_courante)==2000) && $mois==$mois_courant)
		{
			$conso_fin = $this->getConsoCalcul($now);
		}
		else
		{
			// Pour décembre on mesure la consomation au 31 car il y a risque de remise à zéro le 1er Janvier
			// Du coup on ignore la consommation du 31 Décembre...
			if ($mois==12)
			{
				$d = strval($annee)."-12-31";
				$conso_fin = $this->getConsoCalcul(new \DateTime($d));
				//AppBundle::getLogger()->error("koukou1 " . $this->getIdProjet() . "$d -> $conso_fin");
			}
			// Pour les autres mois on prend la conso du 1er du mois suivant
			else
			{
				$m = strval($mois + 1);
				$conso_fin = $this->getConsoCalcul(new \DateTime($annee.'-'.$m.'-01'));
			}
		}

		// Pour Janvier on prend zéro, pas la valeur au 1er Janvier
		// La remise à zéro ne se fait jamais le 1er Janvier
		if ($mois==1)
		{
			$conso_debut = 0;
		}
		else
		{
			$conso_debut = $this->getConsoCalcul(new \DateTime("$annee-$mois-01"));
		}
		if ($conso_fin>$conso_debut)
		{
			return $conso_fin-$conso_debut;
		}
		else
		{
			return 0;
		}
	}

	/*
	 * Renvoie le quota seul (pas la conso) des ressources cpu
	 *
	 * param : $annee ou $date (cf getConsoRessource)
     * return: La consommation "calcul" pour l'année
     *
     */
    public function getQuota($annee=null)
    {
		$conso_cpu = $this->getConsoRessource('cpu',$annee);
		return $conso_cpu[1];
    }

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
    
    ///////////////////////////////////////////////////
    
	public function getEtat()
    {
		return $this->getEtatProjet();
	}


}

