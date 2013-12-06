<?php

error_reporting(E_ALL);

require('../../../../cl/fpdf/fpdf.php');
require('../../FPDI/fpdi.php');
include "../../functions/passprotect.php";
include "abvNations.php";
include "printkeypeople.php";
include "testing.php";
include "toc.php";
include "passwordfile.php";
include "hostlistgenfunctions.php";

// Switch these security measures around when uploading it to the public side

session_start(); 
//authUser($_SESSION[User]);

$hostResult = pg_query(file_get_contents("sql/hostQuery.sql", true));
$people = pg_fetch_all(pg_query(file_get_contents("sql/peopleQuery.sql", true)));
$peopleByPersonId = getArraySortedById($people, "PersonId");
$peopleByRelateId = getArraySortedById($people, "r_person_id");

$disabsById = sortedArrayFromSQL("disabQuery.sql", "HostId");
$petsById = sortedArrayFromSQL("petQuery.sql", "HostId");
$phonesById = sortedArrayFromSQL("phoneQuery.sql", "PersonId");
$emailsById = sortedArrayFromSQL("emailQuery.sql", "PersonId");
$langsById = sortedArrayFromSQL("langQuery.sql", "HostId");
//$peopleByPersonId = sortedArrayFromSQL("peopleQuery.sql", "PersonId");

//$peopleByPersonId = sortedArrayFromSQL("peopleQuery.sql", "PersonId");

$pdf = new PDF_TOC();

$pdf->_toc[] = array("t" => "United States Map", "l" => 0, "p" => 7);
$pdf->_toc[] = array("t" => "United States Servas, Inc. Board and Staff", "l" => 0, "p" => 8);
$pdf->_toc[] = array("t" => "Responsibilites of Servas Travelers", "l" => 0, "p" => 9);
$pdf->_toc[] = array("t" => "Guide to reading the Host List", "l" => 0, "p" => 12);
$pdf->_toc[] = array("t" => "Language Code Abbreviations", "l" => 0, "p" => 14);
$pdf->_toc[] = array("t" => "Country Code Abbreviations", "l" => 0, "p" => 16);
$pdf->_toc[] = array("t" => "Miscellaneous Abbreviations", "l" => 0, "p" => 17);

$blockOriginY = $pdf->GetY();
$currentColX = 0;
$colW = 70;
$blocksDisplayed = 0;
$blockH = 300;
$pageW = 170;
$fontSize = 10;
$font = "Times";

$pageNo = 0;
$pageNoOnLeft = false;
$firstEntry = true;
$oldState = "";
$newState = "";

$peopleIndex = array();
$cityIndex = array();
$pdf->SetFont($font,'',$fontSize);

addPagesFromPDF('HostListFront.pdf', 1);
printTimeStamp();
addPagesFromPDF('HostListFront.pdf', 8, 2);
addPagesFromPDF('Guide for reading the Host List.pdf', 2);
addPagesFromPDF('Language Code Abbreviations.pdf', 2);
addPagesFromPDF('Country Code Abbreviations.pdf', 1);
addPagesFromPDF('HostListFront.pdf', 1, 10);

$pdf->_numPageNum = 17;
printKeyPeople();
$pdf->startPageNums();

// Print the Host Entries
	
for ($i = 0; $hostRow = pg_fetch_array($hostResult); $i++) {	
    printHostEntry($hostRow, $peopleByPersonId);
}

printIndex($peopleIndex, "LastName", "FirstName", "Index By Host Name");
printIndex($cityIndex, "City", "State", "Index by City Name", true);

$pdf->insertTOC(5, 24, 12);
$pdf->Output();
?>