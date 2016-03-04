<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		core/triggers/interface_99_modMyodule_oftrigger.class.php
 * 	\ingroup	of
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modMymodule_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class Interfaceoftrigger
{

    private $db;

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "Triggers of this module are empty functions."
            . "They have no effect."
            . "They are provided for tutorial purpose only.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'of@of';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
    	
        if($action === 'ORDER_VALIDATE') {
				
        	global $conf, $db;
			
			if($conf->global->CREATE_OF_ON_ORDER_VALIDATE) {
				define('INC_FROM_DOLIBARR',true);
				
				dol_include_once('/product/class/product.class.php');
			
				dol_include_once('/of/config.php');
				dol_include_once('/of/class/ordre_fabrication_asset.class.php');
				$PDOdb=new TPDOdb;	
				
				foreach($object->lines as $line) {
					
					// Uniquement si c'est un produit
					if(!empty($line->fk_product) && $line->fk_product_type == 0) {
						
						// On charge le produit pour vérifier son stock
						$prod = new Product($db);
						$prod->fetch($line->fk_product);
						$prod->load_stock();
						
						if($prod->stock_reel < $line->qty) {
						
							$assetOF = new TAssetOF;
							$assetOF->fk_commande = $_REQUEST['id'];
							$assetOF->fk_soc = $object->socid;
							$assetOF->addLine($PDOdb, $line->fk_product, 'TO_MAKE', $line->qty);
							$assetOF->save($PDOdb);
							
						}

					}
					
				}
			}
			
			
        }
		elseif($action === 'ORDER_CANCEL') { 
				
				if($conf->global->DELETE_OF_ON_ORDER_CANCEL) {
					define('INC_FROM_DOLIBARR',true);
					dol_include_once('/of/config.php');
					dol_include_once('/of/class/ordre_fabrication_asset.class.php');
					$PDOdb=new TPDOdb;
					
					// On récupère les identifiants des of créés à partir de cette commande
					$TID_OF_command = TAssetOF::getTID_OF_command($_REQUEST['id']);
				
					foreach($TID_OF_command as $id_of) {
						
						$asset = new TAssetOF;
						$asset->load($PDOdb, $id_of);
						
						if($asset->status == "DRAFT" || $asset->status == "VALID")
							$asset->delete($PDOdb);
						
					}
	
				}
				
		}
		elseif($action==='TASK_TIMESPENT_CREATE') {
			if($conf->workstation->enabled) {
				define('INC_FROM_DOLIBARR',true);
		    	dol_include_once('/of/config.php');
				dol_include_once('/of/class/ordre_fabrication_asset.class.php');
				$PDOdb=new TPDOdb;
					
				$PDOdb->Execute("SELECT rowid 
						FROM ".MAIN_DB_PREFIX."asset_workstation_of 
						WHERE fk_project_task=".$object->id);	
				if($obj = $PDOdb->Get_line()) {
					
					$wsof=new TAssetWorkstationOF;
					$wsof->load($PDOdb, $obj->rowid);
					$wsof->nb_hour_real = ($object->duration_effective + $object->timespent_duration) / 3600;
					$wsof->save($PDOdb);
					
				}
				
				
			}		
				
			
		}
		elseif($action === 'ORDERSUPPLIER_ADD_LIVRAISON') {
			global $db;
			if($conf->of->enabled) {
				define('INC_FROM_DOLIBARR',true);
		    	dol_include_once('/of/config.php');
				dol_include_once('/of/class/ordre_fabrication_asset.class.php');
				$resql =$db->query('SELECT fk_statut FROM llx_commande_fournisseur WHERE rowid = '.$_REQUEST['id']);
				$res = $db->fetch_object($resql);
				if($res->fk_statut == 5) { // La livraison est totale
					//On cherche l'OF lié
					$resql = $db->query("SELECT fk_source 
											FROM ".MAIN_DB_PREFIX."element_element 
											WHERE fk_target = ".$_REQUEST['id']." 
												AND sourcetype = 'ordre_fabrication' 
												AND targettype = 'order_supplier'");
	
					$res = $db->fetch_object($resql);
					
					$id_of = $res->fk_source;
					
					if($id_of > 0) {
						$of = new TAssetOF;
						$of->load($PDOdb, $id_of);
						
						if($of->status != 'CLOSE') {
							$of->closeOF($PDOdb);
							setEventMessage($langs->trans('OFAttachedClosedAutomatically', '<a href="'.dol_buildpath('/of/fiche_of.php?id='.$id_of, 2).'">'.$of->numero.'</a>'));
						}
					}
					
				}
			}
			
		}
		
		
        return 0;
    }
}
