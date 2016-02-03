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
                
                dol_include_once("/asset/config.php");
                dol_include_once("/asset/class/asset.class.php");   
                dol_include_once("/asset/class/ordre_fabrication_asset.class.php");   
                            
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
                  
                        
                   $res = $db->query("SELECT DISTINCT of.rowid as 'fk_of' 
                            FROM ".MAIN_DB_PREFIX."assetOf_line ofl
                            LEFT JOIN ".MAIN_DB_PREFIX."assetOf of ON (of.rowid = ofl.fk_assetOf)
                            WHERE ofl.fk_product IN (".implode(',',$TProduct).")
                            AND of.status='ONORDER'
                            ORDER BY of.date_besoin ASC");    
                   $PDOdb=new TPDOdb;
                   
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
                    //exit;     
                    
                }
                
            }
            
            
        }
        else if($action == "validmodasset"){
        	//print_r($object);exit;
			if(isset($_REQUEST['asset'])){
				
				if($conf->climcneil->enabled && !empty($_REQUEST['asset'])){
					define('INC_FROM_DOLIBARR',true);
			    	dol_include_once("/asset/config.php");
					dol_include_once("/asset/class/asset.class.php");
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
		}
 
        return 0;
    }
            
    function formObjectOptions($parameters, &$object, &$action, $hookmanager) 
    {  
      	global $langs,$db,$conf;
		$langs->load('asset@asset');
		/*echo '<pre>';
		print_r($parameters['context']);
		echo '</pre>';exit;*/

		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context'])) || in_array('propalcard',explode(':',$parameters['context']))) 
        {
        	define('INC_FROM_DOLIBARR',true);
        	dol_include_once("/asset/config.php");
			dol_include_once("/asset/class/asset.class.php");
			
			
			if($action == "create"){
					
				//pre($_REQUEST,true);

				if(isset($_REQUEST['origin']) && isset($_REQUEST['originid'])){
					$sql = "SELECT fk_asset FROM ".MAIN_DB_PREFIX.$_REQUEST['origin']." WHERE rowid = ".$_REQUEST['originid'];
													
					if($resql = $db->query($sql)){
						$res = $db->fetch_object($resql);
						$fk_asset = $res->fk_asset;
					}
				}
				else{
					$fk_asset = 0;
				}

				$sql = "SELECT a.rowid, a.serial_number, p.label FROM ".MAIN_DB_PREFIX."asset as a LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = a.fk_product) WHERE a.fk_soc = ".$_REQUEST['socid']." ORDER BY a.serial_number ASC";

				print '<tr><td>Equipement</td>';
				print '<td colspan="2">';

				if($resql = $db->query($sql)){
					print '<select name="asset" class="flat" id="asset">';
					print '<option value="0">&nbsp;</option>';

					while ($res = $db->fetch_object($resql)) {
						if($res->rowid == $fk_asset){
							print '<option selected="selected" value="'.$res->rowid.'">'.$res->serial_number.' - '.$res->label.'</option>';
						}	
						else{
							print '<option value="'.$res->rowid.'">'.$res->serial_number.' - '.$res->label.'</option>';
						}
					}
					
					print '</select>';
				}
				else{
					print 'Aucun équipement associé à ce tiers';
				}
				print '</td></tr>';
			}
			elseif($action == "modasset"){
				
				$sql = "SELECT fk_asset FROM ".MAIN_DB_PREFIX.$object->table_element." WHERE rowid = ".$object->id;
				$resql = $db->query($sql);

				if($resql){
					$res = $db->fetch_object($resql);
					$fk_asset = $res->fk_asset;
				}
				else 
					$fk_asset = 0;

				$sql = "SELECT a.rowid, a.serial_number, p.label FROM ".MAIN_DB_PREFIX."asset as a LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = a.fk_product) WHERE a.fk_soc = ".$object->socid." ORDER BY a.serial_number ASC";
				$resql = $db->query($sql);
				$id_field = "id";
				print '<tr><td>Equipement</td>';
				print '<td colspan="2">';
				print '<form action="'.$_SERVER["PHP_SELF"].'?'.$id_field.'='.$object->id.'" method="post">';
				print '<input type="hidden" name="action" value="validmodasset" />';
				print '<select name="asset" class="flat" id="asset">';
				print '<option value="0">&nbsp;</option>';

				while ($res = $db->fetch_object($resql)) {
					if($res->rowid == $fk_asset)
						print '<option selected="selected" value="'.$res->rowid.'">'.$res->serial_number.' - '.$res->label.'</option>';
					else
						print '<option value="'.$res->rowid.'">'.$res->serial_number.' - '.$res->label.'</option>';
				}
				
				print '</select>';
				print '<input class="button" type="submit" value="Modifier"></form></td></tr>';
			}
			elseif($action != "edit"){

				if(dolibarr_get_const($db, 'USE_ASSET_IN_ORDER')) {
				
					//pre($object, true);exit;
					$sql = "SELECT fk_asset FROM ".MAIN_DB_PREFIX.$object->table_element." WHERE rowid = ".$object->id;
	
					$resql = $db->query($sql);
					if($resql){
						$res = $db->fetch_object($resql);
	
						$sql = "SELECT a.serial_number, p.label FROM ".MAIN_DB_PREFIX."asset as a LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = a.fk_product) WHERE a.rowid = ".$res->fk_asset;
						$resql = $db->query($sql);
					}
					$id_field = "id";
					print '<tr><td height="10"><table width="100%" class="nobordernopadding"><tbody><tr>';
					print '<td>Equipement</td>';
					print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=modasset&'.$id_field.'='.$object->id.'">'
							.img_picto('Définir Equipement', 'edit')
							.'</a></td>';
					PRINT '</tr></tbody></table></td>';
					print '<td colspan="3">';
					
					if($resql){
						$num = $db->num_rows($resql);
						if($num > 0) {
							$res = $db->fetch_object($resql);
							print $res->serial_number.' - '.$res->label;
						}
					}
					
					print '</select></td></tr>';
					
				}

			}
			
			/*
			 * LIGNES
			 * 
			 */
        	foreach($object->lines as $line){
        		/*echo '<pre>';
				print_r($object);rowid
				echo '</pre>';exit;*/
				
	        	$resql = $db->query('SELECT asset_lot FROM '.MAIN_DB_PREFIX.$object->table_element_line.' WHERE rowid = '.$line->rowid);
				$res = $db->fetch_object($resql);
				
				$ATMdb = new TPDOdb;
				$asset = new TAsset;
				$asset->load($ATMdb, $res->asset_lot);
				
				/*echo '<pre>';
				print_r($asset);
				echo '</pre>';exit;*/
				
				$link = '<a href="'.dol_buildpath('/asset/fiche.php?id='.$asset->getId(),1).'">'.$asset->serial_number.'</a>';
				
				if(!is_null($res->asset_lot))
				{
		        	?> 
					<script type="text/javascript">
						$(document).ready(function(){
							$('#row-<?php echo $line->rowid; ?>').children().eq(0).append(' - <?php echo $langs->trans('Asset'); ?> : <?php echo $link; ?>');
						});
					</script>
					<?php
				}
			}
        }
		elseif (in_array('pricesuppliercard',explode(':',$parameters['context']))) {
			?>
			<script type="text/javascript">
				$(document).ready(function(){
					$('tr.liste_titre').find('>td:last').before('<td class="liste_titre" align="right">Composé fourni</td>');
				});
			</script>
			<?php
		}
	}
     
    function formEditProductOptions($parameters, &$object, &$action, $hookmanager) 
    {
    	/*ini_set('dysplay_errors','On');
		error_reporting(E_ALL);*/ 
    	global $db,$langs;
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		
		/*echo '<pre>';
		print_r($parameters["line"]->rowid);
		echo '</pre>';exit;*/
		
		//Commandes et Factures
    	if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context'])) || in_array('propalcard',explode(':',$parameters['context'])))
        {
        	$resql = $db->query('SELECT asset_lot FROM '.MAIN_DB_PREFIX.$object->table_element_line.' WHERE rowid = '.$_REQUEST['lineid']);
			$res = $db->fetch_object($resql);
        	?> 
			<script type="text/javascript">
			$(document).ready(function(){
				$('#addproduct').append('<input id="lot" type="hidden" value="0" name="lot" size="3">');
				$('#product_desc').before('<div><span id="span_lot"> <?php echo $langs->trans('Asset'); ?> : </span><select id="lotAff" name="lotAff" class="flat"></select></div>');
				$('#lotAff').change(function(){
					$('#lot').val( $('#lotAff option:selected').val() );
				});
				$('#product_id').change( function(){
					$.ajax({
						type: "POST"
						,url: "<?php echo dol_buildpath('/asset/script/ajax.liste_asset.php', 1) ?>"
						,dataType: "json"
						,data: {
							fk_product: $('#product_id').val(),
							fk_soc : <?php echo $object->socid; ?>
							}
						},"json").then(function(select){
							if(select.length > 0){
								$.each(select, function(i,option){
									if(option.flacon == "<?php echo $res->asset_lot; ?>"){
										$('#lotAff').prepend('<option value="'+option.flacon+'" selected="selected">'+option.flaconAff+'</option>');
										$('#lot').val(<?php echo $res->asset_lot; ?>);
									}
									else
										$('#lotAff').prepend('<option value="'+option.flacon+'">'+option.flaconAff+'</option>');
								})
								$('#lotAff').prepend('<option value="0">S&eacute;le ctionnez un <?php echo $langs->trans('Asset'); ?></option>');
							}
						});
				});
				$('#product_id').change();
			});
			</script>
			<?php
			
			$this->resprints='';
        }
 
        /*$this->results=array('myreturn'=>$myvalue);
        $this->resprints='';
 */
        return 0;
    }

	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) {
		
		global $db,$langs;
		
		/*echo '<pre>';
		print_r($parameters);
		echo '</pre>';*/
		
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		
		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('propalcard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	if(dolibarr_get_const($db, 'USE_ASSET_IN_ORDER')) {
	        	?> 
				<script type="text/javascript">
					$('#addproduct').append('<input id="lot" type="hidden" value="0" name="lot" size="3">');
					$('#idprod').parent().parent().find(" > span:last").after('<span id="span_lot"> <?php echo $langs->trans('Asset'); ?> : </span><select id="lotAff" name="lotAff" class="flat"><option value="0" selected="selected">S&eacute;lectionnez un <?php echo $langs->trans('Asset');?></option></select>');
					$('#idprod').change( function(){
						$.ajax({
							type: "POST"
							,url: "<?php echo dol_buildpath('/asset/script/ajax.liste_asset.php', 1) ?>"
							,dataType: "json"
							,data: {
								fk_product: $('#idprod').val(),
								fk_soc : <?php echo $object->socid; ?>
								}
							},"json").then(function(select){
								if(select.length > 0){
									$('#lotAff').empty().show();
									$('#span_lot').show();
									$.each(select, function(i,option){
										if(select.length > 1){
											$('#lotAff').prepend('<option value="'+option.flacon+'">'+option.flaconAff+'</option>');
										}
										else{
											$('#lotAff').prepend('<option value="'+option.flacon+'" selected="selected">'+option.flaconAff+'</option>');
										}
									})
									$('#lotAff').prepend('<option value="0" selected="selected">S&eacute;lectionnez un <?php echo $langs->trans('Asset'); ?></option>');
								}
								else{
									$('#lotAff').empty();
									$('#lotAff').prepend('<option value="0" selected="selected">S&eacute;lectionnez un <?php echo $langs->trans('Asset'); ?></option>');
								}
							});
					});
					$('#lotAff').change(function(){
						$('#lot').val( $('#lotAff option:selected').val() );
					});
				</script>
				<?php
			}
        }

		return 0;
	}

	function printObjectLine ($parameters, &$object, &$action, $hookmanager){
		
		global $db;
		
		/*echo '<pre>';
		print_r($object);
		echo '</pre>';exit;*/
		
		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context'])) || in_array('propalcard',explode(':',$parameters['context']))) 
        {
        	$resql = $db->query('SELECT asset_lot FROM '.MAIN_DB_PREFIX.$object->table_element_line.' WHERE rowid = '.$object->id);
			$res = $db->fetch_object($resql);
			
			if(!is_null($res->asset_lot))
			{
	        	?> 
				<script type="text/javascript">
					$(document).ready(function(){
						$('#row-<?php echo $object->id; ?> :first-child > td').append('<?php echo $res->asset_lot; ?>');
					});
				</script>
				<?php
			}
        }
        
		elseif(in_array('pricesuppliercard',explode(':',$parameters['context']))){
			
			$resql = $db->query('SELECT compose_fourni FROM '.MAIN_DB_PREFIX.'product_fournisseur_price WHERE rowid = '.(($object->product_fourn_price_id) ? $object->product_fourn_price_id : $parameters['id_pfp']) );

			$res = $db->fetch_object($resql);
			
			if($res){
				?>
				<script type="text/javascript">
					$(document).ready(function(){
						$('#row-<?php echo ($object->product_fourn_price_id) ? $object->product_fourn_price_id : $parameters['id_pfp']; ?>').find('>td:last').before('<td align="right"><?php echo ($res->compose_fourni) ? "Oui" : "Non" ; ?></td>');
					});
				</script>
				<?php
			}
		}
		

		return 0;
	}
	
	function formCreateThirdpartyOptions($parameters, &$object, &$action, $hookmanager){
			
		if (in_array('pricesuppliercard',explode(':',$parameters['context']))) {
			dol_include_once("/core/class/html.form.class.php");

			$form = new Form($this->db);
			?>
			<tr id="newField">
				<td class="fieldrequired">Composé fourni</td>
				<td><?php print $form->selectarray('selectOuiNon', array(1=>"Oui",0=>"Non")); ?></td>
			</tr>
			<?php
        }
		
	}
	
	function formEditThirdpartyOptions ($parameters, &$object, &$action, $hookmanager){
		global $db;
		
		/*echo '<pre>';
		print_r($_REQUEST);
		echo '</pre>';exit;*/
		
		if (in_array('pricesuppliercard',explode(':',$parameters['context']))) {
			dol_include_once("/core/class/html.form.class.php");

			$resql = $db->query('SELECT compose_fourni FROM '.MAIN_DB_PREFIX.'product_fournisseur_price WHERE rowid = '.$_REQUEST['rowid']);
			$res = $db->fetch_object($resql);
			
			$form = new Form($db);
			?>
			<tr id="newField">
				<td class="fieldrequired">Composé fourni</td>
				<td><?php print $form->selectarray('selectOuiNon', array(1=>"Oui",0=>"Non"),$res->compose_fourni); ?></td>
			</tr>
			<?php
        }
	}
	
	
	function addMoreLine($parameters, &$object, &$action, $hookmanager)
	{
		global $db,$conf,$langs;
		
		if (!empty($conf->global->OF_SHOW_QTY_THEORIQUE_MOINS_OF))
		{
			$langs->load('asset@asset');
			dol_include_once('/product/class/procudt.class.php');
			
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
		dol_include_once('/asset/lib/asset.lib.php');
		return _calcQtyOfProductInOf($db, $conf, $product);
	}
	


}