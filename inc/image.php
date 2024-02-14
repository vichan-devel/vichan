<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

defined('TINYBOARD') or exit;

class Image {
	public $src, $format, $image, $size;
	public function __construct($src, $format = false, $size = false) {
		global $config;

		$this->src = $src;
		$this->format = $format;

		if ($config['thumb_method'] == 'imagick') {
			$classname = 'ImageImagick';
		} elseif (in_array($config['thumb_method'], array('convert', 'convert+gifsicle', 'gm', 'gm+gifsicle'))) {
			$classname = 'ImageConvert';
		} else {
			$classname = 'Image' . strtoupper($this->format);
			if (!class_exists($classname)) {
				error(_('Unsupported file format: ') . $this->format);
			}
		}

		$this->image = new $classname($this, $size);

		if (!$this->image->valid()) {
			$this->delete();
			error($config['error']['invalidimg']);
		}

		$this->size = (object)array('width' => $this->image->_width(), 'height' => $this->image->_height());
		if ($this->size->width < 1 || $this->size->height < 1) {
			$this->delete();
			error($config['error']['invalidimg']);
		}
	}

	public function resize($extension, $max_width, $max_height) {
		global $config;

		if ($config['thumb_method'] == 'imagick') {
			$classname = 'ImageImagick';
		} elseif ($config['thumb_method'] == 'convert') {
			$classname = 'ImageConvert';
		} elseif ($config['thumb_method'] == 'convert+gifsicle') {
			$classname = 'ImageConvert';
			$gifsicle = true;
		} elseif ($config['thumb_method'] == 'gm') {
			$classname = 'ImageConvert';
			$gm = true;
		} elseif ($config['thumb_method'] == 'gm+gifsicle') {
			$classname = 'ImageConvert';
			$gm = true;
			$gifsicle = true;
		} else {
			$classname = 'Image' . strtoupper($extension);
			if (!class_exists($classname)) {
				error(_('Unsupported file format: ') . $extension);
			}
		}

		$thumb = new $classname(false);
		$thumb->src = $this->src;
		$thumb->format = $this->format;
		$thumb->original_width = $this->size->width;
		$thumb->original_height = $this->size->height;

		$x_ratio = $max_width / $this->size->width;
		$y_ratio = $max_height / $this->size->height;

		if (($this->size->width <= $max_width) && ($this->size->height <= $max_height)) {
			$width = $this->size->width;
			$height = $this->size->height;
		} elseif (($x_ratio * $this->size->height) < $max_height) {
			$height = ceil($x_ratio * $this->size->height);
			$width = $max_width;
		} else {
			$width = ceil($y_ratio * $this->size->width);
			$height = $max_height;
		}

		$thumb->_resize($this->image->image, $width, $height);

		return $thumb;
	}

	public function to($dst) {
		$this->image->to($dst);
	}

	public function delete() {
		file_unlink($this->src);
	}
	public function destroy() {
		$this->image->_destroy();
	}
}

class ImageGD {
	public function GD_create() {
		$this->image = imagecreatetruecolor($this->width, $this->height);
	}
	public function GD_copyresampled() {
		imagecopyresampled($this->image, $this->original, 0, 0, 0, 0, $this->width, $this->height, $this->original_width, $this->original_height);
	}
	public function GD_resize() {
		$this->GD_create();
		$this->GD_copyresampled();
	}
}

class ImageBase extends ImageGD {
	public $image, $src, $original, $original_width, $original_height, $width, $height;
	public function valid() {
		return (bool)$this->image;
	}

	public function __construct($img, $size = false) {
		if (method_exists($this, 'init'))
			$this->init();

		if ($size && $size[0] > 0 && $size[1] > 0) {
			$this->width = $size[0];
			$this->height = $size[1];
		}

		if ($img !== false) {
			$this->src = $img->src;
			$this->from();
		}
	}

	public function _width() {
		if (method_exists($this, 'width'))
			return $this->width();
		// use default GD functions
		return imagesx($this->image);
	}
	public function _height() {
		if (method_exists($this, 'height'))
			return $this->height();
		// use default GD functions
		return imagesy($this->image);
	}
	public function _destroy() {
		if (method_exists($this, 'destroy'))
			return $this->destroy();
		// use default GD functions
		return imagedestroy($this->image);
	}
	public function _resize($original, $width, $height) {
		$this->original = &$original;
		$this->width = $width;
		$this->height = $height;

		if (method_exists($this, 'resize'))
			$this->resize();
		else
			// use default GD functions
			$this->GD_resize();
	}
}

