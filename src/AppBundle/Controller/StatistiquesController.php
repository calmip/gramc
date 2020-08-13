<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\AppBundle;
use AppBundle\Entity\Projet;
use AppBundle\Entity\Version;
use AppBundle\Entity\Individu;
use AppBundle\Entity\Session;
use AppBundle\Entity\Laboratoire;
use AppBundle\Entity\Etablissement;
use AppBundle\Entity\Statut;

use AppBundle\Utils\Functions;
use AppBundle\Utils\Menu;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Esxtension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use JpGraph\JpGraph;

/**
 * Statistiques controller.
 *
 * @Route("statistiques")
 * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
 */
class StatistiquesController extends Controller
{
    /**
     * @Route("/symfony", name="homepage")
     * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
     * @Method({"GET","POST"})
     */
    public function homepageAction(Request $request)
    {

    return $this->render('default/base_test.html.twig');

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }

   /**
     * @Route("/", name="statistiques")
     * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
     */
    public function indexAction(Request $request)
    {

   $data = Functions::selectAnnee($request);
   $versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($data['annee']);
   $annee   =   $data['annee'];

    /*
    $stats = $this->statistiques( $versions, "getAcroLaboratoire" );
    $stats = $this->statistiques( $versions, "getAcroEtablissement" );
    $stats = $this->statistiques( $versions, "getAcroThematique" );
    $stats = $this->statistiques( $versions, "getPrjSousThematique" );
    $stats = $this->statistiques( $versions, "getAcroMetaThematique" );

    $acros          =   $stats['acros'];
    $num_projets    =   $stats['num_projets'];
    $dem_heures     =   $stats['dem_heures'];
    $attr_heures    =   $stats['attr_heures'];
    $conso          =   $stats['conso'];
    */

    $menu[] =   Menu::statistiques_laboratoire( $data['annee'] );
    $menu[] =   Menu::statistiques_etablissement( $data['annee'] );
    $menu[] =   Menu::statistiques_thematique( $data['annee'] );
    $menu[] =   Menu::statistiques_metathematique( $data['annee'] );
    $menu[] =   Menu::statistiques_collaborateur( $data['annee'] );
    $menu[] =   Menu::statistiques_repartition( $data['annee'] );

    $projets_renouvelles    =   AppBundle::getRepository(Projet::class)->findProjetsAnnee($data['annee'], Functions::ANCIENS );
    $projets_nouveaux       =   AppBundle::getRepository(Projet::class)->findProjetsAnnee($data['annee'], Functions::NOUVEAUX );

    $conso_nouveaux         =   0;
    foreach( $projets_nouveaux as $projet )
        $conso_nouveaux =   $conso_nouveaux +   $projet->getConsoCalcul($annee);

    $conso_renouvelles         =   0;
    foreach( $projets_renouvelles as $projet )
        $conso_renouvelles      =   $conso_renouvelles  +   $projet->getConsoCalcul($annee);

    $num_projets_renouvelles    =   count($projets_renouvelles);
    $num_projets_nouveaux       =   count($projets_nouveaux);
    $num_projets                =   $num_projets_renouvelles    +   $num_projets_nouveaux;

    $heures_tous                =   AppBundle::getRepository(Projet::class)->heuresProjetsAnnee($annee, Functions::TOUS );
    $heures_renouvelles         =   AppBundle::getRepository(Projet::class)->heuresProjetsAnnee($annee, Functions::ANCIENS );
    $heures_nouveaux            =   AppBundle::getRepository(Projet::class)->heuresProjetsAnnee($annee, Functions::NOUVEAUX );
    //return new Response( Functions::show( $heures_nouveaux ));

    $versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($data['annee']);

    $individus  =   [];
    $individus_uniques  =   [];

    foreach( $versions as $version )
        {
        $collaborateurs_versions    =   $version->getCollaborateurVersion();
        foreach( $collaborateurs_versions as $collaborateurVersion )
            {
            $individu       =   $collaborateurVersion->getCollaborateur();
            if( $individu == null ) continue;
            $idIndividu     =   $individu->getIdIndividu();

            if( count( $collaborateurs_versions ) == 1 )
                $individus_uniques[$idIndividu] =   $individu;

            $individus[$idIndividu] =   $individu;
           }
        }

    return $this->render('statistiques/index.html.twig',
            [
            'form'  =>  $data['form']->createView(),
            'annee' =>  $data['annee'],
            'menu'  =>  $menu,
            'num_projets'   =>  $num_projets,
            'num_projets_renouvelles'   =>  $num_projets_renouvelles,
            'num_projets_nouveaux'      =>  $num_projets_nouveaux,
            'heures_tous'               =>  $heures_tous,
            'heures_renouvelles'        =>  $heures_renouvelles,
            'heures_nouveaux'           =>  $heures_nouveaux,
            'num_individus'             =>  count( $individus   ),
            'num_individus_uniques'     =>  count( $individus_uniques   ),
            'conso_nouveaux'            =>  $conso_nouveaux,
            'conso_renouvelles'         =>  $conso_renouvelles,

      /*      'acros' =>  $acros,
            'num_projets'   =>  $num_projets,
            'dem_heures'    =>  $dem_heures,
            'attr_heures'   =>  $attr_heures,
            'conso'         =>  $conso, */
            ]);
    }


