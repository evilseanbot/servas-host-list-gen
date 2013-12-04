<?php

function abvNations($origText) {
	$fullName = array("Belgium", "Bolivia", "Brazil", "Canada", "Chile", "Columbia", "Costa Rica", "Czech Republic",  
		"Dominican Republic", "Ecuador", "Egypt", "El Salvador", "Estonia", "Fiji", "France", "Honduras", 
		"India", "Indonesia", "Ireland", "Israel", "Italy",  "Jamaica", "Japan", "Kenya",  
		"Latvia",  "Lithuania", "Madagascar", "Malawi", "Malaysia", "Mexico", "Monaco", "Mongolia", 
		"Morocco", "Myanmar", "Nepal", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", 
		"Norway", "Pakistan", "Panama", "Paraguay", "Peru", "Philippines", "Poland", "Portugal", 
		"Puerto Rico", "Qatar", "Reunion", "Romania", "Russia", "Rwanda", "Saudi Arabia", "Senegal", 
		"Serbia", "Seychelles", "Singapore", "Slovakia", "Slovenia", "South Africa",  "Spain", "Sri Lanka", 
		"Sudan", "Suriname", "Swaziland", "Sweden", "Switzerland", "Taiwan", "Tanzania", "Thailand", 
		"Togo", "Trinidad And Tobago", "Turkey", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", 
		"Uruguay", "Uzbekistan", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe");
	
	$abvName = array("bel", "bol", "bra", "can", "chl", "col", "cri", "cze", 
	"dom", "ecu", "egy", "slv", "est", "fji", "fra", "hnd", 
	"ind", "idn", "irl", "isr", "ita", "jam", "jpn", "ken", 
	"lva", "ltu", "mdg", "mwi", "mys", "mex", "mco", "mng", 
	"mor", "mmr", "npl", "nld", "nzl", "nic", "ner", "nga", 
	"nor", "pak", "pan", "pry", "per", "phl", "pol", "prt", 
	"pri", "qat", "reu", "rom", "rus", "rwa", "sau", "sen", 
	"srb", "syc", "sgp", "svk", "svn", "zaf", "esp", "lak", 
	"sdn", "sur", "swz", "swe", "che", "twn", "tza", "tha", 
	"tgo", "tto", "tur", "uga", "ukr", "are", "gbr", "usa", 
	"ury", "uzb", "ven", "vnm", "yem", "zmb", "zwe");
	$abvLength = 1;
	
	$newText = str_replace($fullName, $abvName, $origText);
	
	//for ($i = 0; $i < $abvLength; $i++) {
    //	str_replace($fullName[$i], $abvName[$i], $origText);
	//}
	
	return $newText;
}

?>