<?php
include __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
$app = new Silex\Application();

$app->post('/put', function(Request $request) use ($app){
    $content = $request->request->get('content');
    $id = md5(time().uniqid());
    $fp = fopen(__DIR__.'/../data/'.$id,'w+');
    fwrite($fp,$content);

    $response = new Response();
    $response->headers->set('Content-Type','application/json');
    $response->setContent(json_encode(array('success'=>true,'id'=>$id)));
    return $response;
});
$app->get('/get/{id}', function($id) use($app){
    $file = __DIR__.'/../data/'.$id;
    $ret = array();
    $success = false;
    if(file_exists($file)){
        $content = file_get_contents($file);
        $ret['content'] = $content;
        $success = true;
    }
    $ret['success']=$success;
    $response = new Response();
    $response->headers->set('Content-Type','application/json');
    $response->setContent(json_encode($ret));
    return $response;    
});
$app->run();
