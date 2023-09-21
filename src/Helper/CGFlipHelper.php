<?php
/**
 * @package CG Flip Module
 * @version 2.1.1
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2023 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
 */
 namespace ConseilGouz\Module\CGFlip\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Content\Site\Model\ArticlesModel; 
use Joomla\Component\Content\Site\Model\ArticleModel; 
use Joomla\Component\Content\Site\Helper\RouteHelper; 

class CGFlipHelper
{
	private $titles = array();
	private static $toc;
	
    static function getFlipArticles($params,$moduleid)
	{   // apply contents plugins
	    PluginHelper::importPlugin('content');
		$access = ComponentHelper::getParams('com_content')->get('show_noauth');
		$user = Factory::getUser();
		$authorised = Access::getAuthorisedViewLevels($user->id);
		$result = array();	
		$hasToC = array();
		$ix = 1;
		$sectionsList = $params->get('articlesist');
		foreach ($sectionsList as $item) {
			if ($item->sf_type == "category") { // one category selected
			    $articles = self::getCategory($item->category,$params,isset($item->choixdate) ? $item->choixdate : 'modified' );
				$multi = isset($item->articlepage) && ($item->articlepage > 1);
				$multicol = $multi && isset($item->articlecol) && ($item->articlecol > 1);
				$col = 0; $nbart = 0;
				$unepage = "";
				foreach ($articles as $article) {
					if ($multi && ($nbart == 0)) {
						if ($multicol && ($col == 0)) {
							$unepage = '<div class="cg_flip_article"><div class="cg_page_article fg-row">';
						} else {
							$unepage = '<div class="cg_flip_article"><div class="cg_page_article">';
						}
					}
					$show_date_field  = isset($item->choixdate) ? $item->choixdate : 'modified';
					$show_date_format = 'Y-m-d H:i:s';
					$article->displayDate = '';
					$article->displayDate = HTMLHelper::_('date', $article->$show_date_field, $show_date_format);
					$article->slug    = $article->id . ':' . $article->alias;
					$article->catslug = $article->catid . ':' . $article->category_alias;
					if ($access || in_array($article->access, $authorised)) {
						$article->link = Route::_(RouteHelper::getArticleRoute($article->slug, $article->catid, $article->language));
					} else {
						$article->link = Route::_('index.php?option=com_users&view=login');
					}
					if ($multi && ($col == 0))	$titles[] = $article->title;
					elseif (!$multi) $titles[] = $article->title;
					$art = self::render_article($article,$params,$multi);
					if ($multi) {
						if ($multicol) $unepage .= '<div class="cg_un_article fg-c'.(12 / $item->articlecol).'">'.$art.'</div>';
						else $unepage .= '<div class="cg_un_article">'.$art.'</div>';
						$col += 1; $nbart += 1;
					} else {
						$result[] = $art;
					}
					if (($multi && ($nbart == $item->articlepage) ) ||  ($multicol && ($col == $item->articlecol) && ($nbart >= $item->articlepage)) ) {
						$result[] = $unepage.'</div></div>';
						$col = 0;$nbart = 0; $unepage= "";
					}
				}
				if ($unepage != "") $result[] = $unepage.'</div></div>';

			} elseif ($item->sf_type == "content") { // one article selected
				$article = self::getArticle($item->article,$params);
				$titles[] = $article->title;
				$result[] = self::render_article($article,$params,false);
			} else { // free text
				$article = $item->text;
				// apply contents plugins
				$item_tmp = new \stdClass;
				$item_tmp->text = $article;
				$item_tmp->params = $params;
				Factory::getApplication()->triggerEvent('onContentPrepare', array ('com_content.article', &$item_tmp, &$item_tmp->params, 0));
				$item_tmp->text = str_replace(array("\r", "\n"),"",$item_tmp->text);
				$item_tmp->text = self::linktopage($moduleid, $item_tmp->text); // 1.1.3: page parameter
				$titles[] = $title = self::lookfortitle($item_tmp->text);
				$result[] =  '<div class="cg_flip_article"><div class="cg_page_article"><div class="cg_un_article">'.$item_tmp->text.'</div></div></div>';
				if (strpos($item_tmp->text,'{summary}') !== false) {
					$hasToC[] = true;
				} else {
					$hasToC[] = false;
				}
			}
			$text = '';
			foreach ($titles as $keytitle=>$title) {
				if (!$title) continue;
				$text .= "<p class='cg_summary'><a class='go_page' data-id='".$moduleid."' data-page='".($keytitle + 1)."' href='#' title='page ".($keytitle + 1)."'>".$title.".........".($keytitle + 1)."</a></p>";
			}
			self::$toc = $text;
		}
		foreach ($hasToC as $keyToC=>$valToC) { // check for summary in a section
			if (!$valToC) continue;
			$result[$keyToC] = str_replace('{summary}',self::$toc,$result[$keyToC]);
		}
		return $result;
	}
	static function getFlipToc() {
		return self::$toc;
	}
	static function getArticle($id, $params) {
		// Get an instance of the generic articles model
		$model     = new ArticleModel(array('ignore_request' => true));
        if ($model) {
		// Set application parameters in model
		$app       = Factory::getApplication();
		$appParams = $app->getParams();
		$model->setState('params', $appParams);

		// Set the filters based on the module params
		$model->setState('list.start', 0);
		$model->setState('list.limit', 1);
		$model->setState('filter.published', 1);
		$model->setState('filter.featured', $params->get('show_front', 1) == 1 ? 'show' : 'hide');

		// Access filter
		$access = ComponentHelper::getParams('com_content')->get('show_noauth');
		$authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
		$model->setState('filter.access', $access);

		// Category filter
		$model->setState('filter.category_id', $params->get('catid', array()));

		// Date filter
		$date_filtering = $params->get('date_filtering', 'off');

		if ($date_filtering !== 'off')
		{
			$model->setState('filter.date_filtering', $date_filtering);
			$model->setState('filter.date_field', $params->get('date_field', 'a.created'));
			$model->setState('filter.start_date_range', $params->get('start_date_range', '1000-01-01 00:00:00'));
			$model->setState('filter.end_date_range', $params->get('end_date_range', '9999-12-31 23:59:59'));
			$model->setState('filter.relative_date', $params->get('relative_date', 30));
		}
		// Filter by language
		$model->setState('filter.language', $app->getLanguageFilter());
		// Ordering
		$model->setState('list.ordering', 'a.hits');
		$model->setState('list.direction', 'DESC');

		$item = $model->getItem($id);
		
		$show_date_field  = $params->get('choixdate', 'modified');
		$show_date_format = 'Y-m-d H:i:s';
		
		$item->displayDate = '';
		$item->displayDate = HTMLHelper::_('date', $item->$show_date_field, $show_date_format);

		$item->slug    = $item->id . ':' . $item->alias;
		$item->catslug = $item->catid . ':' . $item->category_alias;
		if ($access || in_array($item->access, $authorised))
		{
			// We know that user has the privilege to view the article
			$item->link = Route::_(RouteHelper::getArticleRoute($item->slug, $item->catid, $item->language));
		}
		else
		{
			$item->link = Route::_('index.php?option=com_users&view=login');
		}
		$arr = $item;
        }
        else {
        	$arr = false;
        }
		return $arr;
	}
	
