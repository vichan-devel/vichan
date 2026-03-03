<?php
/*
 * ffmpeg.php
 * A barebones ffmpeg based webm implementation for vichan.
 */

function get_webm_info($filename) {
	global $config;

	$filename = escapeshellarg($filename);
	$ffprobe = $config['webm']['ffprobe_path'];
	$ffprobe_out = [];
	$webminfo = [];

	exec("$ffprobe -v quiet -print_format json -show_format -show_streams $filename", $ffprobe_out);
	$ffprobe_out = json_decode(implode("\n", $ffprobe_out), 1);
	$validcheck = is_valid_webm($ffprobe_out);
	$webminfo['error'] = $validcheck['error'];
	if (empty($webminfo['error'])) {
		$video_idx = $validcheck['video_idx'];
		$webminfo['width'] = $ffprobe_out['streams'][$video_idx]['width'];
		$webminfo['height'] = $ffprobe_out['streams'][$video_idx]['height'];
		$webminfo['duration'] = $ffprobe_out['format']['duration'];
	}
	return $webminfo;
}

function locate_webm_tracks($ffprobe_out) {
	$streams = $ffprobe_out['streams'];
	$streamcount = count($streams);
	$video_at = [];
	$audio_at = [];

	for ($k = 0; $k < $streamcount; $k++) {
		$stream = $streams[$k];
		$codec_type = $stream['codec_type'];

		if ($codec_type === 'video') {
			$video_at[] = $k;
		} elseif ($codec_type === 'audio') {
			$audio_at[] = $k;
		}
	}

	return [ 'videoat' => $video_at, 'audioat' => $audio_at ];
}

/**
 * @param string $ffprobe_out
 * @return array<array> An array with the following values:
 *                      error: array with error code and message
 *                      video_idx: int, index of the video track
 *                      audio_idx: ?int, index of the audio track, if present
 */
function is_valid_webm(array $ffprobe_out) {
	global $config;

	if (empty($ffprobe_out)) {
		return [ 'error' => [ 'code' => 1, 'msg' => $config['error']['genwebmerror'] ] ];
	}
	if ($ffprobe_out['format']['duration'] > $config['webm']['max_length']) {
		return [
			'error' => [
				'code' => 4,
				'msg' => sprintf($config['error']['webmtoolong'], $config['webm']['max_length'])
			]
		];
	}
	if ((count($ffprobe_out['streams']) > 1) && (!$config['webm']['allow_audio'])) {
		return [
			'error' => [
				'code' => 3,
				'msg' => $config['error']['webmhasaudio']
			]
		];
	}

	$trackmap = locate_webm_tracks($ffprobe_out);

	if (count($trackmap['videoat']) < 1) {
		return [
			'error' => [
				'code' => 2,
				'msg' => $config['error']['invalidwebm'] . ' [video track count]'
			]
		];
	}
	if (count($trackmap['audioat']) != 0 && !$config['webm']['allow_audio']) {
		return [
			'error' => [
				'code' => 3,
				'msg' => $config['error']['webmhasaudio']
			]
		];
	}

	$audio_idx = count($trackmap['audioat']) > 0 ? 0 : null;
	$video_idx = 0;

	$extension = \pathinfo($ffprobe_out['format']['filename'], \PATHINFO_EXTENSION);

	if ($extension === 'webm' && !stristr($ffprobe_out['format']['format_name'], 'mp4')) {
		if ($ffprobe_out['format']['format_name'] != 'matroska,webm') {
			return [
				'error' => [
					'code' => 2,
					'msg' => $config['error']['invalidwebm']
				]
			];
		}
	} elseif ($extension === 'mp4' || stristr($ffprobe_out['format']['format_name'], 'mp4')) {
		$any_h26x = false;
		foreach ($trackmap['videoat'] as $track_idx) {
			$video_codec = $ffprobe_out['streams'][$track_idx]['codec_name'];
			if ($video_codec === 'h264' || $video_codec === 'h265') {
				$video_idx = $track_idx;
				$any_h26x = true;
				break;
			}
		}

		$any_aac = false;
		if ($audio_idx !== null) {
			foreach ($trackmap['audioat'] as $track_idx) {
				$audio_codec = $ffprobe_out['streams'][$track_idx]['codec_name'];
				if ($audio_codec === 'aac') {
					$audio_idx = $track_idx;
					$any_aac = true;
					break;
				}
			}
		}

		// If the video is not h264, h265 or there is audio but it's not aac.
		if (!$any_h26x || ($audio_idx !== null && !$any_aac)) {
			return [
				'error' => [
					'code' => 2,
					'msg' => $config['error']['invalidwebm'] . ' [h264/h265/aac check]'
				]
			];
		}
	} else {
		return [
			'error' => [
				'code' => 1,
				'msg' => $config['error']['genwebmerror']
			]
		];
	}

	return [
		'error' => [],
		'video_idx' => $video_idx,
		'audio_idx' => $audio_idx
	];
}

function make_webm_thumbnail($filename, $thumbnail, $width, $height, $duration) {
	global $config;

	$filename = escapeshellarg($filename);
	// Should be safe by default but you can never be too safe.
	$thumbnailfc = escapeshellarg($thumbnail);

	// Same as above.
	$width = escapeshellarg($width);
	$height = escapeshellarg($height);
	$ffmpeg = $config['webm']['ffmpeg_path'];
	$ret = 0;
	$ffmpeg_out = [];

	exec("$ffmpeg -strict -2 -ss " . floor($duration / 2) . " -i $filename -v quiet -an -vframes 1 -f mjpeg -vf scale=$width:$height $thumbnailfc 2>&1", $ffmpeg_out, $ret);

	// Work around for https://trac.ffmpeg.org/ticket/4362.
	if (filesize($thumbnail) === 0) {
		// Try again with first frame.
		exec("$ffmpeg -y -strict -2 -ss 0 -i $filename -v quiet -an -vframes 1 -f mjpeg -vf scale=$width:$height $thumbnailfc 2>&1", $ffmpeg_out, $ret);
		clearstatcache();
		// Failed if no thumbnail size even if ret code 0, ffmpeg is buggy.
		if (filesize($thumbnail) === 0) {
			$ret = 1;
		}
	}
	return $ret;
}
