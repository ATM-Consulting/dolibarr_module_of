[onshow;block=begin;when [view.mode]=='view']

	
		<div class="fiche"> <!-- begin div class="fiche" -->
		
			<div class="tabBar">
				
[onshow;block=end]				
				
			<table width="100%" class="border">
				[onshow;block=begin;when [view.mode]=='new']
					<tr>
						<td style="width:20%">Type</td>
						<td>[assetNew.typeCombo;strconv=no;protect=no]</td>
						<td>[assetNew.validerType;strconv=no;protect=no]</td>
					</tr>
				[onshow;block=end]
				[onshow;block=begin;when [view.mode]!='new']
				
				<script type="text/javascript">
					$(document).ready(function(){
						$("#lot_number").autocomplete({
							source: "script/interface.php?get=autocomplete&json=1&fieldcode=lot_number",
							minLength : 1
						});
					});
				</script>
				<tr><td width="20%">Numéro de série</td><td>[asset.serial_number;strconv=no]</td>[asset.typehidden;strconv=no;protect=no]</tr>
				<tr><td>Numéro Lot</td><td>[asset.lot_number;strconv=no;protect=no]</td></tr>
				<tr><td>Produit</td><td>[asset.produit;strconv=no]</td></tr>
				<tr><td>Entrepôt</td><td>[asset.entrepot;strconv=no]</td></tr>
				<tr><td>Société</td><td>[asset.societe;strconv=no]</td></tr>
				<tr><td>Localisation</td><td>[asset.societe_localisation;strconv=no]</td></tr>
				<tr><td>Contenance Maximum</td><td>[asset.contenance_value;strconv=no] [asset.contenance_units;strconv=no]</td></tr>
				<tr><td>Contenance Actuelle</td><td>[asset.contenancereel_value;strconv=no] [asset.contenancereel_units;strconv=no]</td></tr>
				<tr><td>Point de chute</td><td>[asset.point_chute;strconv=no]</td></tr>
				<tr><td>Status</td><td>[asset.status;strconv=no]</td></tr>
				<tr><td>Réutilisabilité</td><td>[asset.reutilisable;strconv=no]</td></tr>
				<tr><td>Gestion du stock</td><td>[asset.gestion_stock;strconv=no]</td></tr>
				<tr>
					<td style="width:20%" [assetField.obligatoire;strconv=no;protect=no]>[assetField.libelle;block=tr;strconv=no;protect=no] </td>
					<td>[assetField.valeur;strconv=no;protect=no] </td>
				</tr>
				[onshow;block=end]
			</table>
			
[onshow;block=begin;when [view.mode]=='view']
	
			</div>

		</div>
		
		<script type="text/javascript">
			$(document).ready(function(){
				$('#fk_entrepot').change(function(){
					$('#lend-return').attr('onclick',"document.location.href='?action=retour_pret&id=[asset.id]&fk_entrepot="+$(this).val()+"'");
				})
			});
		</script>
		
		<div class="tabsAction">
			<input type="button" id="action-delete" value="Supprimer" name="cancel" class="butActionDelete" onclick="if(confirm('Supprimer cet Equipement?')) document.location.href='?action=delete&id=[asset.id]'">
			&nbsp; &nbsp; <input type="button" id="action-clone" value="Cloner" name="cancel" class="butAction" onclick="document.location.href='?action=clone&id=[asset.id]'">
			&nbsp; &nbsp; <a href="?id=[asset.id]&action=edit" class="butAction">Modifier</a>
			[onshow;block=begin;when [view.clinomadic]=='view']
				&nbsp; &nbsp; [view.entrepot;strconv=no]<input type="button" id="lend-return" value="Retour de prêt" name="retour_pret" class="butAction" onclick="document.location.href='?action=retour_pret&id=[asset.id]&fk_entrepot=1'">
			[onshow;block=end]
			&nbsp; &nbsp; <input id="action-mvt-stock" class="butAction" type="button" onclick="document.location.href='?action=stock&id=[asset.id]'" name="mvt_stock" value="Nouveau Mouvement Stock">
		</div>
		<!--<table border="0" width="100%" summary="" style="margin-bottom: 2px;" class="notopnoleftnoright">
			<tr><td valign="middle" class="nobordernopadding"><div class="titre">Mouvements de stock</div></td></tr>
		</table> -->
		[view.liste;strconv=no]
[onshow;block=end]

[onshow;block=begin;when [view.mode]=='stock']
		<div class="border" style="margin-top: 25px;">
			<table width="100%" class="border">
				<tr><td>Type Mouvement</td><td>[stock.type_mvt;strconv=no]</td></tr>
				<tr><td>Quantité</td><td>[stock.qty;strconv=no][asset.contenancereel_units;strconv=no]</td></tr>
				<tr><td>Commentaire</td><td>[stock.commentaire_mvt;strconv=no]</td></tr>
			</table>
		</div>
[onshow;block=end]
	
[onshow;block=begin;when [view.mode]!='view']

		<p align="center">
			[onshow;block=begin;when [view.mode]!='add']
				<input type="submit" value="Enregistrer" name="save" class="button">
				&nbsp; &nbsp; <input type="button" value="Annuler" name="cancel" class="button" onclick="document.location.href='?id=[asset.id]'">
			[onshow;block=end]
		</p>
[onshow;block=end]	
