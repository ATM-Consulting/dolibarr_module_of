[onshow;block=begin;when [view.mode]=='view']

	
		<div class="fiche"> <!-- begin div class="fiche" -->
		[view.head;strconv=no]
		
			<div class="tabBar">
				
[onshow;block=end]				
				
				<table width="100%" class="border">
				<tr><td width="20%">Référence du flacon</td><td>[asset.serial_number;strconv=no]</td></tr>
				<tr><td>Numéro batch</td><td>[asset.lot_number;strconv=no;protect=no]</td></tr>
				<tr><td>Produit</td><td>[asset.produit;strconv=no;protect=no]</td></tr>
				<tr><td>Contenance</td><td>[asset.contenance_value;strconv=no][asset.contenance_units;strconv=no]</td></tr>
				<tr><td>Contenance Réelle</td><td>[asset.contenancereel_value;strconv=no][asset.contenancereel_units;strconv=no]</td></tr>
				</table>
			
[onshow;block=begin;when [view.mode]=='view']
	
			</div>

		</div>
		
		<div class="tabsAction">
			<input type="button" id="action-delete" value="Supprimer" name="cancel" class="butActionDelete" onclick="document.location.href='?action=delete&id=[asset.id]'">
			&nbsp; &nbsp; <input type="button" id="action-clone" value="Cloner" name="cancel" class="butAction" onclick="document.location.href='?action=clone&id=[asset.id]'">
			&nbsp; &nbsp; <a href="?id=[asset.id]&action=edit" class="butAction">Modifier</a>
		</div>
		
		<table border="0" width="100%" summary="" style="margin-bottom: 2px;" class="notopnoleftnoright">
			<tr><td valign="middle" class="nobordernopadding"><div class="titre">Mouvements de stock</div></td></tr>
		</table>
		[view.liste;strconv=no]
[onshow;block=end]	
[onshow;block=begin;when [view.mode]!='view']

		<p align="center">
			<input type="submit" value="Enregistrer" name="save" class="button"> 
			&nbsp; &nbsp; <input type="button" value="Annuler" name="cancel" class="button" onclick="document.location.href='?id=[asset.id]'">
		</p>
[onshow;block=end]	
