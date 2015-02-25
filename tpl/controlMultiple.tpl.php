
<div class="tabBar">
	<table width="100%" class="border">
		<tr><td width="10%">Ajouter la valeur à</td><td>[com.fk_control; strconv=no]</td></tr>
		<tr><td width="20%">Valeur</td><td>[com.value; strconv=no]</td></tr>
	</table>
</div>


[onshow;block=begin;when [view.mode]!='edit']
	<div class="tabsAction">
		<a href="?idm=[com.idm]&action=editValue" class="butAction">Modifier</a>
		<a class="butActionDelete" href="control.php?idm=[com.idm]&action=deleteValue">Supprimer</a>
	</div>
[onshow;block=end]	

[onshow;block=begin;when [view.mode]=='edit']
	<div class="tabsAction" style="text-align:center;">
		<input type="submit" value="Enregistrer" name="saveValue" class="butAction">
		<a class="butAction"  href="list_control_multiple.php">Annuler</a>
	</div>
[onshow;block=end]

<div style="clear:both"></div>

