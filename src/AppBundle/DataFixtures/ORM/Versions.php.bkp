<?php

        namespace AppBundle\DataFixtures\ORM;

        use Doctrine\Common\DataFixtures\FixtureInterface;
        use Doctrine\Common\Persistence\ObjectManager;

        use AppBundle\Entity\Projet;
        use AppBundle\Entity\Version;
        use AppBundle\Entity\Expertise;
        use AppBundle\Entity\CollaborateurVersion;

        use AppBundle\Utils\Etat;

       class Versions  implements FixtureInterface
        {
                public function load(ObjectManager $em)
                {
                    $versions = $em->getRepository(Version::class)->findAll();

                    foreach( $versions as $version )
                        {
                        // labo du responsable

                        if( $version->getPrjLLabo() == null )
                            {
                            $responsable = $version->getResponsable();
                            if( $responsable != null)
                                $version->setLaboResponsable( $responsable );
                            }

                        // codeLangage
                           
                        $codeLangage = $version->getCodeLangage();
                        $modify = false; 
                        if( $codeLangage !=  null )
                            {
                            if( preg_match("/code_ccc/", $codeLangage ) == 1 )
                                {
                                $codeLangage = trim( preg_replace("/(code_ccc)/","",$codeLangage) );
                                $version->setCodeC(true);
                                $modify = true;
                                }
                            if( preg_match("/code_cpp/", $codeLangage ) == 1 )
                                {
                                $codeLangage = trim( preg_replace("/(code_cpp)/","",$codeLangage) );
                                $version->setCodeCpp(true);
                                $modify = true;
                                }
                            if( preg_match("/code_for/", $codeLangage ) == 1 )
                                {
                                $codeLangage = trim( preg_replace("/(code_for)/","",$codeLangage) );
                                $version->setFor(true);
                                $modify = true;
                                }
                            if( preg_match("/code_autre/", $codeLangage ) == 1 )
                                {
                                $codeLangage = trim( preg_replace("/(code_autre)/","",$codeLangage) );
                                $version->setAutre(true);
                                $modify = true;
                                }
                            if( $modify == true )
                                $version->setCodeLangage( $codeLangage );
                         }
                    }

                $em->flush();  
        
            }
           

    }

       

