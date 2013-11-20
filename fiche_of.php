<?php

require('config.php');

require('./class/asset.class.php');
require('./class/ordre_fabrication_asset.class.php');
require('./lib/asset.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


if(!$user->rights->asset->all->lire) accessforbidden();
if(!$user->rights->asset->of->write) accessforbidden();


// Load traductions files requiredby by page
$langs->load("other");
$langs->load("asset@asset");

// Get parameters
_action();

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

function _action() {
	global $user;	
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
				$assetOf=new TAssetOF;
				$assetOf->set_values($_REQUEST);
				_fiche($assetOf,'new');

				break;

			case 'edit'	:
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $_REQUEST['id']);

				_fiche($assetOf,'edit');
				break;

			case 'save':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->set_values($_REQUEST);
				$assetOf->save($PDOdb);
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/fiche_of.php?id=<?=$assetOf->getId();?>";					
				</script>
				<?
				break;
			
			case 'valider':
				
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->status = "VALID";
				$assetOf->updateLines($PDOdb,$_REQUEST['qty']);
				$assetOf->save($PDOdb);
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/fiche_of.php?id=<?=$assetOf->getId();?>";					
				</script>
				<?php
				break;
				
			case 'lancer':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->status = "OPEN";
				$assetOf->openOF($PDOdb);
				$assetOf->save($PDOdb);
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/fiche_of.php?id=<?=$assetOf->getId();?>";					
				</script>
				<?
				break;
				
			case 'terminer':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->status = "CLOSE";
				$assetOf->closeOF($PDOdb);
				$assetOf->save($PDOdb);
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/fiche_of.php?id=<?=$assetOf->getId();?>";					
				</script>
				<?
				break;
				
			case 'delete':
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $_REQUEST['id'], false);
				
				//$PDOdb->db->debug=true;
				$assetOf->delete($PDOdb);
				
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/liste_of.php?delete_ok=1";					
				</script>
				<?
				
				break;
		}
		
	}
	elseif(isset($_REQUEST['id'])) {
		$assetOf=new TAssetOF;
		$assetOf->load($PDOdb, $_REQUEST['id'], false);
		
		_fiche($assetOf, 'view');
	}
	
}

function _fiche(&$assetOf, $mode='edit') {
	global $langs,$db,$conf;
	/***************************************************
	* PAGE
	*
	* Put here all code to build page
	****************************************************/
	
	llxHeader('',$langs->trans('OFAsset'),'','');
	print dol_get_fiche_head(assetPrepareHead( $assetOf, 'assetOF') , 'fiche', $langs->trans('AssetOF'));
	
	$form=new TFormCore($_SERVER['PHP_SELF'],'formeq','POST');
	$form->Set_typeaff($mode);
	$doliform = new Form($db);
	
	//Ajout des champs hidden
	echo $form->hidden('id', $assetOf->rowid);
	if ($mode=='new'){
		echo $form->hidden('action', 'save');
	}
	else {echo $form->hidden('action', 'save');}
	echo $form->hidden('entity', $conf->entity);
	if(!empty($_REQUEST['fk_product'])) echo $form->hidden('fk_product', $_REQUEST['fk_product']);
	
	$TBS=new TTemplateTBS();
	$liste=new TListviewTBS('asset');

	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;
	
	$PDOdb = new TPDOdb;
	
	?>
	<script type="text/javascript">
		$(function() {
			var type = "";
			
			$( "#dialog" ).dialog({
				autoOpen: false,
				show: {
					effect: "blind",
					duration: 1000
				},
				buttons: {
					"Annuler": function() {
						$( this ).dialog( "close" );
					},				
					"Ajouter": function(){
						var idassetOf = <?php echo $assetOf->getId(); ?>;
						var fk_product = $('#fk_product').val();
						
						$.ajax(
							{url : "script/interface.php?get=addofproduct&id_assetOf="+idassetOf+"&fk_product="+fk_product+"&type="+type}
						).done(function(){
							document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/fiche_of.php?id=<?=$assetOf->getId();?>";
						});
					}
				}
			});
			
			$( ".btnaddproduct" ).click(function() {
				type = $(this).attr('id');
				$( "#dialog" ).dialog( "open" );
			});
			
			$("input[name=valider]").click(function(){
				$('#action').val('valider');
			})
			$("input[name=lancer]").click(function(){
				$('#action').val('lancer');
			})
		});
		
		function deleteLine(idLine,type){
			$.ajax(
				{url : "script/interface.php?get=deletelineof&idLine="+idLine+"&type="+type}
			).done(function(){
				$("#"+idLine).remove();
			});
		}
		
		function addAllLines(idLine,btnadd){
			//var qty = $('#qty['+idLine+']').val();
			var qty = $(btnadd).parent().next().next().find('input[type=text]').val();
			$.ajax(
				{url : "script/interface.php?get=addlines&idLine="+idLine+"&qty="+qty}
			).done(function(){
				//document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/fiche_of.php?id=<?=$assetOf->getId();?>";
			});
		}
	</script>
	<?php
	
	$form2 = new TFormCore();
	if($assetOf->status != "DRAFT")
		$form2->Set_typeaff('view');
	else
		$form2->Set_typeaff('edit');
	
	$TNeeded = array();
	$TToMake = array();
	
	$TNeeded = $assetOf->TAssetOFLineAsArray("NEEDED",$form2);
	$TToMake = $assetOf->TAssetOFLineAsArray("TO_MAKE",$form2);
	
	/*echo '<pre>';
	print_r($TToMake);
	echo '</pre>'; exit;*/
	
	print $TBS->render('tpl/fiche_of.tpl.php'
		,array(
			'TNeeded'=>$TNeeded
			,'TTomake'=>$TToMake
		)
		,array(
			'assetOf'=>array(
				'id'=>$assetOf->getId()
				,'numero'=>$form->texte('', 'numero', $assetOf->numero, 100,255,'','','à saisir')
				,'ordre'=>$form->combo('','ordre',$assetOf->TOrdre,$assetOf->ordre)
				,'date_besoin'=>$form->calendrier('','date_besoin',$assetOf->date_besoin,12,12)
				,'date_lancement'=>$form->calendrier('','date_lancement',$assetOf->date_lancement,12,12)
				,'temps_estime_fabrication'=>$form->texte('','temps_estime_fabrication',$assetOf->temps_estime_fabrication, 12,10,'','','0')
				,'temps_reel_fabrication'=>$form->texte('','temps_reel_fabrication', $assetOf->temps_reel_fabrication, 12,10,'','','0')
				,'fk_asset_workstation'=>$form->combo('','fk_asset_workstation',TAssetWorkstation::getWorstations($PDOdb),$assetOf->fk_asset_workstation)
				//,'fk_user'=>$doliform->select_users('','fk_user')
				,'status'=>$form->combo('','status',$assetOf->TStatus,$assetOf->status)
			)
			,'view'=>array(
				'mode'=>$mode
				,'status'=>$assetOf->status
			)
		)
	);
	
	echo $form->end_form();
	// End of page
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
}

?>
<div id="dialog" title="Ajout de Produit">
	<table>
		<tr>
			<td>Produit : </td>
			<td>
				<?php
					$html=new Form($db);
					$html->select_produits('','fk_product','',$conf->product->limit_size,0,1,2,'',3,array());
				?>
			</td>
		</tr>
	</table>
</div>
