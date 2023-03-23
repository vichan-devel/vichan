This directory contains files being the integration of containerchan with vichan-devel imageboards.

Containerchan allows posting of webm files, like they were the regular images.

An original board using this code can be found at:
http://containerchan.org/tb/demo/

The original repo containing the containerchan (possibly with no Tinyboard integration) can be found here:
https://github.com/ccd0/containerchan


Be aware that this is beta software.  Please report any bugs you find.

Installation
------------

Add these lines to inc/secrets.php:

    $config['allowed_ext_files'][] = 'webm';
    $config['additional_javascript'][] = 'js/webm-settings.js';
    $config['additional_javascript'][] = 'js/expand-video.js';

If you have an [FFmpeg](https://www.ffmpeg.org/) binary on your server and you wish to generate real thumbnails (the webm thumbnails created with the original implementation reportedly cause users' browsers to crash), add the following to inc/secrets.php as well:

    $config['webm']['use_ffmpeg'] = true;

    // If your ffmpeg binary isn't in your path you need to set these options
    // as well.

    $config['webm']['ffmpeg_path'] = '/path/to/ffmeg';
    $config['webm']['ffprobe_path'] = '/path/to/ffprobe';

MP4 support
-----------

MP4 support is available only if you use FFmpeg thumbnailing (see above).

    $config['allowed_ext_files'][] = 'mp4';

License
-------

See [LICENSE.md](https://github.com/ccd0/containerchan/blob/master/LICENSE.md).
