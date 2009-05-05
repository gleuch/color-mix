<?php

include_once('./color_mix.php');

$colormix = new ColorMix;
$colormix->setup();
$colormix->route();
$colormix->output();

?>