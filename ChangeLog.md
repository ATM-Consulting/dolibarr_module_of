# Change Log
All notable changes to this project will be documented in this file.

## UNRELEASED

- FIX : wrong parameter usage of mb_strrpos in of.lib.php

## Version 2.13

- FIX : Fatal sur page d'admin des modèles de PDF provoquée par un ancien exemple de modèle obsolète. - *30/03/2023* - 2.13.10
- FIX : function getListChildrenOf doesn't take care of last child OF - *28/03/2023* - 2.13.9
- FIX : Compatibilité v17 > extrafields attribute - *31/01/2023* - 2.13.8
- FIX : Boucle infinie lors de l'enregistrement d'un OF et de la présence d'un ou plusieurs produits non conformes - Déplacement de code *08/11/2022* - 2.13.7
- FIX : Compat V16 *10/06/2022* - 2.13.6
- FIX : Lors de la mise à jour des quantités via l'engrenage, la quantité était constamment remise à 0 (oui oui je fix la même chose qu'en dessous) *31/03/2022* - 2.13.5
- FIX : Lors de la mise à jour des quantités via l'engrenage, la quantités était constamment remise à 0 *10/02/2022* - 2.13.4
- FIX : Ajout du traitement de la conf "Prix d'achat/revient suggéré par défaut" du module Nomenclature *04/02/2022* - 2.13.3
- FIX : conf to not update pmp *26/01/2022* - 2.13.2
- FIX : The click on the gear with a ToMake qty of zero now decreases the quantities of the needed products *05/01/2022* - 2.13.1
- NEW : Configuration permettant de tenir compte des ofs brouillon dans le stock théorique + quelques fix liés au stock théorique *17/12/2021* - 2.13.0


