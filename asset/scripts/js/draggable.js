function scrollTop() {
	return document.scrollTop || document.documentElement.scrollTop || document.body.scrollTop;
}
function winHeight() {
	return window.innerHeight || document.documentElement.clientHeight;
}
function scrollTopBtn($) {
	//the scroll to top button
	var scroll_btn = $('.btn-scroll-up');
	if(scroll_btn.length > 0) {
		var is_visible = false;
		$(window).on('scroll.scroll_btn', function() {
			var scroll = scrollTop();
			var h = winHeight();
			var body_sH = document.body.scrollHeight;
			if(scroll > parseInt(h / 4) || (scroll > 0 && body_sH >= h && h + scroll >= body_sH - 1)) {//|| for smaller pages, when reached end of page
				if(!is_visible) {
					scroll_btn.addClass('display');
					is_visible = true;
				}
			} else {
				if(is_visible) {
					scroll_btn.removeClass('display');
					is_visible = false;
				}
			}
		}).triggerHandler('scroll.scroll_btn');

		scroll_btn.on('click', function(){
			var duration = Math.min(500, Math.max(100, parseInt(scrollTop() / 3)));
			$('html,body').animate({scrollTop: 0}, duration);
			return false;
		});
	}
}
jQuery(function($) {
    $('.draggablePanelList').sortable({
        // Only make the .panel-heading child elements support dragging.
        // Omit this to make then entire <li>...</li> draggable.
        handle: '.panel-heading', 
        connectWith: ".draggablePanelList"
    });
    scrollTopBtn($);
    //$('.dropdown-submenu > a').submenupicker();
});