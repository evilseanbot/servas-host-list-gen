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

function newPage($header, $style, $clearBlocksDisplayed = false) {
	global $pdf, $pageNoOnLeft, $blocksDisplayed;
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
	$pdf->SetX(($style["headerW"]) - ($pdf->GetStringWidth($header)/2) );		
	$pdf->Cell(0, 10, $header, 0, 1);	
	$pdf->Rect(15, 10, $style["pageW"], 0);	

	if ($clearBlocksDisplayed) {
		$blocksDisplayed = 0;
	}
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
    $mappedItems = array_keys($mappedSymbols);

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

function printIndex($index, $style, $firstName, $secondName, $title, $enforceUnique = false) {
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

    newPage($title, $style);
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
            	newPage($title, $style);
            }
	    }
    }
}

function printTimeStamp($pdf, $style) {
    $timeStampX = 80;
    $timeStampY = 250;

	$pdf->SetFont($style["stdFont"], '' , $style["stdFontSize"]+2);
	$pdf->SetY($timeStampY);
	$pdf->SetX($timeStampX);
    date_default_timezone_set('UTC');		
    $pdf->Cell($style["colW"], 5, "Updated: " . date("F j, Y, g:i a") . " UTC", 0, 1);	
	$pdf->SetFont($style["stdFont"], '' , $style["stdFontSize"]);	
}

?>