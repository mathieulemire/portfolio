//  author:   espaciomore
//  date:     20 april 2014

var localization = function(language){
  var lang = language || $('html').attr('lang');

  // substitute translation according to specified language 
  $('*[data-'+lang+']').each(function(e){
    var el = $(this);
    el.text( el.data(lang) );
  });
};