
<div>
	<table width="100%" class="border">
		<tr><td width="20%">Libellé</td><td>[ws.libelle; strconv=no]</td></tr>
		<tr><td width="20%">Groupe d'utilisateurs</td><td>[ws.fk_usergroup; strconv=no]</td></tr>
		<tr><td width="20%">Nombre d'heure de préparation</td><td>[ws.nb_hour_prepare; strconv=no]</td></tr>
		<tr><td width="20%">Nombre d'heure de fabrication</td><td>[ws.nb_hour_manufacture; strconv=no]</td></tr>
		<tr><td width="20%">Nombre d'heure maximale</td><td>[ws.nb_hour_max; strconv=no]</td></tr>
	</table>
</div>

[onshow;block=begin;when [view.mode]=='edit']
	<div class="tabsAction" style="text-align:center;">
		<input type="submit" value="Enregistrer" name="save" class="button"> 
		&nbsp; &nbsp; <input type="button" value="Annuler" name="cancel" class="button" onclick="document.location.href='?action=view&id=[ws.id]'">
	</div>
[onshow;block=end]

[view.endForm;strconv=no]

[onshow;block=begin;when [view.conf_defined_task]==1]
	[onshow;block=begin;when [view.editTask]=='1']
		<div style="margin-top:15px;">
			<form action="[view.actionForm;strconv=no]" type="POST">
				<input type="hidden" name="action" value="editTaskConfirm" />
				<input type="hidden" name="id" value="[ws.id]" />
				<input type="hidden" name="id_task" value="[formTask.id_task;noerr]" />
				
				<table width="100%" class="border">
					<tr><th align="left" colspan="2">[formTask.id_task;noerr;if [val]==0;then 'Ajouter une tâche';else 'Modifier la tâche']</th></tr>
					<tr><td>Libellé</td><td><input size="45" type="text" name="libelle" value="[formTask.libelle;noerr;strconv=no]" /></td></tr>
					<tr><td>Description</td><td><textarea cols="45" rows="3" name="description">[formTask.description;noerr;strconv=no]</textarea></td></tr>
				</table>
				
				<div class="tabsAction" style="text-align:center;">
					<input class="button" type="submit" value="Enregistrer" />
					<a style="font-weight:normal;text-decoration:none" href="?action=view&id=[ws.id]" class="button">Annuler</a>
				</div>
			</form>
		</div>
	[onshow;block=end]
[onshow;block=end]	

[onshow;block=begin;when [view.conf_defined_task]==1]
	<div style="margin-top:15px;">
		<table width="100%" class="border">		
			<tr height="40px;">
				<td colspan="4">&nbsp;&nbsp;<b>Tâches associés</b></td>
			</tr>
			<tr style="background-color:#dedede;">
				<th align="left" width="10%">&nbsp;&nbsp;Tâche</th>
				<th align="left" width="30%">&nbsp;&nbsp;Description</th>
				<th align="center" width="5%">&nbsp;&nbsp;Action</th>
			</tr>
			
			<tr style="background-color:#fff;">
				<td>&nbsp;&nbsp;[wst.libelle;strconv=no;block=tr]</td>
				<td>[wst.description;strconv=no;block=tr]</td>
				<td align="center">[wst.action;strconv=no;block=tr]</td>
			</tr>
			
			<tr>
				<td colspan="4" align="center">[wst;block=tr;nodata]Aucune tâche associée</td>
			</tr>
		</table>	
	</div>
[onshow;block=end]

[onshow;block=begin;when [view.mode]!='edit']
	<div class="tabsAction">
		[onshow;block=begin;when [view.conf_defined_task]==1]
			<a href="?id=[ws.id]&action=editTask" class="butAction">Ajouter une tâche</a>
		[onshow;block=end]
		<a href="?id=[ws.id]&action=edit" class="butAction">Modifier</a>
		<span class="butActionDelete" id="action-delete"  
		onclick="if (window.confirm('Voulez vous supprimer l\'élément ?')){document.location.href='?id=[ws.id]&action=delete'};">Supprimer</span>
	</div>
[onshow;block=end]	

