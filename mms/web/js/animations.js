var mmsAnimations = angular.module('mmsAnimations', ['ngAnimate']);

mmsAnimations.animation('.bookingRow', function() {
	return {
		enter: function(element, done) {
			jQuery(element).css('opacity', 0);
			jQuery(element).animate({
				opacity: 1
			}, done);
			return function (isCancelled) {
				if (isCancelled) {
					jQuery(element).stop();
				}
			};
		},
		
		leave: function(element, done) {
			jQuery(element).css('opacity', 1);
			jQuery(element).animate({
				opacity: 0
			}, done);
			return function (isCancelled) {
				if (isCancelled) {
					jQuery(element).stop();
				}
			};
		},
		
		move: function(element, done) {
			jQuery(element).css('opacity', 0);
			jQuery(element).animate({
				opacity: 1
			}, done);
			return function (isCancelled) {
				if (isCancelled) {
					jQuery(element).stop();
				}
			};
		}
	};
});