<?php
require('../../../../../cl/fpdf/fpdf.php');
require('../../../FPDI/fpdi.php');
include "../../../functions/passprotect.php";
include "../abvNations.php";
include "../printkeypeople.php";
include "../testing.php";
include "../toc.php";
include "../passwordfile.php";
include "../hostlistgenfunctions.php";

// Switch these security measures around when uploading it to the public side

session_start(); 
//authUser($_SESSION[User]);

$hostResult = pg_query(file_get_contents("sql/hostQuery.sql", true));
$people = pg_fetch_all(pg_query(file_get_contents("sql/peopleQuery.sql", true)));
$peopleByPersonId = getArraySortedById($people, "PersonId");
$peopleByRelateId = getArraySortedById($people, "r_person_id");

$disabsById = sortedArrayFromSQL("disabQuery.sql", "HostId");
$petsById = sortedArrayFromSQL("petQuery.sql", "HostId");
$phonesById = sortedArrayFromSQL("phoneQuery.sql", "HostId");
$emailsById = sortedArrayFromSQL("emailQuery.sql", "HostId");
$langsById = sortedArrayFromSQL("langQuery.sql", "HostId");

header('Content-type: text/plain');
echo "<pre>" . print_r($diabsById) . "";
echo "******************** PETS ********************";
echo "" . print_r($petsById) . "</pre>";

?>