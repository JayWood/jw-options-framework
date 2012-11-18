jQuery(document).ready(function($){
    
	$('.jw_tab').click(function(e){
		if(!$(this).hasClass('active')){
			var curTab = $(this);
			var contID = curTab.children('a').attr('id');
			
			$('.jw_tab, .jw_tab_child').removeClass('active');
			curTab.addClass('active');
			
			$('.jw_child_tab').slideUp(function(e){
				curTab.children('ul').slideDown();
			});
			
			$('.jw_cont_tab').fadeOut(250, function(e){
				$(this).removeClass('active');
			});
			
			$('#jw_cont_'+contID).delay(250).fadeIn(250,function(e){
				$(this).addClass('active');
			});
			
		}
	});	
	
	$('.jw_tab_child').click(function(e){
		if(!$(this).hasClass('active')){
			var curTab = $(this);
			var contID = curTab.children('a').attr('id');
			
			$('.jw_tab_child').removeClass('active');
			curTab.addClass('active');
			
			$('.jw_cont_tab').fadeOut(250,function(e){
				$(this).removeClass('active');
			});
			
			$('#jw_cont_'+contID).delay(250).fadeIn(250,function(e){
				$(this).addClass('active');
			});
			
		}
	});
	
});