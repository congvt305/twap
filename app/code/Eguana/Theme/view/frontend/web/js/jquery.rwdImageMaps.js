/*
 * rwdImageMaps jQuery plugin v1.5
 *
 * Allows image maps to be used in a responsive design by recalculating the area coordinates to match the actual image size on load and window.resize
 *
 * Copyright (c) 2013 Matt Stow
 * https://github.com/stowball/jQuery-rwdImageMaps
 * http://mattstow.com
 * Licensed under the MIT license
 */
define([
	'jquery'
], function () {
	(function($) {
		$.fn.rwdImageMaps = function() {
			var c = this;
			var b = function() {
				c.each(function() {
					if (typeof($(this).attr("usemap")) == "undefined") {
						return;
					}
					var e = this,
						d = $(e);
					$("<img />").load(function() {
						var g = "width",
							m = "height",
							n = d.attr(g),
							j = d.attr(m);
						if (!n || !j) {
							var o = new Image();
							o.src = d.attr("src");
							if (!n) {
								n = o.width;
							}
							if (!j) {
								j = o.height;
							}
						}
						var dWidth = d.width(), dHeight = d.height();

						//if(dWidth == 0 ) dWidth = 1100;
						//if(dWidth == 0 ) dHeight = 891;

						var f = dWidth / 100,
							k = dHeight / 100,
							i = d.attr("usemap").replace("#", ""),
							l = "coords";
						$('map[name="' + i + '"]').find("area").each(function() {
							var r = $(this);
							if (!r.data(l)) {
								r.data(l, r.attr(l));
							}
							var q = r.data(l).split(","),
								p = new Array(q.length);
							for (var h = 0; h < p.length; ++h) {
								if (h % 2 === 0) {
									p[h] = parseInt(((q[h] / n) * 100) * f);
								} else {
									p[h] = parseInt(((q[h] / j) * 100) * k);
								}
							}
							r.attr(l, p.toString());
						});
					}).attr("src", d.attr("src"));
				});
			};
			$(window).resize(b).trigger("resize");
			return this;
		}
	})(jQuery);
});