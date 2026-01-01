<?php
/**
 * @file fixedHour.ajax.php
 * @brief Script A.J.A.X exécutée pour la mise à jour dynamique du formulaire Fixed Hour mode
 *
 * $Author: o.jousset $
 * $Date: 2011-06-17 11:25:39 +0200 (ven., 17 juin 2011) $
 * $Revision: 27976 $
 */
include_once dirname( __FILE__ )."/../../../php/environnement_liens.php";

define( SEPARATOR_NAME, '||' );
define( SEPARATOR_ELEMENT, '|s|' );

// L'identifiant du dashboard doit impérativement être présent sinon on quitte
if( !isset( $_POST['dashId'] ) ){
    die();
}

// Mémorisation de l'identifiant du dash et lecture des produits associés à celui-ci
$dashId   = $_POST['dashId'];
$products = DashboardModel::getDashboardProducts( $dashId );

// Si le paramètre 'getNe' est présent (ainsi que le Network Aggregation)
if( isset( $_POST['getNe'] ) && isset( $_POST['na'] ) )
{
    $listNe = array();

    // On organise le tableau de produits de manière à analyser la Master Topo en premier
    $mtId = ProductModel::getIdMasterTopo();
    if( in_array( $mtId, $products ) )
    {
        $products = array_diff( $products, array( $mtId ) ); // On retire le Master Topo
        array_unshift( $products, $mtId ); // On l'empile en premier
    }
 

    // Pour tous les produits, on récupère d'abord tous les identifiants
    $masterTopoNE = array();
    foreach ( $products as $oneProduct )
    {
        // Lecture en base de tous les NE du produit (du NA en paramètre)
        $tmptNe = NeModel::getNeFromProducts( $_POST['na'], array( $oneProduct ) );
        if( count( $tmptNe[$_POST['na']] ) > 0 )
        {
            // Si il s'agit du Master Topo, on mémorise les NE
            if( $oneProduct == $mtId )
            {
                $masterTopoNE = $tmptNe[$_POST['na']];
            }
            else if( count( $masterTopoNE ) > 0 )
            {
                // Lecture des élément mappés pour ce produit et cette NA
                $mappedElt = NeModel::getMapped( array( $_POST['na'] ), true, $oneProduct );
                if( count( $mappedElt ) > 0 )
                {
                    // Si des élements sont mappés pour ce produit, on regarde si
                    // il sont présent dans la liste du Master Topo, si oui on les
                    // retire de la liste
                    foreach( $mappedElt[$_POST['na']] as $src=>$mapped )
                    {
                        if( in_array( $mapped, $masterTopoNE ) )
                        {
                            $tmptNe[$_POST['na']] = array_diff( $tmptNe[$_POST['na']], array( $src ) );
                        }
                    }
                }
            }

            // Pour tous les NE, on récupère les labels (avec gestion du mapping)
            $neIdLabel = NeModel::getLabel( $tmptNe[$_POST['na']], $_POST['na'], $oneProduct );
            foreach( $neIdLabel as $id=>$label )
            {
                // Concaténation de l'ID et du Label
                $listNe []= $id.SEPARATOR_NAME.$label;
            } 
        }
    }    
    echo implode( SEPARATOR_ELEMENT, $listNe );
}

// On retourne la liste des Raw/Kpi sous la forme prodId||famId||Name||Label
if( isset( $_POST['getRawKpi'] ) && isset( $_POST['na'] ) && isset( $_POST['ne'] ) )
{
    $firstAxisProducts = array(); // Stoque les id produits ayant le NE 1er axe
    $thirdAxisProducts = array(); // Stoque les id produits ayant le NE 3ème axe

    // On recherche les produits du Dash ayant les NE selectionnés
    //  - Gestion du 1er axe
    foreach( $products as $oneId )
    {
        // Le Network Element existe t-il ?
        if( NeModel::exists( $_POST['ne'], $_POST['na'], $oneId ) )
        {
            $firstAxisProducts []= $oneId;
        }
        
        // Si il n'existe pas, y-a t'il un mapping vers lui ?
        else
        {
            $mapping = NeModel::getMapping( array( $_POST['na'] => array( $_POST['ne'] ) ), false, $oneId );
            if( $mapping !== false && count( $mapping ) > 0 )
            {
                $firstAxisProducts []= $oneId;
            }
        }
    }

    // - Gestion du 3ème axe
    if( isset( $_POST['na3'] ) && isset( $_POST['ne3'] ) )
    {
        foreach( $products as $oneId )
        {
            if( NeModel::exists( $_POST['ne3'], $_POST['na3'], $oneId ) )
            {
                $thirdAxisProducts []= $oneId;
            }
        }
        $availablePoducts = array_intersect( $firstAxisProducts, $thirdAxisProducts );
    }
    else
    {
        $availablePoducts = $firstAxisProducts;
    }

    // $availablePoducts contient maintenant la liste des produits ayant les NE
    // Pour tous ces produits on recherche les familles ayant les NA sélectionnée
    $availableFamilies = array();
    foreach( $availablePoducts as $oneProduct )
    {
        $naArray = array( $_POST['na'] );
        if( isset( $_POST['na3'] ) )
        {
            $naArray []= $_POST['na3'];
        }
        foreach( FamilyModel::getFamiliesFromNa( $naArray, $oneProduct ) as $oneFam )
        {         
            $famModel = new FamilyModel( $oneFam, $oneProduct );
            $bhInfo = $famModel->getBHInfos();
            if( count( $bhInfo ) > 0 )
            {
                if( strtolower( $bhInfo['bh_indicator_type'] ) == 'kpi' )
                {
                    $rawKpiModel = new KpiModel();
                    $field       = 'kpi_name';
                }
                else
                {
                    $rawKpiModel = new RawModel();
                    $field       = 'edw_field_name';
                }
                $bhRawKpiId    = $rawKpiModel->getIdFromSpecificField( $field, $bhInfo['bh_indicator_name'], Database::getConnection( $oneProduct ) );
                $bhRawKpiLabel = $rawKpiModel->getLabelFromId( $bhRawKpiId, Database::getConnection( $oneProduct ) );
                
                // Si le label est vide, on prend le nom (identifiant)
                if( strlen( trim( $bhRawKpiLabel ) ) === 0 )
                {
                    $bhRawKpiLabel = $bhInfo['bh_indicator_name'];
                }

                // On ajoute le type du champ en suffixe
                $bhRawKpiLabel .= " ({$bhInfo['bh_indicator_type']})";

                // On retourne le code module plutôt que le code
                $module = get_sys_global_parameters( 'module', 0, $oneProduct );
                if( $module == 'def' )
                {
                    $module = get_sys_global_parameters( 'old_module', 0, $oneProduct );
                }

                if( $module !== 0 )
                {
                    $availableFamilies []= $module.SEPARATOR_NAME.$oneFam.SEPARATOR_NAME.$bhRawKpiLabel;
                }
            }
            else
            {
                // Pas de BH configuré pour cette famille
            }
        }
    }
    echo implode( SEPARATOR_ELEMENT, $availableFamilies );
}
