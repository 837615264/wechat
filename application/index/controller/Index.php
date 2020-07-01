<?php
namespace app\index\controller;
use app\wechat\common\Wx;
class Index
{
    public function index()
    {
        $wx = new Wx();
        $wx->createMenu();
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }
}
