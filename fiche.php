<?php

require('config.php');

require('./class/asset.class.php');
require('./lib/asset.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';


if(!$user->rights->asset->all->lire) accessforbidden();

if(isset($conf->global->MAIN_MODULE_FINANCEMENT)) {
	dol_include_once('/financement/class/affaire.class.php');
}

// Load traductions files requiredby by page
$langs->Load("companies");
$langs->Load("other");
$langs->Load("asset@asset");

$hookmanager->initHooks(array('assetcard'));

// Get parameters
_action();

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

function _action() {
	global $user,$conf;	
	$PDOdb=new TPDOdb;
	//$PDOdb->debug=true;
	
	/*******************************************************************
	* ACTIONS
	*
	* Put here all code to do according to value of "action" parameter
	********************************************************************/

	if(isset($_REQUEST['action'])) {
		switch($_REQUEST['action']) {
			case 'new':
			case 'add':
				$asset=new TAsset;
				$asset->load_liste_type_asset($PDOdb);
				$asset->set_values($_REQUEST);
                $asset->load_asset_type($PDOdb);
                
				_fiche($asset,'new');
				
				break;
			
			case 'edit'	:
				$asset=new TAsset;
                $asset->set_values($_REQUEST);
				$asset->load_liste_type_asset($PDOdb);
				$asset->load_asset_type($PDOdb);
				if(!empty($_REQUEST['id'])) $asset->load($PDOdb, $_REQUEST['id']);
			
            	_fiche($asset,'edit');
				break;
			
			case 'stock':
				$asset=new TAsset;
				$asset->load_liste_type_asset($PDOdb);
				$asset->load($PDOdb, $_REQUEST['id']);
				
				_fiche($asset,'stock');
				break;
				
			case 'save':
				//pre($_REQUEST,true);exit;
				$asset=new TAsset;
				$asset->fk_asset_type = $_REQUEST['fk_asset_type'];
				$asset->load_liste_type_asset($PDOdb);
				$asset->load_asset_type($PDOdb);//pre($asset,true);exit;
				if(!empty($_REQUEST['id'])) $asset->load($PDOdb, $_REQUEST['id']);
				
				//on vérifie que le libellé est renseigné
				if  ( empty($_REQUEST['numId']) ){
					$mesg .= '<div class="error">Le numéro Id doit être renseigné.</div>';
				}
				
				if  ( empty($_REQUEST['libelle']) ){
					$mesg .= '<div class="error">Le libellé doit être renseigné.</div>';
				}
				
				//on vérifie que les champs obligatoires sont renseignés
				foreach($asset->assetType->TField as $k=>$field) {
					if (! $field->obligatoire){
						if  ( empty($_REQUEST[$field->code]) ){
							$mesg .= '<div class="error">Le champs '.$field->libelle.' doit être renseigné.</div>';
						}
					}
				}
				
				//ensuite on vérifie ici que les champs (OBLIGATOIRE OU REMPLIS) sont bien du type attendu
				if ($mesg == ''){
					foreach($asset->assetType->TField as $k=>$field) {
						if (! $field->obligatoire || ! empty($_REQUEST[$field->code])){
							switch ($field->type){
								case 'float':
								case 'entier':
									//la conversion en entier se fera lors de la sauvegarde dans l'objet.
									if (! is_numeric($_REQUEST[$field->code]) ){
										$mesg .= '<div class="error">Le champ '.$field->libelle.' doit être un nombre.</div>';
										}
									break;
								default :
									break;
							}
						}
					}
				}
				
				
				$asset->set_values($_REQUEST);
				
				//Cas spécifique contenance_units et contenancereel_units lorsqu'égale à 0 soit kg ou m, etc
				$asset->contenance_units = ($_REQUEST['contenance_units']) ? $_REQUEST['contenance_units'] : $asset->contenance_units;
				$asset->contenancereel_units = ($_REQUEST['contenancereel_units']) ? $_REQUEST['contenance_units'] : $asset->contenancereel_units;
				
				if(!isset($_REQUEST['type_mvt'])) {
					$no_destock_dolibarr = true;
					$asset->save($PDOdb, '', "Modification manuelle", 0, false, 0, $no_destock_dolibarr);
				}
				else{
					global $conf;
					$conf->global->PRODUIT_SOUSPRODUITS = 0;
					$qty = ($_REQUEST['type_mvt'] == 'retrait') ? $_REQUEST['qty'] * -1 : $_REQUEST['qty'];
					$asset->save($PDOdb,$user,$_REQUEST['commentaire_mvt'],$qty);
				}
				
				?>
				<script language="javascript">
					document.location.href="<?php echo dirname($_SERVER['PHP_SELF'])?>/fiche.php?id=<?php echo $asset->rowid?>";					
				</script>
				<?php
				
				break;
				
			case 'clone':
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id']);
				$asset->load_liste_type_asset($PDOdb);
				$asset->load_asset_type($PDOdb);
				$asset->reinit();
				$asset->serial_number.='(copie)';
				//$PDOdb->db->debug=true;
				$asset->save($PDOdb);
				
				_fiche($asset,'view');
				
				break;
				
			case 'retour_pret':
				
				if($conf->clinomadic->enabled){
					$asset=new TAsset;
					$asset->load($PDOdb, $_REQUEST['id']);
					$asset->load_liste_type_asset($PDOdb);
					$asset->load_asset_type($PDOdb);
					$asset->retour_pret($PDOdb,$_REQUEST['fk_entrepot']);
					//$PDOdb->db->debug=true;
				}
				
				_fiche($asset,'view');
				
				break;
				
			case 'delete':
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id']);
				$asset->load_liste_type_asset($PDOdb);
				
				//$PDOdb->db->debug=true;
				$asset->delete($PDOdb);
				
				?>
				<script language="javascript">
					document.location.href="<?php echo dirname($_SERVER['PHP_SELF'])?>/liste.php?delete_ok=1";					
				</script>
				<?
				
				break;
				
			case 'view':
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id']);
				$asset->load_asset_type($PDOdb);
				$asset->load_liste_type_asset($PDOdb);
				
				_fiche($asset, 'view');
				break;
				
			case 'traceability':
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id']);
				$asset->load_asset_type($PDOdb);
				$asset->load_liste_type_asset($PDOdb);
				
				_traceability($PDOdb,$asset);
				break;
			case 'object_linked':
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id']);
				$asset->load_asset_type($PDOdb);
				$asset->load_liste_type_asset($PDOdb);
				
				_object_linked($PDOdb,$asset);
				break;
		}
		
	}
	elseif(isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
		$asset=new TAsset;
		$asset->load($PDOdb, $_REQUEST['id']);
		$asset->load_asset_type($PDOdb);
		$asset->load_liste_type_asset($PDOdb);
		
		_fiche($asset, 'view');
	}
	else{
		?>
		<script language="javascript">
			document.location.href="<?php echo dirname($_SERVER['PHP_SELF'])?>/liste.php";					
		</script>
		<?php
	}


	
	
}

function _fiche(&$asset, $mode='edit') {
global $langs,$db,$conf, $ASSET_LINK_ON_FIELD, $hookmanager;
/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/
	
	llxHeader('',$langs->trans('Asset'),'','');
	print dol_get_fiche_head(assetPrepareHead( $asset, 'asset') , 'fiche', $langs->trans('Asset'));
	
	if(isset($_REQUEST['error'])) {
		?>
		<br><div class="error">Type de mouvement incorrect</div><br>
		<?
	}
	
	// Utilisé pour afficher les bulles d'aide :: A voir si on ferais mieux pas de copier la fonction dans la class TFormCore pour éviter cette instant
	$html=new Form($db);
	
	$form=new TFormCore($_SERVER['PHP_SELF'],'formeq','POST');
	$form->Set_typeaff($mode);
	
	$form2=new TFormCore($_SERVER['PHP_SELF'],'form','POST');
	$form2->Set_typeaff('edit');
	
	echo $form->hidden('id', $asset->rowid);
	if ($mode=='new'){
		echo $form->hidden('action', 'edit');
	}
	else {echo $form->hidden('action', 'save');}
	echo $form->hidden('entity', $conf->entity);
	
	/*
	 * affichage données équipement lié à une affaire du module financement
	 */	
  	if(isset($conf->global->MAIN_MODULE_FINANCEMENT)) {
  		$PDOdb=new TPDOdb;
	 	$id_affaire = $asset->getLink('affaire')->fk_document;
		$affaire=new TFin_affaire;
		$affaire->load($PDOdb, $id_affaire, false);
 		
		$TAffaire = $affaire->get_values();
 	}
	else {
		$TAffaire = array();
	}
	 
	$TBS=new TTemplateTBS();
	$liste=new TListviewTBS('asset');
	
	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;
	
	$TAssetStock = array();
	
	foreach($asset->TStock as &$stock) {
	
		$date = $stock->get_dtcre();
		if($conf->global->ASSET_USE_PRODUCTION_ATTRIBUT){
			$TAssetStock[]=array(
				'date_cre'=>$date
				,'qty'=>$stock->qty
				,'weight_units'=>($asset->gestion_stock != 'UNIT' && $asset->assetType->measuring_units != 'unit') ? measuring_units_string($asset->contenancereel_units,$asset->assetType->measuring_units) : 'unité(s)'
				,'lot' =>$stock->lot
				,'type'=>$stock->type
			);
		}
		else{
			$TAssetStock[]=array(
				'date_cre'=>$date
				,'qty'=>$stock->qty
				,'type'=>$stock->type
			);
		}
		
		
	}
	
	?>
	<script type="text/javascript">
		$('#formeq').submit(function(){
			if($('#type_mvt').val() == ''){
				alert('Type de mouvement incorrect');
				return false;
			}
		})
	</script>
	<?php
	
	$TFields=array();
	
	?>
	<script type="text/javascript">
		$(document).ready(function(){
			
		<?php
		foreach($asset->assetType->TField as $k=>$field) {
			switch($field->type){
				case liste:
					$temp = $form->combo('',$field->code,$field->TListe,$asset->{$field->code});
					break;
				case checkbox:
					$temp = $form->combo('',$field->code,array('oui'=>'Oui', 'non'=>'Non'),$asset->{$field->code});
					break;
				case date:
					$temp = $form->calendrier('',$field->code,$asset->{$field->code});
					break;
				default:
					$temp = $form->texte('', $field->code, $asset->{$field->code}, 50,255,'','','-');
					break;
			}
			
			$TFields[$k]=array(
					'libelle'=>$field->libelle
					,'valeur'=>$temp
					//champs obligatoire : 0 = obligatoire ; 1 = non obligatoire
					,'obligatoire'=>$field->obligatoire ? 'class="field"': 'class="fieldrequired"' 
				);
			
			//Autocompletion
			if($field->type != combo && $field->type != liste){
				?>
				$("#<?php echo $field->code; ?>").autocomplete({
					source: "script/interface.php?get=autocomplete&json=1&fieldcode=<?php echo $field->code; ?>",
					minLength : 1
				});
				
				<?php
			}
		}

		//Concaténation des champs dans le libelle asset
		foreach($asset->assetType->TField as $k=>$field) {
			
			if($field->inlibelle == "oui"){
				$chaineid .= "#".$field->code.", ";
				$chaineval .= "$('#".$field->code."').val().toUpperCase()+' '+";
			}
			
		}
		$chaineval = substr($chaineval, 0,-5);
		$chaineid = substr($chaineid, 0,-2);
		?>
			$('<?php echo $chaineid; ?>').bind("keyup change", function(e) {
				$('#libelle').val(<?php echo $chaineval; ?>);
			});
		});
	</script>
	<?php
	
	/*echo '<pre>';
	print_r($TFields);
	echo '</pre>';exit;*/
	
	if($mode == "edit" && empty($asset->serial_number)){
		$asset->serial_number = $asset->getNextValue($ATMdb);
	}

	if(defined('ASSET_FICHE_TPL')){
		$tpl_fiche = ASSET_FICHE_TPL;
	}
	else{
		$tpl_fiche = "fiche.tpl.php";
	}
    
    $fk_product = (int)GETPOST('fk_product');
    if(!$fk_product) $fk_product=$asset->fk_product;
    
	if($fk_product>0){
		dol_include_once('/product/class/product.class.php');
		$product = new Product($db);
		$product->fetch($fk_product);
		$product->fetch_optionals($product->id);
	}
		
	print $TBS->render('tpl/'.$tpl_fiche
		,array(
			'assetField'=>$TFields
		)
		,array(
			'asset'=>array(
				'id'=>$asset->getId()
				/*,'reference'=>$form->texte('', 'reference', $dossier->reference, 100,255,'','','à saisir')*/ 
				,'serial_number'=>$html->textwithpicto($form->texte('', 'serial_number', $asset->serial_number, 100,255,'','','à saisir'), $langs->trans('CreateAssetFromProductErrorBadMask'), 1, 'help', '', 0, 3)
				,'produit'=>_fiche_visu_produit($asset,$mode)
				,'entrepot'=>_fiche_visu_produit($asset,$mode,'warehouse')
				,'societe'=>_fiche_visu_societe($asset,$mode)
				,'societe_localisation'=>_fiche_visu_societe($asset,$mode,"societe_localisation")
				,'lot_number'=>$html->textwithpicto($form->texte('', 'lot_number', $asset->lot_number, 100,255,'','','à saisir'), $langs->trans('CreateAssetFromProductNumLot'), 1, 'help', '', 0, 3)
				,'dluo'=>$html->textwithpicto($form->calendrier('', 'dluo', $asset->dluo), $langs->trans('AssetDescDLUO'), 1, 'help', '', 0, 3)
				,'contenance_value'=>$form->texte('', 'contenance_value',$asset->contenance_value , 12,50,'','','0.00')
				,'contenance_units'=>_fiche_visu_units($asset, $mode, 'contenance_units',-6)
				,'contenancereel_value'=>$form->texte('', 'contenancereel_value', $asset->contenancereel_value, 12,50,'','','0.00')
				,'contenancereel_units'=>_fiche_visu_units($asset, $mode, 'contenancereel_units',-6)
				,'point_chute'=>$form->texte('', 'point_chute', ($asset->getId()) ? $asset->point_chute : $asset->assetType->point_chute, 12,10,'','','à saisir')
				,'gestion_stock'=>$form->combo('','gestion_stock',$asset->TGestionStock,($asset->getId()) ? $asset->gestion_stock : $asset->assetType->gestion_stock)
				,'status'=>$form->combo('','status',$asset->TStatus,$asset->status)
				,'reutilisable'=>$form->combo('','reutilisable',array('oui'=>'oui','non'=>'non'),($asset->getId()) ? $asset->reutilisable : $asset->assetType->reutilisable)
				,'typehidden'=>$form->hidden('fk_asset_type', ($product->array_options['options_type_asset'] > 0) ? $product->array_options['options_type_asset'] : $asset->fk_asset_type )
			)
			,'stock'=>array(
				'type_mvt'=>$form2->combo('','type_mvt',array(''=>'','retrait'=>'Retrait','ajout'=>'Ajout'),'')
				,'qty'=>$form2->texte('', 'qty', '', 12,10,'','','')
				,'commentaire_mvt'=>$form2->zonetexte('','commentaire_mvt','',100)
			)
			,'assetNew' =>array(
				'typeCombo'=> count($asset->TType) ? $form->combo('','fk_asset_type',$asset->TType,$asset->fk_asset_type): "Aucun type"
				,'validerType'=>$form->btsubmit('Valider', 'validerType')
				
			)
			,'affaire'=>$TAffaire
			,'view'=>array(
				'mode'=>$mode
				,'clinomadic'=>($conf->clinomadic->enabled) ? 'view' : 'none'
				,'use_lot_in_of'=>(int)$conf->global->USE_LOT_IN_OF
				,'entrepot'=>($conf->clinomadic->enabled) ? _fiche_visu_produit($asset,'edit','warehouse') : 'none'
				,'module_financement'=>(int)isset($conf->global->MAIN_MODULE_FINANCEMENT)
				,'champs_production'=>$conf->global->ASSET_USE_PRODUCTION_ATTRIBUT
				,'liste'=>$liste->renderArray($PDOdb,$TAssetStock
					,array(
						'title'=>array(
							'date_cre'=>'Date du mouvement'
							,'qty'  =>'Quantité'
							,'weight_units' => 'Unité'
							,'lot' => 'Lot'
							,'type' => 'Commentaire'
						)
						,'link'=>array_merge($ASSET_LINK_ON_FIELD,array())
						,'liste'=>array(
							'titre'=> 'Mouvements de stock'
						)
					)
				)
			)
		)
	);
	
	$parameters = array('id'=>$asset->getId());
	$reshook = $hookmanager->executeHooks('formObjectOptions',$parameters,$asset,$mode);    // Note that $action and $object may have been modified by hook
	
	echo $form->end_form();
	// End of page
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
}

function _fiche_visu_produit(&$asset, $mode,$type='') 
{
	global $db, $conf, $langs;
	
	dol_include_once('/product/class/html.formproduct.class.php');
	
	if(($mode=='edit' || $mode=='new') && $type == "") {
		ob_start();
		$html=new Form($db);
		print $html->textwithpicto($html->select_produits((!empty($_REQUEST['fk_product']))? $_REQUEST['fk_product'] :$asset->fk_product,'fk_product','',$conf->product->limit_size,0,-1,2,'',3,array()), $langs->trans('CreateAssetFromProductDescListProduct'), 1, 'help', '', 0, 3);
		
		return ob_get_clean();
	}
	elseif($type == "warehouse"){
		ob_start();
		
		$html=new FormProduct($db);
		
		if($mode=='edit' || $mode=='new'){
			echo $html->selectWarehouses($asset->fk_entrepot,'fk_entrepot');
		}
		else{
			$resql = $db->query('SELECT label FROM '.MAIN_DB_PREFIX.'entrepot WHERE rowid = '.$asset->fk_entrepot);
			$res = $db->fetch_object($resql);
			echo $res->label;
		}
		//($asset->$name != "")? $asset->$name : $defaut
		
		return ob_get_clean();
	}
	else {
		if($asset->fk_product > 0) {
			require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
			
			$product = new Product($db);
			$product->fetch($asset->fk_product);
			return $product->getNomUrl(1) . ' - ' . $product->label;
				
			return '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$asset->fk_product.'" style="font-weight:bold;">'.img_picto('','object_product.png', '', 0).' '. $product->label.'</a>';
		} else {
			return 'Non défini';
		}
	}
}
function _fiche_visu_societe(&$asset, $mode,$type="societe") {
global $db;
	
	if($mode=='edit') {
		ob_start();	
		
		$html=new Form($db);
		if($type == "societe"){
			echo $html->select_company($asset->fk_soc,'fk_soc','',1);
		}
		else{
			echo $html->select_company($asset->fk_societe_localisation,'fk_societe_localisation','',1);
		}
		
		return ob_get_clean();
		
	}
	else {
		if($asset->fk_soc > 0) {
			require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
			
			$soc = new Societe($db);
			
			if($type == "societe"){
				$soc->fetch($asset->fk_soc);
			}
			else{
				$soc->fetch($asset->fk_societe_localisation);
			}
			
			return $soc->getNomUrl(1);
			
		} else {
			return 'Non défini';
		}
	}
}

function _fiche_visu_affaire(&$asset, $mode) {
global $db;
	
	if($mode=='edit') {
		ob_start();	
		
		$html=new Form($db);
		echo $html->select_company($asset->fk_soc,'fk_soc','',1);
		
		return ob_get_clean();
		
	}
	else {
		if($asset->fk_soc > 0) {
			require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
			
			$soc = new Societe($db);
			$soc->fetch($asset->fk_soc);	
				
			return '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$asset->fk_soc.'" style="font-weight:bold;"><img border="0" src="'.DOL_URL_ROOT.'/theme/atm/img/object_company.png"> '.$soc->nom.'</a>';
		} else {
			return 'Non défini';
		}
	}
}

function _fiche_visu_units(&$asset, $mode, $name,$defaut=-3) 
{
	global $db;
	
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	
	$ATMdb = new TPDOdb;
	$objType = new TAsset_type;
	$objType->load($ATMdb, $asset->fk_asset_type);

	if($mode=='edit')
	{
		$html=new FormProduct($db);

		if($asset->gestion_stock == 'UNIT' || $asset->assetType->measuring_units == 'unit')
		{
			return custom_load_measuring_units($name, $asset->assetType->measuring_units, ($asset->getId()) ? $asset->$name : $asset->assetType->$name); // <= maintenant même pour unit
			//return "unité(s)"; <= avant
		}
		else
		{
			return custom_load_measuring_units($name, $asset->assetType->measuring_units, ($asset->getId()) ? $asset->$name : $asset->assetType->$name);
			//($asset->$name != "")? $asset->$name : $defaut
		}
	}
	elseif($mode=='new')
	{		
		$html=new FormProduct($db);
		
		if($asset->gestion_stock == 'UNIT' || $asset->assetType->measuring_units == 'unit'){
			return custom_load_measuring_units($name, $asset->assetType->measuring_units, ($asset->getId()) ? $defaut : $asset->assetType->$name);
			//return "unité(s)";
		}
		else
		{
			return custom_load_measuring_units($name, $asset->assetType->measuring_units, ($asset->getId()) ? $defaut : $asset->assetType->$name);
			//($asset->$name != "")? $asset->$name : $defaut
		}
	}
	else
	{
		if($asset->gestion_stock == 'UNIT' || $asset->assetType->measuring_units == 'unit'){
			return "unité(s)";
		}
		else
		{
			return measuring_units_string($asset->$name, $asset->assetType->measuring_units);
		}
	}
}

function _traceability(&$PDOdb,&$asset){
	global $db,$conf,$langs;

	llxHeader('',$langs->trans('Asset'),'','');
	print dol_get_fiche_head(assetPrepareHead( $asset, 'asset') , 'traceability', $langs->trans('Asset'));
	
	$assetLot = new TAssetLot;
	$assetLot->loadBy($PDOdb, $asset->lot_number, 'lot_number');
	
	//pre($assetLot,true);
	
	?>
	<table>
		<tr>
			<td>
				<?php
					//Diagramme de traçabilité lié à la création
					$assetLot->getTraceability($PDOdb,'FROM',$assetLot->lot_number);
				?>
			</td>
			<td>
				<?php
					//Diagramme de traçabilité lié à l'utilisation
					$assetLot->getTraceability($PDOdb,'TO',$assetLot->lot_number);
				?>
			</td>
		</tr>
	</table>
	<?php

}

function _object_linked(&$PDOdb,&$asset){
	global $db,$conf,$langs;

	llxHeader('',$langs->trans('Asset'),'','');
	print dol_get_fiche_head(assetPrepareHead( $asset, 'asset') , 'object_linked', $langs->trans('Asset'));
	
	$assetLot = new TAssetLot;
	$assetLot->loadBy($PDOdb, $asset->lot_number, 'lot_number');
	
	$assetLot->getTraceabilityObjectLinked($PDOdb,$asset->getId());
	
	//pre($asset->TTraceability,true);
	//Liste des expéditions liés à l'équipement
	_listeTraceabilityExpedition($PDOdb,$assetLot);
	
	//Liste des commandes fournisseurs liés à l'équipement
	_listeTraceabilityCommandeFournisseur($PDOdb,$assetLot);
	
	//Liste des commandes clients liés à l'équipement
	_listeTraceabilityCommande($PDOdb,$assetLot);
	
	//Liste des OF liés à l'équipement
	_listeTraceabilityOf($PDOdb,$assetLot);
}

function _listeTraceabilityExpedition(&$PDOdb,&$assetLot){
	
	$listeview = new TListviewTBS($assetLot->getId());
	
	print $listeview->renderArray($PDOdb,$assetLot->TTraceabilityObjectLinked['expedition']
		,array(
			'liste'=>array(
					'titre' => "Expéditions"
			),
			'title'=>array(
				'ref' => 'Référence',
				'societe' => 'Société',
				'ref_fourn' => 'Référence fournisseur',
				'date_commande' => 'Date commande',
				'total_ht' => 'Total HT',
				'date_livraison' => 'Date de livraison',
				'status' => 'Statut',
			)
		)
	);
}

function _listeTraceabilityCommandeFournisseur(&$PDOdb,&$assetLot){
	
	$listeview = new TListviewTBS($assetLot->getId());
	
	print $listeview->renderArray($PDOdb,$assetLot->TTraceabilityObjectLinked['commande_fournisseur']
		,array(
			'liste'=>array(
				'titre' => "Commandes Fournisseurs"
			),
			'title'=>array(
				'ref' => 'Référence',
				'societe' => 'Société',
				'ref_fourn' => 'Référence fournisseur',
				'date_commande' => 'Date commande',
				'total_ht' => 'Total HT',
				'date_livraison' => 'Date de livraison',
				'status' => 'Statut',
			)
		)
	);
}

function _listeTraceabilityCommande(&$PDOdb,&$assetLot){
	
	$listeview = new TListviewTBS($assetLot->getId());
	
	print $listeview->renderArray($PDOdb,$assetLot->TTraceabilityObjectLinked['commande']
		,array(
			'liste'=>array(
				'titre' => "Commandes client"
			),
			'title'=>array(
				'ref' => 'Référence',
				'societe' => 'Société',
				'ref_client' => 'Référence client',
				'date_commande' => 'Date commande',
				'total_ht' => 'Total HT',
				'date_livraison' => 'Date de livraison',
				'status' => 'Statut',
			)
		)
	);
}

function _listeTraceabilityOf(&$PDOdb,&$assetLot){
	
	$listeview = new TListviewTBS($assetLot->getId());
	
	//pre($asset->TTraceabilityObjectLinked['of'],true);
	
	print $listeview->renderArray($PDOdb,$assetLot->TTraceabilityObjectLinked['of']
		,array(
			'liste'=>array(
				'titre' => "Ordre de Fabrication",
			),
			'title'=>array(
					'ref' => 'Référence',
					'societe' => 'Société',
					'produit_tomake' => 'Produits à créer',
					'produit_needed' => 'Produits nécessaire',
					'priorite' => 'Priorité',
					'date_lancement' => 'Date de lancement',
					'date_besoin' => 'Date du besoin',
					'status' => 'Statut',
				)
			)
	);
}

?>
