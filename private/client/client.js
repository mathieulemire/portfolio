$(document).ready(function(){
  // show text
  localization();

  $('.localization').on('click',function(e){
    var el = $(this);
    var trans = el.data('lang');
    var lang = $('html').attr('lang');
    
    el.attr('href','#lang-'+trans);
    el.data('lang',lang);
    el.text(lang.toUpperCase());
    $('html').attr('lang',trans);
    
    localization(trans);
  });
});