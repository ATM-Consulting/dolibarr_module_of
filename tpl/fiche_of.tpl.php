<style type="text/css">
	/* Nécessaire pour cacher les informations qui ne doivent pas être accessibles à la 1ere étape de création d'un OF :: C'est très sale */ 
	[onshow;block=begin;when [assetOf.id]==0]
	.draft, .draftedit,.nodraft,.viewmode,.of-details {
		display:none;		
	}		
	[onshow;block=end]
	
	[onshow;block=begin;when [assetOf.id]!=0]
	#status {
		display:none;
	}
	[onshow;block=end]
</style>		
	<div class="OFMaster" assetOf_id="[assetOf.id]" fk_assetOf_parent="[assetOf.fk_assetOf_parent]">		
		<form id="formOF[assetOf.id]" name="formOF[assetOf.id]" action="fiche_of.php" method="POST">
				[onshow;block=begin;when [view.status]=='CLOSE']
					<input type="hidden" name="action" value="save">
				[onshow;block=end]
				[onshow;block=begin;when [view.status]=='DRAFT']
					<input type="hidden" name="action" value="[assetOf.id;noerr;if [val]!=0;then 'valider';else 'create']">
				[onshow;block=end]
				[onshow;block=begin;when [view.status]=='VALID']
					<input type="hidden" name="action" value="lancer">
				[onshow;block=end]
				[onshow;block=begin;when [view.status]=='OPEN']
					<input type="hidden" name="action" value="terminer">
				[onshow;block=end]
				
				<input type="hidden" name="fk_product_to_add" value="[assetOf.fk_product_to_add]">		
				<input type="hidden" value="[assetOf.id]" name="id">
				
			<table width="100%" class="border">
				
				<tr><td width="20%">Numéro</td><td>[assetOf.numero;strconv=no]</td></tr>
				<tr><td>Ordre</td><td>[assetOf.ordre;strconv=no;protect=no]</td></tr>
				<tr class="notinparentview"><td>OF Parent</td><td>[assetOf.link_assetOf_parent;strconv=no;protect=no;magnet=tr]</td></tr>
				<tr class="notinparentview"><td>Commande</td><td>[assetOf.fk_commande;strconv=no;magnet=tr]</td></tr>
				<tr class="notinparentview"><td>Commande Fournisseur</td><td>[assetOf.commande_fournisseur;strconv=no;magnet=tr] - [assetOf.statut_commande;strconv=no;magnet=tr]</td></tr>
				<tr><td>Client</td><td>[assetOf.fk_soc;strconv=no;protect=no;magnet=tr]</td></tr>
				<tr><td>Date du besoin</td><td>[assetOf.date_besoin;strconv=no]</td></tr>
				<tr><td>Date de lancement</td><td>[assetOf.date_lancement;strconv=no]</td></tr>
				<tr><td>Temps estimé de fabrication</td><td>[assetOf.temps_estime_fabrication;strconv=no] heure(s)</td></tr>
				<tr><td>Temps réel de fabrication</td><td>[assetOf.temps_reel_fabrication;strconv=no] heure(s)</td></tr>
				<tr><td>Statut</td><td><span style="display:none;">[assetOf.status;strconv=no]</span>[assetOf.statustxt;strconv=no]
					[onshow;block=begin;when [view.status]!='CLOSE';when [view.mode]=='view']
						<span class="viewmode notinparentview">, passer à l'état :
						[onshow;block=begin;when [view.status]=='DRAFT']
							<input type="button" onclick="if (confirm('Valider cet Ordre de Fabrication ?')) { submitForm([assetOf.id]); }" class="butAction" name="valider" value="Valider">
						[onshow;block=end]
						[onshow;block=begin;when [view.status]=='VALID']
							<input type="button" onclick="if (confirm('Lancer cet Ordre de Fabrication ?')) { submitForm([assetOf.id]); }" class="butAction" name="lancer" value="Production en cours">
						[onshow;block=end]
						[onshow;block=begin;when [view.status]=='OPEN']
							<input type="button" onclick="if (confirm('Terminer cet Ordre de Fabrication ?')) { submitForm([assetOf.id]); }" class="butAction" name="terminer" value="Terminer">
							<!-- <a href="[assetOf.url]?id=[assetOf.id]&action=terminer" onclick="return confirm('Terminer cet Ordre de Fabrication ?');" class="butAction">Terminer</a> -->
						[onshow;block=end]
					[onshow;block=end]
					</span>
				</td></tr>
				
				<tr><td>Note</td><td>[assetOf.note;strconv=no]</td></tr>
				
			</table>
			
			<div class="of-details" style="margin-top: 25px;">
				<div style="text-align: right;height:40px;" class="draftedit">
					[onshow;block=begin;when [view.mode]!='view']
						<a href="#" class="butAction btnaddworkstation" id_assetOf="[assetOf.id]">Ajouter un poste</a>
					[onshow;block=end]
				</div>
				<table width="100%" class="border workstation">
					<tr style="background-color:#dedede;">
						<th>Poste de travail</th>
						[onshow;block=begin;when [view.defined_user_by_workstation]=='1']
							<th>Utilisateur associé</th>
						[onshow;block=end]
						<th>Nb. heures prévues</th>
						<th>Nb. heures réelles</th>
						<th class="draftedit">Action</th>
					</tr>
					<tr id="WS[workstation.id]" style="background-color:#fff;">
						<td>[workstation.libelle;strconv=no;block=tr]</td>
						[onshow;block=begin;when [view.defined_user_by_workstation]=='1']
							<td align='center'>[workstation.fk_user;strconv=no]</td>
						[onshow;block=end]
						<td align='center'>[workstation.nb_hour;strconv=no]</td>
						<td align='center'>[workstation.nb_hour_real;strconv=no]</td>
						<td align='center' class="draftedit">[workstation.delete;strconv=no]</td>
					</tr>
					<tr>
						<td colspan="4" align="center">[workstation;block=tr;nodata]Aucun poste de travail défini</td>
					</tr>
				</table>
			</div>
	
	
			<div class="of-details" style="margin-top: 25px;">
				<table width="100%" class="border">
					<tr height="40px;">
						<td style="border-right: none;">&nbsp;&nbsp;<b>Produits nécessaires à la fabrication</b></td>
						<td style="border-left: none; text-align: right;">
							[onshow;block=begin;when [view.mode]!='view']
								<a href="#" class="butAction btnaddproduct draftedit" id_assetOf="[assetOf.id]" rel="NEEDED">Ajouter produit</a>						
							[onshow;block=end]
						</td>
						<td style="border-right: none; ">&nbsp;&nbsp;<b>Produits à créer</b></td>
						<td style="border-left: none; text-align: right;">
							[onshow;block=begin;when [view.mode]!='view']
								<a href="#" class="butAction btnaddproduct draftedit" id_assetOf="[assetOf.id]" rel="TO_MAKE">Ajouter produit</a>
							[onshow;block=end]
						</td>
					</tr>
					<tr style="background-color:#fff;">
						<td colspan="2" width="50%" valign="top">
							<!-- NEEDED -->
							<table width="100%" class="border needed">
								<tr style="background-color:#dedede;">
									[onshow;block=begin;when [view.use_lot_in_of]=='1']
										<td width="20%">Lot</td>
									[onshow;block=end]
									<!--<td>Equipement</td>-->
									<td>Produit</td>
									<td>Quantité nécessaire</td>
									<td>Quantité réelle</td>
									<td class="nodraft">Quantité utilisée</td>
									<!-- <td class="draft">Delta</td> -->
									[onshow;block=begin;when [view.defined_workstation_by_needed]=='1']
										<td width="20%">Poste</td>
									[onshow;block=end]
									<td class="draftedit" style="width:20px;">Action</td>
									
								</tr>
								<tr id="[TNeeded.id]">
									[onshow;block=begin;when [view.use_lot_in_of]=='1']
										<td>[TNeeded.lot_number;strconv=no]</td>
									[onshow;block=end]
									<!--<td>Equipement</td>-->
									<td>[TNeeded.libelle;block=tr;strconv=no]</td>
									<td>[TNeeded.qty_needed]</td>
									<td>[TNeeded.qty;strconv=no]</td>
									<td class="nodraft">[TNeeded.qty_used;strconv=no]</td>
									<!-- <td class="draft">[TNeeded.qty_toadd]</td> -->
									[onshow;block=begin;when [view.defined_workstation_by_needed]=='1']
										<td width="20%">[TNeeded.workstations;strconv=no]</td>
									[onshow;block=end]
									<td align='center' class="draftedit">[TNeeded.delete;strconv=no]</td>
									
								</tr>
							</table>
						</td> 
						<td colspan="2" width="50%" valign="top">
							<!-- TO_MAKE -->
							<table width="100%" class="border tomake">
								<tr style="background-color:#dedede;">
									<td class="draftedit" style="width:20px;">Action</td>
									[onshow;block=begin;when [view.use_lot_in_of]=='1']
										<td>Lot</td>
									[onshow;block=end]
									<td>Produit</td>
									<td>Quantité à produire</td>
									<td>Fournisseur</td>
									<td class="draftedit" style="width:20px;">Action</td>
									
								</tr>
								<tr id="[TTomake.id]">
									<td align='center' class="draftedit">[TTomake.addneeded;strconv=no]</td>
									[onshow;block=begin;when [view.use_lot_in_of]=='1']
										<td>[TTomake.lot_number;strconv=no]</td>
									[onshow;block=end]
									<td>[TTomake.libelle;block=tr;strconv=no]</td>
									<td>[TTomake.qty;strconv=no]</td>
									<td>[TTomake.fk_product_fournisseur_price;strconv=no]</td>
									
									<td align='center' class="draftedit">[TTomake.delete;strconv=no]</td>
									
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>

			[onshow;block=begin;when [view.mode]=='view']
				<div class="tabsAction notinparentview buttonsAction">
					
					<input type="button" id="action-delete" value="Supprimer" name="cancel" class="butActionDelete" onclick="if(confirm('Supprimer cet Ordre de Fabrication?'))document.location.href='?action=delete&id=[assetOf.id]'">
					&nbsp; &nbsp; <a href="[assetOf.url]?id=[assetOf.id]&action=edit" class="butAction">Modifier</a>
					&nbsp; &nbsp; <a name="createFileOF" class="butAction notinparentview" href="[assetOf.url]?id=[assetOf.id]&action=createDocOF">Imprimer</a>
					
				</div>
			[onshow;block=end]

			[onshow;block=begin;when [view.mode]!='view']
				<p align="center">
					[onshow;block=begin;when [view.mode]!='add']
						<br />
						<input type="submit" value="Enregistrer" name="save" class="button">
						&nbsp; &nbsp; <input type="button" value="Annuler" name="cancel" class="button" onclick="document.location.href='[assetOf.url_liste]'">
						<br /><br />
					[onshow;block=end]
				</p>
			[onshow;block=end]	

		</form>

	</div><!-- fin de OFMaster -->
	<div id="dialog" title="Ajout de Produit" style="display:none;width: 100%;">
		<table>
			<tr>
				<td>Produit : </td>
				<td>
					[view.select_product;strconv=no]
				</td>
			</tr>
		</table>
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
</div>
	
	<div style="clear:both;"></div>
		<div id="assetChildContener" [view.hasChildren;noerr;if [val]==0;then 'style="display:none"';else '']>
			<h2 id="titleOFEnfants">OF Enfants</h2>
		</div>
	<script type="text/javascript">
		
		$(document).ready(function() {
			var type = "";
			
			/* Le 1er formulaire s'enregistre sans ajax, donc je prend la précaution de ne pas passer au statut suivant lors de l'enregistrement 
			 ni de sauter l'étape de création */
			var formParent = $("div.OFMaster:first form");
			formParent.find("input[type=submit]").unbind().click(function() {
				var action = formParent.children("input[name=action]").val();
				if (action != "save" && action != "create") formParent.children("input[name=action]").val("save");
			});
			
			if([assetOf.id]>0) {
				getChild();
				refreshDisplay();
			}
			
		});
		
		function getChild() {
			
			$('#assetChildContener > *:not("#titleOFEnfants")').remove();
			$('#assetChildContener').append('<p align="center" style="padding:10px; background:#fff;"><img src="img/loading.gif" /></p>');
			$('#assetChildContener').show();
			$.ajax({
				
				url:'script/interface.php?get=getofchildid&id=[assetOf.id]&json=1'
				,dataType:'json'
				
				,success: function(Tid) {
							
					if(Tid.length==0) {
						$('#assetChildContener').hide();
					}
					else {
						$('#assetChildContener > *:not("#titleOFEnfants")').remove();
						for(x in Tid ){
						
							$.ajax({
								url : "[assetOf.url]"
								,async: false
								,data:{
									action:"[view.actionChild]"
									,id:Tid[x]
								}
								,type: 'POST'
							}).done(function(data) {
								
								var html = $(data).find('div.OFMaster');
								html.find('.buttonsAction').remove();
								
								var TAssetOFLineLot = html.find('input.TAssetOFLineLot');
								for (var i = 0; i < TAssetOFLineLot.length; i++)
								{
									$(TAssetOFLineLot).attr('disabled', 'true').css('border', 'none').css('background', 'none');
								}
								
								var id_form = html.find('form').attr('id');
								
								$('#assetChildContener').append(html);
								
								refreshDisplay();
								
								$('select[name^=TAssetOFLine]').change(function(){
									compose_fourni = $(this).find('option:selected').attr('compose_fourni');
									//alert(compose_fourni);
									assetOf_id = $(this).closest('.OFMaster').attr('assetof_id');
									//alert(assetOf_id);
									if(compose_fourni == 0){
										//alert($('.OFMaster[fk_assetOf_parent='+assetOf_id+']').length);
										$('.OFMaster[fk_assetOf_parent='+assetOf_id+']').css('border' , '5px solid red');
									}
									else{
										$('.OFMaster[fk_assetOf_parent='+assetOf_id+']').css('border' , '0px none');
									}
								});
								
									$("#"+id_form).submit(function() {
										var targetForm = this;
										
										if ($(this).attr('rel') == 'noajax') return true;
										
										var oldInputAction = $(targetForm).children('input[name=action]').val();
										$(targetForm).children('input[name=action]').val('save');
										$.ajax({
											url: $(targetForm).attr('action'),
											data: $(targetForm).serialize(),
											type: 'POST',
											async: false,
											success: function () {
												$(targetForm).css('border' , '5px solid green');
												
												$(targetForm).animate({ "border": "5px solid green" }, 'slow');
												$(targetForm).animate({ "border": "0px" }, 'slow');
																							
												$.jnotify('Modifications enregistr&eacute;es', "ok");
											},
											error: function () {
												$.jnotify('Une erreur c\'est produite', "error");
											}
										});
									
										$(targetForm).children('input[name=action]').val(oldInputAction);
										return false;
									});
							});
							
						}
						
					}
			
				}
			});
		
		}

		function submitForm(assetOFId) {
			$('#formOF'+assetOFId).attr('rel', 'noajax').submit();	
		}

		function refreshDisplay() {
			$(".btnaddproduct" ).unbind().click(function() {
				var type = $(this).attr('rel');
				var idassetOf = $(this).attr('id_assetOf');
				
				$( "#dialog" ).dialog({
					show: {
						effect: "blind",
						duration: 200
					},
					width: 500,
					modal:true,
					buttons: {
						"Annuler": function() {
							$( this ).dialog( "close" );
						},				
						"Ajouter": function(){
							var fk_product = $('#fk_product').val();
							
							$.ajax({
								url : "script/interface.php?get=addofproduct&id_assetOf="+idassetOf+"&fk_product="+fk_product+"&type="+type
							})
							.done(function(){
								//document.location.href="?id=[assetOf.id]";
								$( "#dialog" ).dialog("close");
								refreshTab(idassetOf, 'edit');
								getChild();
							});
						}
					}
				});
				
			});
			
			$(".btnaddworkstation" ).unbind().click(function() {
				var from = $(this);
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
							var idassetOf = from.attr('id_assetOf');
							var fk_asset_workstation = $('#fk_asset_workstation').val();
							
							$.ajax({
								url : "script/interface.php?get=addofworkstation&id_assetOf="+idassetOf+"&fk_asset_workstation="+fk_asset_workstation
							})
							.done(function(){
								//document.location.href="?id=[assetOf.id]";
								$( "#dialog-workstation" ).dialog("close");
								refreshTab(idassetOf, '[view.mode]');
							});
						}
					}
				});
			});
			
			if([assetOf.id]>0) {
				$('div.of-details').show();
			}
			
			if('[view.mode]'=='view'){ 
				$('span.viewmode').css('display','inline');
			}
			
			if("[view.actionChild]"=="edit") {
					$('#assetChildContener div.draftedit').css('display','block');
					$('#assetChildContener a.draftedit').css('display','inline');
					$('#assetChildContener td.draftedit,th.draftedit').css('display','table-cell');
			}
			
			if("[view.mode]"=="edit") {
					$('#assetChildContener div.draftedit').css('display','block');
					$('#assetChildContener a.draftedit').css('display','inline');
					$('#assetChildContener td.draftedit,th.draftedit').css('display','table-cell');
			}
			
			if("[view.status]"=='DRAFT') {
			
				$('div.OFMaster td.draft').css('display','table-cell');
				
						
				if('[view.mode]'!='view'){
					$('div.OFMaster div.draftedit').css('display','block');
					$('div.OFMaster a.draftedit').css('display','inline');
					$('div.OFMaster td.draftedit,div.OFMaster th.draftedit').css('display','table-cell');
				} 
			
			}
			else {
				$('td.nodraft').css('display','table-cell');
			}
			
			[onshow;block=begin;when [view.use_lot_in_of]==1]
				$(".TAssetOFLineLot").each(function(){
					fk_product = $(this).attr('fk_product');
					$(this).autocomplete({
						source: "script/interface.php?get=autocomplete&json=1&fieldcode=lot_number&fk_product="+fk_product,
						minLength : 1
					});
				})
			[onshow;block=end]
		}
		
		function refreshTab(id, action) {
			if (typeof(action) == 'undefined') action = 'view';
			
			$.get("fiche_of.php?action="+action+"&id="+id , function(data) {		     	
		     	$('div.OFMaster[assetof_id='+id+'] table.needed').replaceWith(  $(data).find('div.OFMaster[assetof_id='+id+'] table.needed') );
		     	$('div.OFMaster[assetof_id='+id+'] table.tomake').replaceWith(  $(data).find('div.OFMaster[assetof_id='+id+'] table.tomake') );
		     	$('div.OFMaster[assetof_id='+id+'] table.workstation').replaceWith(  $(data).find('div.OFMaster[assetof_id='+id+'] table.workstation') );
		     	
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
		function deleteWS(id_assetOf,idWS) {
			$.ajax(
				{url : "script/interface.php?get=deleteofworkstation&id_assetOf=[assetOf.id]&fk_asset_workstation_of="+idWS }
			).done(function(){
				refreshTab(id_assetOf) ;
			});
			
		}
		
		function addAllLines(id_assetOf,idLine,btnadd){
			[onshow;block=begin;when [view.mode]=='view']
				return alert("Votre OF doit être au statut brouillon et devez être en modification pour mettre à jour les valeurs des produits nécessaires.");
			[onshow;block=end]
			
			[onshow;block=begin;when [view.mode]!='view']
				if ($(btnadd).attr('statut') == 'DRAFT') {
					qty = $(btnadd).parent().parent().find("input[id*='qty']").val();
					
					$.ajax({
						url: "script/interface.php?get=addlines&idLine="+idLine+"&qty="+qty+"&type=json"
						,dataType: 'json'
					}).done(function(data){			
						if(data.length > 0) {
							for (i in data)
							{
								refreshTab(data[i], 'edit');
							}
							
							$.jnotify('Mise à jour des quantités enregistr&eacute;es', "ok");
						}
					});
				} else {
					alert("Cette OF n'est plus au statut brouillon.");
				}
			[onshow;block=end]
		}
		
</script>
