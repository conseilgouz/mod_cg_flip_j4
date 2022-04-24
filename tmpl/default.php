<?php
/**
 * @package CG Flip Module
 * @version 2.0.4
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2022 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
 */
 use ConseilGouz\Module\CGFlip\Site\Helper\CGFlipHelper;

defined('_JEXEC') or die('Restricted access'); 
if ($params->get('ratiotype', '0') == '0') {
	$ratio = $params->get('ratio', '1.41');
} else {
	$ratio = $params->get('ratio_perso', '1.0');
	$ratio = str_replace(',','.',$ratio);
}
$init = $params->get('init','double');
$init_phone = $params->get('init_phone','single');
?>
<div class="cg_flip_main cg_flip_<?php echo $module->id;?>" data="<?php echo $module->id;?>"> 
<?php if (($params->get('typemenu', 'full') != 'aucun') && ($params->get('menu', 'bas') == 'haut')) { 
	echo CGFlipHelper::buildButtons($module,$toc,$nbpages,$params->get('typemenu', 'full'),$params->get('menu', 'bas'));
}
 ?>
	<?php if (($type == "articles") || ($type == "events")) { // preload articles
		echo "<div class='cg_hide'>";
		echo implode("",$pages);
		echo "</div>";
		} ?>
<div class="magazine-viewport" id="magazine-viewport">
	<div class="cg-container">
		<div class="magazine">
		</div>
	</div>
</div>
<?php if (($params->get('typemenu', 'full') != 'aucun') && ($params->get('menu', 'bas') == 'bas')) { 
	echo CGFlipHelper::buildButtons($module,$toc,$nbpages,$params->get('typemenu', 'full'),$params->get('menu', 'bas'));
}
?>
</div>
