jQuery(document).ready(function ($) {

  'use strict';

  $(document).find('a[href]').each(function(index, link){
    var href = $(this).attr('href');
    var regexp = /^https?:\/\/(akismet\.com|wordbench\.org|capitalp|hametuha|amazon|(.*\.)?wordpress\.org|itunes\.apple\.com|twitter\.com|www\.facebook.com|www\.instagram\.com|github\.com|wpdocs\.osdn\.jp|b\.hatena\.ne\.jp)/;
    if ( ! href.test(regexp) && href.test(/^https?:\/\//)) {
      var parts = href.split( '?' );
      var args = [];
      if ( 1 < parts.length ) {
        $.each(parts[1].split('&'), function(i, param){
          var vars = param.split('=');
          switch(param[0]){
            case 'utm_source':
            case 'utm_campaign':
            case 'utm_medium':
              // Do nothing.
              break;
            default:
              args.push(param);
              break;
          }
        });
      }
      if( href.test(/^https:\/\/(wordpress|jetpack|woocommerce)\.com/) ) {
        args.push('aff=4310');
      }
      $.each([
        'utm_source=capitalp',
        'utm_campaign=SponsorUs',
        'utm_medium=voluntary_link'
      ], function(j, u){
        args.push(u);
      });
      parts[1] = args.join('&');
      $(this).attr('href', parts.join('?'));
    }
  });


});
