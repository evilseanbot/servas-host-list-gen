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


$peopleResult = pg_query(file_get_contents("sql/peopleQuery.sql", true));
$langResult = pg_query(file_get_contents("sql/langQuery.sql", true));
$emailResult = pg_query(file_get_contents("sql/emailQuery.sql", true));
$phoneResult = pg_query(file_get_contents("sql/phoneQuery.sql", true));
$petResult = pg_query(file_get_contents("sql/petQuery.sql", true));
$disabResult = pg_query(file_get_contents("sql/disabQuery.sql", true));

$people = pg_fetch_all ($peopleResult);
$langs = pg_fetch_all ($langResult);
$emails = pg_fetch_all ($emailResult);
$phones = pg_fetch_all($phoneResult);
$pets = pg_fetch_all ($petResult);
$disabs = pg_fetch_all ($disabResult);

endProfileRecord("sql");

$pdf = new PDF_TOC();

$pdf->_toc[] = array("t" => "United States Map", "l" => 0, "p" => 7);
$pdf->_toc[] = array("t" => "United States Servas, Inc. Board and Staff", "l" => 0, "p" => 8);
$pdf->_toc[] = array("t" => "Responsibilites of Servas Travelers", "l" => 0, "p" => 9);
$pdf->_toc[] = array("t" => "Guide to reading the Host List", "l" => 0, "p" => 12);
$pdf->_toc[] = array("t" => "Language Code Abbreviations", "l" => 0, "p" => 14);
$pdf->_toc[] = array("t" => "Country Code Abbreviations", "l" => 0, "p" => 16);
$pdf->_toc[] = array("t" => "Miscellaneous Abbreviations", "l" => 0, "p" => 17);

//$pdf->AddPage();
//$pdf->AddFont('Georgia','','georgia2.php');
//$pdf->SetFont('Georgia','',10);


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

startProfileRecord("getSortedArray");
$emailsById = getArraySortedById($emails, "PersonId");
$phonesById = getArraySortedById($phones, "PersonId");
$petsById = getArraySortedById($pets, "HostId");
$langsById = getArraySortedById($langs, "HostId");
$disabsById = getArraySortedById($disabs, "HostId");
//$regionsByZip = getArraySortedById($zips, "zip");
$peopleByPersonId = getArraySortedById($people, "PersonId");
$peopleByRelateId = getArraySortedById($people, "r_person_id");
endProfileRecord("getSortedArray");

