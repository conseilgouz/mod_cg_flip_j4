/**
 * @package CG Flip Module
 * @version 2.4.8
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
 */
var $timestp = 0;
jQuery(document).ready(function($) {
    $(".cg_flip_main").each(function() {
		var $flipid = $(this).attr("data");
		if (typeof Joomla === 'undefined' || typeof Joomla.getOptions === 'undefined') {
			var flipoptions;
		} else {
			var flipoptions = Joomla.getOptions('cg_flip_'+$flipid);
		}
		if (typeof flipoptions !== 'undefined' ) {
			go_flip($,$flipid,flipoptions,false);
		}
	});
})
function go_flip($,myid,options,ajax) {
	var me = ".cg_flip_"+myid;
	var ratio = options.ratio;
	var divWidth = $(me + ' #magazine-viewport').outerWidth(true);
	var isPhone = (options.device == 'phone');
	var turnHeight = (divWidth * ratio) / 2;
	// if (isPhone && (options.zoom == "3")) options.zoom = "0"; // phone : disable scroll zoom
	if ((options.type == "dir") || (options.type == "files")) { //images
		var $pagesArray = options.files;
	} else { // articles or events
		var $pagesArray = [];
		$(me + ' .cg_flip_article').each(function() {
			$pagesArray.push($(this).html());
		});	
	} 
	$(me + ' .cg_flip_menu').click(function(){ // aide
		if ($(me + ' #cg_aide').hasClass('cg_show')) {
			$(me + ' #cg_aide').removeClass("cg_show");	
		} else {
			$(me + ' #cg_aide').addClass("cg_show");	
		}
		return false;
	});
	$(me + ' #cg_aide').click(function(){
		$(me + ' #cg_aide').removeClass("cg_show");	
	});
	$(me + ' .magazine').turn({
			elevation: 50,
			acceleration: !isChrome(),
			gradients: true,
			autoCenter: true,
			pages: parseInt(options.nbpages),
			display: 'double',
			height: turnHeight,
			when: {
			turning: function(event, page, view) {
				var book = $(this),
				currentPage = book.turn('page'),
				pages = book.turn('pages');
				$(me + ' #cg_flip_page').text(page);
			},
			turned: function(event, page, view) {
				$(this).turn('center');
				if (page==1) { 
					$(this).turn('peel', 'br');
				}
				$(me + ' .go_page').click(function() { // reload click event
					$page = $(this).attr('data-page');
					me = ".cg_flip_"+ $(this).attr('data-id');
					$( me + ' .magazine').turn('page',$page);
				});
				if (options.clickpage == 'false') return; 
				if ($(me + ' #magazine-viewport').outerWidth(true) > 767) {
				// add one/2pages onclick event on new pages 
					view.forEach(function($page) {	// one page / two-pages on click
						$(me + ' .page.p'+$page).off('click');
						$(me + ' .page.p'+$page).click(function(e) { 
							if ($(me + ' .magazine').turn("zoom")> 1) return; // ignore click 
							if ($(me + ' #cg_page').hasClass("ison-doc")) {
								$page = this.parentNode.parentNode.getAttribute('page');
								$(me + " #cg_zoom_in").addClass("cg_hide"); // hide zoom
								$(me + ' .magazine').turn("display", "single");
								$(me + ' .magazine-viewport').addClass("single");								
								$turn_options = $(me + ' .magazine').turn('options');
								$(me + ' .magazine').turn("size", $turn_options.width , $turn_options.height * 2);
								$(me + ' #cg_page').removeClass('ison-doc').addClass('ison-book-open');	
								$(me + " #cg_page").attr({"title":options.twopages});;
								$( me + ' .magazine').turn('page',$page).turn('stop');
							} else {
								$(me + " #cg_zoom_in").removeClass("cg_hide"); // show zoom again
								$(me + ' .magazine').turn("display", "double");
								$turn_options = $(me + ' .magazine').turn('options');
								$(me + ' .magazine').turn("size", $turn_options.width, $turn_options.height);
								$(me + ' #cg_page').removeClass('ison-book-open').addClass('ison-doc');	
								$(me + " #cg_page").attr({"title":options.onepage});
								$(me + ' .magazine').css('height:'+ $turn_options.height );
								$(me + ' .magazine-viewport').removeClass("single");
								$(me + ' .magazine-viewport').css('height:'+ $turn_options.height );
							}
						});
					});
				}
			},
			missing: function (event, pages) {
				if ((options.type == "dir") || (options.type == "files")) { 
					for (var i = 0; i < pages.length; i++) {
						let zoom = options.zoom;
						let magnify = options.magnify;
						if (isPhone) {
							zoom = options.mobilezoom;
							magnify = options.mobilemagnify;
						}
						addPage(pages[i], $(this), options.base,$pagesArray[pages[i] - 1],zoom,magnify);
					}
				} else {
					for (var i = 0; i < $pagesArray.length; i++)
						$(this).turn("addPage", $pagesArray[i],i + 1);
				} 
			}// missing
		}// when
	}); // turn
	$(window).keydown(function(e){ // Using arrow keys to turn the page
		var beg = 36, prev_2 = 33, previous = 37, next_2= 34, next = 39, mult = 106, mult_alpha = 220, end = 35;
		var z=90;
		switch (e.keyCode) {
			case beg: 
				$(me + ' .magazine').turn('page',1);
				e.preventDefault();
			break;
			case previous:
			case prev_2:
				$(me + ' .magazine').turn('previous');
				e.preventDefault();
			break;
			case next:
			case next_2:
				$(me + ' .magazine').turn('next');
				e.preventDefault();
			break;
			case end:
				$(me +' .magazine').turn('page',$(me + " .magazine").turn("pages"));
				e.preventDefault();
			break;
			case mult:
			case mult_alpha:
				if ($(me + " #cg_onepage").hasClass("cg_hide")) { // bouton caché: on ignore le clavier
					e.preventDefault();
					return true;
				}
				if ($(me + ' #cg_page').hasClass("ison-doc")) {
					$(me + " #cg_zoom_in").addClass("cg_hide"); // hide zoom
					$(me + ' .magazine').turn("display", "single");
					$turn_options = $(me + ' .magazine').turn('options');
					$(me + ' .magazine-viewport').addClass("single");					
					$(me + ' .magazine').turn("size", $turn_options.width , $turn_options.height * 2);
					$(me + ' #cg_page').removeClass('ison-doc').addClass('ison-book-open');	
					$(me + " #cg_page").attr({"title":options.twopages});
					if (options.zoom == "3") {// wheelzoom
						imgs = document.querySelectorAll('img.zoom');
						imgs.forEach (function (img) {
							img.dispatchEvent(new Event("wheelzoom.destroy"));
						});
						wheelzoom(document.querySelectorAll('img.zoom'));
					}
				} else {
					$(me + " #cg_zoom_in").removeClass("cg_hide"); // show zoom again
					$(me + ' .magazine').turn("display", "double");
					$turn_options = $(me + ' .magazine').turn('options');
					$(me + ' .magazine').turn("size", $turn_options.width, $turn_options.height);
					$(me + ' #cg_page').removeClass('ison-book-open').addClass('ison-doc');	
					$(me + " #cg_page").attr({"title":options.onepage});
					$(me + ' .magazine').css('height:'+ $turn_options.height );
					$(me + ' .magazine-viewport').css('height:'+ $turn_options.height );
					$(me + ' .magazine-viewport').removeClass("single");
					if (options.zoom == "3") {// wheelzoom
						imgs = document.querySelectorAll('img.zoom');
						imgs.forEach (function (img) {
							img.dispatchEvent(new Event("wheelzoom.destroy"));
						});
						wheelzoom(document.querySelectorAll('img.zoom'));
					}
				}
				e.preventDefault();
				break;
		}
	});
	$(me + ' .go_page').click(function() { 
		$page = $(this).attr('data-page');
		me = ".cg_flip_"+ $(this).attr('data-id');
		$( me + ' .magazine').turn('page',$page);
	});

	$(me+ ' .cg_first').click(function() {
		me = ".cg_flip_"+ $(this).attr('data-id');
		$(me + ' .magazine').turn('page',1);
	});
	$(me+ ' .cg_prev').click(function(e) {
		me = ".cg_flip_"+ $(this).attr('data-id');
		$(me +' .magazine').turn('previous');
	});
	$(me+' .cg_next').click(function(e) {
		me = ".cg_flip_"+ $(this).attr('data-id');
		$(me + ' .magazine').turn('next');
	});
	$(me+' .cg_last').click(function() {
		me = ".cg_flip_"+ $(this).attr('data-id');
		$(me +' .magazine').turn('page',$(me + " .magazine").turn("pages"));
	});
	$(me + ' #cg_page').click(function() {
		if ($(me + ' #cg_page').hasClass("ison-doc")) {
			$(me + ' .magazine').turn("display", "single");
			$turn_options = $(me + ' .magazine').turn('options');
			$(me + ' .magazine').turn("size", $turn_options.width , $turn_options.height * 2);
			$(me + ' .magazine-viewport').addClass("single");
			$(me + ' #cg_page').removeClass('ison-doc').addClass('ison-book-open');	
			$(me + " #cg_page").attr({"title":options.twopages});
			if (options.zoom == "3") {// wheelzoom
				imgs = document.querySelectorAll('img.zoom');
				imgs.forEach (function (img) {
					img.dispatchEvent(new Event("wheelzoom.destroy"));
				});
				wheelzoom(document.querySelectorAll('img.zoom'));
			}
		} else {
			$(me + ' .magazine').turn("display", "double");
			$turn_options = $(me + ' .magazine').turn('options');
			$(me + ' .magazine').turn("size", $turn_options.width, $turn_options.height);
			$(me + ' #cg_page').removeClass('ison-book-open').addClass('ison-doc');	
			$(me + " #cg_page").attr({"title":options.onepage});
			$(me + ' .magazine-viewport').removeClass("single");
			$(me + ' .magazine').css('height:'+ $turn_options.height );
			$(me + ' .magazine-viewport').css('height:'+ $turn_options.height );
			if (options.zoom == "3") {// wheelzoom
				imgs = document.querySelectorAll('img.zoom');
				imgs.forEach (function (img) {
					img.dispatchEvent(new Event("wheelzoom.destroy"));
				});
				wheelzoom(document.querySelectorAll('img.zoom'));
			}
		}
	});
	$(window).resize(function() {
		resizeViewport(me,ratio);
	}).bind('orientationchange', function() {
		resizeViewport(me,ratio);
	});
	if ( ( options.init == 'single') || (isPhone && (options.init_phone == 'single')) ) {
		$(me + ' .magazine').addClass('animated');
		$(me+ ' .magazine').turn("display", "single");
		$turn_options = $(me + ' .magazine').turn('options');
		$(me + ' .magazine').turn("size", $turn_options.width , $turn_options.height * 2);
		$(me + ' .magazine-viewport').addClass("single");
		$(me + ' #cg_page').removeClass('ison-doc').addClass('ison-book-open');	
		$(me + " #cg_page").attr({"title":options.twopages});;
	}
	if (options.auto == 'true') { // auto page turn
		var myVar = setInterval(auto_next,parseInt(options.auto_delay));
		$(me).mouseenter(function() {clearInterval(myVar);})
			 .mouseleave(function() {myVar = setInterval(auto_next,parseInt(options.auto_delay))});
 	}
	function auto_next() {
		$max = $(me + " .magazine").turn("pages");
		$curr = $(me + " .magazine").turn('page');
		if ($curr == $max) {
			$(me + ' .magazine').turn('page',1);
		} else {
			$(me + ' .magazine').turn('next');
		}
	}
}
