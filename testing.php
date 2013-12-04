<?php

function printProfiles() {
	global $pdf;
	
	$pdf->SetX(5);
	$pdf->Cell($colW, 5, "Diagnostic info: ", 0, 1);
	$pdf->SetX(5);
	$pdf->Cell($colW, 5, "Execution seconds for everything: " . getExecTime("all"), 0, 1);
	$pdf->SetX(5);
	$pdf->Cell($colW, 5, "Execution seconds for SQL: " . getExecTime("sql") . " %" . (getExecTime("sql") / getExecTime("all") * 100), 0, 1);
	$pdf->SetX(5);
	$pdf->Cell($colW, 5, "Execution seconds for host Printing: " . getExecTime("hostPrinting") . " %" . (getExecTime("hostPrinting") / getExecTime("all") * 100), 0, 1);
	$pdf->SetX(5);
	
	
	$pdf->Cell($colW, 5, "Execution seconds for Bottom Columns: " . getExecTime("bottomCol"), 0, 1);
	$pdf->SetX(5);
	$pdf->Cell($colW, 5, "Execution seconds for Column 1: " . getExecTime("col1"), 0, 1);
	$pdf->SetX(5);
	$pdf->Cell($colW, 5, "Execution seconds for Column 2: " . getExecTime("col2"), 0, 1);
	$pdf->SetX(5);
	$pdf->Cell($colW, 5, "Execution seconds for Column 3: " . getExecTime("col3"), 0, 1);
	$pdf->SetX(5);
	$pdf->Cell($colW, 5, "Execution seconds for getting sorted arrays: " . getExecTime("getSortedArray") . " %" . (getExecTime("getSortedArray") / getExecTime("all") * 100), 0, 1);
	$pdf->SetX(5);
	$pdf->Cell($colW, 5, "Execution seconds for old getting arrays (Lang, Pets, Disabilities): " . getExecTime("getArray"), 0, 1);
	$pdf->SetX(5);
	$pdf->Cell($colW, 5, "Execution seconds for getting host people: " . getExecTime("getHostPeople"), 0, 1);	
}

function getExecTime($section) {
    global $optProfile;
	$totalTime = 0;
	for ($i = 0; $i < sizeof($optProfile[$section]); $i++) {
	    $totalTime += $optProfile[$section][$i]["end"] - $optProfile[$section][$i]["start"];	
	}
	return $totalTime;
}

function startProfileRecord($section) {
    global $optProfile;	
	$newRecord = array();
	$newRecord["start"] = microtime(true);
	array_push($optProfile[$section], $newRecord);
}

function endProfileRecord($section) {
	global $optProfile;
	$optProfile[$section][sizeof($optProfile[$section])]["end"] = microtime(true);
}

$optProfile = array();
$optProfile["all"] = array();
$optProfile["sql"] = array();
$optProfile["bottomCol"] = array();
$optProfile["col1"] = array();
$optProfile["col2"] = array();
$optProfile["col3"] = array();
$optProfile["getArray"] = array();
$optProfile["arrayPaste"] = array();
$optProfile["arrayColumn"] = array();
$optProfile["getEmail"] = array();
$optProfile["getPhone"] = array();
$optProfile["getSortedArray"] = array();
$optProfile["getHostPeople"] = array();
$optProfile["hostPrinting"] = array();

?>