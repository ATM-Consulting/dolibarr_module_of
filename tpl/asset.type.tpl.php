[onshow;block=begin;when [view.mode]=='view']
    [view.head;strconv=no]
[onshow;block=end]  

[onshow;block=begin;when [view.mode]!='view']
    [view.onglet;strconv=no]
    <div>
[onshow;block=end]  
		

<table width="100%" class="border">
	<tr><td width="20%">Libellé</td><td>[assetType.libelle; strconv=no]</td></tr>
	<tr><td width="20%">Code (facultatif)</td><td>[assetType.code; strconv=no]</td></tr>[assetType.supprimable; strconv=no]
	<tr><td width="20%">Masque de numérotation</td><td>[assetType.masque; strconv=no][assetType.info_masque; strconv=no]</td></tr>
	<tr><td width="20%">Gestion du stock</td><td>[assetType.gestion_stock; strconv=no]</td></tr>
	[onshow;block=begin;when [assetType.gestion_stock] != 'Unitaire']
		<tr><td width="20%">Type de l'unité</td><td>[assetType.measuring_units;strconv=no]</td></tr>
		<tr><td width="20%">Contenance maximum</td><td>[assetType.contenance_value;strconv=no] [assetType.contenance_units;strconv=no]</td></tr>
		<tr><td width="20%">Contenance par défaut</td><td>[assetType.contenancereel_value;strconv=no] [assetType.contenancereel_units;strconv=no]</td></tr>
		<tr><td width="20%">Point de chute</td><td>[assetType.point_chute; strconv=no]</td></tr>
	[onshow;block=end]
	[onshow;block=begin;when [view.mode]=='edit']
		<tr><td width="20%">Type de l'unité</td><td>[assetType.measuring_units;strconv=no]</td></tr>
		<tr><td width="20%">Contenance maximum</td><td>[assetType.contenance_value;strconv=no] [assetType.contenance_units;strconv=no]</td></tr>
		<tr><td width="20%">Contenance par défaut</td><td>[assetType.contenancereel_value;strconv=no] [assetType.contenancereel_units;strconv=no]</td></tr>
		<tr><td width="20%">Point de chute</td><td>[assetType.point_chute; strconv=no]</td></tr>
	[onshow;block=end]
	<tr><td width="20%">Réutilisable</td><td>[assetType.reutilisable; strconv=no]</td></tr>
	
</table>


[onshow;block=begin;when [view.mode]=='edit']
	<script type="text/javascript">
	 $(document).ready(function(){
	 	
	 	if($("#gestion_stock").find("option:selected").val() == 'UNIT'){
			$("#measuring_units").parent().parent().hide();
			$("#contenance_value").parent().parent().hide();
			$("#contenancereel_value").parent().parent().hide();
			$("#point_chute").parent().parent().hide();
			
			$("#contenance_value").val('1');
			$("#contenancereel_value").val('1');
			$("#point_chute").val('0');
 		}
	 	
	 	$("#gestion_stock").change(function(){
	 		if($(this).find("option:selected").val() == 'UNIT'){
				$("#measuring_units").parent().parent().hide();
				$("#contenance_value").parent().parent().hide();
				$("#contenancereel_value").parent().parent().hide();
				$("#point_chute").parent().parent().hide();
				
				$("#contenance_value").val('1');
				$("#contenancereel_value").val('1');
				$("#point_chute").val('0');
	 		}
	 		else{
	 			$("#measuring_units").parent().parent().show();
				$("#contenance_value").parent().parent().show();
				$("#contenancereel_value").parent().parent().show();
				$("#point_chute").parent().parent().show();
	 		}
	 	})
	 	
	 	$( "#sortable" ).css('cursor','pointer');
		$(function() {
			$( "#sortable" ).sortable({
			   stop: function(event, ui) {
					var result = $('#sortable').sortable('toArray'); 
					for (var i = 0; i< result.length; i++){
						$(".ordre"+result[i]).attr("value", i)
						}
				}
			});
		});
	});
	
	
	function loadMeasuringUnits(obj)
	{
		var type_unit = $(obj).val();
		
		$.ajax({
			url:'script/interface.php?get=measuringunits'
			,data: {
				type: type_unit,
				name: 'contenance_units'
			}
			,dataType: 'json'
			,success: function(html) {
				$('select[name=contenance_units]').remove();
				$('input#contenance_value').after(html);	
			}
		});
		
		$.ajax({
			url:'script/interface.php?get=measuringunits'
			,data: {
				type: type_unit,
				name: 'contenancereel_units'
			}
			,dataType: 'json'
			,success: function(html) {
				$('select[name=contenancereel_units]').remove();
				$('input#contenancereel_value').after(html);	
			}
		});
		
	}
	</script>
[onshow;block=end]

</div>


[onshow;block=begin;when [view.mode]!='edit']
	<div class="tabsAction">
		<a href="?id=[assetType.id]&action=edit" class="butAction">Modifier</a>
		<span class="butActionDelete" id="action-delete"  
		onclick="if (window.confirm('Voulez vous supprimer l\'élément ?')){document.location.href='?id=[assetType.id]&action=delete'};">Supprimer</span>
	</div>
[onshow;block=end]	

[onshow;block=begin;when [view.mode]=='edit']
	<div class="tabsAction" style="text-align:center;">
		<input type="submit" value="Enregistrer" name="save" class="button"> 
		&nbsp; &nbsp; <input type="button" value="Annuler" name="cancel" class="button" onclick="document.location.href='?id=[assetType.id]'">
	</div>
[onshow;block=end]

<div style="clear:both"></div>

