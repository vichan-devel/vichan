<?php
/* This file is dedicated to the public domain; you may do as you wish with it. */
require dirname(__FILE__) . '/matroska.php';

function matroskaSeekElement($name, $pos) {
    return ebmlEncodeElement('Seek',
        ebmlEncodeElement('SeekID', ebmlEncodeElementName($name))
        . ebmlEncodeElement('SeekPosition', pack('N', $pos))
    );
}

// Make video from single WebM keyframe
function muxWebMFrame($videoTrack, $frame) {
    $lenSeekHead = 73;
    $lenCues = 24;

    // Determine version for EBML header
    $version = 2;
    $videoAttr = $videoTrack->get('Video');
    if (isset($videoAttr)) {
        if ($videoAttr->get('StereoMode') !== NULL) $version = 3;
        if ($videoAttr->get('AlphaMode') !== NULL) $version = 3;
    }
    if ($videoTrack->get('CodecDelay') !== NULL) $version = 4;
    if ($videoTrack->get('SeekPreRoll') !== NULL) $version = 4;
    if ($frame->name() == 'BlockGroup' && $frame->get('DiscardPadding') !== NULL) $version = 4;

    // EBML header
    $ebml = ebmlEncodeElement('EBML',
        ebmlEncodeElement('DocType', "webm")
        . ebmlEncodeElement('DocTypeVersion', chr($version))
        . ebmlEncodeElement('DocTypeReadVersion', "\x02")
    );

    // Segment
    $info = ebmlEncodeElement('Info',
        ebmlEncodeElement('Duration', "\x41\x20\x00\x00")
        . ebmlEncodeElement('MuxingApp', 'ccframe')
        . ebmlEncodeElement('WritingApp', 'ccframe')
    );
    $tracks = ebmlEncodeElement('Tracks',
        ebmlEncodeElement('TrackEntry', $videoTrack->content()->readAll())
    );
    $cues = ebmlEncodeElement('Cues',
        ebmlEncodeElement('CuePoint',
            ebmlEncodeElement('CueTime', "\x00")
            . ebmlEncodeElement('CueTrackPositions',
                ebmlEncodeElement('CueTrack', pack('N', $videoTrack->get('TrackNumber')))
                . ebmlEncodeElement('CueClusterPosition', pack('N', $lenSeekHead + strlen($info) + strlen($tracks) + $lenCues))
            )
        )
    );
    if (strlen($cues) != $lenCues) throw new Exception('length of Cues element wrong');
    $cluster = ebmlEncodeElement('Cluster',
        ebmlEncodeElement('Timecode', "\x00")
        . ebmlEncodeElement($frame->name(), $frame->content()->readAll())
        . ebmlEncodeElement('Void', '')
    );
    $seekHead = ebmlEncodeElement('SeekHead',
        matroskaSeekElement('Info', $lenSeekHead)
        . matroskaSeekElement('Tracks', $lenSeekHead + strlen($info))
        . matroskaSeekElement('Cues', $lenSeekHead + strlen($info) + strlen($tracks))
        . matroskaSeekElement('Cluster', $lenSeekHead + strlen($info) + strlen($tracks) + $lenCues)
    );
    if (strlen($seekHead) != $lenSeekHead) throw new Exception('length of SeekHead element wrong');
    $segment = ebmlEncodeElement('Segment', $seekHead . $info . $tracks . $cues . $cluster);

    return $ebml . $segment;
}

// Locate first WebM keyframe of track $trackNumber after timecode $skip
function firstWebMFrame($segment, $trackNumber, $skip=0) {
    foreach($segment as $x1) {
        if ($x1->name() == 'Cluster') {
            $cluserTimecode = $x1->Get('Timecode');
            foreach($x1 as $blockGroup) {
                $blockRaw = NULL;
                if ($blockGroup->name() == 'SimpleBlock') {
                    $blockRaw = $blockGroup->value();
                } elseif ($blockGroup->name() == 'BlockGroup') {
                    $blockRaw = $blockGroup->get('Block');
                }
                if (isset($blockRaw)) {
                    $block = new MatroskaBlock($blockRaw);
                    if ($block->trackNumber == $trackNumber && $block->keyframe) {
                        if (!isset($cluserTimecode) || $cluserTimecode + $block->timecode >= $skip) {
                            return $blockGroup;
                        } elseif (!isset($frame1)) {
                            $frame1 = $blockGroup;
                        }
                    }
                }
            }
        }
    }
    return isset($frame1) ? $frame1 : NULL;
}

function videoData($filename) {
    $data = array();

    // Open file
    $fileHandle = fopen($filename, 'rb');
    if (!$fileHandle) {
        error_log('could not open file');
        return $data;
    }

    try {
        $root = readMatroska($fileHandle);
        $data['container'] = $root->get('EBML')->get('DocType');

        // Locate segment information and tracks
        $segment = $root->get('Segment');
        if (!isset($segment)) throw new Exception('missing Segment element');

        // Get segment information
        $info = $segment->get('Info');
        if (isset($info)) {
            $timecodeScale = $info->get('TimecodeScale');
            $duration = $info->get('Duration');
            if (isset($timecodeScale) && isset($duration)) {
                $data['duration'] = 1e-9 * $timecodeScale * $duration;
            }
        }

        // Locate video track
        $tracks = $segment->get('Tracks');
        if (!isset($tracks)) throw new Exception('missing Tracks element');
        foreach($tracks as $trackEntry) {
            if ($trackEntry->name() == 'TrackEntry' && $trackEntry->get('TrackType') == 1) {
                $videoTrack = $trackEntry;
                break;
            }
        }
        if (!isset($videoTrack)) throw new Exception('no video track');

        // Get track information
        $videoAttr = $videoTrack->get('Video');
        if (!isset($videoAttr)) throw new Exception('missing video parameters');
        $pixelWidth = $videoAttr->get('PixelWidth');
        $pixelHeight = $videoAttr->get('PixelHeight');
        if (!isset($pixelWidth) || !isset($pixelHeight)) throw new Exception('no width or height');
        if ($pixelWidth == 0 || $pixelHeight == 0) throw new Exception('bad PixelWidth/PixelHeight');
        $displayWidth = $videoAttr->get('DisplayWidth', $pixelWidth);
        $displayHeight = $videoAttr->get('DisplayHeight', $pixelHeight);
        if ($displayWidth == 0 || $displayHeight == 0) throw new Exception('bad DisplayWidth/DisplayHeight');
        $data['width'] = $displayWidth;
        $data['height'] = $displayHeight;

        // Extract frame to use as thumbnail
        if ($videoAttr->get('AlphaMode') != NULL) {
            if (!($pixelWidth % 2 == 0 && $pixelHeight % 2 == 0 && $displayWidth % 2 == 0 && $displayHeight % 2 == 0)) {
                throw new Exception('preview frame blocked due to Chromium bug');
            }
        }
        $trackNumber = $videoTrack->get('TrackNumber');
        if (!isset($trackNumber)) throw new Exception('missing track number');
        if (isset($data['duration']) && $data['duration'] >= 5) {
            $skip = 1e9 / $timecodeScale;
        } else {
            $skip = 0;
        }
        $frame = firstWebMFrame($segment, $trackNumber, $skip);
        if (!isset($frame)) throw new Exception('no keyframes');
        $data['frame'] = muxWebMFrame($videoTrack, $frame);
    } catch (Exception $e) {
        error_log($e->getMessage());
    }

    fclose($fileHandle);
    return $data;
}
