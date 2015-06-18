<?php
include __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
$app = new Silex\Application();

$app->post('/put', function(Request $request) use ($app){
    $content = $request->getContent();
    $json = json_decode($content,true);
    $content = $json['content'];
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
    $response->headers->set('Access-Control-Allow-Methods','GET,OPTIONS,PUT,DELETE');
    $response->headers->set('Access-Control-Allow-Origin','*');
    $response->headers->set('Access-Control-Allow-Credential',"true");

    $response->setContent(json_encode($ret));
    return $response;    
});

$app->get('/get/thumbnail/{id}', function($id) {
    $file = __DIR__.'/../data/'.$id;
    if(!file_exists($file)) {
        return (new Response())->setStatusCode(404);
    }
    $file_thumbnail = __DIR__.'/../data/thumbnail_'.$id;
    if(!file_exists($file_thumbnail)) {
        $imageString = base64_decode(file_get_contents($file));
        $image = imagecreatefromstring($imageString);
        list($width,$height, ) = getimagesizefromstring($imageString);
        $newWidth = 100;
        $newHeight = 100*$height/$width;
        $destinationImage = imagecreatetruecolor($newWidth,$newHeight);
        imagecopyresampled ( $destinationImage , $image , 0 , 0 , 0 , 0 , $newWidth , $newHeight , $width , $height );
        imagejpeg($destinationImage,$file_thumbnail);       
    }
    $response = new Response();
    $response->setContent(file_get_contents($file_thumbnail));
    $response->setStatusCode(200);
    $response->headers->set('Content-Type','image/jpeg');
    $response->headers->set('Access-Control-Allow-Methods','GET,OPTIONS,PUT,DELETE');
    $response->headers->set('Access-Control-Allow-Origin','*');
    $response->headers->set('Access-Control-Allow-Credential',"true");


    return $response; 

});
$app->run();
