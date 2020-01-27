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

namespace AppBundle\Repository;

use AppBundle\Utils\Etat;
use AppBundle\Utils\Functions;
use AppBundle\AppBundle;

use AppBundle\Entity\Projet;
use AppBundle\Entity\Individu;
use AppBundle\Entity\Version;
use AppBundle\Entity\CollaborateurVersion;
use AppBundle\Entity\Session;


/**
 * ProjetRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProjetRepository extends \Doctrine\ORM\EntityRepository
{
    public function findNonTermines()
    {
         return $this->getEntityManager()
                   ->createQuery('SELECT p FROM AppBundle:Projet p WHERE ( NOT p.etatProjet = :termine AND NOT p.etatProjet = :annule)')
                   ->setParameter('termine', Etat::getEtat('TERMINE'))
                   ->setParameter('annule', Etat::getEtat('ANNULE') )
                   ->getResult();
    }

	/*
	 * Retourne le nombre de projets dans un état donné pour un type de projets donné
	 *
     * param $type = si null, compte tous les projets dans un état donné
     *               si entier>0, compte tous les projets dans un état donné et d'un type donné
	 */

	private function countEtatPrj($etat)
	{
         return $this->getEntityManager()
         ->createQuery
        ('SELECT count(p) FROM AppBundle:Projet p WHERE ( p.etatProjet = :etat )')
        ->setParameter('etat', Etat::getEtat($etat) )
        ->getSingleScalarResult();
	}

    public function countEtatPrjType($etat,$type=1)
    {
         return $this->getEntityManager()
         ->createQuery
        ('SELECT count(p) FROM AppBundle:Projet p WHERE ( p.etatProjet = :etat AND p.typeProjet = :type )')
        ->setParameter('etat', Etat::getEtat($etat) )
        ->setParameter('type', $type)
        ->getSingleScalarResult();
    }

    public function countEtat($etat, $type=null)
    {
		if ($type === null )
		{
			return $this->countEtatPrj($etat);
		}
		else
		{
			return $this->countEtatPrjType($etat,$type);
		}
	}

    public function countEtatTest($etat) { return $this->countEtat($etat,'2'); }

    /*
     * Retourne le nombre total de projets
     *
     * param $type = si null, compte tous les projets
     *               si entier>0, compte tous les projets d'un type donné
     *
     */

    private function countAllPrj()
    {
         return $this->getEntityManager()
         ->createQuery
        ('SELECT count(p) FROM AppBundle:Projet p WHERE ( NOT p.etatProjet = :etat )')
        ->setParameter('etat', Etat::getEtat('ANNULE') )
        ->getSingleScalarResult();
	}
    private function countAllPrjType($type)
    {
         return $this->getEntityManager()
         ->createQuery
        ('SELECT count(p) FROM AppBundle:Projet p WHERE ( NOT p.etatProjet = :etat AND p.typeProjet = :type)')
        ->setParameter('etat', Etat::getEtat('ANNULE') )
        ->setParameter(':type', $type)
        ->getSingleScalarResult();
    }
    public function countAll($type=null)
    {
		if ($type === null )
		{
			return $this->countAllPrj();
		}
		else
		{
			return $this->countAllPrjType($type);
		}
	}
    public function countAllTest(){ return $this->countAll('2'); }

	/*****************************************
	 * Renvoie la liste des projets non terminés dans lesquels un individu est collaborateur.
	 * Ne s'intéresse que à la dernière version du projet: si je ne suis plus collaborateur d'un projet les anciennes versions
	 * sont supprimées !
	 * 
	 * $params = $id_individu: L'individu
	 * 
	 * $responsable, $collaborateur : deux flags
	 * 		true		true		=> Renvoie tous les projets dans lesquels $id_individu est collaborateur OU responsable
	 * 		false		true		=> Renvoie tous les projets dans lesquels $id_individu est collaborateur mais PAS RESPONSABLE
	 * 		true		false		=> Renvoie tous les projets dans lesquels $id_individu est responsable
	 * 		false		false		=> Renvoie un tableau vide
	 * 
	 * return = Une collection de projets
	 * 
	 ***********************************************************************************************************/
    public function getProjetsCollab($id_individu, $responsable = true, $collaborateur= true)
    {
	    $dql  = 'SELECT p FROM AppBundle:Projet p, AppBundle:CollaborateurVersion cv, AppBundle:Version v, AppBundle:Individu i ';
	    $dql .= ' WHERE  ( v = p.versionDerniere AND i.idIndividu = :id_individu ';
	    
		// false/false = renvoie une collection vide
	    if( $responsable === false && $collaborateur === false ) return [];

		// true/true = On ne s'occupe pas de la colonne cv.responsable
	    if( ! ($responsable===true && $collaborateur===true))
	    {
			$dql .= ' AND cv.responsable = :responsable ';
		}
	    $dql .= ' AND cv.version =  v AND cv.collaborateur = i ';
	    $dql .= ' AND NOT p.etatProjet = :termine ';
	    $dql .= ' AND NOT  v.etatVersion = :annule AND NOT p.etatProjet = :annule ';
	    $dql .= ' ) ORDER BY p.versionDerniere DESC';

	    $query = $this->getEntityManager()
			->createQuery( $dql )
			->setParameter('id_individu', $id_individu )
			->setParameter('termine', Etat::getEtat('TERMINE'))
			->setParameter('annule', Etat::getEtat('ANNULE'));
		 if( ! ($responsable===true && $collaborateur===true))
		 {
			 $query->setParameter('responsable', $responsable===true?1:0);
		 }

	    return $query->getResult();
    }

    // la liste des projets avec un état $libelle_etat où un individu est collaborateur ou responsable
    public function get_projets_etat($id_individu, $libelle_etat)
    {
        $dql  = 'SELECT p FROM AppBundle:Projet p, AppBundle:CollaborateurVersion cv, AppBundle:Version v, AppBundle:Individu i ';
        $dql .= ' WHERE  ( p = v.projet AND i.idIndividu = :id_individu ';
        $dql .= ' AND cv.version =  v AND cv.collaborateur = i ';
        $dql .= ' AND  p.etatProjet = :etat ';
        $dql .= ' ) ORDER BY p.versionDerniere DESC';

        $code_etat  =   Etat::getEtat($libelle_etat);
        if( $code_etat == null )
		{
            Functions::errorMessage('ProjetRepository :  get_projets_etat : état :' . $libelle_etat . ' inconnu');
            return [];
		}

        return $this->getEntityManager()
             ->createQuery( $dql )
             ->setParameter('id_individu', $id_individu )
             ->setParameter('etat', $code_etat)
             ->getResult();
    }

    ///////////////////////////////////////////////////////////////////////////

    // la liste des projets qu'un individu peut renouveller :  il est collaborateur ou responsable et le projet n'est ni annulé, ni terminé

    public function get_projets_renouvelables_AJETER()
    {
        $moi = AppBundle::getUser();
        if( ! $moi instanceof \AppBundle\Entity\Individu ) return [];

        $dql  = 'SELECT p FROM AppBundle:Projet p, AppBundle:CollaborateurVersion cv, AppBundle:Version v, AppBundle:Individu i ';
        $dql .= ' WHERE  ( p = v.projet AND i.idIndividu = :id_individu ';
        $dql .= ' AND cv.version =  v AND cv.collaborateur = i ';
        $dql .= ' AND  NOT p.etatProjet = :annule AND NOT p .etatProjet = :termine ';
        $dql .= ' ) ORDER BY p.versionDerniere DESC';

        return $this->getEntityManager()
             ->createQuery( $dql )
             ->setParameter('id_individu', $moi->getIdIndividu() )
             ->setParameter('annule', Etat::ANNULE)
             ->setParameter('termine', Etat::TERMINE)
             ->getResult();
    }

	/*
	 * Renvoie le numéro de projet le plus élevé créé une année donnée et pour un type donné.
	 * params: $annee -> L'année (2 chiffres: 17, 18, 19)
	 *         $type  -> Le type (cf. le paramètre prj_prefix)
	 *                   A chaque type est associé un préfixe, pour chaque préfixe il y a un espace de numérotation
	 * Return: Le numéro de plus haut rang, en représentation chaîne de caractère, 3 caractères
	 *         Si aucun projet n'est trouvé retourne '000'
	 *         Si $type n'existe pas ou si le préfixe associé est '', retourne null et log un message
	 *
	 */
    public function getLastNumProjet($annee, $type)
    {
	    $em         = $this->getEntityManager();
	    $prefixes   = AppBundle::getParameter('prj_prefix');
	    if ( !isset ($prefixes[$type]) || $prefixes[$type]==="" )
	    {
			Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Pas de préfixe pour le type $type. Voir le paramètre prj_prefix");
			return null;
		}
		else
		{
			$prf = $prefixes[$type];

		    $dql        =   "SELECT p.idProjet FROM AppBundle:Projet p WHERE p.idProjet LIKE :key ORDER BY p.idProjet ASC";
		    $projetIds  =   $em->createQuery( $dql )
		    					->setParameter('key', $prf . $annee .'%' )
		    					->getResult();
		    if( $projetIds == null )
			{
		        return '000';
			}
		    else
		    {
				$num    = current( end( $projetIds ) );
		        return intval(substr($num,-3));
			};
		}
	}

    /*
     * Retourne le nombre de projets tests non terminés dont je suis responsable dans la session
     *
     * NOTE - Type de projet = 2
     *
     */
    public function countProjetsTestResponsable(Individu $individu)
    {
    $dql  = 'SELECT count(p) FROM AppBundle:Projet p, AppBundle:CollaborateurVersion cv, AppBundle:Version v, AppBundle:Individu i ';
    $dql .= ' WHERE  ( p = v.projet AND i = :individu ';
    $dql .= ' AND cv.responsable = :responsable ';
    $dql .= ' AND cv.version =  v AND cv.collaborateur = i ';
    $dql .= ' AND NOT  v.etatVersion = :termine AND NOT p.etatProjet = :termine ';
    $dql .= ' AND NOT v.etatVersion = :annule AND NOT p.etatProjet = :annule ';
    $dql .= ' AND p.typeProjet = :type) ORDER BY p.versionDerniere DESC';

    return $this->getEntityManager()
         ->createQuery( $dql )
         ->setParameter('individu', $individu )
         ->setParameter('termine', Etat::getEtat('TERMINE'))
         ->setParameter('annule', Etat::getEtat('ANNULE'))
         ->setParameter('type', '2')
         ->setParameter('responsable', 1 )
         ->getSingleScalarResult();
    }

    ////////////////////////////////////////////////////////////////////////////////

     public function findProjetsAnnee($annee, $renouvel = Functions::TOUS )
    {
        $subAnnee = substr( strval($annee), -2 );
        $query = "SELECT  DISTINCT p FROM AppBundle:Version  v ";
        $query .= " JOIN AppBundle:Projet p  WITH v.projet = p ";
        $query .= " JOIN AppBundle:Session s WITH v.session = s ";
        $query .= " WHERE (  s.idSession = :anneeA OR s.idSession = :anneeB ) ";

        if( $renouvel == Functions::NOUVEAUX )
            $query .=   "AND p.idProjet LIKE :Pannee ";
        elseif( $renouvel == Functions::ANCIENS )
            $query .=   "AND NOT ( p.idProjet LIKE :Pannee ) ";

        $projets = $this->getEntityManager()
        ->createQuery( $query )
        ->setParameter('anneeA', $subAnnee . 'A' )
        ->setParameter('anneeB', $subAnnee . 'B' );

        if( $renouvel == Functions::TOUS )
            return $projets->getResult();
        else
            return $projets->setParameter('Pannee', 'P' . $subAnnee . '%')->getResult();
    }

    ////////////////////////////////////////////////////////////////////////////////

     public function findNouveauxProjetsAnnee($annee)
    {
        $subAnnee = substr( strval($annee), -2 );
        $query = "SELECT  DISTINCT p FROM AppBundle:Version  v ";
        $query .= " JOIN AppBundle:Projet p  WITH v.projet = p ";
        $query .= " JOIN AppBundle:Session s WITH v.session = s ";
        $query .= " WHERE (  s.idSession = :anneeA OR s.idSession = :anneeB ) AND p.idProjet LIKE :Pannee ";

        return $this->getEntityManager()
        ->createQuery( $query )
        ->setParameter('anneeA', $subAnnee . 'A' )
        ->setParameter('anneeB', $subAnnee . 'B' )
        ->setParameter('Pannee', 'P' . $subAnnee . '%')
        ->getResult();

    }


