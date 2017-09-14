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
* Scanner class
* @since 1.0
*/

class PluginIpPhoneScannerScanner {
    private $stack            = false;
    private $poolMaxSize      = false;
    private $poolCur          = false;
    private $inventorynumbers = false;
    private $client           = false;

    function __construct($poolmaxsize, $inventorynumbers) {
      $this->stack            = array();
      $this->poolMaxSize      = $poolmaxsize;
      $this->poolCur          = 0;
      $this->inventorynumbers = $inventorynumbers;
      $this->client           = new \GuzzleHttp\Client();
    }

    protected function updateDevice($params = array()) {
      $phone = array();

      $phone['user']         = $params['uid'];
      $phone['name']         = $params['otherserial'];
      $phone['users_id']     = $params['users_id'];
      $phone['manufacturer'] = 'Cisco';
      $phone['model']        = $params['phonemodel'];
      $phone['serial']       = $params['serial'];
      $phone['number_line']  = $params['phonenumber'];
      $phone['mac']          = chunk_split($params['mac'], 2, ':');
      $phone['ip']           = $params['ip'];

      return $this->updateOrAddPhone($phone);
    }

    protected function showInfo($sResult, $sHost) {
      $aDevices = simplexml_load_file($sResult);

      foreach ($aDevices as $device):
        $params = array();
        $params['mac'] = $device->MACAddress;
        $params['name'] = $device->HostName;
        $params['phonenumber'] = $device->phoneDN;
        $params['serial'] = $device->serialNumber;
        $params['phonemodel'] = $device->modelNumber;
        $params['description'] = $device->udi;
        $params['time'] = $device->time;
        $params['date'] = $device->date;
      endforeach;

      $params['otherserial'] = $this->inventorynumbers->byMAC($params['mac']);
      $params['ip'] = $sHost;

      $params['users_id'] = $this->findUserByPhoneNumber($params['phonenumber']);

      if (!$params['otherserial']) {
        $params['otherserial'] = $this->inventorynumbers->bySerial($params['serial']);
      }

      if (strlen($params['serial']) < 3) {
        $params['serial'] = $params['mac'];
      }

      $this->updateDevice($params);
      $this->poolCur -= 1;
      
      return $this->feedThePool();
    }

  /**
   * Add current host in pool
   *
   * @since 1.0
   * @param string $host to add in pool
   */
    protected function addHost($host) {
      $this->stack[] = $host;
    }

  /**
   * List each ip adress in ip network
   *
   * @since 1.0
   * @param string $range ip range
   * @return array ip list from ip range
   */
    private function ipListFromRange($range){
        $parts    = explode('/',$range);
        $exponent = 32-$parts[1].'-';
        $count    = pow(2,$exponent);
        $start    = ip2long($parts[0]);
        $end      = $start+$count;

        return array_map('long2ip', range($start, $end) );
    }

  /**
   * Add each ip in range in current pool
   *
   * @since 1.0
   * @param string $network ip network
   */
    protected function addNetwork($network) {
      foreach ($this->ipListFromRange($network) as $host) {
        $this->addHost($host);
      }
    }

  /**
   * Scan iprange and request xml urls
   *
   * @since 1.0
   */
    protected function feedThePool() {

      while (count($this->stack) > 0 AND ($this->poolCur < $this->poolMaxSize)) {
        $host = array_pop($this->stack);

        $url  = "http://$host/DeviceInformationX";
        $res = $this->client->request('GET', $url);
        while ($res->getStatusCode() == '200') {
          $this->showInfo($res->getBody(), $host);
        }

        $url  = "http://$host/CGI/Java/Serviceability?adapterX=device.statistics.device";
        $res = $this->client->request('GET', $url);
        while ($res->getStatusCode() == '200') {
          $this->showInfo($res->getBody(), $host);
        }

        $this->poolCur += 2;
      }
    }

   /**
   * Add or update phone
   *
   * @since 1.0
   * @param array $params fields of the phone to be retrieved or added
   * @return phone object updated or added, false if not
   */
   public function updateOrAddPhone($params) {
      global $DB;

      $table  = getTableForItemType('Phone');
      $params = Toolbox::addslashes_deep($params);
      $query  = "SELECT `id`
                 FROM `glpi_phones`
                 WHERE
                    `name`= '".$params['name']."'";

      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         $params['id'] = $DB->result($result, 0, 'id');
         $phone = $this->update($params);
      } else {
         $phone = $this->add($params);
      }

      return $phone;
   }

  /**
   * Try to get a user by providing a phonenumber
   *
   * @since 1.0
   * @param string $phonenumber the user's phonenumber
   * @return the user ID if found, otherwise 0
   */
   private function findUserByPhoneNumber($phonenumber) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_users`
                WHERE
                   LOWER(`glpi_users`.`phone`)='".strtolower($phonenumber)."'";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         return $DB->result($result, 0, 'id');
      } else {
         return 0;
      }
   }
}