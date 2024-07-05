<?php
ini_set("display_errors", 1);
$usuario='WEBUSERMYM';
$password='web2014';
$dsn='DEV';

if (!$db = odbc_connect ( $dsn, $usuario, $password) )
    die('error!');

//echo '<pre>';die(print_r($db));

//$result = odbc_exec($db, "SELECT * FROM LIBPRDDAT.A_CRED ac WHERE CODE = 1");

$result = odbc_exec($db, "SELECT EICODCLI, EINRODOC, EIIMPSLD FROM LIBPRDDAT.MMEIREP where EIJDT=20220522 LIMIT 100");
while (odbc_fetch_row($result)) {
    echo '<BR>'.odbc_result($result, 'EICODCLI').' - '.odbc_result($result, 'EINRODOC').' - '.odbc_result($result, 'EIIMPSLD');
}

odbc_close($db)
?>
