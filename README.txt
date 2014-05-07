3.5 et inférieur
===
Création d'un trigger nécessaire à la modification du nouveau champs 'compose_fourni' lors de l'ajout d'un prix fournisseur.
Bout de code à rajouter sur le fichier /htdocs/fourn/class/fournisseur.product.class.php :

				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('UPDATE_BUYPRICE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers
				
A rajouter après ligne 203 :

			dol_syslog(get_class($this).'::update_buyprice sql='.$sql);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();

===				
Appel de hook rajouté sur /htdocs/product/fournisseurs.php

ligne 296
après :		$supplier=new Fournisseur($db);
			$supplier->fetch($socid);
			print $supplier->getNomUrl(1);
			print '<input type="hidden" name="id_fourn" value="'.$socid.'">';
			print '<input type="hidden" name="ref_fourn" value="'.$product->fourn_ref.'">';
			print '<input type="hidden" name="ref_fourn_price_id" value="'.$rowid.'">';


code ajouté :
			if (is_object($hookmanager))
			{
				$parameters=array('id_fourn'=>$id_fourn,'prod_id'=>$product->id);
			    $reshook=$hookmanager->executeHooks('formEditThirdpartyOptions',$parameters,$object,$action);
			}
