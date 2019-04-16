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

// src/AppBundle/Security/User/GramcUserProvider.php
namespace AppBundle\Security\User;

use AppBundle\Entity\Individu;
use AppBundle\Entity\Sso;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

class GramcUserProvider  implements UserProviderInterface
{

    private $em;
    
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }


    public function loadUserByUsername($username)
    {
        //$repository = $this->doctrine->getRepository('AppBundle:Individu');
        $repository = $this->em->getRepository(Sso::class);
        if( $sso = $repository->findOneByEppn($username) )
           return $sso->getIndividu();
           
        $repository = $this->em->getRepository(Individu::class);
        if( $individu =  $repository->findOneBy( ['idIndividu' => $username]) )
            return $individu;
            
        throw new UsernameNotFoundException(
            sprintf('eppn or idIndividu
             "%s" does not exist.', $username)
        );
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof Individu) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'AppBundle\Entity\Individu';
    }
}
