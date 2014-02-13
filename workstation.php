<?php

	require('config.php');
	require('./class/asset.class.php');
	require('./class/ordre_fabrication_asset.class.php');
	
	dol_include_once('/core/class/html.form.class.php');
	
	
	$action=__get('action','list');
	$ATMdb=new TPDOdb;
	
	llxHeader('',$langs->trans('workstation'),'','');
	
	switch($action) {
		
		case 'save':
			$ws=new TAssetWorkstation;
			$ws->load($ATMdb, __get('id',0,'integer'));
			$ws->set_values($_REQUEST);
			$ws->save($ATMdb);
			
			_fiche($ATMdb, $ws);
			
			break;
		
		case 'edit':
			$ws=new TAssetWorkstation;
			$ws->load($ATMdb, __get('id',0,'integer'));
			
			_fiche($ATMdb, $ws,'edit');
			
			break;
		
		case 'delete':
		
			$ws=new TAssetWorkstation;
			$ws->load($ATMdb, __get('id',0,'integer'));
			
			$ws->delete($ATMdb);
			
			_liste($ATMdb);
			
			break;
		
		case 'new':
			
			$ws=new TAssetWorkstation;
			$ws->set_values($_REQUEST);
			
			_fiche($ATMdb, $ws,'edit');
			
			break;
		
		case 'list':
			
			_liste($ATMdb);
			
			break;
		
		
	}
	
	llxFooter();


function _fiche(&$ATMdb, &$ws, $mode='view') {
	global $db;

	$TBS=new TTemplateTBS;
	
	$form=new TFormCore('auto', 'formWS', 'post', true);
	
	$form->Set_typeaff( $mode );
	
	echo $form->hidden('action','save');
	echo $form->hidden('id',$ws->getId());
	
	$formDoli=new Form($db);
	
	$TForm=array(
		'libelle'=>$form->texte('', 'libelle', $ws->libelle,80,255)
		,'nb_hour_max'=>$form->texte('', 'nb_hour_max', $ws->nb_hour_max,3,3)
		,'fk_usergroup'=>$formDoli->select_dolgroups($ws->fk_usergroup, 'fk_usergroup')
		,'id'=>$ws->getId()
	);
	
	
	print $TBS->render('./tpl/workstation.tpl.php',
		array()
		,array(
			'ws'=>$TForm
			,'view'=>array(
				'mode'=>$mode
			)
		)
		
	);
	
	$form->end();
}

function _liste(&$ATMdb) {
global $conf;
	/*
	 * Liste des poste de travail de l'entité
	 */
	
	$l=new TListviewTBS('listWS');

	$sql= "SELECT ws.libelle,ws.fk_usergroup,ws.nb_hour_max 
	
	FROM ".MAIN_DB_PREFIX."asset_workstation ws LEFT OUTER JOIN ".MAIN_DB_PREFIX."llx_asset_workstation_product wsp ON (wsp.fk_asset_workstation=ws.rowid)
	 LEFT OUTER JOIN ".MAIN_DB_PREFIX."asset_workstation_of wsof ON (wsof.fk_asset_workstation=ws.rowid)
	 
	WHERE entity=".$conf->entity;

	$fk_product = __get('id_product',0,'integer');
	if($fk_product>0)$sql.=" AND wsp.fk_product=".$fk_product;

	$fk_assetOF = __get('id_assetOF',0,'integer');
	if($fk_assetOF>0)$sql.=" AND wsp.fk_assetOF=".$fk_assetOF;


	print $l->render($ATMdb, $sql,array(
	
	
	));
	
}
