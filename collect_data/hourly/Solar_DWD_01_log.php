<?php
ini_set("display_errors", 1); 
error_reporting( E_ALL );

function getHumidity($T, $TD) {
  if (is_numeric($T) && is_numeric($TD)) {
    $T = $T - 273.15;
    $TD =$TD - 273.15;
    $RH=100*(exp((17.625*$TD)/(243.04+$TD)) / exp((17.625*$T)/(243.04+$T)));
  } else {
    $RH = '---';
  }
  return $RH;
}


function xml2array ( $xmlObject, $out = array () ) {
  foreach ( (array) $xmlObject as $index => $node )
  $out[$index] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;
  return $out;
}


function getParamArray($rootObj, $id) {
  foreach ($rootObj as $param) {
    if ((string) $param['elementName'] == $id) {
      $output = preg_replace('!\s+!', ';', (string) $param->value);
      $output = explode(';', $output);
      array_shift($output);
      return $output;
    }
  }
}



require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'credentials.php';


$alias = array(
//      'TN' => 'Tn',       // Minimum temperature - within the last 12 hours
//      'TX' => 'Tx',       // Maximum temperature - within the last 12 hours
      'TTT' => 'Temperature',      // Temperature 2m above surface
      'SunD1' => 'SS1',
      'Nh' => 'Bewoelkung_H',       // High cloud cover (>7 km)
      'Nm' => 'Bewoelkung_M',       // Midlevel cloud cover (2-7 km) (%)
      'Nl' => 'Bewoelkung_L',       // Low cloud cover (lower than 2 km) (%)
//      'RR3c' => 'RR%6',   // Total precipitation during the last hour (kg/m2),
//      'R130' => 'RR6',    // Probability of precipitation > 3.0 mm during the last hour
      'DD' => 'Wind_direction',       // 0°..360°, Wind direction
      'FF' => 'Windgeschw',       // Wind speed (m/s)
      'FX1' => 'Boeen',      // Maximum wind gust within the last hour (m/s)
//      'FXh25' => 'fx6',   // Probability of wind gusts >= 25kn within the last 12 hours (% 0..100)
//      'FXh40' => 'fx9',   // Probability of wind gusts >= 40kn within the last 12 hours
//      'FXh55' => 'fx11',  // Probability of wind gusts >= 55kn within the last 12 hours
      'PPPP' => 'Luftdruck',   // Surface pressure, reduced (Pa)
//      'N' => 'N',
      'Td' => 'Td',
//      'SS24'=> 'SS24',
	  'Rad1h'=> 'Rad1h',		// kJ/m2
    );
    $ids = array_keys($alias);
	
	
	
