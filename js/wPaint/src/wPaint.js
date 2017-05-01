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