    /**
     * @Route("/{annee}/repartition", name="statistiques_repartition")
     * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
     */
    public function repartitionAction(Request $request, $annee)
    {
    $data = Functions::selectAnnee($request, $annee);
    $versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($data['annee']);

    $collaborateurs = [];
    $comptes = [];
    foreach ( $versions as $version )
        {
        $collaborateurVersions = $version->getCollaborateurVersion();
        $compte = 0;
        $personne = 0;
        foreach( $collaborateurVersions as $collaborateurVersion )
            {
            if( $collaborateurVersion->getCollaborateur() == null )
                {
                Functions::errorMessage(__METHOD__ . ':' . __LINE__ . " Collaborateur null dans un collaborateurVersion de la version "  . $version );
                continue;
                }
            $personne++;
            if( $collaborateurVersion->getLogin()== true )
                $compte++;
            }

        $idProjet = $version->getProjet()->getIdProjet();
        $collaborateurs[ $personne ][] = $idProjet;
        $comptes[ $compte ][] = $idProjet;
        //if( $compte != $personne ) return new Response('KO');
        }

    $count_collaborateurs = [];
    foreach( $collaborateurs as $personnes => $projets )
        $count_collaborateurs[ $personnes ] = count( array_unique( $projets ) );
    ksort( $count_collaborateurs );

    $count_comptes = [];
    foreach( $comptes as $compte => $projets )
        $count_comptes[ $compte ] = count( array_unique( $projets ) );
    ksort( $count_comptes );

    //return new Response( Functions::show( $collaborateurs ) );

    return $this->render('statistiques/repartition.html.twig',
        [
        //'histogram_collaborateurs' => $this->histogram("Collaborateurs par projet pour l'année " + $data['annee'], $collaborateurs),
        //'histogram_comptes' => $this->histogram("Comptes par projet pour l'année " + $data['annee'], $comptes),
        'histogram_comptes' => $this->line("Répartition des projets  par nombre de projets pour l'année " + $data['annee'], $count_comptes ),
        'histogram_collaborateurs' => $this->line("Répartition des projets par nombre de collaborateurs pour l'année " + $data['annee'], $count_collaborateurs ),
        'collaborateurs'    => $count_collaborateurs,
        'comptes'           => $count_comptes,
        'projets_sans_compte'   => $comptes[ 0 ],
        'annee'             => $data['annee'],
        'form'  =>  $data['form']->createView(),
        ]);
    }

