<?php
class ExifData {
	function ExifData($data) {
		$this->data = $data;
	}
	function get($tag) {
		switch($tag) {
			case 'DateTime':
				$v = $this->getValue('EXIF','DateTimeOriginal');
				if(!$v)$v = $this->getValue('EXIF','DateTimeDigitized');
				if(!$v)$v = $this->getValue('IFD0','DateTime');
				return $v;
			case 'Date':
				$v = $this->get('DateTime');
				if(($timestamp = strtotime($v)) !== false){
					$v = date('j-n-\'y', $timestamp);
				}
				return $v;
			case 'ApertureValue':
				$v = $this->getValue('EXIF','ApertureValue');
				if (!$v) return $this->getValue('COMPUTED','ApertureFNumber');
				$v = explode('/', $v);
				return sprintf('f/%.01f', pow(2, $v[0] / $v[1] / 2));
			case 'Dimension':
				$w = $this->get('Width');
				$h = $this->get('Height');
				if(isset($w,$h)) return utf8_encode(sprintf("%s × %s pixels", $w, $h));//utf8 encode if page encoding is ansi
				return;
			case 'Height':
				return $this->getValue('COMPUTED','Height');
			case 'Width':
				return $this->getValue('COMPUTED','Width');
			case 'ExposureTime':
				$v = explode('/', $this->getValue('EXIF','ExposureTime'));
				if(count($v)>1){
					if($v[0] && $v[1]){
						if ($v[0] / $v[1] < 1) return sprintf('1/%d sec.', $v[1] / $v[0]);
						else return sprintf('%d sec.', $v[0] / $v[1]);
					}
				}
				return;
			case 'ExposureBiasValue':
				$v = $this->getValue('EXIF','ExposureBiasValue');
				if (!$v) return;
				$v = explode('/', $v);
				if(count($v)>1)if($v[1])return sprintf('%s%.01f', $v[0] * $v[1] > 0 ? '' : '', $v[0] / $v[1]);
				return;
			case 'ExposureProgram':
				$v = $this->getValue('EXIF','ExposureProgram');
				switch ($v) {
					case 0:return 'Not defined';
					case 1:return 'Manual';
					case 2:return 'Normal program';
					case 3:return 'Aperture priority';
					case 4:return 'Shutter priority';
					case 5:return 'Creative program (biased toward depth of field)';
					case 6:return 'Action program (biased toward fast shutter speed)';
					case 7:return 'Portrait mode (for closeup photos with the background out of focus)';
					case 8:return 'Landscape mode (for landscape photos with the background in focus)';
					default:return $v;
				}
			case 'Flash':
				$v = $this->getValue('EXIF','Flash');
				switch ($v){
					case 0x0000:return 'Flash did not fire.';
					case 0x0001:return 'Flash fired.';
					case 0x0005:return 'Strobe return light not detected.';
					case 0x0007:return 'Strobe return light detected.';
					case 0x0009:return 'Flash fired, compulsory flash mode.';
					case 0x000d:return 'Flash fired, compulsory flash mode, return light not detected.';
					case 0x000f:return 'Flash fired, compulsory flash mode, return light detected.';
					case 0x0010:return 'Flash did not fire, compulsory flash mode.';
					case 0x0018:return 'Flash did not fire, auto mode.';
					case 0x0019:return 'Flash fired, auto mode.';
					case 0x001d:return 'Flash fired, auto mode, return light not detected.';
					case 0x001f:return 'Flash fired, auto mode, return light detected.';
					case 0x0020:return 'No flash function.';
					case 0x0041:return 'Flash fired, red-eye reduction mode.';
					case 0x0045:return 'Flash fired, red-eye reduction mode, return light not detected.';
					case 0x0047:return 'Flash fired, red-eye reduction mode, return light detected.';
					case 0x0049:return 'Flash fired, compulsory flash mode, red-eye reduction mode.';
					case 0x004d:return 'Flash fired, compulsory flash mode, red-eye reduction mode, return light not detected.';
					case 0x004f:return 'Flash fired, compulsory flash mode, red-eye reduction mode, return light detected.';
					case 0x0058:return 'Flash did not fire, auto mode, red-eye reduction mode.';
					case 0x0059:return 'Flash fired, auto mode, red-eye reduction mode.';
					case 0x005d:return 'Flash fired, auto mode, return light not detected, red-eye reduction mode.';
					case 0x005f:return 'Flash fired, auto mode, return light detected, red-eye reduction mode.';
					default:return $v;
			}
			case 'FocalLength':
				$v = $this->getValue('EXIF','FocalLength');
				if (!$v) return;
				$v = explode('/', $v);
				if(count($v)>1)if($v[1])return sprintf('%.1f mm', $v[0] / $v[1]);
				return;
			case 'Model':
				$ma = $this->getValue('IFD0','Make');
				$mo = $this->getValue('IFD0','Model');
				if($ma || $mo) return ($ma ? $ma.' ' : '').$mo;
				return;
			case 'Orientation':
				$v = $this->getValue('IFD0','Orientation');
				switch ($v) {
					case 1:return 'top - left';
					case 2:return 'top - right';
					case 3:return 'bottom - right';
					case 4:return 'bottom - left';
					case 5:return 'left - top';
					case 6:return 'right - top';
					case 7:return 'right - bottom';
					case 8:return 'left - bottom';
					default:return $v = $this->getValue('EXIF','Orientation');
				}	
			case 'ShutterSpeedValue':
				$v = $this->getValue('EXIF','ShutterSpeedValue');
				if (!$v) return;
				$v = explode('/', $v);
				if(count($v)>1)if($v[1]) return sprintf('%.0f/%.0f sec. (APEX: %d)', $v[0], $v[1], pow(sqrt(2), $v[0] / $v[1]));
				return;
			default:
				$v = $this->getValue('IFD0', $tag);
				if(!$v)$v = $this->getValue('EXIF', $tag);
				return $v;
		}
	}
	function getValue($section, $tag) {
		if(isset($this->data[$section]))if(isset($this->data[$section][$tag])) return $this->data[$section][$tag];
		return;
	}
}