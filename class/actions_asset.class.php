<?php
class ActionsAsset
{ 
     /** Overloading the doActions function : replacing the parent's function with the one below 
      *  @param      parameters  meta datas of the hook (context, etc...) 
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
      *  @param      action             current action (if set). Generally create or edit or null 
      *  @return       void 
      */ 
    function formEditProductOptions($parameters, &$object, &$action, $hookmanager) 
    {
    	/*ini_set('dysplay_errors','On');
		error_reporting(E_ALL);*/ 
    	global $db;
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		
		//Commandes et Factures
    	if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context'])))
        {
        	?> 
			<script type="text/javascript">
				$('input[name=token]').prev().append('<input id="lot" type="hidden" value="0" name="lot" size="3">');
				$('#search_idprod').after('<span id="span_lot"> Batch : </span><select id="lotAff" name="lotAff" class="flat"></select>');
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
										test = false;
									}
									else{
										$('#lotAff').prepend('<option value="'+option.lot+'" selected="selected">'+option.lotAff+'</option>');
										test = true;
									}
								})
								if(!test)
									$('#lotAff').prepend('<option value="0" selected="selected">S&eacute;lectionnez un lot</option>');
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
			
			$this->resprints='';
        }
 
        /*$this->results=array('myreturn'=>$myvalue);
        $this->resprints='';
 */
        return 0;
    }

	/*function formCreateProductOptions ($parameters, &$object, &$action, $hookmanager) {
		
		global $db;
		
		/*echo '<pre>';
		print_r($parameters);
		echo '</pre>';
		
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		
		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	?> 
			<script type="text/javascript">
				$('input[name=token]').prev().append('<input id="lot" type="hidden" value="0" name="poids" size="3">');
				$('#add_product_area').after('<span id="span_lot"> et/ou </span><select id="lotAff" name="lotAff" class="flat"></select>');
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
									$('#lotAff').prepend('<option value="'+option.lot+'">'+option.lot+'</option>');
								})
								$('#lotAff').prepend('<option value="0" selected="selected">S&eacute;lectionnez un lot</option>');
							}
							else{
								$('#lotAff, #span_lot').hide();
							}
						});
				});
				$('#lotAff').change($('#lot').val( $('#lotAff option:selected').val() ))
			</script>
			<?php
        }

		return 0;
	}*/

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
}