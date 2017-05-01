window.rgbHex = (function() {
    function isNumeric(arg) {
        return !isNaN(parseFloat(arg)) && isFinite(arg);
    }

    function trim(arg) {
        return arg.replace(/^\s+|\s+$/g, "");
    }

    function isRgb(arg) {
        arg = trim(arg);
        return isNumeric(arg) && arg >= 0 && arg <= 255;
    }
    
    function isHex(arg) {
        return (/^[0-9a-f]{3}$|^[0-9a-f]{6}$/i).test(trim(arg));
    }

    function rgbToHex(arg) {
        arg = parseInt(arg, 10).toString(16);
        return arg.length === 1 ? '0' + arg : arg; 
    }

    function hexToRgb(arg) {
        return parseInt(arg, 16).toString();
    }

    function processRgb(arg) {
        arg = arg.split(',');

        if ( (arg.length === 3 || arg.length === 4) && isRgb(arg[0]) && isRgb(arg[1]) && isRgb(arg[2]) ) {
            if (arg.length === 4 && !isNumeric(arg[3])) { return null; }
            return '#' + rgbToHex(arg[0]).toUpperCase() + rgbToHex(arg[1]).toUpperCase() + rgbToHex(arg[2]).toUpperCase();
        }
        else {
            return null;
        }
    }

    function processHex(arg) {
        if (isHex(arg)) {
            if (arg.length === 3) { arg = arg.charAt(0) + arg.charAt(0) + arg.charAt(1) + arg.charAt(1) + arg.charAt(2) + arg.charAt(2); }

            return 'rgb(' + hexToRgb(arg.substr(0,2)) + ',' + hexToRgb(arg.substr(2,2)) + ',' + hexToRgb(arg.substr(4,2)) + ')';
        }
    }

    function removeWhitespace(arg) {
        return arg.replace(/\s/g, '');
    }

    return function(arg) {        
        if(!arg) { return null; }

        var code = null,
            rgbRegex = /^rgba?\((.*)\);?$/,
            hexRegex = /^#/;
            
        arg = trim(arg.toString());
        
        if (arg === 'transparent' || removeWhitespace(arg) === 'rgba(0,0,0,0)') {
            return 'transparent';
        }
        else if (rgbRegex.test(arg)) {
            return processRgb(arg.match(rgbRegex)[1]);
        }
        else if (hexRegex.test(arg)) {
            return processHex(arg.split('#').reverse()[0]);
        }
        //try to parse the string without rgb or #
        else {
            code = arg.split(',');

            if (code.length === 1) { return processHex(arg); }
            else if (code.length === 3 || code.length === 4) { return processRgb(arg); }
        }
    };
})();

if(jQuery) {
    jQuery.extend({
        rgbHex: function(arg) { return window.rgbHex(arg); }
    });
}
//     keymaster.js
//     (c) 2011-2013 Thomas Fuchs
//     keymaster.js may be freely distributed under the MIT license.

;(function(global){
  var k,
    _handlers = {},
    _mods = { 16: false, 18: false, 17: false, 91: false },
    _scope = 'all',
    // modifier keys
    _MODIFIERS = {
      '⇧': 16, shift: 16,
      '⌥': 18, alt: 18, option: 18,
      '⌃': 17, ctrl: 17, control: 17,
      '⌘': 91, command: 91
    },
    // special keys
    _MAP = {
      backspace: 8, tab: 9, clear: 12,
      enter: 13, 'return': 13,
      esc: 27, escape: 27, space: 32,
      left: 37, up: 38,
      right: 39, down: 40,
      del: 46, 'delete': 46,
      home: 36, end: 35,
      pageup: 33, pagedown: 34,
      ',': 188, '.': 190, '/': 191,
      '`': 192, '-': 189, '=': 187,
      ';': 186, '\'': 222,
      '[': 219, ']': 221, '\\': 220
    },
    code = function(x){
      return _MAP[x] || x.toUpperCase().charCodeAt(0);
    },
    _downKeys = [];

  for(k=1;k<20;k++) _MAP['f'+k] = 111+k;

  // IE doesn't support Array#indexOf, so have a simple replacement
  function index(array, item){
    var i = array.length;
    while(i--) if(array[i]===item) return i;
    return -1;
  }

  // for comparing mods before unassignment
  function compareArray(a1, a2) {
    if (a1.length != a2.length) return false;
    for (var i = 0; i < a1.length; i++) {
        if (a1[i] !== a2[i]) return false;
    }
    return true;
  }

  var modifierMap = {
      16:'shiftKey',
      18:'altKey',
      17:'ctrlKey',
      91:'metaKey'
  };
  function updateModifierKey(event) {
      for(k in _mods) _mods[k] = event[modifierMap[k]];
  };

  // handle keydown event
  function dispatch(event) {
    var key, handler, k, i, modifiersMatch, scope;
    key = event.keyCode;

    if (index(_downKeys, key) == -1) {
        _downKeys.push(key);
    }

    // if a modifier key, set the key.<modifierkeyname> property to true and return
    if(key == 93 || key == 224) key = 91; // right command on webkit, command on Gecko
    if(key in _mods) {
      _mods[key] = true;
      // 'assignKey' from inside this closure is exported to window.key
      for(k in _MODIFIERS) if(_MODIFIERS[k] == key) assignKey[k] = true;
      return;
    }
    updateModifierKey(event);

    // see if we need to ignore the keypress (filter() can can be overridden)
    // by default ignore key presses if a select, textarea, or input is focused
    if(!assignKey.filter.call(this, event)) return;

    // abort if no potentially matching shortcuts found
    if (!(key in _handlers)) return;

    scope = getScope();

    // for each potential shortcut
    for (i = 0; i < _handlers[key].length; i++) {
      handler = _handlers[key][i];

      // see if it's in the current scope
      if(handler.scope == scope || handler.scope == 'all'){
        // check if modifiers match if any
        modifiersMatch = handler.mods.length > 0;
        for(k in _mods)
          if((!_mods[k] && index(handler.mods, +k) > -1) ||
            (_mods[k] && index(handler.mods, +k) == -1)) modifiersMatch = false;
        // call the handler and stop the event if neccessary
        if((handler.mods.length == 0 && !_mods[16] && !_mods[18] && !_mods[17] && !_mods[91]) || modifiersMatch){
          if(handler.method(event, handler)===false){
            if(event.preventDefault) event.preventDefault();
              else event.returnValue = false;
            if(event.stopPropagation) event.stopPropagation();
            if(event.cancelBubble) event.cancelBubble = true;
          }
        }
      }
    }
  };

  // unset modifier keys on keyup
  function clearModifier(event){
    var key = event.keyCode, k,
        i = index(_downKeys, key);

    // remove key from _downKeys
    if (i >= 0) {
        _downKeys.splice(i, 1);
    }

    if(key == 93 || key == 224) key = 91;
    if(key in _mods) {
      _mods[key] = false;
      for(k in _MODIFIERS) if(_MODIFIERS[k] == key) assignKey[k] = false;
    }
  };

  function resetModifiers() {
    for(k in _mods) _mods[k] = false;
    for(k in _MODIFIERS) assignKey[k] = false;
  };

  // parse and assign shortcut
  function assignKey(key, scope, method){
    var keys, mods;
    keys = getKeys(key);
    if (method === undefined) {
      method = scope;
      scope = 'all';
    }

    // for each shortcut
    for (var i = 0; i < keys.length; i++) {
      // set modifier keys if any
      mods = [];
      key = keys[i].split('+');
      if (key.length > 1){
        mods = getMods(key);
        key = [key[key.length-1]];
      }
      // convert to keycode and...
      key = key[0]
      key = code(key);
      // ...store handler
      if (!(key in _handlers)) _handlers[key] = [];
      _handlers[key].push({ shortcut: keys[i], scope: scope, method: method, key: keys[i], mods: mods });
    }
  };

  // unbind all handlers for given key in current scope
  function unbindKey(key, scope) {
    var multipleKeys, keys,
      mods = [],
      i, j, obj;

    multipleKeys = getKeys(key);

    for (j = 0; j < multipleKeys.length; j++) {
      keys = multipleKeys[j].split('+');

      if (keys.length > 1) {
        mods = getMods(keys);
      }

      key = keys[keys.length - 1];
      key = code(key);

      if (scope === undefined) {
        scope = getScope();
      }
      if (!_handlers[key]) {
        return;
      }
      for (i = 0; i < _handlers[key].length; i++) {
        obj = _handlers[key][i];
        // only clear handlers if correct scope and mods match
        if (obj.scope === scope && compareArray(obj.mods, mods)) {
          _handlers[key][i] = {};
        }
      }
    }
  };

  // Returns true if the key with code 'keyCode' is currently down
  // Converts strings into key codes.
  function isPressed(keyCode) {
      if (typeof(keyCode)=='string') {
        keyCode = code(keyCode);
      }
      return index(_downKeys, keyCode) != -1;
  }

  function getPressedKeyCodes() {
      return _downKeys.slice(0);
  }

  function filter(event){
    var tagName = (event.target || event.srcElement).tagName;
    // ignore keypressed in any elements that support keyboard data input
    return !(tagName == 'INPUT' || tagName == 'SELECT' || tagName == 'TEXTAREA');
  }

  // initialize key.<modifier> to false
  for(k in _MODIFIERS) assignKey[k] = false;

  // set current scope (default 'all')
  function setScope(scope){ _scope = scope || 'all' };
  function getScope(){ return _scope || 'all' };

  // delete all handlers for a given scope
  function deleteScope(scope){
    var key, handlers, i;

    for (key in _handlers) {
      handlers = _handlers[key];
      for (i = 0; i < handlers.length; ) {
        if (handlers[i].scope === scope) handlers.splice(i, 1);
        else i++;
      }
    }
  };

  // abstract key logic for assign and unassign
  function getKeys(key) {
    var keys;
    key = key.replace(/\s/g, '');
    keys = key.split(',');
    if ((keys[keys.length - 1]) == '') {
      keys[keys.length - 2] += ',';
    }
    return keys;
  }

  // abstract mods logic for assign and unassign
  function getMods(key) {
    var mods = key.slice(0, key.length - 1);
    for (var mi = 0; mi < mods.length; mi++)
    mods[mi] = _MODIFIERS[mods[mi]];
    return mods;
  }

  // cross-browser events
  function addEvent(object, event, method) {
    if (object.addEventListener)
      object.addEventListener(event, method, false);
    else if(object.attachEvent)
      object.attachEvent('on'+event, function(){ method(window.event) });
  };

  // set the handlers globally on document
  addEvent(document, 'keydown', function(event) { dispatch(event) }); // Passing _scope to a callback to ensure it remains the same by execution. Fixes #48
  addEvent(document, 'keyup', clearModifier);

  // reset modifiers to false whenever the window is (re)focused.
  addEvent(window, 'focus', resetModifiers);

  // store previously defined key
  var previousKey = global.key;

  // restore previously defined key and return reference to our key object
  function noConflict() {
    var k = global.key;
    global.key = previousKey;
    return k;
  }

  // set window.key and window.key.set/get/deleteScope, and the default filter
  global.key = assignKey;
  global.key.setScope = setScope;
  global.key.getScope = getScope;
  global.key.deleteScope = deleteScope;
  global.key.filter = filter;
  global.key.isPressed = isPressed;
  global.key.getPressedKeyCodes = getPressedKeyCodes;
  global.key.noConflict = noConflict;
  global.key.unbind = unbindKey;

  if(typeof module !== 'undefined') module.exports = assignKey;

})(this);