startProfileRecord("hostPrinting");

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

 
	if ($hostRow["SleepingBagId"] == 2) {
		$SleepingBagAbv = "SBA ";  
	} elseif ($hostRow["SleepingBagId"] == 3) {
		$SleepingBagAbv = "SBN ";  
	}  else {
		$SleepingBagAbv = ""; 
	}
	
	if ($hostRow["SmokingId"] == 1) {
		$SmokingAbv = "HSI ";  
	} elseif ($hostRow["SmokingId"] == 2) {
		$SmokingAbv = "SOK ";  
	} elseif ($hostRow["SmokingId"] == 3) {
		$SmokingAbv = "NIS "; 
	} elseif ($hostRow["SmokingId"] == 4) {
		$SmokingAbv = "NSA ";
	}  else {
		$SmokingAbv = ""; 
	}
	
	if ($hostRow["WantsTravelers"] == "t") {
	    $WantsMoreAbv = "WMT "; 
	} else {
	    $WantsMoreAbv = "";		
	}
	
	if ($hostRow["HostTypeId"] == 1) {
		$HostTypeAbv = "2n";
	} else if ($hostRow["HostTypeId"] == 2) {
	    $HostTypeAbv = "1d";	
	} else if ($hostRow["HosttypeId"] == 3) {
	    $HostTypeAbv = "NOT HOSTING";	
	} else {
		$HostTypeAbv = "";
	}
	
	if ($hostRow["FamiliesWelcome"] == 't') {
	    $familyAbv = " (FAM)";			 
	} else {
	    $familyAbv = "";
	}

	
	if (!is_null($hostRow["regionname"])) {
	    $stateOrRegion = $hostRow["regionname"] . " (" . $hostRow["State"] . ")";
	} else {
	    $stateOrRegion = $hostRow["state_full_name"];	
	}
	
    $state = $hostRow["state_full_name"];
	
	$newStateOrRegion = $stateOrRegion;
	$newState = $state;
	
	if ($firstEntry) {
	    newHostPage();
		newBlock();
		$firstEntry = false;
		$pdf->TOC_Entry("Host List", 0);
	}
	
	if (($newStateOrRegion != $oldStateOrRegion) && ($blocksDisplayed != 0)){
	    newHostPage();
		newBlock();	
     }	
	
	if ($newState != $oldState) {
		$pdf->TOC_Entry($newState, 1);	
	}
	
	if ($newStateOrRegion != $oldStateOrRegion) {
		if (!is_null($hostRow["regionname"])) {
    		$pdf->TOC_Entry($newStateOrRegion, 2);
		}
	}

	   // Count up blocks displayed
   if ($blocksDisplayed == 3) {	
		newHostPage();
	} 
	newBlock();
	$blocksDisplayed++;
	
	
	// Assign a page no to this host's peopleIndex entry.
	$peopleIndex[$hostRow["HostId"]] = array();
	$peopleIndex[$hostRow["HostId"]]["LastName"] = $hostRow["LastName"];
	$peopleIndex[$hostRow["HostId"]]["FirstName"] = $hostRow["FirstName"];	
	$peopleIndex[$hostRow["HostId"]]["IndexNo"] = $pdf->_numPageNum;
    
	// Assign a page no to this host's peopleIndex entry.
	$cityIndex[$hostRow["HostId"]] = array();
	$cityIndex[$hostRow["HostId"]]["City"] = $hostRow["City"];
	$cityIndex[$hostRow["HostId"]]["State"] = $hostRow["State"];	
	$cityIndex[$hostRow["HostId"]]["IndexNo"] = $pdf->_numPageNum;
	
 
    startProfileRecord("col1");
	// Print column 1.
	
	$pdf->Cell($colW,5,$hostRow["State"] . " " . $hostRow["Zip"], 0, 1);
    $pdf->Cell($colW,5,$hostRow["City"], 0, 1);
	
    if ($hostRow["PrivateAddress"] == "t") {
	    $pdf->Cell($colW, 5, "*PRIVATE ADDRESS*", 0, 1);	
	} else {
	    $pdf->Cell($colW, 5, $hostRow["Address1"], 0, 1);	    	
	}
	
    $pdf->MultiCell($colW-10,5,$hostRow["RefLocationMiles"] . " " . $hostRow["RefLocationCardinalPoints"] . " from " . $hostRow["RefLocationDescription"], 0, 1);
	
	$hostPets = $petsById[$hostRow["HostId"]];
	
    $pdf->Cell($colW, 5, "PETS: " . getHostPetsString($hostPets), 0, 1);
	
	$hostDisabs = $disabsById[$hostRow["HostId"]];
    $pdf->Cell($colW, 5, "DISAB: " . getHostDisabsString($hostDisabs), 0, 1);	
	
    $pdf->Cell($colW, 5, $hostRow["MaxGuests"] . "G" . $familyAbv, 0, 1);
	
	$pdf->Cell($colW, 5, $HostTypeAbv . "(+" . $hostRow["ExtendedDays"] . "d): " . $hostRow["AdvNoticeRequired"] . "dn / " . $hostRow["AdvNoticeRecommend"] . "da", 0, 1);
    $pdf->Cell($colW, 5, $SleepingBagAbv . $WantsMoreAbv . $SmokingAbv, 0, 1);
	
	if (!is_null($hostRow["NotAvailDateFrom"])) {
		
		$today = strtotime(date("Y-m-d"));
		$toDate = strtotime($hostRow["NotAvailDateTo"]);
		
		if ($today < $toDate) {
	        $pdf->Cell($colW, 5, "NA: " . $hostRow["nadff"] . "-" . $hostRow["nadtf"], 0, 1);	
		}
	}
	
	endProfileRecord("col1");
	
	startProfileRecord("col2");
	// Print column 2.
	newCol();
	
	if (array_key_exists($hostRow["PersonId"], $peopleByPersonId) ){
	    $mainHost = $peopleByPersonId[$hostRow["PersonId"]][0];
	    $pdf->SetFont($font,'b',$fontSize);	
	    $pdf->SetX($currentColX);	
	    $pdf->Cell($colW, 5, $mainHost["FirstName"] . " " . $mainHost["LastName"] . " (" . $mainHost["p_age"] .", " . $mainHost["Gender"] .")", 0, 1);	
	    $pdf->SetX($currentColX);	
        $pdf->MultiCell($colW, 5, $mainHost["Occupation"], 0, 1);		
	}

    if (array_key_exists($hostRow["PersonId"], $peopleByRelateId) ) {
		$hostPeople = $peopleByRelateId[$hostRow["PersonId"]];
		
		
		$pdf->SetFont($font, '', $fontSize);
			
		for ($i = 0; $i < min(sizeof($hostPeople), 3); $i++) {	
			
			$pdf->SetX($currentColX);	
			$personString = $hostPeople[$i]["FirstName"] . " " . $hostPeople[$i]["LastName"] . " (" . $hostPeople[$i]["p_age"] .", " . $hostPeople[$i]["Gender"] .")";
		    
			if ( ($hostPeople[$i]["Occupation"] != "") || ($hostPeople[$i]["RelationshipDefinition"] != "") ) {
	    		$personString .= " " . limitedString($hostPeople[$i]["Occupation"] , 43). " (" . $hostPeople[$i]["RelationshipDefinition"] .")";			
			}
			
			
			$pdf->MultiCell($colW, 5, $personString, 0, 1);	
			$pdf->SetX($currentColX);	
				
		}
		
	}
	
	$pdf->SetFont($font, '', $fontSize);
    $pdf->SetX($currentColX);
	$pdf->MultiCell($colW, 5, "Mem: " . limitedString($hostRow["Memberships"], 150), 0, 1);
    
	endProfileRecord("col2");
	
	startProfileRecord("col3");

	// Print column 3.
	
	$hostLangs = $langsById[$hostRow["HostId"]];
	$hostLangString = getHostLangString($hostLangs);
	
	$hostEmails = $emailsById[$hostRow["PersonId"]];
	
    newCol();
	$pdf->SetX($currentColX);	
	if (sizeof($hostLangs) != 0) {
	    $pdf->Cell($colW, 5, "Lang: " . $hostLangString, 0, 1);	
	}

    for ($i = 0; $i < sizeof($hostEmails); $i++) {
		$pdf->SetX($currentColX);		
		
		if ($hostEmails[$i]["Private"] == "t") {							
		} else { 
		    $pdf->Cell($colW, 5, $hostEmails[$i]["Email"] . "(". $hostEmails[$i]["EmailCategory"].")", 0, 1);	
		}
	}

	$hostPhones = $phonesById[$hostRow["PersonId"]];	

    for ($i = 0; $i < sizeof($hostPhones); $i++) {
		$pdf->SetX($currentColX);		
		
		if ($hostPhones[$i]["Private"] == "t") {							
		} else { 
		    $pdf->Cell($colW, 5, "(".$hostPhones[$i]["AreaCode"].")" . $hostPhones[$i]["Phone"] . "(". $hostPhones[$i]["PhoneCategory"].")", 0, 1);	
		}
	}
	
	$pdf->SetX($currentColX);
    $pdf->MultiCell($colW, 5, "LV: " . limitedString(removeSpaceHogs(abvNations($hostRow["LivedIn"])), 150), 0, 1);
	$pdf->SetX($currentColX);
    $pdf->MultiCell($colW, 5, "TV: " . limitedString(removeSpaceHogs(abvNations($hostRow["TraveledIn"])), 150), 0, 1);
	
	endProfileRecord("col3");
	
