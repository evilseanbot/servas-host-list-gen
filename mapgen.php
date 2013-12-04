<?php


include "../../functions/passprotect.php";

passprotect("we need a better logo");

error_reporting(-1);
$dbconn3 = pg_connect("dbname=bvb user=admin password=0n3r!n6");
// avg(to_number(latitude, '999D9999')) AS lat, avg(to_number(longitude, '999D9999'))

$markerQuery = "SELECT \"Latitude\", \"Longitude\", \"FirstName\", \"LastName\", \"Zip\"
                  FROM acservas.\"S_Person\" p,
				      acservas.\"S_Address\" a,
				      acservas.\"S_Host\" h
				  WHERE h.\"PersonId\" = p.\"PersonId\" AND
					  p.\"PersonId\" = a.\"PersonId\" AND 
					  a.\"AddressCategoryId\" in ('1', '4') AND 
					  h.\"HostStatus\" in ('A', '') AND
					  p.\"ActiveMember\" = 'TRUE' AND
					  a.\"PrivateAddress\" = 'FALSE' AND
					  a.\"Latitude\" is not NULL AND
					  a.\"Longitude\" is not NULL";

$markerResult = pg_query ($markerQuery);

?>
<style>
     .labels {
		border: 1px solid black;
		background-color: black;
    	color: white;
	 }

	 .contextBox {
	 	width: 500px;
	 	color: black;
	 }

	 #searchPanel {
	 	font-size: 40px;
	 	border: 1px solid gray;
	 	padding: 10px;
	 	margin: 10px;
	 }

	 #searchPanel input[type="text"]
     {
         font-size: 40px;
         width: 150px;
     }

     #howToUse {
         font-size: 15px;
     }
</style>
 
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script type="text/javascript" src="markerwithlabel.js"></script>
<script type="text/javascript">
    var map = {};
    var markers = [];
    function initMap() {
        var latLng = new google.maps.LatLng(37.1700, -119.7462);
        var homeLatLng = new google.maps.LatLng(37.1, -119.7);

        map = new google.maps.Map(document.getElementById('map_canvas'), {
            zoom: 7,
            center: latLng,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });

        google.maps.event.addListener(map, 'click', function() {
            if (infowindow) {
                infowindow.close();
            }
        });
	 
	     var image = "transdot.png";
     }
	 
	 var hosts = [];

<?php
for ($i = 0; $markerRow = pg_fetch_array($markerResult); $i++) {	
?>

    hosts.push({
    	"name": '<?= str_replace("'", "\'", $markerRow["FirstName"]) ?> <?= str_replace("'", "\'", $markerRow['LastName']) ?>', 
    	"lon": <?=$markerRow["Longitude"]?>, 
    	"lat": <?=$markerRow["Latitude"]?>, 
    	"zip": "<?=$markerRow["Zip"]?>"});
<?php
}
?>
    /*
     var marker = new MarkerWithLabel({
       position: new google.maps.LatLng(lat, lon),
       draggable: true,
       map: map,
	   icon: image,
       labelContent: "content h",
       labelAnchor: new google.maps.Point(22, 0),
       labelClass: "labels" // the CSS class for the label
     });
	*/

	function searchForHosts() {
            var lastPosition = "";

            // Clear the existing markers first.

            for (var i = 0; i < markers.length; i++) {
            	markers[i].setMap(null);
            }

            markers = [];

		    for (var i = 0; i < hosts.length; i++) {
			    if (hosts[i].zip == $("#zip").val() ) {

				    var infoWindow = new google.maps.InfoWindow({
                        content: "<div class='contextBox'><h1>" + hosts[i].name + "</h1></div>",
                        maxWidth: 500
				    });
					
                    marker = new google.maps.Marker ({
                        position: new google.maps.LatLng(hosts[i].lat, hosts[i].lon),
                        map: map,
                        infoWindow: infoWindow
				    });

					google.maps.event.addListener(marker, 'click', function() {

                        for (var i = 0; i < markers.length; i++) {
                        	markers[i].infoWindow.close();
                        }

					    this.infoWindow.open(map,this);
					});				    

				    lastPosition =  new google.maps.LatLng(hosts[i].lat, hosts[i].lon);

				    markers.push(marker);
				}
			}

			map.panTo(lastPosition);
	}

	$(document).ready(function()  {
	    $("#search").click(function() {
	    	searchForHosts();
		});

		$("#zip").keyup(function() {
			if ($("#zip").val().length == 5) {
			    searchForHosts();
		    }
		})
	});
 </script>
</head>
<body onLoad="initMap()">
<div id='searchPanel'>
    <input type='text' id='zip'/>
    <a href="#" id='search'>Search by zipcode</a>
    <span id='howToUse'> You can look up these hosts in the Host Book via searching for their name in the listed by name appendix.</span>
</div>
<div id="map_canvas" style="height: 90%; width: 100%"></div>
</body>