    /**
     * @Route("/{annee}/collaborateur", name="statistiques_collaborateur")
     * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
     */
    public function collaborateurAction(Request $request, $annee)
    {
    $data = Functions::selectAnnee($request, $annee);
    $versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($data['annee']);

    //return new Response( Functions::show( count($versions) ) );

    $statuts    = [];
    foreach( AppBundle::getRepository(Statut::class)->findAll() as $statut )
        {
        $statuts[$statut->getIdStatut()] = [ 'statut' => $statut, 'individus' => [], 'count' => 0 ];
        }

    $laboratoires   =   [];
    foreach( AppBundle::getRepository(Laboratoire::class)->findAll() as $laboratoire )
        {
        $laboratoires[$laboratoire->getIdLabo()] = [ 'laboratoire' => $laboratoire, 'individus' => [], 'count' => 0 ];
        }

    $etablissements   =   [];
    foreach( AppBundle::getRepository(Etablissement::class)->findAll() as $etablissement )
        {
        $etablissements[$etablissement->getIdEtab()] = [ 'etablissement' => $etablissement, 'individus' => [], 'count' => 0 ];
        }

	$individusIncomplets = [];
    $individus           =   [];
    foreach( $versions as $version )
        {
        foreach( $version->getCollaborateurVersion() as $collaborateurVersion )
            {
            $individu       =  $collaborateurVersion->getCollaborateur();
            $statut         =  $collaborateurVersion->getStatut();
            $laboratoire    =  $collaborateurVersion->getLabo();
            $etablissement  =  $collaborateurVersion->getEtab();

			// Si un responsable de projet a inséré un collaborateur hors session d'attribution, on ne l'a pas obligé
			// à remplir ces trois champs. Il ne pourra cependant pas renouveler son projet s'il ne les complète pas
			// TODO - Arranger ce truc - cf. ticket #223
			if ($statut==null || $laboratoire==null || $etablissement==null)
			{
				$individusIncomplets[] = $collaborateurVersion;
				continue;
			}

            $statuts[$statut->getId()]['individus'][$individu->getIdIndividu()] =  $individu;
            $laboratoires[$laboratoire->getId()]['individus'][$individu->getIdIndividu()] =  $individu;
            $etablissements[$etablissement->getId()]['individus'][$individu->getIdIndividu()] =  $individu;

            $individus[$individu->getIdIndividu()][$collaborateurVersion->getId()] =
                        [
                        'statut'=>$statut,
                        'laboratoire'=>$laboratoire,
                        'etablissement'=>$etablissement,
                        'version'=>$version,
                        'individu'=>$individu,
                        ];
           }
        }

    $anomaliesStatut         =   [];
    $anomaliesLaboratoire    =   [];
    $anomaliesEtablissement  =   [];

    $changementStatut           =   [];
    $changementLaboratoire      =   [];
    $changementEtablissement    =   [];

    foreach( $individus as $key => $individuArray )
        foreach( $individuArray as  $key1 => $array1 )
            foreach( $individuArray as $key2 =>  $array2 )
                {
                $version1   =  $array1['version'];
                $version2   =  $array2['version'];

                $statut1    =  $array1['statut'];
                $statut2    =  $array2['statut'];

                $laboratoire1   =  $array1['laboratoire'];
                $laboratoire2   =  $array2['laboratoire'];

                $etablissement1 =  $array1['etablissement'];
                $etablissement2 =  $array2['etablissement'];

                if( $key1 < $key2   && $statut1 != $statut2 )
                    {
                    if( $version1->typeSession() == $version2->typeSession() )
                        {
                        $anomaliesStatut[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'statut1'   =>  $statut1,
                                            'statut2'   =>  $statut2,
                                            ];
                        }
                    else
                        {
                        $changementStatut[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'statut1'   =>  $statut1,
                                            'statut2'   =>  $statut2,
                                            ];
                        }
                    }

                if( $key1 < $key2   && $laboratoire1 != $laboratoire2 )
                    if( $version1->typeSession() == $version2->typeSession() )
                        $anomaliesLaboratoire[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'laboratoire1'   =>  $laboratoire1,
                                            'laboratoire2'   =>  $laboratoire2,
                                            ];
                    else
                        $changementLaboratoire[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'laboratoire1'   =>  $laboratoire1,
                                            'laboratoire2'   =>  $laboratoire2,
                                            ];

                if( $key1 < $key2   && $etablissement1 != $etablissement2 )
                    if(  $version1->typeSession() == $version2->typeSession() )
                        $anomaliesEtablissement[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'etablissement1'   =>  $etablissement1,
                                            'etablissement2'   =>  $etablissement2,
                                            ];
                    else
                        $changementEtablissement[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'etablissement1'   =>  $etablissement1,
                                            'etablissement2'   =>  $etablissement2,
                                            ];



                }

    // return new Response( Functions::show(  [ $anomaliesStatut, $anomaliesLaboratoire, $anomaliesEtablissement ] ) );

    $total  =    0;
    $image_data     =   [];
    $acros          =   [];
    foreach( $statuts as $key => $statut )
        {
        $count              =   count( $statut['individus'] );
        $statuts[$key]['count']    =   $count;
        if( $count > 0 )
            {
            $total              =   $total  +   $count;
            $image_data[]       =   $count;
            $acros[]            =   $statut['statut']->__toString();
            }
        else
            unset ( $statuts[$key] );
        }
    $statuts_total         =   $total;
    $image_statuts = $this->camembert( $image_data, $acros, "Nombre de collaborateurs par statut" );
    foreach( $statuts as $key => $statut )
        $statuts[$key]['percent']   =  100 * $statuts[$key]['count'] / $statuts_total ;


    $total  =    0;
    $image_data     =   [];
    $acros          =   [];
    foreach( $laboratoires as $key=>$laboratoire )
        {
        $count                  =   count( $laboratoire['individus'] );
        $laboratoires[$key]['count']   =   $count;
        if( $count > 0 )
            {
            $total                  =   $total  +   $count;
            $image_data[]       =   $count;
            $acros[]            =   $laboratoire['laboratoire']->getAcroLabo();
            }
        else
            unset ( $laboratoires[$key] );
        }
    $laboratoires_total      =   $total;
    $image_laboratoires = $this->camembert( $image_data, $acros, "Nombre de collaborateurs par laboratoire" );
    foreach( $laboratoires as $key=>$laboratoire )
        $laboratoires[$key]['percent']  =  100 * $laboratoires[$key]['count'] / $laboratoires_total;



    $total  =    0;
    $image_data     =   [];
    $acros          =   [];
    foreach( $etablissements as $key=>$etablissement )
        {
        $count                  =   count( $etablissement['individus'] );
        $etablissements[$key]['count'] =   $count;
        if( $count > 0 )
            {
            $total                  =   $total  +   $count;
            $image_data[]       =   $count;
            $acros[]            =   $etablissement['etablissement']->__toString();
            }
        else
            unset ( $etablissements[$key] );
        }
    $etablissements_total    =   $total;
    $image_etablissements = $this->camembert( $image_data, $acros, "Nombre de collaborateurs par établissement" );
    foreach( $etablissements as $key=>$etablissement )
        $etablissements[$key]['percent']  =  100 * $etablissements[$key]['count'] / $etablissements_total;

    //return new Response( Functions::show( $statuts ) );

     return $this->render('statistiques/collaborateur.html.twig',
            [
            'form'  =>  $data['form']->createView(),
            'annee' =>  $data['annee'],
            'statuts'                      => $statuts,
            'laboratoires'                 => $laboratoires,
            'etablissements'               => $etablissements,
            'statuts_total'                => $statuts_total,
            'laboratoires_total'           => $laboratoires_total,
            'etablissements_total'         => $etablissements_total,
            'image_statuts'                => $image_statuts,
            'image_laboratoires'           => $image_laboratoires,
            'image_etablissements'         => $image_etablissements,
            'individusIncomplets'          => $individusIncomplets,
            'anomaliesStatut'              => $anomaliesStatut,
            'anomaliesLaboratoire'         => $anomaliesLaboratoire,
            'anomaliesEtablissement'       => $anomaliesEtablissement,
            'countChangementStatut'        =>  count( $changementStatut ),
            'countChangementLaboratoire'   =>  count( $changementLaboratoire ),
            'countChangementEtablissement' =>  count( $changementEtablissement ),
            ]);

    }
    /**
     * @Route("/{annee}/laboratoire", name="statistiques_laboratoire")
     * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
     */
    public function laboratoireAction(Request $request, $annee)
    {

    $data = Functions::selectAnnee($request, $annee);
    //$versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($data['annee']);

    $stats = $this->statistiques( $annee, "getAcroLaboratoire", "laboratoire" );

    return $this->render('statistiques/laboratoire.html.twig',
            [
            'form'  =>  $data['form']->createView(),
            'annee' =>  $data['annee'],
            'acros' =>  $stats['acros'],
            'num_projets'   =>  $stats['num_projets'],
            'dem_heures'    =>  $stats['dem_heures'],
            'attr_heures'   =>  $stats['attr_heures'],
            'conso'         =>  $stats['conso'],
            //'projets'       =>  $stats['projets'],
            'image_projets' =>  $stats['image_projets'],
            'image_dem'     =>  $stats['image_dem'],
            'image_attr'    =>  $stats['image_attr'],
            'image_conso'   =>  $stats['image_conso'],
            ]);
    }

