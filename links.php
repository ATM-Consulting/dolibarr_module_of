<?php

require('config.php');

require('./class/asset.class.php');
require('./lib/asset.lib.php');

if(isset($conf->global->MAIN_MODULE_FINANCEMENT)) {
	dol_include_once('/financement/class/affaire.class.php');
}

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");

$PDOdb=new TPDOdb;

$asset=new TAsset;
$asset->load($PDOdb, $_REQUEST['fk_asset']);

llxHeader('','Liens avec l\'équipement','','');

_links($PDOdb, $asset);


$PDOdb->close();

llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');

function _links(&$PDOdb, &$asset) {
global $conf, $langs;
	
	$TBS=new TTemplateTBS();
	
	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;
	
	$TLink=array();
	foreach($asset->TLink as &$link) {
	
		$date = $link->get_dtcre();
		
		if($link->type_document=='affaire' && isset($conf->global->MAIN_MODULE_FINANCEMENT)) {
			$affaire=new TFin_affaire;
			$affaire->load($PDOdb, $link->fk_document);
			$reference ='<a href="'.DOL_URL_ROOT_ALT.'/financement/affaire.php?id='.$affaire->getId().'">'. $affaire->reference .'</a>';
			$date = $affaire->get_date('date_affaire');
		}
	
		$TLink[]=array(
			'type'=>$langs->trans( $link->type_document )
			,'fk_document'=>$link->fk_document
			,'reference'=>$reference
			,'date'=>$date
		);
		
		
	}
	
	$liste=new TListviewTBS('asset');
	
	print $TBS->render('./tpl/links.tpl.php',
		array()
		,array(
			'view'=>array(
				'head'=>dol_get_fiche_head(assetPrepareHead($asset)  , 'links', 'Equipement')
				,'liste'=>$liste->renderArray($PDOdb,$TLink
					,array(
						'hide'=>array('fk_document')
						,'title'=>array(
							'type'=>'Type de lien'
							,'reference'=>'Numéro du document'
							,'date'=>'Date du document'
						)
					)
				)
			
			)
			
			
		)
		
	);
	
	
	
	
}
