/**
 * @package CG Flip Module
 * @version 2.4.8
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/
var imgcount = 0;
function addPage(page, book, dir, file,zoom,magnify) {
	var id, pages = book.turn('pages');
	// Create a new element for this page
	var element = jQuery('<div />', {});
	// Add the page to the flipbook
	if (book.turn('addPage', element, page)) {
		// Add the initial HTML
		// It will contain a loader indicator and a gradient
		element.html('<div class="gradient"></div><div class="loader"></div>');
		// Load the page
		loadPage(page, element,dir,file,zoom,magnify);
	}
}
function loadPage(page, pageElement,dir,file,zoom,magnify) {
	var img = jQuery('<img />');
	img.mousedown(function(e) {
		e.preventDefault();
	});
	img.on('load', function(event,zoom,magnify) {
		// Set the size
		jQuery(this).css({width: '100%', height: '100%'});
		jQuery(this).appendTo(pageElement);
		pageElement.find('.loader').remove();
		pageElement.find('.gradient').remove();
		if ((jQuery(this).data('zoom') == "1") || (jQuery(this).data('zoom') == "2") ) { // zoom
			jQuery(this).parent().addClass('zoom');
			url = false;
			if (this.src.indexOf('/th/') > 0){ // 
				url = this.src.replace('/th/','/');
			}
			on = 'grab';
			if (jQuery(this).data('zoom') == "2") on = 'click';
			jQuery(this).parent().zoom({url: url,magnify: jQuery(this).data('magnify'), on:on });
		}
		if (jQuery(this).data('zoom') == "3") { // wheelzoom 
		    if (jQuery(this).hasClass('todo')) {
				wheelzoom(this);  
				jQuery(this).removeClass('todo')
			}
		}
		if (jQuery(this).data('zoom') == "4") { // pinch 
		    if (jQuery(this).hasClass('todo')) {
				new Zoom(this,{rotate:false,minZoom:1,maxZoom:jQuery(this).data('magnify')});
				jQuery(this).removeClass('todo')
			}
		}
	});
	if (((zoom == '3') || (zoom == '4')) && (file.indexOf('/th/') > 0)){ // wheelzoom
		file = file.replace('/th/','/');
	}
	img.attr('src', dir + '/' + file);
	img.data('zoom',zoom);
	img.data('magnify',magnify);
	if ((zoom == "3") || (zoom == "4")) // wheelzoom or pinch 
		img.addClass('zoom todo'); 
}
// http://code.google.com/p/chromium/issues/detail?id=128488
function isChrome() {
	return navigator.userAgent.indexOf('Chrome')!=-1;
}
function resizeViewport(me,ratio) {
	var width = $(me + ' #magazine-viewport').outerWidth(true),
		height = width * ratio;
	if (!jQuery(me+' .magazine-viewport').hasClass('single')) height = height / 2;
	jQuery(me + ' .magazine').removeClass('animated');
	jQuery(me + ' .magazine-viewport').css({
		width: width,
		height: height
	});
	if (jQuery(me + ' .magazine').turn('zoom')==1) {
		if (width%2!==0)
			width-=1;
		if (width!=jQuery(me + ' .magazine').width() || height!=jQuery(me +  '.magazine').height()) {
			jQuery(me + ' .magazine').turn('size', width, height);
			if (jQuery(me + ' .magazine').turn('page')==1)
				jQuery(me + ' .magazine').turn('peel', 'br');
		}
		jQuery(me + ' .magazine').css({top: -height/2, left: -width/2});
	}
	var magazineOffset = jQuery(me + ' .magazine').offset(),
		boundH = height - magazineOffset.top - jQuery(me + ' .magazine').height(),
		marginTop = (boundH - jQuery(' .thumbnails > div').height()) / 2;
	if (magazineOffset.top<jQuery('.made').height())
		jQuery('.made').hide();
	else
		jQuery('.made').show();
	jQuery(me + ' .magazine').addClass('animated');
}

