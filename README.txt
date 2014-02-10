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