    /**
     * @Route("/{annee}/etablissement", name="statistiques_etablissement")
     * @Security("has_role('ROLE_OBS')")
     */
    public function etablissementAction(Request $request, $annee)
    {

    $data = Functions::selectAnnee($request, $annee);
    $versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($data['annee']);

    $stats = $this->statistiques( $versions, "getAcroEtablissement", "établissement" );

    return $this->render('statistiques/etablissement.html.twig',
            [
            'form'  =>  $data['form']->createView(),
            'annee' =>  $data['annee'],
            'acros' =>  $stats['acros'],
            'num_projets'   =>  $stats['num_projets'],
            'dem_heures'    =>  $stats['dem_heures'],
            'attr_heures'   =>  $stats['attr_heures'],
            'conso'         =>  $stats['conso'],
            'projets'       =>  $stats['projets'],
            'image_projets'         =>  $stats['image_projets'],
            'image_dem'         =>  $stats['image_dem'],
            'image_attr'         =>  $stats['image_attr'],
            'image_conso'         =>  $stats['image_conso'],
            ]);
    }

    /**
     * @Route("/{annee}/thematique", name="statistiques_thematique")
     * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
     */
    public function thematiqueAction(Request $request, $annee)
    {

    $data = Functions::selectAnnee($request, $annee);
    $versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($data['annee']);

    $stats = $this->statistiques( $versions, "getAcroThematique", "thématique" );

    return $this->render('statistiques/thematique.html.twig',
            [
            'form'  =>  $data['form']->createView(),
            'annee' =>  $data['annee'],
            'acros' =>  $stats['acros'],
            'num_projets'   =>  $stats['num_projets'],
            'dem_heures'    =>  $stats['dem_heures'],
            'attr_heures'   =>  $stats['attr_heures'],
            'conso'         =>  $stats['conso'],
            'projets'       =>  $stats['projets'],
            'image_projets'         =>  $stats['image_projets'],
            'image_dem'         =>  $stats['image_dem'],
            'image_attr'         =>  $stats['image_attr'],
            'image_conso'         =>  $stats['image_conso'],
            ]);
    }

