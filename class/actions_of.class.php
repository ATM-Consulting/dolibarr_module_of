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
 * \file    class/actions_of.class.php
 * \ingroup of
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class Actionsof
 */
class Actionsof
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */

	function addSearchEntry($parameters, &$object, &$action, $hookmanager) {

		global $langs, $db, $conf, $user;

		if($parameters['currentcontext'] === 'searchform' && DOL_VERSION>=6) {
				$search_boxvalue = &$parameters['search_boxvalue'];
				$langs->load('of@of');
				$this->results['searchintoof']= array(
						'img'=>'object_list'
						, 'label'=>$langs->trans("SearchIntoOf", $search_boxvalue)
						, 'text'=>img_picto('','object_list').' '.$langs->trans("SearchIntoOf", $search_boxvalue)
						, 'url'=>dol_buildpath('/of/liste_of.php',1).'?TListTBS[list_llx_assetOf][search][numero]='.urlencode($search_boxvalue)
				);

				return 0;

		}

	}

    function doActions($parameters, &$object, &$action, $hookmanager)
    {
    	global $langs, $db, $conf, $user;

		// Constante PRODUIT_SOUSPRODUITS passée à 0 pour ne pas déstocker les sous produits lors de la validation de l'expédition
		/*if(in_array('expeditioncard',explode(':',$parameters['context'])) && $action === "confirm_valid") {

			$conf->global->PRODUIT_SOUSPRODUITS = 0;

		}*/
		// --> Maintenant Géré grâce à la constante INDEPENDANT_SUBPRODUCT_STOCK que j'ai rajoutée sur notre Dolibarr


        if($parameters['currentcontext'] === 'ordersuppliercard') {

            if(GETPOST('action') === 'confirm_commande' && GETPOST('confirm') === 'yes') {

                $time_livraison = $object->date_livraison;

                $res = $db->query("SELECT fk_source as 'fk_of'
                            FROM ".MAIN_DB_PREFIX."element_element
                            WHERE sourcetype='ordre_fabrication' AND fk_target=".$object->id." AND targettype='order_supplier' ");

                define('INC_FROM_DOLIBARR',true);

                dol_include_once("/of/config.php");
                dol_include_once("/of/class/ordre_fabrication_asset.class.php");

                if($obj = $db->fetch_object($res)) {
                    // of lié à la commande
                    $PDOdb=new TPDOdb;

                    $OF = new TAssetOF;
                    $OF->load($PDOdb, $obj->fk_of);

                    $OF->date_lancement =  $time_livraison;
                    $OF->save($PDOdb);

                }
                else {
                   // pas d'of liés directement
                   $TProduct = $TProd =  array();
                   foreach($object->lines as &$l) {
                        if($l->product_type == 0){
                        	if (empty($l->fk_product)) continue;

                        	$TProduct[] = $l->fk_product;

                            if(!isset($TProd[$l->fk_product])) $TProd[$l->fk_product] = 0;
                            $TProd[$l->fk_product]+=$l->qty;
                        }
                   }

                   if(!empty($TProduct)) {

	                   $res = $db->query("SELECT DISTINCT of.date_besoin, of.rowid as 'fk_of'
	                            FROM ".MAIN_DB_PREFIX."assetOf_line ofl
	                            LEFT JOIN ".MAIN_DB_PREFIX."assetOf of ON (of.rowid = ofl.fk_assetOf)
	                            WHERE ofl.fk_product IN (".implode(',',$TProduct).")
	                            AND of.status='ONORDER'
	                            ORDER BY of.date_besoin ASC");
	                   $PDOdb=new TPDOdb;

	                   if ($res) {
		                   while($obj = $db->fetch_object($res)) {

		                       $OF = new TAssetOF;
		                       $OF->load($PDOdb, $obj->fk_of);
		                       $to_save = false;
		                       foreach($OF->TAssetOFLine as &$line) {

		                           if(isset($TProd[$line->fk_product]) && $TProd[$line->fk_product]>0) {
		                               $TProd[$line->fk_product]-= ($line->qty_needed>0 ?  $line->qty_needed : $line->qty );

		                               if($OF->date_lancement<$time_livraison){
		                                   $OF->date_lancement =  $time_livraison;
		                                   $to_save = true;
		                               }
		                           }
		                       }

		                       if($to_save) {
		                          // print 'OF '.$OF->getId().'$time_livraison'.$time_livraison;
		                           $OF->save($PDOdb);
		                       }

		                   }
	                   } else {
							setEventMessage($db->lasterror,'errors');
	                   }

                   }

                }

            }


        }


        return 0;
    }

    function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
      	global $langs,$db,$conf;

	}

    function formEditProductOptions($parameters, &$object, &$action, $hookmanager)
    {
    	/*ini_set('dysplay_errors','On');
		error_reporting(E_ALL);*/
    	global $db,$langs;

        /*$this->results=array('myreturn'=>$myvalue);
        $this->resprints='';
 */
        return 0;
    }

	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) {



		return 0;
	}

	function printObjectLine ($parameters, &$object, &$action, $hookmanager){

		global $db;


		return 0;
	}

	function formCreateThirdpartyOptions($parameters, &$object, &$action, $hookmanager){


	}

	function formEditThirdpartyOptions ($parameters, &$object, &$action, $hookmanager){
		global $db;

	}


	function addMoreLine($parameters, &$object, &$action, $hookmanager)
	{
		global $db,$conf,$langs;

		if (!empty($conf->global->OF_SHOW_QTY_THEORIQUE_MOINS_OF))
		{
			$langs->load('asset@asset');
			define('INC_FROM_DOLIBARR', true);
			dol_include_once('/of/config.php');
			dol_include_once('/product/class/product.class.php');

			$product = new Product($db);
			$fk_product = GETPOST('id', 'int');
			$ref_product = GETPOST('ref', 'alpha');
			$f = $product->fetch($fk_product, $ref_product);

			if ($f > 0)
			{
				$product->load_stock();
				list($qty_to_make, $qty_needed) = $this->_calcQtyOfProductInOf($db, $conf, $product);
				$qty = $product->stock_theorique + $qty_to_make - $qty_needed;

				print '<tr>';
				print '<td>'.$langs->trans('ofLabelQtyTheoriqueMoinsOf').'</td>';
				print '<td>'.$langs->trans('ofResultQty', $qty, $qty_to_make, $qty_needed).'</td>';
				print '</tr>';
			}
		}
	}

	private function _calcQtyOfProductInOf(&$db, &$conf, &$product)
	{
		dol_include_once('/of/lib/of.lib.php');
		return _calcQtyOfProductInOf($db, $conf, $product);
	}



}
