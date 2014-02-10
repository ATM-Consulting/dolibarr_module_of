[onshow;block=begin;when [view.mode]=='view']

	
				
[onshow;block=end]				
				
			<table width="100%" class="border">
				<tr><td width="20%">Numéro</td><td>[assetOf.numero;strconv=no]</td></tr>
				<tr><td>Ordre</td><td>[assetOf.ordre;strconv=no;protect=no]</td></tr>
				<tr><td>Date du besoin</td><td>[assetOf.date_besoin;strconv=no]</td></tr>
				<tr><td>Date de lancement</td><td>[assetOf.date_lancement;strconv=no]</td></tr>
				<tr><td>Temps estimé de fabrication</td><td>[assetOf.temps_estime_fabrication;strconv=no]</td></tr>
				<tr><td>Temps réel de fabrication</td><td>[assetOf.temps_reel_fabrication;strconv=no]</td></tr>
				<tr><td>Statut</td><td>[assetOf.status;strconv=no]</td></tr>
				<tr><td>Poste de travail</td><td>[assetOf.fk_asset_workstation;strconv=no]</td></tr>
			</table>
			
[onshow;block=begin;when [view.mode]=='view']
	
	
		<div class="tabsAction">
			<input type="button" id="action-delete" value="Supprimer" name="cancel" class="butActionDelete" onclick="document.location.href='?action=delete&id=[assetOf.id]'">
			&nbsp; &nbsp; <a href="?id=[assetOf.id]&action=edit" class="butAction">Modifier</a>
		</div>

[onshow;block=end]

[onshow;block=begin;when [view.mode]=='view']
		<div class="border" style="margin-top: 25px;">
			<table width="100%" class="border">
				<tr height="40px;">
					<td style="border-right: none;">Produits nécessaires à la fabrication</td>
					<td style="border-left: none; text-align: right;">[onshow;block=begin;when [view.status]=='DRAFT']
						<a href="#null" class="butAction btnaddproduct" id="NEEDED">Ajouter produit</a>
						[onshow;block=end]
					</td>
					<td style="border-right: none; ">Produits à créer</td>
					<td style="border-left: none; text-align: right;">[onshow;block=begin;when [view.status]=='DRAFT']
						<a href="#null" class="butAction btnaddproduct" id="TO_MAKE">Ajouter produit</a>
						[onshow;block=end]
					</td>
				</tr>
				<tr>
					<td colspan="2" width="60%">
						<!-- NEEDED -->
						<table width="100%" class="border">
							<tr style="background-color:#dedede;">
								<!--<td>Lot</td>
								<td>Equipement</td>-->
								<td>Produit</td>
								<td>Quantité nécessaire</td>
								<td>Quantité</td>
								[onshow;block=begin;when [view.status]=='DRAFT']
									<td>Quantité non pourvue</td>
								[onshow;block=end]
								[onshow;block=begin;when [view.status]!='DRAFT']
									<td>Quantité utilisée</td>
								[onshow;block=end]
								[onshow;block=begin;when [view.status]=='DRAFT']
								<td style="width:20px;">Action</td>
								[onshow;block=end]
							</tr>
							<tr id="[TNeeded.id]">
								<!--<td>Lot</td>
								<td>Equipement</td>-->
								<td>[TNeeded.libelle;block=tr;strconv=no]</td>
								<td>[TNeeded.qty_needed]</td>
								<td>[TNeeded.qty;strconv=no]</td>
								[onshow;block=begin;when [view.status]=='DRAFT']
									<td>[TNeeded.qty_toadd]</td>
								[onshow;block=end]
								[onshow;block=begin;when [view.status]!='DRAFT']
									<td>[TNeeded.qty]</td>
								[onshow;block=end]
								[onshow;block=begin;when [view.status]=='DRAFT']
									<td>[TNeeded.delete;strconv=no]</td>
								[onshow;block=end]
							</tr>
						</table>
					</td>
					<td colspan="2" width="40%">
						<!-- TO_MAKE -->
						<table width="100%" class="border">
							<tr style="background-color:#dedede;">
								[onshow;block=begin;when [view.status]=='DRAFT']
								<td style="width:20px;">Action</td>
								[onshow;block=end]
								<td>Produit</td>
								<td>Quantité à produire</td>
								[onshow;block=begin;when [view.status]=='DRAFT']
									<td style="width:20px;">Action</td>
								[onshow;block=end]
							</tr>
							<tr id="[TTomake.id]">
								[onshow;block=begin;when [view.status]=='DRAFT']
									<td>[TTomake.addneeded;strconv=no]</td>
								[onshow;block=end]
								<td>[TTomake.libelle;block=tr;strconv=no]</td>
								<td>[TTomake.qty;strconv=no]</td>
								[onshow;block=begin;when [view.status]=='DRAFT']
									<td>[TTomake.delete;strconv=no]</td>
								[onshow;block=end]
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
[onshow;block=end]

[onshow;block=begin;when [view.mode]=='view']
	<div class="tabsAction">
		[onshow;block=begin;when [view.status]=='DRAFT']
			<input type="submit" onclick="return confirm('Valider cet Ordre de Fabrication?');" class="butAction" name="valider" value="Valider">
		[onshow;block=end]
		[onshow;block=begin;when [view.status]=='VALID']
			&nbsp; &nbsp; <input type="submit" onclick="return confirm('Lancer cet Ordre de Fabrication?');" class="butAction" name="lancer" value="Lancer">
		[onshow;block=end]
		[onshow;block=begin;when [view.status]=='OPEN']
			&nbsp; &nbsp; <a href="?id=[assetOf.id]&action=terminer" onclick="return confirm('Terminer cet Ordre de Fabrication?');" class="butAction">Terminer</a>
		[onshow;block=end]
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
