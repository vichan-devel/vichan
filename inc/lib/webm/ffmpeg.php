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
  exec(sprintf ($config['webm']['ffprobe_exec'],$ffprobe,$filename), $ffprobe_out);
  $ffprobe_out = json_decode(implode("\n", $ffprobe_out), 1);
  $webminfo['error'] = is_valid_webm($ffprobe_out);

  if(empty($webminfo['error'])) {
    $webminfo['width'] = $ffprobe_out['streams'][0]['width'];
    $webminfo['height'] = $ffprobe_out['streams'][0]['height'];
  }

  return $webminfo;
}

function is_valid_webm($ffprobe_out) {
  global $board, $config;

  if (empty($ffprobe_out))
    return array('code' => 1, 'msg' => $config['error']['genwebmerror']);

  if ($ffprobe_out['format']['format_name'] != 'matroska,webm')
    return array('code' => 2, 'msg' => $config['error']['invalidwebm']);

  if ((count($ffprobe_out['streams']) > 1) && (!$config['webm']['allow_audio']))
    return array('code' => 3, 'msg' => $config['error']['webmhasaudio']);

  if ($ffprobe_out['streams'][0]['codec_name'] != 'vp8')
    return array('code' => 2, 'msg' => $config['error']['invalidwebm']);

  if (empty($ffprobe_out['streams'][0]['width']) || (empty($ffprobe_out['streams'][0]['height'])))
    return array('code' => 2, 'msg' => $config['error']['invalidwebm']);

  if ($ffprobe_out['format']['duration'] > $config['webm']['max_length'])
    return array('code' => 4, 'msg' => $config['error']['webmtoolong']);
}

function make_webm_thumbnail($filename, $thumbnail, $width, $height) {
  global $board, $config;

  $filename = escapeshellarg($filename);
  $thumbnail = escapeshellarg($thumbnail); // Should be safe by default but you
                                           // can never be too safe.

  $ffmpeg = $config['webm']['ffmpeg_path'];
  $ffmpeg_out = array();

  exec(sprintf ($config['webm']['ffmpeg_exec'],$ffmpeg,$filename,$width,$height,$thumbnail));

  return count($ffmpeg_out);
}
