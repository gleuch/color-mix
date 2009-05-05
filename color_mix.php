<?php

class ColorMix {
  function ColorMix() {
    $this->folder = dirname($_SERVER["PHP_SELF"]);
  }

  function setup() {
    if (!is_dir('./palettes')) {
      if (!mkdir('./palettes', 0777)) {
        echo 'Could not make /palettes directory. Make sure you have permissions to create new folder and files.';
        exit;
      }
    }
  }

  function route() {
    list($this->controller, $this->action, $this->subcontroller, $this->subaction) = explode("/", $_REQUEST['folder']);
    switch ($this->controller) {
      case 'create':
        $this->create_collection(); break;
      case 'new':
        $this->build_collection(); break;
      case 'save':
        $this->save_palette(); break;
      case 'make':
        $this->make_palette(); break;
      case 'show':
        $this->show_collection(); break;
      case 'palette':
        $this->show_palette(); break;
      case 'index':
      default:
        $this->index(); break;
    }
  }


  function index() {
    $palettes = array();
    if ($handle = opendir('./palettes')) {
      while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && is_dir('./palettes/'. $file) && is_file('./palettes/'. $file .'/master')) {
          array_push($palettes, $file);
        }
      }
      closedir($handle);
    }

    $this->html .= '<h1>Choose a Collection</h1>';

    if (count($palettes) > 0) {
      $this->html .= '<p>There '. (count($palettes) > 1 ? 'are' : 'is') .' '. count($palettes) .' collection'. (count($palettes) > 1 ? 's' : '') .' available to match.</p><ul>';
      foreach ($palettes as $v) {
        $this->html .= '<li><a href="'. $this->folder .'/show/'. $v .'">'. $v.'</li>';
      }
      $this->html .= '</ul>';
        
    } else {
      $this->html .= '<p class="error">There are no collections available to view.</p>';
    }

    $this->html .= '<h4><a href="new">Create a new collection</a></h4>';
  }

  function build_collection() {
    $this->html = '<h1>Create a collection!</h1>'. $this->error .'<form action="'. $this->folder .'/create" method="post"><fieldset class="title"><label for="title">Collection Name</label><input id="title" type="text" name="title" value="'. (isset($_POST['title']) ? $_POST['title'] : '') .'" /></fieldset><div id="colors">';
    if (isset($_POST['color']) && count($_POST['color']) > 0) {
      foreach ($_POST['color'] as $v) {
        $this->html .= '<fieldset class="color"><span class="ex_box" style="background: #'. str_replace("#", "", $v) .';"></span><input type="text" class="inp_color" name="color[]" value="'. str_replace("#", "", $v) .'" /></fieldset>';
      }
    } else {
      $this->html .= '<fieldset class="color"><span class="ex_box"></span><input type="text" class="inp_color" name="color[]" value="" /></fieldset>';
    }
    $this->html .= '</div><p><input id="another_palette" type="button" value="Add another color" /></p><fieldset><input type="submit" value="Mix collection!" /></fieldset></form>';
  }
  
  function create_collection() {
    if (!isset($_POST) || count($_POST) == 0) {
      $this->error = true;
    } else {
      $file = $this->slug($_POST['title']);
      if (empty($_POST['title']) || empty($file)) $this->error .= '<p class="error">Please insert a title.</p>';
      if (!isset($_POST['color']) || count($_POST['color']) < 3) $this->error .= '<p class="error">Please add at least three colors to make a collection.</p>';
      if (is_dir('./palettes/'. $file) && is_file('./palettes/'. $file .'/master')) $this->error = 'A collection already exists with a similar name! Please enter a better title.';
    }

    if ($this->error || !empty($this->error)) {
      $this->build();
    } else {
      $str = $_POST['title'];
      $colors = array_unique($_POST['color']);
      foreach ($colors as $v) $str .= (!empty($str) ? "\n":'') . str_replace('#', '', $v);
      if (!is_dir('./palettes/'. $file)) mkdir('./palettes/'. $file, 0777);
      file_put_contents('./palettes/'.$file .'/master', $str);
      $this->redirect_to('make/'. $file);
    }
  }

  function make_palette() {
    if (empty($this->action)) $this->err('You must specify a collection.');
    $colors = explode("\n", str_replace("\n\n", '', file_get_contents('./palettes/'. $this->action .'/master')));
    $title = array_shift($colors);
    $len = ($_REQUEST['l'] > 1 ? $_REQUEST['l'] : mt_rand(2, 6));
    if ($len > count($colors)) $len = count($colors);
    $mix = array();
    while ($len > 0) {
      list($k,$m) = $this->rand($colors);
      array_push($mix, $m);
      array_splice($colors, $k, 1);
      --$len;
    }

    //if (!is_file('./palettes/'. $this->action .'/history')) touch('./palettes/'. $this->action .'/history');

    $this->html = '<h1>Colors for '. $title .'</h1><ul class="palette c">';
    foreach ($mix as $k=>$v) {
      $this->html .= '<li class="ex_box" title="#'. $v .'" style="background: #'. $v .';"></li>';
      $box .= '<dd>#'. $v .'</dd>';
      $data .= (!empty($data) ? ',' : ''). "'color[". $k ."]':'". $v ."'";
    }
    $ajax = "var r = this; \$.ajax({type: 'GET', url: '". $this->folder ."/save/". $this->action ."', data:{". $data ."}, success:function(t) {\$(r).before('This palette has been saved!').remove();}, failure:function() {alert('Could not save this palette!');} });";
    $this->html .= '</ul><p><input type="button" onclick="location.href=\''. $this->folder .'/make/'. $this->action .'\';" value="Try Another Palette!" /></p><p><input type="button" onclick="'. $ajax .'; return false;" value="Save this palette" /></p><p>&nbsp;</p><dl><dt>Colors in this palette:</dt>'. $box .'</dl>';

    $this->html .= '<p>&nbsp;</p><p><a href="'. $this->folder .'/show/'. $this->action .'">View Original Collection</a></p>';


    $this->html .= '<h3>Previous Palette Options</h3>';
    $history = file_get_contents('./palettes/'. $this->action .'/history');
    $p_colors = array_reverse(array_slice(explode("\n", trim($history)), -5, 5));
    foreach ($p_colors as $v) {
      $palette = explode(',', $v);
      $data = '';
      $this->html .= '<p><ul class="smaller c">';
      foreach ($palette as $k=>$c) {
        $data .= (!empty($data) ? ',' : ''). "'color[". $k ."]':'". $c ."'";
        $this->html .= '<li class="ex_box" title="#'. $c .'" style="background: #'. $c .';"></li>';
      }
      $ajax = "var r = this; \$.ajax({type: 'GET', url: '". $this->folder ."/save/". $this->action ."', data:{". $data ."}, success:function(t) {\$(r).before('This palette has been saved!').remove();}, failure:function() {alert('Could not save this palette!');} });";
      $this->html .= '<li class="left"><input type="button" onclick="'. $ajax .'; return false;" value="Save this palette" /></li></ul></p>';
    }

    if ($file = fopen('./palettes/'. $this->action .'/history', 'a+')) {
      $r = implode(',', array_values($mix)) ."\n";
      fwrite($file, $r);
      fclose($file);
    }
  }

  function show_collection() {
    if (empty($this->action)) $this->err('You must specify a collection.');
    $colors = explode("\n", str_replace("\n\n", '', file_get_contents('./palettes/'. $this->action .'/master')));
    $title = array_shift($colors);

    $this->html = '<h1>Colors for '. $this->action .'</h1><ul class="palette c">';
    foreach ($colors as $v) $this->html .= '<li class="ex_box" style="background: #'. $v .';"></li>';
    $this->html .= '</ul>';

    $this->html .= '<h1>Saved Palettes for '. $this->action .'</h1>';
    
    if ($handle = opendir('./palettes/'. $this->action)) {
      while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && $file != 'master' && is_file('./palettes/'. $this->action .'/'. $file)) {
          $saved .= '<p><ul class="palette smaller c">';
          $colors = explode("\n", str_replace("\n\n", '', file_get_contents('./palettes/'. $this->action .'/'. $file)));
          foreach ($colors as $v) $saved .= '<li class="ex_box" title="#'. $v .'" style="background: #'. $v .';"></li>';
          $saved .= '<li class="textlink"><a href="'. $this->folder .'/palette/'. $this->action .'/'. $file .'">View</a></li></ul></p>';
        }
      }
      closedir($handle);
    }

    $this->html .= (!empty($saved) ? $saved : '<p><em>There are no saved palettes for this collection.</em></p>');
    $this->html .= '<p><a href="'. $this->folder .'/make/'. $this->action .'">Make random palette!</a></p>';
  }

  function show_palette() {
    if (empty($this->action)) $this->err('You must specify a collection.');
    $colors = explode("\n", str_replace("\n\n", '', file_get_contents('./palettes/'. $this->action .'/'. $this->subcontroller)));
    $title = array_shift($colors);

    $this->html = '<h1>Colors for '. $this->action .'</h1><ul class="palette c">';
    foreach ($colors as $v) {
      $this->html .= '<li class="ex_box" title="#'. $v .'" style="background: #'. $v .';"></li>';
      $box .= '<dd>#'. $v .'</dd>';
    }
    $this->html .= '</ul><p>&nbsp;</p><dl><dt>Colors in this palette:</dt>'. $box .'</dl>';
    


    $this->html .= (!empty($saved) ? $saved : '<p><em>There are no saved palettes for this collection.</em></p>');
    $this->html .= '<p><a href="'. $this->folder .'/make/'. $this->action .'">Make random palette!</a></p>';
  }

  function save_palette() {
    $d = date("U");
    foreach ($_REQUEST['color'] as $v) $str .= (!empty($str) ? "\n" : '') . $v;
    if (!empty($v) && is_dir('./palettes/'. $this->action)) {
      file_put_contents('./palettes/'. $this->action .'/'. $d, $str);
      header("Status: 200");
    } else {
      header("Status: 500");
    }
    exit;      
  }

  /* ------------------------- */

  function output() {
    echo '<html><head><title>Color Palette Mixer</title><link rel="stylesheet" type="text/css" media="all" href="'. $this->folder .'/styles.css" /><script type="text/javascript" src="'. $this->folder .'/jquery-1.3.2-min.js"></script><script type="text/javascript" src="'. $this->folder .'/color_mix.js"></script></head><body>'. $this->html .'</body></html>';
  }

  function rand($arr) {
    $k = (count($arr) > 0 ? mt_rand(0, count($arr)-1) : 0);
    return array($k, $arr[$k]);
  }

  function err($err='An unknown error has occurred.') {
    $this->html = $err;
    $this->output();
    exit;
  }

  function slug($str) {
    return strtolower(str_replace(' ', '-', str_replace(array('?', '.',',','!','#','@','$','%','^','&','*','(',')','[','=','+',']','{','}','|','\\','\'','"',';',':','<','>','/','~','`'), '', $str)));
  }

  function redirect_to($url) {
    header("Status: 200");
    header('Location: '. dirname($_SERVER["PHP_SELF"]) .'/'. $url.'?');
    exit;
  }

}

?>