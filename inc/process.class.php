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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
* Class to manage the ip range scan and synchronize phones
* @since 1.0
*/
class PluginIpphonescannerProcess {

   /**
    * Display name of itemtype
    *
    * @return value name of this itemtype
    **/
   public static function getTypeName($nb = 0) {

      return __("IP Phone Scan", "ipphonescanner");
   }

  /**
   * Cron task to synchro Rudec infos
   *
   * @since 1.0
   * @param CronTask $task the crontask object
   */
   public static function croniPPhoneScanning($task) {
      global $DB;

      $scanner = new PluginIpphonescannerScanner(255, new PluginIpphonescannerInventoryNumber());
      $scanner->addNetwork(array('192.168.47.0/24'));
      $scanner->addNetwork(array('172.20.0.0/16'));
      $scanner->feedThePool();

      return true;
   }

   static function cronInfo($name) {

       switch ($name) {
          case 'iPPhoneScanning' :
             return ['description' => __('IP Phones Scanning', 'PluginIpphonescanner')];
       }
       return array();
    }

   /**
   * Install process
   *
   * @since 1.0
   * @param Migration $migration the migration object
   */
   public static function install(Migration $migration) {
      Crontask::Register(__CLASS__, 'iPPhoneScanning', DAY_TIMESTAMP,
                         ['param' => 24,
                          'mode'  => CronTask::MODE_EXTERNAL]);
   }

   /**
    * Plugin uninstall process
    *
    * @return boolean
    */
   public static function uninstall(Migration $migration) {
      return true;
   }

}