(function($) {
	function ColorPicker(el, options) {
		this.$el = $(el);
		this.options = options;
		this.init = false;

		this.generate();
	}
		
	ColorPicker.prototype = {
		generate: function() {
			var _this = this;
			var colorchooser, gradientchooser, contexts, id, common, resizing = false, ci = 0;

			if (!this.$colorPicker) {
				// input and target holders
				this.$colorTarget = $('<div class="wColorPicker-color-target"></div>');
				this.$customInput = $('<input type="text" class="wColorPicker-custom-input"/>').keyup($.proxy(this._customInputKeyup, this));

				this.$colorChooser = $('<canvas id="colorchooser" height="142" width="339" style="border:1px solid black"></canvas>');
				this.$gradientChooser = $('<canvas id="gradientchooser" height="142" width="20" style="border:1px solid black"></canvas>');
				this.$commonHistory = $('<table id="commonhistory">'+
						'<tr id="common"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td rowspan="3" class="ignore" id="curcolor"></td></tr>'+
						'<tr id="commonbw"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>'+
						'<tr id="history"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>'+
						'</table>');
				this.$colorChosen =	$('<table id="colorchosen">'+
						'<tr class="rgb"><td>'+_('R')+' <span class="r">255</span></td><td>'+_('G')+' <span class="g">255</span></td><td>'+_('B')+' <span class="b">255</span></td><td rowspan="2" class="colorpicker-buttons"><button value="white">HSV</button><button value="black">HVS</button><button value="both">'+_('Both')+'</button><button value="flip">'+_('Flip')+'</button></td></tr>'+
						'<tr class="hsv"><td>H <span class="h"></span></td><td>S <span class="s"></span></td><td>V <span class="v"></span></td></tr>'+
						'</table>');
				this.$containerDiv = $('<div class="wColorPicker" title=""></div>')
				.append(this.$colorChooser)
				.append(this.$gradientChooser)
				.append(this.$commonHistory)
				.append(this.$colorChosen);

				if (this.options.dropperButton) { this.$dropperButton = this._createDropperButton(); }

				// the Palettes holder is to enforce size of color picker for effects so no weird bunching up happens when we change sizes
				this.$colorPickerPalettesHolder = $('<div class="wColorPicker-palettes-holder"></div>')
				.append(this.$containerDiv)
				.append(this.$dropperButton);

				// the holder is what we will use on some effects, rather than the pallettes holder which need to remain the same size
				this.$colorPickerHolder = $('<div class="wColorPicker-holder"></div>').append(this.$colorPickerPalettesHolder);
				
				// store our bg independantly this is due to a bug with opacities showing up oddly 
				this.$colorPickerBg = $('<div class="wColorPicker-bg"></div>');

				// put everything into the color picker
				// make sure title="" here otherwise title from button will appear
				this.$colorPicker = $('<div class="wColorPicker" title=""></div>')
				.append(this.$colorPickerBg).append(this.$colorPickerHolder);

				// set some properties
				this.setColor(this.options.color);
				this.setTheme(this.options.theme);

				// get proper width/height and keep it for reference (needed for effects)
				$('body').append(this.$colorPicker);
				this.width = this.$colorPickerPalettesHolder.width();
				this.height = this.$colorPickerPalettesHolder.height();

				// append the color picker properly
				this.$el.append(this.$colorPicker);

				// set mode after to get proper dimensions
				// if hidden will not get proper dimensions
				this.setMode(this.options.mode);

				// call after we have our proper dimensions set
				// setPosition will call setEffect
				this.setPosition(this.options.position);

				colorchooser = this.$colorChooser[0];
				gradientchooser = this.$gradientChooser[0];
				this.contexts = new Object;
				this.curmode = '';
				this.leftButtonDown = false;
				this.contexts.colorchooser = {element: colorchooser, context: colorchooser.getContext("2d")};
				this.contexts.gradientchooser = {element: gradientchooser, context: gradientchooser.getContext("2d")};
				id = this._buildImageData(this.contexts.colorchooser, "both");
				this.contexts.colorchooser.context.putImageData(id, 0, 0);

				common = this.$commonHistory.find('#common td:not(.ignore)');
				$.each(common, function(k, v) {
					var rgb = _this._HSVtoRGB((ci/(common.length-1)), 1, 1);
					$(this).css('background-color', 'rgb('+rgb.r+','+rgb.b+','+rgb.g+')');
					var bw = ((ci/(common.length-1))*255).toFixed();
					_this.$commonHistory.find('#commonbw td:eq('+ci+')').css('background-color', 'rgb('+bw+','+bw+','+bw+')');
					ci++;
				});

				this.$colorPicker.mousedown(function(e){
					if (e.which === 1) _this.leftButtonDown = true;
				});

				this.$colorPicker.mouseup(function(e){
					if (e.which === 1) _this.leftButtonDown = false;
				});

				this.$colorPicker.mouseout(function(e){
					_this.leftButtonDown = false;
				});

				var handleColorChange = function(e){
					if (resizing) return;
					var x = e.offsetX, y = e.offsetY;
					var cure = $(this).attr('id');
					if (!_this.leftButtonDown && e.type !== 'click') return;

					if (!x) {
						x = (e.clientX - this.getBoundingClientRect().left).toFixed();
						y = (e.clientY - this.getBoundingClientRect().top).toFixed();
					}
				
					var color = _this.contexts[cure].context.getImageData(x,y,1,1).data;

					if (e.type === 'click') {
						$(_this.$colorPicker).trigger('colornew', color);
					}

					if (_this.curmode === 'flip' && cure == 'gradientchooser') {
						var id = _this._buildImageData(_this.contexts.colorchooser, 'flipc', _this._RGBtoHSV.apply(null, color).h);
						_this.contexts.colorchooser.context.putImageData(id, 0, 0);
					} else if (_this.curmode != 'flip' && cure != 'gradientchooser') {
						_this._updateGradientCanvas(color);
					}

					$(_this.$colorPicker).trigger('colorchange', color);
				};

				this.$colorChooser.on('click mouseup mousemove', handleColorChange);
				this.$gradientChooser.on('click mouseup mousemove', handleColorChange);

				this.$colorChosen.find('.colorpicker-buttons>button').on('click', function(e) {
					e.preventDefault();
					var val = $(this).val()
					_this.curmode = val;
					_this._updateCanvas();
				});

				this.$commonHistory.find('tr').on('click', 'td', function(e) {
					var rgb, color;
					rgb = $(this).css('background-color').match(/rgb\((\d+), ?(\d+), ?(\d+)\)/);
					if (!rgb) return;
					color = rgb.map(function(v) {return parseInt(v)}).filter(function(v){return !isNaN(v)});
					$(_this.$colorPicker).trigger('colorchange', color);
					$(_this.$colorPicker).trigger('colornew', color);
				});

				this.$colorPicker.on('colorchange', function(e, r, g, b, a) {
					var hsv = _this._RGBtoHSV(r,g,b);
					var cc = _this.$colorChosen;					

					cc.find('.r').text(r);
					cc.find('.g').text(g);
					cc.find('.b').text(b);
					cc.find('.h').text(Math.round(hsv.h*360));
					cc.find('.s').text(hsv.s.toFixed(2));
					cc.find('.v').text(hsv.v.toFixed(2));
				});

				this.$colorPicker.on('colornew', function(e, r, g, b, a) {
					var newtd = $('<td></td>');
					var history = _this.$commonHistory.find('#history');
					newtd.css('background-color', 'rgb('+r+', '+g+', '+b+')');
					history.prepend(newtd);
					history.find('td:last').remove();
				});

				this.$colorPicker.on('colorchange', function(e, r, g, b, a) {
					_this.$commonHistory.find('#curcolor').css('background-color', 'rgb('+r+', '+g+', '+b+')');
				});

				this.$colorPicker.on('colorchange', function(e, r, g, b, a) {
					_this.setColor(window.rgbHex([r, g, b]));
				});
			}

			this.init = true;

			return this;
		},

		/************************************
		 * Setters
		 ************************************/

		setTheme: function(theme) {
			this.$colorPicker.attr('class', this.$colorPicker.attr('class').replace(/wColorPicker-theme-.+\s|wColorPicker-theme-.+$/, ''));
			this.$colorPicker.addClass('wColorPicker-theme-' + theme);
		},

		setOpacity: function(opacity) {
			this.$colorPickerBg.css('opacity', opacity);
		},

		setColor: function(color) {
			if (!window.rgbHex(color)) { return true; }

			this.options.color = color;
			this.$colorTarget.css('backgroundColor', color);
			this.$customInput.val(color);

			if (this.init && this.options.onSelect) { this.options.onSelect.apply(this, [color]); }
		},

		setMode: function(mode) {
			var _this = this,
					showFunc = function(){ _this._toggleEffect('show'); },
					hideFunc = function(){ _this._toggleEffect('hide'); };

			if (mode === 'flat') { this.$colorPicker.removeClass('wColorPicker-zindex').css({position:'relative', display:''}); }
			else { this.$colorPicker.addClass('wColorPicker-zindex').css({position:'absolute'}).hide(); }

			// incase button needs to be reset
			this.$el.find('wColorPicker-button').remove();
			this.$el.unbind('mouseenter', showFunc).unbind('mouseleave', hideFunc);
			$(document).unbind('click', hideFunc);

			// regenerate button
			if (mode !== 'flat') {
				var $button = null,
						$buttonColor = null,
						func = function(e) {
							e.stopPropagation();
							if(_this.options.generateButton) { $buttonColor.css('backgroundColor', _this.options.color); }
							_this._toggleEffect();
						};

				if(this.options.generateButton) {
					$button = $('<div class="wColorPicker-button"></div>');
					$buttonColor = $('<div class="wColorPicker-button-color"></div>').css('backgroundColor', this.options.color);

					this.$el.append($button);
					$button.append($buttonColor.height(this.$el.height() - $button.outerHeight(true)));
				}

				// toggle effect if palette is clicked
				/*this.$noneColorPalette.bind('click', func);
				this.$simpleColorPalette.bind('click', func);
				this.$mixedColorPalette.bind('click', func);*/
			}

			// regenerate events
			if (mode === 'click') {
				this.$el.click(function(e){
					_this._toggleEffect();
					e.stopPropagation(); // so doc event doesn't run
				});

				this.$colorPicker.click(function(e){ e.stopPropagation(); });
				$(document).bind('click', hideFunc);
			}
			else if (mode === 'hover') {
				this.$el.bind('mouseenter', showFunc).bind('mouseleave', hideFunc);
			}
		},

		setEffect: function(effect) {
			if (this.options.mode === 'flat') { return true; }

			// reset
			this.$colorPicker.css('opacity', 1);
			this.$colorPickerHolder.width(this.width).height(this.height);

			if (effect === 'fade') {
				this.$colorPicker.css('opacity', 0);
			}
			else if (effect === 'slide') {
				this.$colorPickerHolder
				.width(this.positionAxis === 'x' ? 0 : this.width)
				.height(this.positionAxis === 'y' ? 0 : this.height);
			}
		},

		setPosition: function(position) {
			if (this.options.mode === 'flat') { return true; }

			var elWidth = this.$el.outerWidth(),
					elHeight = this.$el.outerHeight(),
					center = (this.$el.outerWidth()/2) - (this.$colorPicker.outerWidth()/2),
					middle = (this.$el.outerHeight()/2) - (this.$colorPicker.outerHeight()/2),
					css = {left:'', right:'', top:'', bottom:''},
					firstChar = this.options.position.charAt(0);

			// so we know which way to do our slide effects
			if (firstChar === 't' || firstChar === 'b') { this.positionAxis = 'y'; }
			else if(firstChar === 'l' || firstChar === 'r') { this.positionAxis = 'x'; }

			switch (position) {
				case 'tl': css.left=0;        css.bottom=elHeight; break;
				case 'tc': css.left=center;   css.bottom=elHeight; break;
				case 'tr': css.right=0;       css.bottom=elHeight; break;
				case 'rt': css.left=elWidth;  css.top=0;           break;
				case 'rm': css.left=elWidth;  css.top=middle;      break;
				case 'rb': css.left=elWidth;  css.bottom=0;        break;
				case 'br': css.right=0;       css.top=elHeight;    break;
				case 'bc': css.left=center;   css.top=elHeight;    break;
				case 'bl': css.left=0;        css.top=elHeight;    break;
				case 'lb': css.right=elWidth; css.bottom=0;        break;
				case 'lm': css.right=elWidth; css.top=middle;      break;
				case 'lt': css.right=elWidth; css.top=0;           break;
			}

			this.$colorPicker.css(css);
			this.setEffect(this.options.effect);
		},

		/************************************
		 * Components
		 ************************************/

		// From http://axonflux.com/handy-rgb-to-hsl-and-rgb-to-hsv-color-model-c
		_HSVtoRGB: function(h, s, v) {
			var r, g, b, i, f, p, q, t;
			if (h && s === undefined && v === undefined) {
				s = h.s, v = h.v, h = h.h;
			}
			i = Math.floor(h * 6);
			f = h * 6 - i;
			p = v * (1 - s);
			q = v * (1 - f * s);
			t = v * (1 - (1 - f) * s);
			switch (i % 6) {
				case 0: r = v, g = t, b = p; break;
				case 1: r = q, g = v, b = p; break;
				case 2: r = p, g = v, b = t; break;
				case 3: r = p, g = q, b = v; break;
				case 4: r = t, g = p, b = v; break;
				case 5: r = v, g = p, b = q; break;
			}
			return {
				r: Math.floor(r * 255),
				g: Math.floor(g * 255),
				b: Math.floor(b * 255)
			};
		},
		_RGBtoHSV: function(r, g, b){
			r = r/255, g = g/255, b = b/255;
			var max = Math.max(r, g, b), min = Math.min(r, g, b);
			var h, s, v = max;

			var d = max - min;
			s = max == 0 ? 0 : d / max;

			if (max == min) {
				h = 0; // achromatic
			} else {
				switch (max) {
					case r: h = (g - b) / d + (g < b ? 6 : 0); break;
					case g: h = (b - r) / d + 2; break;
					case b: h = (r - g) / d + 4; break;
				}
				h /= 6;
			}

			return {
				h: h, 
				s: s, 
				v: v
			};
		},

		_buildImageData: function(c, mode, static) {
			var pi = 0, rgb, hh, id, d

			var w = c.element.width;
			var h = c.element.height;
			var id = c.context.createImageData(w,h);
			var d = id.data;

			var setImageData = function(rgb, pi) {
				id.data[4*pi] = rgb.r
				id.data[4*pi+1] = rgb.g
				id.data[4*pi+2] = rgb.b
				id.data[4*pi+3] = 255
			}

			for (ii = 0; ii < h; ii++) {
				for (i = 0; i < w; i++) {
					if (mode === "white") {
						rgb = this._HSVtoRGB(i/w, ii/h, 1);
					} else if (mode === "black") {
						rgb = this._HSVtoRGB(i/w, 1, 1-(ii/h));
					} else if (mode === "flip") {
						rgb = this._HSVtoRGB(ii/h, 1, 1);
					} else if (mode === "flipc") {
						if (i < w/2) {
							rgb = this._HSVtoRGB(static, i/(w/2), 1);
						} else {
							rgb = this._HSVtoRGB(static, 1, 2-(i/(w/2)));
						}
					} else { // both
						if (ii < h/2) {
							rgb = this._HSVtoRGB(i/w, ii/(h/2), 1);
						} else {
							rgb = this._HSVtoRGB(i/w, 1, 2-(ii/(h/2)));
						}
					}
					setImageData(rgb, pi);
					pi++
				}
			}

			return id;
		},

		_updateGradientCanvas: function(color) {
			var gradient = this.contexts.gradientchooser.context.createLinearGradient(0,0,0,this.contexts.gradientchooser.element.height);
			gradient.addColorStop(0, "white");
			gradient.addColorStop(0.5, "rgb("+color[0]+","+color[1]+","+color[2]+")");
			gradient.addColorStop(1, "black");
			this.contexts.gradientchooser.context.fillStyle=gradient;
			this.contexts.gradientchooser.context.fillRect(0,0,this.contexts.gradientchooser.element.width, this.contexts.gradientchooser.element.height);
		},

		_updateCanvas: function() {
			var targetcontext = this.curmode === 'flip' ? this.contexts.gradientchooser : this.contexts.colorchooser;
			var id = this._buildImageData(targetcontext, this.curmode);
			targetcontext.context.putImageData(id, 0, 0);
		},

		_createDropperButton: function() {
			return $('<div class="wColorPicker-dropper"></div>').click($.proxy(this.options.onDropper, this));
		},

		/************************************
		 * Effects
		 ************************************/

		_toggleEffect: function(toggle) {
			var visible = this.$colorPicker.hasClass('wColorPicker-visible');

			// to make sure some effects only occur on a certain state (to avoid an effect running twice and retoggling)
			if(
				!toggle ||
				(toggle === 'show' && visible === false) ||
				(toggle === 'hide' && visible === true)
			) {
				if(!visible) { this.setPosition(this.options.position); }
				this['_' + this.options.effect + 'Effect' + (visible ? 'Hide' : 'Show')]();
				this.$colorPicker.toggleClass('wColorPicker-visible');
			}
		},

		/* none */
		_noneEffectShow: function() {
			this.$colorPicker.css('display', 'inline-block');
		},

		_noneEffectHide: function() {
			this.$colorPicker.hide();
		},

		/* fade */
		_fadeEffectShow: function() {
			this.$colorPicker.stop(true, false).css({display:'inline-block'}).animate({opacity:1}, this.options.showSpeed);
		},

		_fadeEffectHide: function() {
			this.$colorPicker.stop(true, false).animate({opacity:0}, this.options.hideSpeed, $.proxy(function(){ this.$colorPicker.hide(); }, this));
		},

		/* slide */
		_slideEffectShow: function() {
			var animate = this.positionAxis === 'y' ? {height:this.height} : {width:this.width};

			this.$colorPicker.css('display', 'inline-block');
			this.$colorPickerHolder.stop(true, false).animate(animate, this.options.showSpeed);
		},

		_slideEffectHide: function() {
			var animate = this.positionAxis === 'y' ? {height:0} : {width:0};
			this.$colorPickerHolder.stop(true, false).animate(animate, this.options.hideSpeed, $.proxy(function(){ this.$colorPicker.hide(); }, this));
		}
	};

	$.fn.wColorPicker = function(options, value) {
		if (typeof options === 'string') {
			var values = [], wColorPicker = null, elements = null, func = null;
				
			elements = this.each(function() {
				wColorPicker = $(this).data('wColorPicker');

				if (wColorPicker) {
					func = (value ? 'set' : 'get') + options.charAt(0).toUpperCase() + options.substring(1).toLowerCase();

					if (wColorPicker[options]) {
						values.push(wColorPicker[options].apply(wColorPicker, [value]));
					}
					else if (value) {
						if (wColorPicker[func]) { wColorPicker[func].apply(wColorPicker, [value]); }
						if (wColorPicker.options[options]) { wColorPicker.options[options] = value; }
					}
					else {
						if(wColorPicker[func]) { values.push(wColorPicker[func].apply(wColorPicker, [value])); }
						else if (wColorPicker.options[options]) { values.push(wColorPicker.options[options]); }
						else { values.push(null); }
					}
				}
			});

			if (values.length === 1) { return values[0]; }
			else if (values.length > 0) { return values; }
			else { return elements; }
		}

		function get(el) {
			var _options,
					wColorPicker = $.data(el, 'wColorPicker');
			
			if (!wColorPicker) {
				_options = $.extend({}, $.fn.wColorPicker.defaults, options);
				_options.color = window.rgbHex(_options.color) ? _options.color : 'transparent';

				wColorPicker = new ColorPicker(el, _options);
				$.data(el, 'wColorPicker', wColorPicker);
			}

			return wColorPicker;
		}

		return this.each(function(){ get(this); });
	};
	
	$.fn.wColorPicker.defaults = {
		theme           : 'classic',  // set theme
		opacity         : 0.9,        // opacity level
		color           : '#FF0000',  // set init color
		mode            : 'flat',     // mode for palette (flat, hover, click)
		position        : 'bl',       // position of palette, (tl, tc, tr, rt, rm, rb, br, bc, bl, lb, lm, lt)
		generateButton  : true,       // if mode not flat generate button or not
		dropperButton   : false,      // optional dropper button to use in other apps
		effect          : 'slide',    // only used when not in flat mode (none, slide, fade)
		showSpeed       : 500,        // show speed for effects
		hideSpeed       : 500,        // hide speed for effects
		onMouseover     : null,       // callback for color mouseover
		onMouseout      : null,       // callback for color mouseout
		onSelect        : null,       // callback for color when selected
		onDropper       : null        // callback when dropper is clicked
	};

})(jQuery);

