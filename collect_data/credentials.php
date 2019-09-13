<?php
// Ausleseskript Wechselrichter Kostal Piko ab Firmware v05.31 (12.10.2015)
//Kommunikation

//kostal
$IPAdresse_WR = '192.168.2.10';
$mysql_PV_data_tablename_WR="WR_Daten";
$mysql_PV_data_tablename_WRhourly="aggregate_WR_Daten_hourly";
$mysql_PV_data_tablename_WRdaily="aggregate_WR_Daten_daily";
$mysql_PV_data_tablename_WRgewinne="Gewinne_Verlust";
$mysql_PV_data_tablename_WRsteuerl="SteuerlGewinnVerlust";
$mysql_PV_data_tablename_WRgeldw="GeldwerterVorteil";
$mysql_PV_data_tablename_WRstromkosten="Stromkosten";


//BYD
$IPAdresse_BYD = '192.168.2.11';
$mysql_PV_data_tablename_BYD="BYD_Daten"; 

//DWD
$DWD_station_IDs=array('P257','K4087','N3420');
$mysql_PV_data_tablename_DWD="DWD_Daten";
$mysql_PV_data_tablename_DWDfactor="DWD_Rad_factor";

//KOstal/BYD mysql settings
$mysql_PV_data_DB_name="SolarAnlage";
$mysql_PV_data_dsn = "mysql:host=127.0.0.1;port=3307;dbname=$mysql_PV_data_DB_name;charset=utf8mb4";
$mysql_PV_data_username="**********";
$mysql_PV_data_pw="********";
$mysql_PV_data_options = [
	//PDO::ATTR_EMULATE_PREPARES   => false, // turn off emulation mode for "real" prepared statements
	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
	//PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //make the default fetch be an associative array
];


//mysql settings
$mysql_LUX_dataSET_dsn = "mysql:host=127.0.0.1;port=3307;dbname=FHEM;charset=utf8mb4";
$mysql_LUX_dataSET_username="**********";
$mysql_LUX_dataSET_pw="*******";
$mysql_LUX_dataSET_tablename="Heizung";

$mysql_LUX_dataGET_dsn = "mysql:host=127.0.0.1;port=3307;dbname=FHEM;charset=utf8mb4";
$mysql_LUX_dataGET_username="**************";
$mysql_LUX_dataGET_pw="*********";
$mysql_LUX_dataGET_tablename="Current";

$mysql_LUX_data_options = [
	//PDO::ATTR_EMULATE_PREPARES   => false, // turn off emulation mode for "real" prepared statements
	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
	//PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //make the default fetch be an associative array
];

?>