/*	$areaGoodiesModified = limitedString($hostRow["AreaGoodies"], 300);
	$areaGoodiesModified = str_replace("\n", " ", $areaGoodiesModified);
	//$areaGoodiesModified = str_replace("\t", " ", $areaGoodiesModified);
	//$areaGoodiesModified = preg_replace('/\t+/', '', $areaGoodiesModified);
	$areaGoodiesModified = preg_replace("/\s+/", " ", $areaGoodiesModified);*/

	
	$notesModified = limitedString(removeSpaceHogs($hostRow["NotesForGuests"]), 600);
	$areaGoodiesModified = limitedString(removeSpaceHogs($hostRow["AreaGoodies"]), 600);
	$interestsModified = limitedString(removeSpaceHogs($hostRow["Interests"]), 600);
	
	
	startProfileRecord("bottomCol");
    // Display the long story.
    $pdf->SetFont($font,'I',$fontSize);
    $pdf->setY($blockOriginY+60);
	$pdf->SetX(5);
	$pdf->MultiCell($colW*2.85, 4, $notesModified . " | Why: " . $areaGoodiesModified . " | Int: " . $interestsModified, 0, 1);	
/*	$pdf->SetX(5);	
	$pdf->MultiCell($colW*2.75, 4, "Why: " . $areaGoodiesModified, 0, 1);	
	$pdf->SetX(5);
	$pdf->MultiCell($colW*2.75, 4, "Int: " . $interestsModified, 0, 1);*/

	
    $pdf->SetFont($font,'',$fontSize);
	endProfileRecord("bottomCol");

 
		$oldStateOrRegion = $stateOrRegion;
		$oldState = $state;
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