(function () {
  if (!String.prototype.capitalize) {
    String.prototype.capitalize = function () {
      return this.slice(0, 1).toUpperCase() + this.slice(1);
    };
  }
})();

(function ($) {
  $.fn.realWidth = function (type, margin, $el) {
    var width = null, $div = null, method = null;

    type = type === 'inner' || type === 'outer' ? type : '';
    method = type === '' ? 'width' : type + 'Width';
    margin = margin === true ? true : false;
    $div = $(this).clone().css({position: 'absolute', left: -10000}).appendTo($el || 'body');
    width = margin ? $div[method](margin) : $div[method]();

    $div.remove();

    return width;
  };

  $.fn.realHeight = function (type, margin, $el) {
    var height = null, $div = null, method = null;

    type = type === 'inner' || type === 'outer' ? type : '';
    method = type === '' ? 'height' : type + 'Height';
    margin = margin === true ? true : false;
    $div = $(this).clone().css({position: 'absolute', left: -10000}).appendTo($el || 'body');
    height = margin ? $div[method](margin) : $div[method]();

    $div.remove();

    return height;
  };

  $.fn.bindMobileEvents = function () {
    $(this).on('touchstart touchmove touchend touchcancel', function () {
      var touches = (event.changedTouches || event.originalEvent.targetTouches),
          first = touches[0],
          type = '';

      switch (event.type) {
      case 'touchstart':
        type = 'mousedown';
        break;
      case 'touchmove':
        type = 'mousemove';
        event.preventDefault();
        break;
      case 'touchend':
        type = 'mouseup';
        break;
      default:
        return;
      }

      var simulatedEvent = document.createEvent('MouseEvent'); 

      simulatedEvent.initMouseEvent(
        type, true, true, window, 1, 
        first.screenX, first.screenY, first.clientX, first.clientY, 
        false, false, false, false, 0/*left*/, null
      );

      first.target.dispatchEvent(simulatedEvent);
    });
  };
})(jQuery);

