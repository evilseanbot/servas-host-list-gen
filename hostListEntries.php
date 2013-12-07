<?php
function printHostEntry($hostRow, $style, $peopleByPersonId) {
    global $firstEntry, $pdf, $oldStateOrRegion, $oldState, $blocksDisplayed, $peopleIndex, 
    $cityIndex, $currentColX, $peopleByRelateId, $blockOriginY;

	
	if (!is_null($hostRow["regionname"])) {
	    $stateOrRegion = $hostRow["regionname"] . " (" . $hostRow["State"] . ")";
	} else {
	    $stateOrRegion = $hostRow["state_full_name"];	
	}
	
    $state = $hostRow["state_full_name"];
	
	$newStateOrRegion = $stateOrRegion;
	$newState = $state;
	
	if ($firstEntry) {
	    newPage($stateOrRegion, $style, true);
		newBlock();
		$firstEntry = false;
		$pdf->TOC_Entry("Host List", 0);
	}
	
	if (($newStateOrRegion != $oldStateOrRegion) && ($blocksDisplayed != 0)){
	    newPage($stateOrRegion, $style, true);
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
		newPage($stateOrRegion, $style, true);
	} 
	newBlock($style);
	$blocksDisplayed++;
	
	$peopleIndex = addEntryToIndex($peopleIndex, $hostRow, "FirstName", "LastName");
	$cityIndex = addEntryToIndex($cityIndex, $hostRow, "City", "State");
	
    printHostEntryCol1($hostRow, $style);
    printHostEntryCol2($hostRow, $style, $peopleByPersonId);
    printHostEntryCol3($hostRow, $style);
	printHostEntryBottomCol($hostRow, $style);

	$oldStateOrRegion = $stateOrRegion;
	$oldState = $state;
	
}

function printHostEntryCol1($hostRow, $style) {
    global $pdf, $petsById, $disabsById;

    $mappedSymbols = array("SleepingBagId" => array("2" => "SBA ", "3" => "SBN "),
                           "SmokingId" => array("1" => "HSI ", "2" => "SOK ", "3" => "NIS ", "4" => "NSA "),
                           "WantsTravelers" => array("t" => "WMT "),
                           "HostTypeId" => array("1" => "2n", "2" => "1d", "3" => "NOT HOSTING"),
                           "FamiliesWelcome" => array("t" => "(FAM)"),                           
    	             );

    $translatedFields = translateFields($hostRow, $mappedSymbols);
	
	$pdf->Cell($style["colW"], 5, $hostRow["State"] . " " . $hostRow["Zip"], 0, 1);
    $pdf->Cell($style["colW"], 5, $hostRow["City"], 0, 1);
	
    if ($hostRow["PrivateAddress"] == "t") {
	    $pdf->Cell($style["colW"], 5, "*PRIVATE ADDRESS*", 0, 1);	
	} else {
	    $pdf->Cell($style["colW"], 5, $hostRow["Address1"], 0, 1);	    	
	}
	
    $pdf->MultiCell($style["colW"]-10,5,$hostRow["RefLocationMiles"] . " " . $hostRow["RefLocationCardinalPoints"] . " from " . $hostRow["RefLocationDescription"], 0, 1);
	
	$hostPets = $petsById[$hostRow["HostId"]];
    $pdf->Cell($style["colW"], 5, "PETS: " . getHostPetsString($hostPets), 0, 1);
	
	$hostDisabs = $disabsById[$hostRow["HostId"]];
    $pdf->Cell($style["colW"], 5, "DISAB: " . getHostDisabsString($hostDisabs), 0, 1);	
    $pdf->Cell($style["colW"], 5, $hostRow["MaxGuests"] . "G" . " " . $translatedFields["FamiliesWelcome"] . " ", 0, 1);
	$pdf->Cell($style["colW"], 5, $translatedFields["HostTypeId"] . "(+" . $hostRow["ExtendedDays"] . "d): " . $hostRow["AdvNoticeRequired"] . "dn / " . $hostRow["AdvNoticeRecommend"] . "da", 0, 1);
    $pdf->Cell($style["colW"], 5, $translatedFields["SleepingBagId"] . $translatedFields["WantsTravelers"] . $translatedFields["SmokingId"], 0, 1);    

    // Display notice if the host is not available from a certain date.
	if (!is_null($hostRow["NotAvailDateFrom"])) {
		
		$today = strtotime(date("Y-m-d"));
		$toDate = strtotime($hostRow["NotAvailDateTo"]);
		
		if ($today < $toDate) {
	        $pdf->Cell($style["colW"], 5, "NA: " . $hostRow["nadff"] . "-" . $hostRow["nadtf"], 0, 1);	
		}
	}	
}

