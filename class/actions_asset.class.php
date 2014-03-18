<?php
class ActionsAsset
{ 
     /** Overloading the doActions function : replacing the parent's function with the one below 
      *  @param      parameters  meta datas of the hook (context, etc...) 
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
      *  @param      action             current action (if set). Generally create or edit or null 
      *  @return       void 
      */
      
    function doActions($parameters, &$object, &$action, $hookmanager) {
    	/*echo '<pre>';
    	print_r($object);
		echo '</pre>';
		echo $object->fourn_ref;
		$object->fourn_ref.="coucou";*/
	}
            
    function formObjectOptions($parameters, &$object, &$action, $hookmanager) 
    {  
      	global $langs,$db;
		$langs->load('asset@asset');
		/*echo '<pre>';
		print_r($object);
		echo '</pre>';exit;*/

		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context'])) || in_array('propalcard',explode(':',$parameters['context']))) 
        {
        	define('INC_FROM_DOLIBARR',true);
        	dol_include_once("/custom/asset/config.php");
			dol_include_once("/custom/asset/class/asset.class.php");
			
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
							$('#row-<?php echo $line->rowid; ?>').children().eq(0).append(' - <?= $langs->trans('Asset'); ?> : <?= $link; ?>');
						});
					</script>
					<?php
				}
			}
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
				$('#product_desc').before('<div><span id="span_lot"> <?= $langs->trans('Asset'); ?> : </span><select id="lotAff" name="lotAff" class="flat"></select></div>');
				$('#lotAff').change(function(){
					$('#lot').val( $('#lotAff option:selected').val() );
				});
				$('#product_id').change( function(){
					$.ajax({
						type: "POST"
						,url: "<?= dol_buildpath('/asset/script/ajax.liste_asset.php', 1) ?>"
						,dataType: "json"
						,data: {
							fk_product: $('#product_id').val(),
							fk_soc : <?=$object->socid; ?>
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
								$('#lotAff').prepend('<option value="0">S&eacute;le ctionnez un <?= $langs->trans('Asset'); ?></option>');
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
        	?> 
			<script type="text/javascript">
				$('#addproduct').append('<input id="lot" type="hidden" value="0" name="lot" size="3">');
				$('#idprod').parent().parent().find(" > span:last").after('<span id="span_lot"> <?= $langs->trans('Asset'); ?> : </span><select id="lotAff" name="lotAff" class="flat"><option value="0" selected="selected">S&eacute;lectionnez un <?=$langs->trans('Asset');?></option></select>');
				$('#idprod').change( function(){
					$.ajax({
						type: "POST"
						,url: "<?= dol_buildpath('/asset/script/ajax.liste_asset.php', 1) ?>"
						,dataType: "json"
						,data: {
							fk_product: $('#idprod').val(),
							fk_soc : <?= $object->socid; ?>
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
								$('#lotAff').prepend('<option value="0" selected="selected">S&eacute;lectionnez un <?= $langs->trans('Asset'); ?></option>');
							}
							else{
								$('#lotAff').empty();
								$('#lotAff').prepend('<option value="0" selected="selected">S&eacute;lectionnez un <?= $langs->trans('Asset'); ?></option>');
							}
						});
				});
				$('#lotAff').change(function(){
					$('#lot').val( $('#lotAff option:selected').val() );
				});
			</script>
			<?php
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

		return 0;
	}
	
	function formCreateThirdpartyOptions($parameters, &$object, &$action, $hookmanager){

		if (in_array('pricesuppliercard',explode(':',$parameters['context']))) {
			
			echo '<tr id="newField">';
			echo '<td class="fieldrequired">';
			echo "Composé fourni";
			echo "</td>";
			echo "<td>";
			echo '<select name="selectOuiNon">';
			echo '<option value="Oui">Oui</option>';
			echo '<option value="Non">Non</option>';
			echo "</select>";
			echo "</td>";
			echo "</tr>";

        }
		
	}
	
}