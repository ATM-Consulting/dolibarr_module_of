-- ===================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- $Id: llx_asset.sql,v 1.0 2011/11/09 15:04:57 atm-maxime Exp $
-- ===================================================================

CREATE TABLE  `dolibarr`.`llx_asset_link` (
`rowid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`date_cre` DATETIME NOT NULL,
`date_maj` DATETIME NOT NULL,
`fk_asset` INT NOT NULL ,
`fk_document` INT NOT NULL ,
`type_document` VARCHAR( 30 ) NOT NULL
) ENGINE = INNODB ;
