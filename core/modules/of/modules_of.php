<?php
/**
 * Copyright (C) 2019 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';

class ModelePDFOf extends CommonDocGenerator
{
    /**
     * Return list of active generation modules
     *
     * @param DoliDB $db handler
     * @param string $maxfilenamelength length of value to show
     * @return array of templates
     */
    static function liste_modeles($db, $maxfilenamelength = 0)
    {
		global $conf;

		$type = 'of';

		$list = array(
            'templateOF.odt' => 'Standard'
        );

        foreach (glob(DOL_DATA_ROOT.'/of/template/*.odt') as $filepath)
        {
            $file = str_replace(DOL_DATA_ROOT.'/of/template/', '', $filepath);
            if ($file !== 'templateOF.odt') $list[$file] = $file;
        }

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$listStd = getListOfModels($db, $type, $maxfilenamelength);
		if(!empty($listStd) && is_array($listStd)){
			foreach ($listStd as $key => $val ){
				$list[$key] = $val;
			}
		}

        return $list;
    }
}

class ModeleOf extends ModelePDFOf {}