	static function getCategory($id,$params,$ordering) {
		// Get an instance of the generic articles model
		$articles     = new ArticlesModel(array('ignore_request' => true));
		if ($articles) {
		// Set application parameters in model
		$app       = Factory::getApplication();
		$appParams = $app->getParams();
		$articles->setState('params', $appParams);

		// Set the filters based on the module params
		$articles->setState('list.start', 0);
		$articles->setState('list.limit', 0);
		$articles->setState('filter.published', 1);
		// Access filter
		$access     = ComponentHelper::getParams('com_content')->get('show_noauth');
		$authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
		$articles->setState('filter.access', $access);
		$catids = $id;
		$articles->setState('filter.category_id', $catids);		
		$articles->setState('filter.category_id.include', (bool) $params->get('category_filtering_type', 1));
		// Ordering
		$articles->setState('list.ordering', 'a.'.$ordering);
		$articles->setState('list.direction', 'DESC');
		$articles->setState('filter.featured', 'show');
		$articles->setState('filter.author_id',"");
		$articles->setState('filter.author_id.include', 1);
		$excluded_articles = '';
		$date_filtering = 'off';
		// Filter by language
		$articles->setState('filter.language', $app->getLanguageFilter());
		$items = $articles->getItems();
		return $items;
		}
	}
	static function render_article($article,$params,$multi) {
	    $libcreated=Text::_('CG_LIBCREATED'); 
		$libupdated=Text::_('CG_LIBUPDATED');
		$libdateformat  =Text::_('CG_DATEFORMAT');
	    $modulefield = ''.URI::base(true).'/modules/mod_cg_flip';
		$perso = (array)$params->get('perso');
	    // intro text 
		$intro_tmp = new \stdClass;
		$intro_tmp->text = $article->introtext;
		$intro_tmp->params = $params;
		Factory::getApplication()->triggerEvent('onContentPrepare', array ('com_content.article', &$intro_tmp, &$intro_tmp->params, 0));
		$text_tmp = new \stdClass;
		$text_tmp->text ="";
		if (in_array('{text}',$perso)) { // full text requis
			$text_tmp = new \stdClass;
			if (strlen($article->fulltext) == 0) {
				$text_tmp = $intro_tmp;
			}else {
				$text_tmp->text = $article->fulltext;
				$text_tmp->params = $params;
				Factory::getApplication()->triggerEvent('onContentPrepare', array ('com_content.article', &$text_tmp, &$text_tmp->params, 0));
			}
		}
		// personnalisation
		$title = '<a href="'.$article->link.'">'.$article->title.'</a>';
		$rating = '';
		for ($i = 1; $i <= $article->rating; $i++) {
			$rating .= '<img src='.$modulefield.'/images/icon.png />';
		}
		$phocacount = '?'; /// ModIsotopeHelper::getArticlePhocaCount($item->fulltext);
		$choixdate = $params->get('choixdate', 'modified');
		$libdate = $choixdate == "modified" ? $libupdated : $libcreated;
		$perso = $params->get('perso');
		$perso = self::checkNullFields($perso,$article,$phocacount); // suppress null field if required
		$arr_css= array("{title}"=>$title, "{cat}"=>$article->category_title,"{date}"=>$libdate.HTMLHelper::_('date', $article->displayDate, $libdateformat), "{visit}" =>$article->hits, "{intro}" => $intro_tmp->text,"{text}" => $text_tmp->text,"{stars}"=>$rating,"{rating}"=>$article->rating,"{ratingcnt}"=>$article->rating_count,"{count}"=>$phocacount);
		foreach ($arr_css as $key_c => $val_c) {
			$perso = str_replace($key_c,$val_c,$perso);
		}
		if ($params->get('readmore','false') =='true') {
			$perso .= '<p class="isotope-readmore">';
			$perso .= '<a class="isotope-readmore-title" href="'.$article->link.'">';
			$perso .= Text::_('CG_READMORE');
			$perso .='</a></p>';
		}
		if ($multi) return $perso ; // multi-page
		return '<div class="cg_flip_article"><div class="cg_page_article"><div class="cg_un_article">'.$perso.'</div></div></div>';
	}
	// look for {notnull}
	static function checkNullFields($perso,$item,$phocacount) {
	    $regexopen = '/\{(?:notnull)\b(.*)\}/siU';
	    if (!preg_match($regexopen,$perso)) {
			return $perso; // no update
		}
		while (preg_match($regexopen, $perso, $matches, PREG_OFFSET_CAPTURE)) {
		    $replace_deb = $matches[0][1];
		    $replace_len = strlen($matches[0][0]);
		    $regexclose = '/\{\/notnull\}/siU';
		    preg_match($regexclose, $perso, $matchesclose, PREG_OFFSET_CAPTURE);
		    $content_deb = $replace_deb + $replace_len;
		    $content_len = $matchesclose[0][1] - $content_deb;
		    $content = substr($perso, $content_deb, $content_len);
		    $replace_len += $content_len + strlen($matchesclose[0][0]);
		    if ((strpos($content,'{rating}') !== false) && ($item->rating == "0" ))  {
		        $content = "";
		    }
		    if ((strpos($content,'{ratingcnt}') !== false) && ($item->rating_count == "0" ))  {
		           $content = "";
		    }
		    if ((strpos($content,'{count}') !== false) && ($phocacount == '?')) {
		            $content = "";
		   }
		    $perso = substr($perso,0,$replace_deb).$content.substr($perso,$replace_deb + $replace_len);
		}
		return $perso;
	}
// 1.1.3 link to a page
	static function linktopage($moduleid, $text) {
		$regex = '/\{(?:page)\b(.*)\}/siU';
		if (!preg_match_all($regex,$text, $matches, PREG_OFFSET_CAPTURE)) {
			return $text; // no update
		}
		foreach ($matches[0] as $key=>$untext) {
		    $output = $matches[1][$key][0];
		    $exp = explode(":",$output);
		    $page = trim($exp[0]);
		    $letext = $exp[1];
		    $output = str_replace(':','>',$output);
		    $output= "<a id='go_page' data-page='".$page."' >".$letext."........".$page."</a>";
		    $text = str_replace($matches[0][$key][0],$output,$text);
		}
		return $text;
	}
// 1.1.4 : look for h2 as title of a page
	static function lookfortitle($text) {
	    $regex = '`(<h2([^>]*)>)(.+?)(?=</h2>)`';
		if (!preg_match($regex,$text, $matches)) {
			return false; // no update
		}
		return $matches[3];
	}
//-------------------------------------------- 1.2.0 : Events	----------------------------------
	static function getFlipEvents($params)	{
		$ext = $params->get('events_ext');
		$limit = $params->get('nb_events',1);
		$cat = $params->get('cat_events','');
		$res = array();
		if ($ext == "JEvents") {
			$cat = $params->get('cat_events','');
			$res = self::getJEvent($params,$cat,$limit);
		} elseif ($ext == "DPCalendar") {
			$cat = $params->get('cat_events_dp','');
			$res = self::getDPCalendar($params,$cat,$limit);
		}
		return $res;	
	}
	static function getJEvent($params,$cat,$limit) {
		$db = Factory::getDbo();
		$db->setQuery("SELECT enabled FROM #__extensions WHERE element = 'com_jevents'");
		$is_enabled = $db->loadResult();        
		if ($is_enabled != 1) { 
			$res[] = '<div class="cg_flip_article"><div class="cg_page_event" style="background-color:'.$params->get('event_bg','lightblue').'"><div class="cg_un_event">'.Text::_('CG_INSTALL_JEVENTS').'</div></div></div>';
			return $res;
		} 
	    $lang= Factory::getLanguage();
	    $locale = $lang->getLocale();
		setlocale(LC_TIME, $locale[0],$locale[5]);
        $res = array();
		$db = Factory::getDbo();
		$query = $db->getQuery(true);	
		$query->select("GROUP_CONCAT(vrepet.rp_id) as id,GROUP_CONCAT(vrepet.startrepeat) as dtstart, GROUP_CONCAT(vrepet.endrepeat) as dtend,detail.description, detail.summary")
		->from("#__jevents_vevdetail detail ")
		->innerJoin("#__jevents_repetition vrepet ON detail.evdet_id = vrepet.eventdetail_id ")
		->innerJoin("#__jevents_vevent vevent ON vevent.ev_id = vrepet.eventid ")
		->innerJoin("#__categories cat ON vevent.catid = cat.id")
		->where("cat.extension = 'com_jevents' AND cat.id = '".$cat."' AND vevent.state > 0 AND vrepet.startrepeat > now() AND vrepet.endrepeat > now() AND detail.state = 1")
		->order("detail.dtstart ASC")
		->group("vevent.ev_id")
		->setLimit($limit);
		$db->setQuery($query);
		$resdb = $db->loadAssocList();
        if (count($resdb) == 0) {
			$res[] = '<div class="cg_flip_article"><div class="cg_page_event" style="background-color:'.$params->get('event_bg','lightblue').'"><div class="cg_un_event">'.Text::_('CG_NO_EVENT').'</div></div></div>';
			return $res;
        }
        foreach($resdb as $unevt) {
		  $rac = 'index.php/'.$params->get('menupath','').'/detailevenement/';
		  $multi = false;
		  $id = $unevt['id'];
		  $dtstart = $unevt['dtstart'];
		  $dtend = $unevt['dtend'];
		  if (strpos($unevt['id'],',') !== false) $multi = true;
		  
		  if ($multi) {
		      $ids = explode(',',$unevt['id']);
		      $id = $ids[0];
		      $dates = explode(',',$unevt['dtstart']);
		      $dtstart = $dates[0];
		      $dates = explode(',',$unevt['dtend']);
		      $dtend = $dates[count($dates) - 1];
		  }
		  $rac .=	$id; 	
		  if ((strlen($unevt['description']) == 0) || ($params->get('typeaff','title') == "title") ) {
		      if ($multi) {
		          $desc =  htmlentities($unevt['summary'],ENT_NOQUOTES,'utf-8').'<br/>';
		          $desc .= Text::_('CG_FROM').' '.strftime('%A',strtotime($dtstart)).' '.date(Text::_('CG_DATEFORMAT_EVENTS'),strtotime($dtstart));
		          $desc .= ' '.Text::_('CG_TO').' '.strftime('%A',strtotime($dtend)).' '.date(Text::_('CG_DATEFORMAT_EVENTS'),strtotime($dtend));
		      } else {
			     $desc = htmlentities($unevt['summary'],ENT_NOQUOTES,'utf-8').'<br/>'.strftime('%A',strtotime($dtstart)).' '.date(Text::_('CG_DATEFORMAT_EVENTS'),strtotime($dtstart));
			     if (!(date('H\hi',strtotime($dtstart)) == '00h00' )) $desc .= ' '.Text::_('CG_START').' '.date('H\hi',strtotime($dtstart));
		      }
		  } else {
		// on limite la taille pour le post-it: 1ere paragraphe
			$desc = substr($unevt['description'],0,strpos($unevt['description'],'</p>'));
			$desc = self::cleantext($desc);
			$desc = htmlentities($desc,ENT_NOQUOTES,'utf-8');
		  }
		  if ($params->get('liblink_evt','false') =='true') { // 1.2.2 : title as link
			  $desc= '<a class="isotope-readmore-title" href="'.$rac.'">'.$desc.'</a>';
		  }
		  $perso = "";
		  if ($params->get('readmore_evt','false') =='true') {
			$perso .= '<br/><span class="isotope-readmore">';
			$perso .= '<a class="isotope-readmore-title" href="'.$rac.'">';
			$perso .= Text::_('CG_READMORE');
			$perso .='</a></span>';
		}
 		  $desc .= $perso;
 		  $res[] ='<div class="cg_flip_article"><div class="cg_page_event" style="background-color:'.$params->get('event_bg','lightblue').'"><div class="cg_un_event">'.$desc.'</div></div></div>';;
         }
		return $res;
	}
	static function getDPCalendar($params,$cat,$limit) {
		$db = Factory::getDbo();
		$db->setQuery("SELECT enabled FROM #__extensions WHERE name = 'DPCalendar'");
		$is_enabled = $db->loadResult();        
		if ($is_enabled != 1) { 
			$res[] = '<div class="cg_flip_article"><div class="cg_page_event" style="background-color:'.$params->get('event_bg','lightblue').'"><div class="cg_un_event">'.Text::_('CG_INSTALL_DPCALENDAR').'</div></div></div>';
			return $res;
		} 
	    $lang= Factory::getLanguage();
	    $locale = $lang->getLocale();
	    setlocale(LC_TIME, $locale[0],$locale[5]);
	    $res = array();
		$db = Factory::getDbo();
		$query = $db->getQuery(true);	
		$query->select("detail.id,detail.start_date, detail.end_date,detail.title, detail.description, detail.all_day")
		->from("#__dpcalendar_events detail ")
		->innerJoin("#__categories cat ON detail.catid = cat.id")
		->where("cat.extension = 'com_dpcalendar' AND cat.id = '".$cat."' AND detail.start_date > now() AND detail.end_date > now() AND detail.state = 1")
		->order("detail.start_date ASC")
		->setLimit($limit);
		$db->setQuery($query);
		$resdb = $db->loadAssocList();
        if (count($resdb) == 0) {
			$res[] = '<div class="cg_flip_article"><div class="cg_page_event" style="background-color:'.$params->get('event_bg','lightblue').'"><div class="cg_un_event">'.Text::_('CG_NO_EVENT').'</div></div></div>';
			return $res;
        }
        foreach($resdb as $unevt) {
			$rac = 'index.php/'.$params->get('menupathdp').'/'.$unevt['id'];	
			if ((strlen($unevt['description']) == 0) || ($params->get('typeaff') == "title") ) {
			// pas de description: on en génère une à partir du titre
			    $desc = htmlentities($unevt['title'],ENT_NOQUOTES,'utf-8').'<br/>'.strftime('%A',strtotime ($unevt['start_date'])).' '.date('d/m',strtotime($unevt['start_date']));
			if ($unevt['all_day'] == '0') $desc .= ' '.Text::_('CG_START').' '.date('H\hi',strtotime($unevt['start_date']));
			} else {
			// on limite la taille pour le post-it: 1ere paragraphe
				$desc = substr($unevt['description'],0,strpos($unevt['description'],'</p>'));
				$desc = self::cleantext($desc);
				$desc = htmlentities($desc,ENT_NOQUOTES,'utf-8');
			}
			if ($params->get('liblink_evt','false') =='true') { // 1.2.2 : title as link
				$desc= '<a class="isotope-readmore-title" href="'.$rac.'">'.$desc.'</a>';
			}
			$perso = "";
			if ($params->get('readmore_evt','false') =='true') {
				$perso .= '<br/><span class="isotope-readmore">';
				$perso .= '<a class="isotope-readmore-title" href="'.$rac.'">';
				$perso .= Text::_('CG_READMORE');
				$perso .='</a></span>';
			}
			$desc .= $perso;
			$res[] ='<div class="cg_flip_article"><div class="cg_page_event" style="background-color:'.$params->get('event_bg','lightblue').'"><div class="cg_un_event">'.$desc.'</div></div></div>';;
         }
		return $res;
	}	
	