(function ($) {
	'use strict';

	/************************************************************************
	 * Paint class
	 ************************************************************************/
	function Paint(el, options) {
		this.$el = $(el);
		this.options = options;
		this.init = false;

		this.menus = {primary: null, active: null, all: {}};
		this.previousMode = null;
		this.width = this.$el.width();
		this.height = this.$el.height();

		this.ctxBgResize = false;
		this.ctxResize = false;

		this.generate();
		this._init();
	}
	
	Paint.prototype = {
		generate: function () {
			if (this.init) { return this; }

			var _this = this;

			// automatically appends each canvas
			// also returns the jQuery object so we can chain events right off the function call.
			// for the tempCanvas we will be setting some extra attributes but don't won't matter
			// as they will be reset on mousedown anyway.
			function createCanvas(name) {
				var newName = (name ? name.capitalize() : ''),
						canvasName = 'canvas' + newName,
						ctxName = 'ctx' + newName;

				_this[canvasName] = document.createElement('canvas');
				_this[ctxName] = _this[canvasName].getContext('2d');
				_this['$' + canvasName] = $(_this[canvasName]);
				
				_this['$' + canvasName]
				.attr('class', 'wPaint-canvas' + (name ? '-' + name : ''))
				.attr('width', _this.width + 'px')
				.attr('height', _this.height + 'px')
				.css({position: 'absolute', left: 0, top: 0});

				_this.$el.append(_this['$' + canvasName]);

				return _this['$' + canvasName];
			}

			// event functions
			function canvasMousedown(e) {
				e.preventDefault();
				e.stopPropagation();
				_this.draw = true;
				e.canvasEvent = 'down';
				_this._closeSelectBoxes();
				_this._callShapeFunc.apply(_this, [e]);
			}

			function documentMousemove(e) {
				if (_this.draw) {
					e.canvasEvent = 'move';
					_this._callShapeFunc.apply(_this, [e]);
				}
			}

			function documentMouseup(e) {

				//make sure we are in draw mode otherwise this will fire on any mouse up.
				if (_this.draw) {
					_this.draw = false;
					e.canvasEvent = 'up';
					_this._callShapeFunc.apply(_this, [e]);
				}
			}

			// create bg canvases
			createCanvas('bg');
			
			// create drawing canvas
			createCanvas('')
			.on('mousedown', canvasMousedown)
			.bindMobileEvents();
			
			// create temp canvas for drawing shapes temporarily
			// before transfering to main canvas
			createCanvas('temp').hide();
			
			// event handlers for drawing
			$(document)
			.on('mousemove', documentMousemove)
			.on('mousedown', $.proxy(this._closeSelectBoxes, this))
			.on('mouseup', documentMouseup);

			// we will need to preset theme to get proper dimensions
			// when creating menus which will be appended after this
			this.setTheme(this.options.theme);
		},

		_init: function () {
			var index = null,
					setFuncName = null;

			this.init = true;

			// run any set functions if they exist
			for (index in this.options) {
				setFuncName = 'set' + index.capitalize();
				if (this[setFuncName]) { this[setFuncName](this.options[index]); }
			}

			// fix menus
			this._fixMenus();

			// initialize active menu button
			this.menus.primary._getIcon(this.options.mode).trigger('click');      
		},

		resize: function () {
			var bg = this.getBg(),
					image = this.getImage();

			this.width = this.$el.width();
			this.height = this.$el.height();

			this.canvasBg.width = this.width;
			this.canvasBg.height = this.height;
			this.canvas.width = this.width;
			this.canvas.height = this.height;

			if (this.ctxBgResize === false) {
				this.ctxBgResize = true;
				this.setBg(bg, true);
			}

			if (this.ctxResize === false) {
				this.ctxResize = true;
				this.setImage(image, '', true, true);
			}
		},

		/************************************
		 * setters
		 ************************************/
		setTheme: function (theme) {
			var i, ii;

			theme = theme.split(' ');

			// remove anything beginning with "wPaint-theme-" first
			this.$el.attr('class', (this.$el.attr('class') || '').replace(/wPaint-theme-.+\s|wPaint-theme-.+$/, ''));
			
			// add each theme
			for (i = 0, ii = theme.length; i < ii; i++) {
				this.$el.addClass('wPaint-theme-' + theme[i]);
			}
		},

		setMode: function (mode) {
			this.setCursor(mode);
			this.previousMode = this.options.mode;
			this.options.mode = mode;
		},

		setImage: function (img, ctxType, resize, notUndo) {
			if (!img) { return true; }

			var _this = this,
					myImage = null,
					ctx = '';

			function loadImage() {
				var ratio = 1, xR = 0, yR = 0, x = 0, y = 0, w = myImage.width, h = myImage.height;

				if (!resize) {
					// get width/height
					if (myImage.width > _this.width || myImage.height > _this.height || _this.options.imageStretch) {
						xR = _this.width / myImage.width;
						yR = _this.height / myImage.height;

						ratio = xR < yR ? xR : yR;

						w = myImage.width * ratio;
						h = myImage.height * ratio;
					}

					// get left/top (centering)
					x = (_this.width - w) / 2;
					y = (_this.height - h) / 2;
				}

				ctx.clearRect(0, 0, _this.width, _this.height);
				ctx.drawImage(myImage, x, y, w, h);

				_this[ctxType + 'Resize'] = false;

				// Default is to run the undo.
				// If it's not to be run set it the flag to true.
				if (!notUndo) {
					_this._addUndo();
				}
			}
			
			ctxType = 'ctx' + (ctxType || '').capitalize();
			ctx = this[ctxType];
			
			if (window.rgbHex(img)) {
				ctx.clearRect(0, 0, this.width, this.height);
				ctx.fillStyle = img;
				ctx.rect(0, 0, this.width, this.height);
				ctx.fill();
			}
			else {
	if (notUndo) ctx.clearRect(0, 0, this.width, this.height);
				myImage = new Image();
				$(myImage).on('load', loadImage);
	if (typeof img.slice !== "undefined" && img.slice(0, 4) !== "data") myImage.setAttribute('crossOrigin','anonymous');
				myImage.src = img;
			}
		},

		setBg: function (img, resize) {
			if (!img) { return true; }
			
			this.setImage(img, 'bg', resize, true);
		},

		setCursor: function (cursor) {
			cursor = $.fn.wPaint.cursors[cursor] || $.fn.wPaint.cursors['default'];

			this.$el.css('cursor', 'url("' + this.options.path + cursor.path + '") ' + cursor.left + ' ' + cursor.top + ', default');
		},

		setMenuOrientation: function (orientation) {
			$.each(this.menus.all, function (i, menu) {
				menu.options.aligment = orientation;
				menu.setAlignment(orientation);
			});
		},

		getImage: function (withBg) {
			var canvasSave = document.createElement('canvas'),
					ctxSave = canvasSave.getContext('2d');

			withBg = withBg === false ? false : true;

			$(canvasSave)
			.css({display: 'none', position: 'absolute', left: 0, top: 0})
			.attr('width', this.width)
			.attr('height', this.height);

			if (withBg) { ctxSave.drawImage(this.canvasBg, 0, 0); }
			ctxSave.drawImage(this.canvas, 0, 0);

			return canvasSave.toDataURL();
		},

		getBg: function () {
			return this.canvasBg.toDataURL();
		},

		/************************************
		 * prompts
		 ************************************/
		_displayStatus: function (msg) {
			var _this = this;

			if (!this.$status) {
				this.$status = $('<div class="wPaint-status"></div>');
				this.$el.append(this.$status);
			}

			this.$status.html(msg);
			clearTimeout(this.displayStatusTimer);

			this.$status.fadeIn(500, function () {
				_this.displayStatusTimer = setTimeout(function () { _this.$status.fadeOut(500); }, 1500);
			});
		},

		_showModal: function ($content) {
			var _this = this,
					$bg = this.$el.children('.wPaint-modal-bg'),
					$modal = this.$el.children('.wPaint-modal');

			function modalFadeOut() {
					$bg.remove();
					$modal.remove();
					_this._createModal($content);
				}

			if ($bg.length) {
				$modal.fadeOut(500, modalFadeOut);
			}
			else {
				this._createModal($content);
			}
		},

		_createModal: function ($content) {
			$content = $('<div class="wPaint-modal-content"></div>').append($content.children());

			var $bg = $('<div class="wPaint-modal-bg"></div>'),
					$modal = $('<div class="wPaint-modal"></div>'),
					$holder = $('<div class="wPaint-modal-holder"></div>'),
					$close = $('<div class="wPaint-modal-close">X</div>');

			function modalClick() {
				$modal.fadeOut(500, modalFadeOut);
			}

			function modalFadeOut() {
				$bg.remove();
				$modal.remove();
			}

			$close.on('click', modalClick);
			$modal.append($holder.append($content)).append($close);
			this.$el.append($bg).append($modal);

			$modal.css({
				left: (this.$el.outerWidth() / 2) - ($modal.outerWidth(true) / 2),
				top: (this.$el.outerHeight() / 2) - ($modal.outerHeight(true) / 2)
			});

			$modal.fadeIn(500);
		},

		/************************************
		 * menu helpers
		 ************************************/
		_createMenu: function (name, options) {
			options = options || {};
			options.alignment = this.options.menuOrientation;
			options.handle = this.options.menuHandle;
			
			return new Menu(this, name, options);
		},

		_fixMenus: function () {
			var _this = this,
					$selectHolder = null;

			function selectEach(i, el) {
				var $el = $(el),
						$select = $el.clone();

				$select.appendTo(_this.$el);

				if ($select.outerHeight() === $select.get(0).scrollHeight) {
					$el.css({overflowY: 'auto'});
				}

				$select.remove();
			}

			// TODO: would be nice to do this better way
			// for some reason when setting overflowY:auto with dynamic content makes the width act up
			for (var key in this.menus.all) {
				$selectHolder = _this.menus.all[key].$menu.find('.wPaint-menu-select-holder');
				if ($selectHolder.length) { $selectHolder.children().each(selectEach); }
			}
		},

		_closeSelectBoxes: function (item) {
			var key, $selectBoxes;

			for (key in this.menus.all) {
				$selectBoxes = this.menus.all[key].$menuHolder.children('.wPaint-menu-icon-select');

				// hide any open select menus excluding the current menu
				// this is to avoid the double toggle since there are some
				// other events running here
				if (item) { $selectBoxes = $selectBoxes.not('.wPaint-menu-icon-name-' + item.name); }

				$selectBoxes.children('.wPaint-menu-select-holder').hide();
			}
		},

		/************************************
		 * events
		 ************************************/
		//_imageOnload: function () {
		//  /* a blank helper function for post image load calls on canvas - can be extended by other plugins using the setImage called */
		//},

		_callShapeFunc: function (e) {

			// TODO: this is where issues with mobile offsets are probably off
			var canvasOffset = this.$canvas.offset(),
					canvasEvent = e.canvasEvent.capitalize(),
					func = '_draw' + this.options.mode.capitalize() + canvasEvent;

			// update offsets here since we are detecting mouseup on $(document) not on the canvas
			e.pageX = Math.floor(e.pageX - canvasOffset.left);
			e.pageY = Math.floor(e.pageY - canvasOffset.top);

			// call drawing func
			if (this[func]) { this[func].apply(this, [e]); }

			// run callback if set
			if (this.options['draw' + canvasEvent]) { this.options['_draw' + canvasEvent].apply(this, [e]); }

			// run options (user) callback if set
			if (canvasEvent === 'Down' && this.options.onShapeDown) { this.options.onShapeDown.apply(this, [e]); }
			else if (canvasEvent === 'Move' && this.options.onShapeMove) { this.options.onShapeMove.apply(this, [e]); }
			else if (canvasEvent === 'Up' && this.options.onShapeUp) { this.options.onShapeUp.apply(this, [e]); }
		},

		_stopPropagation: function (e) {
			e.stopPropagation();
		},

		/************************************
		 * shape helpers
		 ************************************/
		_drawShapeDown: function (e) {
			this.$canvasTemp
			.css({left: e.PageX, top: e.PageY})
			.attr('width', 0)
			.attr('height', 0)
			.show();

			this.canvasTempLeftOriginal = e.pageX;
			this.canvasTempTopOriginal = e.pageY;
		},
		
		_drawShapeMove: function (e, factor) {
			var xo = this.canvasTempLeftOriginal,
					yo = this.canvasTempTopOriginal;

			// we may need these in other funcs, so we'll just pass them along with the event
			factor = factor || 2;
			e.left = (e.pageX < xo ? e.pageX : xo);
			e.top = (e.pageY < yo ? e.pageY : yo);
			e.width = Math.abs(e.pageX - xo);
			e.height = Math.abs(e.pageY - yo);
			e.x = this.options.lineWidth / 2 * factor;
			e.y = this.options.lineWidth / 2 * factor;
			e.w = e.width - this.options.lineWidth * factor;
			e.h = e.height - this.options.lineWidth * factor;

			$(this.canvasTemp)
			.css({left: e.left, top: e.top})
			.attr('width', e.width)
			.attr('height', e.height);
			
			// store these for later to use in our "up" call
			this.canvasTempLeftNew = e.left;
			this.canvasTempTopNew = e.top;

			factor = factor || 2;

			// TODO: set this globally in _drawShapeDown (for some reason colors are being reset due to canvas resize - is there way to permanently set it)
			this.ctxTemp.fillStyle = this.options.fillStyle;
			this.ctxTemp.strokeStyle = this.options.strokeStyle;
			this.ctxTemp.lineWidth = this.options.lineWidth * factor;
		},
		
		_drawShapeUp: function () {
			this.ctx.drawImage(this.canvasTemp, this.canvasTempLeftNew, this.canvasTempTopNew);
			this.$canvasTemp.hide();
		},

		/****************************************
		 * dropper
		 ****************************************/
		_drawDropperDown: function (e) {
			var pos = {x: e.pageX, y: e.pageY},
					pixel = this._getPixel(this.ctx, pos),
					color = null;

			// if we get no color try getting from the background
			//if(pixel.r === 0 && pixel.g === 0 && pixel.b === 0 && pixel.a === 0) {
			//  imageData = this.ctxBg.getImageData(0, 0, this.width, this.height)
			//  pixel = this._getPixel(imageData, pos);
			//}

			color = 'rgba(' + [ pixel.r, pixel.g, pixel.b, pixel.a ].join(',') + ')';

			// set color from dropper here
			this.options[this.dropper] = color;
			this.menus.active._getIcon(this.dropper).wColorPicker('color', color);
		},

		_drawDropperUp: function () {
			this.setMode(this.previousMode);
		},

		// get pixel data represented as RGBa color from pixel array.
		_getPixel: function (ctx, pos) {
			var imageData = ctx.getImageData(0, 0, this.width, this.height),
					pixelArray = imageData.data,
					base = ((pos.y * imageData.width) + pos.x) * 4;
			
			return {
				r: pixelArray[base],
				g: pixelArray[base + 1],
				b: pixelArray[base + 2],
				a: pixelArray[base + 3]
			};
		}
	};

	/************************************************************************
	 * Menu class
	 ************************************************************************/
	function Menu(wPaint, name, options) {
		this.wPaint = wPaint;
		this.options = options;
		this.name = name;
		this.type = !wPaint.menus.primary ? 'primary' : 'secondary';
		this.docked = true;
		this.dockOffset = {left: 0, top: 0};

		this.generate();
	}
	
	Menu.prototype = {
		generate: function () {
			this.$menu = $('<div class="wPaint-menu"></div>');
			this.$menuHolder = $('<div class="wPaint-menu-holder wPaint-menu-name-' + this.name + '"></div>');
			
			if (this.options.handle) { this.$menuHandle = this._createHandle(); }
			else { this.$menu.addClass('wPaint-menu-nohandle'); }
			
			if (this.type === 'primary') {

				// store the primary menu in primary object - we will need this reference later
				this.wPaint.menus.primary = this;

				this.setOffsetLeft(this.options.offsetLeft);
				this.setOffsetTop(this.options.offsetTop);
			}
			else if (this.type === 'secondary') {
				this.$menu.hide();
			}

			// append menu items
			this.$menu.append(this.$menuHolder.append(this.$menuHandle));
			this.reset();
			
			// append menu
			this.wPaint.$el.append(this.$menu);

			this.setAlignment(this.options.alignment);
		},

		// create / reset menu - will add new entries in the array
		reset: function () {
			var _this = this,
					menu = $.fn.wPaint.menus[this.name],
					key;

			// self invoking function
			function itemAppend(item) { _this._appendItem(item); }

			for (key in menu.items) {

				// only add unique (new) items (icons)
				if (!this.$menuHolder.children('.wPaint-menu-icon-name-' + key).length) {
					
					// add the item name, we will need this internally
					menu.items[key].name = key;

					// check whether the paths are already set
					if (menu.items[key].pathSet === undefined) {
						menu.items[key].pathSet = true;

						// use default img if img not set
						menu.items[key].img = _this.wPaint.options.path + (menu.items[key].img || menu.img);
					}

					// make self invoking to avoid overwrites
					(itemAppend)(menu.items[key]);
				}
			}
		},

		_appendItem: function (item) {
			var $item = this['_createIcon' + item.icon.capitalize()](item);

			if (item.after) {
				this.$menuHolder.children('.wPaint-menu-icon-name-' + item.after).after($item);
			}
			else {
				this.$menuHolder.append($item);
			}
		},

		/************************************
		 * setters
		 ************************************/
		setOffsetLeft: function (left) {
			this.$menu.css({left: left});
		},

		setOffsetTop: function (top) {
			this.$menu.css({top: top});
		},

		setAlignment: function (alignment) {
			var tempLeft = this.$menu.css('left');

			this.$menu.attr('class', this.$menu.attr('class').replace(/wPaint-menu-alignment-.+\s|wPaint-menu-alignment-.+$/, ''));
			this.$menu.addClass('wPaint-menu-alignment-' + alignment);

			this.$menu.width('auto').css('left', -10000);
			this.$menu.width(this.$menu.width()).css('left', tempLeft);

			// set proper offsets based on alignment
			if (this.type === 'secondary') {
				if (this.options.alignment === 'horizontal') {
					this.dockOffset.top = this.wPaint.menus.primary.$menu.outerHeight(true);
				}
				else {
					this.dockOffset.left = this.wPaint.menus.primary.$menu.outerWidth(true);
				}
			}   
		},

		/************************************
		 * handle
		 ************************************/
		_createHandle: function () {
			var _this = this,
			$handle = $('<div class="wPaint-menu-handle"></div>');

			// draggable functions
			function draggableStart() {
				_this.docked = false;
				_this._setDrag();
			}

			function draggableStop() {
				$.each(_this.$menu.data('ui-draggable').snapElements, function (i, el) {
					var offset = _this.$menu.offset(),
					offsetPrimary = _this.wPaint.menus.primary.$menu.offset();

					_this.dockOffset.left = offset.left - offsetPrimary.left;
					_this.dockOffset.top = offset.top - offsetPrimary.top;
					_this.docked = el.snapping;
				});

				_this._setDrag();
			}

			function draggableDrag() {
				_this._setIndex();
			}

			// the drag/snap events for menus are tricky
			// init handle for ALL menus, primary menu will drag a secondary menu with it, but that is un/binded in the toggle function
			this.$menu.draggable({handle: $handle});

			// if it's a secondary menu we want to check for snapping
			// on drag we set docked to false, on snap we set it back to true
			if (this.type === 'secondary') {
				this.$menu.draggable('option', 'snap', this.wPaint.menus.primary.$menu);
				this.$menu.draggable('option', 'start', draggableStart);
				this.$menu.draggable('option', 'stop', draggableStop);
				this.$menu.draggable('option', 'drag', draggableDrag);
			}

			$handle.bindMobileEvents();

			return $handle;
		},

		/************************************
		 * generic icon
		 ************************************/
		_createIconBase: function (item) {
			var _this = this,
			$icon = $('<div class="wPaint-menu-icon wPaint-menu-icon-name-' + item.name + '"></div>'),
			$iconImg = $('<div class="wPaint-menu-icon-img"></div>'),
			width = $iconImg.realWidth(null, null, this.wPaint.$el);

			function mouseenter(e) {
				var $el = $(e.currentTarget);

				$el.siblings('.hover').removeClass('hover');
				if (!$el.hasClass('disabled')) { $el.addClass('hover'); }
			}

			function mouseleave(e) {
				$(e.currentTarget).removeClass('hover');
			}

			function click() {
				_this.wPaint.menus.active = _this;
			}

			var hasKeybind = (typeof item.keybind !== "undefined");
			var formattedTitle = (hasKeybind ? (item.title + " (" + item.keybind + ")") : item.title);
			var activatedFunction = $.proxy(this.wPaint._closeSelectBoxes, this.wPaint, item);

			$icon
			.attr('title', formattedTitle)
			.on('mousedown', activatedFunction)
			.on('mouseenter', mouseenter)
			.on('mouseleave', mouseleave)
			.on('click', click);

			if (hasKeybind) { 
				key(item.keybind, function(e) {
					$.proxy(item.callback, _this.wPaint)();
					_this._iconClick($icon);
					click();
					activatedFunction();
				});
			}

			// can have index:0 so be careful here
			if ($.isNumeric(item.index)) {
				$iconImg
				.css({
					backgroundImage: 'url(' + item.img + ')',
					backgroundPosition: (-width * item.index) + 'px 0px'
				});
			}

			return $icon.append($iconImg);
		},

		/************************************
		 * icon group
		 ************************************/
		_createIconGroup: function (item) {
			var _this = this,
					css = {backgroundImage: 'url(' + item.img + ')'},
					$icon = this.$menuHolder.children('.wPaint-menu-icon-group-' + item.group),
					iconExists = $icon.length,
					$selectHolder = null,
					$option = null,
					$item = null,
					width = 0;

			// local functions
			function setIconClick() {

				// only trigger if menu is not visible otherwise it will fire twice
				// from the mousedown to open the menu which we want just to display the menu
				// not fire the button callback
				if (!$icon.children('.wPaint-menu-select-holder').is(':visible')) {
					item.callback.apply(_this.wPaint, []);
				}
			}

			function selectHolderClick() {
				$icon.addClass('active').siblings('.active').removeClass('active');
			}

			function optionClick() {

				// rebind the main icon when we select an option
				$icon
				.attr('title', item.title)
				.off('click.setIcon')
				.on('click.setIcon', setIconClick);
				
				// run the callback right away when we select an option
				$icon.children('.wPaint-menu-icon-img').css(css);
				item.callback.apply(_this.wPaint, []);
			}

			// crate icon if it doesn't exist yet
			if (!iconExists) {
				$icon = this._createIconBase(item)
				.addClass('wPaint-menu-icon-group wPaint-menu-icon-group-' + item.group)
				.on('click.setIcon', setIconClick)
				.on('mousedown', $.proxy(this._iconClick, this));
			}

			// get the proper width here now that we have the icon
			// this is for the select box group not the main icon
			width = $icon.children('.wPaint-menu-icon-img').realWidth(null, null, this.wPaint.$el);
			css.backgroundPosition = (-width * item.index) + 'px center';

			// create selectHolder if it doesn't exist
			$selectHolder = $icon.children('.wPaint-menu-select-holder');
			if (!$selectHolder.length) {
				$selectHolder = this._createSelectBox($icon);
				$selectHolder.children().on('click', selectHolderClick);
			}

			$item = $('<div class="wPaint-menu-icon-select-img"></div>')
			.attr('title', item.title)
			.css(css);

			$option = this._createSelectOption($selectHolder, $item)
			.addClass('wPaint-menu-icon-name-' + item.name)
			.on('click', optionClick);

			// move select option into place if after is set
			if (item.after) {
				$selectHolder.children('.wPaint-menu-select').children('.wPaint-menu-icon-name-' + item.after).after($option);
			}

			// we only want to return an icon to append on the first run of a group
			if (!iconExists) { return $icon; }
		},

		/************************************
		 * icon generic
		 ************************************/
		_createIconGeneric: function (item) {

			// just a go between for the iconGeneric type
			return this._createIconActivate(item);
		},

		/************************************
		 * icon
		 ************************************/
		_createIconActivate: function (item) {

			// since we are piggy backing icon with the item.group
			// we'll just do a redirect and keep the code separate for group icons
			if (item.group) { return this._createIconGroup(item); }

			var _this = this,
					$icon = this._createIconBase(item);

			function iconClick(e) {
				if (item.icon !== 'generic') { _this._iconClick(e); }
				item.callback.apply(_this.wPaint, [e]);
			}

			$icon.on('click', iconClick);

			return $icon;
		},

		_isIconDisabled: function (name) {
			return this.$menuHolder.children('.wPaint-menu-icon-name-' + name).hasClass('disabled');
		},

		_setIconDisabled: function (name, disabled) {
			var $icon = this.$menuHolder.children('.wPaint-menu-icon-name-' + name);

			if (disabled) {
				$icon.addClass('disabled').removeClass('hover');
			}
			else {
				$icon.removeClass('disabled');
			}
		},

		_getIcon: function (name) {
			return this.$menuHolder.children('.wPaint-menu-icon-name-' + name);
		},

		_iconClick: function (e) {
			if (typeof e.currentTarget !== "undefined") {
				var $el = $(e.currentTarget);
			} else {
				var $el = $(e);
			}

			var menus = this.wPaint.menus.all;

			// make sure to loop using parent object - don't use .wPaint-menu-secondary otherwise we would hide menu for all canvases
			for (var menu in menus) {
				if (menus[menu] && menus[menu].type === 'secondary') { menus[menu].$menu.hide(); }  
			}

			$el.siblings('.active').removeClass('active');
			if (!$el.hasClass('disabled')) { $el.addClass('active'); }
		},

		/************************************
		 * iconToggle
		 ************************************/
		_createIconToggle: function (item) {
			var _this = this,
					$icon = this._createIconBase(item);

			function iconClick() {
				$icon.toggleClass('active');
				item.callback.apply(_this.wPaint, [$icon.hasClass('active')]);
			}

			$icon.on('click', iconClick);

			return $icon;
		},

		/************************************
		 * select
		 ************************************/
		_createIconSelect: function (item) {
			var _this = this,
					$icon = this._createIconBase(item),
					$selectHolder = this._createSelectBox($icon),
					i, ii, $option;

			function optionClick(e) {
				$icon.children('.wPaint-menu-icon-img').html($(e.currentTarget).html());
				item.callback.apply(_this.wPaint, [$(e.currentTarget).html()]);
			}

			// add values for select
			for (i = 0, ii = item.range.length; i < ii; i++) {
				$option = this._createSelectOption($selectHolder, item.range[i]);
				$option.on('click', optionClick);
				if (item.useRange) { $option.css(item.name, item.range[i]); }
			}

			return $icon;
		},

		_createSelectBox: function ($icon) {
			var $selectHolder = $('<div class="wPaint-menu-select-holder"></div>'),
					$select = $('<div class="wPaint-menu-select"></div>'),
					timer = null;

			function clickSelectHolder(e) {
				e.stopPropagation();
				$selectHolder.hide();
			}

			function iconMousedown() {
				timer = setTimeout(function () { $selectHolder.toggle(); }, 200);
			}

			function iconMouseup() {
				clearTimeout(timer);
			}

			function iconClick() {
				$selectHolder.toggle();
			}

			$selectHolder
			.on('mousedown mouseup', this.wPaint._stopPropagation)
			.on('click', clickSelectHolder)
			.hide();

			// of hozizontal we'll pop below the icon
			if (this.options.alignment === 'horizontal') {
				$selectHolder.css({left: 0, top: $icon.children('.wPaint-menu-icon-img').realHeight('outer', true, this.wPaint.$el)});
			}
			// vertical we'll pop to the right
			else {
				$selectHolder.css({left: $icon.children('.wPaint-menu-icon-img').realWidth('outer', true, this.wPaint.$el), top: 0});
			}

			$icon
			.addClass('wPaint-menu-icon-select')
			.append('<div class="wPaint-menu-icon-group-arrow"></div>')
			.append($selectHolder.append($select));

			// for groups we want to add a delay before the selectBox pops up
			if ($icon.hasClass('wPaint-menu-icon-group')) {
				$icon
				.on('mousedown', iconMousedown)
				.on('mouseup', iconMouseup);
			}
			else { $icon.on('click', iconClick); }

			return $selectHolder;
		},

		_createSelectOption: function ($selectHolder, value) {
			var $select = $selectHolder.children('.wPaint-menu-select'),
					$option = $('<div class="wPaint-menu-select-option"></div>').append(value);

			// set class for first item to remove any undesired styles like borders
			if (!$select.children().length) { $option.addClass('first'); }

			$select.append($option);

			return $option;
		},

		_setSelectValue: function (icon, value) {
			this._getIcon(icon).children('.wPaint-menu-icon-img').html(value);
		},

		/************************************
		 * color picker
		 ************************************/
		_createIconColorPicker: function (item) {
			var _this = this,
					$icon = this._createIconBase(item);

			function iconClick() {

				// if we happen to click on this while in dropper mode just revert to previous
				if (_this.wPaint.options.mode === 'dropper') { _this.wPaint.setMode(_this.wPaint.previousMode); }
			}

			function iconOnSelect(color) {
				item.callback.apply(_this.wPaint, [color]);
			}

			function iconOnDropper() {
				$icon.trigger('click');
				_this.wPaint.dropper = item.name;
				_this.wPaint.setMode('dropper');
			}

			$icon
			.on('click', iconClick)
			.addClass('wPaint-menu-colorpicker')
			.wColorPicker({
				mode: 'click',
				generateButton: false,
				dropperButton: true,
				onSelect: iconOnSelect,
				onDropper: iconOnDropper
			});

			return $icon;
		},

		_setColorPickerValue: function (icon, value) {
			this._getIcon(icon).children('.wPaint-menu-icon-img').css('backgroundColor', value);
		},

		/************************************
		 * menu toggle
		 ************************************/
		_createIconMenu: function (item) {
			var _this = this,
					$icon = this._createIconActivate(item);

			function iconClick() {
				_this.wPaint.setCursor(item.name);

				// the items name here will be the menu name
				var menu = _this.wPaint.menus.all[item.name];
				menu.$menu.toggle();
				if (_this.handle) {
					menu._setDrag();
				} else {
					menu._setPosition();
				}
			}

			$icon.on('click', iconClick);

			return $icon;
		},

		// here we specify which menu will be dragged
		_setDrag: function () {
			var $menu = this.$menu,
					drag = null, stop = null;

			if ($menu.is(':visible')) {
				if (this.docked) {

					// make sure we are setting proper menu object here
					drag = stop = $.proxy(this._setPosition, this);
					this._setPosition();
				}

				// register drag/stop events
				this.wPaint.menus.primary.$menu.draggable('option', 'drag', drag);
				this.wPaint.menus.primary.$menu.draggable('option', 'stop', stop);
			}
		},

		_setPosition: function () {
			var offset = this.wPaint.menus.primary.$menu.position();

			this.$menu.css({
				left: offset.left + this.dockOffset.left,
				top: offset.top + this.dockOffset.top
			});
		},

		_setIndex: function () {
			var primaryOffset = this.wPaint.menus.primary.$menu.offset(),
					secondaryOffset = this.$menu.offset();

			if (
				secondaryOffset.top < primaryOffset.top ||
				secondaryOffset.left < primaryOffset.left
			) {
				this.$menu.addClass('wPaint-menu-behind');
			}
			else {
				this.$menu.removeClass('wPaint-menu-behind');
			}
		}
	};

	/************************************************************************
	 * wPaint
	 ************************************************************************/
	$.support.canvas = (document.createElement('canvas')).getContext;

	$.fn.wPaint = function (options, value) {

		function create() {
			if (!$.support.canvas) {
				$(this).html('Browser does not support HTML5 canvas, please upgrade to a more modern browser.');
				return false;
			}

			return $.proxy(get, this)();
		}

		function get() {
			var wPaint = $.data(this, 'wPaint');

			if (!wPaint) {
				wPaint = new Paint(this, $.extend(true, {}, options));
				$.data(this, 'wPaint', wPaint);
			}

			return wPaint;
		}

		function runOpts() {
			var wPaint = $.data(this, 'wPaint');

			if (wPaint) {
				if (wPaint[options]) { wPaint[options].apply(wPaint, [value]); }
				else if (value !== undefined) {
					if (wPaint[func]) { wPaint[func].apply(wPaint, [value]); }
					if (wPaint.options[options]) { wPaint.options[options] = value; }
				}
				else {
					if (wPaint[func]) { values.push(wPaint[func].apply(wPaint, [value])); }
					else if (wPaint.options[options]) { values.push(wPaint.options[options]); }
					else { values.push(undefined); }
				}
			}
		}

		if (typeof options === 'string') {
			var values = [],
					func = (value ? 'set' : 'get') + options.charAt(0).toUpperCase() + options.substring(1);

			this.each(runOpts);

			if (values.length) { return values.length === 1 ? values[0] : values; }
			
			return this;
		}

		options = $.extend({}, $.fn.wPaint.defaults, options);
		options.lineWidth = parseInt(options.lineWidth, 10);
		options.fontSize = parseInt(options.fontSize, 10);

		return this.each(create);
	};

	/************************************************************************
	 * extend
	 ************************************************************************/
	$.fn.wPaint.extend = function (funcs, protoType) {
		var key;
		
		function elEach(func) {
			if (protoType[func]) {
				var tmpFunc = Paint.prototype[func],
						newFunc = funcs[func];
				
				protoType[func] = function () {
					tmpFunc.apply(this, arguments);
					newFunc.apply(this, arguments);
				};
			}
			else {
				protoType[func] = funcs[func];
			}
		}

		protoType = protoType === 'menu' ? Menu.prototype : Paint.prototype;

		for (key in funcs) { (elEach)(key); }
	};

	/************************************************************************
	 * Init holders
	 ************************************************************************/
	$.fn.wPaint.menus = {};

	$.fn.wPaint.cursors = {};

	$.fn.wPaint.defaults = {
		path:            '/',                // set absolute path for images and cursors
		theme:           'standard classic', // set theme
		autoScaleImage:  true,               // auto scale images to size of canvas (fg and bg)
		autoCenterImage: true,               // auto center images (fg and bg, default is left/top corner)
		menuHandle:      true,               // setting to false will means menus cannot be dragged around
		menuOrientation: 'horizontal',       // menu alignment (horizontal,vertical)
		menuOffsetLeft:  5,                  // left offset of primary menu
		menuOffsetTop:   5,                  // top offset of primary menu
		bg:              null,               // set bg on init
		image:           null,               // set image on init
		imageStretch:    false,              // stretch smaller images to full canvans dimensions
		onShapeDown:     null,               // callback for draw down event
		onShapeMove:     null,               // callback for draw move event
		onShapeUp:       null                // callback for draw up event
	};
})(jQuery);

