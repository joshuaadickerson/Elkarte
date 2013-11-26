/**
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 1.0 Alpha
 *
 * This file contains javascript utility functions specific to ElkArte
 */

/**
 * Sets an auto height so small code blocks collaspe
 * Sets a height for larger code blocks and lets them resize / overflow as normal
 */
function elk_codefix()
{
	$('.bbc_code').each(function()
	{
		$(this).height("auto");
		if ($(this).height() > 200)
			$(this).css('height', '20em');
	});
}

/**
 * Turn a regular url button in to an ajax request
 *
 * @param {string} btn string representing this, generally the anchor link tag <a class="" href="" onclick="">
 * @param {string} confirmation_msg_variable var name of the text sting to display in the "are you sure" box
 */
function toggleButtonAJAX(btn, confirmation_msg_variable)
{
	$.ajax({
		type: 'GET',
		url: btn.href + ';xml;api',
		context: document.body,
		beforeSend: ajax_indicator(true)
	})
	.done(function(request) {
		if (request === '')
			return;

		var oElement = $(request).find('elk')[0];

		// No errors
		if (oElement.getElementsByTagName('error').length === 0)
		{
			var text = oElement.getElementsByTagName('text'),
				url = oElement.getElementsByTagName('url'),
				confirm_elem = oElement.getElementsByTagName('confirm');

			// Update the page so button/link/confirm/etc reflect the new on or off status
			if (confirm_elem.length === 1)
				var confirm_text = confirm_elem[0].firstChild.nodeValue.removeEntities();

			$('.' + btn.className.replace(/(list|link)level\d/g, '').trim()).each(function() {
				// @todo: the span should be moved somewhere in themes.js?
				if (text.length === 1)
					$(this).html('<span>' + text[0].firstChild.nodeValue.removeEntities() + '</span>');

				if (url.length === 1)
					$(this).attr('href', url[0].firstChild.nodeValue.removeEntities());

				// Replaces the confirmation var text with the new one from the response to allow swapping on/off
				if (typeof (confirm_text) !== 'undefined')
					eval(confirmation_msg_variable + '= \'' + confirm_text.replace(/[\\']/g, '\\$&') + '\'');
			});
		}
		else
		{
			// Error returned from the called function, show an alert
			if (oElement.getElementsByTagName('text').length !== 0)
				alert(oElement.getElementsByTagName('text')[0].firstChild.nodeValue.removeEntities());

			if (oElement.getElementsByTagName('url').length !== 0)
				window.location.href = oElement.getElementsByTagName('url')[0].firstChild.nodeValue;
		}
	})
	.fail(function() {
		// ajax failure code
	})
	.always(function() {
		// turn off the indicator
		ajax_indicator(false);
	});

	return false;
}

/**
 * Helper function: displays and removes the ajax indicator and
 * hides some page elements inside "container_id"
 * Used by some (one at the moment) ajax buttons
 *
 * @todo it may be merged into the function if not used anywhere else
 *
 * @param {string} btn string representing this, generally the anchor link tag <a class="" href="" onclick="">
 * @param {string} container_id  css ID of the data container
 */
function toggleHeaderAJAX(btn, container_id)
{
	// Show ajax is in progress
	ajax_indicator(true);
	var text_template = '<h3 class="category_header centertext">{text}</h3>';

	$.ajax({
		type: 'GET',
		url: btn.href + ';xml;api',
		context: document.body,
		beforeSend: ajax_indicator(true)
		})
		.done(function(request) {
			var oElement = $(request).find('elk')[0];

			// No errors
			if (oElement.getElementsByTagName('error').length === 0)
			{
				var text = oElement.getElementsByTagName('text')[0].firstChild.nodeValue.removeEntities();

				$('#' + container_id + ' .pagesection').remove();
				$('#' + container_id + ' .category_header').remove();
				$('#' + container_id + ' .topic_listing').remove();
				$(text_template.replace('{text}', text)).insertBefore('#topic_icons');
			}
		})
		.fail(function() {
			// ajax failure code
		})
		.always(function() {
			// turn off the indicator
			ajax_indicator(false);
		});
}

/**
 * Ajaxify the "notify" button in Display
 *
 * @param {string} btn string representing this, generally the anchor link tag <a class="" href="" onclick="">
 */
function notifyButton(btn)
{
	if (typeof (notification_topic_notice) !== 'undefined' && !confirm(notification_topic_notice))
		return false;

	return toggleButtonAJAX(btn, 'notification_topic_notice');
}

/**
 * Ajaxify the "notify" button in MessageIndex
 *
 * @param {string} btn string representing this, generally the anchor link tag <a class="" href="" onclick="">
 */
function notifyboardButton(btn)
{
	if (typeof (notification_board_notice) !== 'undefined' && !confirm(notification_board_notice))
		return false;

	toggleButtonAJAX(btn, 'notification_board_notice');
	return false;
}

/**
 * Ajaxify the "unwatch" button in Display
 *
 * @param {string} btn string representing this, generally the anchor link tag <a class="" href="" onclick="">
 */
function unwatchButton(btn)
{
	toggleButtonAJAX(btn);
	return false;
}

/**
 * Ajaxify the "mark read" button in MessageIndex
 *
 * @param {string} btn string representing this, generally the anchor link tag <a class="" href="" onclick="">
 */
function markboardreadButton(btn)
{
	toggleButtonAJAX(btn);

	// Remove all the "new" icons next to the topics subjects
	$('.new_posts').each(function() {
		$(this).parent().remove();
	});

	return false;
}

/**
 * Ajaxify the "mark all messages as read" button in BoardIndex
 *
 * @param {string} btn string representing this, generally the anchor link tag <a class="" href="" onclick="">
 */
function markallreadButton(btn)
{
	toggleButtonAJAX(btn);

	// Remove all the "new" icons next to the topics subjects
	$('.new_posts').each(function() {
		$(this).parent().remove();
	});

	// Turn the board icon class to off
    $('.board_icon').each(function() {
        $(this).removeClass('on_board on2_board').addClass('off_board');
    });

	$('.board_new_posts').each(function() {
		$(this).removeClass('board_new_posts');
	});

	return false;
}

/**
 * Ajaxify the "mark all messages as read" button in Recent
 *
 * @param {string} btn string representing this, generally the anchor link tag <a class="" href="" onclick="">
 */
function markunreadButton(btn)
{
	toggleHeaderAJAX(btn, 'main_content_section');
	return false;
}

/**
 * This function changes the relative time around the page real-timeish
 */
var relative_time_refresh = 0;
function updateRelativeTime()
{
	// In any other case no more than one hour
	relative_time_refresh = 3600000;

	$('time').each(function() {
		var oRelativeTime = new relativeTime($(this).attr('datetime')),
			postdate = new Date($(this).attr('datetime')),
			today = new Date(),
			time_text = '',
			past_time = (today - postdate) / 1000;

		if (oRelativeTime.seconds())
		{
			$(this).text(oRttime.now);
			relative_time_refresh = Math.min(relative_time_refresh, 10000);
		}
		else if (oRelativeTime.minutes())
		{
			time_text = oRelativeTime.deltaTime > 1 ? oRttime.minutes : oRttime.minute;
			$(this).text(time_text.replace('%s', oRelativeTime.deltaTime));
			relative_time_refresh = Math.min(relative_time_refresh, 60000);
		}
		else if (oRelativeTime.hours())
		{
			time_text = oRelativeTime.deltaTime > 1 ? oRttime.hours : oRttime.hour;
			$(this).text(time_text.replace('%s', oRelativeTime.deltaTime));
			relative_time_refresh = Math.min(relative_time_refresh, 3600000);
		}
		else if (oRelativeTime.days())
		{
			time_text = oRelativeTime.deltaTime > 1 ? oRttime.days : oRttime.day;
			$(this).text(time_text.replace('%s', oRelativeTime.deltaTime));
			relative_time_refresh = Math.min(relative_time_refresh, 3600000);
		}
		else if (oRelativeTime.weeks())
		{
			time_text = oRelativeTime.deltaTime > 1 ? oRttime.weeks : oRttime.week;
			$(this).text(time_text.replace('%s', oRelativeTime.deltaTime));
			relative_time_refresh = Math.min(relative_time_refresh, 3600000);
		}
		else if (oRelativeTime.months())
		{
			time_text = oRelativeTime.deltaTime > 1 ? oRttime.months : oRttime.month;
			$(this).text(time_text.replace('%s', oRelativeTime.deltaTime));
			relative_time_refresh = Math.min(relative_time_refresh, 3600000);
		}
		else if (oRelativeTime.years())
		{
			time_text = oRelativeTime.deltaTime > 1 ? oRttime.years : oRttime.year;
			$(this).text(time_text.replace('%s', oRelativeTime.deltaTime));
			relative_time_refresh = Math.min(relative_time_refresh, 3600000);
		}
	});

	setTimeout('updateRelativeTime()', relative_time_refresh);
}

/**
 * Function/object to handle relative times
 * sTo is optional, if omitted the relative time is
 * calculated from sFrom up to "now"
 *
 * @param {string} sFrom
 * @param {string} sTo
 */
function relativeTime(sFrom, sTo)
{
	if (typeof sTo === 'undefined')
		this.dateTo = new Date();
	else
		this.dateTo = new Date(sTo);

	this.dateFrom = new Date(sFrom);

	this.time_text = '';
	this.past_time = (this.dateTo - this.dateFrom) / 1000;
	this.deltaTime = 0;
}

relativeTime.prototype.seconds = function()
{
	// Within the first 60 seconds it is just now.
	if (this.past_time < 60)
	{
		this.deltaTime = this.past_time;
		return true;
	}

	return false;
};

relativeTime.prototype.minutes = function()
{
	// Within the first hour?
	if (this.past_time >= 60 && Math.round(this.past_time / 60) < 60)
	{
		this.deltaTime = Math.round(this.past_time / 60);
		return true;
	}

	return false;
};

relativeTime.prototype.hours = function()
{
	// Some hours but less than a day?
	if (Math.round(this.past_time / 60) >= 60 && Math.round(this.past_time / 3600) < 24)
	{
		this.deltaTime = Math.round(this.past_time / 3600);
		return true;
	}

	return false;
};

relativeTime.prototype.days = function()
{
	// Some days ago but less than a week?
	if (Math.round(this.past_time / 3600) >= 24 && Math.round(this.past_time / (24 * 3600)) < 7)
	{
		this.deltaTime = Math.round(this.past_time / (24 * 3600));
		return true;
	}

	return false;
};

relativeTime.prototype.weeks = function()
{
	// Weeks ago but less than a month?
	if (Math.round(this.past_time / (24 * 3600)) >= 7 && Math.round(this.past_time / (24 * 3600)) < 30)
	{
		this.deltaTime = Math.round(this.past_time / (24 * 3600) / 7);
		return true;
	}

	return false;
};

relativeTime.prototype.months = function()
{
	// Months ago but less than a year?
	if (Math.round(this.past_time / (24 * 3600)) >= 30 && Math.round(this.past_time / (30 * 24 * 3600)) < 12)
	{
		this.deltaTime = Math.round(this.past_time / (30 * 24 * 3600));
		return true;
	}

	return false;
};

relativeTime.prototype.years = function()
{
	// Oha, we've passed at least a year?
	if (Math.round(this.past_time / (30 * 24 * 3600)) >= 12)
	{
		this.deltaTime = this.dateTo.getFullYear() - this.dateFrom.getFullYear();
		return true;
	}

	return false;
};

/**
 * Used to tag mentioned names when they are entered inline but NOT selected from the dropdown list
 * The name must have appeared in the dropdown and be found in that cache list
 *
 * @param {string} sForm the form that holds the container, only used for plain text QR
 * @param {string} sInput the container that atWho is attached
 */
function revalidateMentions(sForm, sInput)
{
	var cached_names,
		cached_queries,
		body,
		mentions;

	for (var i = 0, count = all_elk_mentions.length; i < count; i++)
	{
		// Make sure this mention object is for this selector, saftey first
		if (all_elk_mentions[i].selector === sInput || all_elk_mentions[i].selector === '#' + sInput)
		{
			// Was this invoked as the editor plugin?
			if (all_elk_mentions[i].oOptions.isPlugin)
			{
				var $editor = $('#' + all_elk_mentions[i].selector).data("sceditor");

				cached_names = $editor.opts.mentionOptions.cache.names;
				cached_queries = $editor.opts.mentionOptions.cache.queries;

				// Clean up the newlines and spacing so we can find the @mentions
				body = $editor.getText().replace(/[\u00a0\r\n]/g, ' ');
				mentions = $($editor.opts.mentionOptions.cache.mentions);
			}
			// Or just our plain text quick reply box?
			else
			{
				cached_names = all_elk_mentions[i].oMention.cached_names;
				cached_queries = all_elk_mentions[i].oMention.cached_queries;

				// Keep everying sepeteate with spaces, not newlines or no breakable
				body = document.forms[sForm][sInput].value.replace(/[\u00a0\r\n]/g, ' ');

				// The last pulldown box that atWho populated
				mentions = $(all_elk_mentions[i].oMention.mentions);
			}

			// Adding a space at the beginning to facilitate catching of mentions at the 1st char
			// and one at the end to simplify catching any aa last thing in the text
			body = ' ' + body + ' ';

			// First check if all those in the list are really mentioned
			$(mentions).find('input').each(function (idx, elem) {
				var name = $(elem).data('name'),
					next_char,
					prev_char,
					index = body.indexOf(name);

				// It is undefined coming from a preview
				if (typeof(name) !== 'undefined')
				{
					if (index === -1)
						$(elem).remove();
					else
					{
						next_char = body.charAt(index + name.length);
						prev_char = body.charAt(index - 1);

						if (next_char !== '' && next_char.localeCompare(" ") !== 0)
							$(elem).remove();
						else if (prev_char !== '' && prev_char.localeCompare(" ") !== 0)
							$(elem).remove();
					}
				}
			});

			for (var k = 0, ccount = cached_queries.length; k < ccount; k++)
			{
				names = cached_names[cached_queries[k]];
				for (var l = 0, ncount = names.length; l < ncount; l++)
					if (body.indexOf(' @' + names[l].name + ' ') !== -1)
						mentions.append($('<input type="hidden" name="uid[]" />').val(names[l].id));
			}
		}
	}
}

/**
 * This is called from the editor plugin or display.template to set where to
 * find the cache values for use in revalidateMentions
 *
 * @param {string} selector id of element that atWho is attached to
 * @param {object} oOptions only set when called from the pluging, contains those options
 */
var all_elk_mentions = [];
function add_elk_mention(selector, oOptions)
{
	// Global does not exist, hummm
	if (all_elk_mentions.hasOwnProperty(selector))
		return;

	// No options means its attached to the plain text box
	if (typeof oOptions === 'undefined')
		oOptions = {};
	oOptions.selector = selector;

	// Add it to the stack
	all_elk_mentions[all_elk_mentions.length] = {
		selector: selector,
		oOptions: oOptions
	};
}

/**
 * Drag and drop to reorder ID's via UI Sortable
 *
 * @param {object} $
 */
(function($) {
	'use strict';
	$.fn.elkSortable = function(oInstanceSettings) {
		$.fn.elkSortable.oDefaultsSettings = {
			opacity: 0.7,
			cursor: 'move',
			axis: 'y',
			scroll: true,
			containment: 'parent',
			delay: 150,
			href: '', // If an error occurs redirect here
			tolerance: 'intersect', // mode to use for testing whether the item is hovering over another item.
			setorder: 'serialize', // how to return the data, really only supports serialize and inorder
			placeholder: '', // css class used to style the landing zone
			preprocess: '', // This function is called at the start of the update event (when the item is dropped) must in in global space
			tag: '#table_grid_sortable', // ID(s) of the container to work with, single or comma separated
			connect: '', // Use to group all related containers with a common CSS class
			sa: '', // Subaction that the xmlcontroller should know about
			title: '', // Title of the error box
			error: '', // What to say when we don't know what happened, like connection error
			token: '' // Security token if needed
		};

		// Account for any user options
		var oSettings = $.extend({}, $.fn.elkSortable.oDefaultsSettings, oInstanceSettings || {});

		// Divs to hold our responses
		var ajax_infobar = document.createElement('div'),
			ajax_errorbox = document.createElement('div');

		// Prepare the infobar and errorbox divs to confirm valid responses or show an error
		$(ajax_infobar).css({'position': 'fixed', 'top': '0', 'left': '0', 'width': '100%'});
		$("body").append(ajax_infobar);
		$(ajax_infobar).slideUp();

		$(ajax_errorbox).css({'display': 'none'});
		$("body").append(ajax_errorbox).attr('id', 'errorContainer');

		// Find all oSettings.tag and attach the UI sortable action
		$(oSettings.tag).sortable({
			opacity: oSettings.opacity,
			cursor: oSettings.cursor,
			axis: oSettings.axis,
			containment: oSettings.containment,
			connectWith: oSettings.connect,
			placeholder: oSettings.placeholder,
			tolerance: oSettings.tolerance,
			delay: oSettings.delay,
			scroll: oSettings.scroll,
			helper: function(e, ui) {
				// Create a clone of the element being dragged, add it to the body, and hide it
				$('body').append('<div id="clone" class="' + oSettings.placeholder + '">' + ui.html() + '</div>');
				$('#clone').hide();

				// Now append the clone element to the container we are working in and show it
				setTimeout(function() {
					$('#clone').appendTo(ui.parent());
					$("#clone").show();
				}, 1);

				// The above process allows page scrolls to work
				return $("#clone");
			},
			update: function(e, ui) {
				// Called when an element is dropped in a new location
				var postdata = '',
					moved = ui.item.attr('id'),
					order = [],
					receiver = ui.item.parent().attr('id');

				// Calling a pre processing function?
				if (oSettings.preprocess !== '')
					window[oSettings.preprocess]();

				// How to post the sorted data
				if (oSettings.setorder === 'inorder')
				{
					// This will get the order in 1-n as shown on the screen
					$(oSettings.tag).find('li').each(function() {
						var aid = $(this).attr('id').split('_');
						order.push({name: aid[0] + '[]', value: aid[1]});
					});
					postdata = $.param(order);
				}
				// Get all id's in all the sortable containers
				else
				{
					$(oSettings.tag).each(function() {
						// Serialize will be 1-n of each nesting / connector
						if (postdata === "")
							postdata += $(this).sortable(oSettings.setorder);
						else
							postdata += "&" + $(this).sortable(oSettings.setorder);
					});
				}

				// Add in our security tags and additional options
				postdata += '&' + elk_session_var + '=' + elk_session_id;
				postdata += '&order=reorder';
				postdata += '&moved=' + moved;
				postdata += '&received=' + receiver;

				if (oSettings.token !== '')
					postdata += '&' + oSettings.token['token_var'] + '=' + oSettings.token['token_id'];

				// And with the post data prepared, lets make the ajax request
				$.ajax({
					type: "POST",
					url: elk_scripturl + "?action=xmlhttp;sa=" + oSettings.sa + ";xml",
					dataType: "xml",
					data: postdata
				})
				.fail(function(jqXHR, textStatus, errorThrown) {
					$(ajax_infobar).attr('class', 'errorbox');
					$(ajax_infobar).html(textStatus).slideDown('fast');
					setTimeout(function() {
						$(ajax_infobar).slideUp();
					}, 3500);
					// Reset the interface?
					if (oSettings.href !== '')
						setTimeout(function() {
							window.location.href = elk_scripturl + oSettings.href;
						}, 1000);
				})
				.done(function(data, textStatus, jqXHR) {
					if ($(data).find("error").length !== 0)
					{
						// Errors get a modal dialog box and redirect on close
						$('#errorContainer').append('<p id="errorContent"></p>');
						$('#errorContent').html($(data).find("error").text());
						$('#errorContent').dialog({
							autoOpen: true,
							title: oSettings.title,
							modal: true,
							close: function(event, ui) {
								// Redirecting due to the error, thats a good idea
								if (oSettings.href !== '')
									window.location.href = elk_scripturl + oSettings.href;
							}
						});
					}
					else if ($(data).find("elk").length !== 0)
					{
						// Valid responses get the unobtrusive slider
						$(ajax_infobar).attr('class', 'infobox');
						$(ajax_infobar).html($(data).find('elk > orders > order').text()).slideDown('fast');
						setTimeout(function() {
							$(ajax_infobar).slideUp();
						}, 3500);
					}
					else
					{
						// Something "other" happened ...
						$('#errorContainer').append('<p id="errorContent"></p>');
						$('#errorContent').html(oSettings.error + ' : ' + textStatus);
						$('#errorContent').dialog({autoOpen: true, title: oSettings.title, modal: true});
					}
				})
				.always(function(data, textStatus, jqXHR) {
					if (textStatus === 'success' && $(data).find("elk > tokens > token").length !== 0)
					{
						// Reset the token
						oSettings.token['token_id'] = $(data).find("tokens").find('[type="token"]').text();
						oSettings.token['token_var'] = $(data).find("tokens").find('[type="token_var"]').text();
					}
				});
			}
		});
	};
})(jQuery);

/**
 * Helper function used in the preprocess call for drag/drop boards
 * Sets the id of all 'li' elements to cat#,board#,childof# for use in the
 * $_POST back to the xmlcontroller
 */
function setBoardIds() {
	// For each category of board
	$("[id^=category_]").each(function() {
		var cat = $(this).attr('id').split('category_'),
				uls = $(this).find("ul");

		// First up add drop zones so we can drag and drop to each level
		if (uls.length === 1)
		{
			// A single empty ul in a category, this can happen when a cat is dragged empty
			if ($(uls).find("li").length === 0)
				$(uls).append('<li id="cbp_' + cat + ',-1,-1"></li>');
			// Otherwise the li's need a child ul so we have a "child-of" drop zone
			else
				$(uls).find("li:not(:has(ul))").append('<ul class="nolist"></ul>');
		}
		// All others normally
		else
			$(uls).find("li:not(:has(ul))").append('<ul class="nolist"></ul>');

		// Next make find all the ul's in this category that have children, update the
		// id's with information that indicates the 1-n and parent/child info
		$(this).find('ul:parent').each(function(i, ul) {

			// Get the (li) parent of this ul
			var parentList = $(this).parent('li').attr('id'),
					pli = 0;

			// No parent, then its a base node 0, else its a child-of this node
			if (typeof (parentList) !== "undefined")
			{
				pli = parentList.split(",");
				pli = pli[1];
			}

			// Now for each li in this ul
			$(this).find('li').each(function(i, el) {
				var currentList = $(el).attr('id');
				var myid = currentList.split(",");

				// Remove the old id, insert the newly computed cat,brd,childof
				$(el).removeAttr("id");
				myid = "cbp_" + cat[1] + "," + myid[1] + "," + pli;
				$(el).attr('id', myid);
			});
		});
	});
}

/**
 * Expands the ... of the page indexes
 *
 * @todo not exactly a plugin and still very bound to the theme structure
 *
 */
;(function($) {
	$.fn.expand_pages = function() {
		var $container,
			lastPositions = new Array();

		// Hovering over an ... we expand it as much as we can
		function hover_expand($element)
		{
			var $expanded_pages_li = $element,
				baseurl = eval($element.data('baseurl')),
				perpage = $element.data('perpage'),
				firstpage = $element.data('firstpage'),
				lastpage = $element.data('lastpage'),
				$exp_pages = $('<li id="expanded_pages" />'),
				pages = 0,
				container_width = $element.outerWidth() * 2,
				width_elements = 3,
				$scroll_left = null,
				$scroll_right = null;

			var aModel = $element.closest('.linavPages').prev().find('a').clone();

			if (typeof(lastPositions[firstpage]) === 'undefined')
				lastPositions[firstpage] = 0;

			$container = $('<ul id="expanded_pages_container">');

			for (var i = firstpage; i < lastpage; i += perpage)
			{
				pages++;
				var bElem = aModel.clone();

				bElem.attr('href', baseurl.replace('%1$d', i)).text(i / perpage + 1);
				$exp_pages.append(bElem);
			}

			if (pages > width_elements)
			{
				$container.append($('<li />').append(aModel.clone()
				.attr('id', 'pages_scroll_left')
				.attr('href', '#').text('<').click(function(ev) {
					ev.stopPropagation();
					ev.preventDefault();
				}).hover(
					function() {
						$exp_pages.animate({
							'margin-left': 0
						}, 200 * pages);
					},
					function() {
						$exp_pages.stop();
						lastPositions[firstpage] = $exp_pages.css('margin-left');
					}
				)));
			}

			$container.append($exp_pages);
			$element.parent().superfish({
				delay : 300,
				speed: 175,
				onHide: function () {
					$container.remove();
				}
			});

			$element.append($container);

			if (pages > width_elements)
			{
				$container.append($('<li />').append(aModel.clone()
				.attr('id', 'pages_scroll_right')
				.attr('href', '#').text('>').click(function(ev) {
					ev.stopPropagation();
					ev.preventDefault();
				}).hover(
					function() {
						var $pages = $exp_pages.find('a'),
							move = 0;

						for (var i = 0, count = $exp_pages.find('a').length; i < count; i++)
							move += $($pages[i]).outerWidth();

						move = (move + $container.find('#pages_scroll_left').outerWidth()) - ($container.outerWidth() - $container.find('#pages_scroll_right').outerWidth());

						$exp_pages.animate({
							'margin-left': -move
						}, 200 * pages);
					},
					function() {
						$exp_pages.stop();
						lastPositions[firstpage] = $exp_pages.css('margin-left');
					}
				)));
			}

			// @todo this seems broken
			$exp_pages.find('a').each(function() {
				if (width_elements > -1)
					container_width += $element.outerWidth();

				if (width_elements <= 0 || pages >= width_elements)
				{
					$container.css({
						'margin-left': -container_width / 2
					}).width(container_width);
				}

				if (width_elements < 0)
					return false;

				width_elements--;
			}).click(function (ev) {
				$expanded_pages_li.attr('onclick', '').unbind('click');
			});

			$exp_pages.css({
				'height': $element.outerHeight(),
				'padding-left': $container.find('#pages_scroll_left').outerWidth(),
				'margin-left': lastPositions[firstpage]
			});
		};

		// Used when the user clicks on the ... to expand instead of just a hover expand
		function expand_pages($element)
		{
			var $baseAppend = $($element.closest('.linavPages')),
				boxModel = $baseAppend.prev().clone(),
				aModel = boxModel.find('a').clone(),
				expandModel = $element.clone(),
				perPage = $element.data('perpage'),
				firstPage = $element.data('firstpage'),
				lastPage = $element.data('lastpage'),
				rawBaseurl = $element.data('baseurl'),
				baseurl = eval($element.data('baseurl')),
				first;

			var i =0,
				oldLastPage = 0,
				perPageLimit = 10;

			// Prevent too many pages to be loaded at once.
			if ((lastPage - firstPage) / perPage > perPageLimit)
			{
				oldLastPage = lastPage;
				lastPage = firstPage + perPageLimit * perPage;
			}

			// Calculate the new pages.
			for (i = lastPage; i > firstPage; i -= perPage)
			{
				var bElem = aModel.clone(),
					boxModelClone = boxModel.clone();

				bElem.attr('href', baseurl.replace('%1$d', i - perPage)).text(i / perPage);
				boxModelClone.find('a').each(function() {
					$(this).replaceWith(bElem[0]);
				});
				$baseAppend.after(boxModelClone);

				// This is needed just to remember where to attach the new expand
				if (typeof first === 'undefined')
					first = boxModelClone;
			}
			$baseAppend.remove();

			if (oldLastPage > 0)
			{
				// This is to remove any hover_expand
				expandModel.find('#expanded_pages_container').each(function() {
					$(this).remove();
				});

				expandModel.click(function(e) {
					var $zhis = $(this);
					e.preventDefault();

					expand_pages($zhis);

					$zhis.unbind('mouseenter focus');
				})
				.bind('mouseenter focus', function() {
					hover_expand($(this));
				})
				.data('perpage', perPage)
				.data('firstpage', lastPage)
				.data('lastpage', oldLastPage)
				.data('baseurl', rawBaseurl);

				first.after(expandModel);
			}
		}

		this.attr('tabindex', 0).click(function(e) {
			var $zhis = $(this);
			e.preventDefault();

			expand_pages($zhis);

			$zhis.unbind('mouseenter focus');
		})
		.bind('mouseenter focus', function() {
			hover_expand($(this));
		});
	};
})(jQuery);

/**
 * SiteTooltip, Basic JQuery function to provide styled tooltips
 *
 * - will use the hoverintent plugin if available
 * - shows the tooltip in a div with the class defined in tooltipClass
 * - moves all selector titles to a hidden div and removes the title attribute to
 *   prevent any default browser actions
 * - attempts to keep the tooltip on screen
 *
 * @param {type} $
 */
(function($) {
	'use strict';
	$.fn.SiteTooltip = function(oInstanceSettings) {
		$.fn.SiteTooltip.oDefaultsSettings = {
			followMouse: 1,
			hoverIntent: {sensitivity: 10, interval: 750, timeout: 50},
			positionTop: 12,
			positionLeft: 12,
			tooltipID: 'site_tooltip', // ID used on the outer div
			tooltipTextID: 'site_tooltipText', // as above but on the inner div holding the text
			tooltipClass: 'tooltip', // The class applied to the outer div (that displays on hover), use this in your css
			tooltipSwapClass: 'site_swaptip', // a class only used internally, change only if you have a conflict
			tooltipContent: 'html' // display captured title text as html or text
		};

		// Account for any user options
		var oSettings = $.extend({}, $.fn.SiteTooltip.oDefaultsSettings, oInstanceSettings || {});

		// Move passed selector titles to a hidden span, then remove the selector title to prevent any default browser actions
		$(this).each(function()
		{
			var sTitle = $('<span class="' + oSettings.tooltipSwapClass + '">' + htmlspecialchars(this.title) + '</span>').hide();
			$(this).append(sTitle).attr('title', '');
		});

		// Determine where we are going to place the tooltip, while trying to keep it on screen
		var positionTooltip = function(event)
		{
			var iPosx = 0,
					iPosy = 0;

			if (!event)
				event = window.event;

			if (event.pageX || event.pageY)
			{
				iPosx = event.pageX;
				iPosy = event.pageY;
			}
			else if (event.clientX || event.clientY)
			{
				iPosx = event.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
				iPosy = event.clientY + document.body.scrollTop + document.documentElement.scrollTop;
			}

			// Position of the tooltip top left corner and its size
			var oPosition = {
				x: iPosx + oSettings.positionLeft,
				y: iPosy + oSettings.positionTop,
				w: $('#' + oSettings.tooltipID).width(),
				h: $('#' + oSettings.tooltipID).height()
			};

			// Display limits and window scroll postion
			var oLimits = {
				x: $(window).scrollLeft(),
				y: $(window).scrollTop(),
				w: $(window).width() - 24,
				h: $(window).height() - 24
			};

			// Don't go off screen with our tooltop
			if ((oPosition.y + oPosition.h > oLimits.y + oLimits.h) && (oPosition.x + oPosition.w > oLimits.x + oLimits.w))
			{
				oPosition.x = (oPosition.x - oPosition.w) - 45;
				oPosition.y = (oPosition.y - oPosition.h) - 45;
			}
			else if ((oPosition.x + oPosition.w) > (oLimits.x + oLimits.w))
			{
				oPosition.x = oPosition.x - (((oPosition.x + oPosition.w) - (oLimits.x + oLimits.w)) + 24);
			}
			else if (oPosition.y + oPosition.h > oLimits.y + oLimits.h)
			{
				oPosition.y = oPosition.y - (((oPosition.y + oPosition.h) - (oLimits.y + oLimits.h)) + 24);
			}

			// Finally set the position we determined
			$('#' + oSettings.tooltipID).css({'left': oPosition.x + 'px', 'top': oPosition.y + 'px'});
		};

		// Used to show a tooltip
		var showTooltip = function() {
			$('#' + oSettings.tooltipID + ' #' + oSettings.tooltipTextID).show();
		};

		// Used to hide a tooltip
		var hideTooltip = function() {
			$('#' + oSettings.tooltipID).fadeOut('slow').trigger("unload").remove();
		};

		// Used to keep html encoded
		function htmlspecialchars(string)
		{
			return $('<span>').text(string).html();
		}

		// For all of the elements that match the selector on the page, lets set up some actions
		return this.each(function()
		{
			// If we find hoverIntent then use it
			if ($.fn.hoverIntent)
			{
				$(this).hoverIntent({
					sensitivity: oSettings.hoverIntent.sensitivity,
					interval: oSettings.hoverIntent.interval,
					over: site_tooltip_on,
					timeout: oSettings.hoverIntent.timeout,
					out: site_tooltip_off
				});
			}
			else
			{
				// Plain old hover it is
				$(this).hover(site_tooltip_on, site_tooltip_off);
			}

			// Create the on tip action
			function site_tooltip_on(event)
			{
				// If we have text in the hidden span element we created on page load
				if ($(this).children('.' + oSettings.tooltipSwapClass).text())
				{
					// Create a ID'ed div with our style class that holds the tooltip info, hidden for now
					$('body').append('<div id="' + oSettings.tooltipID + '" class="' + oSettings.tooltipClass + '"><div id="' + oSettings.tooltipTextID + '" style="display:none;"></div></div>');

					// Load information in to our newly created div
					var tt = $('#' + oSettings.tooltipID),
						ttContent = $('#' + oSettings.tooltipID + ' #' + oSettings.tooltipTextID);

					if (oSettings.tooltipContent === 'html')
						ttContent.html($(this).children('.' + oSettings.tooltipSwapClass).html());
					else
						ttContent.text($(this).children('.' + oSettings.tooltipSwapClass).text());

					// Show then position or it may postion off screen
					tt.show();
					showTooltip();
					positionTooltip(event);
				}

				return false;
			}

			// Create the Bye bye tip
			function site_tooltip_off(event)
			{
				hideTooltip(this);
				return false;
			}

			// Create the tip move with the cursor
			if (oSettings.followMouse)
			{
				$(this).bind("mousemove", function(event) {
					positionTooltip(event);

					return false;
				});
			}

			// Clear the tip on a click
			$(this).bind("click", function() {
				hideTooltip(this);
				return true;
			});

		});
	};
})(jQuery);

/**
 * Error box handler class
 *
 * @param {type} oOptions
 * @returns {errorbox_handler}
 */
var error_txts = {};
function errorbox_handler(oOptions)
{
	this.opt = oOptions;
	this.oError_box = null;
	this.oErrorHandle = window;
	this.evaluate = false;
	this.init();
}

/**
 * @todo this code works well almost only with the editor I think.
 */
errorbox_handler.prototype.init = function()
{
	if (this.opt.check_id !== undefined)
		this.oChecks_on = $(document.getElementById(this.opt.check_id));
	else if (this.opt.selector !== undefined)
		this.oChecks_on = this.opt.selector;
	else if (this.opt.editor !== undefined)
	{
		this.oChecks_on = eval(this.opt.editor);
		this.evaluate = true;
	}

	this.oErrorHandle.instanceRef = this;

	if (this.oError_box === null)
		this.oError_box = $(document.getElementById(this.opt.error_box_id));

	if (this.evaluate === false)
	{
		this.oChecks_on.attr('onblur', this.opt.self + '.checkErrors()');
		this.oChecks_on.attr('onkeyup', this.opt.self + '.checkErrors()');
	}
	else
	{
		var current_error_handler = this.opt.self;
		$(document).ready(function() {
			var current_error = eval(current_error_handler);
			$('#' + current_error.opt.editor_id).data("sceditor").addEvent(current_error.opt.editor_id, 'keyup', function() {
				current_error.checkErrors();
			});
		});
	}
};

errorbox_handler.prototype.boxVal = function()
{
	if (this.evaluate === false)
		return this.oChecks_on.val();
	else
		return this.oChecks_on();
};

/**
 * Runs the field checks as defined by the object instance
 */
errorbox_handler.prototype.checkErrors = function()
{
	var num = this.opt.error_checks.length;

	if (num !== 0)
	{
		// Adds the error checking functions
		for (var i = 0; i < num; i++)
		{
			// Get the element that holds the errors
			var $elem = $(document.getElementById(this.opt.error_box_id + "_" + this.opt.error_checks[i].code));

			// Run the efunction check on this field, then add or remove any errors
			if (this.opt.error_checks[i].efunction(this.boxVal()))
				this.addError($elem, this.opt.error_checks[i].code);
			else
				this.removeError(this.oError_box, $elem);
		}

		this.oError_box.attr("class", "noticebox");
	}

	// Hide show the error box based on if we have any errors
	if (this.oError_box.find("li").length === 0)
		this.oError_box.slideUp();
	else
		this.oError_box.slideDown();
};

/**
 * Add and error to the list
 *
 * @param {type} error_elem
 * @param {type} error_code
 */
errorbox_handler.prototype.addError = function(error_elem, error_code)
{
	if (error_elem.length === 0)
	{
		// First error, then set up the list for insertion
		if ($.trim(this.oError_box.children("#" + this.opt.error_box_id + "_list").html()) === '')
			this.oError_box.append("<ul id='" + this.opt.error_box_id + "_list'></ul>");

		// Add the error it and show it
		$(document.getElementById(this.opt.error_box_id + "_list")).append("<li style=\"display:none\" id='" + this.opt.error_box_id + "_" + error_code + "' class='error'>" + error_txts[error_code] + "</li>");
		$(document.getElementById(this.opt.error_box_id + "_" + error_code)).slideDown();
	}
};

/**
 * Remove an error from the notice window
 *
 * @param {type} error_box
 * @param {type} error_elem
 */
errorbox_handler.prototype.removeError = function(error_box, error_elem)
{
	if (error_elem.length !== 0)
	{
		error_elem.slideUp(function() {
			error_elem.remove();

			// No errors at all then close the box
			if (error_box.find("li").length === 0)
				error_box.slideUp();
		});
	}
};