<?php
	require('config.php');
	require('class/asset.class.php');
	require('lib/asset.lib.php');
	
	$langs->load('asset@asset');
	
	//if (!$user->rights->financement->affaire->read)	{ accessforbidden(); }
	$ATMdb=new TPDOdb;
	$asset=new TAsset_type;
	
	$mesg = '';
	$error=false;
	
	//pre($_REQUEST);
	
	if(isset($_REQUEST['action'])) {
		switch($_REQUEST['action']) {
			case 'add':
			case 'new':
				$asset->set_values($_REQUEST);
				_fiche($ATMdb, $asset,'edit');
				
				break;	
			case 'edit'	:
				//$ATMdb->db->debug=true;
				$asset->load($ATMdb, $_REQUEST['id']);
				
				_fiche($ATMdb, $asset,'edit');
				break;
				
			case 'save':
				//$ATMdb->db->debug=true;
				$asset->load($ATMdb, $_REQUEST['id']);
				$mesg = '<div class="ok">Modifications effectuées.</div>';
				$mode = 'view';

				$asset->set_values($_REQUEST);
				
				$asset->save($ATMdb);
				$asset->load($ATMdb, $_REQUEST['id']);
				_fiche($ATMdb, $asset,$mode);
				break;
			
			case 'view':
				$asset->load($ATMdb, $_REQUEST['id']);
				_fiche($ATMdb, $asset,'view');
				break;
		
			case 'delete':
				$asset->load($ATMdb, $_REQUEST['id']);
				//$ATMdb->db->debug=true;
				
				//avant de supprimer, on vérifie qu'aucune asset n'est de ce type. Sinon on ne le supprime pas.
				if (!$asset->isUsedByAsset($ATMdb)){
					if ($asset->delete($ATMdb)){
						?>
						<script language="javascript">
							document.location.href="?delete_ok=1";					
						</script>
						<?	
					}
					else{
						$mesg = '<div class="error">Ce type d\'équipement ne peut pas être supprimé.</div>';
						_liste($ATMdb, $asset);
					}
				}
				else{
					$mesg = '<div class="error">Le type d\'équipement est utilisé par un équipement. Il ne peut pas être supprimé.</div>';
					_liste($ATMdb, $asset);
				} 
				
				
				break;
		}
	}
	elseif(isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
		$asset->load($ATMdb, $_REQUEST['id']);
		
		_fiche($ATMdb, $asset, 'view');
		
	}
	else {
		/*
		 * Liste
		 */
		 _liste($ATMdb, $asset);
	}
	
	
	$ATMdb->close();
	
	
function _liste(&$ATMdb, &$asset) {
	global $langs,$conf, $db;	
	
	llxHeader('',$langs->trans('AssetType'));
	print dol_get_fiche_head(array()  , '', $langs->trans('AssetType'));
	getStandartJS();
	
	$r = new TSSRenderControler($asset);
	$sql="SELECT rowid as 'ID', libelle as 'Libellé', code as 'Code', '' as 'Supprimer'
		FROM ".MAIN_DB_PREFIX."asset_type
		WHERE 1 ";
	
	$TOrder = array('ID'=>'ASC');
	if(isset($_REQUEST['orderDown']))$TOrder = array($_REQUEST['orderDown']=>'DESC');
	if(isset($_REQUEST['orderUp']))$TOrder = array($_REQUEST['orderUp']=>'ASC');
				
	$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;			
	//print $page;
	$r->liste($ATMdb, $sql, array(
		'limit'=>array(
			'page'=>$page
			,'nbLine'=>'30'
		)
		,'link'=>array(
			'Libellé'=>'<a href="?id=@ID@&action=view">@val@</a>'
			,'Supprimer'=>"<a style=\"cursor:pointer;\" onclick=\"if (window.confirm('Voulez vous supprimer l\'élément ?')){document.location.href='?id=@ID@&action=delete'};\">".img_picto('','delete.png', '', 0)."</a>"
		)
		,'translate'=>array()
		,'hide'=>array()
		,'type'=>array()
		,'liste'=>array(
			'titre'=>$langs->trans('AssetListType')
			,'image'=>img_picto('','title.png', '', 0)
			,'picto_precedent'=>img_picto('','previous.png', '', 0)
			,'picto_suivant'=>img_picto('','next.png', '', 0)
			,'noheader'=> (int)isset($_REQUEST['socid'])
			,'messageNothing'=>$langs->trans('AssetTypeMsgNothing')
			,'order_down'=>img_picto('','1downarrow.png', '', 0)
			,'order_up'=>img_picto('','1uparrow.png', '', 0)
			
		)
		,'orderBy'=>$TOrder
		
	));
	
	echo '<div class="tabsAction">';
	echo '<a class="butAction" href="typeAsset.php?action=new">Nouveau type</a>';
	echo '</div>';
	
	global $mesg, $error;
	dol_htmloutput_mesg($mesg, '', ($error ? 'error' : 'ok'));
	llxFooter();
}	
	
