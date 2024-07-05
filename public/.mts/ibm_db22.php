<?php
ini_set("display_errors", 1);
/*
$dsn = "DB2_DEV";
$database = 'LIBPRDDAT';
$user = 'WEBUSERMYM';
$password = 'web2014';
$hostname = '192.168.1.105';
$port = 446;
$options = '';
//$options = array('autocommit' => DB2_AUTOCOMMIT_OFF);
//$options = array ("trustedcontext" => DB2_TRUSTED_CONTEXT_ENABLE);
//$driver='DB2_DEV';
$driver='DB2';
//$driver  = "IBM DB2 ODBC DRIVER";
//$driver  = "DRIVER={IBM DB2 ODBC DRIVER};";
//$driver='IBM DB2 ODBC DRIVER';
//$driver='ibm_db2';
*/

class MyClass
{
private $server = "Driver={};hostname=192.168.1.105;port=446;database=libprddat;"; #the name of the iSeries
private $user = 'WEBUSERMYM'; #a valid username that will connect to the DB
private $pass = 'web2014'; #a password for the username
private $database = 'LIBPRDDAT';

public function connect()
{
    $dbConnection = odbc_connect($this->server, $this->user, $this->pass);
    return $dbConnection;
}


public function request()   
{
    $db = $this->connect();
	if($db)
	{
		$req1 = " select * FROM LIBPRDDAT.MMEUREP";
		$stmt = odbc_exec($db, $req1);
		
		$row = odbc_fetch_array($stmt);
		echo json_encode($row);
		
		odbc_close($db);
	}
    
}
}

$st = new MyClass();
$st->request();

?>