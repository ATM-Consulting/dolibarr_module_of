[onshow;block=begin;when [view.mode]=='view']
    [view.head;strconv=no]
[onshow;block=end]  

[onshow;block=begin;when [view.mode]!='view']
    [view.onglet;strconv=no]
    <div>
[onshow;block=end]  
		

<table width="100%" class="border">
	<tr><td width="20%">Libellé</td><td>[ws.libelle; strconv=no]</td></tr>
	<tr><td width="20%">Groupe d'utilisateurs</td><td>[ws.fk_usergroup; strconv=no]</td></tr>
	<tr><td width="20%">Nombre d'heure maximale</td><td>[ws.nb_hour_max; strconv=no]</td></tr>
</table>

</div>


[onshow;block=begin;when [view.mode]!='edit']
	<div class="tabsAction">
		<a href="?id=[ws.id]&action=edit" class="butAction">Modifier</a>
		<span class="butActionDelete" id="action-delete"  
		onclick="if (window.confirm('Voulez vous supprimer l\'élément ?')){document.location.href='?id=[ws.id]&action=delete'};">Supprimer</span>
	</div>
[onshow;block=end]	

[onshow;block=begin;when [view.mode]=='edit']
	<div class="tabsAction" style="text-align:center;">
		<input type="submit" value="Enregistrer" name="save" class="button"> 
		&nbsp; &nbsp; <input type="button" value="Annuler" name="cancel" class="button" onclick="document.location.href='?id=[ws.id]'">
	</div>
[onshow;block=end]

<div style="clear:both"></div>

