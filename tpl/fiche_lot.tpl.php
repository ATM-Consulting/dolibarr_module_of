	
		<div class="fiche"> <!-- begin div class="fiche" -->		
				
			<table width="100%" class="border">
				<tr><td width="20%">Numéro Lot</td><td>[assetlot.lot_number;strconv=no;protect=no]</td></tr>
			</table>

			<p align="center">
					[onshow;block=begin;when [view.mode]=='new'] 
						<input type="submit" value="Enregistrer" name="save" class="button">
					[onshow;block=end]
					&nbsp; &nbsp; <input type="button" value="Retour" name="cancel" class="button" onclick="document.location.href='?liste_lot.php'">
			</p>
		</div>
