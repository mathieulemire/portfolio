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

    $app['css'] = $app->protect(function($filename='style.css'){
      $scripts = array(
          file_get_contents(__DIR__.'/../style/bootstrap.min.css'),
          file_get_contents(__DIR__.'/../style/bootstrap.glyphicons.min.css'),
          file_get_contents(__DIR__.'/../style/media.css'),
          file_get_contents(__DIR__.'/../style/'.$filename)
        );
      return implode("\r\n", $scripts);
    });

    $app['js'] = $app->protect(function($filename='client.js'){
      $scripts = array(
          file_get_contents(__DIR__.'/../client/jquery-2.0.2-min.js'),
          file_get_contents(__DIR__.'/../client/bootstrap.min.js'),
          file_get_contents(__DIR__.'/../client/'.$filename)
        );
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
        $css = $app['css'];
        $js = $app['js'];
        $template_engine = $app['template_engine'];
        return $template_engine->render('index.html',array('conf'=>$conf,'css'=>$css(),'js'=>$js().$js('facebook.js')));
      }),
      array('method'=>'GET','name'=>'/signup','callback'=>function(\Symfony\Component\HttpFoundation\Request $req) use($app){
        $rName = $req->get('rName');
        $rEmail = $req->get('rEmail');
        $email = $req->get('email');
        $conf = $app['conf'];
        $css = $app['css'];
        $template_engine = $app['template_engine'];
        return $template_engine->render('signup.html',array('rName'=>$rName,'rEmail'=>$rEmail,'email'=>$email,'conf'=>$conf,'css'=>$css()));
      }),
      array('method'=>'POST','name'=>'/invite','callback'=>function(\Symfony\Component\HttpFoundation\Request $req) use($app){
        $rName = $req->get('rName');
        $rEmail = $req->get('rEmail');
        $email = $req->get('email');
        $conf = $app['conf'];
        $css = $app['css'];
        $template_engine = $app['template_engine'];
        $mail = $app['mail'];
        $message = $template_engine->render('invitation.html',array('rName'=>$rName,'rEmail'=>$rEmail,'email'=>$email,'conf'=>$conf,'css'=>$css()));
        $sent = $mail($email, $rName.' invited you to MailBot', $message);
        return new \Symfony\Component\HttpFoundation\Response($sent ? 'OK':'Mailing Problem', $sent ? 201:500);
      }),
      array('method'=>'POST','name'=>'/signup','callback'=>function(\Symfony\Component\HttpFoundation\Request $req) use($app){
        $email = $req->get('email');
        $name = $req->get('name');
        $conf = $app['conf'];
        $css = $app['css'];
        $template_engine = $app['template_engine'];
        $mail = $app['mail'];
        $message = $template_engine->render('welcome.html',array('name'=>$name,'email'=>$email,'conf'=>$conf,'css'=>$css()));
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