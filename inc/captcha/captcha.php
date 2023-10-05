<?php
class CzaksCaptcha {
  var $content = array();

  var $width, $height, $color, $charset, $style;

  function __construct($text, $left, $top, $charset=false) {
    if (!$charset) {
      $charset = 'abcdefghijklmnopqrstuvwxyz';
    }

    $len = mb_strlen($text, 'utf-8');

    $this->width = $left;
    $this->height = $top;

    $this->charset = preg_split('//u', $charset);
    
    $this->style = "";

    for ($i = 0; $i < $len; $i++) {
      $this->content[] = array(mb_substr($text, $i, 1, 'utf-8'), "top" => $top / 2 - $top / 4,
                                                                 "left" => $left/10 + 9*$left*$i/10/$len,
                                                                 "position" => "absolute");
    }

    $this->color = "hsla(".rand(1,360).", 76%, 78%, 1)";

    $this->add_junk();
    $this->mutate_sizes();
    $this->mutate_positions();
    $this->mutate_transform();
    $this->mutate_anchors();
    $this->randomize();
    $this->mutate_containers();
    $this->mutate_margins();
    $this->mutate_styles();
    $this->randomize();
  }

  function mutate_sizes() {
    foreach ($this->content as &$v) {
      if (!isset ($v['font-size']))
        $v['font-size'] = rand(intval($this->height/3) - 4, intval($this->height/3) + 8);
    }
  }
  function mutate_positions() {
    foreach ($this->content as &$v) {
      $v['top'] += rand(-10,10);
      $v['left'] += rand(-10,10);
    }
  }
  function mutate_transform() {
    $fromto = array('6'=>'9', '9'=>'6', '8'=>'8', '0'=>'0',
                    'z'=>'z', 's'=>'s', 'n'=>'u', 'u'=>'n',
                    'a'=>'ɐ', 'e'=>'ə', 'p'=>'d', 'd'=>'p',
                    'A'=>'∀', 'E'=>'∃', 'H'=>'H', 'o'=>'o',
		    'O'=>'O');

    foreach ($this->content as &$v) {
      $basefrom = -20;
      $baseto = 20;

      if (isset($fromto[$v[0]]) && rand(0,1)) {
        $v[0] = $fromto[$v[0]];
        $basefrom = 160;
        $baseto = 200;
      }

      $v['transform'] = 'rotate('.rand($basefrom,$baseto).'deg)';
      $v['-ms-transform'] = 'rotate('.rand($basefrom,$baseto).'deg)';
      $v['-webkit-transform'] = 'rotate('.rand($basefrom,$baseto).'deg)';
    }
  }
  function randomize(&$a = false) {
    if ($a === false) {
      $a = &$this->content;
    }
  
    shuffle($a);

    foreach ($a as &$v) {
      $this->shuffle_assoc($v);
      
      if (is_array ($v[0])) {
        $this->randomize($v[0]);
      }
    }
  }

  function add_junk() {
    $count = rand(200, 300);

    while ($count--) {
      $elem = array();

      $elem['top'] = rand(0, $this->height);
      $elem['left'] = rand(0, $this->width);
      
      $elem['position'] = 'absolute';

      $elem[0] = $this->charset[rand(0, count($this->charset)-1)];

      switch($t = rand (0,9)) {
        case 0:
          $elem['display'] = 'none'; break;
        case 1:
          $elem['top'] = rand(-60, -90); break;
        case 2:
          $elem['left'] = rand(-40, -70); break;
        case 3:
          $elem['top'] = $this->height + rand(10, 60); break;
        case 4:
          $elem['left'] = $this->width + rand(10, 60); break;
        case 5:
          $elem['color'] = $this->color; break;
        case 6:
          $elem['visibility'] = 'hidden'; break;
        case 7:
          $elem['height'] = rand(0,2);
          $elem['overflow'] = 'hidden'; break;
        case 8:
          $elem['width'] = rand(0,1);
          $elem['overflow'] = 'hidden'; break;
        case 9:
          $elem['font-size'] = rand(2, 6); break;
      }

      $this->content[] = $elem;
    }
  }
  
  function mutate_anchors() {
    foreach ($this->content as &$elem) {
      if (rand(0,1)) {
        $elem['right'] = $this->width - $elem['left'] - (int)(0.5*$elem['font-size']);
        unset($elem['left']);
      }
      if (rand(0,1)) {
        $elem['bottom'] = $this->height - $elem['top'] - (int)(1.5*$elem['font-size']);
        unset($elem['top']);
      }
    }
  }
  
