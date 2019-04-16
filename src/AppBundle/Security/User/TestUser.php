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

namespace AppBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;

class TestUser implements UserInterface, EquatableInterface
{
    public function __toString() {return 'Test User';}

    // implementation EquatableInterface

    public function isEqualTo(UserInterface $user)
    {        
        if ($user instanceof TestUser) return true;
        else return false;
    }

    // implementation UserInterface
    
    public function getUsername()   {   return 'test';  }
    public function getSalt()       {   return null;    }
    public function getPassword()   {   return 'test';  }
    public function eraseCredentials()  {}    
    public function getRoles() { return ['ROLE_USER','ROLE_GLOBAL_ACCESS','ROLE_GLOBAL_ACCESS_W','ROLE_ALLOWED_TO_SWITCH']; }

    // implemenation Gramc
    
    public function getPrenom()     {return 'Test'; }
    public function getNom()        {return 'Test'; }
    public function idIndividu()    {return 0; }
}
