/*!
 * SmartMenus jQuery Plugin Bootstrap Addon - v0.1.1 - August 25, 2014
 * http://www.smartmenus.org/
 *
 * Copyright 2014 Vasil Dinkov, Vadikom Web Ltd.
 * http://vadikom.com
 *
 * Licensed MIT
 */

(function($) {

	// init ondomready
	$(function() {
		// init all menus
		$('#page-menu').addClass('sm').smartmenus({
			subMenusSubOffsetX: 2,
			subMenusSubOffsetY: -6,
			subIndicatorsPos: 'append',
			subIndicatorsText: '...',
			showOnClick: true,
		}).bind({
			'show.smapi': function(e, menu) {
				var $menu = $(menu),
					$scrollArrows = $menu.dataSM('scroll-arrows'),
					obj = $(this).data('smartmenus');
				if ($scrollArrows) {
					// they inherit border-color from body, so we can use its background-color too
					$scrollArrows.css('background-color', $(document.body).css('background-color'));
				}
				$menu.parent().addClass('open' + (obj.isCollapsible() ? ' collapsible' : ''));
			},
			'hide.smapi': function(e, menu) {
				$(menu).parent().removeClass('open collapsible');
			}
		});
	});
	// fix collapsible menu detection for Bootstrap 3
	$.SmartMenus.prototype.isCollapsible = function() {
		return this.$firstLink.parent().css('float') != 'left';
	};
})(jQuery);
	