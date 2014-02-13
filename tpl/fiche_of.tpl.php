<style type="text/css">
	.draft, .draftedit,.nodraft,.viewmode {
		
		display:none;
		
	}
	
</style>


[onshow;block=begin;when [view.mode]=='view']

	
				
[onshow;block=end]				
				
			<table width="100%" class="border">
				<tr><td width="20%">Numéro</td><td>[assetOf.numero;strconv=no]</td></tr>
				<tr><td>Ordre</td><td>[assetOf.ordre;strconv=no;protect=no]</td></tr>
				<tr><td>Date du besoin</td><td>[assetOf.date_besoin;strconv=no]</td></tr>
				<tr><td>Date de lancement</td><td>[assetOf.date_lancement;strconv=no]</td></tr>
				<tr><td>Temps estimé de fabrication (h)</td><td>[assetOf.temps_estime_fabrication;strconv=no]</td></tr>
				<tr><td>Temps réel de fabrication (h)</td><td>[assetOf.temps_reel_fabrication;strconv=no]</td></tr>
				<tr><td>Statut</td><td>[assetOf.status;strconv=no]
					[onshow;block=begin;when [view.status]!='CLOSE']
					<span class="viewmode">, passer à l'état : 
					[onshow;block=begin;when [view.status]=='DRAFT']
						<input type="submit" onclick="return confirm('Valider cet Ordre de Fabrication?');" class="butAction" name="valider" value="Valider">
					[onshow;block=end]
					[onshow;block=begin;when [view.status]=='VALID']
						&nbsp; &nbsp; <input type="submit" onclick="return confirm('Lancer cet Ordre de Fabrication?');" class="butAction" name="lancer" value="Lancer la production">
					[onshow;block=end]
					[onshow;block=begin;when [view.status]=='OPEN']
						&nbsp; &nbsp; <a href="?id=[assetOf.id]&action=terminer" onclick="return confirm('Terminer cet Ordre de Fabrication?');" class="butAction">Terminer</a>
					[onshow;block=end]
					[onshow;block=end]
					</span>
				</td></tr>
			</table>
			
		<div class="" style="margin-top: 25px;">
			<div style="text-align: right;height:40px;" class="draftedit">
				<a href="#" class="butAction btnaddworkstation">Ajouter un poste</a>
			</div>
			<table width="100%" class="border workstation">
				<tr style="background-color:#dedede;">
					<th>Poste de travail</th>
					<th>Nb. heures prévues</th>
					<th>Nb. heures réelles</th>
					<th class="draftedit">Action</th>
				</tr>
				<tr id="WS[workstation.id]" style="background-color:#fff;">
					<td>[workstation.libelle;block=tr]</td>
					<td>[workstation.nb_hour;strconv=no]</td>
					<td>[workstation.nb_hour_real;strconv=no]</td>
					<td class="draftedit">[workstation.delete;strconv=no]</td>
				</tr>
				<tr>
					<td colspan="4" align="center">[workstation;block=tr;nodata]Aucun poste de travail défini</td>
				</tr>
			</table>
		</div>


		<div class="" style="margin-top: 25px;">
			<table width="100%" class="border">
				<tr height="40px;">
					<td style="border-right: none;">Produits nécessaires à la fabrication</td>
					<td style="border-left: none; text-align: right;">
						
						<a href="#" class="butAction btnaddproduct draftedit" id="NEEDED">Ajouter produit</a>
						
					</td>
					<td style="border-right: none; ">Produits à créer</td>
					<td style="border-left: none; text-align: right;">
						
						<a href="#" class="butAction btnaddproduct draftedit" id="TO_MAKE">Ajouter produit</a>
						
					</td>
				</tr>
				<tr style="background-color:#fff;">
					<td colspan="2" width="60%" valign="top">
						<!-- NEEDED -->
						<table width="100%" class="border needed">
							<tr style="background-color:#dedede;">
								<!--<td>Lot</td>
								<td>Equipement</td>-->
								<td>Produit</td>
								<td>Quantité nécessaire</td>
								<td>Quantité réelle</td>
								<td class="nodraft">Quantité utilisée</td>
								<td class="draft">Delta</td>
								<td class="draftedit" style="width:20px;">Action</td>
								
							</tr>
							<tr id="[TNeeded.id]">
								<!--<td>Lot</td>
								<td>Equipement</td>-->
								<td>[TNeeded.libelle;block=tr;strconv=no]</td>
								<td>[TNeeded.qty_needed]</td>
								<td>[TNeeded.qty;strconv=no]</td>
								<td class="nodraft">[TNeeded.qty_used;strconv=no]</td>
								
								<td class="draft">[TNeeded.qty_toadd]</td>
								<td class="draftedit">[TNeeded.delete;strconv=no]</td>
								
							</tr>
						</table>
					</td> 
					<td colspan="2" width="40%" valign="top">
						<!-- TO_MAKE -->
						<table width="100%" class="border tomake">
							<tr style="background-color:#dedede;">
								<td class="draftedit" style="width:20px;">Action</td>
								<td>Produit</td>
								<td>Quantité à produire</td>
								<td>Fournisseur</td>
								<td class="draftedit" style="width:20px;">Action</td>
								
							</tr>
							<tr id="[TTomake.id]">
								
								<td class="draftedit">[TTomake.addneeded;strconv=no]</td>
								
								<td>[TTomake.libelle;block=tr;strconv=no]</td>
								<td>[TTomake.qty;strconv=no]</td>
								<td>[TTomake.fk_product_fournisseur_price;strconv=no]</td>
								
								<td class="draftedit">[TTomake.delete;strconv=no]</td>
								
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>


