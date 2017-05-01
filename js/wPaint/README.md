# wPaint.js

A jQuery paint plugin for a simple drawing surface that you can easily pop into your pages, similar to the basic windows paint program.

This project seems to be abandoned by the original developer, [websanova](https://github.com/websanova). 

This fork provides:

* internationalization support
* drop in replacement for wColorPicker
* keybinds for major functions

This fork gave up on:

* spaces instead of tabs 
* crazy pedantic jshint library

This fork should be a drop in replacement for wPaint. Scripts for 2ch.net and 8ch.net are provided. Build by doing:

```
npm install
grunt --force
```

Pictures say a thousand words:

Old, limiting color chooser:
![0](http://a.pomf.se/egxfjd.png)

New dynamic color chooser that uses two HTML5 canvases, saves history and has 4 modes (also showing keybind for clear):
![0](http://a.pomf.se/cvjnph.png)


## Related Plugins

* [wScratchPad](http://wscratchpad.websanova.com) - Plugin simulating scratch card.
* [wColorPicker](http://wcolorpicker.websanova.com) - Color pallette seleciton plugin.


## Support 

If you enjoy this plugin please leave a small contribution on [Gittip](https://gittip.com/websanova).  I work on these plugins completely in my free time and any contribution is greatly appreciated.


## Settings

Settings are available per plugin.  Meaning only when that plugin is included will those settings be available.

### core

```js
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
```

### main

```js
$.extend($.fn.wPaint.defaults, {
  mode:        'pencil',  // set mode
  lineWidth:   '3',       // starting line width
  fillStyle:   '#FFFFFF', // starting fill style
  strokeStyle: '#FFFF00'  // start stroke style
});
```

### text

```js
$.extend($.fn.wPaint.defaults, {
  fontSize       : '12',    // current font size for text input
  fontFamily     : 'Arial', // active font family for text input
  fontBold       : false,   // text input bold enable/disable
  fontItalic     : false,   // text input italic enable/disable
  fontUnderline  : false    // text input italic enable/disable
});
```

### shapes

No settings.

### file

Note that the callbacks for `file` are user generated for the most part as they deal heavily with client/server side code.  You can view the demo code to get a feeling for how they might be setup.

```js
$.extend($.fn.wPaint.defaults, {
  saveImg: null,   // callback triggerd on image save
  loadImgFg: null, // callback triggered on image fg
  loadImgBg: null  // callback triggerd on image bg
});
```


## Examples

To start, you will need to include any dependencies (the paths and versions may differ):
```html
<!-- jQuery -->
<script type="text/javascript" src="./lib/jquery.1.10.2.min.js"></script>
<!-- jQuery UI -->
<script type="text/javascript" src="./lib/jquery.ui.core.1.10.3.min.js"></script>
<script type="text/javascript" src="./lib/jquery.ui.widget.1.10.3.min.js"></script>
<script type="text/javascript" src="./lib/jquery.ui.mouse.1.10.3.min.js"></script>
<script type="text/javascript" src="./lib/jquery.ui.draggable.1.10.3.min.js"></script>
<!-- wColorPicker -->
<link rel="Stylesheet" type="text/css" href="./lib/wColorPicker.min.css" />
<script type="text/javascript" src="./lib/wColorPicker.min.js"></script>
```



Then you need to include the wPaint core files:
```html
<link rel="Stylesheet" type="text/css" href="./wPaint.min.css" />
<script type="text/javascript" src="./wPaint.min.js"></script>
```

From here we will need to include plugin files for whatever menu icons we would like to support.  This can include the plugins provided with the release of this plugin or any additional plugins that you can write on your own.

```html
<script type="text/javascript" src="./plugins/main/wPaint.menu.main.min.js"></script>
<script type="text/javascript" src="./plugins/text/wPaint.menu.text.min.js"></script>
<script type="text/javascript" src="./plugins/shapes/wPaint.menu.main.shapes.min.js"></script>
<script type="text/javascript" src="./plugins/file/wPaint.menu.main.file.min.js"></script>
```


### path

If you are putting wPaint into a path other than root (most likely you will) then you will need to set the `path` option since the image and cursor icon paths are set in the JavaScript and not in CSS.  This means we can not make them relative from the included file like we can in the CSS file but rather relative to the dispalying page.  The default path is just the root folder `/` but a path can be set for wpaint.

```js
$('#wPaint').wPaint({
  path: '/js/lib/wPaint/'
});
```


### save / load

There have been many questions regarding saving / loading images using wPaint.  Loading images CANNOT be done locally or from other domains due to browser restrictions with [cross origin](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Same_origin_policy_for_JavaScript) policies.  There are some potential workarounds for this using [CORS](https://developer.mozilla.org/en-US/docs/HTML/CORS_Enabled_Image?redirectlocale=en-US&redirectslug=CORS_Enabled_Image) but this has not been implemented yet.

As it stands the best approach to this is to first upload the image to your server, save it, then use the url from your server to set images in the future.  You can check the `upload.php` file in the `test` folder with a php example for saving an image.  However note that saving the image on your server will depend on the language/framework being used.


### themes

With this release multiple themeing has been introduced by simply space separating multiple theme keywords.  For example 'standard classic'.  This is to allow us to theme colours and sizes separately and use them interchangeably.

```js
$("#elem").wPaint({
   theme: 'standard classic'
});
```


### image data

Set image on the fly.  This can be a base64 encoded image or simply a path to any image on the same domain.

```js
$('#wPaint').wPaint('image', '<image_data>');
```

Get the image data:

```js
var imageData = $("#wPaint").wPaint("image");
            
$("#canvasImage").attr('src', imageData);
```


### callbacks

There are three callbacks available for each drawing operation of `down`, `move`, and `up`.  Available is the `this` object which refers to the `wPaint` object and gives access to all internal functions.

```js
$("#elem").wPaint({
    onDrawDown: function (e) {
      console.log(this.settings.mode + ": " + e.pageX + ',' + e.pageY);
    },
    onDrawMove: function (e) {
      console.log(this.settings.mode + ": " + e.pageX + ',' + e.pageY);
    },
    onDrawUp: function (e) {
      console.log(this.settings.mode + ": " + e.pageX + ',' + e.pageY);
    }
});
```


### image / bg

Set the image or background with an image or color at any time.

```js
$("#wPaint").wPaint({
  image: './some/path/imagepreload.png',
  bg: '#ff0000'
});
```


### resize

In case you want to resize your canvas there is a `resize` utility function available.  Call this after you change the dimensions of your canvas element.  Check the `test/fullscreen.html` demo for sample code.

```js
$("#wPaint").wPaint('resize');
```


### clear

Clear the canvas manually.

```javascript
$('#wPaint').wPaint('clear');
```


### undo / redo

We can also manually run an `undo` or `redo`.

```javascript
$('#wPaint').wPaint('undo');
$('#wPaint').wPaint('redo');
```


## Extending

With version 2.0 wPaint can now easily be extended by setting all or some of the following properties:

```js
// add menu
$.fn.wPaint.menus.main = {
  img: '/plugins/main/img/icons-menu-main.png',
  items: {
    undo: {
      icon: 'generic',
      title: 'Undo',
      index: 0,
      callback: function () { this.undo(); }
    }
}

// extend cursors
$.extend($.fn.wPaint.cursors, {
  pencil: 'url("/plugins/main/img/cursor-pencil.png") 0 11.99, default',
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
  undo: function () {
    if (this.undoArray[this.undoCurrent - 1]) {
      this._setUndo(--this.undoCurrent);
    }

    this._undoToggleIcons();
  }
});
```


### overriding

When calling the `$.fn.wPaint.extend()` function the values for functions will not override the existing functions but just extend them with duck punching technique.  This means the original funciton will always run followed by your extended function.

This allows us to just string multiple `generate` or `init` functions together and not have to worry about overwriting any code.



### menus

The first menu appended will always automatically become the `primary` menu meaning it is the one displayed on init.  All other menus will become `secondary` menus meaning they are toggled by icons.

We can extend, modify or add items in the menu by updating the object we want.  So for instance if we want to add a new icon to the main menu we could just do:

```js
$.fn.wPaint.menus.main.items.undo = {
  // set properties here
};
```

Likewise we can overwrite or add properties to an existing object.  For instance below we modified the title and added the `after` property to change the position in which the `undo` icon will appear in the menu.

```js
$.fn.wPaint.menus.main.items.undo = {
  title: 'Undo at your own risk',
  after: 'clear'
};
```


### icon properties

Below is just a sample to list all possible icon properties.  Note that the icon name such as `undo` is the `key` name used for CSS styling and internal naming.

```js
undo: {

  // The icon sets the type of icon we want to generate
  // below are the available types that come out of the box.
  //
  //   generic: just runs a callback and nothing else
  //   activate:  runs callback and activates (highlights)
  //   colorPicker: generates a color picker
  //   select: generates a select box (list)
  //   toggle: toggles on/off returns true or false
  //   menu: toggles secondary menu (icon/menu name must match)
  icon: 'generic',
  
  // Set a group for an icon turning it into a stacked
  // group select (list).  All icons with this group name will
  // be appended to that select list.  If not set the icon will
  // just be standalone.
  group: null,

  // Set placement of icon in reference to another icon
  after: 'clear',

  // Title displayed on hover.
  title: 'Undo',
  
  // set an alternate image path to use for this icon
  img: '/som/path.png',

  // Index position in image file starting from 0
  index: 0,
  
  // a range of values to use for a select icon
  range: [1, 2, 3, 4, 5],

  // User range will set the value of the range as the 
  // css property based on the name of the icon.  For instance
  // if the icon is fontFamily that css property will get set.
  useRange: true,

  // The default value to set for this icon.  This of course
  // can be overridden using `set` calls on init.
  value: 3,

  // Callback when icon is clicked.
  callback: function () { this.undo(); }
}
```

If you want to create a new icon type you will need to extend wPaint to include processing for this new icon.  A funciton in the form below should be written:

```js
_createIconType: function (item) {

  // Get your started with a base icon.
  var $icon = this._createIconBase(item);

  // Return the icon with whatever functionality
  // you want to add to it.
  return $icon;
}
```


### icon images

Images for each plugin should be kept in one file and can be either specificed by the `img` value on the top level and can be overriden at the icon level.  Each icon should also specify an index value as to the position of the icon in the image starting from 0.  Icons should alll be the same size and dimensions should be set in the `size` theme.

```js
$.fn.wPaint.menus.main.items.undo = {
  img: '/plugins/main/img/icons-menu-main.png',
  items: {
    undo: {
      icon: 'generic',
      title: 'Undo',
      img: '/some/other/path.png'
      index: 0,
      callback: function () { this.undo(); }
    }
}
```


### cursors

There is now a master `cursors` object used to store cursor references.

$.extend($.fn.wPaint.cursors, {
  pencil: 'url("/plugins/main/img/cursor-pencil.png") 0 11.99, default',
});

We can sepcify the cursor to use by calling `setCursor()` and passing the cursor name to use.  Note that this is a set function and we can set the cursor at any time.

```js
$('#wPaint').wPaint('cursor', 'rocket');
```

Note that when you are setting the position of the cursor never set it to the exact dimension.  For instance if the iamge is `12x12` and you want it's position to be `12` set it to `11.99`.  This is do to some strange bug in Chrome which will not position the curosr if set exactly.


## Thanks

Thanks to everyone who has contribute code in the previous version and has showed interest in the plugin.  Below is some thanks and attribution for code used in this plugin (if I left you out please let me know).

* [Rounded corners and extending Canvas with new shapes](http://js-bits.blogspot.com/2010/07/canvas-rounded-corner-rectangles.html)
* [Nice efficient algorithm for fill tool](http://www.williammalone.com/articles/html5-canvas-javascript-paint-bucket-tool)


## Resources

* [More jQuery plugins by Websanova](http://websanova.com/plugins)
* [Websanova JavaScript Extensions Project](http://websanova.com/extensions)
* [jQuery Plugin Development Boilerplate](http://wboiler.websanova.com)
* [The Ultimate Guide to Writing jQuery Plugins](http://www.websanova.com/blog/jquery/the-ultimate-guide-to-writing-jquery-plugins)


## License

MIT licensed

Copyright (C) 2011-2012 Websanova http://www.websanova.com
