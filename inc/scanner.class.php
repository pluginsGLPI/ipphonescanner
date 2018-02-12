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

use Nmap\Nmap;

/**
* Scanner class
* @since 1.0
*/

class PluginIpphonescannerScanner {
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

      //$phone['user']         = $params['uid'];
      //$phone['name']         = $params['otherserial'];
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
      $device = simplexml_load_string($sResult);

      $params = array();
      $params['mac'] = (string) $device->MACAddress;
      $params['name'] = (string) $device->HostName;
      $params['phonenumber'] = (string) $device->phoneDN;
      $params['serial'] = (string) $device->serialNumber;
      $params['phonemodel'] = (string) $device->modelNumber;
      $params['description'] = (string) $device->udi;
      $params['time'] = (string) $device->time;
      $params['date'] = (string) $device->date;
      
      //$aBadNumbers = array(173054800, 173055170, 173055140, 173055015, 173055981, 173055197, 173054994, 173054703, 173054719, 173054863, 173054563, 173055054, 173054680, 173054578, 80006, 173054701, 80007, 80005, 173055580, 173054988, 173056540, 90024, 173054644, 173054582, 173055031, 173054625, 173055247, 173054579, 173055276, 173055122, 173055226, 173054638, 173054809, 90009, 173055285, 90008, 173056384, 173054673, 173054624, 173056263, 173056265, 173054555, 173055019, 173054716, 173055139, 173055024, 173054543, 76005, 173055110, 173054683, 173054606, 173055182, 76020, 173055117, 173055256, 173055248, 173055246, 173055245, 173055242, 173056245, 90032, 173054573, 173055683, 173054670, 90033, 173054924, 173055243, 173054804, 173054866, 173055574, 173055575, 173055483, 72098, 173054527, 173056329, 72025, 173054728, 173055614, 173055512, 173055591, 173055590, 173055478, 173055583, 173055595, 173055586, 173055589, 173055600, 173054611, 173055585, 75009, 173055584, 75042, 173055588, 173055648, 173055587, 173055582, 173055646, 173055651, 173055653, 173055650, 173055658, 173055581, 173055649, 173055647, 173055645, 173054972, 173055654, 173054684, 173055693, 173055594, 173055596, 173055598, 173055295, 173055692, 173055114, 173055578, 173055481, 173055472, 173055485, 173055476, 173055471, 173055474, 173055484, 173055475, 173055482, 173055479, 173055477, 173055480, 173055483, 173054712, 173055473, 173054785);

      //if (in_array($device->phoneDN, $aBadNumbers)) {
        //echo $sHost.PHP_EOL;
      //}
      
      $params['otherserial'] = $this->inventorynumbers->byMAC( (string) $params['mac'] );
      $params['ip'] = $sHost;

      $params['users_id'] = $this->findUserByPhoneNumber($params['phonenumber']);

      if (!$params['otherserial']) {
        $params['otherserial'] = $this->inventorynumbers->bySerial( (string) $params['serial'] );
      }

      if (strlen($params['serial']) < 3) {
        $params['serial'] = $params['mac'];
      }

      $this->updateDevice($params);
      $this->poolCur = $this->poolCur - 1;
      $this->feedThePool();

      return true;
    }

  /**
   * Add current host in pool
   *
   * @since 1.0
   * @param string $host to add in pool
   */
    public function addHost($host) {
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
    public function addNetwork($network, $ports = array('80')) {
      //$nmap = new Nmap();
      
	$hosts = Nmap::create()->setTimeout(3600)->scan($network, $ports);

      foreach ($hosts as $host) {
        if ($host->GetState() == 'up') {
         $aAdresses = $host->getIpv4Addresses();
         $currHost = array_pop($aAdresses);
         $this->addHost($currHost->getAddress());
        } 
      }
    }

  /**
   * Scan iprange and request xml urls
   *
   * @since 1.0
   */
    public function feedThePool() {

      echo 'Taille de la pile : '.count($this->stack).' | Position actuelle : '.$this->poolCur.'<br/>';

      echo date('Y-m-d H:i:s').'<br/>';

      while (count($this->stack) > 0) {
        $host = array_pop($this->stack);
        $bError = false;

        echo 'Trying to update '.$host.'<br/>';

        try {
          $url = "http://$host/DeviceInformationX";
          $res = $this->client->request('GET', $url, ['connect_timeout' => 1]);
          while ($res->getStatusCode() == '200') {
            $this->showInfo($res->getBody(), $host);

            try {
              $url = "http://$host/CGI/Java/Serviceability?adapterX=device.statistics.device";
              $res = $this->client->request('GET', $url, ['connect_timeout' => 1]);
              while ($res->getStatusCode() == '200') {
                $this->showInfo($res->getBody(), $host);
              }
            } catch (GuzzleHttp\Exception\ConnectException $e) {
               $bError = true;
            } catch (GuzzleHttp\Exception\ClientException $e) {
               $bError = true;
            } catch (GuzzleHttp\Exception\RequestException $e) {
               $bError = true;
            }
          }
        } catch (GuzzleHttp\Exception\ConnectException $e) {
            $bError = true;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $bError = true;
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $bError = true;
        }

        $this->poolCur = $this->poolCur + 2;

        if ($bError) {
          $this->poolCur = $this->poolCur - 1;
          echo 'Error while trying to update '.$host.'. New position : '.$this->poolCur.'<br/>';
          $this->feedThePool();
        }
      }

      exit;

      if (count($this->stack) == 0 || $this->poolCur < 0) return true;
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
      $phone  = new Phone();
      $params = Toolbox::addslashes_deep($params);
      $query  = "SELECT `id`
                 FROM `glpi_phones`
                 WHERE
                    `serial`= '".$params['serial']."'";

      $result = $DB->query($query);

      echo 'Nombre de ligne '.$DB->numrows($result);

      if ($DB->numrows($result) > 0) {
         $params['id'] = $DB->result($result, 0, 'id');
         $phone = $phone->update($params);
      } else {
         $phone = $phone->add($params);
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
