<?php

    require('config.php');
    
    if(empty($conf->global->USE_LOT_IN_OF)) {
        header('location:'.dol_buildpath('/asset/liste_of.php',1));
    }
    else{
        header('location:'.dol_buildpath('/asset/liste.php',1));
    }
