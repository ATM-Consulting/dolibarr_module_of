<?php

    /*
     * Script de test du module
     * 
     */
   
   require('config.php');
   
   dol_include_once('/asset/class/asset.class.php');
   dol_include_once('/asset/class/ordre_fabrication_asset.class.php');
   dol_include_once('/product/class/product.class.php');
   
  /* ini_set('display_errors', 1);
   error_reporting(E_ALL);
   */
   $PDOdb = new TPDOdb;

   print _num()."Création d'un produit test...";
   $product = new Product($db);
   $product->ref='TESTUNITASSET';
   $product->label = $product->libelle = 'test unitaire asset';
   $id_product_test = $product->create($user);
   if($id_product_test > 0) {
       print $id_product_test."...";
       _ok();
   }
   else{
       
       
       if($product->error == 'ErrorProductAlreadyExists') {
           $product->fetch(0,'TESTUNITASSET');
           _ok();
       }
       else{
            exit("Echec de création du produit test TESTUNITASSET");    
       }
       
   }
   
   print _num()."Création d'un type d'asset...";
   $typeAsset = new TAsset_type;
   $typeAsset->libelle='TEST ASSET TYPE';
   $id_asset_type_test = $typeAsset->save($PDOdb);
   if($id_asset_type_test>0) {
       print $id_asset_type_test.'...';
       
       if($typeAsset->code!='testassettype') {
            exit($typeAsset->code.' : Mauvais code');
       }
       else {
          _ok();   
       }
   }
   else{
       exit("Erreur");
   }
   
  
   
   
   print _num()."Création d'un asset sans produit/type de produit définit...";
   $asset=new TAsset;
   $id_asset_test = $asset->save($PDOdb);
   
   if($id_asset_test<=0) {
       exit("Erreur lors de la création de l'équipement");
   }
   else {
       print $id_asset_test.'...';
   }
   
   
   if($asset->serial_number!='') {
       exit ('pas de type défini sur le produit, anormal si la réf sort à non vide');
   }
   else{
      _ok();
   }
   
   print _num()."Suppression de l'asset de test..."; 
   $asset->delete($PDOdb);
   if(!empty($PDOdb->error)) {
       exit("Erreur lors de la suppression ".$PDOdb->error);
   }
   else{
       _ok();
   }
   
   print _num()."Suppression du type asset  de test..."; 
   $typeAsset->delete($PDOdb);
   if(!empty($PDOdb->error)) {
       exit("Erreur lors de la suppression ".$PDOdb->error);
   }
   else{
       _ok();
   }
      
   
   
   
   print _num()."Suppresion du produit test...";
   if($product->delete()>0) {
        _ok();
   }
   else{
       var_dump($product->errors);
       exit("Impossible de supprimer le produit");
   }
   
   print "Tests terminés !";
   
  function _num() {
      global $num_test_unit;
      
      if(empty($num_test_unit)) $num_test_unit= 1;
      
      $r =  $num_test_unit.'. ';
      
      $num_test_unit++;
      
      return $r;
      
  }

  function _ok() {
      print 'ok<br />';
  }
