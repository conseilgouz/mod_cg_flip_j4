<?php
/**
* CG Flip - Joomla Module 
* Version			: 2.0.4
* copyright 		: Copyright (C) 2022 ConseilGouz. All rights reserved.
* license    		: http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
*/
namespace ConseilGouz\Module\CGFlip\Site\Rule;

defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
class ThumbnailRule extends FormRule
{
	public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null) {
	    $nb = 0;
	    $params = $input->get('params');
	    $type = $params->cg_type;
	    $compression = $params->compression;
	    $recreate = $params->recreate;
	    if ($type == "dir") {
	        if ($params->optimize == '1') {
	            if ($recreate == '1') {
	                $_dir = JPATH_ROOT.'/images/'.$params->dir.'/th';
	                if (Folder::exists($_dir))
	                   Folder::delete($_dir); // supprime avant creation
	            }
	            self::thumbnailFromDir($params,$compression);
	        }
	    } else {
	        self::thumbnailFromSingleImages($value,$params,$compression);
	    }
	    return true;
	}
	function checkparameter($dir) {
		$pattern = array('/\$catid/','/\$catalias/', '/\$articleid/', '/\$articlealias/');
		$replace = array('catid', 'articleid');
		$root = preg_replace($pattern, $replace, $dir);
		if (($pos = strpos($root,'$')) !== false) {
			Factory::getApplication()->enqueueMessage('r&eacute;pertoire <strong>'.$dir.'</strong> incorrect: zone '.substr($root,$pos).' inconnue','error');
			return false;
		}
		return true;
	}
	function thumbnailFromDir($params,$compression) {
	    $files = Folder::files(JPATH_ROOT.'/images/'.$params->dir,null,null ,null ,array() , array('desc.txt','index.html','.htaccess'));
	    $_dir = 'images/'.$params->dir;
	    $nb = 0;
		if (count($files) > 0) { 
			foreach ($files as $file) {
				$imgthumb = $file;
				$pos = strrpos($imgthumb,'/');
				$len = strlen($imgthumb);
				$imgthumb = $_dir.'/th/'.substr($imgthumb,$pos,$len);
				if (!File::exists('../'.$imgthumb)) { // fichier existe déjà  : on sort
					self::createThumbNail(URI::root().$_dir.'/'.$file,$imgthumb,$compression);
					$nb = $nb+1;
				}
			} 
			if ($nb > 0) {
				Factory::getApplication()->enqueueMessage($nb.Text::_('CG_THUMB_OK') );
			}
		}
	}
	function thumbnailFromSingleImages($value,$params,$compression) {
		$slideslist = json_decode((string)str_replace("||", "\"", (string)$value));
		foreach ($slideslist as $item) {
			$imgname = str_replace("||", "\"", $item->imgname);
			$imgthumb = $imgname;
			$pos = strrpos($imgthumb,'/');
			$len = strlen($imgthumb);
			$imgthumb = substr($imgthumb,0,$pos+1).'th/'.substr($imgthumb,$pos+1,$len);
			if (!File::exists('../'.$imgthumb)) { // fichier existe déjà  : on sort
				self::createThumbNail(URI::root().$imgname,$imgthumb,$compression);
				$nb = $nb+1;
			}
		}
		if ($nb > 0) {
			Factory::getApplication()->enqueueMessage($nb.Text::_('CG_THUMB_OK') );
		}
	}
	function createThumbNail($fileIn,$fileOut,$compression) {
	    list($w, $h, $type) = getimagesize($fileIn);
		// size of the image
   	    $width = $w;
	    $height = $h;
        $scale = (($width / $w) > ($height / $h)) ? ($width / $w) : ($height / $h); // greater rate
        $newW = $width/$scale;    // check the size of in file
        $newH = $height/$scale;
        // which side is larger (rounding error)
        if (($w - $newW) > ($h - $newH)) {
            $src = array(floor(($w - $newW)/2), 0, floor($newW), $h);
        } else {
			$src = array(0, floor(($h - $newH)/2), $w, floor($newH));
		}
        $dst = array(0,0, floor($width), floor($height));
	    switch($type) {
	        case IMAGETYPE_JPEG:
				if (!function_exists('imagecreatefromjpeg')) {
						$errorMsg = 'ErrorNoJPGFunction';
						return false;
				}
				try {
						$image1 = imagecreatefromjpeg($fileIn);
				} catch(\Exception $exception) {
					$errorMsg = 'ErrorJPGFunction';
					return false;
				}
				break;
	        case IMAGETYPE_PNG :
				if (!function_exists('ImageCreateFromPNG')) {
					$errorMsg = 'ErrorNoPNGFunction';
					return false;
				}
				try {
					$image1 = ImageCreateFromPNG($fileIn);
				} catch(\Exception $exception) {
					$errorMsg = 'ErrorPNGFunction';
					return false;
				}
				break;
            case IMAGETYPE_GIF :
				if (!function_exists('ImageCreateFromGIF')) {
					$errorMsg = 'ErrorNoGIFFunction';
					return false;
				}
				try {
					$image1 = ImageCreateFromGIF($fileIn);
				} catch(\Exception $exception) {
					$errorMsg = 'ErrorGIFFunction';
					return false;
				}
				break;
            case IMAGETYPE_WBMP:
				if (!function_exists('ImageCreateFromWBMP')) {
					$errorMsg = 'ErrorNoWBMPFunction';
					return false;
				}
				try {
					$image1 = ImageCreateFromWBMP($fileIn);
				} catch(\Exception $exception) {
					$errorMsg = 'ErrorWBMPFunction';
					return false;
				}
				break;
           /* case IMAGETYPE_WEBP:
				if (!function_exists('ImageCreateFromWEBP')) {
					$errorMsg = 'ErrorNoWEBPFunction';
					return false;
				}
				try {
					$image1 = ImageCreateFromWEBP($fileIn);
				} catch(\Exception $exception) {
					$errorMsg = 'ErrorWBMPFunction';
					return false;
				}
				break;*/
            Default:
				$errorMsg = 'ErrorNotSupportedImage';
				return false;
				break;
	        }
			if ($image1) {

				$image2 = @ImageCreateTruecolor($dst[2], $dst[3]);
				if (!$image2) {
					$errorMsg = 'ErrorNoImageCreateTruecolor';
					return false;
				}
				
				ImageCopyResampled($image2, $image1, $dst[0],$dst[1], $src[0],$src[1], $dst[2],$dst[3], $src[2],$src[3]);
				
				// Create the file
	            $typeOut = ($type == IMAGETYPE_WBMP) ? IMAGETYPE_PNG : $type;
	            header("Content-type: ". image_type_to_mime_type($typeOut));
				
				switch($typeOut) {
		            case IMAGETYPE_JPEG:
						if (!function_exists('ImageJPEG')) {
							$errorMsg = 'ErrorNoJPGFunction';
							return false;
						}
						ob_start();
						
						if (!imagejpeg($image2,NULL,$compression)) {
							$errorMsg = 'ErrorWriteFile';
							ob_end_clean();
							return false;
						}
						$imgJPEGToWrite = ob_get_contents();
						ob_end_clean();
						if(!File::write('../'.$fileOut, $imgJPEGToWrite)) {
							$errorMsg = 'ErrorWriteFile';
							return false;
						}						
					break;
		            
					case IMAGETYPE_PNG :
						if (!function_exists('ImagePNG')) {
							$errorMsg = 'ErrorNoPNGFunction';
							return false;
						}
						
						if (!@ImagePNG($image2, NULL,$compression)) {
							$errorMsg = 'ErrorWriteFile';
							return false;
						}
						$imgGIFToWrite = ob_get_contents();
						ob_end_clean();
						if(!File::write('../'.$fileOut, $imgGIFToWrite)) {
							$errorMsg = 'ErrorWriteFile';
							return false;
						}

						break;
		            
					case IMAGETYPE_GIF :
						if (!function_exists('ImageGIF')) {
							$errorMsg = 'ErrorNoGIFFunction';
							return false;
						}
						
						if ($jfile_thumbs == 1) {
							ob_start();
							if (!@ImageGIF($image2, NULL,$compression)) {
								ob_end_clean();
								$errorMsg = 'ErrorWriteFile';
								return false;
							}
							$imgGIFToWrite = ob_get_contents();
							ob_end_clean();
							
							if(!File::write('../'.$fileOut, $imgGIFToWrite)) {
								$errorMsg = 'ErrorWriteFile';
								return false;
							}
						} else {
							if (!@ImageGIF($image2, $fileOut)) {
								$errorMsg = 'ErrorWriteFile';
								return false;
							}
						}
					break;
					case IMAGETYPE_WEBP :
						if (!function_exists('ImageWEBP')) {
							$errorMsg = 'ErrorNoWEBPFunction';
							return false;
						}
						
						if (!@ImageWEBP($image2, NULL,$compression)) {
							$errorMsg = 'ErrorWriteFile';
							return false;
						}
						$imgWEBPToWrite = ob_get_contents();
						ob_end_clean();
						if(!File::write('../'.$fileOut, $imgWEBPToWrite)) {
							$errorMsg = 'ErrorWriteFile';
							return false;
						}

						break;
		            
		            
					Default:
						$errorMsg = 'ErrorNotSupportedImage';
						return false;
						break;
				}
				// free memory
				ImageDestroy($image1);
	            ImageDestroy($image2);
								
			}
		return true;
	}

}