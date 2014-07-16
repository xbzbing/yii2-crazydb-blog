$(document).ready(function() {
	
	$(window).scroll(function() {
		if ($(window).scrollTop() > 400) {
			$("#toolbar").fadeIn(300);
		} else {
			$("#toolbar").fadeOut(300);
		}
	});
	
	$(".back2top").click(function() {
		$('body,html').animate({
			scrollTop : 0
		}, 500);
		return false;
	});
});