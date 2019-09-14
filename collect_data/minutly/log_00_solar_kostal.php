<?php
// Ausleseskript Wechselrichter Kostal Piko ab Firmware v05.31 (12.10.2015)
//Kommunikation

function get_value($dataObject,$ID) {

	//print_r($dataObject);
	//echo "\n$ID\n";
	$key=array_search($ID,array_column($dataObject,'dxsId'));
	return $dataObject[$key]['value'];
}

error_reporting(E_ERROR | E_DEPRECATED | E_USER_DEPRECATED | E_PARSE);
ini_set("display_errors", 1); 

require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'credentials.php';


$dataValues=array();
//Leistungswerte
$Entries1=array('BatVoltage' => '33556226',
'BatCurrent' => '33556238',
'BatCurrentDir' => '33556230',
'ChargeCycles' => '33556228',
'BatTemperature' => '33556227',
'BatStateOfCharge' => '33556229',
'AktHomeConsumptionSolar' => '83886336',
'AktHomeConsumptionBat' => '83886592',
'AktHomeConsumptionGrid' => '83886848',
//'AktHomeConsumptionSolarBat' => '83888128',
'AktHomeConsumption' => '83887872',
'dc1Power' => '33555203',
'dc2Power' => '33555459',
//'dc3Power' => '33555715',
'dcPowerPV' => '33556736',
'acPower' => '67109120',
'operatingStatus' => '16780032');


$Entries2=array(
'GridFreq' => '67110400',
'GridCosPhi' => '67110656',
'GridLimitation' => '67110144',
//'GridPowerL1' => '67109379',
//'GridPowerL2' => '67109635',
//'GridPowerL3' => '67109891',
'GridVoltageL1' => '67109378',
'GridVoltageL2' => '67109634',
'GridVoltageL3' => '67109890',
//'GridCurrentL1' => '67109377',
//'GridCurrentL2' => '67109633',
//'GridCurrentL3' => '67109889',
//'AktHomeConsumptionL1' => '83887106',
//'AktHomeConsumptionL2' => '83887362',
//'AktHomeConsumptionL3' => '83887618',
'dc1Voltage' => '33555202',
'dc1Current' => '33555201',
'dc2Voltage' => '33555458',
'dc2Current' => '33555457',
//'dc3Voltage' => '33555714',
//'dc3Current' => '33555713',
//'ownConsumption' => '83888128',
'operatingStatus' => '16780032');

$url1 = "http://".$IPAdresse_WR."/api/dxs.json?dxsEntries=".implode('&dxsEntries=',$Entries1);
$url2 = "http://".$IPAdresse_WR."/api/dxs.json?dxsEntries=".implode('&dxsEntries=',$Entries2);

$response1 = file_get_contents($url1, "r");
$dataValues['time_sec']=time();
$response2 = file_get_contents($url2, "r");

$dataObject = json_decode($response1, true);
foreach ($Entries1 as $key => $value) {
	$dataValues[$key]=get_value($dataObject['dxsEntries'],$value);
}
$dataObject = json_decode($response2, true);
foreach ($Entries2 as $key => $value) {
	$dataValues[$key]=get_value($dataObject['dxsEntries'],$value);
}
$dataValues['BatPowerLaden']=$dataValues['BatCurrentDir']==0?$dataValues['BatCurrent']*$dataValues['BatVoltage']:0;
$dataValues['BatPowerEntLaden']=$dataValues['BatCurrentDir']==1?$dataValues['BatCurrent']*$dataValues['BatVoltage']:0;
$dataValues['TIMESTAMP']=date('Y-m-d H:i:s',$dataValues['time_sec']);
//print_r($dataValues);

