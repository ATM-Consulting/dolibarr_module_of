<?php

/**
 * Collection of tools related to OF
 *
 * Class OFTools
 */
class OFTools
{
    /**
     * @param TPDOdb $PDOdb
     * @param array $TProduct
     * @param array $TQuantites
     * @param int $fk_commande
     * @param int $fk_soc
     * @param bool $oneOF
     */
    static public function _createOFCommande(&$PDOdb, $TProduct, $TQuantites, $fk_commande, $fk_soc, $oneOF = false)
    {
        global $db, $langs, $conf;

        if(!empty($TProduct))
        {

            $commande = new Commande($db);
            if($commande->fetch($fk_commande)<=0) {

                accessforbidden($langs->trans('CannotLoadThisOrderAreYouInTheGoodEntity'));

            }

            if($oneOF)
            {
                $assetOf = new TAssetOF;
                $assetOf->fk_commande = $fk_commande;
            }

            foreach($TProduct as $fk_commandedet => $v)
            {
                foreach($v as $fk_product=>$dummy)
                {
                    if(!$oneOF)
                    {
                        $assetOf = new TAssetOF;
                        $assetOf->fk_commande = $fk_commande;
                    }

                    if($assetOf->fk_commande > 0)
                    {
                        $com = new Commande($db); //TODO on est pas censé toujours être sur la même commande ? AA
                        $com->fetch($assetOf->fk_commande);
                        $assetOf->fk_project = $com->fk_project;
                        if(!empty($com->date_livraison)) $assetOf->date_besoin = $com->date_livraison;
                    }

                    $qty = $TQuantites[$fk_commandedet];

                    $note_private = '';

                    if(! empty($conf->global->OF_HANDLE_ORDER_LINE_DESC))
                    {
                        $line = new OrderLine($db);
                        $line->fetch($fk_commandedet);

                        $desc = trim($line->desc);

                        if(! empty($desc))
                        {
                            $note_private = $desc;
                        }
                    }

                    $assetOf->fk_soc = $fk_soc;
                    $idLine = $assetOf->addLine($PDOdb, $fk_product, 'TO_MAKE', $qty, 0, '', 0, $fk_commandedet, $note_private);
                    $assetOf->save($PDOdb);

                    if(!empty($conf->global->OF_KEEP_ORDER_DOCUMENTS) && !$oneOF && $assetOf->fk_commande > 0) {
                        $order_dir = $conf->commande->dir_output . "/" . dol_sanitizeFileName($com->ref);
                        $assetOf->copyAllFiles($order_dir);
                    }

                    if(!empty($conf->{ ATM_ASSET_NAME }->enabled) && !empty($conf->global->USE_ASSET_IN_ORDER)) {

                        $TAsset = GETPOST('TAsset', 'none');
                        if(!empty($TAsset[$fk_commandedet])) {
                            dol_include_once('/' . ATM_ASSET_NAME . '/class/asset.class.php');

                            $asset=new TAsset();
                            if($asset->load($PDOdb, $TAsset[$fk_commandedet])) {
                                $assetOf->addAssetLink($asset, $idLine);
                            }
                        }
                    }


                }
            }
            if(!empty($conf->global->OF_KEEP_ORDER_DOCUMENTS) && $oneOF && $assetOf->fk_commande > 0) {
                $order_dir = $conf->commande->dir_output . "/" . dol_sanitizeFileName($com->ref);
                $assetOf->copyAllFiles($order_dir);
            }

            setEventMessage($langs->trans('OFAssetCreated'), 'mesgs');
        }

    }

    /**
     * @param string $workstations
     * @return string
     */
    static public function get_format_label_workstation($workstations=null) {

        global $db,$langs, $TCacheWorkstation;

        if (!empty($workstations))
        {
            $res='';

            $TId = explode(',',$workstations);
            foreach($TId as $fk_ws) {
                if(!empty($res))$res.=', ';
                $res.=$TCacheWorkstation[$fk_ws];
            }

            return $res;
        }
        else
        {
            return '';
        }

    }

    /**
     * @param int $fk_product
     * @return int|string
     */
    static public function get_format_libelle_produit($fk_product = null)
    {
        global $db,$langs;

        if (!empty($fk_product))
        {
            $TId = explode(',',$fk_product);
            $nb_product = count($TId);

            $product = new Product($db);
            $product->fetch($TId[0]);
            $product->ref.=' '.$product->label;

            $res = $product->getNomUrl(1).($nb_product>1 ? ' + '.($nb_product-1).' '.$langs->trans('products') : '');
            return $res;
        }
        else
        {
            return $langs->trans('ProductUndefined');
        }
    }