function printHostEntryCol2($hostRow, $style, $peopleByPersonId) {
	global $peopleByRelateId, $currentColX, $pdf;

	startProfileRecord("col2");
	// Print column 2.
	newCol($style);

	if (array_key_exists($hostRow["PersonId"], $peopleByPersonId) ){
	    
	    $mainHost = $peopleByPersonId[$hostRow["PersonId"]][0];
	    $pdf->SetFont($style["stdFont"],'b',$style["stdFontSize"]);	
	    $pdf->SetX($currentColX);	
	    $pdf->Cell($style["colW"], 5, $mainHost["FirstName"] . " " . $mainHost["LastName"] . " (" . $mainHost["p_age"] .", " . $mainHost["Gender"] .")", 0, 1);	
	    $pdf->SetX($currentColX);	
        $pdf->MultiCell($style["colW"], 5, $mainHost["Occupation"], 0, 1);		
        
	}
    
    if (array_key_exists($hostRow["PersonId"], $peopleByRelateId) ) {
		$hostPeople = $peopleByRelateId[$hostRow["PersonId"]];
		
		
		$pdf->SetFont($style["stdFont"], '', $style["stdFontSize"]);
			
		for ($i = 0; $i < min(sizeof($hostPeople), 3); $i++) {	
			
			$pdf->SetX($currentColX);	
			$personString = $hostPeople[$i]["FirstName"] . " " . $hostPeople[$i]["LastName"] . " (" . $hostPeople[$i]["p_age"] .", " . $hostPeople[$i]["Gender"] .")";
		    
			if ( ($hostPeople[$i]["Occupation"] != "") || ($hostPeople[$i]["RelationshipDefinition"] != "") ) {
	    		$personString .= " " . limitedString($hostPeople[$i]["Occupation"] , 43). " (" . $hostPeople[$i]["RelationshipDefinition"] .")";			
			}
			
			
			$pdf->MultiCell($style["colW"], 5, $personString, 0, 1);	
			$pdf->SetX($currentColX);	
				
		}
		
	}
	
	$pdf->SetFont($style["stdFont"], '', $style["stdFontSize"]);
    $pdf->SetX($currentColX);
	$pdf->MultiCell($style["colW"], 5, "Mem: " . limitedString($hostRow["Memberships"], 150), 0, 1);	
}

function printHostEntryCol3($hostRow, $style) {
    global $pdf, $emailsById, $langsById, $langsById, $phonesById, $currentColX;

	startProfileRecord("col3");
	
	$hostLangs = $langsById[$hostRow["HostId"]];
	$hostLangString = getHostLangString($hostLangs);
	
	$hostEmails = $emailsById[$hostRow["PersonId"]];
	
    newCol($style);
	$pdf->SetX($currentColX);	
	if (sizeof($hostLangs) != 0) {
	    $pdf->Cell($style["colW"], 5, "Lang: " . $hostLangString, 0, 1);	
	}

    for ($i = 0; $i < sizeof($hostEmails); $i++) {
		$pdf->SetX($currentColX);		
		
		if ($hostEmails[$i]["Private"] == "t") {							
		} else { 
		    $pdf->Cell($style["colW"], 5, $hostEmails[$i]["Email"] . "(". $hostEmails[$i]["EmailCategory"].")", 0, 1);	
		}
	}

	$hostPhones = $phonesById[$hostRow["PersonId"]];	

    for ($i = 0; $i < sizeof($hostPhones); $i++) {
		$pdf->SetX($currentColX);		
		
		if ($hostPhones[$i]["Private"] == "t") {							
		} else { 
		    $pdf->Cell($style["colW"], 5, "(".$hostPhones[$i]["AreaCode"].")" . $hostPhones[$i]["Phone"] . "(". $hostPhones[$i]["PhoneCategory"].")", 0, 1);	
		}
	}
	
	$pdf->SetX($currentColX);
    $pdf->MultiCell($style["colW"], 5, "LV: " . limitedString(removeSpaceHogs(abvNations($hostRow["LivedIn"])), 150), 0, 1);
	$pdf->SetX($currentColX);
    $pdf->MultiCell($style["colW"], 5, "TV: " . limitedString(removeSpaceHogs(abvNations($hostRow["TraveledIn"])), 150), 0, 1);	
}

function printHostEntryBottomCol($hostRow, $style) {
    global $blockOriginY, $pdf;

	$notesModified = limitedString(removeSpaceHogs($hostRow["NotesForGuests"]), 600);
	$areaGoodiesModified = limitedString(removeSpaceHogs($hostRow["AreaGoodies"]), 600);
	$interestsModified = limitedString(removeSpaceHogs($hostRow["Interests"]), 600);	
	
    // Display the long story.
    $pdf->SetFont($style["stdFont"],'I',$style["stdFontSize"]);
    $pdf->setY($blockOriginY+60);
	$pdf->SetX(5);
	$pdf->MultiCell($style["bottomColW"], 4, $notesModified . " | Why: " . $areaGoodiesModified . " | Int: " . $interestsModified, 0, 1);	
	
    $pdf->SetFont($style["stdFont"], '' ,$style["stdFontSize"]);
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
    $mappedSymbols = array("PetId" => array("1" => "D", "2" => "C", "3" => "B", "4" => "O"));
    foreach ($hostPets as $hostPet) {
    	$petsString .= translateFields($hostPet, $mappedSymbols)["PetId"];
    }
	return $petsString;
}

function getHostDisabsString($hostDisabs) {
    $disabsString = "";
    $mappedSymbols = array("DisabilityId" => array("1" => "H", "2" => "V", "3" => "G", "4" => "C", "5" => "W"));
    foreach ($hostDisabs as $hostDisab) {
    	$disabsString .= translateFields($hostDisab, $mappedSymbols)["DisabilityId"];
    }
	return $disabsString;
}

function newBlock($style) {
	global $pdf, $blockOriginY, $currentColX;
    $blockOriginY = $pdf->getY();	
	$currentColX = 0;
	$pdf->Rect(15, $blockOriginY, $style["pageW"], 0);	
}

function newCol($style) {
	global $pdf, $blockOriginY, $currentColX;
    $pdf->setY($blockOriginY);
	$currentColX += $style["colW"];
}

?>