<?php
require 'vendor/autoload.php';

use JiriJozif\Moonriset\Moonriset;

$mrs = new Moonriset(51.48, 0.0, "Europe/London"); //Royal Observatory, Greenwich
echo "Moon rises today at {$mrs->rise["hh_mm"]} and sets at {$mrs->set["hh_mm"]}";