class ImageImagick extends ImageBase {
	public function init() {
		$this->image = new Imagick();
		$this->image->setBackgroundColor(new ImagickPixel('transparent'));
	}
	public function from() {
		try {
			$this->image->readImage($this->src);
		} catch(ImagickException $e) {
			// invalid image
			$this->image = false;
		}
	}
	public function to($src) {
		global $config;
		if ($config['strip_exif']) {
			$this->image->stripImage();
		}
		if (preg_match('/\.gif$/i', $src))
			$this->image->writeImages($src, true);
		else
			$this->image->writeImage($src);
	}
	public function width() {
		return $this->image->getImageWidth();
	}
	public function height() {
		return $this->image->getImageHeight();
	}
	public function destroy() {
		return $this->image->destroy();
	}
	public function resize() {
		global $config;

		if ($this->format == 'gif' && ($config['thumb_ext'] == 'gif' || $config['thumb_ext'] == '')) {
			$this->image = new Imagick();
			$this->image->setFormat('gif');

			$keep_frames = array();
			for ($i = 0; $i < $this->original->getNumberImages(); $i += floor($this->original->getNumberImages() / $config['thumb_keep_animation_frames']))
				$keep_frames[] = $i;

			$i = 0;
			$delay = 0;
			foreach ($this->original as $frame) {
				$delay += $frame->getImageDelay();

				if (in_array($i, $keep_frames)) {
					// $frame->scaleImage($this->width, $this->height, false);
					$frame->sampleImage($this->width, $this->height);
					$frame->setImagePage($this->width, $this->height, 0, 0);
					$frame->setImageDelay($delay);
					$delay = 0;

					$this->image->addImage($frame->getImage());
				}
				$i++;
			}
		} else {
			$this->image = clone $this->original;
			$this->image->scaleImage($this->width, $this->height, false);
		}
	}
}


class ImageConvert extends ImageBase {
	public $width, $height, $temp, $gm = false, $gifsicle = false;

