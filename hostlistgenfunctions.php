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
    return getArraySortedById(pg_fetch_all(pg_query(file_get_contents("sql/" . $queryName, true))), $id);
}

?>