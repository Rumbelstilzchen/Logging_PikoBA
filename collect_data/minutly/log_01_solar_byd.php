<?php
// Ausleseskript Wechselrichter Kostal Piko ab Firmware v05.31 (12.10.2015)
//Kommunikation

ini_set("display_errors", 0); 

function load_Dom($html){
	$auth = base64_encode("installer:byd@12345");
	$context = stream_context_create([
		"http" => [
			"header" => "Authorization: Basic $auth"
		]
	]);
	$homepage = file_get_contents($html, false, $context );

	$homepage=str_replace('><input readonly="readonly" type="text" value=','>',$homepage);
	$homepage=str_replace(array('&#8451',' id="1"'),'',$homepage);
	//echo $homepage;
	$dom = new DOMDocument;
	//echo $html."\n";
	$dom->loadHTML($homepage);
	return $dom;
}

function load_DomXPath($html){
	$dom = load_Dom($html);
	$xp = new DOMXPath($dom);
	return $xp;
}


require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'credentials.php';


$html="http://".$IPAdresse_BYD."/asp/StatisticInformation.asp";
$xp=load_DomXPath($html);
$BYD_data['time_sec']=time();
$html="http://".$IPAdresse_BYD."/asp/RunData.asp";
$xp2=load_DomXPath($html);
$BYD_data['TIMESTAMP']=date('Y-m-d H:i:s',$BYD_data['time_sec']);

$BYD_data['Laden_kWh']=filter_var($xp->evaluate('string(//td[.="Total Charge Energy:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);

$BYD_data['EntLaden_kWh']=filter_var($xp->evaluate('string(//td[.="Total Discharge Energy:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$BYD_data['CycleCounts']=filter_var($xp->evaluate('string(//td[.="Total Cycle Counts:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_INT);




$BYD_data['PackVoltage_V']=filter_var($xp2->evaluate('string(//td[.="PackVoltage:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$BYD_data['Current_A']=filter_var($xp2->evaluate('string(//td[.="Current:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$BYD_data['SOC']=filter_var($xp2->evaluate('string(//td[.="SOC:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$BYD_data['SysTemp_C']=filter_var($xp2->evaluate('string(//td[.="SysTemp:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$BYD_data['MaxCellTemp_C']=filter_var($xp2->evaluate('string(//td[.="MaxCellTemp:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$BYD_data['MinCellTemp_C']=filter_var($xp2->evaluate('string(//td[.="MinCellTemp:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$BYD_data['MaxCellVol_V']=filter_var($xp2->evaluate('string(//td[.="MaxCellVol:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$BYD_data['MinCellVol_V']=filter_var($xp2->evaluate('string(//td[.="MinCellVol:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$BYD_data['Power_kW']=filter_var($xp2->evaluate('string(//td[.="Power:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$BYD_data['MaxVolPos']=filter_var($xp2->evaluate('string(//td[.="MaxVolPos:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_INT);
$BYD_data['MinVolPos']=filter_var($xp2->evaluate('string(//td[.="MinVolPos:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_INT);
$BYD_data['MaxTempPos']=filter_var($xp2->evaluate('string(//td[.="MaxTempPos:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_INT);
$BYD_data['MinTempPos']=filter_var($xp2->evaluate('string(//td[.="MinTempPos:"]/following-sibling::*[1][name()="td"])'), FILTER_SANITIZE_NUMBER_INT);





$sql  = "INSERT INTO $mysql_PV_data_tablename_BYD\n";
$sql .= " (`".implode("`, `", array_keys($BYD_data))."`)\n";
$sql .=  " VALUES ('".implode("', '", $BYD_data)."')\n";
//echo "\n\n\n".$sql."\n\n\n";



//create table  
/* foreach ($BYD_data as $key => $value) {
	if ($key != 'time_sec') {
		$table_def[]="`$key` DECIMAL(7,3) NULL";
	}
  }
  echo "CREATE TABLE `SolarAnlage`.`$mysql_PV_data_tablename_BYD` (`time_sec` INT(11) UNSIGNED NOT NULL , ".implode(",\n", $table_def).") ENGINE = InnoDB;";
  */
  
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

?>