	public function init() {
		global $config;

		if ($config['thumb_method'] == 'gm' || $config['thumb_method'] == 'gm+gifsicle')
			$this->gm = true;
		if ($config['thumb_method'] == 'convert+gifsicle' || $config['thumb_method'] == 'gm+gifsicle')
			$this->gifsicle = true;

		$this->temp = false;
	}
	public function get_size($src, $try_gd_first = true) {
		if ($try_gd_first) {
			if ($size = @getimagesize($src))
				return $size;
		}
		$size = shell_exec_error(($this->gm ? 'gm ' : '') . 'identify -format "%w %h" ' . escapeshellarg($src . '[0]'));
		if (preg_match('/^(\d+) (\d+)$/', $size, $m))
			return array($m[1], $m[2]);
		return false;
	}
	public function from() {
		if ($this->width > 0 && $this->height > 0) {
			$this->image = true;
			return;
		}
		$size = $this->get_size($this->src, false);
		if ($size) {
			$this->width = $size[0];
			$this->height = $size[1];

			$this->image = true;
		} else {
			// mark as invalid
			$this->image = false;
		}
	}
	public function to($src) {
		global $config;

		if (!$this->temp) {
			if ($config['strip_exif']) {
				if($error = shell_exec_error(($this->gm ? 'gm ' : '') . 'convert ' .
						escapeshellarg($this->src) . ' -auto-orient -strip ' . escapeshellarg($src))) {
					$this->destroy();
					error(_('Failed to redraw image!'), null, $error);
				}
			} else {
				if($error = shell_exec_error(($this->gm ? 'gm ' : '') . 'convert ' .
						escapeshellarg($this->src) . ' -auto-orient ' . escapeshellarg($src))) {
					$this->destroy();
					error(_('Failed to redraw image!'), null, $error);
				}
			}
		} else {
			rename($this->temp, $src);
			chmod($src, 0664);
		}
	}
	public function width() {
		return $this->width;
	}
	public function height() {
		return $this->height;
	}
	public function destroy() {
		@unlink($this->temp);
		$this->temp = false;
	}
	public function resize() {
		global $config;

		if ($this->temp) {
			// remove old
			$this->destroy();
		}

		$this->temp = tempnam($config['tmp'], 'convert') . ($config['thumb_ext'] == '' ? '' : '.' . $config['thumb_ext']);

		$config['thumb_keep_animation_frames'] = (int)$config['thumb_keep_animation_frames'];

		if ($this->format == 'gif' && ($config['thumb_ext'] == 'gif' || $config['thumb_ext'] == '') && $config['thumb_keep_animation_frames'] > 1) {
			if ($this->gifsicle) {
				if (($error = shell_exec("gifsicle -w --unoptimize -O2 --resize {$this->width}x{$this->height} < " .
						escapeshellarg($this->src . '') . " \"#0-{$config['thumb_keep_animation_frames']}\" -o " .
						escapeshellarg($this->temp))) || !file_exists($this->temp)) {
					$this->destroy();
					error(_('Failed to resize image!'), null, $error);
				}
			} else {
				$convert_args = &$config['convert_args'];

				if (($error = shell_exec_error(($this->gm ? 'gm ' : '') . 'convert ' .
					sprintf($convert_args,
						$this->width,
						$this->height,
						escapeshellarg($this->src),
						$this->width,
						$this->height,
						escapeshellarg($this->temp)))) || !file_exists($this->temp)) {
					$this->destroy();
					error(_('Failed to resize image!'), null, $error);
				}
				if ($size = $this->get_size($this->temp)) {
					$this->width = $size[0];
					$this->height = $size[1];
				}
			}
		} else {
			$convert_args = &$config['convert_args'];

			if (($error = shell_exec_error(($this->gm ? 'gm ' : '') . 'convert ' .
				sprintf($convert_args,
					$this->width,
					$this->height,
					escapeshellarg($this->src . '[0]'),
					$this->width,
					$this->height,
					escapeshellarg($this->temp)))) || !file_exists($this->temp)) {

					if (strpos($error, "known incorrect sRGB profile") === false &&
                                            strpos($error, "iCCP: Not recognizing known sRGB profile that has been edited") === false &&
                                            strpos($error, "cHRM chunk does not match sRGB") === false) {
						$this->destroy();
						error(_('Failed to resize image!')." "._('Details: ').nl2br(htmlspecialchars($error)), null, array('convert_error' => $error));
					}
					if (!file_exists($this->temp)) {
						$this->destroy();
						error(_('Failed to resize image!'), null, $error);
					}
			}
			if ($size = $this->get_size($this->temp)) {
				$this->width = $size[0];
				$this->height = $size[1];
			}
		}
	}
}

class ImagePNG extends ImageBase {
	public function from() {
		$this->image = @imagecreatefrompng($this->src);
	}
	public function to($src) {
		global $config;
		imagepng($this->image, $src);
	}
	public function resize() {
		$this->GD_create();
		imagecolortransparent($this->image, imagecolorallocatealpha($this->image, 0, 0, 0, 0));
		imagesavealpha($this->image, true);
		imagealphablending($this->image, false);
		$this->GD_copyresampled();
	}
}

class ImageGIF extends ImageBase {
	public function from() {
		$this->image = @imagecreatefromgif($this->src);
	}
	public function to($src) {
		imagegif ($this->image, $src);
	}
	public function resize() {
		$this->GD_create();
		imagecolortransparent($this->image, imagecolorallocatealpha($this->image, 0, 0, 0, 0));
		imagesavealpha($this->image, true);
		$this->GD_copyresampled();
	}
}

class ImageJPG extends ImageBase {
	public function from() {
		$this->image = @imagecreatefromjpeg($this->src);
	}
	public function to($src) {
		imagejpeg($this->image, $src);
	}
}
class ImageJPEG extends ImageJPG {
}

class ImageBMP extends ImageBase {
	public function from() {
		$this->image = @imagecreatefrombmp($this->src);
	}
	public function to($src) {
		imagebmp($this->image, $src);
	}
}

class ImageWEBP extends ImageBase {
	public function from() {
		$this->image = @imagecreatefromwebp($this->src);
	}
	public function to($src) {
		imagewebp($this->image, $src);
	}
}