    /**
     * @Route("/{annee}/metathematique", name="statistiques_metathematique")
     * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
     */
    public function metathematiqueAction(Request $request, $annee)
    {

    $data = Functions::selectAnnee($request);
    $versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($data['annee']);

    $stats = $this->statistiques( $versions, "getAcroMetaThematique", "metathématique" );

    return $this->render('statistiques/metathematique.html.twig',
            [
            'form'  =>  $data['form']->createView(),
            'annee' =>  $data['annee'],
            'acros' =>  $stats['acros'],
            'num_projets'   =>  $stats['num_projets'],
            'dem_heures'    =>  $stats['dem_heures'],
            'attr_heures'   =>  $stats['attr_heures'],
            'conso'         =>  $stats['conso'],
            'projets'       =>  $stats['projets'],
            'image_projets'         =>  $stats['image_projets'],
            'image_dem'         =>  $stats['image_dem'],
            'image_attr'         =>  $stats['image_attr'],
            'image_conso'         =>  $stats['image_conso'],
            ]);
    }

    /**
     * @Route("/{annee}/metathematique_csv", name="statistiques_metathematique_csv")
     * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
     */
    public function metathematiqueCSVAction(Request $request, $annee)
    {
    $sortie =   "Statistiques de l'année ". $annee . " par metathematique \n";
    $ligne  =   ["metathematique","Nombre d'heures demandées","Nombre d'heures attribuées","Consommation"];
    $sortie .= join("\t",$ligne) . "\n";

    $versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($annee);
    $stats = $this->statistiques( $versions, "getAcroMetaThematique", "metathématique" );

    foreach( $stats['acros'] as $acro )
        {
        $ligne = [ '"' . $acro . '"', $stats['dem_heures'][$acro], $stats['attr_heures'][$acro], $stats['conso'][$acro] ];
        $sortie .= join("\t",$ligne) . "\n";
        }

    return Functions::csv($sortie,'statistiques_metathematique.csv');
    }

    /**
     * @Route("/{annee}/thematique_csv", name="statistiques_thematique_csv")
     * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
     */
    public function thematiqueCSVAction(Request $request, $annee)
    {
    $sortie =   "Statistiques de l'année ". $annee . " par thematique \n";
    $ligne  =   ["thematique","Nombre d'heures demandées","Nombre d'heures attribuées","Consommation"];
    $sortie .= join("\t",$ligne) . "\n";

    $versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($annee);
    $stats = $this->statistiques( $versions, "getAcroThematique", "thématique" );

    foreach( $stats['acros'] as $acro )
        {
        $ligne = [ '"' . $acro . '"', $stats['dem_heures'][$acro], $stats['attr_heures'][$acro], $stats['conso'][$acro] ];
        $sortie .= join("\t",$ligne) . "\n";
        }

    return Functions::csv($sortie,'statistiques_thematique.csv');
    }

