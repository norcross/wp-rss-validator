jQuery(document).ready( function($) {

// **************************************************************
//  check values and apply classes
// **************************************************************

	var status_block	= $('ul.feed_data li.status span.val');
	var error_block		= $('ul.feed_data li.error_count span.val');
	var warn_block		= $('ul.feed_data li.warn_count span.val');

	if ($(status_block).text() == 'Valid') {
		console.log('Valid');
		$(status_block).addClass('valid');
		$(status_block).after('<span class="valid_item">Valid</span>');
	}

	if ($(status_block).text() !== 'Valid') {
		console.log('Not Valid');
		$(status_block).addClass('invalid');
	}

	if ($(error_block).text() == '0') {
		console.log('No Errors');
		$(error_block).addClass('clean');
		$(error_block).after('<span class="valid_item">Valid</span>');
	}

	if ($(error_block).text() !== '0') {
		console.log('Errors');
		$(error_block).addClass('error');
		$(error_block).after('<input type="button" class="show_errors show_detail" name="errors" value="Show Errors">');
	}

	if ($(warn_block).text() == '0') {
		console.log('No Warnings');
		$(warn_block).addClass('clean');
		$(warn_block).after('<span class="valid_item">Valid</span>');
	}

	if ($(warn_block).text() !== '0') {
		console.log('Warnings');
		$(warn_block).addClass('warning');
		$(warn_block).after('<input type="button" class="show_warnings show_detail" name="warnings" value="Show Warnings">');
	}

// **************************************************************
//  show warning and error message blocks on click
// **************************************************************

	$('input.show_detail').click(function(event) {
		var name = $(this).attr('name');
		
		$('div#rkv_rss_validator div.issue_details[name='+ name +']').toggle(500);

	});

});