<style type="text/css">
	/* Nécessaire pour cacher les informations qui ne doivent pas être accessibles à la 1ere étape de création d'un OF :: C'est très sale */ 
	[onshow;block=begin;when [assetOf.id]==0]
	.draft, .draftedit,.nodraft,.viewmode,.of-details {
		display:none;		
	}		
	[onshow;block=end]
	
</style>		
	<div class="OFMaster" assetOf_id="[assetOf.id]" fk_assetOf_parent="[assetOf.fk_assetOf_parent]">		
		<form id="formOF[assetOf.id]" name="formOF[assetOf.id]" action="fiche_of.php" method="POST">
				<input type="hidden" value="save" name="action">
				<input type="hidden" name="fk_product_to_add" value="[assetOf.fk_product_to_add]">	
				<input type="hidden" name="fk_nomenclature" value="[assetOf.fk_nomenclature]">		
				<input type="hidden" value="[assetOf.id]" name="id">
				
			<table width="100%" class="border">
				
				<tr><td width="20%">Numéro</td><td>[assetOf.numero;strconv=no]</td></tr>
				<tr><td>Ordre</td><td>[assetOf.ordre;strconv=no;protect=no]</td></tr>
				<tr class="notinparentview"><td>OF Parent</td><td>[assetOf.link_assetOf_parent;strconv=no;protect=no;magnet=tr]</td></tr>
				<tr class="notinparentview"><td>Commande</td><td>[assetOf.fk_commande;strconv=no;magnet=tr]</td></tr>
				<tr class="notinparentview"><td>Commande Fournisseur</td><td>[assetOf.commande_fournisseur;strconv=no;magnet=tr]</td></tr>
				<tr><td>Client</td><td>[assetOf.fk_soc;strconv=no;protect=no;magnet=tr]</td></tr>
				<tr><td>Projet</td><td>[assetOf.fk_project;strconv=no;protect=no;magnet=tr]</td></tr>
				<tr><td>Date du besoin</td><td>[assetOf.date_besoin;strconv=no]</td></tr>
				<tr><td>Date de lancement</td><td>[assetOf.date_lancement;strconv=no]</td></tr>
				<tr><td>Temps estimé de fabrication</td><td>[assetOf.temps_estime_fabrication;strconv=no] heure(s)</td></tr>
				<tr><td>Temps réel de fabrication</td><td>[assetOf.temps_reel_fabrication;strconv=no] heure(s)</td></tr>
				<tr><td>Statut</td><td>[assetOf.status;strconv=no]<span style="display:none;">[assetOf.statustxt;strconv=no]</span>
					[onshow;block=begin;when [view.status]!='CLOSE';when [view.mode]=='view']
						<span class="viewmode notinparentview">
							
				
						[onshow;block=begin;when [view.status]=='DRAFT']
							, passer à l'état :<input type="button" onclick="if (confirm('Valider cet Ordre de Fabrication ?')) { submitForm([assetOf.id],'valider'); }" class="butAction" name="valider" value="Valider">
						[onshow;block=end]
						[onshow;block=begin;when [view.status]=='VALID']
							, passer à l'état :<input type="button" onclick="if (confirm('Lancer cet Ordre de Fabrication ?')) { submitForm([assetOf.id],'lancer'); }" class="butAction" name="lancer" value="Production en cours">
						[onshow;block=end]
						[onshow;block=begin;when [view.status]=='OPEN']
							, passer à l'état :<input type="button" onclick="if (confirm('Terminer cet Ordre de Fabrication ?')) { submitForm([assetOf.id],'terminer'); }" class="butAction" name="terminer" value="Terminer">
							<!-- <a href="[assetOf.url]?id=[assetOf.id]&action=terminer" onclick="return confirm('Terminer cet Ordre de Fabrication ?');" class="butAction">Terminer</a> -->
						[onshow;block=end]
					[onshow;block=end]
					</span>
				</td></tr>
				
				<tr><td>Note</td><td>[assetOf.note;strconv=no]</td></tr>
				
			</table>
			
			[onshow;block=begin;when [view.workstation_module_activate]==1]
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
							[onshow;block=begin;when [view.use_project_task]=='1']
								<th>Tâche</th>
							[onshow;block=end]
							[onshow;block=begin;when [view.defined_task_by_workstation]=='1']
								<th>Tâche associé</th>
							[onshow;block=end]
							<th>Nb. heures prévues</th>
							<th>Nb. heures réelles</th>
							<th class="draftedit">Action</th>
						</tr>
						<tr id="WS[workstation.id]" style="background-color:#fff;">
							<td>[workstation.libelle;strconv=no;block=tr]</td>
							[onshow;block=begin;when [view.defined_user_by_workstation]=='1']
								<td align="left">[workstation.fk_user;strconv=no]</td>
							[onshow;block=end]
							[onshow;block=begin;when [view.use_project_task]=='1']
								<td align='center'>[workstation.fk_project_task;strconv=no]</td>
							[onshow;block=end]
							[onshow;block=begin;when [view.defined_task_by_workstation]=='1']
								<td align='center'>[workstation.fk_task;strconv=no]</td>
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
			[onshow;block=end]
	
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
									[onshow;block=begin;when [view.defined_manual_wharehouse]=='1']
										<td width="20%">Entrepôt</td>
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
									[onshow;block=begin;when [view.defined_manual_wharehouse]=='1']
										<td width="20%">[TNeeded.fk_entrepot;strconv=no]</td>
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
									[onshow;block=begin;when [view.defined_manual_wharehouse]=='1']
										<td width="20%">Entrepôt</td>
									[onshow;block=end]
									<td class="draftedit" style="width:20px;">Action</td>
									
								</tr>
								<tr id="[TTomake.id]">
									<td align='center' class="draftedit">[TTomake.addneeded;strconv=no]</td>
									[onshow;block=begin;when [view.use_lot_in_of]=='1']
										<td>[TTomake.lot_number;strconv=no]</td>
									[onshow;block=end]
									<td>[TTomake.libelle;block=tr;strconv=no]
									[onshow;block=begin;when [view.ASSET_USE_MOD_NOMENCLATURE]=='1']
										<div>[TTomake.nomenclature;block=tr;strconv=no]</div>
									[onshow;block=end]
										
									</td>
									<td>[TTomake.qty;strconv=no]</td>
									<td width="30%">[TTomake.fk_product_fournisseur_price;strconv=no]</td>
									[onshow;block=begin;when [view.defined_manual_wharehouse]=='1']
										<td width="20%">[TTomake.fk_entrepot;strconv=no]</td>
									[onshow;block=end]
									<td align='center' class="draftedit">[TTomake.delete;strconv=no]</td>
									
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>

			[onshow;block=begin;when [view.mode]=='view']
				<div class="tabsAction notinparentview buttonsAction">
					
					[onshow;block=begin;when [view.status]=='CLOSE']
						<a class="butActionRefused" title="L'ordre de fabrication est terminé" href="#">Supprimer</a>
					[onshow;block=end]
					[onshow;block=begin;when [view.status]!='CLOSE']
						<a onclick="if(!confirm('Supprimer cet Ordre de Fabrication?')) return false;" class="butActionDelete" href="[assetOf.url]?id=[assetOf.id]&action=delete">Supprimer</a>
					[onshow;block=end]
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
			[onshow;block=begin;when [view.ASSET_USE_MOD_NOMENCLATURE]=='1']
				<tr id="tr_select_nomenclature" style="display:none;">
					<td style="width:80px;" title="Nomenclature">Nomen. : </td>
					<td><select name="fk_nomenclature"></select></td>
				</tr>
			[onshow;block=end]
			<tr>
				<td style="width:80px;">Quantité : </td>
				<td><input type='text' size='4' value='1' name='default_qty_to_make' /></td>
			</tr>
		</table>
	</div>
	[onshow;block=begin;when [view.workstation_module_activate]==1]
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
	[onshow;block=end]
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
			
			/* Couplage avec nomenclature */
			[onshow;block=begin;when [view.ASSET_USE_MOD_NOMENCLATURE]=='1']
				$('#fk_product').change(function() {
					var selectTarget = $("select[name=fk_nomenclature]");
					
					$.ajax({
						url: "script/interface.php?"
						,async: false
						,type: 'GET'
						,data:{
							get: 'getNomenclatures'
							,fk_product: $(this).val()
						}
						,dataType: 'json'
						,success: function(data) {
							$(selectTarget).empty();
							
							if (data.length > 0)
							{
								for (var i in data)
								{
									$(selectTarget).append($("<option qty_reference='"+data[i].qty_reference+"' "+(data[i].is_default ? 'selected="selected"' : '')+" value='"+data[i].rowid+"'>"+ (data[i].title == '' ? '(sans titre)' : data[i].title) +"</option>"));
								}
								
								var qty = $(selectTarget).children('option:selected').attr('qty_reference');
								$('input[name=default_qty_to_make]').attr('value', qty);
								
								$('#tr_select_nomenclature').show();
							}
							else
							{
								$('input[name=default_qty_to_make]').attr('value', 1);
								$('#tr_select_nomenclature').hide();
							}
							
						}
					});
				});
				
			[onshow;block=end]
		});
		
		function getChild() {
			
			$('#assetChildContener > *:not("#titleOFEnfants")').remove();
			$('#assetChildContener').append('<p align="center" style="padding:10px; background:#fff;display:block;"><img src="img/loading.gif" /></p>');
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
									//Soumission du formulaire en ajax, je force manuellement l'action à save pour le bouton Enregistrer
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
											
											//Maj de l'affichage du formulaire en question
											refreshTab($(targetForm).children('input[name=id]').val(), 'edit');									     	
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

		function submitForm(assetOFId, saveType) {
			
			if(saveType!=null) {
				$('#formOF'+assetOFId+' input[name=action]').val(saveType);
			}
			
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
							var params = '';
							
							[onshow;block=begin;when [view.ASSET_USE_MOD_NOMENCLATURE]=='1']
								params += '&fk_nomenclature='+$('select[name=fk_nomenclature]').val();
							[onshow;block=end]
							
							params += '&default_qty_to_make='+$('input[name=default_qty_to_make]').val();
								
							$.ajax({
								url : "script/interface.php?get=addofproduct&id_assetOf="+idassetOf+"&fk_product="+fk_product+"&type="+type+"&user_id=[view.user_id]"+params
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
			[onshow;block=begin;when [view.ASSET_USE_MOD_NOMENCLATURE]=='1']	
			$('.valider_nomenclature').unbind().click(function() {
					var id_assetOF = $(this).data('id_of');
					
					var select = $(this).parent().children('select');
					var qty = $(this).parent().next().children('input[type="text"]');

					$.ajax({
						url: "script/interface.php"
						,type: 'GET'
						,data:{
							get: 'validernomenclature'
							,id_assetOF: id_assetOF
							,fk_product: $(this).data('product')
							,fk_of_line: $(this).data('of_line')
							,fk_nomenclature: select.val()
							,qty: qty.val()
						}
					}).done(function(data){
						//$('.OFMaster').html(data);
						refreshTab(id_assetOF, '[view.mode]');
					});
				});
			
			[onshow;block=end]
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
								url : "script/interface.php?get=addofworkstation&id_assetOf="+idassetOf+"&fk_asset_workstation="+fk_asset_workstation+"&user_id=[view.user_id]"
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
					var fk_product = $(this).attr('fk_product');
					var type = $(this).attr('type_product');
					$(this).autocomplete({
						source: "script/interface.php?get=autocomplete&json=1&fieldcode=lot_number&fk_product="+fk_product+"&type_product="+type,
						minLength : 1
					}).change(function() {
						var inputTarget = $(this).parent().next().find('input[rel=add-asset]');
						$(inputTarget).autocomplete({
							source: "script/interface.php?get=autocomplete-serial&json=1&fk_product="+fk_product+"&lot_number="+$('input[rel=lot-'+$(inputTarget).attr('fk-asset-of-line')+']').val()
							,minLength: 1
							,select: function(event, ui) {
								var value = ui.item.value;
							}
						});
					});
				})
				
				$('input[rel=add-asset]').each(function(){
				    var fk_product = $(this).attr('fk_product');
				    var idline = $(this).attr('fk-asset-of-line');
                    var lot = $('input[rel=lot-'+idline+']').val();
                    $(this).autocomplete({
                        source: "script/interface.php?get=autocomplete-serial&json=1&lot_number="+lot+"&fk_product="+fk_product
                        ,minLength : 1
                        ,select: function(event, ui) {
							var value = ui.item.value;
							var res = value.match(/^\[[0-9]*\]/g);
							
							if (res.length){
								res = res[0].substr(1, res[0].length-2);
							} else {
								res = 0;
							}
							
							var href = $(this).parent().children('a').attr('base-href');
							$(this).parent().children('a').attr('href', href+res)
						}
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
				{
					url : "script/interface.php?get=deletelineof&idLine="+idLine+"&type="+type
					,dataType : 'json'	
				}
			).done(function(TidAssetOF){
				if (TidAssetOF != 0) 
				{
					for (var i in TidAssetOF)
					{
						$('div.OFMaster[assetof_id='+TidAssetOF[i]+']').remove();
					}
				}
				
				if ($('#assetChildContener div.OFMaster').length <= 0) $('#assetChildContener').css('display', 'none');
				
				$("#"+idLine).remove();
			});
		}
		
		function deleteWS(id_assetOf,idWS) {
			$.ajax(
				{url : "script/interface.php?get=deleteofworkstation&id_assetOf=[assetOf.id]&fk_asset_workstation_of="+idWS+"&user_id=[view.user_id]" }
			).done(function(){
				refreshTab(id_assetOf, 'edit') ;
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
						var nbOFModified = data[0].length;
						var nbOFCreate = data[1].length;
						
						if (nbOFModified > 0 || nbOFCreate > 0)
						{
							if (data[0].length > 0) $.jnotify('Mise à jour des quantités enregistr&eacute;es', "ok");
							
							if (nbOFCreate > 0)
							{
								if (nbOFCreate == 1) $.jnotify('Un OF a été créé', "ok");
								else if (nbOFCreate > 1) $.jnotify('Des OF ont été créés', "ok");
								
								//Si des OF sont créés, je met à jour l'affichage de l'OF courant et j'actualise la totalité des OF enfants
								refreshTab($('.OFContent').attr('rel'), 'edit');
								getChild();
								refreshDisplay();
							}
							else
							{
								//Si il n'y a que des OF modifiés, j'actualise les affichages de chacun
								if(nbOFModified > 0) {
									for (i in data[0])
									{
										refreshTab(data[0][i], 'edit');
									}
								}
							}
						}
						
					});
				} else {
					alert("Cette OF n'est plus au statut brouillon.");
				}
			[onshow;block=end]
		}
		
</script>
