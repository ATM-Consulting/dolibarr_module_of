
	[view.liste;strconv=no]

	<div class="tabsAction">
			<input class="butAction" type="submit" value="Enregistrer" name="bt_save" />
			<a href="#" class="butAction btnaddworkstation" id_assetOf="[assetOf.id]">Ajouter un poste</a>
	</div>
	<div id="dialog-workstation" title="Ajout d'un poste de travail"  style="display:none;">
		<table>
			<tr>
				<td>Postes de travail : </td>
				<td>
					[view.select_workstation;strconv=no]
				</td>
			</tr>
		</table>
	</div>
	<script type="text/javascript">
		
		$(".btnaddworkstation" ).click(function() {
				
				$( "#dialog-workstation" ).dialog({
					show: {
						effect: "blind",
						duration: 200
					},
					modal:true,
					buttons: {
						"Annuler": function() {
							$( this ).dialog( "close" );
						},				
						"Ajouter": function(){
							
							var fk_asset_workstation = $('#fk_asset_workstation').val();
							
							document.location.href="?fk_product=[view.fk_product]&action=add&fk_asset_workstation="+fk_asset_workstation;
						}
					}
				});
			});
			
			
	</script>
