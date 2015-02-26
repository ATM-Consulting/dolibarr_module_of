<div>
	<form action="[view.url]" method="POST">
		<input type="hidden" name="action" value="control" />
		<input type="hidden" name="subAction" value="addControl" />
		<input type="hidden" name="id" value="[assetOf.id]" />
		<table width="100%" class="border workstation">
			<tr height="40px;">
				<td colspan="3">&nbsp;&nbsp;<b>Contrôle à ajouter</b></td>
			</tr>
			<tr style="background-color:#dedede;">
				<th align="left" width="50%">&nbsp;&nbsp;Libellé du contrôle</th>
				<th align="center" width="20%">Type</th>
				<th width="5%" class="draftedit">Ajouter</th>
			</tr>
			<tr id="WS[workstation.id]" style="background-color:#fff;">
				<td align="left">&nbsp;&nbsp;[TControl.libelle;strconv=no;block=tr]</td>
				<td align="center">[TControl.type;strconv=no;block=tr]</td>
				<td align='center' class="draftedit">[TControl.action;strconv=no;block=tr]</td>
			</tr>
			<tr>
				<td colspan="4" align="center">[TControl;block=tr;nodata]Aucun contrôle disponible</td>
			</tr>
		</table>
		
		<div class="tabsAction">
			<div class="inline-block divButAction">
				<input [view.nbTControl;noerr;if [val]==0;then 'disabled="disabled"';else ''] class="butAction" type="submit" value="Ajouter les contrôles" />
			</div>
		</div>
	</form>	
	
	<form action="" method="POST">
		<input type="hidden" name="action" value="control" />
		<input type="hidden" name="subAction" value="updateControl" />
		<input type="hidden" name="id" value="[assetOf.id]" />
		<table width="100%" class="border workstation">
			<tr height="40px;">
				<td colspan="3">&nbsp;&nbsp;<b>Contrôles associés</b></td>
			</tr>
			<tr style="background-color:#dedede;">
				<th align="left" width="30%">&nbsp;&nbsp;Libellé du contrôle</th>
				<th align="left" width="40%">&nbsp;&nbsp;Valeur</th>
				<th width="5%">Supprimer</th>
			</tr>
			<tr style="background-color:#fff;">
				<td>&nbsp;&nbsp;[TAssetOFControl.libelle;strconv=no;block=tr]</td>
				<td>[TAssetOFControl.response;strconv=no;block=tr]</td>
				<td align="center">[TAssetOFControl.delete;strconv=no;block=tr]</td>
			</tr>
			<tr>
				<td colspan="4" align="center">[TAssetOFControl;block=tr;nodata]Aucun contrôle associé</td>
			</tr>
		</table>
		
		<div class="tabsAction">
			<div class="inline-block divButAction">
				<input [view.nbTAssetOFControl;noerr;if [val]==0;then 'disabled="disabled"';else ''] class="butAction" type="submit" value="Modifier les contrôles" />
			</div>
		</div>
	</form>
</div>