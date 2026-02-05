/* This file is dedicated to the public domain; you may do as you wish with it. */

if (typeof _ === 'undefined') {
  const _ = (a) => { return a; };
}

// Default settings
const defaultSettings = {
    "videoexpand": true,
    "videohover": false,
    "videovolume": 1.0
};

// Non-persistent settings for when localStorage is absent/disabled
const tempSettings = {};

// Scripts obtain settings by calling this function
function setting(name) {
    if (localStorage) {
        if (localStorage[name] === undefined) return defaultSettings[name];
        return JSON.parse(localStorage[name]);
    } else {
        if (tempSettings[name] === undefined) return defaultSettings[name];
        return tempSettings[name];
    }
}

// Settings should be changed with this function
function changeSetting(name, value) {
    if (localStorage) {
        localStorage[name] = JSON.stringify(value);
    } else {
        tempSettings[name] = value;
    }
}

// Create settings menu
const settingsMenu = document.createElement("div");
const prefix = "", suffix = "", style = "";
if (window.Options) {
  const tab = Options.add_tab("webm", "video-camera", _("WebM"));
  $(settingsMenu).appendTo(tab.content);
}
else {
  prefix = '<a class="unimportant" href="javascript:void(0)">'+_('WebM Settings')+'</a>';
  settingsMenu.style.textAlign = "right";
  settingsMenu.style.background = "inherit";
  suffix = '</div>';
  style = 'display: none; text-align: left; position: absolute; right: 1em; margin-left: -999em; margin-top: -1px; padding-top: 1px; background: inherit;';
}

settingsMenu.innerHTML = prefix
    + '<div style="'+style+'">'
    + '<label><input type="checkbox" name="videoexpand">'+_('Expand videos inline')+'</label><br>'
    + '<label><input type="checkbox" name="videohover">'+_('Play videos on hover')+'</label><br>'
    + '<label><input type="range" name="videovolume" min="0" max="1" step="0.01" style="width: 4em; height: 1ex; vertical-align: middle; margin: 0px;">'+_('Default volume')+'</label><br>'
    + suffix;

function refreshSettings() {
    const settingsItems = settingsMenu.getElementsByTagName("input");
    for (var i = 0; i < settingsItems.length; i++) {
        const control = settingsItems[i];
        if (control.type === "checkbox") {
            control.checked = setting(control.name);
        } else if (control.type === "range") {
            control.value = setting(control.name);
        }
    }
}

function setUpControl(control) {
    if (control.addEventListener) control.addEventListener("change", (e) => {
        if (control.type === "checkbox") {
            changeSetting(control.name, control.checked);
        } else if (control.type === "range") {
            changeSetting(control.name, control.value);
        }
    }, false);
}

refreshSettings();
const settingsItems = settingsMenu.getElementsByTagName("input");
for (var i = 0; i < settingsItems.length; i++) {
    setUpControl(settingsItems[i]);
}

if (settingsMenu.addEventListener && !window.Options) {
    settingsMenu.addEventListener("mouseover", (e) => {
        refreshSettings();
        settingsMenu.getElementsByTagName("a")[0].style.fontWeight = "bold";
        settingsMenu.getElementsByTagName("div")[0].style.display = "block";
    }, false);
    settingsMenu.addEventListener("mouseout", (e) => {
        settingsMenu.getElementsByTagName("a")[0].style.fontWeight = "normal";
        settingsMenu.getElementsByTagName("div")[0].style.display = "none";
    }, false);
}