    /**
     * @Route("/{annee}/laboratoire_csv", name="statistiques_laboratoire_csv")
     * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
     */
    public function laboratoireCSVAction(Request $request, $annee)
    {
    $sortie =   "Statistiques de l'année ". $annee . " par laboratoire \n";
    $ligne  =   ["laboratoire","Nombre d'heures demandées","Nombre d'heures attribuées","Consommation"];
    $sortie .= join("\t",$ligne) . "\n";

    //$versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($annee);
    $stats = $this->statistiques( $annee, "getAcroLaboratoire", "laboratoire" );

    foreach( $stats['acros'] as $acro )
        {
        $ligne = [ '"' . $acro . '"', $stats['dem_heures'][$acro], $stats['attr_heures'][$acro], $stats['conso'][$acro] ];
        $sortie .= join("\t",$ligne) . "\n";
        }

    return Functions::csv($sortie,'statistiques_laboratoire.csv');
    }

    /**
     * @Route("/{annee}/etablissement_csv", name="statistiques_etablissement_csv")
     * @Security("has_role('ROLE_OBS') or has_role('ROLE_PRESIDENT')")
     */
    public function etablissementCSVAction(Request $request, $annee)
    {
    $sortie =   "Statistiques de l'année ". $annee . " par établissement \n";
    $ligne  =   ["établissement","Nombre d'heures demandées","Nombre d'heures attribuées","Consommation"];
    $sortie .= join("\t",$ligne) . "\n";

    $versions = AppBundle::getRepository(Version::class)->findVersionsAnnee($annee);
    $stats = $this->statistiques( $versions, "getAcroEtablissement", "établissement" );

    foreach( $stats['acros'] as $acro )
        {
        $ligne = [ '"' . $acro . '"', $stats['dem_heures'][$acro], $stats['attr_heures'][$acro], $stats['conso'][$acro] ];
        $sortie .= join("\t",$ligne) . "\n";
        }

    return Functions::csv($sortie,'statistiques_etablissement.csv');
    }

	/*
	 * $annee   = L'année considérée
	 * $critere = Un nom de getter de Version permettant de consolider partiellement les données
	 *            Le getter renverra un acronyme (laboratoire, établissement etc)
	 *            (ex = getAcroLaboratoire())
	 * $titre   = Titre du camembert
	 */
    private function statistiques( $annee, $critere, $titre = "Titre" )
    {
		$stats = Functions::projetsParCritere($annee, $critere);
		$acros       = $stats[0];
		$num_projets = $stats[1];
		$dem_heures  = $stats[3];
		$attr_heures = $stats[4];
		$conso       = $stats[5];
		
	    $image_data = [];
	    foreach( $acros as $key => $acro )
	        $image_data[$key]   =  $num_projets[$acro];
	    $image_projets = $this->camembert( $image_data, $acros, "Nombre de projets par " . $titre );
	
	    $image_data = [];
	    foreach( $acros as $key => $acro )
	        $image_data[$key]   =  $dem_heures[$acro];
	    $image_dem = $this->camembert( $image_data, $acros, "Nombre d'heures demandées par " . $titre );
	
	    $image_data = [];
	    foreach( $acros as $key => $acro )
	        $image_data[$key]   =  $attr_heures[$acro];
	    $image_attr = $this->camembert( $image_data, $acros, "Nombre d'heures attribuées par " . $titre );
		
	    $image_data = [];
	    foreach( $acros as $key => $acro )
	        $image_data[$key]   =  $conso[$acro];
	    $image_conso = $this->camembert( $image_data, $acros, "Consommation par " . $titre );

	    return ["acros"         => $acros, 
				"num_projets"   => $num_projets,
	            "dem_heures"    => $dem_heures,
	            "attr_heures"   => $attr_heures,
	            "conso"         => $conso,
	            "image_projets" => $image_projets,
	            "image_dem"     => $image_dem,
	            "image_attr"    => $image_attr,
	            "image_conso"   => $image_conso ];
    }

