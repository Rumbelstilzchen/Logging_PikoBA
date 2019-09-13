<?php
// Ausleseskript Wechselrichter Kostal Piko ab Firmware v05.31 (12.10.2015)
//Kommunikation


ini_set("display_errors", 1); 

require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'credentials.php';

$sql="replace LOW_PRIORITY into $mysql_PV_data_tablename_WRhourly (time_sec,  TIMESTAMP, ChargeCycles, BatTemperature, HomeConsumptionSolar_kWh, HomeConsumptionBat_kWh, HomeConsumptionGrid_kWh, HomeConsumption_kWh, PV_kWh, ac_kWh, BatLaden_kWh, BatLaden_Frei_kWh, BatEntladen_kWh, Einspeisen_kWh)
select
round(min(time_sec),-1) as time_sec,
FROM_UNIXTIME(round(min(time_sec),-1)) as TIMESTAMP,
max(ChargeCycles) as ChargeCycles,
round(avg(BatTemperature),6) as BatTemperature,
round(avg(AktHomeConsumptionSolar)/1000,13) as HomeConsumptionSolar_kWh,
round(avg(AktHomeConsumptionBat)/1000,13) as HomeConsumptionBat_kWh,
round(avg(AktHomeConsumptionGrid)/1000,13) as HomeConsumptionGrid_kWh,
round(avg(AktHomeConsumption)/1000,13) as HomeConsumption_kWh,
round(avg(dcPowerPV)/1000,13) as PV_kWh,
round(avg(acPower)/1000,13) as ac_kWh,
round(avg(BatPowerLaden)/1000,13) as BatLaden_kWh,
round(avg(if(BatPowerLaden>0.1, if(EinspeisenPower>5300,BatPowerLaden,0)  +   if(EinspeisenPower<=5300 AND BatPowerLaden+EinspeisenPower>5300,BatPowerLaden+EinspeisenPower-5300,0),0))/1000,13) as BatLaden_Frei_kWh,
round(avg(BatPowerEntLaden)/1000,13) as BatEntladen_kWh,
round(avg(EinspeisenPower)/1000,13) as Einspeisen_kWh
FROM $mysql_PV_data_tablename_WR
WHERE DATE(TIMESTAMP) Between SUBDATE(CURRENT_DATE(), INTERVAL 1 DAY) AND CURRENT_DATE()
Group By to_days(TIMESTAMP),hour(TIMESTAMP)
Order BY time_sec;\n";

$sql2="replace LOW_PRIORITY into $mysql_PV_data_tablename_WRdaily (time_sec,  TIMESTAMP, ChargeCycles, BatTemperature, HomeConsumptionSolar_kWh, HomeConsumptionBat_kWh, HomeConsumptionGrid_kWh, HomeConsumption_kWh, PV_kWh, ac_kWh, BatLaden_kWh, BatLaden_Frei_kWh, BatEntladen_kWh, Einspeisen_kWh)
select
min(time_sec) as time_sec,
DATE(min(TIMESTAMP)) as TIMESTAMP,
max(ChargeCycles) as ChargeCycles,
round(avg(BatTemperature),6) as BatTemperature,
round(sum(HomeConsumptionSolar_kWh),13) as HomeConsumptionSolar_kWh,
round(sum(HomeConsumptionBat_kWh),13) as HomeConsumptionBat_kWh,
round(sum(HomeConsumptionGrid_kWh),13) as HomeConsumptionGrid_kWh,
round(sum(HomeConsumption_kWh),13) as HomeConsumption_kWh,
round(sum(PV_kWh),13) as PV_kWh,
round(sum(ac_kWh),13) as ac_kWh,
round(sum(BatLaden_kWh),13) as BatLaden_kWh,
round(sum(BatLaden_Frei_kWh),13) as BatLaden_Frei_kWh,
round(sum(BatEntladen_kWh),13) as BatEntladen_kWh,
round(sum(Einspeisen_kWh),13) as Einspeisen_kWh
FROM $mysql_PV_data_tablename_WRhourly
WHERE DATE(TIMESTAMP) Between SUBDATE(CURRENT_DATE(), INTERVAL 1 DAY) AND CURRENT_DATE()
Group By to_days(TIMESTAMP)
Order BY time_sec;\n";
  

try {
  $pdo = new PDO($mysql_PV_data_dsn, $mysql_PV_data_username, $mysql_PV_data_pw, $mysql_PV_data_options);
} catch (Exception $e) {
  error_log($e->getMessage());
  exit('Mysql-Verbindung abgebrochen: '.$e->getMessage()); //something a user can understand
}

try {
	$pdo->exec($sql);
	} 
catch (PDOException $e) { 
    die("ERROR: Could not able to execute\n $sql. \n"
            .$e->getMessage()); 
} 
try {
	$pdo->exec($sql2);
	} 
catch (PDOException $e) { 
    die("ERROR: Could not able to execute\n $sql2. \n"
            .$e->getMessage()); 
}



unset($pdo); 
?>