<?php
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

function sortedArrayFromSQL($queryName, $id) {
    if (!file_get_contents("sql/" . $queryName)) {
    	return "Error: File doesn't exist";
    }
    $string = file_get_contents("sql/" . $queryName, true);
    return getArraySortedById(pg_fetch_all(pg_query($string)), $id);
}

function printHostEntry($hostRow, $peopleByPersonId) {
    global $firstEntry, $pdf, $oldStateOrRegion, $oldState, $blocksDisplayed, $peopleIndex, $cityIndex, $colW, $font, $currentColX, $fontSize, $peopleByRelateId, $blockOriginY;

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
	
    printHostEntryCol1($hostRow);

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
	
	$notesModified = limitedString(removeSpaceHogs($hostRow["NotesForGuests"]), 600);
	$areaGoodiesModified = limitedString(removeSpaceHogs($hostRow["AreaGoodies"]), 600);
	$interestsModified = limitedString(removeSpaceHogs($hostRow["Interests"]), 600);
	
	
	startProfileRecord("bottomCol");
    // Display the long story.
    $pdf->SetFont($font,'I',$fontSize);
    $pdf->setY($blockOriginY+60);
	$pdf->SetX(5);
	$pdf->MultiCell($colW*2.85, 4, $notesModified . " | Why: " . $areaGoodiesModified . " | Int: " . $interestsModified, 0, 1);	
	
    $pdf->SetFont($font,'',$fontSize);
	endProfileRecord("bottomCol");

	$oldStateOrRegion = $stateOrRegion;
	$oldState = $state;
	
}

function printHostEntryCol1($hostRow) {
    global $pdf, $colW, $SleepingBagAbv, $WantsMoreAbv, $SmokingAbv;

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

}
?>