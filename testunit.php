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

   print "Création d'un produit test...";
   $product = new Product($db);
   $product->ref='TESTUNITASSET';
   $product->label = $product->libelle = 'test unitaire asset';
   $id_product_test = $product->create($user);
   if($id_product_test > 0) {
       print $id_product_test."...ok<br/>";
   }
   else{
       var_dump($product->errors,$product->error);
       exit("Echec de création du produit test TESTUNITASSET");
   }
   
   
    print "Création d'un asset sans produit/type de produit définit...";
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
       print 'ok<br />';
   }
   
   print "Suppression de l'asset de test..."; 
   $asset->delete($PDOdb);
   if(!empty($PDOdb->error)) {
       exit("Erreur lors de la suppression");
   }
   else{
       print 'ok<br />';
   }
   
   print "Suppresion du produit test...";
   if($product->delete()>0) {
        print 'ok<br />';
   }
   else{
       var_dump($product->errors);
       exit("Impossible de supprimer le produit");
   }
   
   print "Tests terminés !";