    private function statistiques_AJETER( $versions, $critere, $titre = "Titre" )
    {
	    $projets    =   []; // information si deux versions dans l'année ou juste une
	    foreach( $versions as $version )
        {
        $idProjet   =   $version->getProjet()->getIdProjet();

        if( ! array_key_exists( $idProjet, $projets ) )
            $projets[$idProjet] = false; // une version dans l'année (ou on traite la première)
        else
            $projets[$idProjet] = true; //  deux versions l'année (et c'est la seconde)

        }

	    $acros          =   [];
	    $acro_projets   =   []; // contient acro pour chaque projet
	    $num_projets    =   []; // contient nombre de projets pour chaque acro
	    $dem_heures     =   [];
	    $attr_heures    =   [];
	    $conso          =   [];
	
	    foreach( $versions as $version )
        {
	        $acro       =   $version->$critere();
	        if( $acro == "" ) $acro = "Autres";
	        $idProjet   =   $version->getProjet()->getIdProjet();
	
	        if( ! in_array( $acro, $acros ) )
	            $acros[]    =   $acro;
	
	        if( ! array_key_exists( $idProjet, $acro_projets ) ) // aucune autre version du projet n'est encore comptée
            {
	            $acro_projets[$idProjet] = $acro;
	            if( array_key_exists( $acro, $num_projets ) )
	                $num_projets[$acro] =  $num_projets[$acro] + 1;
	            else
	                $num_projets[$acro] = 1;
	
            }
	
	        elseif( $acro_projets[$idProjet] != $acro ) // une autre version du projet est déjà comptée mais le labo du projet a changé
            {
	            // il n'y a que le nombre de projets qui change, la consommation n'est pas comptée dans ce cas
	            if( array_key_exists( $acro, $num_projets ) )
	                $num_projets[$acro] =  $num_projets[$acro] + 1;
	            else
	                $num_projets[$acro] = 1;
            }
	
	        if( array_key_exists( $acro, $dem_heures ) )
	            $dem_heures[$acro]  =  $dem_heures[$acro] + $version->getDemHeures();
	        else
	            $dem_heures[$acro]  =  $version->getDemHeures();
	
	        if( array_key_exists( $acro, $attr_heures ) )
	            $attr_heures[$acro]  =  $attr_heures[$acro] + $version->getAttrHeures();
	        else
	            $attr_heures[$acro]  =  $version->getAttrHeures();
	
 	        if( $projets[$idProjet] == true )
	            $consoV  =   0; // Seconde version = Conso déjà comptée !
	        else
	            $consoV  =   $version->getConsoCalcul(); // une seule version dans l'année
	
	        if( array_key_exists( $acro, $conso ) )
	            $conso[$acro]       =  $conso[$acro] + $consoV;
	        else
	            $conso[$acro]       =  $consoV;

        }

    asort( $acros );


    $image_data = [];
    foreach( $acros as $key => $acro )
        $image_data[$key]   =  $num_projets[$acro];
    $image_projets = $this->camembert( $image_data, $acros, "Nombre de projets par " . $titre );

    $image_data = [];
    foreach( $acros as $key => $acro )
        $image_data[$key]   =  $dem_heures[$acro];
    $image_dem = $this->camembert( $image_data, $acros, "Nombre d'heures demandées par " . $titre );

    $image_data = [];
    foreach( $acros as $key => $acro )
        $image_data[$key]   =  $attr_heures[$acro];
    $image_attr = $this->camembert( $image_data, $acros, "Nombre d'heures attribuées par " . $titre );


    $image_data = [];
    foreach( $acros as $key => $acro )
        $image_data[$key]   =  $conso[$acro];
    $image_conso = $this->camembert( $image_data, $acros, "Consommation par " . $titre );

    return [ "acros" => $acros, "acro_projets" => $acro_projets, "num_projets" => $num_projets, "projets" => $projets,
             "dem_heures" =>  $dem_heures , "attr_heures" => $attr_heures,  "conso" => $conso,
             "image_conso" => $image_conso,  "image_projets" => $image_projets, "image_dem" => $image_dem, "image_attr" => $image_attr ];

    }

    ///////////////////////////////////////////

    private function camembert( $data, $acros, $titre = "Titre" )
    {
    $seuil = array_sum( $data ) * 0.01;
    $autres = 0;
    foreach( $data as $key => $value )
        {
        if( $data[$key] <= $seuil || $acros[$key] == "Autres" ||  $acros[$key] == "Autre" || $acros[$key] == "autres" || $acros[$key] == "autre" || $acros[$key] == "")
            {
            $autres = $autres + $data[$key];
            unset( $data[$key] );
            unset( $acros[$key] );
            }
        }

    if( $autres > 0 )
        {
        $data[]     =   $autres;
        $acros[]    =   "Autres";
        }

    if( array_sum( $data ) == 0 )
        {
        $data[]     =   1;
        $acros[]    =   "Aucune valeur";
        }

    $data = array_values( $data );
    $acros = array_values( $acros );

    // bibliothèque graphique

    $x = 900;
    $y = 1000;
    $xcenter=0.3;
    $ycenter=0.9;
    $xlegend = 0.02;
    $ylegend = 0.80;
    \JpGraph\JpGraph::load();
    \JpGraph\JpGraph::module('pie');
    // Création du graph Pie. Ce dernier peut être mise en cache  avec PieGraph(300,300,"SomCacheFileName")
    $graph = new \PieGraph($x,$y);
    $graph->SetMargin(60,60,50,50);
    //$graph->SetMargin(160,160,150,150);
    $graph->SetMarginColor("silver");
    $graph->SetFrame(true,'silver');
    //$graph->legend->SetFrameWeight(1);
     $graph->legend->SetFrameWeight(1);

    //      $graph->SetShadow();

    // Application d'un titre au camembert
    $graph->title->Set($titre);
    $graph->legend->Pos($xlegend,$ylegend);

    // Création du graphe
    $p1 = new \PiePlot($data);
    $p1->SetLegends($acros);
    $p1->SetCenter($xcenter,$ycenter);
    $graph->Add($p1);

    //~ $color = array();


    $p1->SetTheme('earth');
    //$p1->SetSliceColors(color);
    // .. Création effective du fichier

    ob_start();
    $graph->Stroke();
    $image_data = ob_get_contents();
    ob_end_clean();

    $image = base64_encode($image_data);
    return $image;
    }


