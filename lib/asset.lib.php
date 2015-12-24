<?php

	function assetPrepareHead(&$asset,$type='type-asset') {
		global $user, $conf;

		switch ($type) {
			case 'type-asset':
				return array(
					array(DOL_URL_ROOT.'/custom/asset/typeAsset.php?id='.$asset->getId(), 'Fiche','fiche')
					,array(DOL_URL_ROOT.'/custom/asset/typeAssetField.php?id='.$asset->getId(), 'Champs','field')
				);
				break;
			case 'asset':
				return array(
						array(DOL_URL_ROOT.'/custom/asset/fiche.php?id='.$asset->getId(), 'Fiche','fiche'),
						array(DOL_URL_ROOT.'/custom/asset/fiche.php?action=traceability&id='.$asset->getId(), 'Traçabilité','traceability'),
						array(DOL_URL_ROOT.'/custom/asset/fiche.php?action=object_linked&id='.$asset->getId(), 'Objets référents','object_linked')
					);
				break;
			case 'assetOF':
				$res = array(array(DOL_URL_ROOT.'/custom/asset/fiche_of.php?id='.$asset->getId(), 'Fiche','fiche'));
				if (!empty($conf->global->ASSET_USE_CONTROL)) $res[] = array(DOL_URL_ROOT.'/custom/asset/fiche_of.php?id='.$asset->getId().'&action=control', 'Contrôle','controle');
				
				return $res;
				break;
			case 'assetlot':
				return array(
						array(DOL_URL_ROOT.'/custom/asset/fiche_lot.php?id='.$asset->getId(), 'Fiche','fiche'),
						array(DOL_URL_ROOT.'/custom/asset/fiche_lot.php?action=traceability&id='.$asset->getId(), 'Traçabilité','traceability'),
						array(DOL_URL_ROOT.'/custom/asset/fiche_lot.php?action=object_linked&id='.$asset->getId(), 'Objets référents','object_linked')
					);
				break;
		}
		
	}
	
	
	function visu_checkbox_user(&$PDOdb, &$form, $group, $TUsers, $name, $status)
	{
		$include = array();
		
		$sql = 'SELECT u.lastname, u.firstname, uu.fk_user 
		  FROM '.MAIN_DB_PREFIX.'usergroup_user uu INNER JOIN '.MAIN_DB_PREFIX.'user u ON (uu.fk_user = u.rowid) 
		  WHERE uu.fk_usergroup = '.(int) $group;
		$PDOdb->Execute($sql);
		
		//Cette input doit être présent que si je suis en brouillon, si l'OF est lancé la présence de cette input va réinitialiser à vide les associations précédentes
		if ($status == 'DRAFT' && $form->type_aff == 'edit') {
		    $res = '<input checked="checked" style="display:none;" type="checkbox" name="'.$name.'" value="0" />';
        }
            
		while ($PDOdb->Get_line()) 
		{
			if ($status == 'DRAFT' || (in_array($PDOdb->Get_field('fk_user'), $TUsers))) {
			    $res .= '<p style="margin:4px 0">'
			                 .$form->checkbox1($PDOdb->Get_field('lastname').' '.$PDOdb->Get_field('firstname'), $name, $PDOdb->Get_field('fk_user'), (in_array($PDOdb->Get_field('fk_user'), $TUsers) ? true : false), ($status == 'DRAFT' ? 'style="vertical-align:text-bottom;"' : 'disabled="disabled" style="vertical-align:text-bottom;"'), '', '', 'case_after', array('no'=>'', 'yes'=>img_picto('', 'tick.png'))).'</p>';
            }
		}
		
		return $res;
	}

	/*Mode opératoire*/
	function visu_checkbox_task(&$PDOdb, &$form, $fk_workstation, $TTasks, $name, $status)
	{
		$include = array();
		
		$sql = 'SELECT rowid, libelle FROM '.MAIN_DB_PREFIX.'asset_workstation_task WHERE fk_workstation = '.(int) $fk_workstation;
		$PDOdb->Execute($sql);

		//Cette input doit être présent que si je suis en brouillon, si l'OF est lancé la présence de cette input va réinitialiser à vide les associations précédentes
		if ($status == 'DRAFT' && $form->type_aff == 'edit') $res = '<input checked="checked" style="display:none;" type="checkbox" name="'.$name.'" value="0" />';
		while ($PDOdb->Get_line())
		{			 
			if ($status == 'DRAFT' || (in_array($PDOdb->Get_field('rowid'), $TTasks))) $res .= '<p style="margin:4px 0">'.$form->checkbox1($PDOdb->Get_field('libelle'), $name, $PDOdb->Get_field('rowid'), (in_array($PDOdb->Get_field('rowid'), $TTasks)), ($status == 'DRAFT' ? 'style="vertical-align:text-bottom;"' : 'disabled="disabled" style="vertical-align:text-bottom;"'), '', '', 'case_before').'</p>';
		}
		
		return $res;
	}
	
	function visu_project_task(&$db, $fk_project_task, $mode, $name)
	{
		if (!$fk_project_task) return ' - ';
			
		require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
		
		$projectTask = new Task($db);
		$projectTask->fetch($fk_project_task);
		
		$link = '<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$fk_project_task.'">'.img_picto('', 'object_projecttask.png').$projectTask->ref.'</a>';
		
		if ($projectTask->progress == 0) $imgStatus = img_picto('En attente', 'statut0.png');
		elseif ($projectTask->progress < 100) $imgStatus = img_picto('En cours', 'statut3.png');
		else $imgStatus = img_picto('Terminée', 'statut4.png');
		
		if ($mode == 'edit')
		{
			$formother = new FormOther($db);
			return $link.' - '.$formother->select_percent($projectTask->progress, $name).' '.$imgStatus;	
		}
		else {
			return $link.' - '.$projectTask->progress.' % '.$imgStatus;
		}
		
	}

	/**
	 *  Override
	 * 	Return a combo box with list of units
	 *  For the moment, units labels are defined in measuring_units_string
	 *
	 *  @param	string		$name                Name of HTML field
	 *  @param  string		$measuring_style     Unit to show: weight, size, surface, volume
	 *  @param  string		$default             Force unit
	 * 	@param	int			$adddefault			Add empty unit called "Default"
	 * 	@return	void
	 */
	function custom_load_measuring_units($name='measuring_units', $measuring_style='', $default='0', $adddefault=0)
	{
		global $langs,$conf,$mysoc;
		$langs->load("other");

		$return='';

		$measuring_units=array();
		if ($measuring_style == 'weight') $measuring_units=array(-6=>1,-3=>1,0=>1,3=>1,99=>1);
		else if ($measuring_style == 'size') $measuring_units=array(-3=>1,-2=>1,-1=>1,0=>1,98=>1,99=>1);
        else if ($measuring_style == 'surface') $measuring_units=array(-6=>1,-4=>1,-2=>1,0=>1,98=>1,99=>1);
		else if ($measuring_style == 'volume') $measuring_units=array(-9=>1,-6=>1,-3=>1,0=>1,88=>1,89=>1,97=>1,99=>1,/* 98=>1 */);  // Liter is not used as already available with dm3
		else if ($measuring_style == 'unit') $measuring_units=array(0=>0);

		$return.= '<select class="flat" name="'.$name.'">';
		if ($adddefault) $return.= '<option value="0">'.$langs->trans("Default").'</option>';

		foreach ($measuring_units as $key => $value)
		{
			$return.= '<option value="'.$key.'"';
			if ($key == $default)
			{
				$return.= ' selected="selected"';
			}
			//$return.= '>'.$value.'</option>';
			if ($measuring_style == 'unit') $return.= '>unité(s)</option>';
			else $return.= '>'.measuring_units_string($key,$measuring_style).'</option>';
		}
		$return.= '</select>';

		return $return;
	}

	/**
	 *	Override de la fonction classique de la class FormProject
	 *  Show a combo list with projects qualified for a third party
	 *
	 *	@param	int		$socid      	Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
	 *	@param  int		$selected   	Id project preselected
	 *	@param  string	$htmlname   	Nom de la zone html
	 *	@param	int		$maxlength		Maximum length of label
	 *	@param	int		$option_only	Option only
	 *	@param	int		$show_empty		Add an empty line
	 *	@return string         		    select or options if OK, void if KO
	 */
	function custom_select_projects($socid=-1, $selected='', $htmlname='projectid', $type_aff = 'view', $maxlength=25, $option_only=0, $show_empty=1)
	{
		global $user,$conf,$langs,$db;
	
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	
		$out='';
	
		if ($type_aff == 'view')
		{
			if ($selected > 0)
			{
				$project = new Project($db);
				$project->fetch($selected);
				
				//return dol_trunc($project->ref,18).' - '.dol_trunc($project->title,$maxlength);
				return $project->getNomUrl(1).' - '.dol_trunc($project->title,$maxlength);
			}
			else 
			{
				return $out;			
			}
		}
	
		$hideunselectables = false;
		if (! empty($conf->global->PROJECT_HIDE_UNSELECTABLES)) $hideunselectables = true;
	
		$projectsListId = false;
		if (empty($user->rights->projet->all->lire))
		{
			$projectstatic=new Project($db);
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);
		}
	
		// Search all projects
		$sql = 'SELECT p.rowid, p.ref, p.title, p.fk_soc, p.fk_statut, p.public';
		$sql.= ' FROM '.MAIN_DB_PREFIX .'projet as p';
		$sql.= " WHERE p.entity = ".$conf->entity;
		if ($projectsListId !== false) $sql.= " AND p.rowid IN (".$projectsListId.")";
		if ($socid == 0) $sql.= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
		if ($socid > 0)  $sql.= " AND (p.fk_soc=".$socid." OR p.fk_soc IS NULL)";
		$sql.= " ORDER BY p.ref ASC";
	
	
		$resql=$db->query($sql);
		if ($resql)
		{
			if (empty($option_only)) {
				$out.= '<select class="flat" name="'.$htmlname.'">';
			}
			if (!empty($show_empty)) {
				$out.= '<option value="0">&nbsp;</option>';
			}
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);
					// If we ask to filter on a company and user has no permission to see all companies and project is linked to another company, we hide project.
					if ($socid > 0 && (empty($obj->fk_soc) || $obj->fk_soc == $socid) && ! $user->rights->societe->lire)
					{
						// Do nothing
					}
					else
					{
						$labeltoshow=dol_trunc($obj->ref,18);
						//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
						//else $labeltoshow.=' ('.$langs->trans("Private").')';
						if (!empty($selected) && $selected == $obj->rowid && $obj->fk_statut > 0)
						{
							$out.= '<option value="'.$obj->rowid.'" selected="selected">'.$labeltoshow.' - '.dol_trunc($obj->title,$maxlength).'</option>';
						}
						else
						{
							$disabled=0;
							$labeltoshow.=' '.dol_trunc($obj->title,$maxlength);
							if (! $obj->fk_statut > 0)
							{
								$disabled=1;
								$labeltoshow.=' - '.$langs->trans("Draft");
							}
							if ($socid > 0 && (! empty($obj->fk_soc) && $obj->fk_soc != $socid))
							{
								$disabled=1;
								$labeltoshow.=' - '.$langs->trans("LinkedToAnotherCompany");
							}
	
							if ($hideunselectables && $disabled)
							{
								$resultat='';
							}
							else
							{
								$resultat='<option value="'.$obj->rowid.'"';
								if ($disabled) $resultat.=' disabled="disabled"';
								//if ($obj->public) $labeltoshow.=' ('.$langs->trans("Public").')';
								//else $labeltoshow.=' ('.$langs->trans("Private").')';
								$resultat.='>';
								$resultat.=$labeltoshow;
								$resultat.='</option>';
							}
							$out.= $resultat;
						}
					}
					$i++;
				}
			}
			if (empty($option_only)) {
				$out.= '</select>';
			}

			if($conf->cliacropose->enabled) { // TODO c'est naze, à refaire en utilisant la vraie autocompletion dispo depuis dolibarr 3.8 pour utiliser l'auto complete projets de doli si active (j'avais rajouté un script ajax/projects.php pour acropose)
			
				// Autocomplétion
				if(isset($selected)) {
					
					$p = new Project($db);
					$p->fetch($selected);
					$selected_value = $p->ref;
					
				}
				
				$out = ajax_autocompleter($selected, $htmlname, DOL_URL_ROOT.'/projet/ajax/projects.php', $urloption, 1);
				$out .= '<input type="text" size="20" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_value.'"'.$placeholder.' />';
				
			}
			
			$db->free($resql);
			
			return $out;
		}
		else
		{
			dol_print_error($db);
			return '';
		}
	}


