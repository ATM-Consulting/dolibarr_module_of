<?php
	require('config.php');
	require('./class/asset.class.php');
	require('./class/ordre_fabrication_asset.class.php');
	
	$langs->load('asset@asset');
	
	dol_include_once('/core/class/html.form.class.php');
	
	
	$action=__get('action','view');
	$id = __get('id', 0);
	$ATMdb=new TPDOdb;
	

	switch($action) {
		case 'view':
			if ($id <= 0) header('Location: '.DOL_MAIN_URL_ROOT.'/custom/asset/list_control.php');
		
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
			header('Location: '.DOL_MAIN_URL_ROOT.'/custom/asset/list_control.php');
			
			break;
			
		case 'addValue':
			$controlMultiple=new TAssetControlMultiple;
			$controlMultiple->fk_control = __get('fk_control', 0);
			
			_ficheMultiple($ATMdb, $controlMultiple, 'edit');
			
			break;
			
		case 'editValue':
			$idm = __get('idm', 0);
			if ($idm <= 0) header('Location: '.DOL_MAIN_URL_ROOT.'/custom/asset/control.php?action=addValue');
			
			$controlMultiple=new TAssetControlMultiple;
			$controlMultiple->load($ATMdb, $idm);
			
			_ficheMultiple($ATMdb, $controlMultiple, 'edit');	
					
			
			break;
			
		case 'saveValue':
			$idm = __get('idm', 0);
			$controlMultiple=new TAssetControlMultiple;
			$controlMultiple->load($ATMdb, $idm);
			$controlMultiple->set_values($_REQUEST);
			$controlMultiple->save($ATMdb);
		
			setEventMessage($langs->trans('AssetSaveControlValueEvent'));

			_ficheMultiple($ATMdb, $controlMultiple, 'view');
		
			break;

		case 'deleteValue':	
			$idm = __get('idm', 0);			
			$controlMultiple=new TAssetControlMultiple;
			$controlMultiple->load($ATMdb, $idm);
			$controlMultiple->delete($ATMdb);
			
			$_SESSION['AssetMsg'] = 'AssetDeleteControlValueEvent';
			header('Location: '.DOL_MAIN_URL_ROOT.'/custom/asset/list_control_multiple.php');
			
			break;
			
		default:
			if ($id <= 0) header('Location: '.DOL_MAIN_URL_ROOT.'/custom/asset/list_control.php');

			$control=new TAssetControl;
			$control->load($ATMdb, $id);
			
			_fiche($ATMdb, $control, 'view');
			
			break;
	}
	

function _fiche(&$ATMdb, &$control, $mode='view') {
	global $db,$langs;

	llxHeader('',$langs->trans('AssetAddControl'),'','');
	$TBS=new TTemplateTBS;
	
	$form=new TFormCore('auto', 'formC', 'post', true);
	
	$form->Set_typeaff($mode);
	
	echo $form->hidden('action','save');
	echo $form->hidden('id',$control->getId());
	
	$formDoli=new Form($db);
	
	$TForm=array(
		'id'=>$control->getId()
		,'libelle'=>$form->texte('', 'libelle', $control->libelle,50,255)
		,'type'=>$form->combo('', 'type', TAssetControl::$TType, $control->type)
		,'question'=>$form->texte('', 'question', $control->question,120,255)
	);
	
	print $TBS->render('./tpl/control.tpl.php', array(), array(
			'co'=>$TForm
			,'view'=>array(
				'mode'=>$mode
			)
		)
	);
	
	$form->end();
	
	llxFooter();
}

function _ficheMultiple(&$ATMdb, &$controlMultiple, $mode='view') {
	global $db,$langs;

	llxHeader('',$langs->trans('AssetAddControlValue'),'','');
	$TBS=new TTemplateTBS;
	
	$form=new TFormCore('auto', 'formCM', 'post', true);
	
	$form->Set_typeaff($mode);
	
	echo $form->hidden('action','saveValue');
	echo $form->hidden('idm',$controlMultiple->getId());
	
	$formDoli=new Form($db);
	
	$TForm=array(
		'idm'=>$controlMultiple->getId()
		,'fk_control'=>$controlMultiple->visu_select_control($ATMdb, $form, 'fk_control')
		,'value'=>$form->texte('', 'value', $controlMultiple->value, 120, 255)
	);
	
	print $TBS->render('./tpl/controlMultiple.tpl.php', array(), array(
			'com'=>$TForm
			,'view'=>array(
				'mode'=>$mode
			)
		)
	);
	
	$form->end();
	
	llxFooter();
}
