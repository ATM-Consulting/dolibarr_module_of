<?php

 //TODO move this to Quality mod
	require('config.php');
	
	dol_include_once('/of/class/ordre_fabrication_asset.class.php');
	if(!$user->rights->of->of->lire) accessforbidden();
	
	$langs->load('asset@asset');
	
	dol_include_once('/core/class/html.form.class.php');
	
	
	$action=__get('action','view');
	$id = __get('id', 0);
	$ATMdb=new TPDOdb;
	

	switch($action) {
		case 'view':
			if ($id <= 0) header('Location: '.dol_buildpath('/of/list_control.php',1));
		
			$control=new TAssetControl;
			$control->load($ATMdb, $id);
			
			_fiche($ATMdb, $control, 'view');
			
			break;
		
		case 'new':
			$control=new TAssetControl;
			
			_fiche($ATMdb, $control, 'edit');
			
			break;
	
		case 'edit':
			$control=new TAssetControl;
			$control->load($ATMdb, $id);
			
			_fiche($ATMdb, $control, 'edit');
			
			break;
			
		case 'save':
			$control=new TAssetControl;
			$control->load($ATMdb, $id);
			$control->set_values($_REQUEST);
			$control->save($ATMdb);
		
			setEventMessage($langs->trans('AssetSaveControlEvent'));
		
			_fiche($ATMdb, $control, 'view');
			
			break;
		
		case 'delete':				
			$control=new TAssetControl;
			$control->load($ATMdb, $id);
			$control->delete($ATMdb);
			
			$_SESSION['AssetMsg'] = 'AssetDeleteControlEvent';
			header('Location: '.dol_buildpath('/of/list_control.php',1));
			
			break;
			
		case 'editValue':
			$control=new TAssetControl;
			$control->load($ATMdb, $id);
			
			_fiche($ATMdb, $control, 'view', 1);	
			
			break;
			
		case 'editValueConfirm':
			$control=new TAssetControl;
			$control->load($ATMdb, $id);
								
			$k=$control->addChild($PDOdb,'TAssetControlMultiple', __get('id_value', 0, 'int'));
			$control->TAssetControlMultiple[$k]->fk_control = $control->getId();
			$control->TAssetControlMultiple[$k]->value = __get('value');
				
			if ($control->TAssetControlMultiple[$k]->save($ATMdb)) setEventMessage($langs->trans('AssetMsgSaveControlValue'));
			else setEventMessage($langs->trans('AssetErrSaveControlValue'));
			
			_fiche($ATMdb, $control, 'view');
			
			break;
			
		case 'deleteValue':
			$control=new TAssetControl;
			$control->load($ATMdb, $id);
			
			if ($control->removeChild('TAssetControlMultiple', __get('id_value',0,'integer'))) 
			{
				$control->save($ATMdb);
				setEventMessage($langs->trans('AssetMsgDeleteControlValue'));
			}
			else setEventMessage($langs->trans('AssetErrDeleteControlValue'));
			
			_fiche($ATMdb, $control, 'view');
			
			break;

			
		default:
			if ($id <= 0) header('Location: '.DOL_MAIN_URL_ROOT.'/custom/asset/list_control.php');

			$control=new TAssetControl;
			$control->load($ATMdb, $id);
			
			_fiche($ATMdb, $control, 'view');
			
			break;
	}
	

function _fiche(&$ATMdb, &$control, $mode='view', $editValue=false) {
	global $db,$langs;

	llxHeader('',$langs->trans('AssetAddControl'),'','');
	$TBS=new TTemplateTBS;
	
	$form=new TFormCore();
	$form->Set_typeaff($mode);
	
	$TForm=array(
		'id'=>$control->getId()
		,'libelle'=>$form->texte('', 'libelle', $control->libelle,50,255)
		,'type'=>$form->combo('', 'type', TAssetControl::$TType, $control->type)
		,'question'=>$form->texte('', 'question', $control->question,120,255)
	);
	
	$TFormVal = _fiche_value($ATMdb, $editValue);
	$TVal = _liste_valeur($ATMdb, $control->getId(), $control->type);
	
	print $TBS->render('./tpl/control.tpl.php', 
		array(
			'TVal'=>$TVal
		)
		,array(
			'co'=>$TForm
			,'FormVal'=>$TFormVal
			,'view'=>array(
				'mode'=>$mode
				,'editValue'=>$editValue
				,'type'=>$control->type
				,'url'=>dol_buildpath('/of/control.php', 1)
			)
		)
	);
	
	
	
	llxFooter();
}

function _fiche_value(&$PDOdb, $editValue)
{
	$res = array();
	
	if (!$editValue) return $res;
	
	$id_value = __get('id_value', 0, 'int');
	$res['id_value'] = $id_value;
	
	if ($id_value > 0)
	{
		$val = new TAssetControlMultiple;
		$val->load($PDOdb, $id_value);
		$res['value'] = $val->value;
	}
	else 
	{
		$res['value'] = '';
	}
	
	return $res;
}

function _liste_valeur(&$PDOdb, $fk_control, $type)
{
	$res = array();
	
	if ($type != 'checkboxmultiple') return $res;
	
	$sql = 'SELECT rowid, value 
			FROM '.MAIN_DB_PREFIX.'asset_control_multiple cm
			WHERE cm.fk_control = '.(int) $fk_control;
	
	$PDOdb->Execute($sql);
	while ($PDOdb->Get_line())
	{
		$res[] = array(
			'value' => $PDOdb->Get_field('value')
			,'action' => '<a title="Modifier" href="?id='.(int) $fk_control.'&action=editValue&id_value='.(int)$PDOdb->Get_field('rowid').'">'.img_picto('','edit.png', '', 0).'</a>&nbsp;&nbsp;&nbsp;<a title="Supprimer" onclick="if (!window.confirm(\'Confirmez-vous la suppression ?\')) return false;" href="?id='.(int) $fk_control.'&action=deleteValue&id_value='.(int)$PDOdb->Get_field('rowid').'">'.img_picto('','delete.png', '', 0).'</a>'
		);
	}

	return $res;
}
