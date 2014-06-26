<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \file       htdocs/core/triggers/interface_90_all_Demo.class.php
 *  \ingroup    core
 *  \brief      Fichier de demo de personalisation des actions du workflow
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *				                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la methode constructeur doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
 */


/**
 *  Class of triggers for Mantis module
 */
 
class InterfaceAssetWorkflow
{
    var $db;
    
    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    
        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "ATM";
        $this->description = "Trigger du module équipement";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'technic';
    }
    
    
    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

	
    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
   		global $db;
		
		if(!defined('INC_FROM_DOLIBARR'))define('INC_FROM_DOLIBARR',true);
    	dol_include_once('/asset/config.php');
		dol_include_once('/commande/class/commande.class.php');
		dol_include_once('/compta/facture/class/facture.class.php');
	   
        /*
		 *  COMMANDES
		 */
        if ($action == 'LINEORDER_INSERT')
        {    
			if(isset($_REQUEST['lot']) && !empty($_REQUEST['lot'])){ //si poids renseigné alors conditionnement
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."commandedet SET asset_lot = \"".$_REQUEST['lot']."\" WHERE rowid = ".$object->rowid);
			}
			
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);

        }
        elseif ($action == 'LINEORDER_UPDATE')
        {
        	if(isset($_REQUEST['lot']) && !empty($_REQUEST['lot'])){ //si poids renseigné alors conditionnement
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."commandedet SET asset_lot = \"".$_REQUEST['lot']."\" WHERE rowid = ".$object->rowid);
			}
        	
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
			
        }
        elseif($action == 'ORDER_CREATE'){
        	
        	if(isset($_REQUEST['asset']) && !empty($_REQUEST['asset'])){ //si poids renseigné alors conditionnement
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."commande SET fk_asset = \"".$_REQUEST['asset']."\" WHERE rowid = ".$object->rowid);
				
				if($conf->climcneil->enabled){
					define('INC_FROM_DOLIBARR',true);
			    	dol_include_once("/custom/asset/config.php");
					dol_include_once("/custom/asset/class/asset.class.php");
					dol_include_once('/core/class/extrafields.class.php');
					
					$ATMdb = new TPDOdb;
					$asset = new TAsset;
					
					$asset->load_liste_type_asset($ATMdb);
					$asset->load_asset_type($ATMdb);
					$asset->load($ATMdb,$_REQUEST['asset']);

					$object->fetch_optionals($object->id,$optionsArray);
					
					foreach($asset->TChamps as $champs => $type){
						//echo $champs." ".$asset->$champs.'<br>';
						if(array_key_exists('options_'.$champs, $object->array_options)){
							$object->array_options['options_'.$champs] = $asset->$champs;
						}
					}
					//pre($object->array_options,true);exit;

					$object->update_extrafields($user);
				}
					
				$db->query('UPDATE '.MAIN_DB_PREFIX.$object->table_element.' SET fk_asset = '.$_REQUEST['asset'].' WHERE rowid = '.$object->id);
			}
			
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif($action === 'ORDER_VALIDATE') {
				
        	global $conf;

			if($conf->global->CREATE_OF_ON_ORDER_VALIDATE) {
				
				dol_include_once('asset/class/ordre_fabrication_asset.class.php');
				$ATMdb = new TPDOdb;
				
				/*echo "<pre>";
				print_r($object);
				echo "</pre>";exit;*/
				
				foreach($object->lines as $line) {
					
					// Uniquement si c'est un produit
					if(!empty($line->fk_product)) {
						
						$assetOF = new TAssetOF;
						$assetOF->fk_commande = $_REQUEST['id'];
						$assetOF->fk_soc = $object->socid;
						$assetOF->addLine($ATMdb, $line->fk_product, 'TO_MAKE', $line->qty);
						$assetOF->save($ATMdb);
						
					}
					
				}
			}
			
        }
		elseif($action === 'ORDER_CANCEL') {
			
			if($conf->global->DELETE_OF_ON_ORDER_CANCEL) {

				/*echo "<pre>";
				print_r($TID_OF_command);
				echo "</pre>";
				exit;*/
				
				dol_include_once('asset/class/ordre_fabrication_asset.class.php');
				$ATMdb = new TPDOdb;
				
				// On récupère les identifiants des of créés à partir de cette commande
				$TID_OF_command = TAssetOF::getTID_OF_command($_REQUEST['id']);
				
				foreach($TID_OF_command as $id_of) {
					
					$asset = new TAssetOF;
					$asset->load($ATMdb, $id_of);
					$asset->delete($ATMdb);
					
				}

			}
			
		}
        
         /*
		 *  PROPAL
		 */
        if ($action == 'LINEPROPAL_INSERT')
        {    
			if(isset($_REQUEST['lot']) && !empty($_REQUEST['lot'])){ //si poids renseigné alors conditionnement
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."propaldet SET asset_lot = \"".$_REQUEST['lot']."\" WHERE rowid = ".$object->rowid);
			}
			
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);

        }
        elseif ($action == 'LINEPROPAL_UPDATE')
        {
        	if(isset($_REQUEST['lot']) && !empty($_REQUEST['lot'])){ //si poids renseigné alors conditionnement
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."propaldet SET asset_lot = \"".$_REQUEST['lot']."\" WHERE rowid = ".$object->rowid);
			}
        	
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
			
        }
		elseif($action == 'PROPAL_CREATE'){
			
        	if(isset($_REQUEST['asset']) && !empty($_REQUEST['asset'])){ //si poids renseigné alors conditionnement
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."propal SET fk_asset = \"".$_REQUEST['asset']."\" WHERE rowid = ".$object->id);
			
				if($conf->climcneil->enabled){
					define('INC_FROM_DOLIBARR',true);
			    	dol_include_once("/custom/asset/config.php");
					dol_include_once("/custom/asset/class/asset.class.php");
					dol_include_once('/core/class/extrafields.class.php');
					
					$ATMdb = new TPDOdb;
					$asset = new TAsset;
					
					$asset->load_liste_type_asset($ATMdb);
					$asset->load_asset_type($ATMdb);
					$asset->load($ATMdb,$_REQUEST['asset']);

					$object->fetch_optionals($object->id,$optionsArray);
					
					foreach($asset->TChamps as $champs => $type){
						//echo $champs." ".$asset->$champs.'<br>';
						if(array_key_exists('options_'.$champs, $object->array_options)){
							$object->array_options['options_'.$champs] = $asset->$champs;
						}
					}
					//pre($object->array_options,true);exit;

					$object->update_extrafields($user);
				}
			}
			
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
        }
		
		/*
		 *  FACTURES
		 */
        elseif ($action == 'LINEBILL_INSERT')
        {
			
        	if(isset($_REQUEST['lot']) && !empty($_REQUEST['lot'])){ //si poids renseigné alors conditionnement	
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."facturedet SET asset_lot = \"".$_REQUEST['lot']."\" WHERE rowid = ".$object->rowid);
			}	
        	
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
			
        }
		elseif ($action == 'LINEBILL_UPDATE')
        {
        	if(isset($_REQUEST['lot']) && !empty($_REQUEST['lot'])){ //si poids renseigné alors conditionnement
        	
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."facturedet SET asset_lot = \"".$_REQUEST['lot']."\" WHERE rowid = ".$object->rowid);
			}
			
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
			
        }
        elseif ($action == 'UPDATE_BUYPRICE')
        {
			global $db, $user;
			
			if ($_REQUEST['action'] == 'updateprice' && isset($_REQUEST['selectOuiNon'])) {
				
				/*echo "<pre>";
				print_r($_REQUEST);
				echo "</pre>";exit;*/
				
				/*$fournisseur = new Fournisseur($db);
				$fournisseur->fetch($_REQUEST['id_fourn']);
				
				$product = new ProductFournisseur($db);
				$product->fetch($_REQUEST['id']);
				$product->fetch_product_fournisseur_price($_REQUEST['ref_fourn_price_id']);
				$product->update_buyprice($_REQUEST['qty'], $_REQUEST['price'], $user, $_REQUEST['price_base_type'], $fournisseur, 0, $_REQUEST['ref_fourn'], $_REQUEST['tva_tx']);*/
				
				//Obligé de faire une requête dégueulasse sinon boucle infini car pas de no trigger
				
				$sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price";
				$sql.= " SET compose_fourni = ".$_REQUEST['selectOuiNon'];
				$sql.= " WHERE fk_soc = ".$_REQUEST['id_fourn'];
				$sql.= " AND ref_fourn = '".$_REQUEST['ref_fourn']."'";
				$sql.= " AND quantity = ".$_REQUEST['qty'];

				$resql = $db->query($sql);

			}        	
        }
		elseif($action == 'BILL_CREATE'){
			
        	if(isset($_REQUEST['asset']) && !empty($_REQUEST['asset'])){ //si poids renseigné alors conditionnement
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."facture SET fk_asset = \"".$_REQUEST['asset']."\" WHERE rowid = ".$object->rowid);
			
				if($conf->climcneil->enabled){
					define('INC_FROM_DOLIBARR',true);
			    	dol_include_once("/custom/asset/config.php");
					dol_include_once("/custom/asset/class/asset.class.php");
					dol_include_once('/core/class/extrafields.class.php');
					
					$ATMdb = new TPDOdb;
					$asset = new TAsset;
					
					$asset->load_liste_type_asset($ATMdb);
					$asset->load_asset_type($ATMdb);
					$asset->load($ATMdb,$_REQUEST['asset']);

					$object->fetch_optionals($object->id,$optionsArray);
					
					foreach($asset->TChamps as $champs => $type){
						//echo $champs." ".$asset->$champs.'<br>';
						if(array_key_exists('options_'.$champs, $object->array_options)){
							$object->array_options['options_'.$champs] = $asset->$champs;
						}
					}
					//pre($object->array_options,true);exit;

					$object->update_extrafields($user);
				}
				
				$db->query('UPDATE '.MAIN_DB_PREFIX.$object->table_element.' SET fk_asset = '.$_REQUEST['asset'].' WHERE rowid = '.$object->id);
			}
			
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

		return 0;
    }
}
