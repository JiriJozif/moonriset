<?php
require 'vendor/autoload.php';

use JiriJozif\Moonriset\Moonriset;

$mrs = new Moonriset(51.48, 0.0, "Europe/London"); //Royal Observatory, Greenwich

echo "Moon today ";
if ($mrs->rise["timestamp"] === false) {
    echo "not rising";
}
else {
    echo "rises at {$mrs->rise["hh_mm"]}";
}

echo " and ";

if ($mrs->set["timestamp"] === false) {
    echo "not setting";
}
else {
    echo "sets at {$mrs->set["hh_mm"]}";
}
