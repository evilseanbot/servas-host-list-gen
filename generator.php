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

startProfileRecord("all");
startProfileRecord("sql");

$hostResult = pg_query(file_get_contents("sql/hostQuery.sql", true));

$people = pg_fetch_all(pg_query(file_get_contents("sql/peopleQuery.sql", true)));

startProfileRecord("getSortedArray");
$peopleByPersonId = getArraySortedById($people, "PersonId");
$peopleByRelateId = getArraySortedById($people, "r_person_id");
endProfileRecord("getSortedArray");

//$peopleByPersonId = sortedArrayFromSQL("peopleQuery2.sql", "PersonId");
$disabsById = sortedArrayFromSQL("disabQuery.sql", "HostId");
$petsById = sortedArrayFromSQL("petQuery.sql", "HostId");
$phonesById = sortedArrayFromSQL("phoneQuery.sql", "HostId");
$emailsById = sortedArrayFromSQL("emailQuery.sql", "HostId");
$langsById = sortedArrayFromSQL("langQuery.sql", "HostId");

endProfileRecord("sql");
startProfileRecord("hostPrinting");

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

$pdf->setSourceFile('HostListFront.pdf'); 
for ($i = 1; $i < 10; $i++) { 
	$cover = $pdf->importPage($i, '/MediaBox'); 
	$pdf->addPage(); 
	$pdf->useTemplate($cover, 0, 0, 210); 
	
	if ($i == 1) {
		$pdf->SetFont($font,'',$fontSize+2);
		$pdf->SetY(250);
		$pdf->SetX(80);
        date_default_timezone_set('UTC');		
        $pdf->Cell($colW, 5, "Updated: " . date("F j, Y, g:i a") . " UTC", 0, 1);	
		$pdf->SetFont($font,'',$fontSize);
	}
}

$pdf->setSourceFile('Guide for reading the Host List.pdf'); 
for ($i = 1; $i < 3; $i++) { 
	$page = $pdf->importPage($i, '/MediaBox'); 
	$pdf->addPage(); 
	$pdf->useTemplate($page, 0, 0, 210); 
}

$pdf->setSourceFile('Language Code Abbreviations.pdf'); 
for ($i = 1; $i < 3; $i++) { 
	$page = $pdf->importPage($i, '/MediaBox'); 
	$pdf->addPage(); 
	$pdf->useTemplate($page, 0, 0, 210); 
}

$pdf->setSourceFile('Country Code Abbreviations.pdf'); 
for ($i = 1; $i < 2; $i++) { 
	$page = $pdf->importPage($i, '/MediaBox'); 
	$pdf->addPage(); 
	$pdf->useTemplate($page, 0, 0, 210); 
}

$pdf->setSourceFile('HostListFront.pdf'); 
for ($i = 10; $i < 11; $i++) { 
	$page = $pdf->importPage($i, '/MediaBox'); 
	$pdf->addPage(); 
	$pdf->useTemplate($page, 0, 0, 210); 
}

$pdf->_numPageNum=17;
//printKeyPeople();
$pdf->startPageNums();
	
for ($i = 0; $hostRow = pg_fetch_array($hostResult); $i++) {	
    printHostEntry($hostRow, $peopleByPersonId);
}

// print out the people index page:
newPage("Index by host name");
$pdf->TOC_Entry("Index by host name", 0);

foreach ($peopleIndex as $key => $row) {
    $LastName[$key]  = $row['LastName'];
    $FirstName[$key] = $row['FirstName'];
}

array_multisort($LastName, SORT_ASC, $FirstName, SORT_ASC, $peopleIndex);

$entriesInCol = 0;
$currentX = 0;
$currentLineY = 0;

foreach ($peopleIndex as $peopleIndexEntry) {
	$pdf->SetX($currentX);
	$currentLineY = $pdf->GetY();
    $nameString = $peopleIndexEntry["LastName"] . ", " . $peopleIndexEntry["FirstName"];
	
    $pdf->Cell(50, 5, $nameString, 0, 1);
	
	$pdf->Rect($currentX + $pdf->GetStringWidth($nameString) + 2, $currentLineY+4, 55 - ($pdf->GetStringWidth($nameString)) - 2, 0);	
	
	$pdf->SetY($currentLineY);
	$pdf->SetX($currentX+55);
    $pdf->Cell(50, 5, $peopleIndexEntry["IndexNo"], 0, 1);	
	
	$entriesInCol++;
	if ($entriesInCol > 50) {
		$entriesInCol = 0;
		$pdf->SetY(10);
	    $currentX += 65;
		
		if ($currentX > 160) {
		    $currentX = 0;
			newPage("Index by Host Name");
		}
		
	}
}

// print out the city index page:
newPage("Index by City Name");
$pdf->TOC_Entry("Index by city name", 0);

foreach ($cityIndex as $key => $row) {
    $City[$key]  = $row['City'];
    $State[$key] = $row['State'];
}

array_multisort($City, SORT_ASC, $State, SORT_ASC, $cityIndex);

$entriesInCol = 0;
$currentX = 0;
$currentLineY = 0;

$uniqueCityIndex = array();

for ($i = 0; $i < sizeof($cityIndex); $i++) {
    if ($i > 0) {
    	if ( ($cityIndex[$i]["City"] != $cityIndex[$i-1]["City"]) || ($cityIndex[$i]["State"] != $cityIndex[$i-1]["State"]) ){
	        array_push($uniqueCityIndex, $cityIndex[$i]);	
		}
	} else {
	    array_push($uniqueCityIndex, $cityIndex[$i]);		
	}
}

$cityIndex = $uniqueCityIndex;

foreach ($cityIndex as $cityIndexEntry) {
	$pdf->SetX($currentX);
	$currentLineY = $pdf->GetY();
    $nameString = $cityIndexEntry["City"] . ", " . $cityIndexEntry["State"];
	
    $pdf->Cell(50, 5, $nameString, 0, 1);
	
	$pdf->Rect($currentX + $pdf->GetStringWidth($nameString) + 2, $currentLineY+4, 55 - ($pdf->GetStringWidth($nameString)) - 2, 0);	
	
	$pdf->SetY($currentLineY);
	$pdf->SetX($currentX+55);
    $pdf->Cell(50, 5, $cityIndexEntry["IndexNo"], 0, 1);	
	
	$entriesInCol++;
	if ($entriesInCol > 50) {
		$entriesInCol = 0;
		$pdf->SetY(10);
	    $currentX += 65;
		
		if ($currentX > 160) {
		    $currentX = 0;
			newPage("Index by City Name");
		}
		
	}
}


endProfileRecord("hostPrinting");
endProfileRecord("all");

//printProfiles();


$pdf->insertTOC(5, 24, 12);
$pdf->Output();
?>