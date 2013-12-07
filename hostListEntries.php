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
	
	$peopleIndex = addEntryToIndex($peopleIndex, $hostRow, "FirstName", "LastName");
	$cityIndex = addEntryToIndex($cityIndex, $hostRow, "City", "State");
	
    printHostEntryCol1($hostRow, $style);
    printHostEntryCol2($hostRow, $peopleByPersonId);
    printHostEntryCol3($hostRow);
	printHostEntryBottomCol($hostRow);

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

?>