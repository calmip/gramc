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

// src/Validator/Constraints/PagesNumberValidator.php
namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use AppBundle\Validator\Constraints\PagesNumber;
use AppBundle\AppBundle;
use AppBundle\Utils\Functions;

/**
 * @Annotation
 */
class PagesNumberValidator extends ConstraintValidator
{
	public function validate($path, Constraint $constraint)
	{
		if( AppBundle::hasParameter('max_pdf_pages') )
		{
			$max_pdf_pages = intval(AppBundle::getParameter('max_pdf_pages'));
		}
		else
		{
			Functions::warningMessage("Le paramètre max_pdf_pages n'est pas défini");
			$max_pdf_pages = 5;
		}

	    if( $path != null && ! empty( $path ) && $path != "" )
        {
	        //$pdftext    = file_get_contents($path);
	        //$num        = preg_match_all("/\/Page\W/", $pdftext, $dummy); // calcul le nombre des pages
	        $num = exec ("pdftk " . $path . "  dump_data | grep NumberOfPages | awk '{print $2}'");
			$num = intval($num);
        }
	    else
	    {
	        $num  = 0;
		}
	    //Functions::debugMessage("PagesNumberValidator: Le fichier PDF a " . $num . " pages");
    
	    if( $num > $max_pdf_pages )
        {
	        if( $max_pdf_pages == 1 )
	        {
	            $this->context->buildViolation($constraint->message1)
	                ->setParameter('{{ pages }}', $num)
	                ->addViolation();
			}
	        else
	        {
	            $this->context->buildViolation($constraint->message2)
	                ->setParameter('{{ pages }}', $num)
	                ->setParameter('{{ max_pages }}', $max_pdf_pages )
	                ->addViolation();
			}
	        //Functions::debugMessage("PagesNumberValidator: violation ajoutée");
        }
   	 }
}
