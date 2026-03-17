<?php
$stime='14:01:26';
$etime='14:02:48';

$startTime = DateTime::createFromFormat('H:i:s', $stime);
$endTime = DateTime::createFromFormat('H:i:s', $etime);

// Calculate the time difference
$timeDifference = $startTime->diff($endTime);

// Format the time difference
$formattedDifference = $timeDifference->format('%H:%I:%S');

// Echo the formatted time difference
echo "Time Difference: $formattedDifference";

?>