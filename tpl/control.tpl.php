
<div class="tabBar">
	<table width="100%" class="border">
		<tr><td width="20%">Libelle</td><td>[co.libelle; strconv=no]</td></tr>
		<tr><td width="10%">Type du contrôle</td><td>[co.type; strconv=no]</td></tr>
		<tr><td width="20%">Question</td><td>[co.question; strconv=no]</td></tr>
	</table>
</div>


[onshow;block=begin;when [view.mode]!='edit']
	<div class="tabsAction">
		<a href="?id=[co.id]&action=edit" class="butAction">Modifier</a>
		<a class="butActionDelete" href="control.php?id=[co.id]&action=delete">Supprimer</a>
	</div>
[onshow;block=end]	

[onshow;block=begin;when [view.mode]=='edit']
	<div class="tabsAction" style="text-align:center;">
		<input type="submit" value="Enregistrer" name="save" class="butAction">
		<a class="butAction"  href="list_control.php">Annuler</a>
	</div>
[onshow;block=end]

<div style="clear:both"></div>