    ////////////////////////////////////////////////////////////////////////////////


    private function histogram( $titre, $donnees, $legende = "abc" )
    {
    // Initialisation du graphique
    \JpGraph\JpGraph::load();
    \JpGraph\JpGraph::module('bar');
    \JpGraph\JpGraph::module('pie');
	$graph = new \BarGraph(700, 500);
    return null;

	// Echelle lineaire ('lin') en ordonnee et pas de valeur en abscisse ('text')
	// Valeurs min et max seront determinees automatiquement
	$graph->setScale("textlin");
	$graph->SetMargin(60,60,50,50);
	$graph->SetMarginColor("silver");
	$graph->SetFrame(true,'silver');
	$graph->legend->SetFrameWeight(1);

	// Creation de l'histogramme
	$histo = new \BarPlot($donnees);
	// Ajout de l'histogramme au graphique
	$graph->add($histo);
	//~ $graphe->xaxis->scale->SetAutoMin($legende[0]);
	$graph->xaxis->SetTickLabels($legende);
	// Ajout du titre du graphique
	$graph->title->set($titre);

    ob_start();
    $graph->Stroke();
    $image_data = ob_get_contents();
    ob_end_clean();

    $image = base64_encode($image_data);
    return $image;
    }

    ////////////////////////////////////////////////////////////////////////////////

    private function line($titre, $donnees)
    {
    \JpGraph\JpGraph::load();
    \JpGraph\JpGraph::module('line');

    $x  =   [];
    $y  =   [];
    foreach( $donnees as $key => $value )
        {
        $x[]    =   $key;
        $y[]    =   $value;
        }


    //$legende = [ '','jan','fév','mar','avr','mai','juin','juil','août','sept','oct','nov','déc' ];
    $donnees = [ 1, 3, 4, 3 ];
    $legende = [ 1, 2, 3, 4 ];

    $graph = new \Graph(800,400);
	$graph->SetScale('textlin');
	$graph->SetMargin(60,60,50,50);
	$graph->SetMarginColor("silver");
	$graph->SetFrame(true,'silver');
	$graph->title->Set($titre);
	$graph->xgrid->Show();
	$graph->xaxis->SetTickLabels($x);

    //$constante_limite = new \LinePlot($quota);
	//$constante_limite->SetColor('#FF0000');
	//$constante_limite->SetLegend('Quotas');
    //$graph->Add($constante_limite);

    $courbe = new \LinePlot($y);
    $courbe->SetLegend('Projets');
    $courbe->SetColor('#2E64FE');

    //aide a l'affichage du graphique : affiche 10% de la conso max en plus
	//$aff_limite = new \LinePlot($affichage_max);
	//$aff_limite->SetColor('#FFFFFF');

    $graph->Add($courbe);
    //$graph->Add($constante_limite);
    //$graph->Add($aff_limite);

    $graph->legend->SetFrameWeight(1);
	$graph->legend->SetLayout(1); // LEGEND_HOR
	$graph->legend->SetPos(0.5,0.98,'center','bottom');

    ob_start();
    $graph->Stroke();
    $image_data = ob_get_contents();
    ob_end_clean();

    $image = base64_encode($image_data);
    return $image;

    //$twig = new \Twig_Environment( new \Twig_Loader_String(), array( 'strict_variables' => false ) );
    //$body = $twig->render( '<img src="data:image/png;base64, {{ EncodedImage }}" />' ,  [ 'EncodedImage' => $image,      ] );

    //return new Response($body);
    }
}
