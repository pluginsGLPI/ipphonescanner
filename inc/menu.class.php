<?php
/*
 LICENSE

 This file is part of the ip phone sca plugin.

 adl plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 adl plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; along with adl. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   adl
 @author    TECLIB' : Walid Nouh
 @copyright Copyright (c) 2012 TECLIB'
 @license   GPLv2+
            http://www.gnu.org/licenses/gpl.txt
 @link      http://www.teclib.com
 @link      http://www.glpi-project.org/
 @since     2012
 ---------------------------------------------------------------------- */


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 *  Class to manage equipment list
 *  @service Bollore
**/
class PluginIpphonescannerMenu extends CommonGLPI {

   public static function getTypeName($nb = 0) {
      return 'IP Phone Scanner';
   }

   static function getMenuContent() {
      global $CFG_GLPI;

      $menu['page']  = "/plugins/ipphonescanner/index.php";
      $menu['title'] = 'IP Phone Scanner';
      return $menu;
   }

   public static function canCreate() {
      return false;
   }
}
