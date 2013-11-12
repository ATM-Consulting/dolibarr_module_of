[onshow;block=begin;when [view.mode]=='view']

	
		<div class="fiche"> <!-- begin div class="fiche" -->
		
			<div class="tabBar">
				
[onshow;block=end]				
				
			<table width="100%" class="border">
				<tr><td width="20%">Numéro</td><td>[assetOf.number;strconv=no]</td></tr>
				<tr><td>Ordre</td><td>[assetOf.ordre;strconv=no;protect=no]</td></tr>
				<tr><td>Date du besoin</td><td>[assetOf.date_besoin;strconv=no]</td></tr>
				<tr><td>Date de lancement</td><td>[assetOf.date_lancement;strconv=no]</td></tr>
				<tr><td>Temps estimé de fabrication</td><td>[assetOf.temps_estime_fabrication;strconv=no]</td></tr>
				<tr><td>Temps réel de fabrication</td><td>[assetOf.temps_reel_fabrication;strconv=no]</td></tr>
				<tr><td>Statuts</td><td>[assetOf.status;strconv=no]</td></tr>
				<tr><td>Poste de travail</td><td>[assetOf.fk_workstation;strconv=no]</td></tr>
				<tr><td>Utilisateur en charge</td><td>[assetOf.fk_user;strconv=no]</td></tr>
			</table>
			
[onshow;block=begin;when [view.mode]=='view']
	
		</div>

		</div>
		
		<div class="tabsAction">
			<input type="button" id="action-delete" value="Supprimer" name="cancel" class="butActionDelete" onclick="document.location.href='?action=delete&id=[assetOf.id]'">
			&nbsp; &nbsp; <a href="?id=[assetOf.id]&action=edit" class="butAction">Modifier</a>
		</div>

[onshow;block=end]

[onshow;block=begin;when [view.mode]=='view']
		<div class="border" style="margin-top: 25px;">
			<table width="100%" class="border">
				<tr><td>Type Mouvement</td><td>[stock.type_mvt;strconv=no]</td></tr>
				<tr><td>Quantité</td><td>[stock.qty;strconv=no][assetOf.contenancereel_units;strconv=no]</td></tr>
				<tr><td>Commentaire</td><td>[stock.commentaire_mvt;strconv=no]</td></tr>
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
