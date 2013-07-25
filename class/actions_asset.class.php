<?php
class ActionsAsset
{ 
     /** Overloading the doActions function : replacing the parent's function with the one below 
      *  @param      parameters  meta datas of the hook (context, etc...) 
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
      *  @param      action             current action (if set). Generally create or edit or null 
      *  @return       void 
      */
      
    function formObjectOptions($parameters, &$object, &$action, $hookmanager) 
    {  
      	global $db;
		
		/*echo '<pre>';
		print_r($object);
		echo '</pre>';exit;*/
		
		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	foreach($object->lines as $line){
        		/*echo '<pre>';
				print_r($object);
				echo '</pre>';*/
	        	$resql = $db->query('SELECT asset_lot FROM '.MAIN_DB_PREFIX.$object->table_element_line.' WHERE rowid = '.$line->rowid);
				$res = $db->fetch_object($resql);
				
				if(!is_null($res->asset_lot))
				{
		        	?> 
					<script type="text/javascript">
						$(document).ready(function(){
							$('#row-<?php echo $line->rowid; ?> :first-child > td').append(' - <?php echo $res->asset_lot; ?>');
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
    	global $db;
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		
		/*echo '<pre>';
		print_r($parameters["line"]->rowid);
		echo '</pre>';exit;*/
		
		//Commandes et Factures
    	if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context'])))
        {
        	$resql = $db->query('SELECT asset_lot FROM '.MAIN_DB_PREFIX.$object->table_element_line.' WHERE rowid = '.$parameters["line"]->rowid);
			$res = $db->fetch_object($resql);
        	?> 
			<script type="text/javascript">
			$(document).ready(function(){
				$('input[name=token]').prev().append('<input id="lot" type="hidden" value="0" name="lot" size="3">');
				$('#product_desc').after('<span id="span_lot"> Batch : </span><select id="lotAff" name="lotAff" class="flat"></select>');
				$('#lotAff').change(function(){
						$('#lot').val( $('#lotAff option:selected').val() );
				});
				$('#product_id').change( function(){
					$.ajax({
						type: "POST"
						,url: "<?=DOL_URL_ROOT; ?>/custom/asset/script/ajax.liste_lot.php"
						,dataType: "json"
						,data: {fk_product: $('#product_id').val()}
						},"json").then(function(select){
							if(select.length > 0){
								$.each(select, function(i,option){
									if(option.lot == "<?php echo $res->asset_lot; ?>")
										$('#lotAff').prepend('<option value="'+option.lot+'" selected="selected">'+option.lotAff+'</option>');
									else
										$('#lotAff').prepend('<option value="'+option.lot+'">'+option.lotAff+'</option>');
								})
								$('#lotAff').prepend('<option value="0">S&eacute;lectionnez un lot</option>');
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
		
		global $db;
		
		/*echo '<pre>';
		print_r($parameters);
		echo '</pre>';*/
		
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		
		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	?> 
			<script type="text/javascript">
				$('#addpredefinedproduct').append('<input id="lot" type="hidden" value="0" name="lot" size="3">');
				$('#idprod').after('<span id="span_lot"> Batch : </span><select id="lotAff" name="lotAff" class="flat"></select>');
				$('#lotAff, #span_lot').hide();
				$('#idprod').change( function(){
					$.ajax({
						type: "POST"
						,url: "<?=DOL_URL_ROOT; ?>/custom/asset/script/ajax.liste_lot.php"
						,dataType: "json"
						,data: {fk_product: $('#idprod').val()}
						},"json").then(function(select){
							if(select.length > 0){
								$('#lotAff').empty().show();
								$('#span_lot').show();
								$.each(select, function(i,option){
									if(select.length > 1){
										$('#lotAff').prepend('<option value="'+option.lot+'">'+option.lotAff+'</option>');
									}
									else{
										$('#lotAff').prepend('<option value="'+option.lot+'" selected="selected">'+option.lotAff+'</option>');
									}
								})
								$('#lotAff').prepend('<option value="0" selected="selected">S&eacute;lectionnez un batch</option>');
							}
							else{
								$('#lotAff, #span_lot').hide();
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
		
		echo '<pre>';
		print_r($object);
		echo '</pre>';exit;
		
		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
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
}