## Version 2.12
- FIX : lors de la création d'OF enfant, si l'OF parent a une date de besoin, alors les OF enfants reprennent cette même date de besoin *07/02/2023* - 2.12.6
- FIX : lors de la création automatique d'un OF à la validation d'une commande, la date de besoin doit être égale à la date de livraison si renseignée (même comportement que l'action manuelle) *27/01/2023* - 2.12.5
- FIX : Compatibilité V15 - Onglet fichiers joints *17/12/2021* - 2.12.4
- FIX : Ajout du choix d'entrepot vide DA021060 + Ajout  de la préselection en fonction de l'entrepot par  défaut produit *02/12/2021* - 2.12.3
- FIX : Ne  pas créer les lignes à 0 de qté *02/12/2021* - 2.12.2
- FIX : Button cancel sur stock transfert non fonctionnel depuis OF *30/11/2021* - 2.12.1
- NEW : Ordre des composants dans OF en fonction de la ref produit + Affichage référence lors de transfert de stock *30/11/2021* - 2.12.0

## Version 2.11
NEW : Gestion du stock théorique *21/11/2021* - 2.11.0

## Version 2.10

- FIX lot_number targeting for lignesToMake ODT *21/11/2021* - 2.10.2
- FIX lot_number targeting value for ODT *16/11/2021* - 2.10.1
- NEW Refonte des listes OFs sur le modèle standard *14/10/2021* - 2.10  
 Refonte des listes OFs (principale, depuis fiche produit onglet OF, depuis fiche commande onglet OF) sur le modèle standard.  
 Ajout de filtres sur le client, le produit et la commande client.  
 Ajout de la possibilité de rechercher par le numéro, le client et le produit dans la recherche globale.

## Version 2.9

- NEW Conf pour regrouper les lignes *13/10/2021* - 2.9

## Version 2.8

- NEW Gestion des calculs auto pour chaque ligne  *11/10/2021* - 2.8

## Version 2.7

- NEW massaction edition des entrepôts de tous les composants d'un of  *05/10/2021* - 2.7

## Version 2.6

- NEW ajout d'un lien de l'OF lié au transfert de stock *29/09/2021* - 2.6

## Version 2.5

- NEW Num lot dans multiselect equipement + suppression colonne lot dans consommé  *30/09/2021* - 2.5

## Version 2.4

- FIX: Fatal: TObjetStd not found *08/10/2021* - 2.4.1
- NEW Sélection des équipements pour les composants d'un OF : mise en place d'un multisect  *08/09/2021* - 2.4
- NEW Bouton Transfert de stock depuis un OF *16/09/2021* - 2.3

## Version 2.2 - *14/09/2021*
- FIX : includes utilisant `workstation` au lieu de `workstationatm` - *2021-09-23* - 2.2.2
- FIX : Ajout du numéro de numéro de lot correspondant au numéro de série dans la colonne 'Lot' des produits nécessaires à la fabrication *14/09/2021* - 2.2.1
- NEW Gestion des calculs automatiques conformes/non conformes *24/08/2021* - 2.2.0
- NEW Gestion des catégories produits *24/08/2021* - 2.1

## Version 2.0 - *08/07/2021*

**IMPORTANT : Requires WorkstationAtm 2.0**

- FIX : Use subquery instead of left join to avoid error on SUM(qty) *17/02/2023* - 2.1.2
- FIX : $langs->transnoentities() instead of $langs->trans() on all linked files of OF + product linked files are missing *09/12/2022* - 2.1.1
- FIX : $langs->transnoentities() instead of $langs->trans() on linked product files of OF + additional description is hidden by default *25/11/2022* - 2.1.0
- FIX : Default extrafield reflinenumber visibility  *05/07/2021* - 2.0.2
- FIX : Redirect error with asset ATM select  *05/07/2021* - 2.0.1
- NEW : Compatibility with Workstation ATM for Dolibarr v14 *28/06/2021* - 2.0.0  
  **requires WorkstationAtm 2.0**

## Version 1.18

- FIX : Missing rowid on create of product line *2021-12-08* - 1.18.4
- FIX : Fix add hidden conf OF_FORCE_GENERATE_PDF_ON_VALID for generating document on validate *2021-12-03* - 1.18.3
- FIX : Fix missing common behavior generating document on validate *2021-11-17* - 1.18.2 
- FIX : Dolibarr v13+v14 compatibility (NOTOKENRENEWAL + NOCSRFCHECK + GETPOST + module descriptor)  
       Note: some compatibility issues were not checked for (boxes, missing CSRF token in link URLs, triggers)
       - *2021-06-30* - 1.18.1
- NEW : Option to add RefLineNumber before line "desc" col in PDF and card *2021-05-20* - 1.18.0
- NEW : Add Standard Dolibarr Documents models support *2021-05-19* - 1.17.0

- FIX : Supplier order reception status applied for OF
    
    Anomalie constatée si la configuration OF suivante est activée : "L'ordre de fabrication lié manuellement à une commande fournisseur suit son statut".
    Anomalie qui apparaît lorsque l'on choisit un fournisseur pour un produit à créer d'un OF.
  
    Comportement constaté :
    La réception complète de la commande fournisseur fait passer l'OF à son statut suivant uniquement. Cela fonctionne donc correctement si l'OF est au statut "Prod en cours" à ce moment là, il passe bien au statut "Terminé". Par contre si il est "Validé" il passe en "Production en cours" et ne se clôture pas. Cela amène ensuite l'incrémentation du stock en double (lors de la réception commande fournisseur et lors de la clôture de l'OF).
    Cette anomalie a été évoqué dans le ticket 10982

    Nouveau comportement :
    Le statut de l'OF passe au statut "Terminé" lors de la réception complète de la commande fournisseur associée et cela quelque soit le statut de l'OF à ce moment là, sauf si l'OF possède d'autres produits à créer qui ne dépendent pas d'une commande fournisseur.

- FIX : Not loaded TBOdb

## Version 1.15

- FIX : missing en_US translations - 1.15.5 - *2021-04-21*
- FIX : translation QtyToMake - *2021-04-02*
- NEW : Option to revert of task creation hierarchy order - *2021-02-09*
- FIX : OF card project display - *2021-02-18*

## Version 1.14

- FIX : missing Hook context
- FIX : reflinenumber not displayed on expeditioncard
