[onshow;block=begin;when [view.mode]=='view']

        [view.head;strconv=no]
                                
[onshow;block=end]

[onshow;block=begin;when [view.mode]!='view']  
	[view.onglet;strconv=no]
    <div>
[onshow;block=end]    
		
			
			<table width="100%" class="border">
			
			<tr><td width="20%">Libellé</td><td>[assetType.libelle; strconv=no]</td></tr>
			<tr><td width="20%">Code</td><td>[assetType.code; strconv=no]</td></tr>
			</table>
	
	<br>
	[assetType.titreChamps; strconv=no]	


<dd> Si le type est Liste, séparer les élements par ';' .<br> <u>Exemple :</u> Si marque est de type liste, mettre "Ford;Citroën;Peugeot" dans les options.
<br><br></dd>

<table id="sort" class="border" style="width:100%;">
	<!-- entête du tableau -->
	<thead>
		<tr>
			[onshow;block=begin;when [view.mode]=='edit']
				<td>Déplacer</td>
			[onshow;block=end]
			<td>Code</td>
			<td>Libellé</td>
			<td>Type</td>
			<td>Options</td>
			<td>Obligatoire</td>
			<td>Visible dans la liste</td>
			<td>Concaténer dans le libellé</td>
			[onshow;block=begin;when [view.mode]=='edit']
				<td>Action</td>
			[onshow;block=end]
		</tr>
	</thead>
	<!--<ul id="sortable" style="list-style-type:none;">-->
	<tbody>
	<!-- fields déjà existants -->
	<tr id="[assetField.indice;block=tr;stdconv=no;protect=no]" >
		[assetField.ordre;strconv=no;protect=no]
		[onshow;block=begin;when [view.mode]=='edit']
			<td class="sortable">[assetType.pictoMove; strconv=no]	</td>
		[onshow;block=end]
		<td>[assetField.code;strconv=no;protect=no]</td>
		<td>[assetField.libelle;strconv=no;protect=no]</td>
		<td>[assetField.type;strconv=no;protect=no]</td>
		<td>[assetField.options;strconv=no;protect=no]</td>
		<td>[assetField.obligatoire;strconv=no;protect=no]</td>
		<td>[assetField.inliste;strconv=no;protect=no]</td>
		<td>[assetField.inlibelle;strconv=no;protect=no]</td>
		[onshow;block=begin;when [view.mode]=='edit']
			<td>
				<img src="./img/delete.png"  onclick="document.location.href='?id=[assetType.id]&idField=[assetField.id]&action=deleteField'">
			</td>
		[onshow;block=end]
	</tr>
	
	[onshow;block=begin;when [view.mode]=='edit']
		<!-- Nouveau field-->
		<tr id="[newField.indice;strconv=no;protect=no]">
			[newField.ordre;strconv=no;protect=no]
			<td class="sortable" >Nouveau</td>
			<td>[newField.code;strconv=no;protect=no]</td>
			<td>[newField.libelle;strconv=no;protect=no]</td>
			<td>[newField.type;strconv=no;protect=no]</td>
			<td>[newField.options;strconv=no;protect=no]</td>
			<td>[newField.obligatoire;strconv=no;protect=no]</td>
			<td>[newField.inliste;strconv=no;protect=no]</td>
			<td>[newField.inlibelle;strconv=no;protect=no]</td>
			<td><input type="submit" value="Ajouter" name="newField" class="button"></td>
		</tr>
	[onshow;block=end]
	</tbody>
	<!--</ul>-->
</table>

[onshow;block=begin;when [view.mode]=='edit']
	<script>
	 	$(".sortable").css('cursor','pointer');
		$(function() {
			$("#sort tbody").sortable({
				//handle: fixHelper,
				stop: function(event, ui) {
					//alert($("#sortable").html())
					var result = $("#sort tbody").sortable('toArray');
					//alert(result);
					for (var i = 0; i< result.length; i++){
						$(".ordre"+result[i]).attr("value", i)
						}
				}
			});
		});	
	</script>
[onshow;block=end]

</div>
[onshow;block=begin;when [view.mode]!='edit']
	<div class="tabsAction">
		<a href="?id=[assetType.id]&action=edit" class="butAction">Modifier</a>
	</div>
[onshow;block=end]	
[onshow;block=begin;when [view.mode]=='edit']
	<div class="tabsAction" style="text-align:center;">
		<input type="submit" value="Enregistrer" name="save" class="button"> 
		&nbsp; &nbsp; <input type="button" value="Annuler" name="cancel" class="button" onclick="document.location.href='?id=[assetType.id]'">
	</div>
[onshow;block=end]

<div style="clear:both"></div>