    /**
     * @param string $numeros
     * @param int $id
     * @return string
     */
    static public function get_format_link_of($numeros,$id) {

        $TNumero = explode(',', $numeros);

        if(count($TNumero) == 1) return '<a href="'.dol_buildpath('/of/fiche_of.php', 1).'?id='.$id.'">'.img_picto('','object_list.png','',0).' '.$TNumero[0].'</a>';

        $TReturn=array();
        foreach($TNumero as $numero) {

            $TReturn[] = '<a href="'.dol_buildpath('/of/fiche_of.php', 1).'?ref='.$numero.'">'.img_picto('','object_list.png','',0).' '.$numero.'</a>';

        }

        return implode(', ',$TReturn);
    }

    /**
     * @param int $fk_soc
     * @return string
     */
    static public function get_format_libelle_societe($fk_soc)
    {
        global $db;

        if($fk_soc>0)
        {
            $societe = new Societe($db);
            $societe->fetch($fk_soc);
            $url = $societe->getNomUrl(1);

            return $url;
        }

        return '';
    }

    /**
     * @param int $fk
     * @return mixed|string
     */
    static public function get_format_label_supplier_order($fk){
        global $db;

        if($fk>0)
        {
            $o = new CommandeFournisseur($db);
            if($o->fetch($fk)>0) return $o->getNomUrl(1).' - '.$o->getLibStatut(0);
            else return $fk;
        }

        return '';
    }

    /**
     * @param int $fk
     * @param int $fk_commandedet
     * @param string $fk_products
     * @return int|string
     */
    static public function get_format_libelle_commande($fk, $fk_commandedet=0, $fk_products='')
    {
        global $db,$langs,$conf;
        $TCommandeIds = array();
        if(strpos($fk,',')!==false) {
            $TCommandeIds = explode(',', $fk);
        }else $TCommandeIds[] = (int)$fk;

        $fk_commandedet = (int)$fk_commandedet;
        if(!empty($TCommandeIds)) {
            $res = '';
            foreach($TCommandeIds as $fk) {
                $fk = (int) $fk;

                if($fk > 0) {
                    $o = new Commande($db);
                    if($o->fetch($fk) > 0) {

                        $res .= '<div style="white-space:nowrap;">' . $o->getNomUrl(1);
                        $res .= '<br />' . price($o->total_ht, 0, $langs, 1, -1, -1, $conf->currency);
                        $res .= '</div>';


                    } else return $fk;
                }
            }
            return $res;
        }

        return '';
    }

    /**
     * @param int $fk
     * @return mixed|string
     */
    static public function get_format_libelle_projet($fk) {
        global $db;

        if($fk>0)
        {
            dol_include_once('/projet/class/project.class.php');
            $o = new Project($db);
            if($o->fetch($fk)>0) return $o->getNomUrl(1);
            else return $fk;
        }

        return '';
    }

    /**
     * @param TPDOdb $PDOdb
     */
    static public function _printTicket(&$PDOdb)
    {
        global $db,$conf,$langs;

        $dirName = 'OF_TICKET('.date("Y_m_d").')';
        $dir = DOL_DATA_ROOT.'/of/'.$dirName.'/';
        $fileName = date('YmdHis').'_ETIQUETTE';

        $TPrintTicket = GETPOST('printTicket', 'array');
        $TInfoEtiquette = self::_genInfoEtiquette($db, $PDOdb, $TPrintTicket);

        //var_dump($TInfoEtiquette);exit;
        @mkdir($dir, 0777, true);

        if(defined('TEMPLATE_OF_ETIQUETTE')) $template = TEMPLATE_OF_ETIQUETTE;
        else if($conf->global->DEFAULT_ETIQUETTES == 2){
            $template = "etiquette_custom.html";
        }else{
            $template = "etiquette.html";
        }

        $TBS=new TTemplateTBS();
        $templatefile=DOL_DATA_ROOT.'/of/template/'.$template;
        if(!is_file($templatefile)) $templatefile = dol_buildpath('/of/exempleTemplate/'.$template);

        $file_path = $TBS->render($templatefile
            ,array(
                'TInfoEtiquette'=>$TInfoEtiquette
            )
            ,array(
                'date'=>date("d/m/Y")
            ,'margin_top' =>  intval($conf->global->DEFINE_MARGIN_TOP)
            , 'margin_left_impair' => intval($conf->global->DEFINE_MARGIN_LEFT)
            , 'width' => intval($conf->global->DEFINE_WIDTH_DIV)
            , 'height' => intval($conf->global->DEFINE_HEIGHT_DIV)
            , 'margin_right_pair' =>intval($conf->global->DEFINE_MARGIN_RIGHT)
            , 'margin_top_cell' =>intval($conf->global->DEFINE_MARGIN_TOP_CELL)
            , 'langs' => $langs
            , 'display_note' => empty($conf->global->OF_HANDLE_ORDER_LINE_DESC) ? 0 : 1
            )
            ,array()
            ,array(
                'outFile'=>$dir.$fileName.".html"
            ,'convertToPDF'=>true
            )

        );

        header("Location: ".dol_buildpath("/document.php?modulepart=of&entity=1&file=".$dirName."/".$fileName.".pdf", 1));
        exit;
    }

