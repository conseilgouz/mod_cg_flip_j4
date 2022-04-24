/**
 * @package CG Flip Module
 * @version 2.0.4
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2022 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
 */
var $timestp = 0;
jQuery(document).ready(function($) {
    $(".cg_flip_main").each(function() {
		var $this = $(this);
		var myid = $this.attr("data");
		if (typeof Joomla === 'undefined' || typeof Joomla.getOptions === 'undefined') {
			var options;
		} else {
			var options = Joomla.getOptions('cg_flip_'+myid);
		}
		if (typeof options === 'undefined' ) { // cache Joomla problem
			request = {
				'option' : 'com_ajax',
				'module' : 'cg_flip',
				'data'   : 'param',
				'id'     : myid,
				'format' : 'raw'
				};
				$.ajax({
					type   : 'POST',
					data   : request,
					success: function (response) {
						options = JSON.parse(response);
						go_flip($,myid,options,true);
					}
				});
		};
		if (typeof options !== 'undefined' ) {
			go_flip($,myid,options,false);
		}
	});
})
function go_flip($,myid,options,ajax) {
	var me = ".cg_flip_"+myid;
	var ratio = options.ratio;
	var divWidth = $(me + ' #magazine-viewport').outerWidth(true);
	var isPhone = (divWidth < 768);
	var turnHeight = (divWidth * ratio) / 2;
	if ((options.type == "dir") || (options.type == "files")) { //images
	    if (ajax) {  // from AJAX request
			var $pagesArray = [],key;
			for (key in options.files) {
				$pagesArray.push(options.files[key]);
			}
		} else {
			var $pagesArray = options.files;
		}
	} else { // articles or events
		var $pagesArray = [];
		$(me + ' .cg_flip_article').each(function() {
			$pagesArray.push($(this).html());
		});	
		if (ajax) { // nombre de pages non encore calculé....
			options.nbpages = $pagesArray.length;
		}
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
			},
			missing: function (event, pages) {
				if ((options.type == "dir") || (options.type == "files")) { 
					for (var i = 0; i < pages.length; i++)
						addPage(pages[i], $(this), options.base,$pagesArray[pages[i] - 1]);
				} else {
					for (var i = 0; i < $pagesArray.length; i++)
						$(this).turn("addPage", $pagesArray[i],i + 1);
				} 
				}
			}
	});
	$(window).keydown(function(e){ // Using arrow keys to turn the page
		var beg = 36, prev_2 = 33, previous = 37, next_2= 34, next = 39, mult = 106, mult_alpha = 220, end = 35;
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
					$(me + ' .magazine').turn("size", $turn_options.width , $turn_options.height * 2);
					$(me + ' #cg_page').removeClass('ison-doc').addClass('ison-book-open');	
					$(me + " #cg_page").attr({"title":options.twopages});;
				} else {
					$(me + " #cg_zoom_in").removeClass("cg_hide"); // show zoom again
					$(me + ' .magazine').turn("display", "double");
					$turn_options = $(me + ' .magazine').turn('options');
					$(me + ' .magazine').turn("size", $turn_options.width, $turn_options.height);
					$(me + ' #cg_page').removeClass('ison-book-open').addClass('ison-doc');	
					$(me + " #cg_page").attr({"title":options.onepage});
					$(me + ' .magazine').css('height:'+ $turn_options.height );
					$(me + ' .magazine-viewport').css('height:'+ $turn_options.height );
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
			$(me + ' .magazine-viewport').addClass("double");
			$(me + ' #cg_page').removeClass('ison-doc').addClass('ison-book-open');	
			$(me + " #cg_page").attr({"title":options.twopages});;
		} else {
			$(me + ' .magazine').turn("display", "double");
			$turn_options = $(me + ' .magazine').turn('options');
			$(me + ' .magazine').turn("size", $turn_options.width, $turn_options.height);
			$(me + ' #cg_page').removeClass('ison-book-open').addClass('ison-doc');	
			$(me + " #cg_page").attr({"title":options.onepage});
			$(me + ' .magazine-viewport').removeClass("double");
			$(me + ' .magazine').css('height:'+ $turn_options.height );
			$(me + ' .magazine-viewport').css('height:'+ $turn_options.height );
		}
	});
	$(me).resize(function() {
		resizeViewport(me);
	}).bind('orientationchange', function() {
		resizeViewport(me);
	});
	if ( ( options.init == 'single') || (isPhone && (options.init_phone == 'single')) ) {
		$(me + ' .magazine').addClass('animated');
		$(me+ ' .magazine').turn("display", "single");
		$turn_options = $(me + ' .magazine').turn('options');
		$(me + ' .magazine').turn("size", $turn_options.width , $turn_options.height * 2);
		$(me + ' .magazine-viewport').addClass("double");
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