//Correction of PV eigenverbrauch
if ($dataValues['acPower']<=0.001){
	$dataValues['AktHomeConsumptionSolar']=0;
	$dataValues['AktHomeConsumptionBat']=0; //Vermutlich nicht nötig, da noch nicht gesehen...
	$dataValues['AktHomeConsumption']=$dataValues['AktHomeConsumptionGrid'];
} elseif ($dataValues['dcPowerPV']<=0.001){
	$dataValues['AktHomeConsumptionSolar']=0;
	$dataValues['AktHomeConsumption']=$dataValues['AktHomeConsumptionGrid']+$dataValues['AktHomeConsumptionBat'];
}
//manchmal ist AktHomeConsumptionSolar negativ... wird hier korrigiert
if ( $dataValues['AktHomeConsumptionSolar']<0 || $dataValues['AktHomeConsumptionBat']<0 || $dataValues['AktHomeConsumption']<0 || $dataValues['AktHomeConsumptionGrid']<0 ){
	$dataValues['AktHomeConsumptionSolar']=$dataValues['AktHomeConsumptionSolar']>0?$dataValues['AktHomeConsumptionSolar']:0;
	$dataValues['AktHomeConsumptionBat']=$dataValues['AktHomeConsumptionBat']>0?$dataValues['AktHomeConsumptionBat']:0;
	$dataValues['AktHomeConsumptionGrid']=$dataValues['AktHomeConsumptionGrid']>0?$dataValues['AktHomeConsumptionGrid']:0;
	
	$dataValues['AktHomeConsumption']=$dataValues['AktHomeConsumptionSolar']+$dataValues['AktHomeConsumptionBat']+$dataValues['AktHomeConsumptionGrid'];
}


//Correction of loading battery by grid (Ausgleichsladung)
if ($dataValues['BatCurrentDir']==0 && $dataValues['BatPowerLaden']>$dataValues['AktHomeConsumptionGrid'] && $dataValues['dcPowerPV']<1){
	$dataValues['AktHomeConsumptionGrid'] = $dataValues['AktHomeConsumptionGrid'] + $dataValues['BatPowerLaden'];
	$dataValues['AktHomeConsumption']     = $dataValues['AktHomeConsumption']     + $dataValues['BatPowerLaden'];
}


$dataValues['EinspeisenPower']=$dataValues['acPower']>0.001?$dataValues['acPower']-$dataValues['AktHomeConsumptionSolar']-$dataValues['AktHomeConsumptionBat']:0;


$sql  = "INSERT INTO $mysql_PV_data_tablename_WR\n";
$sql .= " (`".implode("`, `", array_keys($dataValues))."`)\n";
$sql .=  " VALUES ('".implode("', '", $dataValues)."')\n";
//echo "\n\n\n".$sql."\n\n\n";



//create table  
 /*foreach ($dataValues as $key => $value) {
	if ($key != 'time_sec') {
		$table_def[]="`$key` DECIMAL(11,6) NULL";
	}
  }
  echo "CREATE TABLE `SolarAnlage`.`$mysql_PV_data_tablename_WR` ( `ID` INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,PRIMARY KEY (`ID`) ,
`time_sec` INT(11) UNSIGNED NOT NULL , ".implode(",\n", $table_def).") ENGINE = InnoDB;";*/
  
  
  
  

try {
	$pdo = new PDO($mysql_PV_data_dsn, $mysql_PV_data_username, $mysql_PV_data_pw, $mysql_PV_data_options);
} catch (Exception $e) {
	//error_log($e->getMessage());
	exit('Mysql-Verbindung abgebrochen: '.$e->getMessage()."\n\tTimestamp:".date('Y-m-d H:i:s',time())."\n\n"); //something a user can understand
}

try {
	$pdo->exec($sql);
	} 
catch (PDOException $e) { 
    die("ERROR: Could not able to execute\n\t$sql\n\t"
            .$e->getMessage()."\n\tTimestamp:".date('Y-m-d H:i:s',time())."\n\n"); 
} 

unset($pdo); 


/*echo "\n";
echo "\n";
echo "\n\n\n Fhem script:\n";
echo "define Piko_BA HTTPMOD $url 60\n attr Piko_BA userattr";
$counter =1;
foreach ($Entries as $value){
	printf(' reading%02dName reading%02dRegex',$counter,$counter);
	$counter++;
}

$counter =1;
foreach ($Entries as $key => $value){
	printf("\nattr Piko_BA reading%02dName %s\n",$counter,$key);
	printf('attr Piko_BA reading%02dRegex :%d,"value":([\d\.]+)',$counter,$value);
	$counter++;
}

foreach ($Entries as $key => $value)
	$log_string.='|'.$key;
echo "\n".'define Piko_BA.log DbLog ./db.conf Piko_BA:('.substr($log_string,1).').*'."\n";


*/
?>