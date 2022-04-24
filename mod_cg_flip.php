<?php
/**
 * @package CG Flip Module
 * @version 2.0.4 
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2022 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
 */
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;
use ConseilGouz\Module\CGFlip\Site\Helper\CGFlipHelper;

$document 		= JFactory::getDocument();
$modulefield	= ''.JURI::base(true).'/media/'.$module->module;

HTMLHelper::_('jquery.framework',true);

$document->addStyleSheet($modulefield.'/css/cgflip.min.css'); 
$document->addStyleSheet($modulefield.'/css/up.css'); 
$document->addScript($modulefield.'/js/turn.min.js');
$document->addScript($modulefield.'/js/magazine.min.js');

$document->addScript($modulefield.'/js/cg_flip.min.js');

$font = '@font-face {
  font-family: "cgflip";
  src: url("'.$modulefield.'/fonts/cgflip.eot?59685174");
  src: url("'.$modulefield.'/fonts/cgflip.eot?59685174#iefix") format("embedded-opentype"),
       url("'.$modulefield.'/fonts/cgflip.woff2?59685174") format("woff2"),
       url("'.$modulefield.'/fonts/cgflip.woff?59685174") format("woff"),
       url("'.$modulefield.'/fonts/cgflip.ttf?59685174") format("truetype"),
       url("'.$modulefield.'/fonts/cgflip.svg?59685174#cgflip") format("svg");
  font-weight: normal;
  font-style: normal;
}';

$document->addStyleDeclaration($font);

$document->addStyleDeclaration($params->get('css_gen','')); // custom module css

$files = array();
$type = $params->get('cg_type', 'dir');
$optimize = $params->get('optimize', '0');
$toc = "";
if ($type == "dir") {
	$dir =  $params->get('dir', '');
	if ($optimize == '1') $dir .= '/th';
	$files = glob('images/'.$dir.'/*.jpg'); 
	$nbpages = count($files);
} elseif ($type == "files") {
	$fileslist = $params->get('slideslist');
	$files = array();
	foreach ($fileslist as $file) {
		$imgname = $file->file_name;
		if ($optimize == '1')  {
			$imgthumb = $imgname;
			$pos = strrpos($imgthumb,'/');
			$len = strlen($imgthumb);
			$imgthumb = substr($imgthumb,0,$pos+1).'th/'.substr($imgthumb,$pos+1,$len);
			$files[] = $imgthumb;
		} else {
			$files[] = $imgname;
		}
	}
	$nbpages = count($files);
} elseif ($type == "articles") {
	$pages =  CGFlipHelper::getFlipArticles($params,$module->id);
	$toc = CGFlipHelper::getFlipToc(); // table of contents
	$nbpages = count($pages);						  
} elseif ($type == "events") {
	$pages =  CGFlipHelper::getFlipEvents($params);
	$nbpages = count($pages);
    if (($nbpages == 1) && (strpos($pages[0],JTEXT::_('CG_NO_EVENT')) !== false) && ($params->get('emptyhide', 'dir') == 'true')) return true; // no event
}
if ($params->get('ratiotype', '0') == '0') {
	$ratio = $params->get('ratio', '1.41');
} else {
	$ratio = $params->get('ratio_perso', '1.0');
	$ratio = str_replace(',','.',$ratio);
}
$document->addScriptOptions('cg_flip_'.$module->id, 
	array('id' => $module->id,'base' => JURI::base(true),'type' => $type,'ratio' => $ratio
		,'speffect' => $params->get('sp-effect','fadeIn'),'nbpages' => $nbpages,'onepage' => JText::_('CG_UNE_PAGE')
		,'twopages' => JText::_('CG_DEUX_PAGE'),'init' => $params->get('init','double'),'init_phone' => $params->get('init_phone','single')
		,'files' => $files,'auto' => $params->get('auto', 'false'),'auto_delay' => $params->get('auto_delay', '3000'))
	);


require(ModuleHelper::getLayoutPath($module->module));
?>