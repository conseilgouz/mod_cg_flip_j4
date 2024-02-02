/**
 * @package CG Flip Module
 * @version 2.4.0
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/
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
		if (jQuery(this).data('zoom') == "1") {
			jQuery(this).parent().addClass('zoom');
			jQuery(this).parent().zoom({magnify: jQuery(this).data('magnify'), on:'grab' });
		}
	});
	img.attr('src', dir + '/' + file);
	img.data('zoom',zoom);
	img.data('magnify',magnify);
}
// http://code.google.com/p/chromium/issues/detail?id=128488
function isChrome() {
	return navigator.userAgent.indexOf('Chrome')!=-1;
}
function resizeViewport(me) {
	var width = jQuery(window).width(),
		height = jQuery(window).height(),
		options = jQuery(me + ' .magazine').turn('options');
	jQuery(me + ' .magazine').removeClass('animated');
	jQuery(me + ' .magazine-viewport').css({
		width: width,
		height: height
	});
	if (jQuery(me + ' .magazine').turn('zoom')==1) {
		var bound = calculateBound({
			width: options.width,
			height: options.height,
			boundWidth: Math.min(options.width, width),
			boundHeight: Math.min(options.height, height)
		});
		if (bound.width%2!==0)
			bound.width-=1;
		if (bound.width!=jQuery(me + ' .magazine').width() || bound.height!=jQuery(me +  '.magazine').height()) {
			jQuery(me + ' .magazine').turn('size', bound.width, bound.height);
			if (jQuery(me + ' .magazine').turn('page')==1)
				jQuery(me + ' .magazine').turn('peel', 'br');
			jQuery(me + ' .next-button').css({height: bound.height, backgroundPosition: '-38px '+(bound.height/2-32/2)+'px'});
			jQuery(me + ' .previous-button').css({height: bound.height, backgroundPosition: '-4px '+(bound.height/2-32/2)+'px'});
		}
		jQuery(me + ' .magazine').css({top: -bound.height/2, left: -bound.width/2});
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

// Calculate the width and height of a square within another square

function calculateBound(d) {
	
	var bound = {width: d.width, height: d.height};

	if (bound.width>d.boundWidth || bound.height>d.boundHeight) {
		
		var rel = bound.width/bound.height;

		if (d.boundWidth/rel>d.boundHeight && d.boundHeight*rel<=d.boundWidth) {
			
			bound.width = Math.round(d.boundHeight*rel);
			bound.height = d.boundHeight;

		} else {
			
			bound.width = d.boundWidth;
			bound.height = Math.round(d.boundWidth/rel);
		
		}
	}
		
	return bound;
}


