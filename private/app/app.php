<?php
namespace Portfolio;

abstract class App
{
  protected static $instance = null;
  protected static $routes = null;

  public static function build()
  {
    static::init();
    static::addRoutes();
    static::run();
  }

  public static function init( $prototype = '\\Silex\\Application')
  {
    if (static::$instance)
      return static::$instance;

    $app = new $prototype;

    $app['template_engine'] = $app->share(function(){
      $loader = new \Twig_Loader_Filesystem(__DIR__.'/../templates');
      return new \Twig_Environment($loader);
    });

    $app['conf'] = $app->share(function(){
      return json_decode(file_get_contents(__DIR__.'/conf.json'));
    });

    $app['mail'] = $app->protect(function($to,$sub,$msg){
      $headers  = 'MIME-Version: 1.0' . "\r\n";
      $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
      $headers .= 'From: cs@mailbot.com (MailBot)' . "\r\n";
      return mail( $to, $sub, $msg, $headers );
    });

    $app['loader'] = $app->protect(function($f=array(),$p='..'){
      $scripts = array();
      foreach ($f as $i => $filename) {      
        $scripts[] = file_get_contents(__DIR__.'/'.$p.'/'.$filename);
      }
      return implode("\r\n", $scripts);
    });

    static::$instance = $app;
    return static::$instance;
  }

  protected static function addRoutes()
  {
    $app = static::$instance;
    static::$routes = array(                
      array('method'=>'GET','name'=>'/','callback'=>function() use($app){
        $conf = $app['conf'];
        $loader = $app['loader'];
        $css = $loader(array('bootstrap.min.css','bootstrap.glyphicons.min.css','style.css'),'../style');
        $js = $loader(array('jquery-2.0.2-min.js','bootstrap.min.js','client.js'),'../client');
        $template_engine = $app['template_engine'];
        return $template_engine->render('index.html',array('conf'=>$conf,'css'=>$css,'js'=>$js));
      }),
      array('method'=>'GET','name'=>'/portfolio/{album}','callback'=>function($album) use($app){
        $conf = $app['conf'];
        $loader = $app['loader'];
        $css = $loader(array('style.css','image-slide.css'),'../style');
        $js = $loader(array('jquery-1.4.2-min.js','jquery.easing.1.3.js','image-slide.js'),'../client');
        $template_engine = $app['template_engine'];
        return $template_engine->render('portfolio.html',array(
          'conf'=>$conf,'css'=>$css,'js'=>$js,
          'album'=>'samana_20140411',
          'filename'=>'1_1024.jpg',
          'thumbnails'=>array('1_1024.jpg','2_1024.jpg','3_1024.jpg','2_1024.jpg','3_1024.jpg','2_1024.jpg','3_1024.jpg')
          )
        );
      }),      
      array('method'=>'POST','name'=>'/invite','callback'=>function(\Symfony\Component\HttpFoundation\Request $req) use($app){
        $rName = $req->get('rName');
        $rEmail = $req->get('rEmail');
        $email = $req->get('email');
        $loader = $app['loader'];
        $css = $loader(array('style.css'),'../style');
        $template_engine = $app['template_engine'];
        $mail = $app['mail'];
        $message = $template_engine->render('invitation.html',array('rName'=>$rName,'rEmail'=>$rEmail,'email'=>$email,'conf'=>$conf,'css'=>$css));
        $sent = $mail($email, $rName.' invited you to MailBot', $message);
        return new \Symfony\Component\HttpFoundation\Response($sent ? 'OK':'Mailing Problem', $sent ? 201:500);
      }),
      array('method'=>'POST','name'=>'/signup','callback'=>function(\Symfony\Component\HttpFoundation\Request $req) use($app){
        $email = $req->get('email');
        $name = $req->get('name');
        $loader = $app['loader'];
        $css = $loader(array('style.css'),'../style');
        $template_engine = $app['template_engine'];
        $mail = $app['mail'];
        $message = $template_engine->render('welcome.html',array('name'=>$name,'email'=>$email,'conf'=>$conf,'css'=>$css));
        $sent = $mail($email,'Welcome to MailBot', $message);
        return new \Symfony\Component\HttpFoundation\Response($sent ? 'OK':'Mailing Problem', $sent ? 201:500);
      })  
    );

    foreach (static::$routes as $i => $r) {
      switch ($r['method']) {
        case 'GET':
          $app->get($r['name'],$r['callback']);
          break;
        case 'POST':
          $app->post($r['name'],$r['callback']);
          break;
      }
    }
  }

  public static function run()
  {
    static::$instance->run();
  }
}