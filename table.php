<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Moonrise, Moon Transit and Moonset in the Year</title>
<style>
html {
    background-color:white;
}
div#navig {
    text-align:center;
    margin-bottom:1rem;
}
div#navig label {
    padding:0 1rem;
}

table {
    margin:0 auto;
    border-collapse:collapse;
    border:solid 1px black;
    font-family:sans-serif;
}
table caption {
    font-weight:bold;
}
th, td {
    border:dotted 1px black;
    padding: 1px 3px;
    font-size:12px;
    text-align:center;
}
td:nth-child(3n-1) {
    border-left:solid 1px black;
}
div#help {
    text-align:center;
}
</style>
</head>
<body>
<?php
require 'vendor/autoload.php';

use JiriJozif\Moonriset\Moonriset;

$year = intval($_GET["year"] ?? date("Y"));
$lat = floatval($_GET["lat"] ?? 51.5);
$lon = floatval($_GET["lon"] ?? 0.0);
$tz = strval($_GET["tz"] ?? "UTC");
$tzs = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

echo "<div id='navig'>";
echo "<h1>Moonrise, Moon Transit and Moonset in the Year</h1>";
echo "<form action='{$_SERVER['PHP_SELF']}' method='get'>";
echo "<label>Year:<input type='number' name='year' min='1900' max='2100' value='{$year}' onchange='form.submit()'></label>";
echo "<label>Latitude:<input type='number' name='lat' min='-90.0' max='90.0' step='0.5' value='{$lat}' onchange='form.submit()'></label>";
echo "<label>Longitude:<input type='number' name='lon' min='-180.0' max='180.0' step='1.0' value='{$lon}' onchange='form.submit()'></label>";
echo "<label>Timezone:<select name='tz' onchange='form.submit()'>";
foreach ($tzs as $tzi) {
    echo "<option value='{$tzi}'" . ($tzi === $tz ? ' selected' : '') . ">{$tzi}</option>";
}
echo "</select></label>";
echo "</form></div>\n";

$mrs = new Moonriset($lat, $lon, $tz);
echo "<table>";
echo "<thead>";
echo "<tr><td rowspan='2'></td>";
for ($month = 1; $month <= 12; $month++) {
    $monthName = Date("F", mktime(0, 0, 0, $month, 1, 2000));
    echo "<th colspan='3'>{$monthName}</th>";
}
echo "</tr>\n";
echo "<tr>";
for ($month = 1; $month <= 12; $month++) {
    echo "<th>rise</th><th>tran</th><th>set</th>";
}
echo "</tr>\n";
echo "</thead>";
echo "<tbody>";
for ($day = 1; $day <= 31; $day++) {
    echo "<tr><th>{$day}</th>";
    for ($month = 1; $month <= 12; $month++) {
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        if ($day <= $lastDay) {
            $mrs->setDate($year, $month, $day);
            echo "<td>{$mrs->rise["hh_mm"]}";
            if ($mrs->rise2["timestamp"]) {
                echo "<br>{$mrs->rise2["hh_mm"]}";
            }
            echo "</td>";

            echo "<td>{$mrs->transit["hh_mm"]}";

            echo "<td>{$mrs->set["hh_mm"]}";
            if ($mrs->set2["timestamp"]) {
                echo "<br>{$mrs->set2["hh_mm"]}";
            }
            echo "</td>";
        }
        else {
            echo "<td></td><td></td><td></td>";
        }
    }
    echo "</tr>\n";
}
echo "</tbody>";
echo "</table>\n";
echo "<div id='help'>'**:**' = Moon continuously above horizon, '--:--' = Moon continuously below horizon</div>";
?>
</body>
</html>