    /**
     * @param DoliDB $db
     * @param TPDOdb $PDOdb
     * @param aray $TPrintTicket
     * @return array
     */
    static public function _genInfoEtiquette(&$db, &$PDOdb, &$TPrintTicket)
    {
        global $conf;

        $TInfoEtiquette = array();
        if (empty($TPrintTicket)) return $TInfoEtiquette;

        dol_include_once('/commande/class/commande.class.php');

        $assetOf = new TAssetOF;
        $cmd = new Commande($db);
        $product = new Product($db);
        $pos = 1;
        $cpt=0;
        foreach ($TPrintTicket as $fk_assetOf => $qty)
        {
            if ($qty <= 0) continue;

            $load = $assetOf->load($PDOdb, $fk_assetOf);

            if ($load === true)
            {
                $cmd->fetch($assetOf->fk_commande);

                foreach ($assetOf->TAssetOFLine as &$assetOfLine)
                {

                    if ($assetOfLine->type == 'TO_MAKE' && $product->fetch($assetOfLine->fk_product) > 0)
                    {
                        for ($i = 0; $i < $qty; $i++)
                        {
                            $cpt++;
                            if (($cpt%2)==0)$div='pair';
                            else $div='impair';
                            $TInfoEtiquette[] = array(
                                'numOf' => $assetOf->numero
                            ,'float' => $div
                            ,'refCmd' => $cmd->ref
                            ,'refCliCmd' => $cmd->ref_client
                            ,'refProd' => $product->ref
                            ,'qty_to_print' => $qty
                            ,'qty_to_make' => $assetOfLine->qty
                            ,'label' => wordwrap(preg_replace('/\s\s+/', ' ', $product->label), 20, $conf->global->DEFAULT_ETIQUETTES == 2?"\n":"</br>")
                            ,'pos' => ceil($pos/8)
                            ,'note_private' => $assetOfLine->note_private
                            );

                            //var_dump($TInfoEtiquette);exit;
                            $pos++;
                            //var_dump($TInfoEtiquette);
                        }
                    }
                }
            }

        }//exit;

        return $TInfoEtiquette;
    }

    /**
     * @param string $name
     * @param int $value
     * @return string
     */
    static public function get_number_input($name, $value) {
        return '<input type="number" name="'.$name.'" value="'.$value.'"/><input type="hidden" name="old_'.$name.'" value="'.$value.'"/>';
    }

    /**
     * @param TPDOdb $PDOdb
     * @param string $TNewRank
     * @param string $TOldRank
     */
    static public function _setAllRank($PDOdb, $TNewRank, $TOldRank) {
        $TToUpdate= array();

        //On récupère uniquement les ofs qui ont été modifiés
        foreach($TNewRank as $key => $val){
            if($val != $TOldRank[$key]) $TToUpdate[$key] = $val;
        }
        if(!empty($TToUpdate)) {
            asort($TToUpdate); //On réordonne par value (pour que les valeurs les plus basses soient traités en première)
            foreach($TToUpdate as $fk_of => $new_rank) {
                $assetOf = new TAssetOF;
                $assetOf->load($PDOdb, $fk_of);
                $assetOf->rank = $new_rank;
                $assetOf->save($PDOdb);
            }
        }
    }
}