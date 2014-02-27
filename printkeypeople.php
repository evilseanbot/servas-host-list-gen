<?php
function printKeyPeople() {
    global $pdf, $font, $fontSize, $emailsById, $phonesById;
    $style = array("colW" => 70, "bottomColW" => 199, "headerW" => 105, "blockH" => 300, "pageW" => 170, "stdFontSize" => 10, "stdFont" => "Times");
    newPage("Interviewers", $style);
	$pdf->startPageNums();
    $pdf->TOC_Entry("Interviewer list", 0);
	
    $keyPeopleQuery = "
    SELECT *, 'FALSE' is_area_rep
    FROM 
        acservas.\"S_Person\" p,
        acservas.\"S_Address\" a,
        acservas.full_state_names fsn
    WHERE
        p.\"PersonId\" = a.\"PersonId\" AND
        a.\"AddressCategoryId\" = '1' AND
        a.\"State\" = fsn.state_abv AND
		p.\"PersonId\" in (SELECT \"PersonId\" FROM acservas.\"S_P_Category\" WHERE \"P_CategoryDefinitionId\" = '4') AND
		p.\"PersonId\" not in (SELECT \"PersonId\" FROM acservas.\"S_P_Category\" WHERE \"P_CategoryDefinitionId\" = '7')
   UNION
	    SELECT *, 'TRUE' is_area_rep
    FROM 
        acservas.\"S_Person\" p,
        acservas.\"S_Address\" a,
        acservas.full_state_names fsn
    WHERE
        p.\"PersonId\" = a.\"PersonId\" AND
        a.\"AddressCategoryId\" = '1' AND
        a.\"State\" = fsn.state_abv AND
		p.\"PersonId\" in (SELECT \"PersonId\" FROM acservas.\"S_P_Category\" WHERE \"P_CategoryDefinitionId\" = '4') AND
		p.\"PersonId\" in (SELECT \"PersonId\" FROM acservas.\"S_P_Category\" WHERE \"P_CategoryDefinitionId\" = '7') 
ORDER BY 
        state_full_name, is_area_rep DESC, \"LastName\", \"FirstName\"
    ";
    $keyPeopleResult = pg_query($keyPeopleQuery);
	    
	$newState = "";
	$oldState = "";
	
	$linesOnPage = 0;
	
    for ($i = 0; $personRow = pg_fetch_array($keyPeopleResult); $i++) {	
	    if ($linesOnPage > 50) {
			newPage("Interviewers", $style);
			$linesOnPage = 0;
		}
	
	    $newState = $personRow["State"];
		
		if ($newState != $oldState) {
		    $pdf->Cell(100, 5, "", 0, 1);
		    $pdf->SetFont($font, 'b', $fontSize);
			$pdf->Cell(100, 5, $personRow["state_full_name"], 0, 1);
			$pdf->SetFont($font, '', $fontSize);
			
			$linesOnPage += 2;
		}
		
		if ($personRow["is_area_rep"] == 'TRUE') {
            $otherRole = "(Area Representative)";
        } else {
            $otherRole = "";
		}
	
	    $personListing = $personRow["FirstName"] . " " . 
	        $personRow["LastName"] . " " . 
	        $otherRole . " " . 
	        $emailsById[$personRow["PersonId"]][0]["Email"] . " " . 
	        $phonesById[$personRow["PersonId"]][0]["AreaCode"] . "-" . 
	        $phonesById[$personRow["PersonId"]][0]["Phone"];	
	
        $pdf->Cell(100, 5, $personListing, 0, 1);
		
		$oldState = $personRow["State"];
		$linesOnPage++;
    }
}
?>