(function ($) {

  // setup menu
  $.fn.wPaint.menus.main = {
    img: 'plugins/main/img/icons-menu-main.png',
    items: {
      undo: {
        icon: 'generic',
        title: _('Undo'),
        keybind: 'alt+z',
        index: 0,
        callback: function () { this.undo(); }
      },
      redo: {
        icon: 'generic',
        title: _('Redo'),
        keybind: 'alt+shift+z,alt+r',
        index: 1,
        callback: function () { this.redo(); }
      },
      clear: {
        icon: 'generic',
        title: _('Clear'),
        keybind: 'alt+x',
        index: 2,
        callback: function () { this.clear(); }
      },
      rectangle: {
        icon: 'activate',
        title: _('Rectangle'),
        index: 3,
        callback: function () { this.setMode('rectangle'); }
      },
      ellipse: {
        icon: 'activate',
        title: _('Ellipse'),
        index: 4,
        callback: function () { this.setMode('ellipse'); }
      },
      line: {
        icon: 'activate',
        title: _('Line'),
        keybind: 'alt+l',
        index: 5,
        callback: function () { this.setMode('line'); }
      },
      pencil: {
        icon: 'activate',
        title: _('Pencil'),
        keybind: 'alt+p',
        index: 6,
        callback: function () { this.setMode('pencil'); }
      },
      eraser: {
        icon: 'activate',
        title: _('Eraser'),
        keybind: 'alt+e',
        index: 8,
        callback: function () { this.setMode('eraser'); }
      },
      bucket: {
        icon: 'activate',
        title: _('Bucket'),
        keybind: 'alt+b',
        index: 9,
        callback: function () { this.setMode('bucket'); }
      },
      fillStyle: {
        title: _('Fill Color'),
        icon: 'colorPicker',
        callback: function (color) { this.setFillStyle(color); }
      },
      lineWidth: {
        icon: 'select',
        title: _('Stroke Width'),
        range: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        value: 2,
        callback: function (width) { this.setLineWidth(width); }
      },
      strokeStyle: {
        icon: 'colorPicker',
        title: _('Stroke Color'),
        callback: function (color) { this.setStrokeStyle(color); }
      }      
    }
  };

  // extend cursors
  $.extend($.fn.wPaint.cursors, {
    'default': {path: 'plugins/main/img/cursor-crosshair.png', left: 7, top: 7},
    dropper:   {path: 'plugins/main/img/cursor-dropper.png', left: 0, top: 12},
    pencil:    {path: 'plugins/main/img/cursor-pencil.png', left: 0, top: 11.99},
    bucket:    {path: 'plugins/main/img/cursor-bucket.png', left: 0, top: 10},
    eraser1:   {path: 'plugins/main/img/cursor-eraser1.png', left: 1, top: 1},
    eraser2:   {path: 'plugins/main/img/cursor-eraser2.png', left: 2, top: 2},
    eraser3:   {path: 'plugins/main/img/cursor-eraser3.png', left: 2, top: 2},
    eraser4:   {path: 'plugins/main/img/cursor-eraser4.png', left: 3, top: 3},
    eraser5:   {path: 'plugins/main/img/cursor-eraser5.png', left: 3, top: 3},
    eraser6:   {path: 'plugins/main/img/cursor-eraser6.png', left: 4, top: 4},
    eraser7:   {path: 'plugins/main/img/cursor-eraser7.png', left: 4, top: 4},
    eraser8:   {path: 'plugins/main/img/cursor-eraser8.png', left: 5, top: 5 },
    eraser9:   {path: 'plugins/main/img/cursor-eraser9.png', left: 5, top: 5},
    eraser10:  {path: 'plugins/main/img/cursor-eraser10.png', left: 6, top: 6}
  });

  // extend defaults
  $.extend($.fn.wPaint.defaults, {
    mode:        'pencil',  // set mode
    lineWidth:   '3',       // starting line width
    fillStyle:   '#FFFFFF', // starting fill style
    strokeStyle: '#FFFF00'  // start stroke style
  });

  // extend functions
  $.fn.wPaint.extend({
    undoCurrent: -1,
    undoArray: [],
    setUndoFlag: true,

    generate: function () {
      this.menus.all.main = this._createMenu('main', {
        offsetLeft: this.options.menuOffsetLeft,
        offsetTop: this.options.menuOffsetTop
      });
    },

    _init: function () {
      // must add undo on init to set the first undo as the initial drawing (bg or blank)
      this._addUndo();
      this.menus.all.main._setIconDisabled('clear', true);
    },

    setStrokeStyle: function (color) {
      this.options.strokeStyle = color;
      this.menus.all.main._setColorPickerValue('strokeStyle', color);
    },

    setLineWidth: function (width) {
      this.options.lineWidth = width;
      this.menus.all.main._setSelectValue('lineWidth', width);

      // reset cursor here based on mode in case we need to update cursor (for instance when changing cursor for eraser sizes)
      this.setCursor(this.options.mode);
    },

    setFillStyle: function (color) {
      this.options.fillStyle = color;
      this.menus.all.main._setColorPickerValue('fillStyle', color);
    },

    setCursor: function (cursor) {
      if (cursor === 'eraser') {
        this.setCursor('eraser' + this.options.lineWidth);
      }
    },

    /****************************************
     * undo / redo
     ****************************************/
    undo: function () {
      if (this.undoArray[this.undoCurrent - 1]) {
        this._setUndo(--this.undoCurrent);
      }

      this._undoToggleIcons();
    },

    redo: function () {
      if (this.undoArray[this.undoCurrent + 1]) {
        this._setUndo(++this.undoCurrent);
      }

      this._undoToggleIcons();
    },

    _addUndo: function () {

      //if it's not at the end of the array we need to repalce the current array position
      if (this.undoCurrent < this.undoArray.length - 1) {
        this.undoArray[++this.undoCurrent] = this.getImage(false);
      }
      else { // owtherwise we push normally here
        this.undoArray.push(this.getImage(false));

        //if we're at the end of the array we need to slice off the front - in increment required
        if (this.undoArray.length > this.undoMax) {
          this.undoArray = this.undoArray.slice(1, this.undoArray.length);
        }
        //if we're NOT at the end of the array, we just increment
        else { this.undoCurrent++; }
      }

      //for undo's then a new draw we want to remove everything afterwards - in most cases nothing will happen here
      while (this.undoCurrent !== this.undoArray.length - 1) { this.undoArray.pop(); }

      this._undoToggleIcons();
      this.menus.all.main._setIconDisabled('clear', false);
    },

    _undoToggleIcons: function () {
      var undoIndex = (this.undoCurrent > 0 && this.undoArray.length > 1) ? 0 : 1,
          redoIndex = (this.undoCurrent < this.undoArray.length - 1) ? 2 : 3;

      this.menus.all.main._setIconDisabled('undo', undoIndex === 1 ? true : false);
      this.menus.all.main._setIconDisabled('redo', redoIndex === 3 ? true : false);
    },

    _setUndo: function (undoCurrent) {
      this.setImage(this.undoArray[undoCurrent], null, null, true);
    },

    /****************************************
     * clear
     ****************************************/
    clear: function () {

      // only run if not disabled (make sure we only run one clear at a time)
      if (!this.menus.all.main._isIconDisabled('clear')) {
        this.ctx.clearRect(0, 0, this.width, this.height);
        this._addUndo();
        this.menus.all.main._setIconDisabled('clear', true);
      }
    },

    /****************************************
     * rectangle
     ****************************************/
    _drawRectangleDown: function (e) { this._drawShapeDown(e); },

    _drawRectangleMove: function (e) {
      this._drawShapeMove(e);

      this.ctxTemp.rect(e.x, e.y, e.w, e.h);
      this.ctxTemp.stroke();
      this.ctxTemp.fill();
    },

    _drawRectangleUp: function (e) {
      this._drawShapeUp(e);
      this._addUndo();
    },

    /****************************************
     * ellipse
     ****************************************/
    _drawEllipseDown: function (e) { this._drawShapeDown(e); },

    _drawEllipseMove: function (e) {
      this._drawShapeMove(e);

      this.ctxTemp.ellipse(e.x, e.y, e.w, e.h);
      this.ctxTemp.stroke();
      this.ctxTemp.fill();
    },

    _drawEllipseUp: function (e) {
      this._drawShapeUp(e);
      this._addUndo();
    },

    /****************************************
     * line
     ****************************************/
    _drawLineDown: function (e) { this._drawShapeDown(e); },

    _drawLineMove: function (e) {
      this._drawShapeMove(e, 1);

      var xo = this.canvasTempLeftOriginal;
      var yo = this.canvasTempTopOriginal;
      
      if (e.pageX < xo) { e.x = e.x + e.w; e.w = e.w * - 1; }
      if (e.pageY < yo) { e.y = e.y + e.h; e.h = e.h * - 1; }
      
      this.ctxTemp.lineJoin = 'round';
      this.ctxTemp.beginPath();
      this.ctxTemp.moveTo(e.x, e.y);
      this.ctxTemp.lineTo(e.x + e.w, e.y + e.h);
      this.ctxTemp.closePath();
      this.ctxTemp.stroke();
    },

    _drawLineUp: function (e) {
      this._drawShapeUp(e);
      this._addUndo();
    },

    /****************************************
     * pencil
     ****************************************/
    _drawPencilDown: function (e) {
      this.ctx.lineJoin = 'round';
      this.ctx.lineCap = 'round';
      this.ctx.strokeStyle = this.options.strokeStyle;
      this.ctx.fillStyle = this.options.strokeStyle;
      this.ctx.lineWidth = this.options.lineWidth;
      
      //draw single dot in case of a click without a move
      this.ctx.beginPath();
      this.ctx.arc(e.pageX, e.pageY, this.options.lineWidth / 2, 0, Math.PI * 2, true);
      this.ctx.closePath();
      this.ctx.fill();
      
      //start the path for a drag
      this.ctx.beginPath();
      this.ctx.moveTo(e.pageX, e.pageY);
    },
    
    _drawPencilMove: function (e) {
      this.ctx.lineTo(e.pageX, e.pageY);
      this.ctx.stroke();
    },
    
    _drawPencilUp: function () {
      this.ctx.closePath();
      this._addUndo();
    },

    /****************************************
     * eraser
     ****************************************/
    _drawEraserDown: function (e) {
      this.ctx.save();
      this.ctx.globalCompositeOperation = 'destination-out';
      this._drawPencilDown(e);
    },
    
    _drawEraserMove: function (e) {
      this._drawPencilMove(e);
    },
    
    _drawEraserUp: function (e) {
      this._drawPencilUp(e);
      this.ctx.restore();
    },

    /****************************************
     * bucket
     ****************************************/
    _drawBucketDown: function (e) {
      this.ctx.fillArea(e.pageX, e.pageY, this.options.fillStyle);
      this._addUndo();
    }
  });
})(jQuery);

