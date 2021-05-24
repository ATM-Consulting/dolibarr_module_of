# Change Log
All notable changes to this project will be documented in this file.

## UNRELEASED


## Version 1.18

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

- FIX: missing en_US translations - 1.15.5 - *2021-04-21*
- FIX : translation QtyToMake - *2021-04-02*
- NEW : Option to revert of task creation hierarchy order - *2021-02-09*
- FIX : OF card project display - *2021-02-18*

## Version 1.14

- FIX missing Hook context
- FIX reflinenumber not displayed on expeditioncard
