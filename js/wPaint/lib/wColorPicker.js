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
