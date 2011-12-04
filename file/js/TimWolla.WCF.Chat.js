/**
 * TimWolla.WCF.Chat
 * 
 * @author	Tim Düsterhus
 * @copyright	2010-2011 Tim Düsterhus
 * @license	Creative Commons Attribution-NonCommercial-ShareAlike <http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode>
 * @package	timwolla.wcf.chat
 */
if (typeof TimWolla == 'undefined') var TimWolla = {};
if (typeof TimWolla.WCF == 'undefined') TimWolla.WCF = {};
	

(function ($, document) {
	TimWolla.WCF.Chat = {
		titleTemplate: '',
		init: function(roomID, messageID) {
			this.bindEvents();
		},
		bindEvents: function() {
			$('.smiley').click(function(event) {
				alert($(event.target).attr('alt'));
			});
			
			// $(window).bind('beforeunload', function() {
				// return false;
			// });
			
			$('.chatRoom').click($.proxy(function (event) {
				if (typeof window.history.replaceState != 'undefined') {
					event.preventDefault();
					this.changeRoom($(event.target));
				}
			}, this));
		},
		changeRoom: function(target) {
			window.history.replaceState({}, '', target.attr('href'));
			
			// actually change the room
			$.ajax(target.attr('href'), {
				dataType: 'json',
				data: { ajax: 1 },
				type: 'POST',
				success: $.proxy(function (data, textStatus, jqXHR) {
					this.loading = false;
					$('#loading-' + target.attr('id')).remove();
					target.css({
						'float' : 'none',
						'padding' : '5px 25px 7px 35px',
						'width' : '',
						'overflow' : 'visible'
					});
					
					// set new topic
					if (data.topic == '') {
						if(data.topic == '' && $('#topic').text().trim() == '') return;
						$('#topic').wcfBlindOut('vertical', function() {
								$('#topic').text('');
						});
					} else {
						if($('#topic').text().trim() != '') {
							$('#topic').text(data.topic);
						} else {
							$('#topic').text(data.topic);
							$('#topic').wcfBlindIn();
						}
					}
					
					// set page-title
					$('title').text(this.titleTemplate.fetch(data));
				}, this),
				beforeSend: $.proxy(function () {
					if(this.loading == true || target.parent().attr('class') == "activeMenuItem") return false;
					
					// mark as active;
					$('.activeMenuItem .chatRoom').parent().removeClass('activeMenuItem');
					target.parent().addClass('activeMenuItem');
					
					this.loading = true;
					//target.append('<img id="loading-' + target.attr('id') + '" src="' + WCF.Icon.get('wcf.global.spinner') + '" />');
					target.css({
						'width' : target.width() - 19, 
						'float' : 'left',
						'padding' : '5px 0 7px 35px',
						'overflow': 'hidden'
					});
					target.parent().append('<img id="loading-' + target.attr('id') + '" class="ajaxLoad" src="' + RELATIVE_WCF_DIR + 'icon/spinner1.svg" alt="" />');
					$('#loading-' + target.attr('id')).css({'marginTop' : function(index) {return (target.parent().height() / 2) - ($(this).height() / 2)}});
				}, this)
			});
		}
	};
})(jQuery, document);