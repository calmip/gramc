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

namespace AppBundle\Utils;

use     AppBundle\Entity\Statut;
use     AppBundle\Entity\Etablissement;
use     AppBundle\Entity\Laboratoire;
use     AppBundle\Entity\Individu;
use     AppBundle\AppBundle;
use     AppBundle\Utils\Functions;

class IndividuForm 
{
        protected   $login;
        protected   $delete;
        protected   $responsable;
        
        protected   $mail;
        protected   $prenom;
        protected   $nom;
        
        protected   $statut;
        protected   $laboratoire;
        protected   $etablissement;
        protected   $id;

    public function __construct(Individu    $individu = null)
    {
        $this->delete       = false;
        $this->responsable  = false;
        if( $individu != null )
            {
            $this->mail             =   $individu->getMail();
            $this->prenom           =   $individu->getPrenom();
            $this->nom              =   $individu->getNom();
            $this->statut           =   $individu->getStatut();
            $this->laboratoire      =   $individu->getLabo();
            $this->etablissement    =   $individu->getEtab();
            $this->id               =   $individu->getId();
            }
    }

    public function __toString()
    {
    $output = '';
    if( $this->getDelete() == true ) $output .= 'delete:'; else $output .= 'undelete:';
    if( $this->getResponsable() == true ) $output .= 'responsable:'; else $output .= 'collaborateur:';
    $output .= $this->getMail() . ':' .  $this->getPrenom() . ':' . $this->getNom() . ':'. $this->getStatut() .':';
    $output .= $this->getLaboratoire() . ':' . $this->getEtablissement() . ':' . $this->getId();
    return $output;
    }

    public function getLogin(){ return $this->login; }
    public function setLogin($login){ $this->login = $login; return $this; }

    public function getResponsable(){ return $this->responsable; }
    public function setResponsable($responsable){ $this->responsable = $responsable; return $this; }

    public function getDelete(){ return $this->delete; }
    public function setDelete($delete){ $this->delete = $delete; return $this; }

    public function getMail(){ return $this->mail; }
    public function setMail($mail){ $this->mail = $mail; return $this; }

    public function getId(){ return $this->id; }
    public function setId($id){ $this->id = $id; return $this; }

    public function getPrenom(){ return $this->prenom; }
    public function setPrenom($prenom){ $this->prenom = $prenom; return $this; }

    public function getNom(){ return $this->nom; }
    public function setNom($nom){ $this->nom = $nom; return $this; }

    public function getStatut(){ return $this->statut; }
    public function setStatut($statut){ $this->statut = $statut; return $this; }

    public function getLaboratoire(){ return $this->laboratoire; }
    public function setLaboratoire($laboratoire){ $this->laboratoire = $laboratoire; return $this; }

    public function getEtablissement(){ return $this->etablissement; }
    public function setEtablissement($etablissement){ $this->etablissement = $etablissement; return $this; }

    //////////////////////////////////////////////////////////////

    public function nouvelIndividu()
    {
    $individu = new Individu();
    
    $individu->setMail( $this->getMail() );
    $individu->setNom( $this->getNom() );
    $individu->setPrenom( $this->getPrenom() );
    $individu->setLabo( $this->getLaboratoire() );
    $individu->setLabo( $this->getLaboratoire() );
    $individu->setEtab( $this->getEtablissement() );
    $individu->setStatut( $this->getStatut() );
    $em =   AppBundle::getManager();
    $em->persist( $individu );
    $em->flush( $individu );
    Functions::warningMessage('Utilisateur ' . $individu . '(' . $individu->getMail() . ') id(' . $individu->getIdIndividu() . ') a été créé');
    return $individu;
    }

    public function modifyIndividu( $individu )
    {
    if( $individu != null )
        {
        $em =   AppBundle::getManager();
        
        //$individu->setMail( $this->getMail() );  // mail n'est pas transmis
        
        if( $individu->getNom() != $this->getNom() )
            {
            Functions::warningMessage("Le nom de l'individu " .$individu . " id(" . $individu->getIdIndividu() . ") a été modifié de " . 
            $individu->getNom() . " vers " . $this->getNom() );
            $individu->setNom( $this->getNom() );
            $em->persist( $individu );
            }

        if( $individu->getPrenom() != $this->getPrenom() )
            {
            Functions::warningMessage("Le prénom de l'individu " .$individu . " id(" . $individu->getIdIndividu() . ") a été modifié de " . 
            $individu->getPrenom() . " vers " . $this->getPrenom() );
            $individu->setPrenom( $this->getPrenom() );
            $em->persist( $individu );
            }

        if( $individu->getLabo() != $this->getLaboratoire() )
            {
            Functions::warningMessage("Le laboratoire de l'individu " .$individu . " id(" . $individu->getIdIndividu() . ") a été modifié de " . 
            $individu->getLabo() . " vers " . $this->getLaboratoire() );
            $individu->setLabo( $this->getLaboratoire() );
            $em->persist( $individu );
            }

        if( $individu->getEtab() != $this->getEtablissement() )
            {
            Functions::warningMessage("L'établissement de l'individu " .$individu . " id(" . $individu->getIdIndividu() . ") a été modifié de " . 
            $individu->getEtab() . " vers " . $this->getEtablissement() );
            $individu->setEtab( $this->getEtablissement() );
            $em->persist( $individu );
            }

        if( $individu->getStatut() != $this->getStatut() )
            {
            Functions::warningMessage("Le statut de l'individu " .$individu . " id(" . $individu->getIdIndividu() . ") a été modifié de " . 
            $individu->getStatut() . " vers " . $this->getStatut() );
            $individu->setStatut( $this->getStatut() );
            $em->persist( $individu );
            }
        
        $em->flush();
        }
    else
        Functions::errorMessage('IndividuForm:synchronizeIndividu: Individu null !');
        
    return $individu;
    }
}