function _fiche(&$ATMdb, &$asset, $mode) {
	global $langs,$db,$user;
	
	//pre($asset);

	llxHeader('',$langs->trans('AssetType'), '', '', 0, 0);
	
	$form=new TFormCore($_SERVER['PHP_SELF'],'form1','POST');
	$doliform=new Form($db);
	
	$form->Set_typeaff($mode);
	echo $form->hidden('id', $asset->getId());
	echo $form->hidden('action', 'save');
	
	$TBS=new TTemplateTBS();

	$langs->load('other');
	$measuring_units_values = array(
		'weight' => $langs->trans('Weight'),
		'size' => $langs->trans('Length'),
		'surface' => $langs->trans('Surface'),
		'volume' => $langs->trans('Volume')
	);
	
	print $TBS->render('tpl/asset.type.tpl.php'
		,array()
		,array(
			'assetType'=>array(
				'id'=>$asset->getId()
				,'code'=>$form->texte('', 'code', $asset->code, 20,255,'','','à saisir')
				,'libelle'=>$form->texte('', 'libelle', $asset->libelle, 20,255,'','','à saisir') 
				,'masque'=>$form->texte('', 'masque', $asset->masque, 20,80,'','','à saisir')
				,'info_masque'=>$doliform->textwithpicto('',$asset->info(),1,0,'',0,3)
				,'point_chute'=>$form->texte('', 'point_chute', $asset->point_chute, 12,10,'','','à saisir')
				,'gestion_stock'=>$form->combo('','gestion_stock',$asset->TGestionStock,$asset->gestion_stock)
				,'perishable'=>$doliform->textwithpicto($form->combo('','perishable',array(0 => 'non', 1 => 'oui'),$asset->perishable), $langs->trans('AssetDescPerishable'),1,0,'',0,3)
				,'cumulate'=>$doliform->textwithpicto($form->combo('','cumulate',array(0 => 'non', 1 => 'oui'),$asset->cumulate), $langs->trans('AssetDescCumulate'),1,0,'',0,3)
				,'reutilisable'=>$form->combo('','reutilisable',array('oui'=>'oui','non'=>'non'),$asset->reutilisable)
				,'measuring_units'=>$form->combo('', 'measuring_units', $measuring_units_values, $asset->measuring_units, 1 , 'loadMeasuringUnits(this);')
				,'contenance_value'=>$form->texte('', 'contenance_value', $asset->contenance_value, 12,10,'','','')
				,'contenance_units'=>_fiche_visu_units($asset, $mode, 'contenance_units',-6)
				,'contenancereel_value'=>$form->texte('', 'contenancereel_value', $asset->contenancereel_value, 12,10,'','','')
				,'contenancereel_units'=>_fiche_visu_units($asset, $mode, 'contenancereel_units',-6)
				,'supprimable'=>$form->hidden('supprimable', 1)
			)
			,'view'=>array(
				'mode'=>$mode
				,'nbChamps'=>count($asset->TField)
				,'head'=>dol_get_fiche_head(assetPrepareHead($asset)  , 'fiche',$langs->trans('AssetType'))
				,'onglet'=>dol_get_fiche_head(array()  , '', $langs->trans('AssetCreateType'))
			)
			
		)	
		
	);
	
	echo $form->end_form();
	// End of page
	
	global $mesg, $error;
	dol_htmloutput_mesg($mesg, '', ($error ? 'error' : 'ok'));
	llxFooter();
}

function _fiche_visu_units(&$asset, $mode, $name,$defaut=-3) {
	global $db,$langs;
	
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	
	$langs->load("other");
	
	if (!empty($asset->measuring_units)) $type = $asset->measuring_units;
	else $type = 'weight';
	
	if($mode=='edit') {
		ob_start();	
		
		$html=new FormProduct($db);
		
		echo $html->select_measuring_units($name, $type, $asset->$name);
		//($asset->$name != "")? $asset->$name : $defaut
		
		return ob_get_clean();
		
	}
	elseif($mode=='new'){
		ob_start();	
		
		$html=new FormProduct($db);
		
		echo $html->select_measuring_units($name, $type, $defaut);
		//($asset->$name != "")? $asset->$name : $defaut
		
		return ob_get_clean();
	}
	else{
		ob_start();	
		
		echo measuring_units_string($asset->$name, $type);
		
		return ob_get_clean();
	}
	
	/*
	 *
			// Weight
            print '<tr><td>'.$langs->trans("Weight").'</td><td colspan="3">';
            print '<input name="weight" size="4" value="'.GETPOST('weight').'">';
            print $formproduct->select_measuring_units("weight_units","weight");
            print '</td></tr>';
            // Length
            print '<tr><td>'.$langs->trans("Length").'</td><td colspan="3">';
            print '<input name="size" size="4" value="'.GETPOST('size').'">';
            print $formproduct->select_measuring_units("size_units","size");
            print '</td></tr>';
            // Surface
            print '<tr><td>'.$langs->trans("Surface").'</td><td colspan="3">';
            print '<input name="surface" size="4" value="'.GETPOST('surface').'">';
            print $formproduct->select_measuring_units("surface_units","surface");
            print '</td></tr>';
            // Volume
            print '<tr><td>'.$langs->trans("Volume").'</td><td colspan="3">';
            print '<input name="volume" size="4" value="'.GETPOST('volume').'">';
            print $formproduct->select_measuring_units("volume_units","volume");
            print '</td></tr>'; 
	 * 
	 */
	
}
	
