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
	global $pdf, $pageNoOnLeft, $colW, $pageW;
	$pdf->AddPage();
	
	if ($pageNoOnLeft == true) {
	   $pageNoOnLeft = false;	
	} else {
	   $pageNoOnLeft = true;	
	}

	$pdf->setY(0);
	if (!$pageNoOnLeft) {
		$pdf->SetX(200);
	}
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
	$string = preg_replace("/\s+/", " ", $string);
	return $string;
}

function sortedArrayFromSQL($queryName, $id) {
    $string = file_get_contents("sql/" . $queryName, true);
    return getArraySortedById(pg_fetch_all(pg_query($string)), $id);
}

function addEntryToIndex($index, $hostRow, $firstName, $secondName) {
	global $pdf;
	$index[$hostRow["HostId"]] = array();
	$index[$hostRow["HostId"]][$firstName] = $hostRow[$firstName];
	$index[$hostRow["HostId"]][$secondName] = $hostRow[$secondName];	
	$index[$hostRow["HostId"]]["IndexNo"] = $pdf->_numPageNum;

	return $index;
}

function translateFields ($row, $mappedSymbols) {
    $mappedItems = array_keys($mappedSymbols); //$mappedSymbols.keys(); //["SleepingBagId", "WantsTravelers", "SmokingId", "HostTypeId", "FamiliesWelcome"];

    $translatedFields = [];
    foreach ($mappedItems as $mappedItem) {
	    if (array_key_exists($row[$mappedItem], $mappedSymbols[$mappedItem])) {
	        $translatedFields[$mappedItem] = $mappedSymbols[$mappedItem][$row[$mappedItem]];
	    } else {
	    	$translatedFields[$mappedItem] = "";
	    }
	}
	return $translatedFields;
}

function printHostEntryCol2($hostRow, $peopleByPersonId) {
	global $peopleByRelateId, $colW, $currentColX, $font, $fontSize, $pdf;

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

}

function printHostEntryCol3($hostRow) {
    global $pdf, $emailsById, $langsById, $langsById, $phonesById, $currentColX, $colW;

	startProfileRecord("col3");
	
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
}

function printHostEntryBottomCol($hostRow) {
    global $font, $fontSize, $blockOriginY, $colW, $pdf;

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
}

function addPagesFromPDF($pdfName, $numOfPages, $startingPage = 1) {
	global $pdf;
	$pdf->setSourceFile('addonPdfs/' . $pdfName); 
	for ($i = $startingPage; $i < $startingPage + $numOfPages; $i++) { 
		$page = $pdf->importPage($i, '/MediaBox'); 
		$pdf->addPage(); 
		$pdf->useTemplate($page, 0, 0, 210); 
		$pdf->_numPageNum++;
     }
}

function printIndex($index, $firstName, $secondName, $title, $enforceUnique = false) {
    global $pdf;

	foreach ($index as $key => $row) {
	    $firstNamePages[$key]  = $row[$firstName];
	    $secondNamePages[$key] = $row[$secondName];
	}

    array_multisort($firstNamePages, SORT_ASC, $secondNamePages, SORT_ASC, $index);

    $uniqueIndex = [];

    if ($enforceUnique) {
		for ($i = 0; $i < sizeof($index); $i++) {
		    if ($i > 0) {
		    	if ( ($index[$i][$firstName] != $index[$i-1][$firstName]) || ($index[$i][$secondName] != $index[$i-1][$secondName]) ){
			        array_push($uniqueIndex, $index[$i]);	
				}
			} else {
			    array_push($uniqueIndex, $index[$i]);		
			}
		}

		$index = $uniqueIndex;
    }

    newPage($title);
    $pdf->TOC_Entry($title, 0);

    
    
    $currentX = 0;
    $entriesInCol = 0;
    $currentLineY = 0;    
    $entriesPerCol = 50;
    
    foreach ($index as $indexEntry) {
    	$pdf->SetX($currentX);
    	$currentLineY = $pdf->GetY();

    	
    	$nameString = $indexEntry[$firstName] . ", " . $indexEntry[$secondName];
    	$pdf->Cell(50, 5, $nameString, 0, 1);
	    $pdf->Rect($currentX + $pdf->GetStringWidth($nameString) + 2, $currentLineY+4, 55 - ($pdf->GetStringWidth($nameString)) - 2, 0);
	    $pdf->SetY($currentLineY);
	    $pdf->SetX($currentX + 55);
	    $pdf->Cell(50, 5, $indexEntry["IndexNo"], 0, 1);

	    $entriesInCol++;
	    if ($entriesInCol > $entriesPerCol) {
            $entriesInCol = 0;
            $pdf->SetY(10);
            $currentX += 65;

            if ($currentX > 160) {
            	$currentX = 0;
            	newPage($title);
            }
	    }
    }
}

function printTimeStamp() {
    global $pdf, $font, $fontSize, $colW;

	$pdf->SetFont($font,'',$fontSize+2);
	$pdf->SetY(250);
	$pdf->SetX(80);
    date_default_timezone_set('UTC');		
    $pdf->Cell($colW, 5, "Updated: " . date("F j, Y, g:i a") . " UTC", 0, 1);	
	$pdf->SetFont($font,'',$fontSize);	
}

?>