# Change Log
All notable changes to this project will be documented in this file.

## UNRELEASED


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

- FIX : Default extrafield reflinenumber visibility  *05/07/2021* - 2.0.2
- FIX : Redirect error with asset ATM select  *05/07/2021* - 2.0.1
- NEW : Compatibility with Workstation ATM for Dolibarr v14 *28/06/2021* - 2.0.0  
  **requires WorkstationAtm 2.0**

## Version 1.18

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
