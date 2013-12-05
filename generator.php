<?php

error_reporting(E_ALL);

require('../../../../cl/fpdf/fpdf.php');
require('../../FPDI/fpdi.php');
include "../../functions/passprotect.php";
include "abvNations.php";
include "printkeypeople.php";
include "testing.php";
include "toc.php";

// Switch these security measures around when uploading it to the public side


include "passwordfile.php";

session_start(); 
//authUser($_SESSION[User]);

function authUser($user) {
	$QueryActiveApproved = "
	SELECT * 
	FROM acservas.\"S_Person\" p LEFT JOIN acservas.\"S_Host\" h USING (\"PersonId\") 
	WHERE \"ActiveMember\" = TRUE AND 
	    (\"HostStatus\" = 'A' OR 
		 \"PersonId\" in (SELECT \"PersonId\" FROM acservas.\"S_P_Category\" WHERE \"P_CategoryDefinitionId\" in ('12', '13') ) 
		 OR \"PersonId\" in (SELECT \"PersonId\" FROM acservas.current_travelers)) AND 
	\"PersonId\" = '" . $user . "'";

	$ResultActiveApproved = pg_query($QueryActiveApproved);
	if (pg_num_rows($ResultActiveApproved) <= 0) {
	    echo "You are not authorized to download the host list.<br>
		The host list is downloadable by primary Hosts with active accounts (In good standing and have renewed thier memberships during the last renewal cycle) and primary Travelers with active LOIs.<br>
		Please review your account and make sure that you fit all criteria for authorization to download the host list.<br>
		Please contact the office at info@usservas.org if you have any questions <br>
		Thank you";
		exit();
	}    
}


function newBlock() {
	global $pdf, $blockOriginY, $currentColX, $colW, $blockH, $pageW;
    $blockOriginY = $pdf->getY();	
	$currentColX = 0;
	$pdf->Rect(15, $blockOriginY, $pageW, 0);	
}

function newCol() {
	global $pdf, $blockOriginY, $currentColX, $colW;
    $pdf->setY($blockOriginY);
	$currentColX += $colW;
}

function newPage($header) {
	global $pdf, $pageNo, $pageNoOnLeft, $colW, $pageW;
	$pdf->AddPage();
	
	$pageNo++;
	if ($pageNoOnLeft == true) {
	   $pageNoOnLeft = false;	
	} else {
	   $pageNoOnLeft = true;	
	}

	$pdf->setY(0);
	if (!$pageNoOnLeft) {
		$pdf->SetX(200);
	}
	//$pdf->Cell(10, 10, $pageNo, 0, 1);		
	$pdf->Cell(10, 10, $pdf->numPageNo(), 0, 1);
	$pdf->setY(0);
	$pdf->SetX(($colW*1.5) - ($pdf->GetStringWidth($header)/2) );		
	$pdf->Cell(0, 10, $header, 0, 1);	
	$pdf->Rect(15, 10, $pageW, 0);	
}

function newHostPage() {
	global $hostRow, $blocksDisplayed, $stateOrRegion;

	$blocksDisplayed = 0;
	newPage($stateOrRegion);
}



function getArraySortedById($multiSQL, $idType) {
	$arrayFromSQL = array();
	for ($i = 0; $i < sizeof($multiSQL); $i++) {
		$rowId = $multiSQL[$i][$idType];
	    if (!array_key_exists($rowId, $arrayFromSQL)) {
		   $arrayFromSQL[$rowId] = array();
	    }
	
		array_push($arrayFromSQL[$rowId], $multiSQL[$i]);
	}
	return $arrayFromSQL;	
}



function getHostLangString($hostLangs) {
    
	$langString = $hostLangs[0]["LanguageCode"] . "(" . $hostLangs[0]["LanguageFluency"] .")";
	
	for ($i = 1; $i < sizeof($hostLangs); $i++) {
	
    	$langString = $langString . ", " . $hostLangs[$i]["LanguageCode"] . "(" . $hostLangs[$i]["LanguageFluency"] . ")";						 
	}
	
	return $langString;
}

function getHostPetsString($hostPets) {
    $petsString = "";
	
	for ($i = 0; $i < sizeof($hostPets); $i++) {
	    if ($hostPets[$i]["PetId"] == "1") {
		    $petsString .= "D";	
		} else if ($hostPets[$i]["PetId"] == "2") {
		    $petsString .= "C";	
		} else if ($hostPets[$i]["PetId"] == "3") {
		    $petsString .= "B";	
		} else if ($hostPets[$i]["PetId"] == "4") {
		    $petsString .= "O";	
		}
	}
	
	return $petsString;
}

function getHostDisabsString($hostDisabs) {
    $disabsString = "";
	
	for ($i = 0; $i < sizeof($hostDisabs); $i++) {
	    if ($hostDisabs[$i]["DisabilityId"] == "1") {
		    $disabsString .= "H";	
		} else if ($hostDisabs[$i]["DisabilityId"] == "2") {
		    $disabsString .= "V";	
		} else if ($hostDisabs[$i]["DisabilityId"] == "3") {
		    $disabsString .= "G";	
		} else if ($hostDisabs[$i]["DisabilityId"] == "4") {
		    $disabsString .= "C";	
		}else if ($hostDisabs[$i]["DisabilityId"] == "5") {
		    $disabsString .= "W";	
		}
	}
	return $disabsString;
}


function limitedString ($origString, $limit) {
    if (strlen($origString) > $limit) {
	    $newString = substr($origString, 0, $limit);
		$newString .= "...";
		return $newString;
	} else {
	    return $origString;	
	}
}



function removeSpaceHogs($string) {
	$string = str_replace("\n", " ", $string);
	//$areaGoodiesModified = str_replace("\t", " ", $areaGoodiesModified);
	//$areaGoodiesModified = preg_replace('/\t+/', '', $areaGoodiesModified);
	$string = preg_replace("/\s+/", " ", $string);
	return $string;
}

startProfileRecord("all");
startProfileRecord("sql");



$peopleQuery = "
SELECT p.*, r.\"PersonId\" as r_person_id, date_part('year',age(p.\"BirthYear\")) as p_age, rd.\"RelationshipDefinition\"
FROM 
    acservas.\"S_Person\" p LEFT JOIN 
	acservas.\"S_P_Relationships\" r ON p.\"PersonId\" = r.\"RelatedPersonId\" LEFT JOIN 
	acservas.\"S_P_RelationshipDefinitions\" rd ON r.\"RelationshipDefinitionId\" = rd.\"RelationshipDefinitionId\" 
ORDER BY r.\"RelatedPersonId\" 
";

$peopleResult = pg_query ($peopleQuery);


$langQuery = "
SELECT l.*, lc.*
FROM acservas.\"S_H_Languages\" l,
     acservas.\"S_Host\" h,
	 acservas.\"S_H_LanguageCategories\" lc
WHERE h.\"HostId\" = l.\"HostId\" AND
      l.\"LanguageId\" = lc.\"LanguageId\"
";
$langResult = pg_query ($langQuery);

$emailQuery = "
SELECT e.*, ed.*
FROM acservas.\"S_Emails\" e,
     acservas.\"S_Email_CategoryDefinitions\" ed
WHERE e.\"EmailCategoryId\" = ed.\"EmailCategoryId\" 
";
$emailResult = pg_query ($emailQuery);

$phoneQuery = "
SELECT p.*, pd.*
FROM acservas.\"S_Phones\" p,
     acservas.\"S_Phone_CategoryDefinitions\" pd
WHERE p.\"PhoneCategoryId\" = pd.\"PhoneCategoryId\" 
";
$phoneResult = pg_query ($phoneQuery);


$petQuery = "
SELECT p.*, pc.*
FROM acservas.\"S_H_Pets\" p,
     acservas.\"S_H_PetCategories\" pc
WHERE p.\"PetId\" = pc.\"PetId\" 
";
$petResult = pg_query ($petQuery);

$disabQuery = "
SELECT d.*, dc.*
FROM acservas.\"S_H_Disabilities\" d,
     acservas.\"S_H_DisabilityCategories\" dc
WHERE d.\"DisabilityId\" = dc.\"DisabilityId\" 
";
$disabResult = pg_query ($disabQuery);

/*
$stateInput = $_GET["State"];
if (strlen($stateInput) == 2) {
   	
} else {
    $stateInput = "";	
}
*/

/*$regionZipQuery = "
SELECT *
FROM acservas.\"zipcounty\" zc, acservas.\"countyregion\" cr, acservas.regions r
WHERE cr.\"countyname\" = zc.\"county\" AND cr.\"regionid\" = r.\"regionid\"
";
$regionZipResult = pg_query ($regionZipQuery);*/


$hostQuery = "
SELECT *, to_char(h.\"NotAvailDateFrom\", 'MM/DD/YYYY') as nadff, 
          to_char(h.\"NotAvailDateTo\", 'MM/DD/YYYY') as nadtf
FROM acservas.\"S_Address\" a 
    LEFT JOIN acservas.zipcounty zc ON zc.zip = substring(a.\"Zip\" from 1 for 5)
	LEFT JOIN acservas.countyregion cr ON zc.county = cr.countyname AND zc.state = cr.state
	LEFT JOIN acservas.regions r ON r.regionid = cr.regionid,
    acservas.\"S_Person\" p,
	acservas.\"S_Host\" h,
    acservas.full_state_names fn
WHERE p.\"PersonId\" = a.\"PersonId\" AND
      p.\"PersonId\" = h.\"PersonId\" AND
      a.\"State\" = fn.state_abv AND
	  a.\"AddressCategoryId\" in ('1', '4') AND 
	  h.\"HostStatus\" in ('A', '') AND
	  p.\"ActiveMember\" = 'TRUE'
ORDER BY state_full_name, regionname, county, \"City\", \"Zip\"";
$hostResult = pg_query ($hostQuery);

$people = pg_fetch_all ($peopleResult);
$langs = pg_fetch_all ($langResult);
$emails = pg_fetch_all ($emailResult);
$phones = pg_fetch_all($phoneResult);
$pets = pg_fetch_all ($petResult);
$disabs = pg_fetch_all ($disabResult);
//$zips = pg_fetch_all ($regionZipResult);

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