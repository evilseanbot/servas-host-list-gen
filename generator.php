<?php

error_reporting(E_ALL);

require('../../../../cl/fpdf/fpdf.php');
require('../../FPDI/fpdi.php');
include "../../functions/passprotect.php";
include "abvNations.php";
include "printKeyPeople.php";
include "profiling.php";
include "toc.php";
include "passwordFile.php";
include "hostListGenFunctions.php";
include "hostListEntries.php";

// Switch these security measures around when uploading it to the public side

session_start(); 
//authUser($_SESSION[User]);

$hostResult = pg_query(file_get_contents("sql/hostQuery.sql", true));
$disabsById = sortedArrayFromSQL("disabQuery.sql", "HostId");
$petsById = sortedArrayFromSQL("petQuery.sql", "HostId");
$phonesById = sortedArrayFromSQL("phoneQuery.sql", "PersonId");
$emailsById = sortedArrayFromSQL("emailQuery.sql", "PersonId");
$langsById = sortedArrayFromSQL("langQuery.sql", "HostId");
$peopleByPersonId = sortedArrayFromSQL("peopleQuery.sql", "PersonId");
$peopleByRelateId = sortedArrayFromSQL("peopleQuery.sql", "r_person_id");

$pdf = new PDF_TOC();

addHostListTOCEntries($pdf);


$TOCPages = 2;

// The PDF starts on page 1.
$pdf->_numPageNum = 1 + $TOCPages;

$blockOriginY = $pdf->GetY();
$currentColX = 0;
$blocksDisplayed = 0;

// $style is a collection of information about the presentation of the document.

$style = array("colW" => 70, "bottomColW" => 199, "headerW" => 105, "blockH" => 300, "pageW" => 170, "stdFontSize" => 10, "stdFont" => "Times");

$pageNoOnLeft = false;
$firstEntry = true;
$oldState = "";
$newState = "";

$peopleIndex = array();
$cityIndex = array();
$pdf->SetFont($style["stdFont"],'',$style["stdFontSize"]);

addHostListFrontPages($pdf, $style);

printKeyPeople();
$pdf->startPageNums();

// Print the Host Entries
	
for ($i = 0; $hostRow = pg_fetch_array($hostResult); $i++) {	
    printHostEntry($hostRow, $style, $peopleByPersonId);
}


//echo "test11";

printIndex($peopleIndex, $style, "LastName", "FirstName", "Index By Host Name");
printIndex($cityIndex, $style, "City", "State", "Index by City Name", true);

$pdf->insertTOC(5, 24, 12);
//echo "test12";
$pdf->Output();
?>