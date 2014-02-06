<!doctype html>
<html ng-app="mmsApp">
	<head>
		<title>Demo</title>
		<script src="http://code.jquery.com/jquery-1.10.2.min.js"></script>
		<meta charset="UTF-8">
		
		<style>
		body {
			margin: 0px;
			padding: 0px;
		}
		ul {
			margin: 0px;
			padding: 0px;
		}
		li {
			width: 100%;
			height: 199px;
			border-bottom: 1px solid #000;
			display: inline-block;
		}
		</style>
		
		<script type="text/javascript">
		var offScreenRow = 20;
		var lastTopRow;
		
		function scrollToRow(id) {
			var pos = $('#'+id).position();
			$(window).scrollTop(pos.top);
		}
		
		function init() {
			for (i=1; i<=6+offScreenRow*2; i++) {
				genRow(i);
			}
			setTimeout(function() {
				scrollToRow(offScreenRow+1);
			}, 0);
		}
		
		function track() {
			// console.log('ScrollTop: ' + $(window).scrollTop());
			var topRow;
			$.each($('li'), function(index, li) {
				if ($(li).position().top > $(window).scrollTop()) {
					return false;
				}
				topRow = li;
			});
			if (lastTopRow == null) {
				lastTopRow = topRow;
			}
			// console.log('topRow: ' + $(topRow).attr('id') + ' | lastTopRow: ' + $(lastTopRow).attr('id'));
			var topRowId = parseInt($(topRow).attr('id'));
			var lastTopRowId = parseInt($(lastTopRow).attr('id'));
			if (topRowId < lastTopRowId) {
				for (i=0; i<Math.abs(topRowId - lastTopRowId); i++) {
					genBefore();
				}
			} else if (topRowId > lastTopRowId) {
				for (i=0; i<Math.abs(topRowId - lastTopRowId); i++) {
					genAfter();
				}
			}
			lastTopRow = topRow;
		}
		
		function genAfter() {
			var id = $('li').last().attr('id');
			genRow(parseInt(id)+1);
			$('li').first().remove();
			$(window).scrollTop($(window).scrollTop()-200);
		}
		
		function genBefore() {
			var id = $('li').first().attr('id');
			genRow(id-1, true);
			$(window).scrollTop($(window).scrollTop()+200);
			$('li').last().remove();
		}
		
		function genRow(id, before) {
			if (before) {
				$('<li>').attr('id', id).html('id: '+id).prependTo($('ul'));
			} else {
				$('<li>').attr('id', id).html('id: '+id).appendTo($('ul'));
			}
		}
		
		$(document).ready(function() {
			init();
			$(window).scroll(function() {
				track();
			});
		});
		</script>
		
	</head>
	<body>
		<ul>
		</ul>
  	</body>
</html>