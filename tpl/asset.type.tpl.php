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
	<tr><td width="20%">Masque</td><td>[assetType.masque; strconv=no]</td></tr>
	<tr><td width="20%">Point de chute</td><td>[assetType.point_chute; strconv=no]</td></tr>
	<tr><td width="20%">Gestion du stock</td><td>[assetType.gestion_stock; strconv=no]</td></tr>
	<tr><td width="20%">Réutilisable</td><td>[assetType.reutilisable; strconv=no]</td></tr>
	<tr><td width="20%">Contenance maximum</td><td>[assetType.contenance_value;strconv=no] [assetType.contenance_units;strconv=no]</td></tr>
	<tr><td width="20%">Contenance par défaut</td><td>[assetType.contenancereel_value;strconv=no] [assetType.contenancereel_units;strconv=no]</td></tr>
</table>

[onshow;block=begin;when [view.mode]=='edit']
	<script>
	 $(document).ready(function(){
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

