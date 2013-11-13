[onshow;block=begin;when [view.mode]=='view']

	
		<div class="fiche"> <!-- begin div class="fiche" -->
		
			<div class="tabBar">
				
[onshow;block=end]				
				
			<table width="100%" class="border">
				<tr><td width="20%">Numéro</td><td>[assetOf.numero;strconv=no]</td></tr>
				<tr><td>Ordre</td><td>[assetOf.ordre;strconv=no;protect=no]</td></tr>
				<tr><td>Date du besoin</td><td>[assetOf.date_besoin;strconv=no]</td></tr>
				<tr><td>Date de lancement</td><td>[assetOf.date_lancement;strconv=no]</td></tr>
				<tr><td>Temps estimé de fabrication</td><td>[assetOf.temps_estime_fabrication;strconv=no]</td></tr>
				<tr><td>Temps réel de fabrication</td><td>[assetOf.temps_reel_fabrication;strconv=no]</td></tr>
				<tr><td>Statuts</td><td>[assetOf.status;strconv=no]</td></tr>
				<tr><td>Poste de travail</td><td>[assetOf.fk_asset_workstation;strconv=no]</td></tr>
			</table>
			
[onshow;block=begin;when [view.mode]=='view']
	
		</div>

		</div>
		
		<div class="tabsAction">
			<input type="button" id="action-delete" value="Supprimer" name="cancel" class="butActionDelete" onclick="document.location.href='?action=delete&id=[assetOf.id]'">
			&nbsp; &nbsp; <a href="?id=[assetOf.id]&action=edit" class="butAction">Modifier</a>
			&nbsp; &nbsp; <a href="?id=[assetOf.id]&action=valider" class="butAction">Valider</a>
			[onshow;block=begin;when [view.status]=='DRAFT']
				&nbsp; &nbsp; <a href="?id=[assetOf.id]&action=lancer" class="butAction">Lancer</a>
			[onshow;block=end]
			[onshow;block=begin;when [view.status]!='DRAFT']
				&nbsp; &nbsp; <a href="?id=[assetOf.id]&action=terminer" class="butAction">Terminer</a>
			[onshow;block=end]
		</div>

[onshow;block=end]

[onshow;block=begin;when [view.mode]=='view']
		<div class="border" style="margin-top: 25px;">
			<table width="100%" class="border">
				<tr>
					<td>Produits nécessaire à la fabrication</td><td><a href="#null" class="butAction btnaddproduct" id="NEEDED">Ajouter produit</a></td>
					<td>Produits à créer</td><td><a href="#null" class="butAction btnaddproduct" id="TO_MAKE">Ajouter produit</a></td>
				</tr>
				<tr>
					<td colspan="2">
						<!-- NEEDED -->
						<table width="100%" class="border">
							<tr>
								<!-- Lot
								<td>Lot</td>
								<!-- Equipement
								<td>Equipement</td>
								<!-- Produit -->
								<td>Produit</td>
								<!-- Quantité nécessaire -->
								<td>Quantité nécessaire</td>
								<!-- Quantité -->
								<td>Quantité</td>
								<!-- Quantité non pourvu -->
								<td>Quantité non pourvu</td>
								<!-- Qauntité utilisé -->
								<td>Qauntité utilisé</td>
								<!-- Action -->
								<td>Action</td>
							</tr>
							<tr>
								<!-- Lot
								<td>Lot</td>
								<!-- Equipement
								<td>Equipement</td>
								<!-- Produit -->
								<td>[TNeeded.libelle;block=tr]</td>
								<!-- Quantité nécessaire -->
								<td>[TNeeded.qty]</td>
								<!-- Quantité -->
								<td>Quantité</td>
								<!-- Quantité non pourvu -->
								<td>Quantité non pourvu</td>
								<!-- Qauntité utilisé -->
								<td>Qauntité utilisé</td>
								<!-- Action -->
								<td>Action</td>
							</tr>
						</table>
					</td>
					<td colspan="2">
						<!-- TO_MAKE -->
						<table width="100%" class="border">
							<tr>
								<!-- Action : ajout auto des produits NEEDED -->
								<td>Action</td>
								<!-- Produit -->
								<td>Produit</td>
								<!-- Quantité à produire -->
								<td>Quantité à produire</td>
								<!-- Action -->
								<td>Action</td>
							</tr>
							<tr>
								<!-- Action : ajout auto des produits NEEDED -->
								<td>Action</td>
								<!-- Produit -->
								<td>[TTomake.libelle;block=tr]</td>
								<!-- Quantité à produire -->
								<td>Quantité à produire</td>
								<!-- Action -->
								<td>Action</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
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
