[onshow;block=begin;when [view.mode]=='view']

	
		<div class="fiche"> <!-- begin div class="fiche" -->
		
		<div class="tabs">
		<a class="tabTitle"><img border="0" title="" alt="" src="./img/object_technic.png"> Affaire</a>
		<a href="?id=[asset.id]" class="tab" id="active">Fiche</a>
		</div>
		
			<div class="tabBar">
				
[onshow;block=end]				
				
			<table width="100%" class="border">
			<tr><td width="20%">Numéro de série</td><td>[asset.serial_number;strconv=no]</td></tr>
			<tr><td>Produit</td><td>[asset.produit;strconv=no]</td></tr>
			<tr><td>Société</td><td>[asset.societe;strconv=no]</td></tr>
			<tr><td>[onshow;block=tr; when[view.module_financement]==1 ]Affaire</td><td><a href="[onshow.DOL_URL_ROOT_ALT]/financement/affaire.php?id=[affaire.rowid]">[affaire.reference]</a></td></tr>
			<tr><td>date de livraison</td><td>[asset.date_achat;strconv=no]</td></tr>
			
			<!--<tr><td>Coût copie noir & blanc</td><td>[asset.copy_black;strconv=no]</td></tr>
			<tr><td>Coût copie couleur</td><td>[asset.copy_color;strconv=no]</td></tr>
-->
			</table>
			
[onshow;block=begin;when [view.mode]=='view']
	
		</div>

		</div>
		
		<div class="tabsAction">
		<input type="button" id="action-delete" value="Supprimer" name="cancel" class="butActionDelete" onclick="document.location.href='?action=delete&id=[asset.id]'">
		&nbsp; &nbsp; <input type="button" id="action-clone" value="Cloner" name="cancel" class="button" onclick="document.location.href='?action=clone&id=[asset.id]'">
		&nbsp; &nbsp; <a href="?id=[asset.id]&action=edit" class="butAction">Modifier</a>

		</div>
[onshow;block=end]	
[onshow;block=begin;when [view.mode]!='view']

		<p align="center">
			<input type="submit" value="Enregistrer" name="save" class="button"> 
			&nbsp; &nbsp; <input type="button" value="Annuler" name="cancel" class="button" onclick="document.location.href='?id=[asset.id]'">
		</p>
[onshow;block=end]	
