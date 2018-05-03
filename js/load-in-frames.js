

/**
 * 
 * To use this remember to install the js_frameset theme
 * Then add 
 *      $config['additional_javascript'][] = 'js/load-in-frames.js';
 * to the board you want frames on
**/


$(document).ready(function () {
    if (window != window.top) { /* I'm in a frame! */ }
    else {
        // If we are not in an iframe we should load frame html and load current page in the frame
        var fs = document.createElement('frameset'),
            f1 = document.createElement('frame'),
            f2 = document.createElement('frame');
        fs.cols = "200px,*";
        fs.setAttribute('frameborder', "1");
        fs.setAttribute('border', "2");
        fs.setAttribute('bordercolor', "#777");

        f1.name = "navframe";
        f1.src = "/sidebar.html";
        f1.scrolling = "auto";
        f1.setAttribute('noresize', 'noresize');
        f1.setAttribute('marginwidth', '0');
        f1.setAttribute('marginheight', '0');

        f2.name = "pageframe";
        f2.src = window.location.href;
        f2.scrolling = "auto";
        f2.setAttribute('frameborder', '0');
        f2.setAttribute('marginwidth', '0');
        f2.setAttribute('marginheight', '0');
        
        fs.appendChild(f1);
        fs.appendChild(f2);
        $("body").replaceWith(fs);
    }
});

