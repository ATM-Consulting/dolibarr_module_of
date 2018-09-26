<?php

    require('config.php');
    
    if(empty($conf->global->USE_LOT_IN_OF)) {
    	header('location:'.dol_buildpath('/' . ATM_ASSET_NAME . '/liste_of.php',1));
    }
    else{
    	header('location:'.dol_buildpath('/' . ATM_ASSET_NAME . '/liste.php',1));
    }
