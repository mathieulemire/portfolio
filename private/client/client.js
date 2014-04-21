$(document).ready(function(){
  localization();
  $('.localization').on('click',function(e){
    var el = $(this);
    var trans = el.data('lang');
    var lang = $('html').attr('lang');
    // set new language
    el.attr('href','#lang-'+trans);
    el.data('lang',lang);
    el.text(lang.toUpperCase());
    $('html').attr('lang',trans);
    // replace translations
    localization(trans);
  });
});