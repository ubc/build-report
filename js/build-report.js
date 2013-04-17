(function ($, d){

	var  Build_Report = {
		shell :{},
		cookie : [],
		init: function(){
			var cookie = $.cookie('ctlt_report_builder');
			Build_Report.cookie = ( cookie ? cookie.split(',') : [] ) ;
			Build_Report.shell = $('.report-list-shell');
			
			console.log( Build_Report.cookie );
			
			$('.build-report-action').on('click', Build_Report.click );
		},
		
		add_to_cookie: function( post_id ) {
			
			Build_Report.cookie.push( post_id.toString() );
			$.cookie( 'ctlt_report_builder', Build_Report.cookie.join(',') , { expires: 14, path: '/' });
			
			
			//console.log( 'added '+post_id+' to cookie' );
		},
		
		remove_from_cookie: function( post_id ) {
			
			var idx = Build_Report.cookie.indexOf( post_id.toString() ); // Find the index
			
	 		if(idx!=-1) { Build_Report.cookie.splice(idx, 1); }
	 		
	 		$.cookie( 'ctlt_report_builder', Build_Report.cookie.join(',') , { expires: 14, path: '/' });
	 		
	 		//console.log( 'removeded '+post_id+' from cookie' );
		},
		
		click: function() {
			var el = $(this);
			
			var post_id = el.data( 'post_id' );
			var action  = el.data( 'action' );
			var remove_text = el.data( 'remove_text' );
			var add_text = el.data( 'add_text' );
			var new_text    = ( 'add' == action ?  remove_text : add_text );
			
			var new_action  =  ( 'add' == action ?  'remove' : 'add' );
			
			if( 'add' == action ) {
			
				Build_Report.add_to_cookie( post_id );
			
			} else {
				Build_Report.remove_from_cookie( post_id );
			}
			Build_Report.update_report_list();
			
			el.text( new_text );
			el.data( 'action', new_action );
			
			return false;
		
		},
		
		update_report_list: function() {
			
			var data = {
				action: 'report_list'
			};
			
			jQuery.post( build_report_ajaxurl, data, function(response) {
				
				// alert( 'Got this from the server: ' + response );
				jQuery('.report-list-wrap').html( response );
				// update the list
			});
		
		} 
		
		
	} 

	$(d).ready(Build_Report.init);


})( jQuery, document );




/*!
 * jQuery Cookie Plugin v1.3.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD. Register as anonymous module.
		define(['jquery'], factory);
	} else {
		// Browser globals.
		factory(jQuery);
	}
}(function ($) {

	var pluses = /\+/g;

	function raw(s) {
		return s;
	}

	function decoded(s) {
		return decodeURIComponent(s.replace(pluses, ' '));
	}

	function converted(s) {
		if (s.indexOf('"') === 0) {
			// This is a quoted cookie as according to RFC2068, unescape
			s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
		}
		try {
			return config.json ? JSON.parse(s) : s;
		} catch(er) {}
	}

	var config = $.cookie = function (key, value, options) {

		// write
		if (value !== undefined) {
			options = $.extend({}, config.defaults, options);

			if (typeof options.expires === 'number') {
				var days = options.expires, t = options.expires = new Date();
				t.setDate(t.getDate() + days);
			}

			value = config.json ? JSON.stringify(value) : String(value);

			return (document.cookie = [
				config.raw ? key : encodeURIComponent(key),
				'=',
				config.raw ? value : encodeURIComponent(value),
				options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
				options.path    ? '; path=' + options.path : '',
				options.domain  ? '; domain=' + options.domain : '',
				options.secure  ? '; secure' : ''
			].join(''));
		}

		// read
		var decode = config.raw ? raw : decoded;
		var cookies = document.cookie.split('; ');
		var result = key ? undefined : {};
		for (var i = 0, l = cookies.length; i < l; i++) {
			var parts = cookies[i].split('=');
			var name = decode(parts.shift());
			var cookie = decode(parts.join('='));

			if (key && key === name) {
				result = converted(cookie);
				break;
			}

			if (!key) {
				result[name] = converted(cookie);
			}
		}

		return result;
	};

	config.defaults = {};

	$.removeCookie = function (key, options) {
		if ($.cookie(key) !== undefined) {
			// Must not alter options, thus extending a fresh object...
			$.cookie(key, '', $.extend({}, options, { expires: -1 }));
			return true;
		}
		return false;
	};

}));
