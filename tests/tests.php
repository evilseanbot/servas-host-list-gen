<?php

ini_set('error_reporting', E_ALL);

require('../../../../../cl/fpdf/fpdf.php');
require('../../../FPDI/fpdi.php');

include "../../../functions/passprotect.php";
include "../passwordfile.php";
include "../hostlistgenfunctions.php";


echo "Limited strings limits to a given limit, then appends '...' : ";

$longString = "abcdefghijklmnop";
$limit = 5;

if (limitedString($longString, $limit)  == "abcde...") {
	echo "PASSED";
} else {
	echo "FAILED";
}

$a = $b;
?>