!function(){window.CanvasRenderingContext2D&&(CanvasRenderingContext2D.prototype.fillArea=function(a,b,c){function d(a){return{r:p[a],g:p[a+1],b:p[a+2],a:p[a+3]}}function e(a){p[a]=c.r,p[a+1]=c.g,p[a+2]=c.b,p[a+3]=c.a}function f(a){return g.r===a.r&&g.g===a.g&&g.b===a.b&&g.a===a.a}if(!a||!b||!c)return!0;var g,h,i,j,k,l,m=this.canvas.width,n=this.canvas.height,o=this.getImageData(0,0,m,n),p=o.data,q=[[a,b]];if(g=d(4*(b*m+a)),l=this.canvas.style.color,this.canvas.style.color=c,c=this.canvas.style.color.match(/^rgba?\((.*)\);?$/)[1].split(","),this.canvas.style.color=l,c={r:parseInt(c[0],10),g:parseInt(c[1],10),b:parseInt(c[2],10),a:parseInt(c[3]||255,10)},f(c))return!0;for(;q.length;){for(h=q.pop(),i=4*(h[1]*m+h[0]);h[1]-->=0&&f(d(i));)i-=4*m;for(i+=4*m,++h[1],j=!1,k=!1;h[1]++<n-1&&f(d(i));)e(i),h[0]>0&&(f(d(i-4))?j||(q.push([h[0]-1,h[1]]),j=!0):j&&(j=!1)),h[0]<m-1&&(f(d(i+4))?k||(q.push([h[0]+1,h[1]]),k=!0):k&&(k=!1)),i+=4*m}this.putImageData(o,0,0)})}();
(function ($) {

  // setup menu
  $.fn.wPaint.menus.text = {
    img: 'plugins/text/img/icons-menu-text.png',
    items: {
      bold: {
        icon: 'toggle',
        title: _('Bold'),
        index: 0,
        callback: function (toggle) { this.setFontBold(toggle); }
      },
      italic: {
        icon: 'toggle',
        title: _('Italic'),
        index: 1,
        callback: function (toggle) { this.setFontItalic(toggle); }
      },
      /*underline: {
        icon: 'toggle',
        title: 'Undelrine',
        index: 2,
        callback: function (toggle) { this.setFontUnderline(toggle); }
      },*/
      fontSize: {
        title: _('Font Size'),
        icon: 'select',
        range: [8, 9, 10, 12, 14, 16, 20, 24, 30],
        value: 12,
        callback: function (size) { this.setFontSize(size); }
      },
      fontFamily: {
        icon: 'select',
        title: _('Font Family'),
        range: ['Arial', 'Courier', 'Times', 'Verdana'],
        useRange: true,
        value: 'Arial',
        callback: function (family) { this.setFontFamily(family); }
      }
    }
  };

  // add icon to main menu
  $.fn.wPaint.menus.main.items.text = {
    icon: 'menu',
    after: 'pencil',
    title: 'Text',
    index: 7,
    callback: function () { this.setMode('text'); }
  };

  // extend defaults
  $.extend($.fn.wPaint.defaults, {
    fontSize       : '12',    // current font size for text input
    fontFamily     : 'Arial', // active font family for text input
    fontBold       : false,   // text input bold enable/disable
    fontItalic     : false,   // text input italic enable/disable
    fontUnderline  : false    // text input italic enable/disable
  });

  // extend functions
  $.fn.wPaint.extend({
    generate: function () {
      this.$textCalc = $('<div></div>').hide();

      // make sure clicking on the text-tnput doesn't trigger another textInput
      this.$textInput = $('<textarea class="wPaint-text-input" spellcheck="false"></textarea>')
      .on('mousedown', this._stopPropagation) 
      .css({position: 'absolute'})
      .hide();
      
      $('body').append(this.$textCalc);
      this.$el.append(this.$textInput);

      this.menus.all.text = this._createMenu('text');
    },

    _init: function () {
      var _this = this;

      function inputClick() {
        _this._drawTextIfNotEmpty();
        _this.$textInput.hide();
        _this.$canvasTemp.hide();
      }

      // in case we click on another element while typing - just auto set the text
      for (var i in this.menus.all) {
        this.menus.all[i].$menu
        .on('click', inputClick)
        .on('mousedown', this._stopPropagation);
      }

      // same idea here for clicking outside of the canvas area
      $(document).on('mousedown', inputClick);
    },

    /****************************************
     * setters
     ****************************************/
    setFillStyle: function (fillStyle) {
      this.$textInput.css('color', fillStyle);
    },

    setFontSize: function (size) {
      this.options.fontSize = parseInt(size, 10);
      this._setFont({fontSize: size + 'px', lineHeight: size + 'px'});
      this.menus.all.text._setSelectValue('fontSize', size);
    },

    setFontFamily: function (family) {
      this.options.fontFamily = family;
      this._setFont({fontFamily: family});
      this.menus.all.text._setSelectValue('fontFamily', family);
    },

    setFontBold: function (bold) {
      this.options.fontBold = bold;
      this._setFont({fontWeight: (bold ? 'bold' : '')});
    },

    setFontItalic: function (italic) {
      this.options.fontItalic = italic;
      this._setFont({fontStyle: (italic ? 'italic' : '')});
    },

    setFontUnderline: function (underline) {
      this.options.fontUnderline = underline;
      this._setFont({fontWeight: (underline ? 'underline' : '')});
    },

    _setFont: function (css) {
      this.$textInput.css(css);
      this.$textCalc.css(css);
    },

    /****************************************
     * Text
     ****************************************/
    _drawTextDown: function (e) {
      this._drawTextIfNotEmpty();
      this._drawShapeDown(e, 1);

      this.$textInput
      .css({left: e.pageX - 1, top: e.pageY - 1, width: 0, height: 0})
      .show().focus();
    },
    
    _drawTextMove: function (e) {
      this._drawShapeMove(e, 1);

      this.$textInput.css({left: e.left - 1, top: e.top - 1, width: e.width, height: e.height});
    },

    _drawTextIfNotEmpty: function () {
      if (this.$textInput.val() !== '') { this._drawText(); }
    },

    // just draw text - don't want to trigger up here since we are just copying text from input box here
    _drawText: function () {
      var fontString = '',
          lines = this.$textInput.val().split('\n'),
          linesNew = [],
          textInputWidth = this.$textInput.width() - 2,
          width = 0,
          lastj = 0,
          offset = this.$textInput.position(),
          left = offset.left + 1,
          top = offset.top + 1,
          //underlineOffset = 0,
          i, ii, j, jj;

      if (this.options.fontItalic) { fontString += 'italic '; }
      //if(this.settings.fontUnderline) { fontString += 'underline '; }
      if (this.options.fontBold) { fontString += 'bold '; }
      
      fontString += this.options.fontSize + 'px ' + this.options.fontFamily;
      
      for (i = 0, ii = lines.length; i < ii; i++) {
        this.$textCalc.html('');
        lastj = 0;
        
        for (j = 0, jj = lines[0].length; j < jj; j++) {
          width = this.$textCalc.append(lines[i][j]).width();
          
          if (width > textInputWidth) {
            linesNew.push(lines[i].substring(lastj, j));
            lastj = j;
            this.$textCalc.html(lines[i][j]);
          }
        }
        
        if (lastj !== j) { linesNew.push(lines[i].substring(lastj, j)); }
      }
      
      lines = this.$textInput.val(linesNew.join('\n')).val().split('\n');

      for (i = 0, ii = lines.length; i < ii; i++) {
        this.ctx.fillStyle = this.options.fillStyle;
        this.ctx.textBaseline = 'top';
        this.ctx.font = fontString;
        this.ctx.fillText(lines[i], left, top);
        
        top += this.options.fontSize;
        
        /*if(lines[i] !== '' && this.options.fontTypeUnderline) {
          width = this.$textCalc.html(lines[i]).width();
          
          //manually set pixels for underline since to avoid antialiasing 1px issue, and lack of support for underline in canvas
          var imgData = this.ctx.getImageData(0, top+underlineOffset, width, 1);
          
          for (j=0; j<imgData.width*imgData.height*4; j+=4) {
            imgData.data[j] = parseInt(this.options.fillStyle.substring(1,3), 16);
            imgData.data[j+1] = parseInt(this.options.fillStyle.substring(3,5), 16);
            imgData.data[j+2] = parseInt(this.options.fillStyle.substring(5,7), 16);
            imgData.data[j+3] = 255;
          }
          
          this.ctx.putImageData(imgData, left, top+underlineOffset);
        }*/
      }

      this.$textInput.val('');
      this._addUndo();
    }
  });
})(jQuery);

