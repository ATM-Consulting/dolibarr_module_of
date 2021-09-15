<!-- START fiche_of.tpl -->
<style type="text/css">
	/* Nécessaire pour cacher les informations qui ne doivent pas être accessibles à la 1ere étape de création d'un OF :: C'est très sale */
	[onshow;block=begin;when [assetOf.id]==0]
	.draft, .draftedit,.nodraft,.viewmode,.of-details {
		display:none;
	}
	[onshow;block=end]

	.ui-autocomplete {
		z-index:999;
	}
</style>
	<div class="OFMaster" assetOf_id="[assetOf.id]" fk_assetOf_parent="[assetOf.fk_assetOf_parent]">
		<form id="formOF[assetOf.id]" name="formOF[assetOf.id]" action="fiche_of.php" method="POST">
				<input type="hidden" value="[view.action]" name="action">
				<input type="hidden" name="fk_product_to_add" value="[assetOf.fk_product_to_add]">
				<input type="hidden" name="fk_nomenclature" value="[assetOf.fk_nomenclature]">
				<input type="hidden" value="[assetOf.id]" name="id">

			<table width="100%" class="border">

				<tr><td width="20%">[view.langs.transnoentities(NumberOf)]</td><td>[assetOf.numero;strconv=no]</td></tr>
				<tr rel="ordre">
					<td>[view.editField;strconv=no][view.langs.transnoentities(Ordre)]</td>
					<td class="editableField">[assetOf.ordre;strconv=no;protect=no]</td>
				</tr>
				[onshow;block=begin;when [assetOf.id]=0]
				<tr><td>[view.langs.transnoentities(ProductToProduce)]</td><td>[assetOf.product_to_create;strconv=no;protect=no]</td></tr>
				<tr><td>[view.langs.transnoentities(QtyToMake)]</td><td>[assetOf.quantity_to_create;strconv=no;protect=no]</td></tr>
				[onshow;block=end]
				<tr><td>[view.langs.transnoentities(ParentOF)]</td><td>[assetOf.link_assetOf_parent;strconv=no;protect=no;magnet=tr]</td></tr>
				<tr rel="fk_commande">
                    [onshow;block=begin;when [conf.global.OF_MANAGE_ORDER_LINK_BY_LINE;noerr]!=1]
					    <td>[view.editField;strconv=no][view.langs.transnoentities(Order)]</td>
                    [onshow;block=end]
                    [onshow;block=begin;when [conf.global.OF_MANAGE_ORDER_LINK_BY_LINE;noerr]==1]
                    <td>[view.langs.transnoentities(Order)]</td>
                    [onshow;block=end]
					<td class="editableField">[assetOf.fk_commande;strconv=no;magnet=tr]</td>
				</tr>
				<tr><td>[view.langs.transnoentities(SupplierOrder)]</td><td>[assetOf.commande_fournisseur;strconv=no;magnet=tr]</td></tr>
				<tr rel="fk_soc">
					<td>[view.editField;strconv=no][view.langs.transnoentities(Customer)]</td>
					<td class="editableField">[assetOf.fk_soc;strconv=no;protect=no]</td>
				</tr>
				<tr rel="fk_project">
					<td>[view.editField;strconv=no][view.langs.transnoentities(Project)]</td>
					<td class="editableField">[assetOf.fk_project;strconv=no;protect=no]</td>
				</tr>
				<tr rel="date_besoin">
					<td>[view.editField;strconv=no][view.langs.transnoentities(DateNeeded)]</td>
					<td class="editableField">[assetOf.date_besoin;strconv=no]</td>
				</tr>
				<tr rel="date_lancement">
					<td>[view.editField;strconv=no][view.langs.transnoentities(DateLaunch)]</td>
					<td class="editableField">[assetOf.date_lancement;strconv=no]</td>
				</tr>
				<tr rel="date_start">
					<td>[view.langs.transnoentities(DateStart)]</td>
					<td class="editableField">[assetOf.date_start;strconv=no]</td>
				</tr>
				<tr rel="date_end">
					<td>[view.langs.transnoentities(DateEnd)]</td>
					<td class="editableField">[assetOf.date_end;strconv=no]</td>
				</tr>
				[onshow;block=begin;when [rights.show_ws_time]==1]
					<tr><td>[view.langs.transnoentities(EstimatedMakeTime)]</td><td>[assetOf.temps_estime_fabrication;strconv=no] heure(s)</td></tr>
				[onshow;block=end]
				<tr><td>[view.langs.transnoentities(RealMakeTime)]</td><td>[assetOf.temps_reel_fabrication;strconv=no] heure(s)</td></tr>
				[onshow;block=begin;when [view.show_cost]=='1']
				<tr><td>[view.langs.transnoentities(EstimatedProducCost)]</td><td>[assetOf.total_estimated_cost;strconv=no]</td></tr>
				<tr><td>[view.langs.transnoentities(RealProducCost)]</td><td>[assetOf.total_cost;strconv=no]</td></tr>
				<tr><td>[view.langs.transnoentities(FinalProducCost)]</td><td>[assetOf.current_cost_for_to_make;strconv=no]</td></tr>


				[onshow;block=end]
				<tr rel="status">
					<td>[view.langs.transnoentities(Status)][view.editFieldStatus;strconv=no]</td>
					<td>[assetOf.status;strconv=no]</td>
				</tr>

				<tr rel="note">
					<td>[view.editField;strconv=no][view.langs.transnoentities(Comments)]</td>
					<td class="editableField">[assetOf.note;strconv=no]</td>
				</tr>
                [onshow;block=begin;when [conf.global.OF_RANK_PRIOR_BY_LAUNCHING_DATE;noerr]==1]
                    <tr rel="rank">
                        <td>[view.editField;strconv=no][view.langs.transnoentities(Rank)]</td>
                        <td class="editableField">[assetOf.rank;strconv=no]</td>
                    </tr>
                [onshow;block=end]
			</table>

			<div class="of-details" style="margin-top: 25px;">
				<table width="100%" class="border" style="border:2px solid #b2ea97;">

					<tr height="40px;">
						<td style="border-right: none; background-color:#b2ea97;" colspan="4">&nbsp;&nbsp;<strong>[view.langs.transnoentities(ProductsToCreate)]</strong></td>
					</tr>
					<tr style="background-color:#fff;">
						<td colspan="4" valign="top">
							<!-- TO_MAKE -->
							<table width="100%" class="border tomake">
								<tr style="background-color:#dedede;">
                                    [onshow;block=begin;when [conf.global.OF_USE_REFLINENUMBER;noerr]==1]
                                        <td>[view.langs.transnoentities(RefLineNumber)]</td>
                                    [onshow;block=end]
									<td class="draftedit" style="width:20px;">[view.langs.transnoentities(OFActions)]</td>
									[onshow;block=begin;when [view.use_lot_in_of]=='1']
										<td>[view.langs.transnoentities(Lot)]</td>
									[onshow;block=end]
									<td>[view.langs.transnoentities(Product)]</td>
									<td>[view.langs.transnoentities(QtyToProduce)]</td>
                                    [onshow;block=begin;when [conf.global.OF_MANAGE_NON_COMPLIANT;noerr]!=1]
									    <td>[view.langs.transnoentities(ProduceQty)]</td>
                                    [onshow;block=end]
                                    [onshow;block=begin;when [conf.global.OF_MANAGE_NON_COMPLIANT;noerr]==1]
									    <td>[view.langs.transnoentities(Compliant)]</td>
                                    [onshow;block=end]
									<td>[view.langs.transnoentities(Supplier)]</td>
                                    [onshow;block=begin;when [conf.global.OF_MANAGE_NON_COMPLIANT;noerr]==1]
                                        <td >[view.langs.transnoentities(NonCompliant)]</td>
                                    [onshow;block=end]
									[onshow;block=begin;when [view.defined_manual_wharehouse]=='1']
										<td width="20%">[view.langs.transnoentities(Warehouse)]</td>
									[onshow;block=end]
									<td class="draftedit" style="width:20px;">[view.langs.transnoentities(OFActions)]</td>

								</tr>
								<tr id="[TTomake.id]">
                                    [onshow;block=begin;when [conf.global.OF_USE_REFLINENUMBER;noerr]==1]
                                        <td>[TTomake.reflinenumber;strconv=no;noerr]</td>
                                    [onshow;block=end]
									<td align="center" valign="top" class="draftedit">[TTomake.addneeded;strconv=no]</td>
									[onshow;block=begin;when [view.use_lot_in_of]=='1']
										<td valign="top">[TTomake.lot_number;strconv=no]</td>
									[onshow;block=end]
									<td valign="top">[TTomake.libelle;block=tr;strconv=no]
									[onshow;block=begin;when [conf.global.OF_COEF_MINI_TU_1;noerr]!='']
									[onshow;block=begin;when [TTomake.object_attr_weight]!='']
										( Poids : [TTomake.object_attr_weight;frm=0 000,00] [TTomake.object_attr_weight_units;onformat=measuring_units_weight_string], Mini-TU1 : [TTomake.object_attr_weight;onformat=calc_mini_tu1;frm=0 000,00] [TTomake.object_attr_weight_units;onformat=measuring_units_weight_string] )
									[onshow;block=end]
									[onshow;block=end]

									[onshow;block=begin;when [view.ASSET_USE_MOD_NOMENCLATURE]=='1']
										<div>[TTomake.nomenclature;block=tr;strconv=no]</div>
									[onshow;block=end]
										[TTomake.extrafields;strconv=no]
									</td>
									<td valign="top">[TTomake.qty;strconv=no]</td>
									<td valign="top">[TTomake.qty_used;strconv=no]</td>
									<td width="30%" valign="top">[TTomake.fk_product_fournisseur_price;strconv=no]</td>
                                    [onshow;block=begin;when [conf.global.OF_MANAGE_NON_COMPLIANT;noerr]==1]
                                        <td valign="top">[TTomake.qty_non_compliant;strconv=no]</td>
                                    [onshow;block=end]
									[onshow;block=begin;when [view.defined_manual_wharehouse]=='1']
										<td width="20%">[TTomake.fk_entrepot;strconv=no]</td>
									[onshow;block=end]
									<td align="center" valign="top" class="draftedit">[TTomake.delete;strconv=no]</td>

								</tr>
							</table>
						</td>
					</tr>
							[onshow;block=begin;when [view.mode]!='view']
					<tr>
						<td colspan="4" style="height:40px; border-left: none; text-align: right;">
								<a href="#" class="butAction btnaddproduct draftedit" id_assetOf="[assetOf.id]" rel="TO_MAKE">[view.langs.transnoentities(AddProduct)]</a>
						</td>
					</tr>
						[onshow;block=end]
				</table>
			</div>


			[onshow;block=begin;when [view.workstation_module_activate]==1]
				<div class="of-details" style="margin-top: 25px;">
					<table width="100%" class="border workstation" style="border:2px solid #f5893f;">
    					<tr height="40px;">
    						<td style="border-right: none; background-color:#f5893f; color:#fff;" colspan="4">&nbsp;&nbsp;<strong>[view.langs.transnoentities(WorkstationUsed)]</strong></td>
    					</tr>
						<tr>
							<td colspan="4" valign="top">
    							<!-- USED -->
    							<table width="100%" class="border used">
    								<tr style="background-color:#dedede;">
                						<td>[view.langs.transnoentities(Workstation)]</td>
            							[onshow;block=begin;when [view.defined_user_by_workstation]=='1']
            								<td>[view.langs.transnoentities(Users)]</td>
            							[onshow;block=end]
            							[onshow;block=begin;when [view.use_project_task]=='1']
            								<td>[view.langs.transnoentities(Task)]</td>
            							[onshow;block=end]
            							[onshow;block=begin;when [view.defined_task_by_workstation]=='1']
            								<td>[view.langs.transnoentities(AssociatedTask)]</td>
            							[onshow;block=end]
            							[onshow;block=begin;when [rights.show_ws_time]==1]
            								<td>[view.langs.transnoentities(TimeBeforeStart)]</td>
            							[onshow;block=end]
            							[onshow;block=begin;when [rights.show_ws_time]==1]
            								<td>[view.langs.transnoentities(NbPlannedHour)]</td>
            							[onshow;block=end]
            							<td>[view.langs.transnoentities(NbRealHour)]</td>
            							<td>[view.langs.transnoentities(Rank)]</td>
            							<th class="draftedit">[view.langs.transnoentities(Action)]</th>
            						</tr>
            						<tr id="WS[workstation.id]" style="background-color:#fff;">
            							<td>[workstation.libelle;strconv=no;block=tr]<br />[workstation.note_private;strconv=no;]</td>
            							[onshow;block=begin;when [view.defined_user_by_workstation]=='1']
            								<td align="left">[workstation.fk_user;strconv=no]</td>
            							[onshow;block=end]
            							[onshow;block=begin;when [view.use_project_task]=='1']
            								<td>[workstation.fk_project_task;strconv=no]</td>
            							[onshow;block=end]
            							[onshow;block=begin;when [view.defined_task_by_workstation]=='1']
            								<td align='left'>[workstation.fk_task;strconv=no]</td>
            							[onshow;block=end]
            							[onshow;block=begin;when [rights.show_ws_time]==1]
            								<td>[workstation.nb_days_before_beginning;strconv=no]</td>
            							[onshow;block=end]
            							[onshow;block=begin;when [rights.show_ws_time]==1]
            								<td>[workstation.nb_hour;strconv=no]</td>
            							[onshow;block=end]
            							<td>[workstation.nb_hour_real;strconv=no]</td>
            							<td>[workstation.rang;strconv=no]</td>
            							<td align='center' class="draftedit">[workstation.delete;strconv=no]</td>
            						</tr>
            						<tr>
            							<td colspan="5" align="center">[workstation;block=tr;nodata][view.langs.transnoentities(NoWorkstation)]</td>
            						</tr>
            						[onshow;block=begin;when [view.show_cost]=='1']
            						<tr style="background-color:#dedede;">
            							<td>[view.langs.transnoentities(Cost)]</td>
            							[onshow;block=begin;when [view.defined_user_by_workstation]=='1']
            								<td align="left">&nbsp;</td>
            							[onshow;block=end]
            							[onshow;block=begin;when [view.use_project_task]=='1']
            								<td>&nbsp;</td>
            							[onshow;block=end]
            							[onshow;block=begin;when [view.defined_task_by_workstation]=='1']
            								<td>&nbsp;</td>
            							[onshow;block=end]
            							[onshow;block=begin;when [rights.show_ws_time]==1]
            								<td>&nbsp;</td>
            								<td>[assetOf.mo_estimated_cost;strconv=no]</td>
            							[onshow;block=end]
            							<td>[assetOf.mo_cost;strconv=no]</td>
            							<td class="draftedit">&nbsp;</td>
            							<td class="draftedit">&nbsp;</td>
            						</tr>
            						[onshow;block=end]
    							</table>
							</td>
					</table>
					<div style="text-align: right;padding-top:16px;" class="draftedit">
						[onshow;block=begin;when [view.mode]!='view']
							<a href="#" class="butAction btnaddworkstation" id_assetOf="[assetOf.id]">[view.langs.transnoentities(AddWorkstation)]</a>
						[onshow;block=end]
					</div>
				</div>
			[onshow;block=end]

			<div class="of-details" style="margin-top: 25px;">
				<table width="100%" class="border" style="border:2px solid #269393;">
					<tr height="40px;">
						<td style="border-right: none; background-color:#269393; color:#fff;" colspan="4">&nbsp;&nbsp;<strong>[view.langs.transnoentities(ProductsNeeded)]</strong></td>
					</tr>
					<tr style="background-color:#fff;">
						<td colspan="4" valign="top">
							<!-- NEEDED -->
							<table width="100%" class="border needed">
								<tr style="background-color:#dedede;">
                                    [onshow;block=begin;when [view.defined_show_categorie]=='1']
										<td width="20%">[view.langs.transnoentities(Categories)]</td>
									[onshow;block=end]
									[onshow;block=begin;when [view.use_lot_in_of]=='1']
										<td width="20%">[view.langs.transnoentities(Lot)]</td>
									[onshow;block=end]

									<!--<td>Equipement</td>-->
									<td>[view.langs.transnoentities(Products)]</td>

									<td>[view.langs.transnoentities(Cost)]</td>
									<td>[view.langs.transnoentities(QtyNeeded)]</td>
									<td>
										[view.langs.transnoentities(PlannedQty)]
									</td>
									[onshow;block=begin;when [view.mode]=='edit']
									<td align="center" style="background-color:#efefef" width="30px">
									[view.link_update_qty_used;strconv=no]
									</td>
									[onshow;block=end]
									<td class="nodraft">[view.langs.transnoentities(QuantityUsed)]</td>
									<!-- <td class="draft">Delta</td> -->
									[onshow;block=begin;when [view.defined_workstation_by_needed]=='1']
										<td width="20%">[view.langs.transnoentities(Workstation)]</td>
									[onshow;block=end]
									[onshow;block=begin;when [view.defined_manual_wharehouse]=='1']
										<td width="20%">[view.langs.transnoentities(Warehouse)]</td>
									[onshow;block=end]
									<td class="draftedit" style="width:20px;">[view.langs.transnoentities(Action)]</td>

								</tr>
								<tr id="[TNeeded.id]">
                                    [onshow;block=begin;when [view.defined_show_categorie]=='1']
										<td width="20%">[TNeeded.categLabel;strconv=no]</td>
									[onshow;block=end]
									[onshow;block=begin;when [view.use_lot_in_of]=='1']
										<td>[TNeeded.lot_number;strconv=no]</td>
									[onshow;block=end]
									<!--<td>Equipement</td>-->
									<td class="col-01">[TNeeded.libelle;block=tr;strconv=no]<br />[TNeeded.note_private;strconv=no;]</td>

									<td class="col-02">[TNeeded.cost]</td>
									<td class="col-03">[TNeeded.qty_needed]</td>
									<td class="qty_planned col-04" data-qty='[TNeeded.qty_planned]'>[TNeeded.qty;strconv=no]</td>
									[onshow;block=begin;when [view.mode]=='edit']
									<td class="col-05">
									&nbsp;
									</td>
									[onshow;block=end]
									<td class="nodraft qty_used col-06">[TNeeded.qty_used;strconv=no]</td>
									<!-- <td class="draft">[TNeeded.qty_toadd]</td> -->
									[onshow;block=begin;when [view.defined_workstation_by_needed]=='1']
										<td  class="col-07" width="20%">[TNeeded.workstations;strconv=no]</td>
									[onshow;block=end]
									[onshow;block=begin;when [view.defined_manual_wharehouse]=='1']
										<td class="col-08" width="20%">[TNeeded.fk_entrepot;strconv=no]</td>
									[onshow;block=end]
									<td align='center' class="draftedit col-01">[TNeeded.delete;strconv=no]</td>

								</tr>

								[onshow;block=begin;when [view.show_cost]=='1']
								<tr style="background-color:#dedede;">
									[onshow;block=begin;when [view.use_lot_in_of]=='1']
										<td>&nbsp;</td>
									[onshow;block=end]
                                    [onshow;block=begin;when [view.defined_show_categorie]=='1']
										<td>&nbsp;</td>
									[onshow;block=end]

									<td>[view.langs.transnoentities(Cost)]</td>
									<td>&nbsp;</td>

									<td class="nodraft">[assetOf.compo_estimated_cost;strconv=no]</td>
									<td class="nodraft">[assetOf.compo_planned_cost;strconv=no]</td>
									[onshow;block=begin;when [view.mode]=='edit']
									<td>
									&nbsp;
									</td>
									[onshow;block=end]
									<td class="nodraft">[assetOf.compo_cost;strconv=no]</td>

									[onshow;block=begin;when [view.defined_workstation_by_needed]=='1']
										<td width="20%">&nbsp;</td>
									[onshow;block=end]
									[onshow;block=begin;when [view.defined_manual_wharehouse]=='1']
										<td width="20%">&nbsp;</td>
									[onshow;block=end]
									<td align='center' class="draftedit">&nbsp;</td>


								</tr>
								[onshow;block=end]

							</table>
						</td>
					</tr>
					<tr>
						<td colspan="4" style="border-left: none;height:40px;text-align: right;">
							[onshow;block=begin;when [view.mode]!='view']
								<a href="#" class="butAction btnaddproduct draftedit" id_assetOf="[assetOf.id]" rel="NEEDED">[view.langs.transnoentities(AddProduct)]</a>
							[onshow;block=end]
						</td>
					</tr>
				</table>
			</div>

			[onshow;block=begin;when [view.mode]=='view']
				<div class="tabsAction notinparentview buttonsAction">
					[onshow;block=begin;when [view.show_cost]=='1']
						<a onclick="if(!confirm('[view.langs.transnoentities(ReloadPriceFourn)]')) return false;" class="butActionDelete warning" href="[assetOf.url]?id=[assetOf.id]&action=reload_pmp">[view.langs.transnoentities(ReloadPrice)]</a>
					[onshow;block=end]
					[onshow;block=begin;when [stocktransfer.enable]==1]
					&nbsp; &nbsp; <a href="[stocktransfer.url]?action=create&id_of=[assetOf.id]" class="butAction">[view.langs.transnoentities(StockTransfer)]</a>
					[onshow;block=end]
					[onshow;block=begin;when [view.allow_delete_of_finish]!='1']
						[onshow;block=begin;when [view.status]=='CLOSE']
							<a class="butActionRefused" title="L'ordre de fabrication est terminé" href="#">[view.langs.transnoentities(Delete)]</a>
						[onshow;block=end]
						[onshow;block=begin;when [view.status]!='CLOSE']
							<a onclick="if(!confirm('[view.langs.transnoentities(DeleteOF)]')) return false;" class="butActionDelete" href="[assetOf.url]?id=[assetOf.id]&action=delete">[view.langs.transnoentities(Delete)]</a>
						[onshow;block=end]
					[onshow;block=end]
					[onshow;block=begin;when [view.allow_delete_of_finish]=='1']
						<a onclick="if(!confirm('[view.langs.transnoentities(DeleteOF)]')) return false;" class="butActionDelete" href="[assetOf.url]?id=[assetOf.id]&action=delete">[view.langs.transnoentities(Delete)]</a>
					[onshow;block=end]
					&nbsp; &nbsp; <a href="[assetOf.url]?id=[assetOf.id]&action=edit" class="butAction">[view.langs.transnoentities(Modify)]</a>

				</div>
			[onshow;block=end]

			[onshow;block=begin;when [view.mode]!='view']
				<p align="center">
					[onshow;block=begin;when [view.mode]!='add']
						<br />
						<input type="submit" value="[view.langs.transnoentities(Save)]" name="save" class="button">
						&nbsp; &nbsp; <input type="button" value="[view.langs.transnoentities(Cancel)]" name="cancel" class="button" onclick="document.location.href='[assetOf.url_liste]'">
						<br /><br />
					[onshow;block=end]
				</p>
			[onshow;block=end]

		</form>

	</div><!-- fin de OFMaster -->
	<div id="dialog" title="[view.langs.transnoentities(AddingProduct)]" style="display:none;width: 100%;">
		<table>
			<tr>
				<td>[view.langs.transnoentities(Product)] : </td>
				<td>
					[view.select_product;strconv=no]
				</td>
			</tr>
			[onshow;block=begin;when [view.ASSET_USE_MOD_NOMENCLATURE]=='1']
				<tr id="tr_select_nomenclature" style="display:none;">
					<td style="width:80px;" title="[view.langs.transnoentities(OFNomenclature)]">[view.langs.transnoentities(OFNomenclatureShort)] : </td>
					<td><select name="fk_nomenclature"></select></td>
				</tr>
			[onshow;block=end]
			<tr>
				<td style="width:80px;">[view.langs.transnoentities(Quantity)] : </td>
				<td><input type='text' size='4' value='1' name='default_qty_to_make' /></td>
			</tr>
		</table>
	</div>
	[onshow;block=begin;when [view.workstation_module_activate]==1]
		<div id="dialog-workstation" title="[view.langs.transnoentities(AddingWorkstation)]"  style="display:none;">
			<table>
				<tr>
					<td>[view.langs.transnoentities(Workstation)] : </td>
					<td>
						[view.select_workstation;strconv=no]
					</td>
				</tr>
			</table>
		</div>
	[onshow;block=end]
