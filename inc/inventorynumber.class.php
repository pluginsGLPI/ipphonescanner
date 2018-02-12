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
* InventoryNumber class
* @since 1.0
*/

class PluginIpphonescannerInventoryNumber {
    private $bySerialArray           = false;
    private $byMACArray              = false;

    function __construct() {
      $this->bySerialArray = array();
      $this->byMACArray    = array();

      $file = fopen("../plugins/ipphonescanner/data/mapping.csv","r");

      while (($data = fgetcsv($file)) !== FALSE) {
        $this->bySerialArray[$data[1]] = $data[0];
        $this->byMACArray[$data[2]]    = $data[0];
      }

      fclose($file);
    }

    public function byMac($iKey = 0){
      return (isset($this->byMACArray[$iKey])) ? $this->byMACArray[$iKey] : '';
    }

    public function bySerial($iKey = 0){
      return (isset($this->bySerialArray[$iKey])) ? $this->bySerialArray[$iKey] : '';
    }
}