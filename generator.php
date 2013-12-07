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

$pdf->_toc[] = array("t" => "United States Map", "l" => 0, "p" => 7);
$pdf->_toc[] = array("t" => "United States Servas, Inc. Board and Staff", "l" => 0, "p" => 8);
$pdf->_toc[] = array("t" => "Responsibilites of Servas Travelers", "l" => 0, "p" => 9);
$pdf->_toc[] = array("t" => "Guide to reading the Host List", "l" => 0, "p" => 12);
$pdf->_toc[] = array("t" => "Language Code Abbreviations", "l" => 0, "p" => 14);
$pdf->_toc[] = array("t" => "Country Code Abbreviations", "l" => 0, "p" => 16);
$pdf->_toc[] = array("t" => "Miscellaneous Abbreviations", "l" => 0, "p" => 17);

$TOCPages = 2;

// The PDF starts on page 1.
$pdf->_numPageNum = 1;

$blockOriginY = $pdf->GetY();
$currentColX = 0;
$colW = 70;
$blocksDisplayed = 0;
$blockH = 300;
$pageW = 170;
$fontSize = 10;
$font = "Times";

// $style is a collection of information about the presentation of the document.

$style = array("colW" => 70, "bottomColW" => 199, "blockH" => 300, "pageW" => 170, "stdFontSize" => 10, "stdFont" => "Times");

$pageNoOnLeft = false;
$firstEntry = true;
$oldState = "";
$newState = "";

$peopleIndex = array();
$cityIndex = array();
$pdf->SetFont($style["stdFont"],'',$style["stdFontSize"]);

addPagesFromPDF('HostListFront.pdf', 1);
printTimeStamp();
addPagesFromPDF('HostListFront.pdf', 8, 2);
addPagesFromPDF('Guide for reading the Host List.pdf', 2);
addPagesFromPDF('Language Code Abbreviations.pdf', 2);
addPagesFromPDF('Country Code Abbreviations.pdf', 1);
addPagesFromPDF('HostListFront.pdf', 1, 10);

printKeyPeople();
$pdf->startPageNums();

// Print the Host Entries
	
for ($i = 0; $hostRow = pg_fetch_array($hostResult); $i++) {	
    printHostEntry($hostRow, $style, $peopleByPersonId);
}

printIndex($peopleIndex, "LastName", "FirstName", "Index By Host Name");
printIndex($cityIndex, "City", "State", "Index by City Name", true);

$pdf->insertTOC(5, 24, 12);
$pdf->Output();
?>