</div>

	<div style="clear:both;"></div>

	<script type="text/javascript">

		$(document).ready(function() {
			var type = "";

			/* Le 1er formulaire s'enregistre sans ajax, donc je prend la précaution de ne pas passer au statut suivant lors de l'enregistrement
			 ni de sauter l'étape de création */
			var formParent = $("div.OFMaster:first form");
			formParent.find("input[type=submit]").unbind().click(function() {
				var action = formParent.children("input[name=action]").val();
				if (action != "save" && action != "create") formParent.children("input[name=action]").val("save");
			});

			if([assetOf.id]>0) {
				getChild();
				refreshDisplay();
			}

			/* Couplage avec nomenclature */
			[onshow;block=begin;when [view.ASSET_USE_MOD_NOMENCLATURE]=='1']
				$('#fk_product').change(function() {
					var selectTarget = $("select[name=fk_nomenclature]");

					$.ajax({
						url: "script/interface.php?"
						,async: false
						,type: 'GET'
						,data:{
							get: 'getNomenclatures'
							,fk_product: $(this).val()
						}
						,dataType: 'json'
						,success: function(data) {
							$(selectTarget).empty();

							if (data.length > 0)
							{
								for (var i in data)
								{
									$(selectTarget).append($("<option qty_reference='"+data[i].qty_reference+"' "+(data[i].is_default ? 'selected="selected"' : '')+" value='"+data[i].rowid+"'>"+ (data[i].title == '' ? '[view.langs.transnoentities(withouttitle)]' : data[i].title) +"</option>"));
								}

								var qty = $(selectTarget).children('option:selected').attr('qty_reference');
								$('input[name=default_qty_to_make]').attr('value', qty);

								$('#tr_select_nomenclature').show();
							}
							else
							{
								$('input[name=default_qty_to_make]').attr('value', 1);
								$('#tr_select_nomenclature').hide();
							}

						}
					});
				});

			[onshow;block=end]
		});

		function getChild() {

			$('#assetChildContener > *:not("#titleOFEnfants")').remove();
			$('#assetChildContener').append('<p align="center" style="padding:10px; background:#fff;display:block;"><img src="img/loading.gif" /></p>');
			$('#assetChildContener').show();

			if([view.OF_MINIMAL_VIEW_CHILD_OF]==1) {

				$.ajax({

					url:'script/interface.php?get=getchildlisthtml&id=[assetOf.id]'
					,dataType:'html'

				}).done(function(data) {
					$('#assetChildContener').html(data);
				});


			}
			else{

				$.ajax({

					url:'script/interface.php?get=getofchildid&id=[assetOf.id]&json=1'
					,dataType:'json'

					,success: function(Tid) {

						if(Tid.length==0) {
							$('#assetChildContener').hide();
						}
						else {
							$('#assetChildContener > *:not("#titleOFEnfants")').remove();
							for(x in Tid ){

								$.ajax({
									url : "[assetOf.url]"
									,async: false
									,data:{
										action:"[view.actionChild]"
										,id:Tid[x]
									}
									,type: 'POST'
								}).done(function(data) {

									var html = $(data).find('div.OFMaster');
									html.find('.buttonsAction,.notinparentview').remove();

									var TAssetOFLineLot = html.find('input.TAssetOFLineLot');
									for (var i = 0; i < TAssetOFLineLot.length; i++)
									{
										$(TAssetOFLineLot).attr('disabled', 'true').css('border', 'none').css('background', 'none');
									}

									var id_form = html.find('form').attr('id');

									$('#assetChildContener').append(html);

									refreshDisplay();

									$('select[name^=TAssetOFLine]').change(function(){
										compose_fourni = $(this).find('option:selected').attr('compose_fourni');
										//alert(compose_fourni);
										assetOf_id = $(this).closest('.OFMaster').attr('assetof_id');
										//alert(assetOf_id);
										if(compose_fourni == 0){
											//alert($('.OFMaster[fk_assetOf_parent='+assetOf_id+']').length);
											$('.OFMaster[fk_assetOf_parent='+assetOf_id+']').css('border' , '5px solid red');
										}
										else{
											$('.OFMaster[fk_assetOf_parent='+assetOf_id+']').css('border' , '0px none');
										}
									});

									$("#"+id_form).submit(function() {
										var targetForm = this;

										if ($(this).attr('rel') == 'noajax') return true;

										var oldInputAction = $(targetForm).children('input[name=action]').val();
										//Soumission du formulaire en ajax, je force manuellement l'action à save pour le bouton Enregistrer
										$(targetForm).children('input[name=action]').val('save');
										$.ajax({
											url: $(targetForm).attr('action'),
											data: $(targetForm).serialize(),
											type: 'POST',
											async: false,
											success: function () {
												$(targetForm).css('border' , '5px solid green');

												$(targetForm).animate({ "border": "5px solid green" }, 'slow');
												$(targetForm).animate({ "border": "0px" }, 'slow');

												$.jnotify('[view.langs.transnoentities(ModificationSaved)]', "ok");

												//Maj de l'affichage du formulaire en question
												refreshTab($(targetForm).children('input[name=id]').val(), 'edit');
											},
											error: function () {
												$.jnotify("[view.langs.transnoentities(ErrorOccurred)]", "error");
											}
										});

										$(targetForm).children('input[name=action]').val(oldInputAction);
										return false;
									});
								});

							}

						}

					}
				});

			}

		}

		function submitForm(assetOFId, saveType) {

			if(saveType!=null) {
				$('#formOF'+assetOFId+' input[name=action]').val(saveType);
			}

			$('#formOF'+assetOFId).attr('rel', 'noajax').submit();
		}

		function refreshDisplay() {
			$(".btnaddproduct" ).unbind().click(function() {
				var type = $(this).attr('rel');
				var idassetOf = $(this).attr('id_assetOf');

                $('#fk_product').val(null).trigger('change');//reinit le select

				$( "#dialog" ).dialog({
					show: {
						effect: "blind",
						duration: 200
					},
					width: 500,
					modal:true,
					buttons: {
						"[view.langs.transnoentities(BtCancel)]": function() {
							$( this ).dialog( "close" );
						},
						"[view.langs.transnoentities(BtAdd)]": function(){
							var fk_product = $('#fk_product').val();
							var params = '';

							[onshow;block=begin;when [view.ASSET_USE_MOD_NOMENCLATURE]=='1']
								params += '&fk_nomenclature='+$('select[name=fk_nomenclature]').val();
							[onshow;block=end]

							params += '&default_qty_to_make='+$('input[name=default_qty_to_make]').val();

							$.ajax({
								url : "script/interface.php?get=addofproduct&id_assetOf="+idassetOf+"&fk_product="+fk_product+"&type="+type+"&user_id=[view.user_id]"+params
							})
							.done(function(){
								//document.location.href="?id=[assetOf.id]";
								$( "#dialog" ).dialog("close");
								refreshTab(idassetOf, 'edit');
								getChild();
							});
						}
					}
				});

			});
			[onshow;block=begin;when [view.ASSET_USE_MOD_NOMENCLATURE]=='1']
			$('.valider_nomenclature').unbind().click(function() {
					var id_assetOF = $(this).data('id_of');

					var select = $(this).parent().children('select');
					var qty = $(this).parent().next().children('input[type="text"]');

					$.ajax({
						url: "script/interface.php"
						,type: 'GET'
						,data:{
							get: 'validernomenclature'
							,id_assetOF: id_assetOF
							,fk_product: $(this).data('product')
							,fk_of_line: $(this).data('of_line')
							,fk_nomenclature: select.val()
							,qty: qty.val()
						}
					}).done(function(data){
						//$('.OFMaster').html(data);
						refreshTab(id_assetOF, '[view.mode]');
					});
				});

			[onshow;block=end]
			$(".btnaddworkstation" ).unbind().click(function() {
				var from = $(this);
				$( "#dialog-workstation" ).dialog({
					show: {
						effect: "blind",
						duration: 200
					},
					modal:true,
					buttons: {
						"[view.langs.transnoentities(BtCancel)]": function() {
							$( this ).dialog( "close" );
						},
						"[view.langs.transnoentities(BtAdd)]": function(){
							var idassetOf = from.attr('id_assetOf');
							var fk_asset_workstation = $('#fk_asset_workstation').val();

							$.ajax({
								url : "script/interface.php?get=addofworkstation&id_assetOf="+idassetOf+"&fk_asset_workstation="+fk_asset_workstation+"&user_id=[view.user_id]"
							})
							.done(function(){
								//document.location.href="?id=[assetOf.id]";
								$( "#dialog-workstation" ).dialog("close");
								refreshTab(idassetOf, '[view.mode]');
							});
						}
					}
				});
			});

			if([assetOf.id]>0) {
				$('div.of-details').show();
			}

			if('[view.mode]'=='view'){
				$('span.viewmode').css('display','inline');
			}

			if("[view.actionChild]"=="edit") {
					$('#assetChildContener div.draftedit').css('display','block');
					$('#assetChildContener a.draftedit').css('display','inline');
					$('#assetChildContener td.draftedit,th.draftedit').css('display','table-cell');
			}

			if("[view.mode]"=="edit") {
					$('#assetChildContener div.draftedit').css('display','block');
					$('#assetChildContener a.draftedit').css('display','inline');
					$('#assetChildContener td.draftedit,th.draftedit').css('display','table-cell');
			}

			if("[view.status]"=='DRAFT') {

				$('div.OFMaster td.draft').css('display','table-cell');


				if('[view.mode]'!='view'){
					$('div.OFMaster div.draftedit').css('display','block');
					$('div.OFMaster a.draftedit').css('display','inline');
					$('div.OFMaster td.draftedit,div.OFMaster th.draftedit').css('display','table-cell');
				}

			}
			else {
				$('td.nodraft').css('display','table-cell');
			}

			[onshow;block=begin;when [view.use_lot_in_of]==1]
				$(".TAssetOFLineLot").each(function(){
					var fk_product = $(this).attr('fk_product');
					var type = $(this).attr('type_product');
					$(this).autocomplete({
						source: "script/interface.php?get=autocomplete&json=1&fieldcode=lot_number&fk_product="+fk_product+"&type_product="+type,
						minLength : 1
					}).change(function() {
						var inputTarget = $(this).parent().next().find('input[rel=add-asset]');
						$(inputTarget).autocomplete({
							source: "script/interface.php?get=autocomplete-serial&json=1&fk_product="+fk_product+"&lot_number="+$('input[rel=lot-'+$(inputTarget).attr('fk-asset-of-line')+']').val()
							,minLength: 1
							,select: function(event, ui) {
								var value = ui.item.value;
							}
						});
					});
				})

				$('input[rel=add-asset]').each(function(){
				    var fk_product = $(this).attr('fk_product');
				    var idline = $(this).attr('fk-asset-of-line');
                    var lot = $('input[rel=lot-'+idline+']').val();
                    $(this).autocomplete({
                        source: "script/interface.php?get=autocomplete-serial&json=1&lot_number="+lot+"&fk_product="+fk_product
                        ,minLength : 1
                        ,select: function(event, ui) {
							var value = ui.item.value;
							var res = value.match(/^\[[0-9]*\]/g);

							if (res.length){
								res = res[0].substr(1, res[0].length-2);
							} else {
								res = 0;
							}

							if(res > 0) {
								let $btnAssetFromAutoComplete = $(this).parent().children('a.add-asset-from-autocomplete');
								var href = $btnAssetFromAutoComplete.attr('base-href');
								$btnAssetFromAutoComplete.attr('href', href+res)
								$btnAssetFromAutoComplete.show();
								window.location.replace(href + res + "#" + idline);
							}
						}
                    });
                })

			[onshow;block=end]
		}

		function refreshTab(id, action) {
			if (typeof(action) == 'undefined') action = 'view';

			$.get("fiche_of.php?action="+action+"&id="+id , function(data) {

		     	$('div.OFMaster[assetof_id='+id+'] table.needed').replaceWith(  $(data).find('div.OFMaster[assetof_id='+id+'] table.needed') );
		     	$('div.OFMaster[assetof_id='+id+'] table.tomake').replaceWith(  $(data).find('div.OFMaster[assetof_id='+id+'] table.tomake') );
		     	$('div.OFMaster[assetof_id='+id+'] table.workstation').replaceWith(  $(data).find('div.OFMaster[assetof_id='+id+'] table.workstation') );

		     	refreshDisplay();
			});

		}

		function deleteLine(idLine,type){
			$.ajax(
				{
					url : "script/interface.php?get=deletelineof&idLine="+idLine+"&type="+type
					,dataType : 'json'
				}
			).done(function(TidAssetOF){
				if (TidAssetOF != 0)
				{
					for (var i in TidAssetOF)
					{
						$('div.OFMaster[assetof_id='+TidAssetOF[i]+']').remove();
					}
				}

				if ($('#assetChildContener div.OFMaster').length <= 0) $('#assetChildContener').css('display', 'none');

				$("#"+idLine).remove();
				refreshTab($('.OFContent').attr('rel'), 'edit');
			});
		}

		function deleteWS(id_assetOf,idWS) {
			$.ajax(
				{url : "script/interface.php?get=deleteofworkstation&id_assetOf=[assetOf.id]&fk_asset_workstation_of="+idWS+"&user_id=[view.user_id]" }
			).done(function(){
				refreshTab(id_assetOf, 'edit') ;
			});

		}

		function updateQtyNeededForMaking(id_assetOf,idLine,btnadd) {

			[onshow;block=begin;when [view.mode]=='view']
				return alert("[view.langs.transnoentities(OFMustBeDraftAndEditMode)]");
			[onshow;block=end]

			[onshow;block=begin;when [view.mode]!='view']

				if ($(btnadd).attr('statut') == 'DRAFT' || $(btnadd).attr('statut') == 'OPEN') {
					qty = $(btnadd).closest('tr').find("input[id*='qty']").val();
					qty_used = $(btnadd).closest('tr').find("input[id*='qty_used']").val();
					qty_non_compliant = $(btnadd).closest('tr').find("input[id*='qty_non_compliant']").val();

					if ($(btnadd).attr('statut') == 'DRAFT') {
						action = 'updateqty';
					} else {
						action = 'updateqty_usernocompliant';
					}

					$.ajax({
						url: "script/interface.php?get=updateQtyMaking&id="+id_assetOf+"&idLine="+idLine+"&action="+action+"&qty="+qty+"&qty_used="+qty_used+"&qty_non_compliant="+qty_non_compliant+"&type=json"
						,dataType: 'json'
					}).done(function(result){

						if(result) {
							 $.jnotify("[view.langs.transnoentities(QtyUpdated)]", "ok");
						}
						else{
							$.jnotify("[view.langs.transnoentities(SomeQtyCantBeUpdated)]", "warning");
						}

						refreshTab($('.OFContent').attr('rel'), 'edit');
						getChild();
						refreshDisplay();

					});
				} else {
					$.jnotify("[view.langs.transnoentities(OFIsNotDraftStatus)]","errors");
				}
			[onshow;block=end]

		}


		function quickEditField(idOf,a) {
			var $a = $(a);

			var $tr = $a.closest('tr[rel]');

			var field = $tr.attr('rel');

			$('a.quickEditButton').hide();

			$.ajax({
				url:"fiche_of.php"
				,data:{
					action:"edit"
					,id:idOf
				}
			}).done(function(html) {
				$input = $(html).find('tr[rel='+field+']>td.editableField');

				$input.append('<input class="button" value="[view.langs.transnoentities(Modify)]" type="submit">');

				$tr.find('td.editableField').replaceWith( $input );

				$('div.OFMaster > form input[name=action]').val('quick-save');
			});

		}

		/**
		 * Permet de mettre à jour les inputs "Quantité utilisée" sur l'édition de l'OF avec la colonne "Qté planifiée"
		 */
		function updateQtyUsed(img_picto)
		{
			$(img_picto).closest('table').find('.qty_planned').each(function(i, item) {
				$(item).next('td').next('.qty_used').children('input').val($(item).data('qty'));
			});
		}
</script>
<!-- END fiche_of.tpl -->
