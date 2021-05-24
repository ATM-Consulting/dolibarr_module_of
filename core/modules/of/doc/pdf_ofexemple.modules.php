<?php
/* Copyright (C) 2004-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2013	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      	Christophe Battarel <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cedric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2017       Ferran Marcet       <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/commande/doc/pdf_atmeratosthene.modules.php
 *	\ingroup    commande
 *	\brief      Fichier de la classe permettant de generer les commandes au modele Eratosthène
 */

require_once dol_buildpath('of/core/modules/of/modules_of.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once dol_buildpath('pdfevolution/core/lib/pdf.lib.php'); // include lib not in current DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php' for backward compatibility;


/**
 *	Classe to generate PDF orders with template atmeratosthene
 */
class pdf_ofexemple extends ModelePDFOf
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var int 	Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array() Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.3 = array(5, 3)
	 */
	public $phpmin = array(5, 2);

	/**
	 * Dolibarr version of the loaded document
	 * @public string
	 */
	public $version = 'development';

	public $page_largeur;
	public $page_hauteur;
	public $format;
	public $marge_gauche;
	public $marge_droite;
	public $marge_haute;
	public $marge_basse;

	public $emetteur;	// Objet societe qui emet


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf,$langs,$mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills", "products", "pdfevolution@pdfevolution", "ofpdf@of"));

		$this->db = $db;
		$this->name = "PDF OF EXEMPLE - Pour les dev";
		$this->description = 'Utiliser comme base pour la création de PDF OF ce modèle est a faire évoluer il n\'est pas disponible par défaut ';
		$this->update_main_doc_field = 1;		// Save the name of generated file as the main doc when generating a doc with this template

		// Dimension page
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode reglement
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 0;                // Affiche si il y a eu escompte
		$this->option_credit_note = 0;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   // Support add of a watermark on drafts

		// Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined

		// Define position of columns
		$this->posxdesc=$this->marge_gauche+1;


		$this->tabTitleHeight = 5; // default height

		$this->tva=array();
		$this->localtax1=array();
		$this->localtax2=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}

	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		TAssetOF	$object				Object to generate
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *  @return     int             			    1=OK, 0=KO
	 */
	function write_file($object, $outputlangs, $srctemplatepath='', $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblignes;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		// Translations
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "orders", "deliveries", "ofpdf@of"));

		$nblignes = count($object->TAssetOFLine);

		// Documents
		$objectref = dol_sanitizeFileName($object->ref);
		$filedir = $conf->of->multidir_output[$object->entity]  . '/' . $objectref;



		// Definition of $dir and $file
		if ($object->specimen)
		{
			$file = $filedir . "/SPECIMEN.pdf";
		}
		else
		{
			$file = $filedir . "/exemple " . $objectref . ".pdf";
		}

		if (! file_exists($filedir))
		{
			if (dol_mkdir($filedir) < 0)
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$filedir);
				return 0;
			}
		}

		if (file_exists($filedir))
		{
			$object->order = false;
			if(!empty($object->fk_commande)) {
				dol_include_once('/commande/class/commande.class.php');
				$object->order = new Commande($db);
				if($object->order->fetch($object->fk_commande) <= 0){
					$object->order = false;
				}
			}

			if ($object->fk_soc) {
				require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
				$object->thirdparty = new Societe($this->db);
				if ($object->thirdparty->fetch($object->fk_soc) <= 0) { $object->thirdparty = false; }
			}


			// Add pdfgeneration hook
			if (! is_object($hookmanager))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
				$hookmanager=new HookManager($this->db);
			}
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
			global $action;
			$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

			// Create pdf instance
			$pdf=pdf_getInstance($this->format);
			$default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
			$pdf->SetAutoPageBreak(1,0);

			if (class_exists('TCPDF'))
			{
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}

			$pdf->SetFont(pdf_getPDFFont($outputlangs));
			// Set path to the background PDF File
			if (! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
			{
				$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
				$tplidx = $pdf->importPage(1);
			}

			$pdf->Open();
			$pagenb=0;
			$pdf->SetDrawColor(128,128,128);

			$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
			$pdf->SetSubject($outputlangs->transnoentities("PdfOrderTitle"));
			$pdf->SetCreator("Dolibarr ".DOL_VERSION);
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfOrderTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
			if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right


			// New page
			$pdf->AddPage();
			if (! empty($tplidx)) $pdf->useTemplate($tplidx);
			$pagenb++;
			$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs);


			// Chargement des labels des extrafield
			$extrafields = new ExtraFields($db);
			$extrafields->fetch_name_optionals_label('product');

			// Finalement en HTML c'est mieux
			// Loop on each lines
			$html='';
			foreach ($object->TAssetOFLine as $i => $line) {

				$html.='your html text for line '.$i.'<br/>';


			}

			$pdf->setCellPaddings(1, 1, 1, 1);
			$pdf->writeHTMLCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 10, $this->marge_gauche, $top_shift + 10, $outputlangs->convToOutputCharset($html), 0, 1);

			$posy = $pdf->GetY();


			// Pied de page
			$this->_pagefoot($pdf, $object, $outputlangs);
			if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

			$pdf->Close();

			$pdf->Output($file, 'F');

			// Add pdfgeneration hook
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters=array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
			global $action;
			$reshook=$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

			if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

			$this->result = array('fullpath'=>$file);

			return 1;   // Pas d'erreur
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$filedir);
			return 0;
		}
	}


	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  TAssetOF		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	string		$titlekey		Translation key to show as title of document
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey="PdfOrderTitle")
	{
		global $conf,$langs,$hookmanager;

		// Translations
		$outputlangs->loadLangs(array("main", "bills", "propal", "orders", "companies", "ofpdf@of"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		// Show Draft Watermark
		if($object->statut==0 && (! empty($conf->global->OF_DRAFT_WATERMARK)) )
		{
			pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->OF_DRAFT_WATERMARK);
		}

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

		$posy=$this->marge_haute;
		$posx=$this->marge_gauche;

		$pdf->SetXY($this->marge_gauche,$posy);


		// texte N° de OF
		$pdf->SetFont('','B', $default_font_size);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,0);
		$title=$outputlangs->transnoentities('PDFOfNumber');
		$pdf->MultiCell(80, 3, $title, '', 'L');

		// REF OF
		$pdf->SetXY($this->page_largeur/2 - 30,$posy-3);
		$pdf->SetFont('','B',$default_font_size+3);
		$pdf->setCellPaddings(1, 1, 1, 1);
		$pdf->setCellMargins(1, 1, 1, 1);
		// set color for background
		$pdf->MultiCell(60, 3, $outputlangs->convToOutputCharset($object->ref), true, 'C');

		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->setCellMargins(0, 0, 0, 0);

		// DATE
		$pdf->SetFont('','',$default_font_size);
		$pdf->SetXY($this->page_largeur - $this->marge_droite - 80,$posy);
		$pdf->MultiCell(80, 3, $outputlangs->transnoentities("Du")." : " . dol_print_date($object->date_lancement,"%d %b %Y",false,$outputlangs,true), '', 'R');


		// Finalement en HTML c'est mieux
		$html='<table>';

		$html.='<tr>';

		$html.='<td>';
		if ($object->thirdparty){
			$html.= $outputlangs->transnoentities("Customer")." : <strong>" . $outputlangs->convToOutputCharset($object->thirdparty->name).'</strong>';
		}
		$html.='</td>';

		$html.='<td>';
		if ($object->ref_client){
			$html.= $outputlangs->transnoentities("CustomerOrderNumber")." : <strong>" . $outputlangs->convToOutputCharset($object->ref_client).'</strong>';
		}
		$html.='</td>';

		$html.='</tr>';


		$html.='</table>';

		$pdf->setCellPaddings(1, 1, 1, 1);
		$pdf->writeHTMLCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 10, $this->marge_gauche, $posy + 10, $html);


		$pdf->SetTextColor(0,0,0);
		return $pdf->GetY();
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	TAssetOF		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		global $conf;
		$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		//return pdf_pagefoot($pdf,$outputlangs,'ORDER_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
	}



	/**
	 *   	Define Array Column Field
	 *
	 *   	@param	TAssetOF			$object
	 *   	@param	outputlangs		$outputlangs    langs
	 *      @param	int			   $hidedetails		Do not show line details
	 *      @param	int			   $hidedesc		Do not show desc
	 *      @param	int			   $hideref			Do not show ref
	 *      @return	null
	 */
	function defineColumnField($object,$outputlangs,$hidedetails=0,$hidedesc=0,$hideref=0){

		global $conf, $hookmanager;

		// Default field style for content
		$this->defaultContentsFieldsStyle = array(
			'align' => 'R', // R,C,L
			'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		// Default field style for content
		$this->defaultTitlesFieldsStyle = array(
			'align' => 'C', // R,C,L
			'padding' => array(0.5,0,0.5,0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		/*
		 * For exemple
		 $this->cols['theColKey'] = array(
		 'rank' => $rank, // int : use for ordering columns
		 'width' => 20, // the column width in mm
		 'title' => array(
		 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
		 'label' => ' ', // the final label : used fore final generated text
		 'align' => 'L', // text alignement :  R,C,L
		 'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		 ),
		 'content' => array(
		 'align' => 'L', // text alignement :  R,C,L
		 'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		 ),
		 );
		 */

		$rank=0; // do not use negative rank
		$this->cols['desc'] = array(
			'rank' => $rank,
			'width' => false, // only for desc
			'status' => true,
			'title' => array(
				'textkey' => 'Designation', // use lang key is usefull in somme case with module
				'align' => 'L',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'L',
			),
		);

		$rank = $rank + 10;
		$this->cols['photo'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH)?20:$conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Photo',
				'label' => ' '
			),
			'content' => array(
				'padding' => array(0,0,0,0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'border-left' => false, // remove left line separator
		);

		if (! empty($conf->global->MAIN_GENERATE_ORDERS_WITH_PICTURE))
		{
			$this->cols['photo']['status'] = true;
		}


		$rank = $rank + 10;
		$this->cols['vat'] = array(
			'rank' => $rank,
			'status' => false,
			'width' => 16, // in mm
			'title' => array(
				'textkey' => 'VAT'
			),
			'border-left' => true, // add left line separator
		);

		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN))
		{
			$this->cols['vat']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['subprice'] = array(
			'rank' => $rank,
			'width' => 19, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'PriceUHT'
			),
			'border-left' => true, // add left line separator
		);

		$rank = $rank + 10;
		$this->cols['qty'] = array(
			'rank' => $rank,
			'width' => 16, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'Qty'
			),
			'border-left' => true, // add left line separator
		);

		$rank = $rank + 10;
		$this->cols['progress'] = array(
			'rank' => $rank,
			'width' => 19, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Progress'
			),
			'border-left' => false, // add left line separator
		);

		if($this->situationinvoice)
		{
			$this->cols['progress']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['unit'] = array(
			'rank' => $rank,
			'width' => 11, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Unit'
			),
			'border-left' => true, // add left line separator
		);
		if($conf->global->PRODUCT_USE_UNITS){
			$this->cols['unit']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['discount'] = array(
			'rank' => $rank,
			'width' => 13, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'ReductionShort'
			),
			'border-left' => true, // add left line separator
		);
		if ($this->atleastonediscount){
			$this->cols['discount']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['totalexcltax'] = array(
			'rank' => $rank,
			'width' => 26, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'TotalHT'
			),
			'border-left' => true, // add left line separator
		);


		$parameters=array(
			'object' => $object,
			'outputlangs' => $outputlangs,
			'hidedetails' => $hidedetails,
			'hidedesc' => $hidedesc,
			'hideref' => $hideref
		);

		$reshook=$hookmanager->executeHooks('defineColumnField',$parameters,$this);    // Note that $object may have been modified by hook
		if ($reshook < 0)
		{
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}
		elseif (empty($reshook))
		{
			$this->cols = array_replace($this->cols, $hookmanager->resArray); // array_replace is used to preserve keys
		}
		else
		{
			$this->cols = $hookmanager->resArray;
		}

	}



	/*
	 *
	 * DEBUT PARTIE NORMALEMENT DANS LA CLASSE CommonDocGenerator
	 *
	 *
	 */

	/**
	 *   	uasort callback function to Sort colums fields
	 *
	 *   	@param	array			$a    			PDF lines array fields configs
	 *   	@param	array			$b    			PDF lines array fields configs
	 *      @return	int								Return compare result
	 */
	function columnSort($a, $b) {

		if(empty($a['rank'])){ $a['rank'] = 0; }
		if(empty($b['rank'])){ $b['rank'] = 0; }
		if ($a['rank'] == $b['rank']) {
			return 0;
		}
		return ($a['rank'] > $b['rank']) ? -1 : 1;

	}

	/**
	 *   	Prepare Array Column Field
	 *
	 *   	@param	object			$object    		common object
	 *   	@param	outputlangs		$outputlangs    langs
	 *      @param		int			$hidedetails		Do not show line details
	 *      @param		int			$hidedesc			Do not show desc
	 *      @param		int			$hideref			Do not show ref
	 *      @return	null
	 */
	function prepareArrayColumnField($object,$outputlangs,$hidedetails=0,$hidedesc=0,$hideref=0){

		global $conf;

		$this->defineColumnField($object,$outputlangs,$hidedetails,$hidedesc,$hideref);


		// Sorting
		uasort ( $this->cols, array( $this, 'columnSort' ) );

		// Positionning
		$curX = $this->page_largeur-$this->marge_droite; // start from right

		// Array witdh
		$arrayWidth = $this->page_largeur-$this->marge_droite-$this->marge_gauche;

		// Count flexible column
		$totalDefinedColWidth = 0;
		$countFlexCol = 0;
		foreach ($this->cols as $colKey =>& $colDef)
		{
			if(!$this->getColumnStatus($colKey)) continue; // continue if desable

			if(!empty($colDef['scale'])){
				// In case of column widht is defined by percentage
				$colDef['width'] = abs($arrayWidth * $colDef['scale'] / 100 );
			}

			if(empty($colDef['width'])){
				$countFlexCol++;
			}
			else{
				$totalDefinedColWidth += $colDef['width'];
			}
		}

		foreach ($this->cols as $colKey =>& $colDef)
		{
			// setting empty conf with default
			if(!empty($colDef['title'])){
				$colDef['title'] = array_replace($this->defaultTitlesFieldsStyle, $colDef['title']);
			}
			else{
				$colDef['title'] = $this->defaultTitlesFieldsStyle;
			}

			// setting empty conf with default
			if(!empty($colDef['content'])){
				$colDef['content'] = array_replace($this->defaultContentsFieldsStyle, $colDef['content']);
			}
			else{
				$colDef['content'] = $this->defaultContentsFieldsStyle;
			}

			if($this->getColumnStatus($colKey))
			{
				// In case of flexible column
				if(empty($colDef['width'])){
					$colDef['width'] = abs(($arrayWidth - $totalDefinedColWidth)) / $countFlexCol;
				}

				// Set positions
				$lastX = $curX;
				$curX = $lastX - $colDef['width'];
				$colDef['xStartPos'] = $curX;
				$colDef['xEndPos']   = $lastX;
			}
		}
	}

	/**
	 *   	get column content width from column key
	 *
	 *   	@param	string			$colKey    		the column key
	 *      @return	float      width in mm
	 */
	function getColumnContentWidth($colKey)
	{
		$colDef = $this->cols[$colKey];
		return  $colDef['width'] - $colDef['content']['padding'][3] - $colDef['content']['padding'][1];
	}


	/**
	 *   	get column content X (abscissa) left position from column key
	 *
	 *   	@param	string    $colKey    		the column key
	 *      @return	float      X position in mm
	 */
	function getColumnContentXStart($colKey)
	{
		$colDef = $this->cols[$colKey];
		return  $colDef['xStartPos'] + $colDef['content']['padding'][3];
	}

	/**
	 *   	get column position rank from column key
	 *
	 *   	@param	string		$colKey    		the column key
	 *      @return	int         rank on success and -1 on error
	 */
	function getColumnRank($colKey)
	{
		if(!isset($this->cols[$colKey]['rank'])) return -1;
		return  $this->cols[$colKey]['rank'];
	}

	/**
	 *   	get column position rank from column key
	 *
	 *   	@param	string		$newColKey    	the new column key
	 *   	@param	array		$defArray    	a single column definition array
	 *   	@param	string		$targetCol    	target column used to place the new column beside
	 *   	@param	bool		$insertAfterTarget    	insert before or after target column ?
	 *      @return	int         new rank on success and -1 on error
	 */
	function insertNewColumnDef($newColKey, $defArray, $targetCol = false, $insertAfterTarget = false)
	{
		// prepare wanted rank
		$rank = -1;

		// try to get rank from target column
		if(!empty($targetCol)){
			$rank = $this->getColumnRank($targetCol);
			if($rank>=0 && $insertAfterTarget){ $rank++; }
		}

		// get rank from new column definition
		if($rank<0 && !empty($defArray['rank'])){
			$rank = $defArray['rank'];
		}

		// error: no rank
		if($rank<0){ return -1; }

		foreach ($this->cols as $colKey =>& $colDef)
		{
			if( $rank <= $colDef['rank'])
			{
				$colDef['rank'] = $colDef['rank'] + 1;
			}
		}

		$defArray['rank'] = $rank;
		$this->cols[$newColKey] = $defArray; // array_replace is used to preserve keys

		return $rank;
	}


	/**
	 *   	print standard column content
	 *
	 *   	@param	PDF		    $pdf    	pdf object
	 *   	@param	float		$curY    	curent Y position
	 *   	@param	string		$colKey    	the column key
	 *   	@param	string		$columnText   column text
	 *      @return	int         new rank on success and -1 on error
	 */
	function printStdColumnContent($pdf, &$curY, $colKey, $columnText = '')
	{
		global $hookmanager;

		$parameters=array(
			'curY' =>& $curY,
			'columnText' => $columnText,
			'colKey' => $colKey
		);
		$reshook=$hookmanager->executeHooks('printStdColumnContent',$parameters,$this);    // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error,$hookmanager->errors,'errors');
		if (!$reshook)
		{
			if(empty($columnText)) return;
			$pdf->SetXY($this->getColumnContentXStart($colKey),$curY); // Set curent position
			$colDef = $this->cols[$colKey];
			$pdf->MultiCell( $this->getColumnContentWidth($colKey),2, $columnText,'',$colDef['content']['align']);
		}

	}


	/**
	 *   	get column status from column key
	 *
	 *   	@param	string			$colKey    		the column key
	 *      @return	float      width in mm
	 */
	function getColumnStatus($colKey)
	{
		if( !empty($this->cols[$colKey]['status'])){
			return true;
		}
		else  return  false;
	}

	function pdfTabTitles(&$pdf, $tab_top, $tab_height, $outputlangs, $hidetop=0)
	{
		global $hookmanager;

		foreach ($this->cols as $colKey => $colDef)
		{

			$parameters=array(
				'colKey' => $colKey,
				'pdf' => $pdf,
				'outputlangs' => $outputlangs,
				'tab_top' => $tab_top,
				'tab_height' => $tab_height,
				'hidetop' => $hidetop
			);

			$reshook=$hookmanager->executeHooks('pdfTabTitles',$parameters,$this);    // Note that $object may have been modified by hook
			if ($reshook < 0)
			{
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			}
			elseif (empty($reshook))
			{

				if(!$this->getColumnStatus($colKey)) continue;

				// get title label
				$colDef['title']['label'] = !empty($colDef['title']['label'])?$colDef['title']['label']:$outputlangs->transnoentities($colDef['title']['textkey']);

				// Add column separator
				if(!empty($colDef['border-left'])){
					$pdf->line($colDef['xStartPos'], $tab_top, $colDef['xStartPos'], $tab_top + $tab_height);
				}

				if (empty($hidetop))
				{
					$pdf->SetXY($colDef['xStartPos'] + $colDef['title']['padding'][3], $tab_top + $colDef['title']['padding'][0] );

					$textWidth = $colDef['width'] - $colDef['title']['padding'][3] -$colDef['title']['padding'][1];
					$pdf->MultiCell($textWidth,2,$colDef['title']['label'],'',$colDef['title']['align']);

					$this->tabTitleHeight = max ($pdf->GetY()- $tab_top + $colDef['title']['padding'][2] , $this->tabTitleHeight );

				}

			}
		}


		return $this->tabTitleHeight;
	}

}