////////////////////////////////////////////////////////////////////////////////

     public function findAnciensProjetsAnnee($annee)
    {
        $subAnnee = substr( strval($annee), -2 );
        $query = "SELECT  DISTINCT p FROM AppBundle:Version  v ";
        $query .= " JOIN AppBundle:Projet p  WITH v.projet = p ";
        $query .= " JOIN AppBundle:Session s WITH v.session = s ";
        $query .= " WHERE (  s.idSession = :anneeA OR s.idSession = :anneeB ) AND NOT ( p.idProjet LIKE :Pannee )";

        return $this->getEntityManager()
        ->createQuery( $query )
        ->setParameter('anneeA', $subAnnee . 'A' )
        ->setParameter('anneeB', $subAnnee . 'B' )
        ->setParameter('Pannee', 'P' . $subAnnee . '%')
        ->getResult();

    }
    //////////////////////////////////////////////////////////////////////////////////

     public function countProjetsAnnee($annee)
    {
        $subAnnee = substr( strval($annee), -2 );
        $query = "SELECT COUNT ( DISTINCT p ) FROM AppBundle:Version  v ";
        $query .= " JOIN AppBundle:Projet p  WITH v.projet = p ";
        $query .= " JOIN AppBundle:Session s WITH v.session = s ";
        $query .= " WHERE (  s.idSession = :anneeA OR s.idSession = :anneeB ) ";

        return $this->getEntityManager()
        ->createQuery( $query )
        ->setParameter('anneeA', $subAnnee . 'A' )
        ->setParameter('anneeB', $subAnnee . 'B' )
        ->getSingleScalarResult();

    }

    //////////////////////////////////////////////////////////////////////////////////
    //
    // $renouvel = Functions::TOUS, Functions::NOUVEAUX, Functions::ANCIENS
    //
     public function heuresProjetsAnnee($annee, $renouvel = Functions::TOUS )
    {
        $subAnnee = substr( strval($annee), -2 );

        $query = "SELECT SUM(v.demHeures), SUM(v.attrHeures), SUM(v.penalHeures) FROM AppBundle:Version  v ";
        $query .= " JOIN AppBundle:Session s WITH v.session = s ";
        $query .= " JOIN AppBundle:Projet p WITH v.projet = p ";
        $query .= " WHERE (  s.idSession = :anneeA OR s.idSession = :anneeB ) ";

        if( $renouvel == Functions::NOUVEAUX )
            $query .=   "AND p.idProjet LIKE :Pannee ";
        elseif( $renouvel == Functions::ANCIENS )
            $query .=   "AND NOT ( p.idProjet LIKE :Pannee ) ";

        $heures = $this->getEntityManager()
        ->createQuery( $query )
        ->setParameter('anneeA', $subAnnee . 'A' )
        ->setParameter('anneeB', $subAnnee . 'B' );

        if( $renouvel == Functions::TOUS )
            $heures = $heures->getSingleResult();
        else
            $heures = $heures->setParameter('Pannee', 'P' . $subAnnee . '%')
                    ->getSingleResult();


        $query = "SELECT SUM(r.demHeures), SUM(r.attrHeures) FROM AppBundle:Rallonge r ";
        $query .= " JOIN AppBundle:Version v WITH r.version = v ";
        $query .= " JOIN AppBundle:Session s WITH v.session = s ";
        $query .= " JOIN AppBundle:Projet p WITH v.projet = p ";
        $query .= " WHERE (  s.idSession = :anneeA OR s.idSession = :anneeB ) AND r.validation = :true AND r.attrAccept = :true " ;

        if( $renouvel == Functions::NOUVEAUX )
            $query .=   "AND p.idProjet LIKE :Pannee ";
        elseif( $renouvel == Functions::ANCIENS )
            $query .=   "AND NOT ( p.idProjet LIKE :Pannee ) ";

         $heures_rallonges = $this->getEntityManager()
        ->createQuery( $query )
        ->setParameter('anneeA', $subAnnee . 'A' )
        ->setParameter('anneeB', $subAnnee . 'B' )
        ->setParameter('true', true );

        if( $renouvel == Functions::TOUS )
            $heures_rallonges = $heures_rallonges->getSingleResult();
        else
            $heures_rallonges = $heures_rallonges->setParameter('Pannee', 'P' . $subAnnee . '%')
                    ->getSingleResult();

        return [
                'demHeures'         => $heures[1],
                'attrHeures'        => $heures[2],
                'penalHeures'       => $heures[3],
                'rallongeDemHeures'      => $heures_rallonges[1],
                'rallongeAttrHeures'     => $heures_rallonges[2],
                ];

        //return array_merge( $heures, $heures_rallonges );


    }


}
