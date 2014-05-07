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
				<tr><td width="20%">Numéro de série</td><td>[asset.serial_number;strconv=no]</td>[asset.typehidden;strconv=no;protect=no]</tr>
				<tr><td>Produit</td><td>[asset.produit;strconv=no]</td></tr>
				<tr><td>Société</td><td>[asset.societe;strconv=no]</td></tr>
				<tr>
					<td style="width:20%" [assetField.obligatoire;strconv=no;protect=no]>[assetField.libelle;block=tr;strconv=no;protect=no] </td>
					<td>[assetField.valeur;strconv=no;protect=no] </td>
				</tr>
				[onshow;block=end]
			</table>
			
[onshow;block=begin;when [view.mode]=='view']
	
		</div>

		</div>
		
		<div class="tabsAction">
			<input type="button" id="action-delete" value="Supprimer" name="cancel" class="butActionDelete" onclick="document.location.href='?action=delete&id=[asset.id]'">
			&nbsp; &nbsp; <input type="button" id="action-clone" value="Cloner" name="cancel" class="butAction" onclick="document.location.href='?action=clone&id=[asset.id]'">
			&nbsp; &nbsp; <a href="?id=[asset.id]&action=edit" class="butAction">Modifier</a>
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
