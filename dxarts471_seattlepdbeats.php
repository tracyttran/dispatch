<?php
session_start();
require_once(dirname(__FILE__) . "/twitteroauth-master/autoload.php");

use Abraham\TwitterOAuth\TwitterOAuth;

// CONSTANTS
$NUMTWEETS = 40; 
$OLDESTDATE = "2017-1-28";
$DAY = "28";
$HOURS = 24;
$NUM_REGIONS = 51; 

// authentication constants for Twitter API
$twitteruser = "TracyyyTran";
$notweets = 30;
$consumerkey = "WxLUKU1bIX0i8vm5pCb6WxtIV";
$consumersecret = "qcTbPIl1ybI3XKtesscfuNA0R3bKo2bINclLp1FojUk8mwPDNo";
$accesstoken = "637223096-bsa6hcnfdJrLpDLZmLdzTP9IkawxyWmfX6YOiO4u";
$accesstokensecret = "u1f9nhsJ0ePJt6bgT8E3s5aROpbvuKU8JA7cBre9FXwaH";
 

// get authenticated
$connection = new TwitterOAuth($consumerkey, $consumersecret, $accesstoken, $accesstokensecret);


/*
 beatsarray is an array of arrays. the outer array is an array of police beats. 
 each police beat then has a boolean array of length hours, where the element is 1
 if police were dispatched to that region during that hour
 */
$beatsarray = array();

// inner array of length hours: initiatilize all to 0
$dayarray = array();
for ($i = 0; $i < $HOURS; $i++) {
    array_push($dayarray, 0); 
}

for ($i = 0 ; $i < $NUM_REGIONS; $i++) {
    array_push($beatsarray, $dayarray);
}


/* all the police beats. the order corresponds to the physical order of the LEDs
   connected in series
*/ 
$beatstoindices = array(
    "W3", 
    "F2",
    "F3",
    "O3",
    "S1",
    "S3",
    "S2",
    "R3",
    "R1",
    "O2",
    "F1",
    "W2",
    "W1",
    "M1",
    "D2",
    "E1",
    "E3",
    "K3",
    "R2",
    "G3",
    "K1",
    "M3",
    "M2",
    "D1",
    "Q3",
    "E2",
    "G1",
    "O1",
    "K2",
    "G2",
    "C3",
    "C2",
    "U2",
    "U3",
    "L3",
    "L1",
    "L2",
    "U1",
    "C1",
    "Q2",
    "Q1",
    "B1",
    "B2",
    "D3",
    "B3",
    "J3",
    "N3",
    "N2",
    "N1",
    "J1",
    "J2"
);

$arrsize = sizeof((array) $beatstoindices);


    
for ($i = 0; $i < $arrsize; $i++) {
    $beat = $beatstoindices[$i];

    // request to Twitter API
    $statuses = $connection->get("search/tweets", ["q" => "from:SeattlePD" . $beat . " since:" . $OLDESTDATE, "result_type" => "recent", "count" => $NUMTWEETS]);
    
    $jsonarr = $statuses->statuses;
    $size = sizeof((array) $jsonarr);
    
    for ($j = 0; $j < $size; $j++) {
        // pull the time of each status
        $tweet = $jsonarr[$j];

        $datetimearr = explode(" ", (string) $tweet->created_at);
        $day = $datetimearr[2]; 
        
        // make sure the tweet is from the date we want
        if (strcmp($day, $DAY) == 0) {
            $time = $datetimearr[3]; // hardcoded to format: Sun Jan 08 19:07:20 +0000 2017
            $hour = substr($time, 0, 2); // pulls the hour. also hardcoded based on format: 19:07:20
            $psthour = (((int) $hour) - 8); // hour is in UDT. convert to PST. 
            $psthour = $psthour - 1; // according to documentation, the tweets are tweeted one hour after actual dispatch for safety
            if ($psthour < 0) {
                $psthour = 24 + $psthour;
            }
            $psthour = $psthour % 24;

            // Fill the beatsarray: set the hour to 1 of the corresponding police beat
            $beatsarray[$i][$psthour] = 1;
        }
    }
}    

// Open serial connection
exec("mode com4: BAUD=9600 PARITY=n DATA=8 STOP=1 to=off dtr=off rts=off");
$fp =fopen("com4", "w");

/* array of "messages", where a message = a binary string of length 24 where the ith 
   element is 1 if police were dispatched during hour i
 */
$messagearray = array();
for ($i = 0; $i < $NUM_REGIONS; $i++) {
    $str = "";
    $beat = $beatsarray[$i];
    $numhours = sizeof((array) $beat);
    for ($j = 0; $j < $numhours; $j++) {
        $str = $str . $beat[$j];
    }
 
    array_push($messagearray, $str);   
}

/* send message array over serial connection. messages are sent individually 
    for ease of processing on other side
*/
for ($i = 0; $i < sizeof($messagearray); $i++) {
    fwrite($fp, $messagearray[$i]);
    echo("sent " . $messagearray[$i]);
    echo("\n");
    sleep(2);
}

// close serial connection
fclose($fp);

?>


