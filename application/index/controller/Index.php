<?php
namespace app\index\controller;
use app\wechat\common\Wx;
class Index
{
    public function index()
    {
        $wx = new Wx();
        $wx->responseMsg();
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }

    public function upload(){
        $wx = new Wx();
        $data = $wx->uploadMedia('image','./static/img/hotel.jpg');
        print_r($data);
    }

    public function getImg(){
        $wx = new Wx();
        $wx->getMedia('V3HEw_0-Iie2kHNDfQpKVIg5tRhuq_uUifYiiwas8pOb5Arn9j4pFWWdB5AQNANa');
    }
}