function _getArrayNomenclature(&$PDOdb, $TAssetOFLine=false, $fk_product=false)
{
	global $conf;
	
	$TRes = array();
	
	if (!$conf->nomenclature->enabled) return $TRes;
	
	include_once DOL_DOCUMENT_ROOT.'/custom/nomenclature/class/nomenclature.class.php';
	
	$fk_product = $TAssetOFLine->fk_product ? $TAssetOFLine->fk_product : $fk_product;
	
	$TNomen = TNomenclature::get($PDOdb, $fk_product);
	foreach ($TNomen as $TNomenclature) 
	{
		$TRes[$TNomenclature->getId()] = !empty($TNomenclature->title) ? $TNomenclature->title : '(sans titre)';
	}
	
	return $TRes;
}

function _calcQtyOfProductInOf(&$db, &$conf, &$product)
{
	$qty_to_make = $qty_needed = 0;
	$sql = 'SELECT (SELECT SUM(aol.qty_used) - SUM(aol.qty_stock) 
			        	FROM  '.MAIN_DB_PREFIX.'assetOf_line aol 
			        	INNER JOIN '.MAIN_DB_PREFIX.'assetOf ao ON (aol.fk_assetOf = ao.rowid)
			        	AND aol.fk_product = '.$product->id.' 
			        	AND aol.type = "TO_MAKE"  
			        	AND ao.status IN ("DRAFT", "VALID", "OPEN")) AS qty_to_make
			        ,(SELECT '.( !empty($conf->global->OF_USE_DESTOCKAGE_PARTIEL) ? 'SUM(aol.qty_needed) - SUM(aol.qty_used)' : ' (SUM(aol.qty_needed) - SUM(aol.qty_used)) + SUM(aol.qty_used) - SUM(aol.qty_stock)' ).'
			        	FROM '.MAIN_DB_PREFIX.'assetOf_line aol
						INNER JOIN '.MAIN_DB_PREFIX.'assetOf ao ON (aol.fk_assetOf = ao.rowid) 
						WHERE aol.fk_product = '.$product->id.'
						AND aol.type = "NEEDED"
						AND ao.status IN ("DRAFT", "VALID", "OPEN")) AS qty_needed';
	
	$resql = $db->query($sql);
	
	if ($row = $db->fetch_object($resql)) 
	{
		$qty_to_make = is_null($row->qty_to_make) ? 0 : $row->qty_to_make;
		$qty_needed = is_null($row->qty_needed) ? 0 : $row->qty_needed;
	}
	
	return array($qty_to_make, $qty_needed);
}
