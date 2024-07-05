<?php
ini_set("display_errors", 1);

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

$conn_string = "DRIVER={$driver};DATABASE=$database;HOSTNAME=$hostname;PORT=$port;PROTOCOL=TCPIP;UID=$user;PWD=$password;";
$conn = db2_connect($conn_string, '', '');

if ($conn) {
    echo "Connection succeeded.";
    db2_close($conn);
}
else {
    echo "Connection failed. ";
	echo "<br />|".db2_conn_error()." ||| ".db2_conn_errormsg()."|<br />"; 
}

echo '<br><br><br><br>';
echo $dsn;
$conn = odbc_connect($dsn,$user,$password );

 
echo '<br><br><br><br>'; 


/*
echo '<br><br><br><br>'; 
$dsn_pdo= "ibm_db2:DRIVER={ibm_db2};DATABASE=".$database.";" .
"HOSTNAME=".$hostname.";PORT=".$port.";PROTOCOL=TCPIP;UID=".$user.";PWD=".$password;
echo $dsn_pdo;
$db = new PDO($dsn_pdo,
"",
"");
*/




?>