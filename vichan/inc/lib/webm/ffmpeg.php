<?php
/*
* ffmpeg.php
* A barebones ffmpeg based webm implementation for vichan.
*/
function get_webm_info($filename) {
  global $board, $config;
  $filename = escapeshellarg($filename);
  $ffprobe = $config['webm']['ffprobe_path'];
  $ffprobe_out = array();
  $webminfo = array();
  exec("$ffprobe -v quiet -print_format json -show_format -show_streams $filename", $ffprobe_out);
  $ffprobe_out = json_decode(implode("\n", $ffprobe_out), 1);
  $webminfo['error'] = is_valid_webm($ffprobe_out);
  if(empty($webminfo['error'])) {
    $webminfo['width'] = $ffprobe_out['streams'][0]['width'];
    $webminfo['height'] = $ffprobe_out['streams'][0]['height'];
    $webminfo['duration'] = $ffprobe_out['format']['duration'];
  }
  return $webminfo;
}
function is_valid_webm($ffprobe_out) {
  global $board, $config;
  if (empty($ffprobe_out))
    return array('code' => 1, 'msg' => $config['error']['genwebmerror']);
  $extension = pathinfo($ffprobe_out['format']['filename'], PATHINFO_EXTENSION);
  if ($extension === 'webm') {
    if ($ffprobe_out['format']['format_name'] != 'matroska,webm')
      return array('code' => 2, 'msg' => $config['error']['invalidwebm']);
  } elseif ($extension === 'mp4') {
    if ($ffprobe_out['streams'][0]['codec_name'] != 'h264' && $ffprobe_out['streams'][1]['codec_name'] != 'aac')
      return array('code' => 2, 'msg' => $config['error']['invalidwebm']);
  } else {
    return array('code' => 1, 'msg' => $config['error']['genwebmerror']);  
  }
  if ((count($ffprobe_out['streams']) > 1) && (!$config['webm']['allow_audio']))
    return array('code' => 3, 'msg' => $config['error']['webmhasaudio']);
  if (empty($ffprobe_out['streams'][0]['width']) || (empty($ffprobe_out['streams'][0]['height'])))
    return array('code' => 2, 'msg' => $config['error']['invalidwebm']);
  if ($ffprobe_out['format']['duration'] > $config['webm']['max_length'])
    return array('code' => 4, 'msg' => sprintf($config['error']['webmtoolong'], $config['webm']['max_length']));
}
function make_webm_thumbnail($filename, $thumbnail, $width, $height, $duration) {
  global $board, $config;
  $filename = escapeshellarg($filename);
  $thumbnailfc = escapeshellarg($thumbnail); // Should be safe by default but you
                                           // can never be too safe.
  $width = escapeshellarg($width);
  $height = escapeshellarg($height); // Same as above.
  $ffmpeg = $config['webm']['ffmpeg_path'];
  $ret = 0;
  $ffmpeg_out = array();
  exec("$ffmpeg -strict -2 -ss " . floor($duration / 2) . " -i $filename -v quiet -an -vframes 1 -f mjpeg -vf scale=$width:$height $thumbnailfc 2>&1", $ffmpeg_out, $ret);
  // Work around for https://trac.ffmpeg.org/ticket/4362
  if (filesize($thumbnail) === 0) {
    // try again with first frame
    exec("$ffmpeg -y -strict -2 -ss 0 -i $filename -v quiet -an -vframes 1 -f mjpeg -vf scale=$width:$height $thumbnailfc 2>&1", $ffmpeg_out, $ret);
    clearstatcache();
    // failed if no thumbnail size even if ret code 0, ffmpeg is buggy
    if (filesize($thumbnail) === 0) {
      $ret = 1;
    }
  }
  return $ret;
}
