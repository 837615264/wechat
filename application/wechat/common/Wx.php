<?php
namespace app\wechat\common;
use think\Exception;

define('TOKEN','rupan');
class Wx{
    private $appid;
    private $appsecret;

    function __construct($arr=array())
    {
        $this->appid=isset($arr['appid'])?$arr['appid']:'wx73b4a58dbcdf30ed';
        $this->appsecret=isset($arr['appsecret'])?$arr['appsecret']:'bcca006562fdf39fa48c22225b3fce35';
    }

    /**
     * 获取access_token
     * @return bool|mixed|string
     */
    public function access_token()
    {
        $filename='access_token';
        if(file_exists($filename) && (time()-filemtime($filename))<7200)
        {
            return file_get_contents($filename);
        }else{
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->appsecret}";
            $access_token=$this->curl($url,'GET');
            $access_token=json_decode($access_token,true)['access_token'];
            file_put_contents($filename,$access_token);
            return $access_token;
        }
    }

    /******************************************************************微信接入验证START******************************************************************/
    /**
     * 微信接入验证
     * @throws Exception
     */
    public function valid()
    {
        $echoStr=$_GET['echostr'];
        if($this->checkSignature())
        {
            echo $echoStr;
            exit;
        }
    }

    /**
     * 微信接入验证规则
     * @return bool
     * @throws Exception
     */
    public function checkSignature()
    {
        if(!defined('TOKEN'))
        {
            throw new Exception('TOKEN is not defined!');
        }
        $signature=$_GET['signature'];      //微信加密签名
        $timestamp=$_GET['timestamp'];      //时间戳
        $nonce=$_GET['nonce'];              //随机数
        $token=TOKEN;
        $array = array($token, $timestamp, $nonce);
        sort($array, SORT_STRING);
        $str = implode($array);
        $sha1_str=sha1($str);
        if($sha1_str==$signature)
        {
            return true;
        }else{
            return false;
        }
    }
    /******************************************************************微信接入验证END********************************************************************/

    /******************************************************网页微信授权Start********************************************************/

    /**
     * 获取网页授权access_token
     * @param $code
     * @return mixed|string
     */
    public function getAccessTokenForOauth($code)
    {
        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appid}&secret={$this->appsecret}&code={$code}&grant_type=authorization_code";
        $result=$this->curl($url,'GET');
        return json_decode($result,true);
    }

    /**
     * 刷新网页授权access_token
     * @param $refresh_token
     * @return mixed|string
     */
    public function refresh_token($refresh_token)
    {
        $url="https://api.weixin.qq.com/sns/oauth2/refresh_token?appid={$this->appid}&grant_type=refresh_token&refresh_token={$refresh_token}";
        $result=$this->curl($url,'GET');
        return json_decode($result,true);
    }

    /**
     * 拉取用户信息(需scope为 snsapi_userinfo)
     * @param $access_token
     * @param $openid
     * @return mixed|string
     */
    public function getUserInfo($access_token,$openid)
    {
        $url="https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang=zh_CN ";
        $result=$this->curl($url,'GET');
        return json_decode($result,true);
    }

    /**
     * 检验授权凭证（access_token）是否有效
     * @param $access_token
     * @param $openid
     * @return array|mixed|stdClass
     */
    public function checkAccessToken($access_token,$openid)
    {
        $url="https://api.weixin.qq.com/sns/auth?access_token={$access_token}&openid={$openid}";
        $result=$this->curl($url,'GET');
        return json_decode($result,true);
    }


    /****************************************************网页微信授权End*********************************************************/


    /**
     * 处理请求
     * $cookie_file = tempnam('./temp','coo');  创建cookie文件保存的位置
     * @param $url
     * @param $method
     * @param array $data
     * @param bool $setcookie
     * @param bool $cookie_file
     * @return mixed|string
     */
    function  curl($url,$method,$data=array(),$setcookie=false,$cookie_file=false){
        $ch = curl_init();//1.初始化
        curl_setopt($ch, CURLOPT_URL, $url); //2.请求地址
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);//3.请求方式
        //4.参数如下禁止服务器端的验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //伪装请求来源，绕过防盗
        //curl_setopt($ch,CURLOPT_REFERER,"http://wthrcdn.etouch.cn/");
        //配置curl解压缩方式（默认的压缩方式）
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Encoding:gzip'));
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.2.1; zh-cn; HUAWEI G610-U00 Build/HuaweiG610-U00) AppleWebKit/537.36 (KHTML, like Gecko)Version/4.0 MQQBrowser/5.0 Mobile Safari/537.36");
        //Mozilla/5.0 (Windows NT 10.0; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0
        //指明以哪种方式进行访问,利用$_SERVER['HTTP_USER_AGENT'],可以获取
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        if($method=="POST"){//5.post方式的时候添加数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if($setcookie==true){
            //如果设置要请求的cookie，那么把cookie值保存在指定的文件中
            //echo 1;die;
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        }else{
            //就从文件中读取cookie的信息
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $tmpInfo = curl_exec($ch);//获取html内容
        file_put_contents('./curl',$url.PHP_EOL.$tmpInfo);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $tmpInfo;
    }

    /**
     * 创建自定义菜单
     * @return mixed|string
     */
    public function createMenu()
    {
        $access_token=$this->access_token();
        $redirect_uri=urlencode('http://wx.bilibilii.cn/login.php');
        $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";
        $menu=' {
             "button":[
             {
                  "type":"view",
                  "name":"网页授权",
                  "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appid.'&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect"
              },
              {
                   "name":"菜单",
                   "sub_button":[
                   {
                       "type":"view",
                       "name":"我的博客",
                       "url":"http://blog.51neko.con/"
                    },
                    {
                       "type":"location_select",
                       "name":"地图",
                       "key":"2"
                    }]
               }]
         }';
        return $this->curl($url,'POST',$menu);
    }

    /**
     * 获取自定义菜单
     * @return mixed|string
     */
    public function getMenu()
    {
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/menu/get?access_token={$access_token}";
        return $this->curl($url,'GET');
    }

    /**
     * 删除自定义菜单
     * @return mixed|string
     */
    public function delMenu()
    {
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/menu/delete?access_token={$access_token}";
        return $this->curl($url,'GET');
    }

    /**
     * 生成换取二维码的票据
     * @param string|int $scene_id 票据id
     * @param string $type  是否临时二维码
     * @param int $expire   过期时间
     * @return mixed|string
     */
    public function ticket($scene_id,$type,$expire)
    {
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$access_token}";
        //临时二维码，参数为字符串
        if($type=='tmp'&&(is_string($scene_id)))
        {
            $data='{"expire_seconds": %s, "action_name": "QR_STR_SCENE", "action_info": {"scene": {"scene_str": "%s"}}}';
            $data=sprintf($data,$expire,$scene_id);
        }
        //临时二维码，参数为整形
        if($type=='tmp'&&(!is_string($scene_id)))
        {
            $data='{"expire_seconds": %s, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
            $data=sprintf($data,$expire,$scene_id);
        }
        //永久二维码，参数为字符串
        if($type!='tmp'&&(is_string($scene_id))){
            $data='{"expire_seconds": %s, "action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "%s"}}}';
            $data=sprintf($data,$expire,$scene_id);
        }
        //永久二维码，参数为整形
        if($type!='tmp'&&(!is_string($scene_id)))
        {
            $data='{"expire_seconds": %s, "action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
            $data=sprintf($data,$expire,$scene_id);
        }
        $ticket=$this->curl($url,'POST',$data);
        return json_decode($ticket,true)['ticket'];
    }

    /**
     * 获取二维码
     * @param string|int $scene_id 票据id
     * @param string $type  是否临时二维码
     * @param int $expire   过期时间
     * @return mixed|string
     */
    public function qrcode($scene_id,$type='tmp',$expire=604800)
    {
        $ticket=$this->ticket($scene_id,$type,$expire);
        $ticket=urlencode($ticket);
        $url="https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$ticket}";
        $qrcode=$this->curl($url,'GET');
        header('content-type:image/jpg');
        echo $qrcode;
    }

    /**
     * 接收用户消息并响应（发送消息）
     */
    public function responseMsg()
    {
        //接收客户端请求
        $postStr = file_get_contents('php://input');
        libxml_disable_entity_loader(true);     //禁止外部非法加载实体
        //解析xml数据
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        file_put_contents('./log',$postStr);
        $msgType=$postObj->MsgType;
        switch($msgType)
        {
            case 'event':
                if($postObj->Event == 'subscribe' or $postObj->Event == 'SCAN')    //扫码关注公众号事件
                {
                    $this->eventForSubscribe($postObj);
                }
                else if($postObj->Event == 'unsubscribe')       //取消关注事件
                {
                    $this->eventForUnsubscribe($postObj);
                }
                else if($postObj->Event == 'LOCATION')
                {
                    $this->eventForLocation($postObj);      //获取用户地理位置事件
                }
                else if($postObj->Event == 'CLICK')
                {
                    $this->eventForClick($postObj);     //点击菜单拉取消息时的事件
                }
                else if($postObj->Event == 'VIEW')
                {
                    $this->eventForView($postObj);     //点击菜单跳转链接时的事件
                }
                break;
            case 'text':
                $this->_doText($postObj);       //接收文本消息
                ;break;
            case 'image':
                $this->_doImage($postObj);      //接收图片消息
                ;break;
            case 'voice':
                $this->_doVoice($postObj);      //接收语音消息
                ;break;
            case 'video':
                $this->_doVideo($postObj);      //接收视频消息
                ;break;
            case 'shortvideo':
                $this->_doShortVideo($postObj);     //接收小视频消息
                ;break;
            case 'location':
                $this->_doLocation($postObj);       //接收位置消息
                ;break;
            case 'link':
                $this->_doLink($postObj);       //接收链接消息
                ;break;
        }
    }

    /*************************************************************接收普通消息START****************************************************************/

    /**
     * 接收文本消息
     * @param $postObj
     */
    public function _doText($postObj)
    {
        $content = trim($postObj->Content);
        if(mb_substr($content,0,2,'utf-8') == '附近')
        {
            //$content = mb_substr($content,2,mb_strlen($content,'utf-8'),'utf-8');
            $replyContent = $this->turingRobot($content);     //调用图灵机器人进行回复
            $this->replyText($postObj , $replyContent);
        }
        else{
            $replyContent = $this->turingRobot($content);     //调用图灵机器人进行回复
            $this->replyText($postObj , $replyContent);
        }
    }

    /**
     * 接收图片消息
     * @param $postObj
     */
    public function _doImage($postObj)
    {
        $toUser=$postObj->FromUserName;     //客户端账号
        $fromUser=$postObj->ToUserName;     //公众平台账号
        $time=time();       //当前时间
        $url=$postObj->PicUrl;      //图片地址
        $this->replyText($postObj , '斗图什么的还不擅长哦');
    }

    /**
     * 接收语音消息
     * @param $postObj
     */
    public function _doVoice($postObj)
    {
        $content = $postObj->Recognition;       //用户发送的语音识别结果
        $content = rtrim($content,'。');
        $str = '';
        if(!empty($content)){
            $result = $this->youdao($content);
            $str.= $result['english'];
            if(isset($result['uk']) && !empty($result['uk']))
            {
                $str .= " 英[{$result['uk']}]";
            }
            if(isset($result['us']) && !empty($result['us'])){
                $str .= " 美[{$result['us']}]";
            }
        }else{
            $str .= "抱歉，没有听清您说的话。";
        }
        $this->replyText($postObj , $str);
    }

    /**
     * 接收视频消息
     * @param $postObj
     */
    public function _doVideo($postObj){
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $mediaId = $postObj->MediaId;       //视频消息媒体id，可以调用获取临时素材接口拉取数据。
        $thumbMediaId = $postObj->ThumbMediaId;     //视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
        $this->replyText($postObj , '流量告急，不要发视频啦！');
    }

    /**
     * 接收小视频消息
     * @param $postObj
     */
    public function _doShortVideo($postObj){
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $mediaId = $postObj->MediaId;       //视频消息媒体id，可以调用获取临时素材接口拉取数据。
        $thumbMediaId = $postObj->ThumbMediaId;     //视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
        $this->replyText($postObj , '流量告急，不要发视频啦！');
    }

    /**
     * 接收位置信息
     * @param $postObj
     */
    public function _doLocation($postObj)
    {
        $x = $postObj->Location_X;        //纬度
        $y = $postObj->Location_Y;        //经度
        $scale = $postObj->Scale;       //地图缩放大小
        $label = $postObj->Label;       //地理位置信息
        $urls=$this->baiduMap($x,$y);
        $this->replyLocation($postObj,$urls);
    }

    /**
     * 接收链接消息
     * @param $postObj
     */
    public function _doLink($postObj){
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $title = $postObj->Title;       //消息标题
        $description = $postObj->Description;       //消息描述
        $url = $postObj->Url;       //消息链接
        $this->replyText($postObj , '主人说了，不可以打开不明链接！');
    }
    /*************************************************************接收普通消息END******************************************************************/

    /*************************************************************接收事件推送START******************************************************************/
    /**
     * 扫码关注公众号事件
     * @param $postObj
     */
    public function eventForSubscribe($postObj)
    {
        $toUser = $postObj->FromUserName;       //客户端账号
        $fromUser = $postObj->ToUserName;       //公众平台账号
        $createTime = $postObj->CreateTime;     //消息创建时间 （整型）
        $eventKey = isset($postObj->EventKey) ? $postObj->EventKey : '';        //事件KEY值，qrscene_为前缀，后面为二维码的参数值
        $ticket = isset($postObj->Ticket) ? $postObj->Ticket : '';      //二维码的ticket，可用来换取二维码图片
        if($eventKey && $postObj->Event == 'SCAN'){         //扫描带参数二维码,用户已关注时的事件推送
            //todo
        }
        else if($eventKey && $postObj->Event == 'subscribe'){       //扫描带参数二维码,用户未关注时，进行关注后的事件推送
            //todo
        }else{      //扫描未带参数二维码关注
            //todo
        }
        //当前时间
        $time=time();
        //回复内容
        $content='欢迎关注';
        $textTpl='<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        </xml>';
        $textTpl=sprintf($textTpl,$toUser,$fromUser,$time,$content);
        echo $textTpl;
    }

    /**
     * 取消关注公众号事件
     * @param $postObj
     */
    public function eventForUnsubscribe($postObj){
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $createTime = $postObj->CreateTime;       //消息创建时间 （整型）
    }

    /**
     * 获取用户地理位置事件
     * @param $postObj
     */
    public function eventForLocation($postObj)
    {
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $createTime = $postObj->CreateTime;       //消息创建时间 （整型）
        $latitude = $postObj->Latitude;       //纬度
        $longitude = $postObj->Longitude;     //经度
        $precision = $postObj->Precision;       //精度
    }

    /**
     * 点击菜单拉取消息时的事件
     * @param $postObj
     */
    public function eventForClick($postObj){
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $createTime = $postObj->CreateTime;       //消息创建时间 （整型）
        $eventKey = $postObj->EventKey;     //事件KEY值，与自定义菜单接口中KEY值对应
    }

    /**
     * 点击菜单跳转链接时的事件
     * @param $postObj
     */
    public function eventForView($postObj){
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $createTime = $postObj->CreateTime;       //消息创建时间 （整型）
        $eventKey = $postObj->EventKey;     //事件KEY值，设置的跳转URL
    }
    /*************************************************************接收事件推送END********************************************************************/

    /*************************************************************被动回复用户消息START********************************************************************/
    /**
     * 回复文本消息
     * @param $postObj
     * @param $replyContent     //回复的内容
     */
    public function replyText($postObj , $replyContent)
    {
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $time = time();       //消息创建时间 （整型）
        $textTpl =
        '<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        </xml>';
        $textTpl = sprintf($textTpl,$toUser,$fromUser,$time,$replyContent);
        echo $textTpl;
    }

    /**
     * 回复图片消息
     * @param $postObj
     * @param $mediaId 通过素材管理中的接口上传多媒体文件，得到的id
     */
    function replyImage($postObj , $mediaId)
    {
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $time = time();     //消息创建时间戳 （整型）
        $imageTpl =
                '<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[image]]></MsgType>
                <Image>
                <MediaId><![CDATA[%s]]></MediaId>
                </Image>
                </xml>';
        $imageTpl = sprintf($imageTpl,$toUser,$fromUser,$time,$mediaId);
        echo $imageTpl;
    }

    /**
     * 回复语音消息
     * @param $postObj
     * @param $mediaId 通过素材管理中的接口上传多媒体文件，得到的id
     */
    public function replyVoice($postObj , $mediaId){
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $time = time();     //消息创建时间戳 （整型）
        $voiceTpl =
            '<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[voice]]></MsgType>
                <Voice>
                <MediaId><![CDATA[%s]]></MediaId>
                </Voice>
                </xml>';
        $voiceTpl = sprintf($voiceTpl,$toUser,$fromUser,$time,$mediaId);
        echo $voiceTpl;
    }

    /**
     * 回复视频消息
     * @param $postObj
     * @param $mediaId 通过素材管理中的接口上传多媒体文件，得到的id
     * @param $title 视频消息的标题
     * @param $description 视频消息的描述
     */
    public function replyVideo($postObj , $mediaId , $title , $description){
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $time = time();     //消息创建时间戳 （整型）
        $videoTpl =
            '<xml>
              <ToUserName><![CDATA[%s]]></ToUserName>
              <FromUserName><![CDATA[%s]]></FromUserName>
              <CreateTime>%s</CreateTime>
              <MsgType><![CDATA[video]]></MsgType>
              <Video>
                <MediaId><![CDATA[%s]]></MediaId>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
              </Video>
            </xml>';
        $videoTpl = sprintf($videoTpl,$toUser,$fromUser,$time,$mediaId,$title,$description);
        echo $videoTpl;
    }

    /**
     * 回复音乐消息
     * @param $postObj
     * @param $thumbMediaId 缩略图的媒体id，通过素材管理中的接口上传多媒体文件，得到的id
     * @param string $title 音乐标题
     * @param string $description 音乐描述
     * @param string $musicUrl 音乐链接
     * @param string $HQ_musicUrl 高质量音乐链接，WIFI环境优先使用该链接播放音乐
     */
    public function replyMusic($postObj , $thumbMediaId , $title = '' , $description = '' , $musicUrl = '' , $HQ_musicUrl = ''){
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $time = time();     //消息创建时间戳 （整型）
        $musicTpl='<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[music]]></MsgType>
        <Music>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <MusicUrl><![CDATA[%s]]></MusicUrl>
        <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
        <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
        </Music>
        </xml>';
        $musicTpl=sprintf($musicTpl,$toUser,$fromUser,$time,$title,$description,$musicUrl,$HQ_musicUrl,$thumbMediaId);
        echo $musicTpl;
    }

    /**
     * 回复图文消息
     * @param $postObj
     * @param $articleCount 图文消息个数(当用户发送文本、图片、语音、视频、图文、地理位置这六种消息时，开发者只能回复1条图文消息,其余场景最多可回复8条图文消息)
     * @param array $data 图文数据(标题title,描述description,链接picUrl,跳转链接url)
     */
    public function replyNews($postObj , $articleCount = 1 , $data = array())
    {
        $toUser = $postObj->FromUserName;     //客户端账号
        $fromUser = $postObj->ToUserName;     //公众平台账号
        $msgType = $postObj->MsgType;       //消息类型
        $time = time();       //消息创建时间戳 （整型）
        $typeOne = ['text' , 'image', 'voice', 'video' , 'location'];
        if(in_array($msgType , $typeOne)){
            $articleCount = 1;
        }else{
            $articleCount = $articleCount > 8 ? 8 : $articleCount;
        }

        $newsTpl =
                '<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[news]]></MsgType>
                <ArticleCount>%s</ArticleCount>
                <Articles>%s</Articles>
                </xml>';
        $news = '';
        foreach($data as $k=>$v)
        {
            $news .=
                '<item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item>';
            $news=sprintf($news,$v['title'],$v['description'],$v['picUrl'],$v['url']);
        }

        $newsTpl=sprintf($newsTpl,$toUser,$fromUser,$time,$articleCount,$news);
        echo $newsTpl;
    }
    /*************************************************************被动回复用户消息END**********************************************************************/



    /**
     * 回复位置信息（图文）
     * @param $postObj
     * @param $urls
     */
    public function replyLocation($postObj,$urls)
    {
        //客户端账号
        $toUser=$postObj->FromUserName;
        //公众平台账号
        $fromUser=$postObj->ToUserName;
        //当前时间
        $time=time();
        //地理位置信息
        $label=$postObj->Label;
        $mapTpl='<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[news]]></MsgType>
                <ArticleCount>%s</ArticleCount>
                <Articles>
                <item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item>
                </Articles>
                </xml>';
        $mapTpl=sprintf($mapTpl,$toUser,$fromUser,$time,1,"{$label}附近的酒店","{$label}附近的酒店",'http://wx.51neko.com/static/img/hotel.jpg',$urls['hotel']);
        echo $mapTpl;
    }



    /**
     * 获取所有用户的openid
     * @param string $next_openid
     * @return array
     */
    public function getOpenId($next_openid='')
    {
        static $openid_list=array();
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/user/get?access_token={$access_token}&next_openid={$next_openid}";
        $open_id=$this->curl($url,'GET');
        $result=json_decode($open_id,true);
        $openid_list=array_merge($openid_list,$result['data']['openid']);
        $openid_list=array_unique($openid_list);
        if($result['count']==10000)
        {
            $this->getOpenId($result['next_openid']);
        }
        return $openid_list;
    }

    /**
     * 消息推送
     */
    public function push()
    {
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token={$access_token}";
        $openidList=$this->getOpenId();
        $data['touser']=$openidList;
        $data['mpnews']=['media_id'=>'SDD2S3SSWMrRFEKr3fYCKtGUYMX47VpWTD6616o3ASnZvG9Ix_HtCULfYlShudzf'];
        $data['msgtype']='mpnews';
        $data['send_ignore_reprint']=1;
        //$data['text']=['content'=>'再测试一下'];     //短时间内内容不能重复
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $res=$this->curl($url,'POST',$data);
        print_r($res);
        //SDD2S3SSWMrRFEKr3fYCKtGUYMX47VpWTD6616o3ASnZvG9Ix_HtCULfYlShudzf
    }

    /**
     * 新增临时素材
     * @param $type 文件类型:图片（image）、语音（voice）、视频（video）和缩略图（thumb）
     * @param $file 文件名
     * @return mixed
     */
    public function uploadMedia($type,$file)
    {
        $byte = filesize($file);
        $fileType = strtoupper(pathinfo($file,PATHINFO_EXTENSION));
        $KB = 1024;
        $MB = 1024 * 1024;

        $typeSize = [
            'image' => $MB * 10,
            'voice' => $MB * 2,
            'video' => $MB * 10,
            'thumb' => $KB * 64
        ];
        if(! isset($typeSize[$type])) throw new Exception('type is not found');
        if($byte > $typeSize[$type]) throw new Exception('the file is too large');
        if($type == 'image' && ! in_array($fileType , ['PNG' , 'JPG' , 'JPEG' , 'GIF'])) throw new Exception('unsupported image types');
        if($type == 'voice' && ! in_array($fileType , ['AMR' , 'MP3'])) throw new Exception('unsupported image types');
        if($type == 'video' && $fileType != 'MP4') throw new Exception('unsupported image types');
        if($type == 'thumb' && $fileType != 'JPG') throw new Exception('unsupported image types');

        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=ACCESS_TOKEN&type=TYPE";
        $data['access_token'] = $this->access_token();
        $data['type'] = $type;
        $fileObj = new \CURLFile($file);
        $data['media'] = $fileObj;
        $result = $this->curl($url,'POST',$data);
        if(isset($result['errcode'])) throw new Exception($result['errmsg']);
        return json_decode($result,true)['media_id'];
    }

    /**
     * 获取临时素材
     * @param $media_id
     */
    public function getMedia($media_id)
    {
        $access_token = $this->access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id={$media_id}";
        $result = $this->curl($url,'GET');
        if(isset($result['errcode'])) throw new Exception($result['errmsg']);
        if(isset(json_decode($result)['video_url'])){
            return json_decode($result)['video_url'];
        }else{
            return $result;
        }
    }

    /**
     * 上传图文消息素材
     * @param $num
     */
    public function uploadNews($num)
    {
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token={$access_token}";
        /*******************************数据库操作*************************************/
        $pdo=new PDO('mysql:host=47.93.55.244;dbname=wechat','root','root');
        $sql="SELECT * FROM news ORDER BY id DESC LIMIT {$num}";
        $data=$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $news='{"articles":[';
        foreach($data as $k=>$v)
        {
            $newInfo['thumb_media_id']=$v['media_id'];
            $newInfo['author']=$v['author'];
            $newInfo['title']=$v['title'];
            $newInfo['content_source_url']=$v['source_url'];
            $newInfo['content']=$v['content'];
            $newInfo['digest']=$v['intro'];
            $newInfo['show_cover_pic']=$v['is_show'];
            $news.=json_encode($newInfo,JSON_UNESCAPED_UNICODE).',';
        }
        $news=rtrim($news,',');
        $news.=']}';
        /*************************************TheEnd************************************/
        $result=$this->curl($url,'POST',$news);
        return json_decode($result,true)['media_id'];
    }



    /***********************************************************第三方START*************************************************************/
    /**
     * 图灵机器人
     * @param $msg
     */
    private function turingRobot($msg)
    {
        $key='401caa7df7be4a4582667fed405937de';
        $encode = mb_detect_encoding($msg, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
        $info = mb_convert_encoding($msg, 'UTF-8', $encode);
        $userId = 1;
        $url = "http://www.tuling123.com/openapi/api?key={$key}&info={$info}&userid={$userId}";
        $result = $this->curl($url,'POST');
        return json_decode($result,true)['text'];
    }

    /**
     * 有道翻译
     * @param $content
     */
    public function youdao($content,$status=true)
    {
        static $data;
        $appkey='7b1c47448c5892ba';     //appkey
        $key='D4xLwfEWN4coQXswgvMtBdEDC37Y7FPP';    //密钥
        $salt=rand(10000,99999);
        $time=strtotime("now");

        $len = mb_strlen($content,'utf-8');
        $input = $len <= 20 ? $content : (mb_substr($content, 0, 10) . $len . mb_substr($content, $len - 10, $len));
        $sign = hash("sha256", $appkey.$input.$salt.$time.$key);
        $content = rawurlencode($content);
        $url="https://openapi.youdao.com/api?q={$content}&from=auto&to=EN&appKey={$appkey}&salt={$salt}&sign={$sign}&signType=v3&curtime={$time}";
        $result=$this->curl($url,'GET');
        $result=json_decode($result,true);
        if(isset($result['translation'][0])&&!isset($result['basic']['uk-phonetic'])&&$status)
        {
            $data['english']=$result['translation'][0];
            $this->youdao($result['translation'][0],false);
        }
        if(isset($result['translation'][0])&&isset($result['basic']['uk-phonetic']))
        {
            $data['english']=$result['translation'][0];
            $data['uk']=isset($result['basic']['uk-phonetic']) ? $result['basic']['uk-phonetic'] : '';
            $data['us']=isset($result['basic']['us-phonetic']) ? $result['basic']['us-phonetic'] : '';
        }
        return $data;
    }

    /**
     * 百度地图（检索）
     * @param $msg
     * @return array
     */
    public function baiduMap($x,$y)
    {
        $hotelUrl="http://api.map.baidu.com/place/search?query=酒店&location={$x},{$y}&radius={$x},{$y}&output=html&ak=fDzBUTlM1CzfzjVL7ZDExgzOnZMak6QY";
        $bankUrl="http://api.map.baidu.com/place/search?query=银行&location={$x},{$y}&radius={$x},{$y}&output=html&ak=fDzBUTlM1CzfzjVL7ZDExgzOnZMak6QY";
        $hospitalUrl="http://api.map.baidu.com/place/search?query=医院&location={$x},{$y}&radius={$x},{$y}&output=html&ak=fDzBUTlM1CzfzjVL7ZDExgzOnZMak6QY";
        return ['hotel'=>$hotelUrl,'bank'=>$bankUrl,'hospital'=>$hospitalUrl];
    }
    /***********************************************************第三方END*************************************************************/
}