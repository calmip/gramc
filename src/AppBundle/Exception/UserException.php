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

namespace AppBundle\Exception;

use Symfony\Component\Security\Core\User\UserInterface;
//use Symfony\Component\Security\Core\Exception\AccountStatusException;
//use Symfony\Component\Security\Core\Exception\AuthenticationException;

class UserException extends \RuntimeException
//class UserException extends AuthenticationException
{

private     $user = null;
    
public function __construct(UserInterface $user, $message = null )
    {
        parent::__construct( $message );
        $this->user = $user;
    }

public function getUser()
    {
        return $this->user;
    }

public function setUser(UserInterface $user)
    {
        $this->user = $user;
        return $this;
    }

}