  function mutate_containers() {
    for ($i = 0; $i <= 80; $i++) {
      $new = [];
      $new['width'] = rand(0, $this->width*2);
      $new['height'] = rand(0, $this->height*2);
      $new['top'] = rand(-$this->height * 2, $this->height * 2);
      $new['bottom'] = $this->height - ($new['top'] + $new['height']);
      $new['left'] = rand(-$this->width * 2, $this->width * 2);
      $new['right'] = $this->width - ($new['left'] + $new['width']);
      
      $new['position'] = 'absolute';

      $new[0] = [];
      
      $cnt = rand(0,10);
      for ($j = 0; $j < $cnt; $j++) {
        $elem = array_pop($this->content);
        if (!$elem) break;
        
        if (isset($elem['top'])) $elem['top'] -= $new['top'];
        if (isset($elem['bottom'])) $elem['bottom'] -= $new['bottom'];
        if (isset($elem['left'])) $elem['left'] -= $new['left'];
        if (isset($elem['right'])) $elem['right'] -= $new['right'];
        
        $new[0][] = $elem;
      }
      
      if (rand (0,1)) unset($new['top']);
                 else unset($new['bottom']);
      if (rand (0,1)) unset($new['left']);
                 else unset($new['right']);
                 
      $this->content[] = $new;
      
      shuffle($this->content);
    }
  }
  
  function mutate_margins(&$a = false) {
    if ($a === false) {
      $a = &$this->content;
    }
    
    foreach ($a as &$v) {
      $ary = ['top', 'left', 'bottom', 'right'];
      shuffle($ary);
      $cnt = rand(0,4);
      $ary = array_slice($ary, 0, $cnt);
      
      foreach ($ary as $prop) {
	$margin = rand(-1000, 1000);
	
	$v['margin-'.$prop] = $margin;
	
	if (isset($v[$prop])) {
	  $v[$prop] -= $margin;
	}
      }
      
      if (is_array($v[0])) {
	$this->mutate_margins($v[0]);
      }
    }
  }
  
  function mutate_styles(&$a = false) {
    if ($a === false) {
      $a = &$this->content;
    }
    
    foreach ($a as &$v) {
      $content = $v[0];
      unset($v[0]);
      $styles = array_splice($v, 0, rand(0, 6));
      $v[0] = $content;
      
      $id_or_class = rand(0,1);
      $param = $id_or_class ? "id" : "class";
      $prefix = $id_or_class ? "#" : ".";
      $genname = "zz-".base_convert(rand(1,999999999), 10, 36);
      
      if ($styles || rand(0,1)) {
        $this->style .= $prefix.$genname."{";
        $this->style .= $this->rand_whitespace();
      
        foreach ($styles as $k => $val) {
          if (is_int($val)) {
            $val = "".$val."px";
          }

          $this->style .= "$k:";
          $this->style .= $this->rand_whitespace();
          $this->style .= "$val;";
          $this->style .= $this->rand_whitespace();
        }
        $this->style .= "}";
        $this->style .= $this->rand_whitespace();
      }
      
      $v[$param] = $genname;
    
      if (is_array($v[0])) {
	$this->mutate_styles($v[0]);
      }
    }
  }

  function to_html(&$a = false) {
    $inside = true;
    
    if ($a === false) {
      if ($this->style) {
        echo "<style type='text/css'>";
        echo $this->style;
        echo "</style>";
      }
    
      echo "<div style='position: relative; width: ".$this->width."px; height: ".$this->height."px; overflow: hidden; background-color: ".$this->color."'>";
      $a = &$this->content;
      $inside = false;
    }
    
    foreach ($a as &$v) {
      $letter = $v[0];

      unset ($v[0]);

      echo "<div";
      echo $this->rand_whitespace(1);
      
      if (isset ($v['id'])) {
        echo "id='$v[id]'";
        echo $this->rand_whitespace(1);

        unset ($v['id']);
      }
      if (isset ($v['class'])) {
        echo "class='$v[class]'";
        echo $this->rand_whitespace(1);
                
        unset ($v['class']);
      }
      
      echo "style='";

      foreach ($v as $k => $val) {
        if (is_int($val)) {
          $val = "".$val."px";
        }

        echo "$k:";
        echo $this->rand_whitespace();
        echo "$val;";
        echo $this->rand_whitespace();

      }

      echo "'>";
      echo $this->rand_whitespace();
      
      if (is_array ($letter)) {
        $this->to_html($letter);
      }
      else {
        echo $letter;
      }
      
      echo "</div>";
    }

    if (!$inside) {
      echo "</div>";
    }

  }

  function rand_whitespace($r = 0) {
    switch (rand($r,4)) {
      case 0:
        return "";
      case 1:
        return "\n";
      case 2:
        return "\t";
      case 3:
        return " ";
      case 4:
        return "   ";
    }
  }



    function shuffle_assoc(&$array) {
        $keys = array_keys($array);

        shuffle($keys);

        foreach($keys as $key) {
            $new[$key] = $array[$key];
        }

        $array = $new;

        return true;
    }
}

//$charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789卐";

//(new CzaksCaptcha("hotwheels", 300, 80, $charset))->to_html();
?>
