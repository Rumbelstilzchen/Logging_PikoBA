<?php
ini_set("display_errors", 1); 
error_reporting( E_ALL );

require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'credentials.php';

$sql="drop table if exists `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_DWDfactor`;\n";
$sql.="create table `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_DWDfactor` as select 
`T2`.`month` as `month`,
`T2`.`factor` as `Faktor`,
if (`T2`.`N`>15, (`T2`.`N` * `T2`.`Sum_XY` - `T2`.`Sum_X` * `T2`.`Sum_Y`)/(`T2`.`N` * `T2`.`Sum_X2` - `T2`.`Sum_X` * `T2`.`Sum_X`),`T2`.`factor`) as `LeastSQR`
from (
	select
	count(*) as N,
    sum(`T1`.`value` * `T1`.`Rad`) as `SUM_XY`,
    sum(`T1`.`Rad`) as `SUM_X`,
    sum(`T1`.`value`) as `SUM_Y`,
    sum(`T1`.`Rad` * `T1`.`Rad`) as `SUM_X2`,
	month(`T1`.`time`) AS `month`,
	avg(`T1`.`value` / `T1`.`Rad`) AS `factor` 
	from (
		select 
		`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRhourly`.`TIMESTAMP` AS `time`,
		'PV Power' AS `metric`,
		`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRhourly`.`PV_kWh`*1000 AS `value`,
		`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_DWD`.`Rad1h` / 3.6 * 37.65 * 0.187 AS `Rad`
		from (`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRhourly`
			join `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_DWD` on ( TIMESTAMPADD(HOUR,1,`$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRhourly`.`TIMESTAMP`) = `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_DWD`.`TIMESTAMP`))
		where `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_WRhourly`.`time_sec` >= (select `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_DWD`.`time_sec` from `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_DWD` order by `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_DWD`.`time_sec` limit 1) - 3601 ) `T1`
	where `T1`.`Rad` > 0 AND `T1`.`value` > 0
	group by month(`T1`.`time`)) `T2`;";


/*$sql2="replace INTO `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_DWDfactor` (`month`, `Faktor`, `LeastSQR`) 
     SELECT month(DATE_ADD(CURDATE(),INTERVAL 5 DAY)), `Faktor`, `LeastSQR`
     FROM `$mysql_PV_data_DB_name`.`$mysql_PV_data_tablename_DWDfactor`
     WHERE `month`=month(CURDATE())";*/

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

/*try {
	$pdo->exec($sql2);
	} 
catch (PDOException $e) { 
    die("ERROR: Could not able to execute\n $sql2. \n"
            .$e->getMessage()); 
}*/

unset($pdo);

?>