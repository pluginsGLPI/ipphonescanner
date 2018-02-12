<?php

include_once('../../inc/includes.php');

$error = NULL;
$config = array();

$ini_file = implode(DIRECTORY_SEPARATOR, array(
   dirname($_SERVER['SCRIPT_FILENAME']),
   "conf.ini"
));

function load_config() {
   global $error, $ini_file, $config;

   //Load file if it exists
   if(file_exists($ini_file)) {
      $config = parse_ini_file($ini_file);
   } else {
      $error = "ERREUR : Le fichier conf.ini n'existe pas";
      return FALSE;
   }

   if (!Session::haveRight('phone', UPDATE)) {
      return FALSE;
   }


   //Validation of range value
   //TODO: add more checks like type checking
   if ( ! (isset($config['range_start']) and isset($config['range_end']))
   ) {
      $error = "ERREUR : Aucune plage de numéros n'a été configuré dans le fichier conf.ini!";
      return FALSE;
   }

   //Everything goes well!!
   return TRUE;
}

function display_provisioning($provisioning) {
   //Display phone numbers
   global $CFG_GLPI;

   echo "<div style='margin:0 auto;'>\n";
   echo "<table style='border:black solid 1px; width:600px'>\n";


   foreach( $provisioning as $number => $infos ) {

      echo "  <tr style>\n";

      //display phone number
      echo "    <td width='20px'>\n";
      echo $number . "\n";
      echo "    </td>\n";


      if (isset($infos['is_active']) && $infos['is_active']==1 && $infos["users_id"] && $infos["users_id"] > 0) {
          $color  = '#FAA'; //Red
          $status = "actuellement utilisé" . "\n";
      } else if (isset($infos["users_id"]) && $infos["users_id"] > 0) {
          $color  = '#AAA';
          $status = "anciennement utilisé" . "\n";
      } else if (isset($infos["phones_id"]) && $infos["phones_id"] > 0) {
         $color  = '#AAF'; //Green
         $status = "associé à un téléphone". "\n";
      } else if ($infos['is_active']==0) {
         $color  = '#AFA'; //Green
         $status = "libre". "\n";
      } else {
         $color  = '#FFF';
         $status = '?';
      }

      echo "    <td width='10px' style='font-weight:bold; background-color:".$color."'>\n";
      echo $status."\n";
      echo "    </td>\n";

      echo "    <td width='100px'>\n";
      //display login if any
      if (isset($infos['name'])) {
         printf('<a href="%s">%s %s (%s)</a>',
            $CFG_GLPI["root_doc"]."/front/user.form.php?id=".$infos["users_id"],
            $infos['firstname'],
            $infos['realname'],
            $infos['name']);
      }
      echo "    </td>\n";
      echo "    <td width='20px'>\n";
      if (isset($infos['phones_id'])) {
         printf('<a href="%s">%s</a>',
            $CFG_GLPI["root_doc"]."/front/phone.form.php?id=".$infos["phones_id"],
            $infos['phone_name']?$infos['phone_name']:"sans nom");
      }

      echo "    </td>\n";
      echo "  </tr>\n";
      echo "    </td>\n";

   }

   echo "</table>\n";
   echo "</div>\n";

}

function get_provisioning($config) {
   global $DB;
   //Prepare Phonenumbers list
   $phonenumbers_status = array();
   for($number=$config['range_start']; $number<= $config['range_end']; $number++) {
      $phonenumbers_status['0'.$number] = array('is_active' => 0); //default status 0 = Available
   }

   //Get GLPI users list
   $query = "SELECT `id` as users_id, `name`, `phone`, `is_active` , `firstname`, `realname` FROM `glpi_users`";
   $result = $DB->query($query);
   while ( $data = $DB->fetch_assoc($result) ) {
      $phonenumber = $data['phone'];
      if ( isset($phonenumbers_status[$phonenumber]) ) {
         $phonenumbers_status[$phonenumber] = $data;
      }
   }
   //Get phone users list
   $query = "SELECT `id` AS `phones_id`, `name` AS `phone_name`, `number_line` AS `phonenumber` FROM `glpi_phones` WHERE `number_line` IS NOT NULL;";
   $result = $DB->query($query);
   while ( $data = $DB->fetch_assoc($result) ) {

        $stack = array();
        if (preg_match('/(\d{10})$/', $data['phonenumber'], $stack)) {
             $phonenumber = $stack[1];
             $phonenumbers_status[$phonenumber]['phone_name'] = $data['phone_name'];
             $phonenumbers_status[$phonenumber]['phones_id'] = $data['phones_id'];
	}
   }

   return $phonenumbers_status;
}

Html::header('IP Phone Scanner', $_SERVER["PHP_SELF"], "plugins",
             "ipphonescanner");

if (load_config()) {
   $provisioning = get_provisioning($config);
   display_provisioning($provisioning);
} else {
   echo "<div style='width:100%' class='error'>" . $error . "</div>";
}

Html::footer();
?>