(function ($) {
  var img = 'plugins/shapes/img/icons-menu-main-shapes.png';

  // extend menu
  $.extend(true, $.fn.wPaint.menus.main.items, {
    rectangle: {
      group: 'shapes'
    },
    roundedRect: {
      icon: 'activate',
      group: 'shapes',
      title: _('Rounded Rectangle'),
      img: img,
      index: 0,
      callback: function () { this.setMode('roundedRect'); }
    },
    square: {
      icon: 'activate',
      group: 'shapes',
      title: _('Square'),
      img: img,
      index: 1,
      callback: function () { this.setMode('square'); }
    },
    roundedSquare: {
      icon: 'activate',
      group: 'shapes',
      title: _('Rounded Square'),
      img: img,
      index: 2,
      callback: function () { this.setMode('roundedSquare'); }
    },
    diamond: {
      icon: 'activate',
      group: 'shapes',
      title: _('Diamond'),
      img: img,
      index: 4,
      callback: function () { this.setMode('diamond'); }
    },

    ellipse: {
      group: 'shapes2'
    },
    circle: {
      icon: 'activate',
      group: 'shapes2',
      title: _('Circle'),
      img: img,
      index: 3,
      callback: function () { this.setMode('circle'); }
    },
    pentagon: {
      icon: 'activate',
      group: 'shapes2',
      title: _('Pentagon'),
      img: img,
      index: 5,
      callback: function () { this.setMode('pentagon'); }
    },
    hexagon: {
      icon: 'activate',
      group: 'shapes2',
      title: _('Hexagon'),
      img: img,
      index: 6,
      callback: function () { this.setMode('hexagon'); }
    }
  });

  // extend functions
  $.fn.wPaint.extend({
    /****************************************
     * roundedRect
     ****************************************/
    _drawRoundedRectDown: function (e) { this._drawShapeDown(e); },

    _drawRoundedRectMove: function (e) {
      this._drawShapeMove(e);

      var radius = e.w > e.h ? e.h / e.w : e.w / e.h;

      this.ctxTemp.roundedRect(e.x, e.y, e.w, e.h, Math.ceil(radius * (e.w * e.h * 0.001)));
      this.ctxTemp.stroke();
      this.ctxTemp.fill();
    },

    _drawRoundedRectUp: function (e) {
      this._drawShapeUp(e);
      this._addUndo();
    },

    /****************************************
     * square
     ****************************************/
    _drawSquareDown: function (e) { this._drawShapeDown(e); },

    _drawSquareMove: function (e) {
      this._drawShapeMove(e);

      var l = e.w > e.h ? e.h : e.w;

      this.ctxTemp.rect(e.x, e.y, l, l);
      this.ctxTemp.stroke();
      this.ctxTemp.fill();
    },

    _drawSquareUp: function (e) {
      this._drawShapeUp(e);
      this._addUndo();
    },

    /****************************************
     * roundedSquare
     ****************************************/
    _drawRoundedSquareDown: function (e) { this._drawShapeDown(e); },

    _drawRoundedSquareMove: function (e) {
      this._drawShapeMove(e);

      var l = e.w > e.h ? e.h : e.w;

      this.ctxTemp.roundedRect(e.x, e.y, l, l, Math.ceil(l * l * 0.001));
      this.ctxTemp.stroke();
      this.ctxTemp.fill();
    },

    _drawRoundedSquareUp: function (e) {
      this._drawShapeUp(e);
      this._addUndo();
    },

    /****************************************
     * diamond
     ****************************************/
    _drawDiamondDown: function (e) { this._drawShapeDown(e); },

    _drawDiamondMove: function (e) {
      this._drawShapeMove(e);

      this.ctxTemp.diamond(e.x, e.y, e.w, e.h);
      this.ctxTemp.stroke();
      this.ctxTemp.fill();
    },

    _drawDiamondUp: function (e) {
      this._drawShapeUp(e);
      this._addUndo();
    },

    /****************************************
     * circle
     ****************************************/
    _drawCircleDown: function (e) { this._drawShapeDown(e); },

    _drawCircleMove: function (e) {
      this._drawShapeMove(e);

      var l = e.w > e.h ? e.h : e.w;

      this.ctxTemp.ellipse(e.x, e.y, l, l);
      this.ctxTemp.stroke();
      this.ctxTemp.fill();
    },

    _drawCircleUp: function (e) {
      this._drawShapeUp(e);
      this._addUndo();
    },

    /****************************************
     * pentagon
     ****************************************/
    _drawPentagonDown: function (e) { this._drawShapeDown(e); },

    _drawPentagonMove: function (e) {
      this._drawShapeMove(e);

      this.ctxTemp.pentagon(e.x, e.y, e.w, e.h);
      this.ctxTemp.stroke();
      this.ctxTemp.fill();
    },

    _drawPentagonUp: function (e) {
      this._drawShapeUp(e);
      this._addUndo();
    },

    /****************************************
     * hexagon
     ****************************************/
    _drawHexagonDown: function (e) { this._drawShapeDown(e); },

    _drawHexagonMove: function (e) {
      this._drawShapeMove(e);

      this.ctxTemp.hexagon(e.x, e.y, e.w, e.h);
      this.ctxTemp.stroke();
      this.ctxTemp.fill();
    },

    _drawHexagonUp: function (e) {
      this._drawShapeUp(e);
      this._addUndo();
    }
  });
})(jQuery);

