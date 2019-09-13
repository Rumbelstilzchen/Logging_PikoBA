<?php
// Ausleseskript Wechselrichter Kostal Piko ab Firmware v05.31 (12.10.2015)
//Kommunikation


ini_set("display_errors", 1); 

require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'credentials.php';
$mysql_PV_data_tablename_output1=$mysql_PV_data_tablename_WRgewinne."_PVonly";

$sql="drop table if exists `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_output1`;\n";
$sql.="create table `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_output1` as select 
`T1`.`time_sec` AS `time_sec`,`T1`.`TIMESTAMP` AS `TIMESTAMP`,
round(sum((`T1`.`Einspeisen_kWh` + `T1`.`HomeConsumptionBat_kWh` - if(`T1`.`BatLaden_Frei_kWh` is not NULL,(`T1`.`BatLaden_Frei_kWh`)*0.90,0)) * `T1`.`verkauf_cent_kWh`) / 100,5) AS `Verkauf`,
round(sum(`T1`.`HomeConsumptionSolar_kWh` * `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRstromkosten`.`cent_kWh`) / 100,5) AS `Ersparnis`,
round(sum(`T1`.`ac_kWh` * `T1`.`verkauf_cent_kWh`) / 100,8) AS `SteuerlGewinn`,
round(sum(`T1`.`HomeConsumptionSolar_kWh` * `T1`.`geldwv_cent_kWh`) / 100 * 0.19,5) AS `MWST`,
round(sum((`T1`.`HomeConsumptionGrid_kWh` + `T1`.`HomeConsumptionBat_kWh`) * `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRstromkosten`.`cent_kWh`) / 100,5) AS `BezugKosten`,
round(`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRsteuerl`.`SteuerlAbschr`-0.66986301369,5) AS `Abschreibung`
from (
	select
	`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`.`time_sec` AS `time_sec`,
	`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`.`TIMESTAMP` AS `TIMESTAMP`,
	`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`.`Einspeisen_kWh` AS `Einspeisen_kWh`,
	`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`.`ac_kWh` AS `ac_kWh`,
	`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`.`HomeConsumptionSolar_kWh` AS `HomeConsumptionSolar_kWh`,
	`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`.`HomeConsumptionBat_kWh` AS `HomeConsumptionBat_kWh`,
	`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`.`HomeConsumptionGrid_kWh` AS `HomeConsumptionGrid_kWh`,
	`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`.`BatLaden_Frei_kWh` AS `BatLaden_Frei_kWh`,
	`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRstromkosten`.`cent_kWh` AS `verkauf_cent_kWh`,
	`$mysql_PV_data_tablename_WRgeldw`.`cent_kWh` AS `geldwv_cent_kWh`
	from ((
		`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`
		left join `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRstromkosten` on(`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`.`TIMESTAMP` >= `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRstromkosten`.`DATE` and `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`.`TIMESTAMP` <= `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRstromkosten`.`Valid_UNTIL`))
		left join `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRgeldw` on (year(`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRdaily`.`TIMESTAMP`) = `$mysql_PV_data_tablename_WRgeldw`.`jahr`))
	where `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRstromkosten`.`TYPE` = 'Verkauf') `T1`
	left join `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRstromkosten` on (`T1`.`TIMESTAMP` >= `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRstromkosten`.`DATE` and `T1`.`TIMESTAMP` <= `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRstromkosten`.`Valid_UNTIL`)
	left join `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRsteuerl` on `T1`.`TIMESTAMP`=date(`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRsteuerl`.`TIMESTAMP`)
where `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRstromkosten`.`TYPE` = 'Bezug'
group by to_days(`T1`.`TIMESTAMP`)
Order by TIMESTAMP ASC;";


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

unset($pdo);

?>