foreach ($DWD_station_IDs as $Stationkey=>$StationID){

	$file = 'https://opendata.dwd.de/weather/local_forecasts/mos/MOSMIX_L/single_stations/'.$StationID.'/kml/MOSMIX_L_LATEST_'.$StationID.'.kmz';
	//$newfile = 'tmp_name_file.zip';
	$newfile = tempnam(sys_get_temp_dir(), 'Tux');

		$zip = new ZipArchive();
	if (file_put_contents($newfile , file_get_contents($file))) {
		$res=$zip->open($newfile);
		if ($res === TRUE) {
			//echo "ok\n";
			//echo "NR-files ".$zip->numFiles."\n";
			$xml_string = $zip->getFromName($zip->getNameIndex($zip->numFiles-1));
			if ( $xml_string!= false ) {
					$xml_string = str_replace(
					  array("kml:", "dwd:"),
					  array("", ""),
					  $xml_string
					);
				$xml = simplexml_load_string($xml_string);
				//$xml = new SimpleXMLElement($xml_string);
			}
			$zip->close();
			unlink($newfile);
		} else {
			echo 'Fehler, Code:' . $res."\n";
			exit("cannot open <$file>\n");
		}
	} else {
		exit("cannot copy <$newfile>\n");
	}
	//echo "$xml_string\n";
	//print_r($xml);
	$timeSteps = xml2array($xml->Document->ExtendedData->ProductDefinition->ForecastTimeSteps->TimeStep);
	//$timeSteps = xml2array($xml->Document->ExtendedData->ProductDefinition->ForecastTimeSteps->TimeStep);

    $lines = array_fill(0, count($timeSteps), array());


    foreach ($timeSteps as $key => $value) {
        $date = new DateTime($value);
        //array_push($lines[$key], $date->format('U'));
        $lines[$key][]=$date->format('U');
        //array_push($lines[$key], $date->format('Y-m-d H:i:s'));
        //$lines[$key][]=$date->format('Y-m-d H:i:s');
    } // $timeSteps





    $fnode = $xml->Document->Placemark->ExtendedData->Forecast;
    foreach ($ids as $id) {
        $param = getParamArray($fnode, $id);
        if (count($param) === 0) {
          $param = array_fill(0, count($timeSteps), '---');
        }

        foreach ($param as $key => $value) {

            $v = $value;

            if (in_array($id, array('TN', 'TX', 'TTT', 'Td'))) {
                $v = $value - 273.15;
            }

            if ($id == 'PPPP') {
                $v = $value / 100;
            }

            if ($id == 'SunD1') {
                $v = $value;
            }

            /*if (in_array($id, array('N', 'Nh', 'Nm', 'Nl'))) {
                $v = round($value * 8 / 100);
            }*/

            if ($value == '-') {
                $v = '---';
            }

            //array_push($lines[$key], $v);
            $lines[$key][]=$v*1;
        }
    }// foreach $ids


    // calculate humidity
    $t = getParamArray($fnode, 'TTT');
    $d = getParamArray($fnode, 'Td');
    foreach ($t as $key => $value) {
        //array_push($lines[$key], getHumidity($value, $d[$key]));
        $lines[$key][]=getHumidity($value, $d[$key])*1;
    }
	$output[$Stationkey]=$lines;
}









$factor=count($DWD_station_IDs);




foreach ($output[0] as $key => $value){
	
	$valueset=$value;
	for ($i=1;$i<$factor;$i++){
		$valueset=array_map(function () {
			return array_sum(func_get_args());
		}, $valueset,$output[$i][$key]);
	}
	$valueset=array_map(
		function($val) use ($factor) { return round($val / $factor); }, 
		$valueset
	);
	
	if ($valueset[0]!=$value[0]){
		echo "timestamps are different";
	}
	
	
	$timestamp=$valueset[0];

	if (isset($sql_insert)){
		$sql_insert=$sql_insert.",\n(FROM_UNIXTIME(".$valueset[0].'),'.implode(',', $valueset).")";
	} else {
		$sql_insert="INSERT INTO $mysql_PV_data_tablename_DWD (TIMESTAMP,time_sec,".implode(',', $alias).",Humidity)
		VALUES (FROM_UNIXTIME($valueset[0]),".implode(',', $valueset).")";
	}

	
	
	
	//echo '"'.implode('","', $valueset).'",'."\r\n";
	
}



try {
  $pdo = new PDO($mysql_PV_data_dsn, $mysql_PV_data_username, $mysql_PV_data_pw, $mysql_PV_data_options);
} catch (Exception $e) {
  error_log($e->getMessage());
  exit('Mysql-Verbindung abgebrochen: '.$e->getMessage()); //something a user can understand
}



//delete old values,
$sql_delete="DELETE FROM $mysql_PV_data_tablename_DWD WHERE time_sec >=". $output[0][0][0];
//echo $sql_delete;
try {
	$pdo->exec($sql_delete);
	} 
catch (PDOException $e) { 
	die("ERROR: Could not able to execute\n $sql_delete. \n"
			.$e->getMessage()); 
}

//insert/update values
//echo $sql_insert;
try {
	$pdo->exec($sql_insert.";");
	} 
catch (PDOException $e) { 
	die("ERROR: Could not able to execute\n $sql_insert. \n"
			.$e->getMessage()); 
}
unset($pdo); 

?>