!function(){window.CanvasRenderingContext2D&&(CanvasRenderingContext2D.prototype.diamond=function(a,b,c,d){return a&&b&&c&&d?(this.beginPath(),this.moveTo(a+.5*c,b),this.lineTo(a,b+.5*d),this.lineTo(a+.5*c,b+d),this.lineTo(a+c,b+.5*d),this.lineTo(a+.5*c,b),this.closePath(),void 0):!0}),window.CanvasRenderingContext2D&&(CanvasRenderingContext2D.prototype.ellipse=function(a,b,c,d){if(!(a&&b&&c&&d))return!0;var e=.5522848,f=c/2*e,g=d/2*e,h=a+c,i=b+d,j=a+c/2,k=b+d/2;this.beginPath(),this.moveTo(a,k),this.bezierCurveTo(a,k-g,j-f,b,j,b),this.bezierCurveTo(j+f,b,h,k-g,h,k),this.bezierCurveTo(h,k+g,j+f,i,j,i),this.bezierCurveTo(j-f,i,a,k+g,a,k),this.closePath()}),window.CanvasRenderingContext2D&&(CanvasRenderingContext2D.prototype.hexagon=function(a,b,c,d){if(!(a&&b&&c&&d))return!0;var e=.225,f=1-e;this.beginPath(),this.moveTo(a+.5*c,b),this.lineTo(a,b+d*e),this.lineTo(a,b+d*f),this.lineTo(a+.5*c,b+d),this.lineTo(a+c,b+d*f),this.lineTo(a+c,b+d*e),this.lineTo(a+.5*c,b),this.closePath()}),window.CanvasRenderingContext2D&&(CanvasRenderingContext2D.prototype.pentagon=function(a,b,c,d){return a&&b&&c&&d?(this.beginPath(),this.moveTo(a+c/2,b),this.lineTo(a,b+.4*d),this.lineTo(a+.2*c,b+d),this.lineTo(a+.8*c,b+d),this.lineTo(a+c,b+.4*d),this.lineTo(a+c/2,b),this.closePath(),void 0):!0}),window.CanvasRenderingContext2D&&(CanvasRenderingContext2D.prototype.roundedRect=function(a,b,c,d,e){return a&&b&&c&&d?(e||(e=5),this.beginPath(),this.moveTo(a+e,b),this.lineTo(a+c-e,b),this.quadraticCurveTo(a+c,b,a+c,b+e),this.lineTo(a+c,b+d-e),this.quadraticCurveTo(a+c,b+d,a+c-e,b+d),this.lineTo(a+e,b+d),this.quadraticCurveTo(a,b+d,a,b+d-e),this.lineTo(a,b+e),this.quadraticCurveTo(a,b,a+e,b),this.closePath(),void 0):!0})}();
(function ($) {
  var img = 'plugins/file/img/icons-menu-main-file.png';

  // extend menu
  $.extend(true, $.fn.wPaint.menus.main.items, {
    save: {
      icon: 'generic',
      title: _('Save Image'),
      img: img,
      index: 0,
      callback: function () {
        this.options.saveImg.apply(this, [this.getImage()]);
      }
    },
    loadBg: {
      icon: 'generic',
      group: 'loadImg',
      title: _('Load Image to Foreground'),
      img: img,
      index: 2,
      callback: function () {
        this.options.loadImgFg.apply(this, []);
      }
    },
    loadFg: {
      icon: 'generic',
      group: 'loadImg',
      title: _('Load Image to Background'),
      img: img,
      index: 1,
      callback: function () {
        this.options.loadImgBg.apply(this, []);
      }
    }  
  });

  // extend defaults
  $.extend($.fn.wPaint.defaults, {
    saveImg: null,   // callback triggerd on image save
    loadImgFg: null, // callback triggered on image fg
    loadImgBg: null  // callback triggerd on image bg
  });

  // extend functions
  $.fn.wPaint.extend({
    _showFileModal: function (type, images) {
      var _this = this,
          $content = $('<div></div>'),
          $img = null;

      function appendContent(type, image) {
        function imgClick(e) {

          // just in case to not draw on canvas
          e.stopPropagation();
          if (type === 'fg') { _this.setImage(image); }
          else if (type === 'bg') { _this.setBg(image, null, null, true); }
        }

        $img.on('click', imgClick);
      }

      for (var i = 0, ii = images.length; i < ii; i++) {
        $img = $('<img class="wPaint-modal-img"/>').attr('src', images[i]);
        $img = $('<div class="wPaint-modal-img-holder"></div>').append($img);

        (appendContent)(type, images[i]);

        $content.append($img);
      }

      this._showModal($content);
    }
  });
})(jQuery);
