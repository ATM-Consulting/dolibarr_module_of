<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 		\defgroup   asset     Module Asset
 *      \brief      Asset management (Products related to companies
 */

/**
 *      \file       htdocs/includes/modules/modAsset.class.php
 *      \ingroup    asset
 *      \brief      Description and activation file for module Asset
 *		\version	$Id: modAsset.class.php,v 1.67 2011/11/08 15:58:32 atm-maxime Exp $
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 * 		\class      modAsset
 *      \brief      Description and activation class for module Asset
 */
class modAsset extends DolibarrModules
{
	/**
	 *   \brief      Constructor. Define names, constants, directories, boxes, permissions
	 *   \param      DB      Database handler
	 */
	function __construct($DB)
	{
        global $langs,$conf;
		
        $this->db = $DB;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 104160;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'asset';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "ATM";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Gestion des &eacute;quipements";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '2.0';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='pictoof@asset';

		// Defined if the directory /mymodule/includes/triggers/ contains triggers or not
		
		
		$this->module_parts = array(
			'hooks'=>array('ordercard', 'invoicecard', 'pricesuppliercard','propalcard', 'expeditioncard')
			,'triggers' => 1
		);
		
		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array();
		$r=0;

		// Relative path to module style sheet if exists. Example: '/mymodule/css/mycss.css'.
		//$this->style_sheet = '/mymodule/mymodule.css.php';

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array("admin.php@asset");

		// Dependencies
		$this->depends = array('modSociete', 'modCommande', 'modProduct', 'modStock');	// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,5);	// Minimum version of Dolibarr required by module
		$this->langfiles = array('asset@asset');

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		//                             2=>array('MAIN_MODULE_MYMODULE_NEEDSMARTY','chaine',1,'Constant to say module need smarty',1)
		$this->const = array(
			array('ASSET_USE_PRODUCTION_ATTRIBUT','chaine','1',"",1)
			// Template à utiliser
			,array('TEMPLATE_OF','chaine','templateOF.odt',"Template à utiliser",1)
			//Pour afficher la sélection d'un équipement dans une liste déroulante lors de l'ajout d'une ligne de commande
			,array('USE_ASSET_IN_ORDER','chaine',0,'Pour afficher la sélection d\'un équipement dans une liste déroulante lors de l\'ajout d\'une ligne de commande',1)
			,array('ASSET_MANUAL_WAREHOUSE', 'chaine',0,'Définir manuellement les entrepôts sur l\'OF',1)
			,array('ASSET_USE_DEFAULT_WAREHOUSE',0,'Précise si le comportement du destockage/stockage doit prendre l\'entrepôt de la configuration ou celui défini sur chaque équipement',1)
			,array('ASSET_DEFAULT_WAREHOUSE_ID_TO_MAKE',0,'Identifiant de l\'entrepôt pour gérer le stock via les OF (produits à fabriquer)',1)
			,array('ASSET_DEFAULT_WAREHOUSE_ID_NEEDED',0,'Identifiant de l\'entrepôt pour gérer le stock via les OF (produits nécessaires)',1)
			,array('USE_LOT_IN_OF', 'chaine', 0,'Utiliser la gestion des lots dans OF',1)
			,array('CREATE_CHILDREN_OF', 'chaine', 1,'Permet de créer des OF enfants si les composant sont hors stock',1)
			,array('ASSET_DEFINED_USER_BY_WORKSTATION', 'chaine', 0,'Permettre l\'association d\'un ou plusieurs utilisateurs d\'être assigné à un poste de travail sur un OF',1)
			,array('ASSET_DEFINED_WORKSTATION_BY_NEEDED', 'chaine', 0,'Permet de ventiler les produits de composition par poste de travail',1)
			,array('ASSET_USE_CONTROL', 'chaine', 0,'Permet de définir des contrôles à appliquer lors du processus de l\'OF',1)
		);


		$this->tabs = array(
			'product:+tabEquipement1:Asset:asset@asset:$user->rights->asset->all->lire:/asset/liste.php?fk_product=__ID__'
			,'product:+tabOF1:WorkStation:asset@asset:$user->rights->asset->of->lire:/asset/workstation.php?fk_product=__ID__'
			,'product:+tabOF2:OF:asset@asset:$user->rights->asset->of->lire:/asset/liste_of.php?fk_product=__ID__'
			,'order:+tabOF3:OF:asset@asset:$user->rights->asset->of->lire:/asset/liste_of.php?fk_commande=__ID__'
			//,'product:+tabEquipement2:Ordre de Fabrication:@asset:/asset/liste_of.php?fk_product=__ID__'
			//,'product:+tabEquipement2:Attribut équipement:@asset:/asset/attribut.php?fk_product=__ID__&action=edit'
		);

        // Dictionnaries
        $this->dictionnaries=array();
        

        // Boxes
		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
        $this->boxes = array();			// List of boxes
		$r=0;
		// Example:
		/*
		$this->boxes[$r][1] = "myboxa.php";
		$r++;
		$this->boxes[$r][1] = "myboxb.php";
		$r++;
		*/

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		$r++;
		$this->rights[$r][0] = 104121;
		$this->rights[$r][1] = 'Lire les '.$langs->trans('Asset');
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'lire';
		
		$r++;
		$this->rights[$r][0] = 104124;
		$this->rights[$r][1] = 'Créer les '.$langs->trans('Asset');
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = 104122;
		$this->rights[$r][1] = 'Lire les Ordres de fabrication';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'of';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 104123;
		$this->rights[$r][1] = 'Créer des Ordres de fabrication';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'of';
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = 104126;
		$this->rights[$r][1] = 'Lire les Types d\'équipement';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'type';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 104125;
		$this->rights[$r][1] = 'Créer des Types d\'équipement';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'type';
		$this->rights[$r][5] = 'write';
		
		$r++;
		$this->rights[$r][0] = 104127;
		$this->rights[$r][1] = 'Générer les documents';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'read';

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		// $this->rights[$r][0] = 2000; 				// Permission id (must not be already used)
		// $this->rights[$r][1] = 'Permision label';	// Permission label
		// $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $r++;

		$langs->load('asset@asset'); // load lang file to translate menu
		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;
		$this->menu[$r]=array(	'fk_menu'=>0,			// Put 0 if this is a top menu
					'type'=>'top',			// This is a Top menu entry
					'titre'=>$langs->trans('GPAO'),
					'mainmenu'=>'asset',
					'leftmenu'=>'',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
					'url'=>'/asset/liste.php',
					'position'=>100,
					'enabled'=>'$user->rights->asset->all->lire',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->all->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
			'type'=>'left',			// This is a Left menu entry
			'titre'=>$langs->trans('Asset'),
			'mainmenu'=>'asset',
			'leftmenu'=>'assetlist',
			'url'=>'/asset/liste.php',
			'position'=>100,
			'target'=>'',
			'user'=>2);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset,fk_leftmenu=assetlist',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
			'type'=>'left',			// This is a Left menu entry
			'titre'=>$langs->trans('AssetToComplete'),
			'mainmenu'=>'assetToComplete',
			'leftmenu'=>'assetlist',
			'url'=>'/asset/liste.php?no_serial=1',
			'position'=>101,
			'target'=>'',
			'user'=>2);				// 0=Menu for internal users,1=external users, 2=both
		
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset,fk_leftmenu=assetlist',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
			'type'=>'left',			// This is a Left menu entry
			'titre'=>$langs->trans('AssetLotsList'),
			'mainmenu'=>'assetlot',
			'leftmenu'=>'assetlist',
			'url'=>'/asset/liste_lot.php',
			'position'=>102,
			'enabled'=>'$conf->global->USE_LOT_IN_OF',
			'target'=>'',
			'user'=>2);				// 0=Menu for internal users,1=external users, 2=both
		
		$r++;

		
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset,fk_leftmenu=assetlist',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
			'type'=>'left',			// This is a Left menu entry
			'titre'=>$langs->trans('AssetLoaned'),
			'mainmenu'=>'assetLent',
			'leftmenu'=>'assetlist',
			'url'=>'/asset/liste.php?pret=1',
			'position'=>103,
			'target'=>'',
			'enabled'=>'$conf->clinomadic->enabled',
			'user'=>2);				// 0=Menu for internal users,1=external users, 2=both
		
		$r++;
		
		
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('AssetWorkstation'),
					'mainmenu'=>'asset',
					'leftmenu'=>'workstationList',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
					'url'=>'/asset/workstation.php',
					'position'=>200,
					'enabled'=>'$user->rights->asset->of->lire',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->of->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset,fk_leftmenu=workstationList',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('AssetNewWorkstation'),
					'mainmenu'=>'newworkstation',
					'leftmenu'=>'workstationList',// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
					'url'=>'/asset/workstation.php?action=new',
					'position'=>201,
					'enabled'=>'$user->rights->asset->of->lire',// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->of->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);
		$r++;
		
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('AssetType'),
					'mainmenu'=>'asset',
					'leftmenu'=>'typeequipement',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
					'url'=>'/asset/typeAsset.php',
					'position'=>256,
					'enabled'=>'$user->rights->asset->type->lire',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->type->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset,fk_leftmenu=typeequipement',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('AssetNewType'),
					'mainmenu'=>'newtypeequipement',
					'leftmenu'=>'typeequipement',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
					'url'=>'/asset/typeAsset.php?action=new',
					'position'=>257,
					'enabled'=>'$user->rights->asset->type->write',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->type->write',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);
		$r++;
		
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('AssetProductionOrder'),
					'mainmenu'=>'asset',
					'leftmenu'=>'assetOFlist',
					'url'=>'/asset/liste_of.php',
					'position'=>300,
					'enabled'=>'$user->rights->asset->of->lire',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->of->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset,fk_leftmenu=assetOFlist',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('AssetNewProductionOrder'),
					'mainmenu'=>'newAssetOF',
					'leftmenu'=>'assetOFlist',
					'url'=>'/asset/fiche_of.php?action=new',
					'position'=>301,
					'enabled'=>'$user->rights->asset->of->lire',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->of->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('AssetControl'),
					'mainmenu'=>'asset',
					'leftmenu'=>'assetControlList',
					'url'=>'/asset/list_control.php',
					'position'=>302,
					'enabled'=>'$conf->global->ASSET_USE_CONTROL',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->of->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset,fk_leftmenu=assetControlList',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('AssetListControl'),
					'mainmenu'=>'assetControlList',
					'leftmenu'=>'assetNewControl',
					'url'=>'/asset/list_control.php',
					'position'=>303,
					'enabled'=>'$conf->global->ASSET_USE_CONTROL',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->of->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset,fk_leftmenu=assetControlList',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('AssetNewControl'),
					'mainmenu'=>'assetControlList',
					'leftmenu'=>'assetNewControl',
					'url'=>'/asset/control.php?action=new',
					'position'=>304,
					'enabled'=>'$conf->global->ASSET_USE_CONTROL',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->of->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset,fk_leftmenu=assetControlList',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('AssetListValueMultiple'),
					'mainmenu'=>'assetControlList',
					'leftmenu'=>'assetNewControl',
					'url'=>'/asset/list_control_multiple.php',
					'position'=>305,
					'enabled'=>'$conf->global->ASSET_USE_CONTROL',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->of->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=asset,fk_leftmenu=assetControlList',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('AssetAddValueMultiple'),
					'mainmenu'=>'assetControlList',
					'leftmenu'=>'assetNewControl',
					'url'=>'/asset/control.php?action=addValue',
					'position'=>306,
					'enabled'=>'$conf->global->ASSET_USE_CONTROL',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->asset->of->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		
		
		
		// Exports
		$r=1;

		// Example:
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        // $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
		// $this->export_permission[$r]=array(array("facture","facture","export"));
		// $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
		// $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		// $r++;
		
		// Example:
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='Asset';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r]=$this->picto;
		$this->import_entities_array[$r]=array();
		$this->import_tables_array[$r]=array(
				'p'=>MAIN_DB_PREFIX.'product'
				,'a'=>MAIN_DB_PREFIX.'asset'
				,'s'=>MAIN_DB_PREFIX.'societe'
				,'s2'=>MAIN_DB_PREFIX.'societe'
				,'e'=>MAIN_DB_PREFIX.'entrepot'
			);
		$this->import_tables_creator_array[$r]=array('p'=>'fk_user_author');	// Fields to store import user id
		$this->import_fields_array[$r]=array(
				'a.serial_number'=>"NumeroSerie"
				,'a.lot_number'=>"NumeroLot"
				,'a.fk_product'=>"ReferenceProduit"
				,'a.fk_entrepot'=>"LibelleEntrepot"
				,'a.fk_soc'=>"Societe"
				,'a.fk_societe_localisation'=>"Localisation"
				,'a.gestion_stock'=>"GestionStock"
				,'a.dluo'=>"DLUO"
			);
		$this->import_regex_array[$r]=array();
		$this->import_examplevalues_array[$r]=array(
				'a.serial_number'=>"SN-0001"
				,'a.lot_number'=>"LOT-0001"
				,'a.fk_product'=>"PR-001"
				,'a.fk_entrepot'=>"ENT-0001"
				,'a.fk_soc'=>"ATM Consulting"
				,'a.fk_societe_localisation'=> "ATM Consulting"
				,'a.gestion_stock'=>"Unitaire"
				,'a.dluo'=>"17/03/2015"
			);
		$this->import_convertvalue_array[$r]=array(
				'a.fk_product'=>array('rule'=>'fetchidfromref','classfile'=>'/product/class/product.class.php','class'=>'Product','method'=>'fetch','element'=>'Product')
				,'a.fk_entrepot'=>array('rule'=>'fetchidfromref','classfile'=>'/product/stock/class/entrepot.class.php','class'=>'Entrepot','method'=>'fetch','element'=>'Entrepot')
				,'a.fk_soc'=>array('rule'=>'fetchidfromref','classfile'=>'/societe/class/societe.class.php','class'=>'Societe','method'=>'fetch','element'=>'Societe')
				,'a.fk_societe_localisation'=>array('rule'=>'fetchidfromref','classfile'=>'/societe/class/societe.class.php','class'=>'Societe','method'=>'fetch','element'=>'Societe')
		);
		$r++;
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories.
	 *      @return     int             1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $db;
		
		$sql = "ALTER TABLE ".MAIN_DB_PREFIX."product_fournisseur_price";
		$sql.= " ADD (compose_fourni int)";
		$db->query($sql);		
		
		$sql = array();

		$result=$this->load_tables();
		
		dol_include_once('/core/class/extrafields.class.php');
        $extrafields=new ExtraFields($this->db);
		
		$param = array('options'=>array("asset_type:libelle:rowid"=>""));
		$res = $extrafields->addExtraField('type_asset', 'Type Equipement', 'sellist', 0, 255, 'product',0,0,'',$param);

		$url =dol_buildpath("/asset/script/create-maj-base.php",2);
		file_get_contents($url);

		//Permet d'activer la gestion des produit virtuel
		dolibarr_set_const($db, "PRODUIT_SOUSPRODUITS", 1);
		
		return $this->_init($sql, $options);
	}

	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted.
	 *      @return     int             1 if OK, 0 if KO
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}


	/**
	 *		\brief		Create tables, keys and data required by module
	 * 					Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 					and create data commands must be stored in directory /mymodule/sql/
	 *					This function is called by this->init.
	 * 		\return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		
			
		return $this->_load_tables('/asset/sql/');
	}
}

?>