	static function cleantext($text)
	{
		$text = str_replace('<p>', ' ', $text);
		$text = str_replace('</p>', ' ', $text);
		$text = strip_tags($text, '<br>');
		$text = trim($text);
		return $text;
	}
	public static function buildButtons($module,$toc,$nbpages,$typemenu,$menu_pos) {
		$title = $toc=='' ? Text::_('CG_AIDE'): Text::_('CG_TOC');
		$cls = 'cg_flip_pos_'.$menu_pos;
		if ($typemenu == 'full') {
		$buttons = '
		<div class="cg_flip_menu_base '.$cls.' fg-row" >
			<div class="first fg-c3 fg-cs1" >
				<div class="fg-row">
					<a id="cg_onepage" class="fg-c6 fg-cs6" rel="onepage">
					<i class="cg-icons ison-doc"  id="cg_page" title="'.Text::_('CG_UNE_PAGE').'"></i>		
					</a>
					<a class="cg_flip_menu fg-c6 fg-cs6 hidden-phone" data-id="'.$module->id.'" rel="menu" >
					<i class="ison-cg-menu cg-icons" title="'.$title.'"></i>		
					</a>
				</div>';
		if ($toc == '') {
			$buttons .='<div id="cg_aide" class="cg_aide hidden-phone" data-id="'.$module->id.'">
						<p><b>'.Text::_('CG_SHORT').'</b></p>
						<p class="cg_align_left"><i class="ison-cg-star"></i>&nbsp;1 page/2 pages<br/>
						<i class="ison-cg-right-big"></i>&nbsp;'.Text::_('CG_NEXT').'<br/>
						<i class="ison-cg-left-big"></i>&nbsp;'.Text::_('CG_PREV').'</p>
						</div>';
	    }else {
			$buttons .= '<div id="cg_aide" data-id="'.$module->id.'" class="hidden-phone cg_aide cg_margin_left">
						'.$toc.'</div>';
	    }
		$buttons .= '</div>
					<div class="prev fg-c3 fg-cs3" >
					<div class="fg-row">
						<a class="cg_first fg-c6 fg-cs6" data-id="'.$module->id.'" title="'.Text::_('CG_FIRST_PAGE').'">
						<i class="cg-icons ison-to-start-alt" id="cg_first" style="float:right"></i>		
						</a>
						<a class="cg_prev fg-c6 fg-cs6" data-id="'.$module->id.'" title="'.Text::_('CG_PREV_PAGE').'">
						<i class="cg-icons ison-to-start"  id="cg_prev"></i>		
						</a>
					</div>
					</div>
					<div id="cg_count" class="fg-c3 fg-cs4">
						<span class="cg_count_class">
							<span  id="cg_flip_page" class="cg_flip_page">1</span>&nbsp;/&nbsp;'.$nbpages.'</span>
					</div>
					<div class="next fg-c3 fg-cs3" >
						<div class="fg-row">
							<a class="cg_next fg-c6 fg-cs6" data-id="'.$module->id.'" class="cg_margin_right">
							<i class="cg-icons ison-to-end" id="cg_next" title="'.Text::_('CG_NEXT_PAGE').'"></i>		
							</a>
							<a class="cg_last fg-c6 fg-cs6" data-id="'.$module->id.'" >
							<i class="cg-icons ison-to-end-alt" id="cg_last" title="'.Text::_('CG_LAST_PAGE').'"></i>		
							</a>
						</div>
					</div>
					</div>';
		} else { // 2 buttons menu
			$buttons = '
			<div class="cg_flip_menu_base cg_flip_menu_mini '.$cls.' fg-row" >
				<a class="cg_prev fg-c6 fg-cs6" data-id="'.$module->id.'">
				<div class="previous-button cg_right"  id="cg_prev" alt="Next" title="'.Text::_('CG_PREV_PAGE').'"></div>		
				</a>
				<a class="cg_next fg-c6 fg-cs6" data-id="'.$module->id.'">
				<div class="next-button cg_left" id="cg_next" title="'.Text::_('CG_NEXT_PAGE').'"></div>		
				</a>
			</div>
			';
		}
		return $buttons;
	}
//------------------------------------------------ AJAX Request --------------------------------------	
	public static function getAjax() {
        $input = Factory::getApplication()->input;
		$id = $input->get('id');
		$module = self::getModuleById($id);
		$params = new Registry($module->params);  		
        $output = '';
		if ($input->get('data') == "param") {
			return self::getParams($id,$params);
		}
		return false;
	}
// Get Module per ID
	private static function getModuleById($id) {
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('m.id, m.title, m.module, m.position, m.content, m.showtitle, m.params')
			->from('#__modules AS m')
			->where('m.id = '.(int)$id);
		$db->setQuery($query);
		return $db->loadObject();
	}
	private static function getParams($id,$params) {
		$files = array();
		$type = $params->get('cg_type', 'dir');
		$optimize = $params->get('optimize', '0');
		if ($type == "dir") {
			$dir =  $params->get('dir', '');
			if ($optimize == '1') $dir .= '/th';
			$files = glob('images/'.$dir.'/*.{jpg,png}',GLOB_BRACE); 
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
			$nbpages = 0;
		}
		if ($params->get('ratiotype', '0') == '0') {
			$ratio = $params->get('ratio', '1.41');
		} else {
			$ratio = $params->get('ratio_perso', '1.0');
			$ratio = str_replace(',','.',$ratio);
		}	
		$files_java = '{"';
		$ix=0;
		foreach ($files as $afile) {
		      if ($files_java != '{"') $files_java .= '","';
		      $files_java .= $ix.'":"'.$afile;
			  $ix +=1;
		}
		if ($files_java != '{"') {
			$files_java .= '"}';
		} else {
			$files_java = '""';
		}
		$ret = '{"id" :"'.$id.'","base":"'.URI::base(true).'","type":"'.$type.'","ratio":"'.$ratio.'"';
		$ret .= ',"speffect":"'.$params->get('sp-effect','fadeIn').'","nbpages":"'.$nbpages.'","onepage":"'.Text::_('CG_UNE_PAGE').'"';
		$ret .= ',"twopages":"'.Text::_('CG_DEUX_PAGE').'","init":"'.$params->get('init','double').'"';
		$ret .= ',"init_phone":"'.$params->get('init_phone','single').'","files":'.$files_java.'}';
		return $ret;
	}
}
