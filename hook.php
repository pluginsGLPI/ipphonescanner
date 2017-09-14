<?php
/*
 -------------------------------------------------------------------------
 IpPhoneScanner plugin for GLPI
 Copyright (C) 2017 by the IpPhoneScanner Development Team.

 https://github.com/pluginsGLPI/ipphonescanner
 -------------------------------------------------------------------------

 LICENSE

 This file is part of IpPhoneScanner.

 IpPhoneScanner is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 IpPhoneScanner is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with IpPhoneScanner. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_ipphonescanner_install() {
   $migration = new Migration(PLUGIN_IPPHONESCANNER_VERSION);
   $classes = ['PluginIpPhoneScannerScanner', 'PluginIpPhoneScannerProcess', 'PluginIpPhoneScannerInventoryNumber'];

   foreach ($classes as $class) {
      if ($plug = isPluginItemType($class)) {
         $plugname = strtolower($plug['plugin']);
         $dir      = GLPI_ROOT . "/plugins/$plugname/inc/";
         $item     = strtolower($plug['class']);
         if (file_exists("$dir$item.class.php")) {
            include_once ("$dir$item.class.php");
            call_user_func([$class, 'install'], $migration);
         }
      }
   }
   return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_ipphonescanner_uninstall() {
   $migration = new Migration(PLUGIN_IPPHONESCANNER_VERSION);
   $classes = ['PluginIpPhoneScannerScanner', 'PluginIpPhoneScannerProcess', 'PluginIpPhoneScannerInventoryNumber'];
   foreach ($classes as $class) {
      call_user_func([$class,'uninstall'], $migration);
   }

   return true;
}