[onshow;block=begin;when [view.mode]=='view']
	<div class="tabsAction">
		
		<input type="button" id="action-delete" value="Supprimer" name="cancel" class="butActionDelete" onclick="document.location.href='?action=delete&id=[assetOf.id]'">
		&nbsp; &nbsp; <a href="?id=[assetOf.id]&action=edit" class="butAction">Modifier</a>

		
	</div>
[onshow;block=end]
	
[onshow;block=begin;when [view.mode]!='view']

		<p align="center">
			[onshow;block=begin;when [view.mode]!='add']
				<input type="submit" value="Enregistrer" name="save" class="button">
				&nbsp; &nbsp; <input type="button" value="Annuler" name="cancel" class="button" onclick="document.location.href='?id=[assetOf.id]'">
			[onshow;block=end]
		</p>
[onshow;block=end]	

	<div id="dialog" title="Ajout de Produit">
		<table>
			<tr>
				<td>Produit : </td>
				<td>
					[view.select_product;strconv=no]
				</td>
			</tr>
		</table>
	</div>
	<div id="dialog-workstation" title="Ajout d'un poste de travail">
		<table>
			<tr>
				<td>Postes de travail : </td>
				<td>
					[view.select_workstation;strconv=no]
				</td>
			</tr>
		</table>
	</div>
</div>
	
	<div style="clear:both;"></div>
	<div id="assetChildContener">
		
		<h2>Asset Child</h2>
	
	</div>
	
	<script type="text/javascript">
		
			var Tid = new Array([assetOf.idChild]);
		
			for(x in Tid ){
				
				$.get("fiche_of.php?id="+Tid[x], function(data) {
					var html = $(data).find('div.OFContent');
					
					$('#assetChildContener').append(html );
				});
				
			}
			
			if(Tid.length==0) {
				$('#assetChildContener').hide();
			}
		

		$(document).ready(function() {
			var type = "";
			
			$( "#dialog" ).dialog({
				autoOpen: false,
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
						var idassetOf = [assetOf.id];
						var fk_product = $('#fk_product').val();
						
						$.ajax({
							url : "script/interface.php?get=addofproduct&id_assetOf="+idassetOf+"&fk_product="+fk_product+"&type="+type
						})
						.done(function(){
							//document.location.href="?id=[assetOf.id]";
							$( "#dialog" ).dialog("close");
							refreshTab();
						});
					}
				}
			});
			
			
			$( "#dialog-workstation" ).dialog({
				autoOpen: false,
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
						var idassetOf = [assetOf.id];
						var fk_asset_workstation = $('#fk_asset_workstation').val();
						
						$.ajax({
							url : "script/interface.php?get=addofworkstation&id_assetOf="+idassetOf+"&fk_asset_workstation="+fk_asset_workstation
						})
						.done(function(){
							//document.location.href="?id=[assetOf.id]";
							$( "#dialog-workstation" ).dialog("close");
							refreshTab();
						});
					}
				}
			});
			
			
			
			$(".btnaddproduct" ).click(function() {
				type = $(this).attr('id');
				$( "#dialog" ).dialog( "open" );
			});
			
			$(".btnaddworkstation" ).click(function() {
				$( "#dialog-workstation" ).dialog( "open" );
			});
			
			$("input[name=valider]").click(function(){
				$('#action').val('valider');
			})
			$("input[name=lancer]").click(function(){
				$('#action').val('lancer');
			})
			
			
			refreshDisplay();
			
		});
		
		function refreshDisplay() {
			
			if('[view.mode]'=='view'){ 
				$('span.viewmode').css('display','inline');
			}
			
			[onshow;block=begin;when [view.status]=='DRAFT']
			
				$('td.draft').css('display','table-cell');
				
						
				if('[view.mode]'!='view'){
					$('div.draftedit').css('display','block');
					$('a.draftedit').css('display','inline');
					$('td.draftedit,th.draftedit').css('display','table-cell');
				} 
			
			[onshow;block=end]
			
			[onshow;block=begin;when [view.status]!='DRAFT']
			
				$('td.nodraft').css('display','table-cell');
			
			[onshow;block=end]
			
		}
		
		function refreshTab() {
			var id = [assetOf.id];
			
			$.get("fiche_of.php?action=edit&id="+id , function(data) {
		     	$('div.OFContent[rel='+id+'] table.needed').replaceWith(  $(data).find('div.OFContent[rel='+id+'] table.needed') );
		     	$('div.OFContent[rel='+id+'] table.tomake').replaceWith(  $(data).find('div.OFContent[rel='+id+'] table.tomake') );
		     	$('div.OFContent[rel='+id+'] table.workstation').replaceWith(  $(data).find('div.OFContent[rel='+id+'] table.workstation') );
		     	
		     	refreshDisplay();
			});
			
		}
		
		function deleteLine(idLine,type){
			$.ajax(
				{url : "script/interface.php?get=deletelineof&idLine="+idLine+"&type="+type}
			).done(function(){
				$("#"+idLine).remove();
			});
		}
		function deleteWS(idWS) {
			$.ajax(
				{url : "script/interface.php?get=deleteofworkstation&id_assetOf=[assetOf.id]&fk_asset_workstation_of="+idWS }
			).done(function(){
				refreshTab() ;
			});
			
		}
		
		
		function addAllLines(idLine,btnadd){
			//var qty = $('#qty['+idLine+']').val();
			var qty = $(btnadd).parent().next().next().find('input[type=text]').val();
			$.ajax(
				{url : "script/interface.php?get=addlines&idLine="+idLine+"&qty="+qty}
			).done(function(){
				refreshTab() 
			});
		}
</script>
