<?php
	require(__DIR__.'/config.default.php');
	
	define('ASSET_FICHE_TPL','fiche.tpl.php');
	//define('ASSET_LISTE_TYPE','latoxan');
	//define('ASSET_LIST_FIELDS', "e.rowid as 'ID',e.serial_number, p.rowid as 'fk_product',p.ref as 'N° Nomenclature', p.label, s.nom as 'nom', e.date_cre as 'Date de Création'");
	//define('TEMPLATE_OF','templateOF.odt');

	//Pour afficher la s�lection d'un équipement dans une liste déroulante lors de l'ajout d'une ligne de commande
	//dolibarr_set_const($db, 'USE_ASSET_IN_ORDER', 0);
	
	//Permet de définir des liens sur les champs de l'équipement
	$ASSET_LINK_ON_FIELD = array();
	
	/*$ASSET_LINK_ON_FIELD = array(
					"lot_number"=>'<a href="http://'.$_SERVER['SERVER_NAME'].'/ophis/batch.php?action=edit&ID=@val@" target="_blank">@val@</a>',
					"lot"=>'<a href="http://'.$_SERVER['SERVER_NAME'].'/ophis/batch.php?action=edit&ID=@val@" target="_blank">@val@</a>'
					);*/
