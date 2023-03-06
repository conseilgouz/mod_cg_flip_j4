<?php
/**
* CG Flip - Joomla Module 
* Version			: 2.0.5
* copyright 		: Copyright (C) 2023 ConseilGouz. All rights reserved.
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
		// $slideslist = json_decode((string)str_replace("||", "\"", (string)$value));
		foreach ($value as $item) {
		    $imgname = $item->file_name;
		    if ($pos = strpos($imgname,"#")) {
		        $imgname = substr($imgname,0, $pos);
		    }
			$imgthumb = $imgname;
			$pos = strrpos($imgthumb,'/');
			$len = strlen($imgthumb);
			$imgthumb = substr($imgthumb,0,$pos+1).'th/'.substr($imgthumb,$pos+1,$len);
			if (!File::exists('../'.$imgthumb)) { // fichier existe déjà  : on sort
			    self::createThumbNail(URI::root().$imgname,JPATH_ROOT.'/'.$imgthumb,$compression);
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
		return self::img_resize($fileIn, $fileOut, $type, $src[2],$src[3], $dst[2], $dst[3], $compression);
	}
    function img_resize($imgSrc, $imgDest, $typeSrc, $wSrc, $hSrc, $wDest, $hDest, $quality = 70)
    {
        $hDest = (int) $hDest;
        if ($typeSrc === IMAGETYPE_PNG) {
            $img = imagecreatefrompng($imgSrc);
            if ($img === false) {
                $errorMsg = 'ErrorPNGFunction';
                return false;
            }
            $imgNew = imagecreatetruecolor($wDest, $hDest);
            imagealphablending($imgNew, false);
            imagesavealpha($imgNew, true);
            $transparency = imagecolorallocatealpha($imgNew, 255, 255, 255, 127);
            imagefilledrectangle($imgNew, 0, 0, $wDest, $hDest, $transparency);
            imagecopyresampled($imgNew, $img, 0, 0, 0, 0, $wDest, $hDest, $wSrc, $hSrc);
            imagepng($imgNew, $imgDest, 9);
        } elseif ($typeSrc === IMAGETYPE_WEBP) { // WEBP
            $img = imagecreatefromwebp($imgSrc);
            $imgNew = imagecreatetruecolor($wDest, $hDest);
            imagecopyresampled($imgNew, $img, 0, 0, 0, 0, $wDest, $hDest, $wSrc, $hSrc);
            imagewebp($imgNew, $imgDest, $quality);
        } elseif ($typeSrc == IMG_JPG) {
            $img = imagecreatefromjpeg($imgSrc);
            $imgNew = imagecreatetruecolor($wDest, $hDest);
            imagecopyresampled($imgNew, $img, 0, 0, 0, 0, $wDest, $hDest, $wSrc, $hSrc);
            imagejpeg($imgNew, $imgDest, $quality);
        } else {
            $errorMsg = 'ErrorNotSupportedImage';
			return false;
        }
		return true;
    }

}