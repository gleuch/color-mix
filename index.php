<?php
/*
           _                           _      
          | |                         (_)     
  ___ ___ | | ___  _ __ ____ _ __ ___  ___  __
 / __/ _ \| |/ _ \| '__|____| '_ ` _ \| \ \/ /
| (_| (_) | | (_) | |       | | | | | | |>  < 
 \___\___/|_|\___/|_|       |_| |_| |_|_/_/\_\


------------------------------------------------
  by Greg Leuch
  http://www.gleuch.com/projects/color-mix
------------------------------------------------
 
*/



include_once('./color_mix.php');

$colormix = new ColorMix;
$colormix->setup();
$colormix->route();
$colormix->output();

?>