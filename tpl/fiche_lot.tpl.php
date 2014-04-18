[onshow;block=begin;when [view.mode]=='view']

	
		<div class="fiche"> <!-- begin div class="fiche" -->
		
			<div class="tabBar">
				
[onshow;block=end]				
				
			<table width="100%" class="border">
				[onshow;block=begin;when [view.mode]!='new']
				<tr><td>Numéro Lot</td><td>[assetlot.lot_number;strconv=no;protect=no]</td></tr>
				[onshow;block=end]
			</table>
			
[onshow;block=begin;when [view.mode]=='view']
	
		</div>

		</div>
		
		<div class="tabsAction">
			<input type="button" id="action-delete" value="Supprimer" name="cancel" class="butActionDelete" onclick="document.location.href='?action=delete&id=[assetlot.id]'">
			&nbsp; &nbsp; <input type="button" id="action-clone" value="Cloner" name="cancel" class="butAction" onclick="document.location.href='?action=clone&id=[assetlot.id]'">
			&nbsp; &nbsp; <a href="?id=[assetlot.id]&action=edit" class="butAction">Modifier</a>
		</div>
[onshow;block=end]
	
[onshow;block=begin;when [view.mode]!='view']

		<p align="center">
			[onshow;block=begin;when [view.mode]!='add']
				<input type="submit" value="Enregistrer" name="save" class="button">
				&nbsp; &nbsp; <input type="button" value="Annuler" name="cancel" class="button" onclick="document.location.href='?id=[assetlot.id]'">
			[onshow;block=end]
		</p>
[onshow;block=end]	
