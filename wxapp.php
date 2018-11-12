<?php
/**
 * 旺铺点餐模块小程序接口定义
 *

 * @author 武汉志汇科技

 * @url pintuan
 */
ini_set("memory_limit","500M");
defined('IN_IA') or exit('Access Denied');
header("Access-Control-Allow-Origin: *");
class pintuanModuleWxapp extends WeModuleWxapp {
    public function doPageSystem(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        if($res['gs_img']){
            if(strpos($res['gs_img'],',')){
                $res['gs_img']= explode(',',$res['gs_img']);
            }else{
                $res['gs_img']=array(
                    0=>$res['gs_img']
                );
            }
        }
        $res['attachurl']=$_W['attachurl'];
        echo json_encode($res);
    }
    //url(七牛)
    public function doPageUrl(){
        global $_GPC, $_W;
        echo $_W['attachurl'];
    }
    //获取openid
    public function doPageOpenid(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        $code=$_GPC['code'];
        $appid=$res['appid'];
        $secret=$res['appsecret'];
        // echo $appid;die;
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$secret."&js_code=".$code."&grant_type=authorization_code";
        function httpRequest($url,$data = null){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            if (!empty($data)){
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //执行
            $output = curl_exec($curl);
            curl_close($curl);
            return $output;
        }
        $res=httpRequest($url);
        print_r($res);
    }
    /////////////////////////////////////////////////////////
    //我的接口
    //保存用户的openid
    public function doPageSavaopenid(){
        global $_GPC, $_W;
        $openid=$_GPC['openid'];
        $uid=$_GPC['uid'];
        $sdata=array();
        $data=array();
        $sdata['openid']=$openid;
        if(empty($openid)){
            $res=0;
        }else{
            $res=pdo_update('pintuan_user',$sdata,array('u_id'=>$uid));
        }
        if ($res){
            $data['code']=1;
            $data['msg']='保存openid成功';
        }else{
            $data['code']=2;
            $data['msg']='openid已存在了';
        }
        echo json_encode($data);

    }
    /// 栏目与地区接口
    public function doPagegetareaorlanmu(){
        global $_GPC, $_W;
        $getaid=$_GPC['cid'];
        $areeres=pdo_getall('pintuan_areatype',array('uniacid'=>$_W['uniacid']));
        if (empty($getaid)){
            $getaid=$areeres[0]['Cid'];
        }
        $lanmures=pdo_getall('pintuan_lanmu',array('uniacid'=>$_W['uniacid'],'tid'=>$getaid,'HasNewItems'=>0));
        $data=array();
        $Data=array();
        if($areeres&&$lanmures){
            $data['code']=1;
            $data['CategoryList']=$areeres;
            foreach ($lanmures as $k=>$v){
                $lanmures[$k]['Times']=0;
                $lanmures[$k]['ActivityType']='拼货';
                $lanmures[$k]['StartTime']=$this->getopentime();
                $lanmures[$k]['ToTime']=$this->getclosetime();
            }
            $data['ActivityList']=$lanmures;
            $Data['Data']=$data;

        }
        echo json_encode($Data);
    }
    //获取轮播图
    public function doPagegetbanner(){
        global $_GPC, $_W;
        $getaid=$_GPC['valueID'];//地区id
        if (empty($getaid)){
            $getaid=1;
        }
        $bannerres=pdo_getall('pintuan_banner',array('uniacid'=>$_W['uniacid'],'aid'=>$getaid));
        $data=array();
        if($bannerres){
            $data['msg']='success';
            $data['code']=1;
            $data['Data']['ADList']=$bannerres;
        }else{
            $data['msg']='faill';
            $data['code']=0;
            $data['Data']['ADList']=$bannerres;
        }
        echo json_encode($data);

    }

    //栏目信息+栏目下商品
    public function doPagegetlanmu(){
        global $_GPC, $_W;
        $getlid=$_GPC['QsID'];//专栏id
        $ordernum=$_GPC['ordernum'] ? $_GPC['ordernum'] : 0;
        $getstate=$_GPC['state'];
        //栏目信息
        $lanmu=pdo_get('pintuan_lanmu',array('QsID'=>$getlid,'uniacid'=>$_W['uniacid']));
        $alldata=array();
        $info=array();//专场信息
        $info['ID']=$lanmu['QsID'];
        $info['ChengTuanCount']=5;
        $info['Name']=$lanmu['Name'];
        $info['Description']=$lanmu['Description'];
        $info['OpenStatu']='开拼中';
        $info['Part1Title']='本期新款';
        $info['Part2Title']='近期好货';
        $info['ShopUserID']=$lanmu['ShopUserID'];
        $info['ShopUserName']=$lanmu['ShopUserName'];
        $info['AppCover']=$lanmu['AppCover'];
        $info['Summary']=$lanmu['Content'];
        $info['ShowCoinPayIcon']=false;

        $info['StartTime']=$this->getopentime();
        $info['ToTime']=$this->getclosetime();
        $info['VisitResult']=array();
        $SortMenus=array(
            '0'=>array('Title'=>'综合','Value'=>0,'selected'=>true),
            '1'=>array('Title'=>'销量','Value'=>1,'selected'=>true),
            '2'=>array('Title'=>'价格高','Value'=>4,'selected'=>true),
            '3'=>array('Title'=>'价格低','Value'=>5,'selected'=>false),
            '4'=>array('Title'=>'筛选','Value'=>20,'selected'=>true),
        );
        $alldata['Data']['Info']=$info;
        $alldata['Data']['Info']['SortMenus']=$SortMenus;
        //商品列表信息
        //默认综合，分页
        //页码数
        $indexnum=$_GPC['pageIndex']-1;
        //每页数量
        $everynum=$_GPC['pagesize'];
         //只需要列表需要分页
        //if($getstate==0){
           $limits= " limit " . $everynum * $indexnum . ',' . $everynum;
       // }else{
       // //    $limits='';
       // }
     $filterValues=htmlspecialchars_decode($_GPC['filterValues']);
        $filterValuesarr=json_decode($filterValues,true);
        $MaxPrice=$filterValuesarr['MaxPrice']?$filterValuesarr['MaxPrice']:100000;
        $MinPrice=$filterValuesarr['MinPrice']?$filterValuesarr['MinPrice']:0;
        $goodtype=$filterValuesarr['Params'][0]['Values'];//是一个以逗号隔开的字符串
        $bighomeids=$filterValuesarr['Params'][1]['Values'];//所在大厦
        if(empty($goodtype)){
            //分类为空
            if(empty($bighomeids)){
                //大厦为空
                $sxwhere=" and Price>=".$MinPrice.' and Price <='.$MaxPrice;
            }else{
                $sxwhere=" and Price>=".$MinPrice.' and Price <='.$MaxPrice.' and StallsName in ('.$bighomeids.')';
            }
        }else{
            if(empty($bighomeids)){
                $sxwhere=" and Price>=".$MinPrice.' and Price <='.$MaxPrice.' and tid in ('.$goodtype.')';
            }else{
                $sxwhere=" and Price>=".$MinPrice.' and Price <='.$MaxPrice.' and tid in ('.$goodtype.')'.' and StallsName in ('.$bighomeids.')';
            }
        }
        switch ($ordernum)
        {
            case 0:
                //综合
                $orders=$sxwhere." order by (Price+TotalQty) desc".$limits;
                break;
            case 1:
                //销量
                $orders=$sxwhere." order by TotalQty desc".$limits;
                break;
            case 4:
                //价格高
                $orders=$sxwhere." order by Price desc".$limits;
                break;
            case 5:
                $orders=$sxwhere." order by Price asc".$limits;
                //价格低
                break;
            //随意;
        }
        //$where="WHERE uniacid=:uniacid and isdelete=0 and Statu=1 and zid=";
        //$data[':uniacid']=$_W['uniacid'];
        // $sql="select * from " . tablename("pintuan_goodmy") .$where.$getlid.$orders;
        //如果是搜索进来的
        $keyword=$_GPC['key'];
       
        if ($getstate==1){//搜索的
            $where="WHERE uniacid=:uniacid and isdelete=0 and Statu=1 and Title LIKE  concat('%', :name,'%') ";
            $data[':name']=$keyword;
            $data[':uniacid']=$_W['uniacid'];
            $sql="select * from " . tablename("pintuan_goodmy") .$where.$orders;
            $alldata['Data']['SortMenus']=$SortMenus;
            $alldata['Data']['Part1Title']='本期新款';
            $alldata['Data']['Part2Title']='近期好货';
            $alldata['Data']['PassItems']=array();
            $alldata['Data']['ShopList']=array();
        }else if($getstate==0){//普通的
            $where="WHERE uniacid=:uniacid and isdelete=0 and Statu=1 and zid=";
            $data[':uniacid']=$_W['uniacid'];
            $sql="select * from " . tablename("pintuan_goodmy") .$where.$getlid.$orders;
        }else if($getstate==2){//分类
            $gettid=$_GPC['rid'];
            if ($gettid==0){
                $where="WHERE uniacid=:uniacid and isdelete=0 and Statu=1";
                $data[':uniacid']=$_W['uniacid'];
                $sql="select * from " . tablename("pintuan_goodmy") .$where.$orders;
            }else{
                $where="WHERE uniacid=:uniacid and isdelete=0 and Statu=1 and tid=";
                $data[':uniacid']=$_W['uniacid'];
                $sql="select * from " . tablename("pintuan_goodmy") .$where.$gettid.$orders;
            }
            $alldata['Data']['SortMenus']=$SortMenus;
            $alldata['Data']['Part1Title']='本期新款';
            $alldata['Data']['Part2Title']='近期好货';
            $alldata['Data']['PassItems']=array();
            $alldata['Data']['ShopList']=array();
        }
        $allgood=pdo_fetchall($sql,$data);
        foreach ($allgood as $k=>$v){
            $allgood[$k]['Discount']=0;
            $allgood[$k]['IsSaleOut']=false;
            $allgood[$k]['IsShowStatuIcon']=false;
            $allgood[$k]['ChengTuanCount']=5;
            if(empty($v['Videos'])){
                $allgood[$k]['HasVideo']=0;
            }else{
                $allgood[$k]['HasVideo']=1;
            }
            $allgood[$k]['OriPrice']=$v['Price'];
            $allgood[$k]['Statu']=$v['Statu']==1?'已上架':'已下架';
            $allgood[$k]['tips']="已拼".$v['DealCount']."件,我也要拼！";
        }
        $alldata['Data']['NewItems']=$allgood;
      
        echo json_encode($alldata);
    }
    //筛选
    public function doPageFindneed(){
        global $_GPC, $_W;
        $getQsID=$_GPC['QsID'];
        $getstate=$_GPC['state'];
        $data=array();
        $panerl=['商品分类','大厦筛选','价格区间','货期情况'];
        if ($getstate==0){//栏目下的筛选
            //获取指定栏目下的所有商品分类id
            $getalltid=pdo_getall('pintuan_goodmy', array('zid' => $getQsID), array('tid'));
            //获取指定栏目下的所有商品所在大厦id
            $StallsName=pdo_getall('pintuan_goodmy', array('zid' => $getQsID), array('StallsName'));
        }else if ($getstate==1){//搜索里面的筛选
            //所有商品分类id
            $getalltid=pdo_getall('pintuan_goodmy', array(), array('tid'));
            $data['Data']['sqlinfo']=$getalltid;
            //所有商品所在大厦id
            $StallsName=pdo_getall('pintuan_goodmy', array(), array('StallsName'));
        }else{//分类里面的筛选

        }
        $allid=array();
        $allbigid=array();
        foreach ($getalltid as $k=>$v){
            array_push($allid,$v['tid']);
        }
        foreach ($StallsName as $k=>$v){
            array_push($allbigid,$v['StallsName']);
        }
        $uniqueid=array_unique($allid);
        $uniquebigid=array_unique($allbigid);
        $idandname=array();
        $bidandhname=array();
        foreach ($uniqueid as $k=>$v){
            $IDandName=pdo_get('pintuan_typetwomy', array('ID' => $v), array('ID','Name'));
            array_push($idandname,$IDandName);

        }
        foreach ($uniquebigid as $k=>$v){
            $IDandName=pdo_fetch("SELECT bid as ID, hname as Name FROM ".tablename('pintuan_bighome')." WHERE bid = :uid LIMIT 1", array(':uid' => $v));
            array_push($bidandhname,$IDandName);
        }

        foreach ($idandname as $k=>$v){
            $idandname[$k]['selected']=false;
        }
        foreach ($bidandhname as $k=>$v){
            $bidandhname[$k]['selected']=false;
        }
        //分类
        $data['Data']['Panels'][0]['Name']=$panerl[0];
        $data['Data']['Panels'][0]['Panels']=$idandname;
        $data['Data']['Panels'][0]['choose']=false;
        $data['Data']['Panels'][0]['selected']=true;
        $data['Data']['Panels'][0]['TypeID']=1;
        //大厦
        $data['Data']['Panels'][1]['Name']=$panerl[1];
        $data['Data']['Panels'][1]['Panels']=$bidandhname;
        $data['Data']['Panels'][1]['choose']=false;
        $data['Data']['Panels'][1]['selected']=true;
        $data['Data']['Panels'][1]['TypeID']=2;
        //价格范围
      $price=array(
            ['ID'=>1,'Name'=>"0-50",'selected'=>false],
            ['ID'=>2,'Name'=>"50-200",'selected'=>false],
            ['ID'=>3,'Name'=>"200-500",'selected'=>false]
        );
        $data['Data']['Panels'][2]['Name']=$panerl[2];
        $data['Data']['Panels'][2]['Panels']=$price;
        $data['Data']['Panels'][2]['choose']=false;
        $data['Data']['Panels'][2]['selected']=true;
        $data['Data']['Panels'][2]['TypeID']=3;
        //货期情况
       $huoqi=array(
            ['ID'=>1,'Name'=>"现货",'selected'=>false],
            ['ID'=>2,'Name'=>"排单7天内",'selected'=>false],
            ['ID'=>3,'Name'=>"排单15天内",'selected'=>false]
        );
        $data['Data']['Panels'][3]['Name']=$panerl[3];
        $data['Data']['Panels'][3]['Panels']=$huoqi;
        $data['Data']['Panels'][3]['choose']=false;
        $data['Data']['Panels'][3]['selected']=true;
        $data['Data']['Panels'][3]['TypeID']=4;
        echo json_encode($data);
    }
    //获取商品详细
    public function doPagegetdetailgood(){
        global $_GPC, $_W;
        $gid=$_GPC['gID'];//商品id
        $uid=$_GPC['uid'];//用户id
        $islike=pdo_get('pintuan_mylike',array('gid'=>$gid,'uid'=>$uid,'uniacid'=>$_W['uniacid']));
        $good=pdo_get('pintuan_goodmy',array('gID'=>$gid,'uniacid'=>$_W['uniacid']));
        $alldata=array();
        $Activity=array();//专场信息
        $Activity['ChengTuanCount']=1;
        $Activity['IsStart']=true;
        $Activity['ShowCoinPayIcon']=false;
        $Activity['QsID']=$good['zid'];//栏目id
        $Activity['TotalSaleCount']=$good['TotalQty'];
        $Activity['TransCount']=$good['DealCount'];
        $Activity['StartTime']=$this->getopentime();
        $Activity['EndTime']=$this->getclosetime();
        $alldata['Data']['Activity']=$Activity;

        $ButtomBigButtons=array(
            '0'=>array('action'=>'拼单','isEnable'=>true,'isPoint'=>true,'title'=>'我要拿货','type'=>'button')
        );

        $alldata['Data']['ButtomBigButtons']=$ButtomBigButtons;
        $ButtomSmallButtons=array(
            '0'=>array('action'=>'分享','isEnable'=>false,'isPoint'=>false,'title'=>'分享','type'=>'button'),
            '1'=>array('action'=>'收藏','isEnable'=>true,'isPoint'=>false,'title'=>'收藏','type'=>'button'),
            '2'=>array('action'=>'拿货车','isEnable'=>true,'isPoint'=>false,'title'=>'拿货车','type'=>'button'),
        );
        $alldata['Data']['ButtomSmallButtons']=$ButtomSmallButtons;

        $Buttons=array(
            '0'=>array('action'=>'补货','isEnable'=>true,'isPoint'=>false,'title'=>'我要补货','type'=>'button')
        );
        $alldata['Data']['Buttons']=$Buttons;
        $alldata['Data']['Name']=$good['Title'];
        $alldata['Data']['CanDownLoadPicAndVideo']=true;
        $alldata['Data']['Cover']=$good['Itemcover'];
        $alldata['Data']['coverurl']=$good['Itemcover'];
        $alldata['Data']['Discount']='';
        $alldata['Data']['DisplayStatu']='新款';
        if($islike){
            $alldata['Data']['IsFavorite']=true;
        }else{
            $alldata['Data']['IsFavorite']=false;
        }
        $alldata['Data']['IsTodayDeliver']=false;
        $alldata['Data']['IsYuGao']=false;
        $alldata['Data']['IsShowShareBtn']=true;
        $alldata['Data']['ItemID']=$good['gID'];
        $alldata['Data']['Price']=$good['Price'];
        $alldata['Data']['OriPrice']=$good['OriPrice'];
        $alldata['Data']['ItemStatu']='已上架';
        $alldata['Data']['Color']=$good['Colors'];
        $alldata['Data']['Size']=$good['Sizes'];
        $alldata['Data']['Videos']=$good['Videos'];
        //获取地区
        $gettid=pdo_get('pintuan_lanmu', array('QsID' => $good['zid']), array('fid'));
        $Name=pdo_get('pintuan_warehome', array('fid' => $gettid['fid']), array('Name'));
        $endtime=date("m月d日",time());
        $preendtime=date("m月d日",time()+604800);
        $alldata['Data']['endmsg']="明天（".$endtime."）12点结束";
        $alldata['Data']['preendmsg']="排单6~10天，预计".$preendtime."内可发货，如遇档口改期，延3～5天";
        $alldata['Data']['sendaddress']=$Name['Name'];

        $img=explode(',',$good['Images']);
        $alldata['Data']['Images']=$img;
        $tags=explode('/',$good['Tag']);
        $alldata['Data']['Tags']=$tags;
        //购物车部分
        $getcolors=explode('/',$good['Colors']);
        $getsizes=explode('/',$good['Sizes']);
        $Sizes=array();
        $SizeList=array();
        foreach ($getsizes as $k=>$v){
            $Sizes[$v]=2000;
            $SizeList[$k]['Size']=$v;
            $SizeList[$k]['Stock']=2000;
            $SizeList[$k]['qty']=0;
        }
        //每种颜色下的所有信息
        foreach ($getcolors as $k=>$v){
            $alldata['Data']['Products'][]['Color']=$v;
            $alldata['Data']['Products'][$k]['Sizes']=$Sizes;
            $alldata['Data']['Products'][$k]['SizeList']=$SizeList;
        }
        echo json_encode($alldata);

    }
    //用户登录
    public function doPageLogin(){
        global $_GPC, $_W;
        $uid=$_GPC['uid'];
        $phone=$_GPC['phone'];
        $psw=$_GPC['psw'];
        $data=array();
        if(!empty($uid)){
            $res=pdo_get('pintuan_user',array('u_id'=>$uid,'uniacid'=>$_W['uniacid']));
            if ($phone==$res['phone']){
                $data['code']=1;
                $data['msg']='检验登录成功';
                $data['info']=$res;
            }else{
                $data['code']=0;
                $data['msg']='检验登录失败';
                $data['info']=array();
            }
            echo json_encode($data);
        }else if (!empty($phone)){
            $res=pdo_get('pintuan_user',array('phone'=>$phone,'psw'=>$psw,'uniacid'=>$_W['uniacid']));
            if ($res){
                $data['code']=1;
                $data['msg']='用户登录成功';
                $data['info']=$res;
            }else{
                $data['code']=0;
                $data['msg']='用户登录失败';
                $data['info']=array();
            }
            echo json_encode($data);
        }else{
            $data['code']=0;
            $data['msg']='手机号不可为空，登录失败';
            echo json_encode($data);
        }
    }
    //用户注册和找回密码
    public function doPageRegorFind(){
        global $_GPC, $_W;
        $getphone=$_GPC['phone'];
        $getpsw=$_GPC['psw'];
        $data=array();
        $res=pdo_get('pintuan_user',array('phone'=>$getphone,'uniacid'=>$_W['uniacid']));
        if ($res){//找回密码，也就是重新设置密码
            $cdata=array();
            $cdata['psw']=$getpsw;
            $cres=pdo_update('pintuan_user',$cdata,array('phone'=>$getphone));
            if ($cres){
                $data['code']=1;
                $data['msg']='找回密码成功';
                echo json_encode($data);
            }else{
                $data['code']=0;
                $data['msg']='找回密码失败';
                echo json_encode($data);
            }
        }else{
            $inserdata=array();
            $inserdata['phone']=$getphone;
            $inserdata['psw']=$getpsw;
            $inserdata['uniacid']=$_W['uniacid'];
            $inserdata['username']=$this->randName(7);
            $res=pdo_insert('pintuan_user',$inserdata);
            if ($res){
                $data['code']=1;
                $data['msg']='注册成功';
                echo json_encode($data);
            }else{
                $data['code']=0;
                $data['msg']='注册失败';
                echo json_encode($data);
            }
        }

    }
   
    //短信验证码,聚合或者腾讯云的或者阿里云的
    public function doPageSmscode(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
        $resarr=array();
        if($res['item']==1){
            $tpl_id=$res['tpl_id'];
            $tel=$_GPC['tel'];
            $code=$_GPC['code'];
            $key=$res['appkey'];
            $url = "http://v.juhe.cn/sms/send?mobile=".$tel."&tpl_id=".$tpl_id."&tpl_value=%23code%23%3D".$code."&key=".$key;
            $data=file_get_contents($url);
            print_r($data);
        }
        if($res['item']==2){
            include IA_ROOT.'/addons/pintuan/txsms/SmsSingleSender.php';
            $appid = $res['appid'];; // 1400开头
            $appkey = $res['tx_appkey'];;
            $phoneNumbers = $_GPC['tel'];;
            $templateId = $res['template_id'];
            $smsSign = "iMacau";
            try {
                $ssender = new SmsSingleSender($appid, $appkey);
                $params = [$_GPC['code']];
                $result = $ssender->sendWithParam($res['code'], $phoneNumbers, $templateId,
                    $params, $smsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
                $rsp = json_decode($result);
                echo $result;
            } catch(\Exception $e) {
                echo var_dump($e);
            }
        }
        if ($res['item']==3){
            require_once MODULE_ROOT.'/aliyun-dysms-php-sdk/api_demo/SmsDemo.php';
            $code = cache_load('code');
            $resarr['status']=1;
            $resarr['msg']='send success!';
            $resarr['code']=$code;
            return $this->result(0,'success',$resarr);
        }
    }
    //获取搜索标签
    public function doPageSeachTag() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_searchtag');
        $data=array();
        $name=array();
        foreach ($res as $k=>$v){
            array_push($name,$v['Name']);
        }
        $data['Data']['HotBrands']=$name;
        $data['Data']['HotCategroys']=array();
        $data['Data']['HotKeyWords']=array();
        $data['Data']['SearchKeywords']=$name;
        echo json_encode($data);
    }
    //分类列表
    public function doPageAllType(){
        global $_W, $_GPC;
        $alldata=array();
        $type=pdo_getall('pintuan_typeonemy',array('uniacid'=>$_W['uniacid']),array(),'','ID ASC');
        $alldata['Data']['List']=$type;
        foreach ($type as $k=>$v){
            $list=pdo_getall('pintuan_typetwomy',array('uniacid'=>$_W['uniacid'],'Pid'=>$v['ID']),array(),'','ID ASC');
            $alldata['Data']['List'][$k]['Datas']=$list;
        }

        echo json_encode($alldata);
    }
    //收藏
    public function doPageDolike() {
        global $_W, $_GPC;
        $getid=$_GPC['id'];
        $uid=$_GPC['uid'];
        $data=array();
        if (!empty($getid)&&!empty($uid)){
            $res = pdo_get('pintuan_mylike', array('gid' => $getid,'uid'=>$uid));
            if($res){
                //存在就删除
                $deleres=pdo_delete('pintuan_mylike', array('gid' => $getid,'uid'=>$uid));
                if ($deleres){
                    $data['msg']="取消收藏成功";
                    $data['code']=1;
                }else{
                    $data['msg']="取消收藏失败";
                    $data['code']=0;
                }
            }else{
                //不存在就插入
                $res=pdo_insert('pintuan_mylike',array('gid'=>$getid,'uid'=>$uid,'uniacid' => $_W['uniacid']));
                if ($res){
                    $data['msg']="收藏成功";
                    $data['code']=1;
                }else{
                    $data['msg']="收藏失败";
                    $data['code']=0;
                }
            }
        }else{
            $data['msg']="收藏操作失败，缺uid或者gid";
            $data['code']=0;
        }

        echo json_encode($data);
    }
    public function doPageGetlike() {
        global $_W, $_GPC;
        $list = pdo_getall('pintuan_mylike', array('uid' => $_GPC['uid']));
        $allgood=array();
        $echogood=array();
        foreach ($list as $k=>$v){
            $getgood=pdo_get('pintuan_goodmy', array('gID' => $v['gid']));
            array_push($allgood,$getgood);
        }
        foreach ($allgood as $k=>$v){
            $allgood[$k]['Discount']=0;
            $alldata[$k]['Cover']=$v['Itemcover'];
            $alldata[$k]['DealCount']=$v['Itemcover'];
            $alldata[$k]['DisplayStatu']='新款';
            if(empty($v['Videos'])){
                $allgood[$k]['HasVideo']=false;
            }else{
                $allgood[$k]['HasVideo']=true;
            }
            $allgood[$k]['IsSaleOut']=false;
            $allgood[$k]['ID']=$v['gID'];
            $allgood[$k]['IsShowStatuIcon']=false;
            $allgood[$k]['ChengTuanCount']=1;
            $allgood[$k]['OriPrice']=$v['Price'];
            $allgood[$k]['Statu']=$v['Statu']==1?'已上架':'已下架';
            $allgood[$k]['tips']="已拼".$v['DealCount']."件,我也要拼！";

        }
        $echogood['Data']=$allgood;
        echo json_encode($echogood);

    }
    //添加和更新购物车
    public function doPageAddMycar(){
        global $_W, $_GPC;
        $data=htmlspecialchars_decode($_GPC['Products']);
        $arr=json_decode($data,true);
        $gid=$_GPC['itemId'];
        $uid=$_GPC['uid'];
        $zid=pdo_get("pintuan_goodmy",array('gID'=>$gid),array('zid'));
        $getfid=pdo_get("pintuan_lanmu",array('QsID'=>$zid['zid']),array('fid'));
        $data=array();
        $resarr=array();
        $state=$_GPC['state'];
        foreach ($arr as $k=>$v){
            $data['cUid']=$uid;
            $data['Itemid']=$gid;
            $data['Color']=$v['color'];
            $data['Size']=$v['size'];
            $data['Qty']=$v['qty'];
            $data['fid']=$getfid['fid'];
            $data['uniacid']=$_W['uniacid'];
            //先查询是否存在该商品相同规格和颜色了，如果存在就更新
            $issave=pdo_get("pintuan_cart",array('cUid'=>$uid,"Itemid"=>$gid,"Color"=>$v['color'],"Size"=>$v['size']));
            if($issave){
                if($state){
                    if($v['qty']==0){
                        pdo_delete('pintuan_cart',array('cUid'=>$uid,"Itemid"=>$gid,"Color"=>$v['color'],"Size"=>$v['size']));
                    }else{
                        $res=pdo_update('pintuan_cart',array('Qty'=>$v['qty']),array('cUid'=>$uid,"Itemid"=>$gid,"Color"=>$v['color'],"Size"=>$v['size']));

                    }

                }else{
                    //添加购物车时如果已经存在就更新
                    $res=pdo_update('pintuan_cart',array('Qty'=>($v['qty']+$issave['Qty'])),array('cUid'=>$uid,"Itemid"=>$gid,"Color"=>$v['color'],"Size"=>$v['size']));
                }
            }else{
                if(!empty($v['qty'])){
                    $res=pdo_insert('pintuan_cart',$data);
                }

            }
        }

        if ($res){
            $resarr['code']=1;
            $resarr['re']=$res;
            $resarr['msg']='加入购物车成功';
        }else{
            $resarr['re']=$res;

            $resarr['code']=0;
            $resarr['msg']='加入购物车失败';
        }
        echo json_encode($resarr);
    }
    //返回购物车的数据
    public function doPageGetcart(){
        global $_W, $_GPC;
        $uid=$_GPC['uid'];
        $goodids=array();
        $data=array();
        $gids=pdo_getall('pintuan_cart', array('cUid' => $uid), array('Itemid'));
        foreach ($gids as $k=>$v){
            array_push($goodids,$v['Itemid']);
        }
        //剔除重复gid
        $dealid=array_unique($goodids);
        $goodids=$dealid;
        $data['Data']['TotalAmount ']=$this->dototal($goodids,$uid);
        $data['Data']['TotalCount ']=$this->getTotalCount($uid);
        //查询所有商品的发货地点
        $fids=pdo_getall('pintuan_cart', array('cUid' => $uid), array('fid'));
        $fidarr=array();
        foreach ($fids as $k=>$v){
            array_push($fidarr,$v['fid']);
        }
        //剔除重复fid
        $dealfid=array_unique($fidarr);
        $fidarr=array_merge($dealfid);

        $items=array();//有效商品集合
        foreach ($fidarr as $k=>$v){
            $itemidarr=array();//剔除重复id
            $Itemid=pdo_getall('pintuan_cart', array('fid' => $v['fid'],'cUid'=>$uid), array('Itemid'));
            foreach ($Itemid as $k1=>$v1){
                array_push($itemidarr,$v1['Itemid']);
            }
            $dealitemid=array_unique($itemidarr);
            $Itemid=array_merge($dealitemid);

            $getname=pdo_get('pintuan_warehome', array('fid' => $v['fid']), array('Name'));
            foreach ($Itemid as $kk=>$vv){
                $getgood=pdo_get('pintuan_goodmy', array('gID' => $vv));
                $items[$k]['TimeList'][0]["Items"][$kk]['AgentItemID']=$vv;
                $items[$k]['TimeList'][0]["Items"][$kk]['Cover']=$getgood['Itemcover'];
                $items[$k]['TimeList'][0]["Items"][$kk]['Name']=$getgood['Title'];
                $items[$k]['TimeList'][0]["Items"][$kk]['Price']=number_format($getgood['Price'],2);
                $items[$k]['TimeList'][0]["Items"][$kk]['TotalQty']=$this->getoneTotalCount($uid,$vv);
                $getgoodinfo=pdo_getall('pintuan_cart', array('Itemid' => $vv,'cUid'=>$uid));
                foreach ($getgoodinfo as $kkk=>$vvv){
                    $items[$k]['TimeList'][0]["Items"][$kk]['Products'][$kkk]["Color"]=$vvv['Color'];
                    $items[$k]['TimeList'][0]["Items"][$kk]['Products'][$kkk]["Size"]=$vvv['Size'];
                    $items[$k]['TimeList'][0]["Items"][$kk]['Products'][$kkk]["Qty"]=$vvv['Qty'];
                }

            }

            $items[$k]['WareHouseName']=$getname['Name'];
        }

        $data['Data']['Items']=$items;
        echo json_encode($data);

    }
    //返回购物车数量
    public function doPagegetTotalCount(){
        global $_W, $_GPC;
        $getuid=$_GPC['uid'];
        $arr=array();
        $totolcont=$this->getTotalCount($getuid);
        $arr['Data']['CartItemQty']=$totolcont?$totolcont:0;
        echo json_encode($arr);
    }
    //返回购物车总价格
    public function doPagegetTotal(){
        global $_W, $_GPC;
        $data=htmlspecialchars_decode($_GPC['itemInfos']);
        $arr=json_decode($data,true);
        $gidsarr=array();
        $arrtotal=array();
        foreach ($arr as $k=>$v){
            array_push($gidsarr,$v['AgentItemID']);
        }

        $getid=json_decode($_GPC['gids'],true);
        $uid=$_GPC['uid'];
        if(empty($gidsarr)){
            $totol=$this->dototal($getid,$uid);
            $arrtotal['Data']['TotalAmount']=number_format($totol,2);
            echo json_encode($arrtotal);
        }else{
            $totol=$this->dototal($gidsarr,$uid);
            $arrtotal['Data']['TotalAmount']=number_format($totol,2);
            echo json_encode($arrtotal);
        }

    }
    //删除购物车
    public function doPageDelshopcat(){
        global $_W, $_GPC;
        $getgid=$_GPC['ids'];
        $gidar=explode(',',$getgid);
        $uid=$_GPC['uid'];
        $data=array();
        foreach ($gidar as $k=>$v){
            $res=pdo_delete('pintuan_cart',array('Itemid'=>$v,"cUid"=>$uid));
        }
        if($res){
            $data['Data']['Result']=1;
            $data['Data']['msg']='删除成功';
        }else{
            $data['Data']['Result']=0;
            $data['Data']['msg']='删除失败';
        }
        echo json_encode($data);

    }
    //获取编辑购物车商品信息
    public function doPageGetUpdatecart(){
        global $_W, $_GPC;
        $gid=$_GPC['id'];
        $uid=$_GPC['uid'];
        $good=pdo_get("pintuan_goodmy",array('gID'=>$gid));
        //购物车部分
        $getcolors=explode('/',$good['Colors']);
        $getsizes=explode('/',$good['Sizes']);
        $getcarcolor=pdo_getall("pintuan_cart",array('cUid'=>$uid,"Itemid"=>$gid),array("Color"));
        $getcarsize=pdo_getall("pintuan_cart",array('cUid'=>$uid,"Itemid"=>$gid),array("Size"));
        //处理重复数组
        //$getcarcolor=$this->dearRepArr($getcarcolor,"Color");
        // $getcarsize=$this->dearRepArr($getcarsize,"Size");

        $alldata=array();
        $Sizes=array();
        $SizeList=array();
        foreach ($getcolors as $k=>$v){
            foreach ($getsizes as $kk=>$vv){
                $getqty=pdo_get("pintuan_cart",array('cUid'=>$uid,"Itemid"=>$gid,"Color"=>$v,"Size"=>$vv),array("Qty"));
                $Sizes[$vv]=2000;
                $SizeList[$kk]['Size']=$vv;
                $SizeList[$kk]['Stock']=2000;
                $SizeList[$kk]['qty']=$getqty['Qty'] ? $getqty['Qty']:0;
            }
            $alldata['Data']['Products'][]['Color']=$v;
            $alldata['Data']['Products'][$k]['Sizes']=$Sizes;
            $alldata['Data']['Products'][$k]['SizeList']=$SizeList;
        }
        $alldata['Data']['MainColorPic']=$good['Itemcover'];
        $alldata['Data']['ID']=$good['gID'];
        echo json_encode($alldata);
    }
    //获取并判断是否有收货地址
    public function doPageGetDefaultaddress(){
        global $_W, $_GPC;
        $uid=$_GPC['uid'];
        $data=array();
        //判断是否有地址
        $ishave=pdo_get("pintuan_address",array('uid'=>$uid,'uniacid'=>$_W['uniacid']));
        if ($ishave){
            //获取默认地址
            $res=pdo_get("pintuan_address",array('uid'=>$uid,'uniacid'=>$_W['uniacid'],"isDefault"=>1));
            if ($res){
                $data['Data']=$res;
            }else{
                $data['Data']=$ishave;
            }
            $data['Result']=true;
        }else{
            $data['Result']=false;
            $data['Data']=array();
        }
        echo json_encode($data);
    }
    //添加收货地址
    public function doPageAddaddress(){
        global $_W, $_GPC;
        $uid=$_GPC['uid'];
        $data=array();
        $updatas=array();
        $updatas['realName']=$_GPC['realName'];
        $updatas['mobile']=$_GPC['mobile'];
        $updatas['areaId']=$_GPC['areaId'];
        $updatas['address']=$_GPC['address'];
        $updatas['uniacid']=$_W['uniacid'];
        $updatas['uid']=$uid;
        $isdefault=pdo_get("pintuan_address",array('uid'=>$uid,'uniacid'=>$_W['uniacid'],"isDefault"=>1));
        if($isdefault){
            //已经有默认了
            if($_GPC['isDefault']){//此条地址是否设置默认
                pdo_update('pintuan_address',array('isDefault'=>0),array('ID'=>$isdefault['ID']));
                $updatas['isDefault']=1;
            }else{
                $updatas['isDefault']=0;
            }
        }else{
            //还没有默认
            $updatas['isDefault']=1;
        }
        $res=pdo_insert("pintuan_address",$updatas);
        if($res){
            $data['Data']['state']=1;
            $data['Data']['msg']='添加成功';
        }else{
            pdo_update('pintuan_address',array('isDefault'=>1),array('ID'=>$isdefault['ID']));
            $data['Data']['state']=0;
            $data['Data']['msg']='添加失败';
        }
        echo json_encode($data);
    }
    //修改地址
    public function doPageUpdAddress(){
        global $_W, $_GPC;
        $resdata=array();
        $id=$_GPC['ID'];
        $uid=$_GPC['uid'];
        $data['realName']=$_GPC['realName'];
        $data['mobile']=$_GPC['mobile'];
        $data['areaId']=$_GPC['areaId'];
        $data['address']=$_GPC['address'];
        $data['isDefault']=$_GPC['isDefault'];
        $isdefault=pdo_get("pintuan_address",array('uid'=>$uid,'uniacid'=>$_W['uniacid'],"isDefault"=>1));
        if($isdefault){
            //已经有默认了
            if($_GPC['isDefault']){//此条地址是否设置默认
                pdo_update('pintuan_address',array('isDefault'=>0),array('ID'=>$isdefault['ID']));
                $data['isDefault']=1;
            }else{
                $data['isDefault']=0;
            }
        }else{
            //还没有默认
            $data['isDefault']=1;
        }
        $res=pdo_update('pintuan_address',$data,array('ID'=>$id));
        if($res){
            $resdata['Data']['state']=1;
            $resdata['Data']['msg']='修改成功';
        }else{
            pdo_update('pintuan_address',array('isDefault'=>1),array('ID'=>$isdefault['ID']));
            $resdata['Data']['state']=0;
            $resdata['Data']['msg']='修改失败';
        }
        echo json_encode($resdata);
    }
    //删除地址
    public function doPageDelAdd(){
        global $_W, $_GPC;
        $resdata=array();
        $id=$_GPC['ID'];
        $res=pdo_delete('pintuan_address',array('ID'=>$id));
        if($res){
            $resdata['Data']['state']=1;
            $resdata['Data']['msg']='删除成功';
        }else{
            $resdata['Data']['state']=0;
            $resdata['Data']['msg']='删除失败';
        }
        echo json_encode($resdata);
    }
    //获取单一地址
    public function doPagegetoneAdd(){
        global $_W, $_GPC;
        $ID=$_GPC['ID'];
        $res=pdo_get('pintuan_address',array('ID'=>$ID));
        $data['Data']=$res;
        echo json_encode($data);
    }
    //我的地址
    public function doPageMyAddress(){
        global $_W, $_GPC;
        $uid=$_GPC['uid'];
        $res=pdo_getall('pintuan_address',array('uid'=>$uid), array() , '' , 'isDefault DESC');
        $data['Data']=$res;
        echo json_encode($data);
    }
    //结算商品清单
    public function doPageCartlist(){
        global $_W, $_GPC;
        $uid=$_GPC['uid'];
        $data=htmlspecialchars_decode($_GPC['itemInfos']);
        $arr=json_decode($data,true);
        $gidsarr=array();//商品id数组
        $fidarr=array();//发货地id数组
        $PostFee=array();//各发货地运费
        foreach ($arr as $k=>$v){
            array_push($gidsarr,$v['AgentItemID']);
        }
        foreach ($gidsarr as  $k=>$v){
            $fid=pdo_get('pintuan_cart', array('Itemid' => $v), array('fid'));
            array_push($fidarr,$fid['fid']);
        }
        $Orders=array();//购物车商品集
        $fidarr=array_unique($fidarr);
        $fidarr=array_merge($fidarr);
      
        foreach ($fidarr as $k=>$v){
            $getname=pdo_get('pintuan_warehome', array('fid' => $v), array('Name'));
          	$i=0;
            foreach ($gidsarr as $kk=>$vv){
                $getgood=pdo_get('pintuan_goodmy', array('gID' => $vv));
               //查找商品下的发货地
                $fid=pdo_get('pintuan_lanmu',array('QsID'=>$getgood['zid']),array('fid'));
               if ($fid['fid']!=$v){
                 array_merge($gidsarr);
                  $i=0;
                    continue;
                
                }else{
                    $Orders[$k]['Items'][$i]['AgentItemID']=$vv;
                    $Orders[$k]['Items'][$i]['fid']=$v;
                    $Orders[$k]['Items'][$i]['Cover']=$getgood['Itemcover'];
                    $Orders[$k]['Items'][$i]['Name']=$getgood['Title'];
                    $Orders[$k]['Items'][$i]['Price']=number_format($getgood['Price'],2);
                    $Orders[$k]['Items'][$i]['TotalQty']=$this->getoneTotalCount($uid,$vv);
                    $getgoodinfo=pdo_getall('pintuan_cart', array('Itemid' => $vv,'cUid'=>$uid));
                    foreach ($getgoodinfo as $kkk=>$vvv){
                        $Orders[$k]['Items'][$i]['Products'][$kkk]["Color"]=$vvv['Color'];
                        $Orders[$k]['Items'][$i]['Products'][$kkk]["Size"]=$vvv['Size'];
                        $Orders[$k]['Items'][$i]['Products'][$kkk]["Qty"]=$vvv['Qty'];
                    }
                 	$i++;
                }
            }
            //发货地下的所有商品数量
            $sql="select  sum(Qty) as ftotal from" . tablename("pintuan_cart") ." where uniacid={$_W['uniacid']} and cUid=".$uid." and fid=".$v;
            //单商品的总数
            $fsum=pdo_fetchall($sql);
            $Orders[$k]['TotalQty']=$fsum[0]['ftotal'];
            $Orders[$k]['Name']=$getname['Name'];
            //运费
            $emsprice=pdo_get("pintuan_warehome",array('fid'=>$v),array("emsprice"));
            array_push($PostFee,$emsprice['emsprice']*$fsum[0]['ftotal']);
        }
        $resarr['Data']['Orders']=$Orders;
        $resarr['Data']['ShipApplyNotice']="系统根据您的拿货情况和回货周期，会最优地帮您选择发货方式，下单付款后您也可以通过“我的－配送清单”进行修改！";
        $resarr['Data']['ShipSettingNotice']="系统默认使用申通或韵达，或您有特殊物流需求，可通过联系APP客服！";
        //商品金额
        $allgoodtotal=$this->dototal($gidsarr,$uid);
        $resarr['Data']['TotalProductAmount']=number_format($allgoodtotal,2);
        //订单总价
        $resarr['Data']['TotalPayableAmount']=number_format($allgoodtotal+array_sum($PostFee),2);
        //总运费
        $resarr['Data']['TotalOriPostFeeAmount']=number_format(array_sum($PostFee),2);
       // $resarr=array();
        //$resarr['Data']=$Orders;
        echo json_encode($resarr);

    }
    //提交订单
    public function doPageAddMyOrder(){
        global $_W, $_GPC;
        $uid=$_GPC['uid'];
        $addid=$_GPC['aid'];//地址id
        $goodinfo=json_decode(htmlspecialchars_decode($_GPC['arr']),true);//商品信息数组
        //查询用户地址信息
        $addinfo=pdo_get('pintuan_address',array('ID'=>$addid));
        $data['user_id']=$uid;//用户id
        $data['name']=$addinfo['realName'];//姓名
        $data['address']=$addinfo['areaId'].' '.$addinfo['address'];//地址
        $data['money']=$_GPC['money'];//付款金额
        $data['postfee']=$_GPC['postfee'];//运费
        $data['tel']=$addinfo['mobile'];//手机号
        $data['uniacid']=$_W['uniacid'];//小程序id
        $data['order_num']=date('YmdHis',time()).rand(1111,9999);//订单号
        $data['time']=date("Y-m-d H:i:s",time());//下单时间
       //"共2款,3件,合计¥ 667.21"
        $typenum=count($goodinfo);//款数
        $misc=array();//件数
        foreach ($goodinfo as $k=>$v){
            array_push($misc,$v['num']);
        }
        $data['Summary']='共'.$typenum.'款,'.array_sum($misc).'件,合计￥'.$_GPC['money'];//购买数量情况
        $res=pdo_insert('pintuan_order',$data);
        $order_id=pdo_insertid();
        $chidid=array();
            if($res){
                foreach ($goodinfo as $k=>$v){
                    $onegood=pdo_get('pintuan_goodmy',array('gID'=>$v['id']));
                    $data2['name']=$onegood['Title'];//商品名称
                    $data2['number']=$v['num'];//商品数量
                    $data2['money']=$v['price'];//商品单价
                    $data2['img']=$onegood['Itemcover'];//商品图片
                    $data2['spec']=$v['msg'];//商品规格
                    $data2['dishes_id']=$v['id'];//商品id
                    $data2['fid']=$v['fid'];//商品id
                    $data2['uniacid']=$_W['uniacid'];//小程序id
                    $data2['order_id']=$order_id;
                    $data2['Code']=date('Ymd',time()).'-'.$this->randNum(8);
                    $res2=pdo_insert('pintuan_order_goods',$data2);
                   array_push($chidid,pdo_insertid());
                }
                if($res2){
                  //清除购物车
                    foreach ($goodinfo as $k=>$v){
                        pdo_delete('pintuan_cart',array('Itemid'=>$v['id'],"cUid"=>$uid));
                    }
                   $resdata['Data']['OrderIds']=implode(',',$chidid);
                   $resdata['Data']['orderid']=$order_id;
                    $resdata['code']=1;
                    $resdata['msg']='提交订单成功';
                    echo json_encode($resdata);
                }else{
                    $resdata['code']=0;
                    $resdata['msg']='提交订单失败';
                    echo json_encode($resdata);
                }

            }else{
                $resdata['code']=0;
                $resdata['msg']='提交订单失败';
                echo json_encode($resdata);
            }

    }
      //获取订单列表
       public function doPageGetOrder(){
        global $_W, $_GPC;
        $uid=$_GPC['uid'];
        $statuid=$_GPC['StatuID'];//订单状态
        $data=array();
        if ($statuid==1){
            $data['Data']['Notice']="未支付的订单将在专场结束后自动取消，请尽快付款！";
        }else if ($statuid==5){
            $data['Data']['Notice']="";
        }else{
            $data['Data']['Notice']='';
        }
       
        if ($statuid==0){
            $getorder=pdo_getall('pintuan_order',array('user_id'=>$uid), array(), '', 'id DESC');
        }else{
            $getorder=pdo_getall('pintuan_order',array('user_id'=>$uid,'state'=>$statuid), array(), '', 'id DESC');
        }
         
         
          if (empty($getorder)){
            $data['Data']['OrderList']=array();
        }
        foreach ($getorder as $k=>$v){
           if ($v['state']==3){
                $Buttons=array(
                    '0'=>array('isPoint'=>true,'isEnable'=>false,'title'=>'支付','action'=>'买家支付'),
                    '1'=>array('isPoint'=>false,'isEnable'=>true,'title'=>'确认收货','action'=>'买家确认收货'),
                );
            }else{
                $Buttons=array(
                    '0'=>array('isPoint'=>true,'isEnable'=>$v['state']==1?true:false,'title'=>'支付','action'=>'买家支付'),
                    '1'=>array('isPoint'=>false,'isEnable'=>$v['state']==1?true:false,'title'=>'取消订单','action'=>'买家取消'),
                );
            }
            $data['Data']['OrderList'][$k]['Buttons']=$Buttons;
            $data['Data']['OrderList'][$k]['Code']=$v['order_num'];
            $data['Data']['OrderList'][$k]['ID']=$v['id'];
            $getchid=pdo_getall('pintuan_order_goods',array('order_id'=>$v['id']), array('id'));
            $getimg=pdo_getall('pintuan_order_goods',array('order_id'=>$v['id']), array(), '', 'id DESC', array(1,3));
            $cid=array();
            $img=array();
            foreach ($getchid as $kk=>$vv){
                array_push($cid,$vv['id']);
            }
            foreach ($getimg as $kk=>$vv){
                array_push($img,$vv['img']);
            }
            $data['Data']['OrderList'][$k]['OrderIDS']=implode(',',$cid);//子订单
            $data['Data']['OrderList'][$k]['Images']=$img;
            $data['Data']['OrderList'][$k]['PayableAmount']=$v['money'];
            switch ($v['state']){
                case 1:
                    $data['Data']['OrderList'][$k]['Statu']='待支付';
                    break;
                case 2:
                    $data['Data']['OrderList'][$k]['Statu']='待发货';
                    break;
                case 3:
                    $data['Data']['OrderList'][$k]['Statu']='待收货';
                    break;
                case 4:
                    $data['Data']['OrderList'][$k]['Statu']='已完成';
                    break;
                case 5:
                    $data['Data']['OrderList'][$k]['Statu']='已取消';
                    break;
                default:
                    $data['Data']['OrderList'][$k]['Statu']='全部';
            }
            $data['Data']['OrderList'][$k]['Summary']=$v['Summary'];
            if (count($img)==1){
                $data['Data']['OrderList'][$k]['Title']=$getimg[0]['name'];
            }
        }
        echo json_encode($data);
    }
    //订单详细
    public function doPageGetDetailOrder(){
        global $_W, $_GPC;
        $uid=$_GPC['uid'];
        $oid=$_GPC['orderid'];
        $getorder=pdo_get('pintuan_order',array('id'=>$oid));
        $Summary=$getorder['Summary'];
        $numandfont=explode(",合计",$Summary);
        $data=array();
        $data['Data']['Buttonbool']=true;
        if ($getorder['state']==3){
            $Buttons=array(
                '0'=>array('isPoint'=>true,'isEnable'=>false,'title'=>'支付','action'=>'买家支付'),
                '1'=>array('isPoint'=>false,'isEnable'=>true,'title'=>'确认收货','action'=>'买家确认收货'),
            );
        }else{
            $Buttons=array(
                '0'=>array('isPoint'=>true,'isEnable'=>$getorder['state']==1?true:false,'title'=>'支付','action'=>'买家支付'),
                '1'=>array('isPoint'=>false,'isEnable'=>$getorder['state']==1?true:false,'title'=>'取消订单','action'=>'买家取消'),
            );
        }
        $data['Data']['Buttons']=$Buttons;
        $data['Data']['BottomInfo']['ButtomLine1']=$numandfont[0];
        $data['Data']['BottomInfo']['ButtomLine2']=$numandfont[1];
        $data['Data']['ConsigneeInfo']['Address']=$getorder['address'];
        $data['Data']['ConsigneeInfo']['Mobile']=$getorder['tel'];
        $data['Data']['ConsigneeInfo']['Recipient']=$getorder['name'];
        $data['Data']['CreateTime']=$getorder['time'];
        $data['Data']['DiscountAmount']='0.00';
        $data['Data']['ID']=$getorder['id'];
        $data['Data']['Code']=$getorder['order_num'];
        $getchid=pdo_getall('pintuan_order_goods',array('order_id'=>$getorder['id']), array('id'));
        $cid=array();
        foreach ($getchid as $kk=>$vv){
            array_push($cid,$vv['id']);
        }
        $data['Data']['OrderIDS']=implode(',',$cid);//子订单id
       if(empty($getorder['postfeenum'])){
            $data['Data']['PackageList']=array();
        }else{
            $data['Data']['PackageList']['Code']=$getorder['postfeenum'];
            $data['Data']['PackageList']['Name']=$getorder['postfeename'];
            $data['Data']['PackageList']['ShipTime']=$getorder['jd_time'];
        }
        $data['Data']['PostFee']=$getorder['postfee'];//运费
        $data['Data']['ProductAmount']=floatval(str_replace(',','',$getorder['money']))-floatval($getorder['postfee']);//商品金额=实付金额-运费
        $data['Data']['PayableAmount']=$getorder['money'];//实付金额
        switch ($getorder['state']){
            case 1:
                $data['Data']['Statu']='待支付';
                break;
            case 2:
                $data['Data']['Statu']='待发货';
                break;
            case 3:
                $data['Data']['Statu']='待收货';
                break;
            case 4:
                $data['Data']['Statu']='已完成';
                break;
            case 5:
                $data['Data']['Statu']='已取消';
                break;
            default:
                $data['Data']['Statu']='未知';
        }
        //各发货地下的商品
        $allfid=pdo_getall('pintuan_order_goods',array('order_id'=>$oid),array('fid'));
        $allgoodinfo=pdo_getall('pintuan_order_goods',array('order_id'=>$oid));
         $fids=array();//发货地id
         foreach ($allfid as $k=>$v){
             array_push($fids,$v['fid']);
         }
         $fids=array_unique($fids);
         $fids=array_merge($fids);
        foreach ($fids as $k=>$v){
            $getname=pdo_get('pintuan_warehome', array('fid' => $v), array('Name'));
            $i=0;
            foreach ($allgoodinfo as $kk=>$vv){
                if ($vv['fid']!=$v){
                    $i=0;
                    continue;
                }else{
                   if($vv['Summary']==2||$vv['Summary']==3||$vv['Summary']==4){
                        $Buttons=array(
                            '0'=>array('isPoint'=>false,'isEnable'=>false,'title'=>'取消','action'=>'买家取消'),
                            '1'=>array('isPoint'=>false,'isEnable'=>false,'title'=>'我要补货','action'=>'补货'),
                        );
                    }else{
                        $Buttons=array(
                            '0'=>array('isPoint'=>false,'isEnable'=>$vv['Summary']==5?false:true,'title'=>'取消','action'=>'买家取消'),
                            '1'=>array('isPoint'=>false,'isEnable'=>$vv['Summary']==5?true:false,'title'=>'我要补货','action'=>'补货'),
                        ); 
                    }
                    $data['Data']['SenderList'][$k]['ChildOrders'][$i]['Buttons']=$Buttons;
                    $data['Data']['SenderList'][$k]['ChildOrders'][$i]['Code']=$vv['Code'];
                    $data['Data']['SenderList'][$k]['ChildOrders'][$i]['Cover']=$vv['img'];
                    $data['Data']['SenderList'][$k]['ChildOrders'][$i]['ItemID']=$vv['dishes_id'];
                    $data['Data']['SenderList'][$k]['ChildOrders'][$i]['OrderID']=$vv['id'];
                    $data['Data']['SenderList'][$k]['ChildOrders'][$i]['Title']=$vv['name'];
                    $data['Data']['SenderList'][$k]['ChildOrders'][$i]['Price']=$vv['money'];
                    $data['Data']['SenderList'][$k]['ChildOrders'][$i]['TotalQty']=$vv['number'];
                    $data['Data']['SenderList'][$k]['ChildOrders'][$i]['desc']=explode(',',$vv['spec']);
                    switch ($vv['Summary']){
                        case 1:
                            $data['Data']['SenderList'][$k]['ChildOrders'][$i]['Summary']='待支付';
                            break;
                        case 2:
                            $data['Data']['SenderList'][$k]['ChildOrders'][$i]['Summary']='待发货';
                            break;
                        case 3:
                            $data['Data']['SenderList'][$k]['ChildOrders'][$i]['Summary']='待收货';
                            break;
                        case 4:
                            $data['Data']['SenderList'][$k]['ChildOrders'][$i]['Summary']='已完成';
                            break;
                        case 5:
                            $data['Data']['SenderList'][$k]['ChildOrders'][$i]['Summary']='已取消';
                            break;
                        default:
                            $data['Data']['SenderList'][$k]['ChildOrders'][$i]['Summary']='未知';
                    }
                    $i++;
                }
            }
            $data['Data']['SenderList'][$k]['Name']=$getname['Name'];
        }
        echo json_encode($data);
    }
    //取消订单
    public function doPageCancelMyOrder(){
        global $_W, $_GPC;
        $res=pdo_update('pintuan_order',array('state'=>5,'cancel_time'=>date("Y-m-d H:i:s")),array('id'=>$_GPC['id']));
        if ($res){
          //取消所有子订单
            $allchild=pdo_getall('pintuan_order_goods',array('order_id'=>$_GPC['id']));
            foreach ($allchild as $k=>$v){
                pdo_update('pintuan_order_goods',array('Summary'=>5),array('id'=>$v['id']));
            }
            $data['Result']=true;
        }else{
            $data['Result']=false;
        }
        echo  json_encode($data);
    }
    //取消子订单
    public function doPageCancelChildOrder(){
        global $_W, $_GPC;
        $res=pdo_update('pintuan_order_goods',array('Summary'=>5),array('id'=>$_GPC['childId']));
        if ($res){
            //修改价格floatval(str_replace(',','',$getorder['money']))
            //取消的价格
            $chidorder=pdo_get('pintuan_order_goods',array('id'=>$_GPC['childId']));
            $order=pdo_get('pintuan_order',array('id'=>$chidorder['order_id']),array('money','Summary','postfee'));
            //运费
            $postfee=pdo_get('pintuan_warehome',array('fid'=>$chidorder['fid']),array('emsprice'));
            $newalltotal=floatval(str_replace(',','',$order['money']))-floatval(str_replace(',','',$chidorder['money'])*$chidorder['number'])-floatval($postfee['emsprice']*$chidorder['number']);
            $ordersummary=explode(',',$order['Summary']);
            //款数
            $ks=$this->findNum($ordersummary[0]);
            //件数
            $jshu=$this->findNum($ordersummary[1]);
            $newsummary='共'.($ks-1).'款,'.($jshu-$chidorder['number']).'件,合计￥'.$newalltotal;

            $re=pdo_update('pintuan_order',array('money'=>$newalltotal,'Summary'=>$newsummary,'postfee'=>($order['postfee']-$postfee['emsprice']*$chidorder['number'])),array('id'=>$chidorder['order_id']));
			if($re){
                $newmoney=pdo_get('pintuan_order',array('id'=>$chidorder['order_id']),array('money'));
                if ($newmoney['money']<=0){
                    pdo_update('pintuan_order',array('state'=>5),array('id'=>$chidorder['order_id']));
                }
            }
            $data['Result']=true;
        }else{
            $data['Result']=false;
        }
        echo  json_encode($data);
    }
  //确认收货
    public function doPageOkMyOrder(){
        global $_W, $_GPC;
        $res=pdo_update('pintuan_order',array('state'=>4,'complete_time'=>date("Y-m-d H:i:s")),array('id'=>$_GPC['id']));
        $res2=pdo_update('pintuan_order_goods',array('Summary'=>4),array('order_id'=>$_GPC['id']));
        if($res&&$res2){
            $data['Result']=true;
            $data['Message']='签收成功';
        }else{
            $data['Result']=false;
            $data['Message']='签收失败';
        }
        echo json_encode($data);
    }
  //字符串中去数字
    function findNum($str=''){
        $str=trim($str);
        if(empty($str)){return '';}
        $result='';
        for($i=0;$i<strlen($str);$i++){
            if(is_numeric($str[$i])){
                $result.=$str[$i];
            }
        }
        return $result;
    }
    //获取开启每天的开拼时间
    function getopentime(){
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $start = mktime(7,0,0,$month,$day,$year);//当天开始时间戳
        return date('Y-m-d H:i:s',$start);
    }
    //获取开启每天的开拼时间
    function getclosetime(){
        $year = date("Y");
        $month = date("m");
        $day = date("d")+1;
        $start = mktime(7,0,0,$month,$day,$year);//当天开始时间戳
        return date('Y-m-d H:i:s',$start);
    }
    /**
     * 根据用户id和商品id计算价格
     * @param $data 传入商品的id数组
     */
    function dototal($data,$uid){
        global $_W, $_GPC;
        $total=array();
        foreach ($data as $k=>$v){
            $sql="select  sum(Qty) as total from" . tablename("pintuan_cart") ." where uniacid={$_W['uniacid']} and cUid=".$uid." and Itemid=".$v;
            //单商品的总数
            $ressum=pdo_fetchall($sql);
            $price=pdo_get('pintuan_goodmy', array('gID' =>$v),array("Price"));
            array_push($total,$ressum[0]['total']*$price['Price']);
        }
        return array_sum($total);

    }
    //获取购物车数量
    function getTotalCount($uid){
        global $_W, $_GPC;
        $sql="select  sum(Qty) as total from " . tablename("pintuan_cart") ." where uniacid={$_W['uniacid']} and cUid=".$uid;
        //单商品的总数
        $ressum=pdo_fetchall($sql);
        return $ressum[0]['total'];
    }
    //获取单商品购物车数量
    function getoneTotalCount($uid,$gid){
        global $_W, $_GPC;
        $sql="select  sum(Qty) as total from" . tablename("pintuan_cart") ." where uniacid={$_W['uniacid']} and cUid=".$uid." and Itemid=".$gid;
        //单商品的总数
        $ressum=pdo_fetchall($sql);
        return $ressum[0]['total'];
    }
    //处理待key的重复数组函数
    function dearRepArr($arr,$key){
        $a=array();
        foreach ($arr as $k=>$v){
            array_push($a,$v[$key]);
        }
        //剔除重复数组
        $b=array_unique($a);
        //修复键
        $c=array_merge($b);
        return $c;

    }
   //随机生成用户名
    function randName($length)
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyz   
               ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key='';
        for ($i=1;$i<=4;$i++){
            $key .=chr(rand(97, 122));//小写97-122，大写65-90
        }
        $key=$key.'_';
        for($i=0;$i<$length;$i++)
        {
            $key .= $pattern{mt_rand(0,35)};    //生成php随机数
        }
        return $key;
    }
    //随机生成订单号
    function randNum($length)
    {
        $pattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key='';
        for($i=0;$i<$length;$i++)
        {
            $key .= $pattern{mt_rand(0,16)};    //生成php随机数
        }
        return $key;
    }
      //微信支付
    public function doPagedoPay(){
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/wxpay.php';
        $res=pdo_get('pintuan_pay',array('uniacid'=>$_W['uniacid']));
        $res2=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        if($res2['url_name']){
            $res2['url_name']=$res2['url_name'];
        }else{
            $res2['url_name']='天天拼团';
        }
        //支付需要传入的参数 openid 订单id 支付金额
        $appid=$res2['appid'];
        $openid=$_GPC['openid'];//oQKgL0ZKHwzAY-KhiyEEAsakW5Zg
        $mch_id=$res['mchid'];
        $key=$res['wxkey'];
        $out_trade_no = $mch_id. time();
        $root=$_W['siteroot'];
        pdo_update('pintuan_order',array('code'=>$out_trade_no),array('id'=>$_GPC['orderid']));
        $total_fee =$_GPC['money'];
        if(empty($total_fee)) //默认1分
        {
            $body =$res2['url_name'];
            $total_fee = floatval(1*100);
        }else{
            $body = $res2['url_name'];
            $total_fee = floatval($total_fee*100);
        }
        $weixinpay = new WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$root);
        $return=$weixinpay->pay();
        echo json_encode($return);
    }
    ////////////////////////////////////////////////////////

    //通过用户id请求用户信息
    public function doPageUserInfo() {
        global $_W, $_GPC;
        $res = pdo_get('pintuan_user', array('u_id' => $_GPC['uid']));
        echo json_encode($res);
    }
    //分类下菜品
    public function doPageDishes(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_goods',array('type_id'=>$_GPC['type_id'],'is_show'=>1,'type !='=>$_GPC['type']),array(),'',array('num asc'));
        echo json_encode($res);
    }

    //菜品列表
    public function doPageDishesList() {
        global $_W, $_GPC;
        //  $type = pdo_getall('pintuan_type', array('uniacid' => $_W['uniacid'], 'store_id' => $_GPC['store_id'], 'is_open' => 1), array(), '', 'order_by ASC');
        $sql=" select * from".tablename('pintuan_type')." where  uniacid={$_W['uniacid']} and store_id={$_GPC['store_id']} and is_open=1 and id in(select type_id from".tablename('pintuan_goods')." where uniacid={$_W['uniacid']} and is_show=1 and type !={$_GPC['type']} and store_id={$_GPC['store_id']}) order by order_by asc";
        $type=pdo_fetchall($sql);
        $list = pdo_getall('pintuan_goods', array('uniacid' => $_W['uniacid'], 'is_show' => 1, 'type !=' => $_GPC['type'], 'store_id' => $_GPC['store_id']), array(), '', 'num ASC');
        $data2 = array();
        for ($i = 0;$i < count($type);$i++) {
            $data = array();
            for ($k = 0;$k < count($list);$k++) {
                if ($type[$i]['id'] == $list[$k]['type_id']) {
                    $data[] = $list[$k];
                }
            }
            $data2[] = array('id' => $type[$i]['id'], 'type_name' => $type[$i]['type_name'], 'good' => $data);
        }
        echo json_encode($data2);
    }
    //商品详情
    public function doPageGoodInfo(){
        global $_W, $_GPC;
        $good=pdo_get('pintuan_goods',array('id'=>$_GPC['good_id'],'uniacid'=>$_W['uniacid']));
        $spec=pdo_getall('pintuan_spec',array('good_id'=>$_GPC['good_id'],'uniacid'=>$_W['uniacid']),array(),'','num asc');
        $specval=pdo_getall('pintuan_spec_val',array('uniacid'=>$_W['uniacid']),array(),'','num asc');
        $data2=array();
        for($i=0;$i<count($spec);$i++){
            $data=array();
            for($k=0;$k<count($specval);$k++){
                if($spec[$i]['id']==$specval[$k]['spec_id']){
                    $data[]=array(
                        'spec_val_id'=>$specval[$k]['id'],
                        'spec_val_name'=>$specval[$k]['name'],
                        'spec_val_num'=>$specval[$k]['num']
                    );
                }
            }
            $data2[]=array(
                'spec_id'=>$spec[$i]['id'],
                'spec_name'=>$spec[$i]['name'],
                'spec_num'=>$spec[$i]['num'],
                'spec_val'=>$data
            );
        }
        $good['spec']=$data2;
        echo json_encode($good);
    }
    //规格组合
    public function doPageGgZh(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_spec_combination',array('combination'=>$_GPC['combination'],'good_id'=>$_GPC['good_id']));
        $good=pdo_get('pintuan_goods',array('id'=>$_GPC['good_id']));
        $res['box_money']=$good['box_money'];
        echo json_encode($res);
    }
    //添加购物车
    public function doPageAddCar(){
        global $_W, $_GPC;
        if($_GPC['type']==2){
            if($_GPC['son_id']){
                $good=pdo_get('pintuan_shopcar',array('good_id'=>$_GPC['good_id'],'store_id'=>$_GPC['store_id'],'son_id'=>$_GPC['son_id'],'dr_id'=>$_GPC['dr_id'],'user_id'=>$_GPC['user_id'],'combination_id'=>$_GPC['combination_id'],'type'=>2));
            }else{
                $good=pdo_get('pintuan_shopcar',array('good_id'=>$_GPC['good_id'],'store_id'=>$_GPC['store_id'],'user_id'=>$_GPC['user_id'],'combination_id'=>$_GPC['combination_id'],'son_id'=>0,'type'=>2));
            }
        }else{
            $good=pdo_get('pintuan_shopcar',array('good_id'=>$_GPC['good_id'],'store_id'=>$_GPC['store_id'],'user_id'=>$_GPC['user_id'],'combination_id'=>$_GPC['combination_id'],'type'=>1));
        }

        $combination=pdo_get('pintuan_spec_combination',array('id'=>$_GPC['combination_id']));
        $list=pdo_get('pintuan_goods',array('id'=>$_GPC['good_id']));
        if($_GPC['combination_id']){
            $kc=$combination['number'];
        }else{
            $kc=$list['inventory'];
        }

        if(!$_GPC['combination_id'] and $list['restrict_num']>0 and $good['num']==$list['restrict_num']){
            echo '超出购买限制!';
        }else{
            if(($_GPC['num']+$good['num'])>$kc){
                echo '超出库存!';
            }else{
                if($good){
                    $data['num']=$_GPC['num']+$good['num'];
                    $res=pdo_update('pintuan_shopcar',$data,array('id'=>$good['id']));
                }else{
                    if($_GPC['type']==2){
                        $data['type']=$_GPC['type'];
                    }
                    if($_GPC['son_id']){
                        $data['son_id']=$_GPC['son_id'];
                        $data['dr_id']=$_GPC['dr_id'];
                    }
                    $data['good_id']=$_GPC['good_id'];
                    $data['store_id']=$_GPC['store_id'];
                    $data['user_id']=$_GPC['user_id'];
                    $data['box_money']=$_GPC['box_money'];
                    $data['num']=$_GPC['num'];
                    $data['spec']=$_GPC['spec'];
                    $data['money']=$_GPC['money'];
                    $data['combination_id']=$_GPC['combination_id'];
                    $res=pdo_insert('pintuan_shopcar',$data);
                }
                if($res){
                    echo  '1';
                }else{
                    echo  '2';
                }
            }


        }
    }


    //抢购添加购物车
    public function doPageQgAddCar(){
        global $_W, $_GPC;
        pdo_delete('pintuan_shopcar',array('store_id'=>$_GPC['store_id'],'user_id'=>$_GPC['user_id'],'is_qg'=>1));
        $data['good_id']=$_GPC['good_id'];
        $data['store_id']=$_GPC['store_id'];
        $data['user_id']=$_GPC['user_id'];
        $data['box_money']=$_GPC['box_money'];
        $data['is_qg']=$_GPC['is_qg'];
        $data['qg_name']=$_GPC['qg_name'];
        $data['qg_logo']=$_GPC['qg_logo'];
        $data['num']=1;
        $data['money']=$_GPC['money'];
        $data['combination_id']=$_GPC['combination_id'];
        $res=pdo_insert('pintuan_shopcar',$data);
        if($res){
            echo  '1';
        }else{
            echo  '2';
        }
    }

    //删除抢购菜品
    public function doPageQgDelCar(){
        global $_W, $_GPC;
        $res=pdo_delete('pintuan_shopcar',array('id'=>$_GPC['id']));
        if($res){
            echo  '1';
        }else{
            echo  '2';
        }
    }

    //清空购物车
    public function doPageDelCar(){
        global $_W, $_GPC;
        if($_GPC['type']==2){
            if($_GPC['son_id']){
                $res=pdo_delete('pintuan_shopcar',array('user_id'=>$_GPC['user_id'],'son_id'=>$_GPC['son_id'],'dr_id'=>$_GPC['dr_id'],'store_id'=>$_GPC['store_id'],'type'=>2));
            }else{
                $res=pdo_delete('pintuan_shopcar',array('user_id'=>$_GPC['user_id'],'store_id'=>$_GPC['store_id'],'type'=>2));
            }

        }else{
            $res=pdo_delete('pintuan_shopcar',array('user_id'=>$_GPC['user_id'],'store_id'=>$_GPC['store_id'],'type'=>1));
        }

        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }
    //修改购物车
    public function doPageUpdCar(){
        global $_W, $_GPC;
        $car=pdo_get('pintuan_shopcar',array('id'=>$_GPC['id']));
        $combination=pdo_get('pintuan_spec_combination',array('id'=>$car['combination_id']));
        $list=pdo_get('pintuan_goods',array('id'=>$car['good_id']));
        if($car['combination_id']){
            $kc=$combination['number'];
        }else{
            $kc=$list['inventory'];
        }

        if(!$car['combination_id'] and $list['restrict_num']>0 and $_GPC['num']>$list['restrict_num']){
            echo '超出购买限制!';
        }else{
            if(($_GPC['num'])>$kc){
                echo '超出库存!';
            }else{
                if($_GPC['num']==0){
                    $res=pdo_delete('pintuan_shopcar',array('id'=>$_GPC['id']));
                    if($res){
                        echo '1';
                    }else{
                        echo '2';
                    }
                }else{
                    $res=pdo_update('pintuan_shopcar',array('num'=>$_GPC['num']),array('id'=>$_GPC['id']));
                    if($res){
                        echo '1';
                    }else{
                        echo '2';
                    }
                }

            }
        }


    }

//我的购物车
    public function doPageMyCar(){
        global $_W, $_GPC;
        if($_GPC['type']==2){
            if($_GPC['son_id']){
                $sql="select a.*,b.number,c.logo,c.name,c.inventory from " . tablename("pintuan_shopcar") . " a"  . " left join " . tablename("pintuan_spec_combination") . " b on b.id=a.combination_id " . " left join " . tablename("pintuan_goods") . " c on c.id=a.good_id  WHERE a.store_id=:store_id and a.user_id=:user_id and a.son_id={$_GPC['son_id']} and a.dr_id={$_GPC['dr_id']} and a.type=2 ";
            }else{
                $sql="select a.*,b.number,c.logo,c.name,c.inventory from " . tablename("pintuan_shopcar") . " a"  . " left join " . tablename("pintuan_spec_combination") . " b on b.id=a.combination_id " . " left join " . tablename("pintuan_goods") . " c on c.id=a.good_id  WHERE a.store_id=:store_id and a.user_id=:user_id and a.son_id=0 and a.type=2 ";
            }
        }else{
            $sql="select a.*,b.number,c.logo,c.name,c.inventory from " . tablename("pintuan_shopcar") . " a"  . " left join " . tablename("pintuan_spec_combination") . " b on b.id=a.combination_id " . " left join " . tablename("pintuan_goods") . " c on c.id=a.good_id  WHERE a.store_id=:store_id and a.user_id=:user_id and a.type=1";
        }

        $res=pdo_fetchall($sql,array('store_id'=>$_GPC['store_id'],'user_id'=>$_GPC['user_id']));
        $num=0;
        $money=0;
        $box_money=0;
        if($_GPC['type']==2){
            for($i=0;$i<count($res);$i++){
                $num=$res[$i]['num']+$num;
                $money=$res[$i]['num']*$res[$i]['money']+$money;
                $box_money=$res[$i]['num']*$res[$i]['box_money']+$box_money;
            }
        }else{
            for($i=0;$i<count($res);$i++){
                $num=$res[$i]['num']+$num;
                $money=$res[$i]['num']*$res[$i]['money']+$res[$i]['num']*$res[$i]['box_money']+$money;
                $box_money=$res[$i]['num']*$res[$i]['box_money']+$box_money;
            }
        }

        $data['box_money']=$box_money;
        $data['res']=$res;
        $data['num']=$num;
        $data['money']=$money;
        echo json_encode($data);
    }

    //我的购物车
    public function doPageMyCar2(){
        global $_W, $_GPC;
        $sql="select a.*,b.number,c.logo,c.name,c.inventory from " . tablename("pintuan_shopcar") . " a"  . " left join " . tablename("pintuan_spec_combination") . " b on b.id=a.combination_id " . " left join " . tablename("pintuan_goods") . " c on c.id=a.good_id  WHERE a.store_id=:store_id and a.user_id=:user_id  and a.type=2 ";
        $res=pdo_fetchall($sql,array('store_id'=>$_GPC['store_id'],'user_id'=>$_GPC['user_id']));
        $num=0;
        $money=0;
        $box_money=0;
        if($_GPC['type']==2){
            for($i=0;$i<count($res);$i++){
                $num=$res[$i]['num']+$num;
                $money=$res[$i]['num']*$res[$i]['money']+$money;
                $box_money=$res[$i]['num']*$res[$i]['box_money']+$box_money;
            }
        }else{
            for($i=0;$i<count($res);$i++){
                $num=$res[$i]['num']+$num;
                $money=$res[$i]['num']*$res[$i]['money']+$res[$i]['num']*$res[$i]['box_money']+$money;
                $box_money=$res[$i]['num']*$res[$i]['box_money']+$box_money;
            }
        }

        $data['box_money']=$box_money;
        $data['res']=$res;
        $data['num']=$num;
        $data['money']=$money;
        echo json_encode($data);
    }

//热销
    public function doPageHot(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_goods',array('store_id'=>$_GPC['store_id'],'uniacid'=>$_W['uniacid'],'is_hot'=>1,'is_show'=>1,'type !='=>$_GPC['type']));
        echo json_encode($res);
    }
    //商家列表
    public function doPageStoreList(){
        global $_W, $_GPC;
        $time=time();
        $up="UPDATE ".tablename('pintuan_store')."SET state=4 where uniacid={$_W['uniacid']} and state=2 and UNIX_TIMESTAMP(rzdq_time)<={$time}";
        pdo_query($up);
        $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        // $lat="30.525980";
        //  $lng="114.353440";
        if($_GPC['lat']){
            $lat=$_GPC['lat'];
        }else{
            $lat='30.592760';
        }
        if($_GPC['lng']){
            $lng=$_GPC['lng'];
        }else{
            $lng='114.305250';
        }
        $where=" WHERE a.uniacid=:uniacid and a.is_open=1 and a.state=2";
        if($_GPC['type_id']){
            $where .=" and a.md_type = :md_type";
            $data[':md_type']=$_GPC['type_id'];
        }
        if($_GPC['keywords']){
            $where.=" and a.name LIKE  concat('%', :name,'%') ";
            $data[':name']=$_GPC['keywords'];
        }
        if($_GPC['nopsf']==1){
            $where .=" and (b.money is null || b.money=0)";
        }
        if($_GPC['nostart']==1){
            $where .=" and a.start_at=0";
        }
        if($_GPC['yhhd']){
            $where .=$_GPC['yhhd'];
        }

        $data[':uniacid']=$_W['uniacid'];
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        if(!$_GPC['by']){
            $_GPC['by']="number asc";
        }
        if($system['distance']!=0){
            $sql="select xx.* from (SELECT a.*, ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)/2),2)+COS($lat*PI()/180)*COS(SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)*POW(SIN(($lng*PI()/180-SUBSTRING_INDEX(coordinates, ',', -1)*PI()/180)/2),2)))*1000) AS juli ,b.money as ps_money,c.ps_mode,c.ps_time,c.xyh_open,c.xyh_money FROM ".tablename("pintuan_store") . " a left join (select min(money) as money,store_id from ".tablename("pintuan_distribution")." group by store_id) b on a.id=b.store_id " . " left join " . tablename("pintuan_storeset") . " c on c.store_id=a.id left join (select min(reduction) as money,store_id from ".tablename("pintuan_reduction")." ) d on a.id=d.store_id ".  $where ." ORDER BY ".$_GPC['by'].") xx where xx.juli<=".$system['distance'];
        }else{
            $sql="SELECT a.*, ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)/2),2)+COS($lat*PI()/180)*COS(SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)*POW(SIN(($lng*PI()/180-SUBSTRING_INDEX(coordinates, ',', -1)*PI()/180)/2),2)))*1000) AS juli ,b.money as ps_money,c.ps_mode,c.ps_time,c.xyh_open,c.xyh_money FROM ".tablename("pintuan_store") . " a left join (select min(money) as money,store_id from ".tablename("pintuan_distribution")." group by store_id) b on a.id=b.store_id " . " left join " . tablename("pintuan_storeset") . " c on c.store_id=a.id left join (select min(reduction) as money,store_id from ".tablename("pintuan_reduction")." ) d on a.id=d.store_id ".  $where ." ORDER BY ".$_GPC['by'];
        }
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;

        $list = pdo_fetchall($select_sql,$data);
        for($i=0;$i<count($list);$i++){
            $ntime=date('Hi');
            $time=str_replace(':','',$list[$i]['time']);
            $time2=str_replace(':','',$list[$i]['time2']);
            $time3=str_replace(':','',$list[$i]['time3']);
            $time4=str_replace(':','',$list[$i]['time4']);
            if(($time<=$ntime && $ntime<$time2) or($time3<=$ntime && $ntime<$time4)){
                $list[$i]['is_yy']=1;
            }else{
                $list[$i]['is_yy']=2;
            }
            $mj=pdo_getall('pintuan_reduction',array('store_id'=>$list[$i]['id']));
            $list[$i]['mj']=$mj;
        }
        echo json_encode($list);
    }
    //商家详情
    public function doPageStoreInfo(){
        global $_W, $_GPC;
        $call=pdo_get('pintuan_call',array('store_id'=>$_GPC['store_id']));
        $store=pdo_get('pintuan_store',array('id'=>$_GPC['store_id']));
        $storeset=pdo_get('pintuan_storeset',array('store_id'=>$_GPC['store_id']));
        $storeset['is_call']=$call['is_open'];
        $reduction=pdo_getall('pintuan_reduction',array('store_id'=>$_GPC['store_id'],'type !='=>$_GPC['type']),array(),'','full ASC');
        $psf=pdo_getall('pintuan_distribution',array('store_id'=>$_GPC['store_id']),array(),'','num asc');
        if($store['environment']){
            if(strlen($store['environment'])>51){
                $store['environment']= explode(',',$store['environment']);
            }else{
                $store['environment']=array(
                    0=>$store['environment']
                );
            }
        }
        if($store['yyzz']){
            if(strlen($store['yyzz'])>51){
                $store['yyzz']= explode(',',$store['yyzz']);
            }else{
                $store['yyzz']=array(
                    0=>$store['yyzz']
                );
            }
        }
        function video($video) {
            $vid = trim(strrchr($video, '/'), '/');
            $vid = substr($vid, 0, -5);
            $json = file_get_contents("http://vv.video.qq.com/getinfo?vids=" . $vid . "&platform=101001&charge=0&otype=json");
            // echo $json;die;
            $json = substr($json, 13);
            $json = substr($json, 0, -1);
            $a = json_decode(html_entity_decode($json));
            $sz = json_decode(json_encode($a), true);
            // print_R($sz);die;
            $url = $sz['vl']['vi']['0']['ul']['ui']['0']['url'];
            $fn = $sz['vl']['vi']['0']['fn'];
            $fvkey = $sz['vl']['vi']['0']['fvkey'];
            $url = $url . $fn . '?vkey=' . $fvkey;
            return $url;
        }
        $store['store_video'] = video($store['store_video']);
        $data['store']=$store;
        $data['reduction']=$reduction;
        $data['psf']=$psf;
        $data['storeset']=$storeset;
        echo json_encode($data);
        file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=UpdateGroup&m=pintuan&store_id=".$_GPC['store_id']);//模板

    }
    //我的地址详情
    public function doPageMyAddressInfo(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_useradd',array('id'=>$_GPC['id']));
        $res['area']=explode(',',$res['area']);
        echo json_encode($res);
    }


    //是否新用户
    public function doPageIsNew(){
        global $_W, $_GPC;
        if($_GPC['store_id']){
            $res=pdo_get('pintuan_order',array('store_id'=>$_GPC['store_id'],'user_id'=>$_GPC['user_id']));
        }else{
            $res=pdo_get('pintuan_order',array('user_id'=>$_GPC['user_id']));
        }
        if($res){
            echo '2';
        }else{
            echo '1';
        }
    }
    //外卖下订单
    public function doPageAddOrder(){
        global $_W, $_GPC;
        $storeset=pdo_get('pintuan_storeset',array('store_id'=>$_GPC['store_id']));
        $data['user_id']=$_GPC['user_id'];//用户id
        $data['store_id']=$_GPC['store_id'];//商家id
        $data['order_num']=date('YmdHis',time()).rand(1111,9999);//订单号
        if($_GPC['pay_type']==4 and $storeset['is_hdfk']==1){
            $data['state']=2;//1.待付款2.待接单3.等待送达4.完成5.已评价6.取消7.拒绝8.退款中9.已退款10.退款拒绝
        }elseif($_GPC['pay_type']==4 and $storeset['is_hdfk']==3){
            $data['state']=6;//1.待付款2.待接单3.等待送达4.完成5.已评价6.取消7.拒绝8.退款中9.已退款10.退款拒绝
        }else{
            $data['state']=1;//1.待付款2.待接单3.等待送达4.完成5.已评价6.取消7.拒绝8.退款中9.已退款10.退款拒绝
        }
        $data['time']=date("Y-m-d H:i:s",time());//下单时间
        $data['money']=$_GPC['money'];//付款金额
        $data['box_money']=$_GPC['box_money'];//包装费
        $data['ps_money']=$_GPC['ps_money'];//配送费
        $data['mj_money']=$_GPC['mj_money'];//满减优惠
        $data['yhq_money']=$_GPC['yhq_money'];//优惠券金额
        $data['yhq_money2']=$_GPC['yhq_money2'];//红包金额
        $data['zk_money']=$_GPC['zk_money'];//折扣金额
        $data['coupon_id']=$_GPC['coupon_id'];//优惠券id
        $data['coupon_id2']=$_GPC['coupon_id2'];//红包id
        $data['xyh_money']=$_GPC['xyh_money'];//新用户立减
        $data['tel']=$_GPC['tel'];//电话
        $data['name']=$_GPC['name'];//姓名
        $data['address']=$_GPC['address'];//地址
        $data['discount']=$_GPC['discount'];//优惠
        $data['tableware']=$_GPC['tableware'];//餐具
        $data['sex']=$_GPC['sex'];//1.男2.女
        $data['note']=$_GPC['note'];//备注
        $data['type']=$_GPC['type'];//1.外卖
        $data['is_dd']=$_GPC['is_dd'];//是否到店
        $data['area']=$_GPC['area'];//区域
        $data['lat']=$_GPC['lat'];//经度
        $data['lng']=$_GPC['lng'];//纬度
        $data['uniacid']=$_W['uniacid'];//小程序id
        $data['form_id']=$_GPC['form_id'];//下单成功通知
        $data['form_id2']=$_GPC['form_id2'];//发货通知
        $data['delivery_time']=$_GPC['delivery_time'];//送达时间
        $data['order_type']=$_GPC['order_type'];//1.配送2.到店
        $data['pay_type']=$_GPC['pay_type'];//1.微信支付4.货到付款
        $res=pdo_insert('pintuan_order',$data);
        $order_id=pdo_insertid();



        $a=json_decode(html_entity_decode($_GPC['sz']));
        $sz=json_decode(json_encode($a),true);
        // /print_r($sz);die;
        if($res){
            if($_GPC['coupon_id']){
                pdo_update('pintuan_usercoupons',array('state'=>1),array('id'=>$_GPC['coupon_id']));
            }
            if($_GPC['coupon_id2']){
                pdo_update('pintuan_usercoupons',array('state'=>1),array('id'=>$_GPC['coupon_id2']));
            }

            for($i=0;$i<count($sz);$i++){
                $data2['name']=$sz[$i]['name'];//商品名称
                $data2['number']=$sz[$i]['num'];//商品数量
                $data2['money']=$sz[$i]['money'];//商品单价
                $data2['img']=$sz[$i]['img'];//商品图片
                $data2['spec']=$sz[$i]['spec'];//商品规格
                $data2['dishes_id']=$sz[$i]['dishes_id'];//商品id
                $data2['is_qg']=$sz[$i]['is_qg'];//商品id
                $data2['uniacid']=$_W['uniacid'];//小程序id
                $data2['order_id']=$order_id;
                if($sz[$i]['is_qg']==1){
                    pdo_update('pintuan_qggoods',array('surplus -='=>1),array('id'=>$sz[$i]['dishes_id']));
                }
                $res2=pdo_insert('pintuan_order_goods',$data2);
            }
            if($_GPC['pay_type']==4 and $storeset['is_hdfk']==1){//货到付款
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=NewOrderMessage&m=pintuan&order_id=".$order_id);//模板消息
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=Message&m=pintuan&order_id=".$order_id);//模板消息
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=QtPrint&m=pintuan&order_id=".$order_id);//打印机
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=HcPrint&m=pintuan&order_id=".$order_id);//打印机
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=sms&m=pintuan&type=1&store_id=".$_GPC['store_id']);//短信
            }
            if($_GPC['pay_type']==2){//余额支付
                if($_GPC['money']>0){
                    pdo_update('pintuan_user', array('wallet -=' => $_GPC['money']), array('id' => $_GPC['user_id']));
                    $data4['money'] = $_GPC['money'];
                    $data4['user_id'] = $_GPC['user_id'];
                    $data4['type'] = 2;
                    $data4['note'] = '外卖订单';
                    $data4['time'] = date('Y-m-d H:i:s');
                    pdo_insert('pintuan_qbmx', $data4);
                }
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=NewOrderMessage&m=pintuan&order_id=".$order_id);//模板消息
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=payorder&m=pintuan&order_id=".$order_id);
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=Message&m=pintuan&order_id=".$order_id);//模板消息
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=QtPrint&m=pintuan&order_id=".$order_id);//打印机
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=HcPrint&m=pintuan&order_id=".$order_id);//打印机
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=sms&m=pintuan&type=1&store_id=".$_GPC['store_id']);//短信
            }
            pdo_delete('pintuan_shopcar',array('user_id'=>$_GPC['user_id'],'store_id'=>$_GPC['store_id'],'type'=>1));
            echo $order_id;
        }else{
            echo '下单失败';
        }

    }


//店内下订单
    public function doPageAddDnOrder(){
        global $_W, $_GPC;
        $table=pdo_get('pintuan_table',array('id'=>$_GPC['table_id']));
        $store=pdo_get('pintuan_storeset',array('store_id'=>$_GPC['store_id']));
        if($store['is_czztpd']==2){
            $table['status']=0;
        }
        if($table['status']==0){
            $data['table_id']=$_GPC['table_id'];//桌子id
            $data['user_id']=$_GPC['user_id'];//用户id
            $data['store_id']=$_GPC['store_id'];//商家id
            $data['mj_money']=$_GPC['mj_money'];//满减优惠
            $data['money']=$_GPC['money'];//付款金额
            $data['tableware']=$_GPC['tableware'];//餐具
            $data['yhq_money']=$_GPC['yhq_money'];//优惠券金额
            $data['yhq_money2']=$_GPC['yhq_money2'];//红包金额
            $data['coupon_id']=$_GPC['coupon_id'];//优惠券id
            $data['coupon_id2']=$_GPC['coupon_id2'];//红包id
            $data['xyh_money']=$_GPC['xyh_money'];//新用户立减
            $data['discount']=$_GPC['discount'];//优惠
            $data['note']=$_GPC['note'];//备注
            $data['type']=$_GPC['type'];//1.外卖2.店内
            $data['dn_state']=1;
            if($_GPC['pay_type']==2){
                $data['dn_state']=2;
            }
            $data['form_id']=$_GPC['form_id'];//下单成功通知
            $data['pay_type']=$_GPC['pay_type'];//1.微信支付5.餐后支付
            $data['uniacid']=$_W['uniacid'];//小程序id
            $data['order_num']=date('YmdHis',time()).rand(1111,9999);//订单号
            $data['time']=date("Y-m-d H:i:s",time());//下单时间
            $res=pdo_insert('pintuan_order',$data);
            $order_id=pdo_insertid();
            $a=json_decode(html_entity_decode($_GPC['sz']));
            $sz=json_decode(json_encode($a),true);
            if($res){
                if($_GPC['coupon_id']){
                    pdo_update('pintuan_usercoupons',array('state'=>1),array('id'=>$_GPC['coupon_id']));
                }
                if($_GPC['coupon_id2']){
                    pdo_update('pintuan_usercoupons',array('state'=>1),array('id'=>$_GPC['coupon_id2']));
                }
                for($i=0;$i<count($sz);$i++){
                    $data2['name']=$sz[$i]['name'];//商品名称
                    $data2['number']=$sz[$i]['num'];//商品数量
                    $data2['money']=$sz[$i]['money'];//商品单价
                    $data2['img']=$sz[$i]['img'];//商品图片
                    $data2['spec']=$sz[$i]['spec'];//商品规格
                    $data2['dishes_id']=$sz[$i]['dishes_id'];//商品id
                    $data2['uniacid']=$_W['uniacid'];//小程序id
                    $data2['order_id']=$order_id;
                    $res2=pdo_insert('pintuan_order_goods',$data2);
                }
                pdo_delete('pintuan_shopcar',array('user_id'=>$_GPC['user_id'],'store_id'=>$_GPC['store_id'],'type'=>2));
                pdo_update('pintuan_table',array('status'=>1),array('id'=>$_GPC['table_id']));
                if($_GPC['pay_type']==5){
                    //file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=Message&m=pintuan&order_id=".$order_id);//模板消息
                    file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=sms&m=pintuan&type=2&store_id=".$_GPC['store_id']);//短信
                    $res=file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=QtPrint&m=pintuan&order_id=".$order_id);//打印机
                    file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=HcPrint&m=pintuan&order_id=".$order_id);//打印机
                }
                if($_GPC['pay_type']==2){
                    if($_GPC['money']>0){
                        pdo_update('pintuan_user', array('wallet -=' => $_GPC['money']), array('id' => $_GPC['user_id']));
                        $data4['money'] = $_GPC['money'];
                        $data4['user_id'] = $_GPC['user_id'];
                        $data4['type'] = 2;
                        $data4['note'] = '店内订单';
                        $data4['time'] = date('Y-m-d H:i:s');
                        pdo_insert('pintuan_qbmx', $data4);
                    }

                    file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=sms&m=pintuan&type=2&store_id=".$_GPC['store_id']);//短信
                    $res=file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=QtPrint&m=pintuan&order_id=".$order_id);//打印机
                    file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=HcPrint&m=pintuan&order_id=".$order_id);//打印机
                }
                echo $order_id;
            }else{
                echo '下单失败';
            }
        }elseif($table['status']==1){
            echo '已开台';
        }
    }
//预约下订单
    public function doPageAddYyOrder(){
        global $_W, $_GPC;
        $data['order_num']=date('YmdHis',time()).rand(1111,9999);//订单号
        $data['table_id']=$_GPC['table_id'];//桌子id
        $data['user_id']=$_GPC['user_id'];//用户id
        $data['store_id']=$_GPC['store_id'];//商家id
        $data['money']=$_GPC['money'];//金额
        $data['note']=$_GPC['note'];//备注
        $data['tel']=$_GPC['tel'];//电话
        $data['name']=$_GPC['name'];//姓名
        $data['sex']=$_GPC['sex'];//1.男2.女
        $data['deposit']=$_GPC['deposit'];//押金
        $data['pay_type']=$_GPC['pay_type'];
        $data['tableware']=$_GPC['tableware'];//餐具
        $data['delivery_time']=$_GPC['delivery_time'];//时间
        $data['time']=date("Y-m-d H:i:s");
        $data['uniacid']=$_W['uniacid'];
        if($_GPC['money']==0){
            $data['yy_state']=2;//已付款
            file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=sms&m=pintuan&type=3&store_id=".$_GPC['store_id']);//短信
        }else{
            $data['yy_state']=1;//待付款
        }
        if($_GPC['pay_type']==2){//余额支付
            if($_GPC['money']>0){
                pdo_update('pintuan_user', array('wallet -=' => $_GPC['money']), array('id' => $_GPC['user_id']));
                $data4['money'] = $_GPC['money'];
                $data4['user_id'] = $_GPC['user_id'];
                $data4['type'] = 2;
                $data4['note'] = '店内订单';
                $data4['time'] = date('Y-m-d H:i:s');
                pdo_insert('pintuan_qbmx', $data4);
            }
            $data['yy_state']=2;//已付款
            file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=sms&m=pintuan&type=3&store_id=".$_GPC['store_id']);//短信
        }


        $data['type']=3;//预约
        $res=pdo_insert('pintuan_order',$data);
        $order_id=pdo_insertid();
        $a=json_decode(html_entity_decode($_GPC['sz']));
        $sz=json_decode(json_encode($a),true);
        if($res){
            for($i=0;$i<count($sz);$i++){
                $data2['name']=$sz[$i]['name'];//商品名称
                $data2['number']=$sz[$i]['num'];//商品数量
                $data2['money']=$sz[$i]['money'];//商品单价
                $data2['img']=$sz[$i]['img'];//商品图片
                $data2['spec']=$sz[$i]['spec'];//商品规格
                $data2['dishes_id']=$sz[$i]['dishes_id'];//商品id
                $data2['uniacid']=$_W['uniacid'];//小程序id
                $data2['order_id']=$order_id;
                $res2=pdo_insert('pintuan_order_goods',$data2);
            }
            echo $order_id;
        }else{
            echo '下单失败';
        }

    }

//当面付下订单
    public function doPageDmOrder(){
        global $_W, $_GPC;
        $data['money'] = $_GPC['money'];
        $data['order_num']=date('YmdHis',time()).rand(1111,9999);
        $data['user_id'] = $_GPC['user_id'];
        $data['store_id'] = $_GPC['store_id'];
        $data['type'] = 4;
        $data['pay_type'] =$_GPC['pay_type'];
        $data['uniacid'] = $_W['uniacid'];
        $data['time'] = date('Y-m-d H:i:s');
        $data['dm_state'] = 1;
        $res = pdo_insert('pintuan_order', $data);
        $order_id = pdo_insertid();
        if ($res) {
            if($_GPC['pay_type']==2){
                pdo_update('pintuan_user', array('wallet -=' => $_GPC['money']), array('id' => $_GPC['user_id']));
                $data4['money'] = $_GPC['money'];
                $data4['user_id'] = $_GPC['user_id'];
                $data4['type'] = 2;
                $data4['note'] = '当面付订单';
                $data4['time'] = date('Y-m-d H:i:s');
                pdo_insert('pintuan_qbmx', $data4);
                pdo_update('pintuan_order',array('dm_state'=>2),array('id'=>$order_id));
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=NewDmOrderMessage&m=pintuan&order_id=".$order_id);//短信
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=sms&m=pintuan&type=2&store_id=".$_GPC['store_id']);//短信
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=QtPrint&m=pintuan&order_id=".$order_id);//打印机
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=addintegral&m=pintuan&type=5&order_id=".$order_id);//短信
            }
            echo $order_id;
        } else {
            echo '下单失败';
        }
    }

//加菜
    public function doPageAddgoods(){
        global $_W, $_GPC;
        $a=json_decode(html_entity_decode($_GPC['sz']));
        $sz=json_decode(json_encode($a),true);
        $id=array();
        for($i=0;$i<count($sz);$i++){
            $data['name']=$sz[$i]['name'];//商品名称
            $data['number']=$sz[$i]['num'];//商品数量
            $data['money']=$sz[$i]['money'];//商品单价
            $data['img']=$sz[$i]['img'];//商品图片
            $data['spec']=$sz[$i]['spec'];//商品规格
            $data['dishes_id']=$sz[$i]['dishes_id'];//商品id
            $data['uniacid']=$_W['uniacid'];//小程序id
            $data['order_id']=$_GPC['order_id'];
            $data['is_jc']=1;
            $res2=pdo_insert('pintuan_order_goods',$data);
            $id[]=pdo_insertid();
        }
        $id=implode(",",$id);
//$id=json_encode($id);
        $res=pdo_update('pintuan_order',array('money +='=>$_GPC['money']),array('id'=>$_GPC['order_id']));
        $order=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        pdo_delete('pintuan_shopcar',array('user_id'=>$order['user_id'],'store_id'=>$order['store_id'],'type'=>2));
        file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=JcPrint&m=pintuan&order_id=".$_GPC['order_id']."&money=".$_GPC['money']."&good=".$id);
        file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=JcPrint2&m=pintuan&order_id=".$_GPC['order_id']."&money=".$_GPC['money']."&good=".$id);
        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }


//支付
    public function doPagePay(){
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/wxpay.php';
        $res=pdo_get('pintuan_pay',array('uniacid'=>$_W['uniacid']));
        $res2=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        if($res2['url_name']){
            $res2['url_name']=$res2['url_name'];
        }else{
            $res2['url_name']='餐饮小程序';
        }
        $appid=$res2['appid'];
        $openid=$_GPC['openid'];//oQKgL0ZKHwzAY-KhiyEEAsakW5Zg
        $mch_id=$res['mchid'];
        $key=$res['wxkey'];
        $out_trade_no = $mch_id. time();
        $root=$_W['siteroot'];
        if($_GPC['type']==2){
            pdo_update('pintuan_czorder',array('code'=>$out_trade_no),array('id'=>$_GPC['order_id']));
        }elseif($_GPC['type']==3){
            pdo_update('pintuan_hyorder',array('code'=>$out_trade_no),array('id'=>$_GPC['order_id']));
        }else{
            pdo_update('pintuan_order',array('code'=>$out_trade_no),array('id'=>$_GPC['order_id']));
        }
        $total_fee =$_GPC['money'];
        if(empty($total_fee)) //押金
        {
            $body =$res2['url_name'];
            $total_fee = floatval(99*100);
        }else{
            $body = $res2['url_name'];
            $total_fee = floatval($total_fee*100);
        }
        $weixinpay = new WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$root);
        $return=$weixinpay->pay();
        echo json_encode($return);
    }

//改变订单状态
    public function doPagePayOrder(){
        global $_W, $_GPC;
        $order=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        $store=pdo_get('pintuan_storeset',array('store_id'=>$order['store_id']));
        $sys=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']),'ps_name');
        $ps_name=empty($sys['ps_name'])?'超级跑腿':$sys['ps_name'];
        if($order['type']==1){//外卖
            if($store['is_jd']==1){
                $data['state']=3;
                $data['pay_time']=date('Y-m-d H:i:s');
                $data['jd_time']=date('Y-m-d H:i:s');
                $res=pdo_update('pintuan_order',$data,array('id'=>$_GPC['order_id']));
            }else{
                $data['state']=2;
                $data['pay_time']=date('Y-m-d H:i:s');
                $res=pdo_update('pintuan_order',$data,array('id'=>$_GPC['order_id']));
            }
            if($store['is_jd']==1&&$order['order_type']==1){//自动接单
                if( $store['ps_mode']=='达达配送'){
                    file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=TestDada&m=pintuan&order_id=".$_GPC['order_id']);//达达
                }
                if( $store['ps_mode']=='快服务配送'){
                    $res=file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=kfw&m=pintuan&order_id=".$_GPC['order_id']);//快服务
                    $data['ship_id']=$res;
                }
                if( $store['ps_mode']==$ps_name){
                    file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=cjpt&m=pintuan&order_id=".$_GPC['order_id']);//跑腿
                }
            }


        }elseif($order['type']==2){//店内
            $data['dn_state']=2;
            $data['pay_time']=date('Y-m-d H:i:s');
            $res=pdo_update('pintuan_order',$data,array('id'=>$_GPC['order_id']));
        }

        if($res){
            pdo_update('pintuan_store',array('score +='=>1),array('id'=>$order['store_id']));
            $good=pdo_getall('pintuan_order_goods',array('order_id'=>$_GPC['order_id']));
            for($i=0;$i<count($good);$i++){
                pdo_update('pintuan_goods', array('inventory -=' => $good[$i]['number']), array('id' => $good[$i]['dishes_id']));
                pdo_update('pintuan_goods', array('sales +=' => $good[$i]['number']), array('id' => $good[$i]['dishes_id']));
            }
            echo '1';
        }else{
            echo '2';
        }
    }
    //我的订单
    public function  doPageMyOrder(){
        global $_W, $_GPC;
        if($_GPC['dn_state']){
            $where=" WHERE a.user_id=".$_GPC['user_id']." and (a.state in (".$_GPC['state'].") || a.dn_state in (".$_GPC['dn_state'].")) and a.del=2";
        }else{
            $where=" WHERE a.user_id=".$_GPC['user_id']." and a.state in (".$_GPC['state'].")  and a.del=2";
        }
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        $sql="select  a.*,b.name as store_name,b.logo,b.tel as store_tel  from " . tablename("pintuan_order")  . " a"  . " left join " . tablename("pintuan_store") . " b on b.id=a.store_id ".$where." order by a.id DESC";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $list = pdo_fetchall($select_sql);
        $good=pdo_getall('pintuan_order_goods',array('uniacid'=>$_W['uniacid']));
        $data2=array();
        for($i=0;$i<count($list);$i++){
            $data=array();
            $num=0;
            for($k=0;$k<count($good);$k++){
                if($list[$i]['id']==$good[$k]['order_id']){
                    $data[]=array(
                        'good_id'=>$good[$k]['dishes_id'],
                        'img'=>$good[$k]['img'],
                        'number'=>$good[$k]['number'],
                        'name'=>$good[$k]['name'],
                        'money'=>$good[$k]['money'],
                        'spec'=>$good[$k]['spec']
                    );
                    $num=$num+$good[$k]['number'];
                }
            }
            $data2[]=array(
                'order'=>$list[$i],
                'good'=>$data,
                'num'=>$num
            );
        }
        echo  json_encode($data2);
    }
    //我的预约订单
    public function  doPageMyYyOrder(){
        global $_W, $_GPC;
        $where=" WHERE a.user_id=".$_GPC['user_id']." and a.type=3  and a.yy_state in (".$_GPC['yy_state'].")  and a.del=2";
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        $sql="select  a.*,b.name as store_name,b.logo,b.tel as store_tel  from " . tablename("pintuan_order")  . " a"  . " left join " . tablename("pintuan_store") . " b on b.id=a.store_id ".$where." order by a.id DESC";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $list = pdo_fetchall($select_sql);

        $good=pdo_getall('pintuan_order_goods',array('uniacid'=>$_W['uniacid']));
        $data2=array();
        for($i=0;$i<count($list);$i++){
            $data=array();
            $num=0;
            for($k=0;$k<count($good);$k++){
                if($list[$i]['id']==$good[$k]['order_id']){
                    $data[]=array(
                        'good_id'=>$good[$k]['dishes_id'],
                        'img'=>$good[$k]['img'],
                        'number'=>$good[$k]['number'],
                        'name'=>$good[$k]['name'],
                        'money'=>$good[$k]['money'],
                        'spec'=>$good[$k]['spec']
                    );
                    $num=$num+$good[$k]['number'];
                }
            }
            $data2[]=array(
                'order'=>$list[$i],
                'good'=>$data,
                'num'=>$num
            );
        }
        echo  json_encode($data2);
    }
    //订单详情
    public function doPageOrderInfo(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        $res2=pdo_getall('pintuan_order_goods',array('order_id'=>$_GPC['order_id']));
        $res3=pdo_get('pintuan_store',array('id'=>$res['store_id']));
        $res4=pdo_get('pintuan_storeset',array('store_id'=>$res['store_id']));
        $sql="select  a.*,b.name as type_name from " . tablename("pintuan_table")  . " a"  . " left join " . tablename("pintuan_table_type") . " b on b.id=a.type_id where a.id=".$res['table_id'];
        $res5=pdo_fetch($sql);
        $res6=pdo_get('pintuan_table_type',array('id'=>$res['table_id']));
        $store=pdo_get('pintuan_store',array('id'=>$res['store_id']));
        $storetype=pdo_get('pintuan_storetype',array('id'=>$store['md_type']));
        $res['yj_money']=$res['money']-$storetype['poundage']*$res['money']/100;
        $data['order']=$res;
        $data['good']=$res2;
        $data['store']=$res3;
        $data['storeset']=$res4;
        $data['table']=$res5;
        $data['table2']=$res6;
        echo json_encode($data);
    }
    //取消订单
    public function doPageCancelOrder(){
        global $_W, $_GPC;
        $order=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        if($order['type']==1){
            $res=pdo_update('pintuan_order',array('state'=>6,'cancel_time'=>date("Y-m-d H:i:s")),array('id'=>$_GPC['order_id']));
        }elseif($order['type']==2){
            $res=pdo_update('pintuan_order',array('dn_state'=>3,'cancel_time'=>date("Y-m-d H:i:s")),array('id'=>$_GPC['order_id']));
        }
        if($res){
            if($order['type']==2){
                pdo_update('pintuan_table',array('status'=>0),array('id'=>$order['table_id']));
            }
            if($order['coupon_id']){
                pdo_update('pintuan_usercoupons',array('state'=>2),array('id'=>$order['coupon_id']));
            }
            if($order['coupon_id2']){
                pdo_update('pintuan_usercoupons',array('state'=>2),array('id'=>$order['coupon_id2']));
            }
            echo  '1';
        }else{
            echo '2';
        }
    }


    //申请退款
    public function doPageTkOrder(){
        global $_W, $_GPC;
        $res=pdo_update('pintuan_order',array('state'=>8),array('id'=>$_GPC['order_id']));
        if($res){
            echo  '1';
        }else{
            echo  '2';
        }
    }
    //删除订单
    public function doPageDelOrder(){
        global $_W, $_GPC;
        $res=pdo_update('pintuan_order',array('del'=>1),array('id'=>$_GPC['order_id']));
        if($res){
            echo  '1';
        }else{
            echo '2';
        }
    }
    //确认收货
    public function doPageOkOrder(){
        global $_W, $_GPC;
        $res=pdo_update('pintuan_order',array('state'=>4,'complete_time'=>date("Y-m-d H:i:s")),array('id'=>$_GPC['order_id']));
        $res2=pdo_update('pintuan_earnings',array('state'=>2),array('order_id'=>$_GPC['order_id']));
        if($res){
            file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=addintegral&m=pintuan&type=1&order_id=".$_GPC['order_id']);//短信
            echo  '1';
        }else{
            echo '2';
        }
    }
    //导航
    public function doPageNav(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_nav',array('uniacid'=>$_W['uniacid'],'state'=>1),array(),'','num asc');
        echo  json_encode($res);
    }
    //分类导航
    public function doPageTypeAd(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_typead',array('uniacid'=>$_W['uniacid'],'status'=>1),array(),'','orderby asc');
        echo  json_encode($res);
    }
    //商家轮播图
    public function doPageStoreAd(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_storead',array('store_id'=>$_GPC['store_id'],'status'=>1),array(),'','orderby asc');
        echo  json_encode($res);
    }
    //轮播图
    public function doPageAd(){
        global $_W, $_GPC;
        if($_GPC['type']){
            $res=pdo_getall('pintuan_ad',array('uniacid'=>$_W['uniacid'],'status'=>1,'type'=>$_GPC['type']),array(),'','orderby asc');
        }else{
            $res=pdo_getall('pintuan_ad',array('uniacid'=>$_W['uniacid'],'status'=>1),array(),'','orderby asc');
        }

        echo  json_encode($res);
    }
//模板消息
    public function doPageMessage(){
        global $_W, $_GPC;
        function getaccess_token($_W){
            $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
            $appid=$res['appid'];
            $secret=$res['appsecret'];
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            $data = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($data,true);
            return $data['access_token'];
        }
        //设置与发送模板信息
        function set_msg($_W){
            $access_token = getaccess_token($_W);
            $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
            $res2=pdo_get('pintuan_order',array('id'=>$_GET['order_id']));
            $user=pdo_get('pintuan_user',array('id'=>$res2['user_id']));
            if(!$res2['pay_time']){
                $res2['pay_time']=$res2['time'];
            }
            $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$res["xd_tid"].'",
           "page": "pintuan/pages/Liar/loginindex",
           "form_id":"'.$res2['form_id'].'",
           "data": {
             "keyword1": {
               "value": "'.$res2['order_num'].'",
               "color": "#173177"
             },
             "keyword2": {
               "value":"'.$res2['name'].'",
               "color": "#173177"
             },
             "keyword3": {
               "value": "'.$res2['tel'].'",
               "color": "#173177"
             },
             "keyword4": {
               "value":  "'.$res2['money'].'",
               "color": "#173177"
             },
             "keyword5": {
               "value": "'.$res2['pay_time'].'",
               "color": "#173177"
             }
           }
         }';
            // $formwork=$data;
            $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$formwork);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }
        echo set_msg($_W);
    }




//充值模板消息
    public function doPageCzMessage(){
        global $_W, $_GPC;
        pdo_delete('pintuan_formid',array('time <='=>time()-60*60*24*7));
        ///////////////模板消息拒绝///////////////////
        function getaccess_token($_W){
            $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
            $appid=$res['appid'];
            $secret=$res['appsecret'];
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            $data = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($data,true);
            return $data['access_token'];
        }
        //设置与发送模板信息
        function set_msg($_W){
            $access_token = getaccess_token($_W);
            $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
            $res2=pdo_get('pintuan_czorder',array('id'=>$_GET['order_id']));
            $user=pdo_get('pintuan_user',array('id'=>$res2['user_id']));
            $store=pdo_get('pintuan_store',array('id'=>$res2['store_id']));
            $form=pdo_get('pintuan_formid',array('user_id'=>$res2['user_id'],'time >='=>time()-60*60*24*7));
            $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$res["cz_tid"].'",
           "page": "pintuan/pages/Liar/loginindex",
           "form_id":"'.$form['form_id'].'",
           "data": {
             "keyword1": {
               "value": "'.$res2['money'].'",
               "color": "#173177"
             },
             "keyword2": {
               "value":"'.$res2['money2'].'",
               "color": "#173177"
             },
             "keyword3": {

               "value": "'.date("Y-m-d H:i:s").'",
               "color": "#173177"
             },
             "keyword4": {
               "value":  "充值成功,请前往个人中心钱包查看",
               "color": "#173177"
             }
           }
         }';
            // $formwork=$data;
            $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$formwork);
            $data = curl_exec($ch);
            curl_close($ch);
            // return $data;
            pdo_delete('pintuan_formid',array('id'=>$form['id']));
        }
        echo set_msg($_W);
    }













//达达
    public function doPageTestDada(){
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/peisong/peisong.php';
        $order=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        $set=pdo_get('pintuan_psset',array('store_id'=>$order['store_id']));
        $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
//*********************配置项*************************
        $config = array();
        $config['app_key'] = $system['dada_key'];
        $config['app_secret'] = $system['dada_secret'];
        $config['source_id'] =$set['source_id'];
        $config['url'] = 'http://newopen.imdada.cn/api/cityCode/list';
        //获取城市code
        $city=explode(',',$order['area']);
        $city=$city['1'];
        $city=substr($city,0,strpos($city,'市'));
        $cityCode=Peisong::getCityCode($config,$city);
//发单请求数据,只是样例数据，根据自己的需求进行更改。
        $config['url'] = 'http://newopen.imdada.cn/api/order/addOrder';
        $data2 = array(
            'shop_no'=>  $set['shop_no'],//门店编号
            'origin_id'=> $order['order_num'],//订单id
            'city_code'=> $cityCode,//城市
            'tips'=> 0,//小费
            'info'=> $order['note'],//备注
            // 'cargo_type'=> 1,
            // 'cargo_weight'=> 10,
            'cargo_price'=> $order['money'],
            // 'cargo_num'=> 2,
            'is_prepay'=> 0,
            'expected_fetch_time'=>time()+600,
            //'expected_finish_time'=> 0,
            // 'invoice_title'=> '发票抬头',
            'receiver_name'=> $order['name'],
            'receiver_address'=> $order['address'],
            'receiver_phone'=> $order['tel'],
            // 'receiver_tel'=> '18599999999',
            'receiver_lat'=> $order['lat'],
            'receiver_lng'=> $order['lng'],
            'callback'=>$_W['siteroot']."addons/pintuan/payment/peisong/notify.php"
        );
        $result= Peisong::requestMethod($config,$data2);

        return $result;
    }


//帮助中心
    public function doPageGetHelp(){
        global $_W, $_GPC;
        $res= pdo_getall('pintuan_help',array('uniacid'=>$_W['uniacid']),array() , '' , 'sort ASC');
        echo json_encode($res);
    }
//品质优选
    public function doPageBrand(){
        global $_W, $_GPC;
        $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        $lat=$_GPC['lat'];
        $lng=$_GPC['lng'];
        if($system['distance']!=0){
            $sql="select xx.* from (SELECT *, ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)/2),2)+COS($lat*PI()/180)*COS(SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)*POW(SIN(($lng*PI()/180-SUBSTRING_INDEX(coordinates, ',', -1)*PI()/180)/2),2)))*1000) AS juli  FROM ".tablename("pintuan_store") . " where uniacid={$_W['uniacid']} and is_brand=1 and state=2 ORDER BY number ASC) xx where xx.juli<=".$system['distance'];
        }else{
            $sql="SELECT *, ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)/2),2)+COS($lat*PI()/180)*COS(SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)*POW(SIN(($lng*PI()/180-SUBSTRING_INDEX(coordinates, ',', -1)*PI()/180)/2),2)))*1000) AS juli  FROM ".tablename("pintuan_store") . " where uniacid={$_W['uniacid']} and is_brand=1 and state=2  ORDER BY number ASC";
        }
        $res=pdo_fetchall($sql);
        // $res= pdo_getall('pintuan_store',array('uniacid'=>$_W['uniacid'],'is_brand'=>1,'state'=>2),array() , '' , 'number ASC');
        echo json_encode($res);
    }
//周边吃啥
    public function doPageZbOrder(){
        global $_W, $_GPC;
        $sql="select  * from " . tablename("pintuan_order") ." where uniacid={$_W['uniacid']} and state in(2,3,4,5) order by id DESC LIMIT 0,10";
        $res=pdo_fetchall($sql);
        for($i=0;$i<count($res);$i++){
            $good=pdo_get('pintuan_order_goods',array('order_id'=>$res[$i]['id']));
            $res[$i]['goods_name']=$good['name'];
            $time=time()-strtotime($res[$i]['time']);

            if(($time/60)>1440){
                $time=floor($time/60/60/24).'天';
            }elseif(($time/60)>60){
                $time=floor($time/60/60).'小时';
            }else{
                $time=floor($time/60).'分钟';
            }
            $res[$i]['time2']=$time;
        }
// /	print_r($res);die;
        echo json_encode($res);
    }
//优惠券列表
    public function doPageCoupons(){
        global $_W, $_GPC;
        $time=strtotime(date("Y-m-d"));
        $sql="select  * from " . tablename("pintuan_coupons") ." where uniacid={$_W['uniacid']} and store_id={$_GPC['store_id']} and UNIX_TIMESTAMP(start_time)<={$time} and  UNIX_TIMESTAMP(end_time)>={$time} order by id DESC";
        $coupons=pdo_fetchall($sql);
        //$coupons=pdo_getall('pintuan_coupons',array('uniacid'=>$_W['uniacid'],'store_id'=>$_GPC['store_id']));
        $type=pdo_getall('pintuan_storetype',array('uniacid'=>$_W['uniacid']));
        $usercoupons=pdo_getall('pintuan_usercoupons',array('user_id'=>$_GPC['user_id']));
        for($i=0;$i<count($coupons);$i++){
            $coupons[$i]['state']=2;
            for($j=0;$j<count($type);$j++){
                if(strpos($coupons[$i]['type_id'],',')){
                    $type_id= explode(',',$coupons[$i]['type_id']);
                }else{
                    $type_id=array(
                        0=>$coupons[$i]['type_id']
                    );
                }
                $data=array();
                for($l=0;$l<count($type_id);$l++){
                    if($type_id[$l]==$type[$j]['id']){
                        $data[]=$type[$j]['type_name'];
                    }
                }
                $coupons[$i]['type_name']=$data;

            }
            for($k=0;$k<count($usercoupons);$k++){
                if($coupons[$i]['id']==$usercoupons[$k]['coupon_id']){
                    $coupons[$i]['state']=1;//领取
                }
            }
        }
        echo json_encode($coupons);
    }
//我的优惠券
    public function doPageMyCoupons(){
        global $_W, $_GPC;
        $time=strtotime(date("Y-m-d"));
        if($_GPC['store_id']){
            $store=pdo_get('pintuan_store',array('id'=>$_GPC['store_id']));
            $where=" and (b.store_id=".$_GPC['store_id']." || b.store_id=0) and  (find_in_set('{$store['md_type']}',type_id) || type_id=0)";
        }
        $sql="select a.*,b.full,b.reduce,b.instruction,b.store_id,b.name,b.type_id,b.type as coupon_type,b.end_time,d.name as store_name,d.logo from " . tablename("pintuan_usercoupons") . " a"  . " left join " . tablename("pintuan_coupons") . " b on b.id=a.coupon_id " . "  left join " . tablename("pintuan_store") . " d on d.id=b.store_id  WHERE  UNIX_TIMESTAMP(b.start_time)<={$time} and  UNIX_TIMESTAMP(b.end_time)>={$time} and a.state=2 and  a.user_id={$_GPC['user_id']}".$where;
        $res=pdo_fetchall($sql);
        $type=pdo_getall('pintuan_storetype',array('uniacid'=>$_W['uniacid']));
        for($i=0;$i<count($res);$i++){
            $data=array();
            for($j=0;$j<count($type);$j++){
                if(strpos($res[$i]['type_id'],',')){
                    $type_id= explode(',',$res[$i]['type_id']);
                }else{
                    $type_id=array(
                        0=>$res[$i]['type_id']
                    );
                }
                for($l=0;$l<count($type_id);$l++){
                    if($type_id[$l]==$type[$j]['id']){
                        $data[]=$type[$j]['type_name'];
                    }
                }
                $res[$i]['type_name']=$data;
            }
        }
        echo json_encode($res);
    }
//领取优惠券
    public function doPageLqCoupons(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_coupons',array('id'=>$_GPC['coupon_id']));
        if($res['stock']>0){
            $data['user_id']=$_GPC['user_id'];
            $data['coupon_id']=$_GPC['coupon_id'];
            $data['uniacid']=$_W['uniacid'];
            $data['time']=date('Y-m-d H:i:s');
            $data['type']=1;//手动领取
            $res=pdo_insert('pintuan_usercoupons',$data);
            if($res){//领取成功
                pdo_update('pintuan_coupons',array('stock -='=>1),array('id'=>$_GPC['coupon_id']));
                echo '1';
            }else{
                echo '2';
            }
        }else{
            echo  '手慢了';
        }

    }
//天降红包
    public function doPageTjCoupons(){
        global $_W, $_GPC;
        //$usercoupons=pdo_getall('pintuan_usercoupons',array('user_id'=>$_GPC['user_id']));
        $jt=date('Y-m-d');
        $where="'%$jt%'";
        $sql="select  * from " . tablename("pintuan_usercoupons") ." where user_id={$_GPC['user_id']} and time LIKE {$where} and type=2";
        $usercoupons=pdo_fetch($sql);
        // print_r($usercoupons);die;
        if(!$usercoupons){
            $time=strtotime(date("Y-m-d"));
            $sql="select  * from " . tablename("pintuan_coupons")."  where id not in(select coupon_id from " . tablename("pintuan_usercoupons")." where user_id={$_GPC['user_id']}) and uniacid={$_W['uniacid']} and store_id=0  and UNIX_TIMESTAMP(start_time)<={$time} and  UNIX_TIMESTAMP(end_time)>={$time} and stock>0 order by id DESC";
            $coupons=pdo_fetchall($sql);

            if(count($coupons)>0){
                $couponset=pdo_get('pintuan_couponset',array('uniacid'=>$_W['uniacid']));//查看天降红包数量
                if($couponset['number']>count($coupons)){
                    $number=count($coupons);
                }else{
                    $number=$couponset['number'];
                }
                $coupons2=array_rand($coupons,$number);

                if($number==1){//只有一个红包

                    $coupons3=pdo_getall('pintuan_coupons',array('id'=>$coupons[$coupons2]['id']));
                    $type=pdo_getall('pintuan_storetype',array('uniacid'=>$_W['uniacid']));
                    for($i=0;$i<count($coupons3);$i++){
                        for($j=0;$j<count($type);$j++){
                            if($coupons3[$i]['type_id']){
                                if(strpos($coupons3[$i]['type_id'],',')){
                                    $type_id= explode(',',$coupons3[$i]['type_id']);
                                }else{
                                    $type_id=array(
                                        0=>$coupons3[$i]['type_id']
                                    );
                                }
                            }
                            $data=array();
                            for($l=0;$l<count($type_id);$l++){
                                if($type_id[$l]==$type[$j]['id']){
                                    $data[]=$type[$j]['type_name'];
                                }
                            }
                            $coupons3[$i]['type_name']=$data;
                        }
                    }
                    $data2['user_id']=$_GPC['user_id'];
                    $data2['coupon_id']=$coupons[$coupons2]['id'];
                    $data2['uniacid']=$_W['uniacid'];
                    $data2['time']=date('Y-m-d H:i:s');
                    $data2['type']=2;//自动领取
                    pdo_insert('pintuan_usercoupons',$data2);
                    pdo_update('pintuan_coupons',array('stock -='=>1),array('id'=>$coupons[$coupons2]['id']));
                    echo json_encode($coupons3);
                }else{
                    $cid=array();
                    for($v=0;$v<count($coupons2);$v++){
                        $cid[]=$coupons[$coupons2[$v]]['id'];
                    }

                    //	print_R($cid);die;
                    $coupons3=pdo_getall('pintuan_coupons',array('id'=>$cid));
                    $type=pdo_getall('pintuan_storetype',array('uniacid'=>$_W['uniacid']));
                    for($i=0;$i<count($coupons3);$i++){
                        for($j=0;$j<count($type);$j++){
                            if($coupons3[$i]['type_id']){
                                if(strpos($coupons3[$i]['type_id'],',')){
                                    $type_id= explode(',',$coupons3[$i]['type_id']);
                                }else{
                                    $type_id=array(
                                        0=>$coupons3[$i]['type_id']
                                    );
                                }
                            }
                            $data=array();
                            for($l=0;$l<count($type_id);$l++){
                                if($type_id[$l]==$type[$j]['id']){
                                    $data[]=$type[$j]['type_name'];
                                }
                            }
                            $coupons3[$i]['type_name']=$data;
                        }
                    }
                    for($m=0;$m<count($cid);$m++){//循环领取红包
                        $data2['user_id']=$_GPC['user_id'];
                        $data2['coupon_id']=$cid[$m];
                        $data2['uniacid']=$_W['uniacid'];
                        $data2['time']=date('Y-m-d H:i:s');
                        $data2['type']=2;//自动领取
                        pdo_insert('pintuan_usercoupons',$data2);
                        pdo_update('pintuan_coupons',array('stock -='=>1),array('id'=>$cid[$m]));
                    }
                    echo json_encode($coupons3);
                }

            }else{
                echo '暂无红包';
            }
        }else{
            echo '今日已领';
        }
    }

//红包设置
    public function doPageCouponSet(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_couponset',array('uniacid'=>$_W['uniacid']));
        echo json_encode($res);
    }
//评论
    public function doPageAssess(){
        global $_W, $_GPC;
        $data['store_id'] = $_GPC['store_id']; //商家id
        $data['order_id'] = $_GPC['order_id']; //订单id
        $data['stars'] = $_GPC['stars']; //分数
        $data['content'] = $_GPC['content']; //内容
        $data['img'] = $_GPC['img']; //图片
        $data['time'] = date("Y-m-d H:i:s", time()); //创建时间
        $data['user_id'] = $_GPC['user_id']; //用户id
        $data['uniacid'] = $_W['uniacid']; //小程序id
        $data['state'] = 1; //未回复
        $res = pdo_insert('pintuan_assess', $data);
        $order = pdo_get('pintuan_order', array('id' => $_GPC['order_id']));
        if ($res) {
            $total = pdo_get('pintuan_assess', array('uniacid' => $_W['uniacid'], 'store_id' => $_GPC['store_id']), array('sum(stars) as total'));
            $count = pdo_get('pintuan_assess', array('uniacid' => $_W['uniacid'], 'store_id' => $_GPC['store_id']), array('count(id) as count'));
            if ($total['total'] > 0 and $count['count'] > 0) {
                $pf = ($total['total'] / $count['count']);
                $pf = number_format($pf, 1);
            } else {
                $pf = 0;
            }
            pdo_update('pintuan_store', array('sales' => $pf), array('id' => $_GPC['store_id']));
            // echo $order['type'];die;
            if ($order['type'] == 1) {
                $data2['state'] = 5;
            }
            pdo_update('pintuan_order', $data2, array('id' => $_GPC['order_id']));
            file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=addintegral&m=pintuan&type=3&order_id=".$_GPC['order_id']);//短信
            echo '1';
        } else {
            echo '2';
        }
    }
//查看评论
    public function doPageAssessList(){
        global $_W, $_GPC;
        $res=count(pdo_getall('pintuan_assess',array('store_id'=>$_GPC['store_id'])));
        $res2=count(pdo_getall('pintuan_assess',array('store_id'=>$_GPC['store_id'],'stars >='=>4)));
        $res3=count(pdo_getall('pintuan_assess',array('store_id'=>$_GPC['store_id'],'stars <='=>2)));
        $data['all']=$res;
        $data['ok']=$res2;
        $data['no']=$res3;
        $where=" WHERE a.store_id=".$_GPC['store_id'];
        if($_GPC['type']==1){
            $where .=" and a.stars>=4";
        }elseif($_GPC['type']==2){
            $where .=" and a.stars<=2";
        }
        if($_GPC['img']==1){
            $where .=" and a.img!=''";
        }
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        $sql="select  a.*,b.name ,b.img as user_img  from " . tablename("pintuan_assess")  . " a"  . " left join " . tablename("pintuan_user") . " b on b.id=a.user_id ".$where." order by a.id DESC";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $list = pdo_fetchall($select_sql);
        for($i=0;$i<count($list);$i++){
            if($list[$i]['img']){
                if(strpos($list[$i]['img'],',')){
                    $list[$i]['img']= explode(',',$list[$i]['img']);
                }else{
                    $list[$i]['img']=array(
                        0=>$list[$i]['img']
                    );
                }
            }
        }
        $data['assess']=$list;
        echo json_encode($data);
    }
//推荐菜
    public function doPageTjGoods(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_goods',array('is_tj'=>1,'is_show'=>1,'store_id'=>$_GPC['store_id']));
        echo json_encode($res);
    }

    //上传图片
    public function doPageUpload(){
        global $_W, $_GPC;
        $uptypes=array(
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/pjpeg',
            'image/gif',
            'image/bmp',
            'image/x-png' ,
            'video/mp4'
        );
        $max_file_size=2000000;     //上传文件大小限制, 单位BYTE
        $destination_folder="../attachment/pintuan/".date(Y)."/".date(m)."/".date(d)."/"; //上传文件路径
        //$destination_folder="../attachment/"; //上传文件路径
        $watermark=2;      //是否附加水印(1为加水印,其他为不加水印);
        $watertype=1;      //水印类型(1为文字,2为图片)
        $waterposition=1;     //水印位置(1为左下角,2为右下角,3为左上角,4为右上角,5为居中);
        $waterstring="666666";  //水印字符串
        // $waterimg="xplore.gif";    //水印图片
        $imgpreview=1;      //是否生成预览图(1为生成,其他为不生成);
        $imgpreviewsize=1/2;    //缩略图比例
        if (!is_uploaded_file($_FILES["upfile"]['tmp_name']))
            //是否存在文件
        {
            echo "图片不存在!";
            exit;
        }
        $file = $_FILES["upfile"];
        if($max_file_size < $file["size"])
            //检查文件大小
        {
            echo "文件太大!";
            exit;
        }
        // if(!in_array($file["type"], $uptypes))
        // //检查文件类型
        // {
        // 	echo "文件类型不符!".$file["type"];
        // 	exit;
        // }
        if (!file_exists($destination_folder)){
            mkdir ($destination_folder,0777,true);
        }
        $filename=$file["tmp_name"];
        $image_size = getimagesize($filename);
        $pinfo=pathinfo($file["name"]);
        $ftype=$pinfo['extension'];
        $destination = $destination_folder.str_shuffle(time().rand(111111,999999)).".".$ftype;
        if (file_exists($destination) && $overwrite != true)
        {
            echo "同名文件已经存在了";
            exit;
        }
        if(!move_uploaded_file ($filename, $destination))
        {
            echo "移动文件出错";
            exit;
        }
        $pinfo=pathinfo($destination);
        $fname="pintuan/".date(Y)."/".date(m)."/".date(d)."/".$pinfo['basename'];
        echo $fname;
        @require_once (IA_ROOT . '/framework/function/file.func.php');
        @$filename=$fname;
        @file_remote_upload($filename);
    }
/////////////////////////////////////////



//桌位类型
    public function doPageTableType() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_table_type', array('uniacid' => $_W['uniacid'], 'store_id' => $_GPC['store_id']));
        echo json_encode($res);
    }
//桌号
    public function doPageTable() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_table', array('uniacid' => $_W['uniacid'], 'type_id' => $_GPC['type_id'], 'status' => 0));
        echo json_encode($res);
    }
//桌号
    public function doPageTable2() {
        global $_W, $_GPC;
        if($_GPC['type_id']){
            $pageindex = max(1, intval($_GPC['page']));
            $pagesize=$_GPC['pagesize'];
            $sql="select  *  from " . tablename("pintuan_table")  . " where uniacid={$_W['uniacid']} and type_id={$_GPC['type_id']} and store_id={$_GPC['store_id']} order by orderby ASC";
            $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
            $res = pdo_fetchall($select_sql);
        }else{
            $pageindex = max(1, intval($_GPC['page']));
            $pagesize=$_GPC['pagesize'];
            $sql="select  *  from " . tablename("pintuan_table")  . " where uniacid={$_W['uniacid']} and store_id={$_GPC['store_id']} order by orderby ASC";
            $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
            $res = pdo_fetchall($select_sql);
        }
        echo json_encode($res);
    }


//通过桌号查桌子名称和类型
    public function doPageZhuohao() {
        global $_W, $_GPC;
        $res = pdo_get('pintuan_table', array('id' => $_GPC['id']));
        $res2 = pdo_get('pintuan_table_type', array('id' => $res['type_id']));
        $data['table_name'] = $res['name'];
        $data['type_name'] = $res2['name'];
        $data['status'] = $res['status'];
        echo json_encode($data);
    }
























































    public function doPageQtPrint(){ //前台打印
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/print/dyj.php';
        $res=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        $store=pdo_get('pintuan_store',array('id'=>$res['store_id']),'name');
        $print=pdo_get('pintuan_storeset',array('store_id'=>$res['store_id']),array('is_jd','print_mode'));
        if(($print['is_jd']==1)or($print['print_mode']==1&&$res['state']==2) or ($print['print_mode']==1&&($res['dn_state']==1 or $res['dn_state']==2)) or $_GPC['type']==1){//type==1前台打印订单
            $res3=pdo_getall('pintuan_dyj',array('store_id'=>$res['store_id'],'state'=>1,'location'=>1));
            $res2=pdo_getall('pintuan_order_goods',array('order_id'=>$_GPC['order_id']));
            if($res['type']==2){
                //$table = pdo_get('pintuan_table', array('id' => $res['table_id']));
                $sql=" select a.name,b.name as type_name from ".tablename('pintuan_table')." a left join ".tablename('pintuan_table_type')." b on a.type_id=b.id where a.id={$res['table_id']}";
                $table=pdo_fetch($sql);
            }
            if($res['pay_type']==1){
                $is_yue="微信支付";
            }elseif($res['pay_type']==2){
                $is_yue="余额支付";
            }elseif($res['pay_type']==3){
                $is_yue="积分支付";
            }
            elseif($res['pay_type']==4){
                $is_yue="货到付款";
            } elseif($res['pay_type']==5){
                $is_yue="餐后付款";
            }

            if($res['order_type']==2){
                $is_take= "店内自提";
            }elseif($res['order_type']==1){
                $is_take="外卖配送";
            }elseif($res['order_type']==3){
                $is_take="预约到店";
            }
            foreach ($res3 as $key => $value) {
                $style="\n";
                if($value['type']==3){
                    $style="<BR>";
                }
                if($value['type']==4){
                    $style="<0D0A>";
                }

                $content = $style;
                if($value['type']==1&&$value['num']){
                    $content = "^N".$value['num'];
                }
                if($res['type']==4){
                    $user=pdo_get('pintuan_user',array('id'=>$res['user_id']),'name');
                    $content.= "              收银台".$style.$style.$style;
                    $content.= "--------------------------------".$style;
                    $content.= "金额    ：" . $res['money'] . "元".$style;
                    $content.= "--------------------------------".$style;
                    $content.= "付款人  ：" . $user['name'] .$style;
                    $content.= "--------------------------------".$style;
                    $content.= "商家名称：" . $store['name'] . $style;
                    $content.= "--------------------------------".$style;
                    $content.= "付款时间：" . $res['time'] . $style;
                    $content.= "--------------------------------".$style;
                    $content .= "流水号：".$res['order_num'].$style;

                }else{
                    $content .= "          ".$store['name'].$style.$style;
                    $content .= "         订单编号  #".$res['id'].$style.$style;
                    if($res['type']==1){
                        $content .= "          ".$is_take. $style.$style;
                        $content .= "          ".$value['dyj_title'].$style.$style;
                        $content .= "------------".$is_yue."------------".$style;

                        $content .= "--------------------------------".$style;
                        $content .= "下单时间：".$res['time'].$style;
                    }
                    if($res['type']==2){
                        $content .= "------------".$is_yue."------------".$style;
                        $content .= "--------------------------------".$style;
                        $content .= "开台时间：".$res['time'].$style.$style;
                        $content .= "桌号：".$table['type_name'].'('.$table['name'].')'.$style;
                    }
                    $content .= "--------------------------------".$style;
                    if($res['order_type']==2&&$res['type']==1){
                        $content .= "自提时间：".$res['delivery_time'].$style;
                        $content .= "--------------------------------".$style;
                    }
                    if($res['order_type']==1&&$res['type']==1){
                        $content .= "送达时间：".$res['delivery_time'].$style;
                        $content .= "--------------------------------".$style;
                    }
                    $content .='名称'.str_repeat(" ",15)."数量  价格".$style;
                    $content .= "--------------------------------".$style;
                    $name='';
                    for($i=0;$i<count($res2);$i++){
                        $name=$res2[$i]['name'];
                        if($res2[$i]['spec']){
                            $name=$res2[$i]['name'].'('.$res2[$i]['spec'].')';
                        }
                        $content .= "".$name."$style";
                        $content .=str_repeat(" ",20).$res2[$i]['number']."   ".number_format($res2[$i]['number']*$res2[$i]['money'],2).$style;
                    }
                    $content .= "--------------------------------".$style;
                    if($res['type']==1){
                        $content .= "包装费：　　　　　　　　 ".$res['box_money'].$style;
                        if($res['order_type']==1){
                            $content .= "--------------------------------".$style;
                            $content .= "配送费：　　　　　　　　 ".$res['ps_money'].$style;
                        }
                        $content .= "--------------------------------".$style;
                        if($res['mj_money']>0){
                            $content .= "满减优惠：　　　　　　　-".number_format($res['mj_money'],2).$style;
                        }
                        if($res['xyh_money']>0){
                            $content .= "新用户立减：　　　　　　-".number_format($res['xyh_money'],2).$style;
                            $content .= "--------------------------------.".$style;
                        }
                        if($res['zk_money']>0){
                            $content .= "会员折扣：　　　　　　-".number_format($res['zk_money'],2).$style;
                            $content .= "--------------------------------.".$style;
                        }

                        if($res['pay_type']==5){
                            $content .= "应付：　　　　　　　　　 ".$res['money'].$style;
                        }else{
                            $content .= "已付：　　　　　　　　　 ".$res['money'].$style;
                        }

                        $content .= "--------------------------------".$style;
                        $content .= "流水号：".$res['order_num'].$style;
                        $content .= "送货地点：".$res['address'].$style;
                        $content .= "联系电话：".$res['tel'].$style;
                        $content .= "联系人：".$res['name'].$style;
                    }
                    if($res['type']==2){

                        if($res['mj_money']>0){
                            $content .= "满减优惠：　　　　　　　-".number_format($res['mj_money'],2).$style;
                            $content .= "--------------------------------.".$style;
                        }
                        $content .= "已付：　　　　　　　　　 ".$res['money'].$style;
                        $content .= "--------------------------------".$style;
                        $content .= "流水号：".$res['order_num'].$style;
                    }
                    if($res['note']){
                        $content .= "备注：".$res['note'].$style;
                    }
                }
                if($value['type']==1){//365
                    $rst=Dyj::dy($value['dyj_id'],$content,$value['dyj_key']);

                }
                if($value['type']==2){//易联云
                    $rst=Dyj::ylydy($value['api'],$value['token'],$value['yy_id'],$value['mid'],$content);
                }
                if($value['type']==3){//飞蛾
                    $rst=Dyj::fedy($value['fezh'],$value['fe_ukey'],$value['fe_dycode'],$content,$value['num']);

                }
                if($value['type']==4){//喜讯
                    $url="115.28.15.113:60002";
                    $pages=empty($value['num'])?1:$value['num'];
                    $content .= "<0D0A><0D0A><0D0A><0D0A>";
                    $data = array(
                        'dingdanID'=>'dingdanID='.$res['order_num'], //订单号
                        'dayinjisn'=>'dayinjisn='.$value['xx_sn'], //打印机ID号
                        'dingdan'=>'dingdan='.$content, //订单内容
                        'pages'=>'pages='.$pages, //联数
                        'replyURL'=>'replyURL=1'); //回复确认URL
                    $post_data = implode('&',$data);
                    $rst =Dyj::postData($url, $post_data);
                    //var_dump($rst);die;

                }

            }

        }

    }



    public function doPageHcPrint(){ //后厨打印
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/print/hcdyj.php';
        $res=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        $sql="select a.*,b.label_id from".tablename('pintuan_order_goods')."a left join ".tablename('pintuan_goods')." b on a.dishes_id=b.id  where a.order_id={$_GPC['order_id']}";
        $res2=pdo_fetchall($sql);
        if($res['type']==2){
            //$table = pdo_get('pintuan_table', array('id' => $res['table_id']));
            $sql=" select a.name,b.name as type_name from ".tablename('pintuan_table')." a left join ".tablename('pintuan_table_type')." b on a.type_id=b.id where a.id={$res['table_id']}";
            $table=pdo_fetch($sql);
        }
        $print=pdo_get('pintuan_storeset',array('store_id'=>$res['store_id']),array('is_jd','print_mode'));

        if(($print['is_jd']==1)or($print['print_mode']==1&&$res['state']==2) or ($print['print_mode']==1&&($res['dn_state']==1 or $res['dn_state']==2))){
            $result =   array();

            //判断打印类型
            $type=pdo_get('pintuan_storeset',array('store_id'=>$res['store_id']));
            if($type['print_type']==2){
                //按相同标签组成新的数组
                foreach($res2 as $k=>$v){
                    $result[$v['label_id']][]    =   $v;
                }
                foreach ($result as $key => $value) {
                    $content1='';
                    $res3=pdo_getall('pintuan_dyj',array('store_id'=>$res['store_id'],'state'=>1,'location'=>2,'tag_id'=>$key));
                    $content1 .= "         订单编号  #".$_GPC['order_id']."\n\n";

                    $content1 .= "       ".$res3[0]['dyj_title']."\n\n";
                    $content1 .= "开台时间：".$res['time']."\n\n";
                    if($res['type']==2){
                        $content1 .= "桌号：".$table['type_name'].'('.$table['name'].")\n\n";
                    }
                    $content1 .= "--------------------------------"."\n";
                    $content1 .='名称'.str_repeat(" ",15)."数量\n\n";
                    $content='';
                    foreach ($value as $key2 => $value2) {
                        $content .= "".$value2['name']."(".$value2['spec'].")\n";
                        $content .=str_repeat(" ",20).$value2['number']."\n";
                    }

                    if($res3){
                        foreach ($res3 as $key3 => $value3) {
                            $content=$content1.$content;

                            if($value3['type']==1){//365
                                $rst=Hcdyj::dy($value3['dyj_id'],$content,$value3['dyj_key']);

                            }
                            if($value3['type']==2){//易联云
                                $rst=Hcdyj::ylydy($value3['api'],$value3['token'],$value3['yy_id'],$value3['mid'],$content);
                            }
                            if($value3['type']==3){//飞蛾
                                $rst=Hcdyj::fedy($value3['fezh'],$value3['fe_ukey'],$value3['fe_dycode'],$content,$value3['num']);
                            }
                            if($value['type']==4){//喜讯
                                $url="115.28.15.113:60002";
                                $pages=empty($value3['num'])?1:$value3['num'];
                                $data = array(
                                    'dingdanID'=>'dingdanID='.$res['order_num'], //订单号
                                    'dayinjisn'=>'dayinjisn='.$value3['xx_sn'], //打印机ID号
                                    'dingdan'=>'dingdan='.$content, //订单内容
                                    'pages'=>'pages='.$pages, //联数
                                    'replyURL'=>'replyURL=1'); //回复确认URL
                                $post_data = implode('&',$data);
                                $rst =Hcdyj::postData($url, $post_data);

                            }
                        }
                    }
                }

            }else{
                $store=pdo_get('pintuan_store',array('id'=>$res['store_id']),'name');
                $res3=pdo_getall('pintuan_dyj',array('store_id'=>$res['store_id'],'state'=>1,'location'=>2));
                if($res['pay_type']==1){
                    $is_yue="微信支付";
                }elseif($res['pay_type']==2){
                    $is_yue="余额支付";
                }elseif($res['pay_type']==3){
                    $is_yue="积分支付";
                }
                elseif($res['pay_type']==4){
                    $is_yue="货到付款";
                } elseif($res['pay_type']==5){
                    $is_yue="餐后付款";
                }
                if($res['order_type']==2){
                    $is_take= "店内自提";
                }elseif($res['order_type']==1){
                    $is_take="外卖配送";
                }elseif($res['order_type']==3){
                    $is_take="预约到店";
                }
                $content .= "         ".$store['name']."\n\n";
                $content .= "         订单编号  #".$res['id']."\n\n";
                if($res['type']==1){
                    $content .= "          ".$is_take. "\n\n";
                    $content .= "          ".$res3['0']['dyj_title']."\n\n";
                    $content .= "------------".$is_yue."------------"."\n\n";

                    $content .= "--------------------------------"."\n\n";
                    $content .= "下单时间：".$res['time']."\n";
                }
                if($res['type']==2){
                    $content .= "------------".$is_yue."------------"."\n";
                    $content .= "--------------------------------"."\n";
                    $content .= "开台时间：".$res['time']."\n"."\n";
                    $content .= "桌号：".$table['type_name'].'('.$table['name'].')'."\n";
                }
                $content .= "--------------------------------"."\n";
                if($res['order_type']==2&&$res['type']==1){
                    $content .= "自提时间：".$res['delivery_time'].$style;
                    $content .= "--------------------------------".$style;
                }
                if($res['order_type']==1&&$res['type']==1){
                    $content .= "送达时间：".$res['delivery_time'].$style;
                    $content .= "--------------------------------".$style;
                }
                $content .='名称'.str_repeat(" ",15)."数量  价格"."\n";
                $content .= "--------------------------------"."\n";
                $name='';
                for($i=0;$i<count($res2);$i++){
                    $name=$res2[$i]['name'];
                    if($res2[$i]['spec']){
                        $name=$res2[$i]['name'].'('.$res2[$i]['spec'].')';
                    }
                    $content .= "".$name."\n";
                    $content .=str_repeat(" ",20).$res2[$i]['number']."   ".number_format($res2[$i]['number']*$res2[$i]['money'],2)."\n";
                }
                $content .= "--------------------------------"."\n";
                if($res['type']==1){
                    $content .= "包装费：　　　　　　　　 ".$res['box_money']."\n";
                    $content .= "--------------------------------"."\n";
                    $content .= "配送费：　　　　　　　　 ".$res['ps_money']."\n";
                    $content .= "--------------------------------"."\n";
                    if($res['mj_money']>0){
                        $content .= "满减优惠：　　　　　　　-".number_format($res['mj_money'],2)."\n";
                    }
                    if($res['xyh_money']>0){
                        $content .= "新用户立减：　　　　　　-".number_format($res['xyh_money'],2)."\n";
                        $content .= "--------------------------------."."\n";
                    }
                    if($res['zk_money']>0){
                        $content .= "会员折扣：　　　　　　-".number_format($res['zk_money'],2)."\n";
                        $content .= "--------------------------------."."\n";
                    }


                    $content .= "已付：　　　　　　　　　 ".$res['money']."\n";
                    $content .= "--------------------------------"."\n";
                    $content .= "流水号：".$res['order_num']."\n";
                    $content .= "送货地点：".$res['address']."\n";
                    $content .= "联系电话：".$res['tel']."\n";
                    $content .= "联系人：".$res['name']."\n";
                }
                if($res['type']==2){

                    if($res['mj_money']>0){
                        $content .= "满减优惠：　　　　　　　-".number_format($res['mj_money'],2)."\n";
                        $content .= "--------------------------------."."\n";
                    }
                    $content .= "已付：　　　　　　　　　 ".$res['money']."\n";
                    $content .= "--------------------------------"."\n";
                    $content .= "流水号：".$res['order_num']."\n";
                }
                if($res['note']){
                    $content .= "备注：".$res['note']."\n";
                }
                // $content1 .= "         订单编号  #".$_GPC['order_id']."\n\n";
                //   	$content1 .= "          ".$res3[0]['dyj_title']."\n\n";
                // $content1 .= "下单时间：".$res['time']."\n\n";
                // $content1 .='名称'.str_repeat(" ",15)."数量\n\n";
                // $content1 .= "--------------------------------"."\n";
                // $content1='';
                // foreach ($res2 as $key2 => $value2) {
                // 	$content1 .= "".$value2['name']."\n";
                // 	$content 1.=str_repeat(" ",20).$value2['number']."\n";
                // }

                foreach ($res3 as $key => $value) {
                    $content=$content1.$content;
                    if($value['type']==1){//365
                        $rst=Hcdyj::dy($value['dyj_id'],$content,$value['dyj_key']);

                    }
                    if($value['type']==2){//易联云
                        $rst=Hcdyj::ylydy($value['api'],$value['token'],$value['yy_id'],$value['mid'],$content);
                    }
                    if($value['type']==3){//飞蛾
                        $rst=Hcdyj::fedy($value['fezh'],$value['fe_ukey'],$value['fe_dycode'],$content,$value['num']);
                    }
                    if($value['type']==4){//喜讯
                        $url="115.28.15.113:60002";
                        $pages=empty($value['num'])?1:$value['num'];
                        $data = array(
                            'dingdanID'=>'dingdanID='.$res['order_num'], //订单号
                            'dayinjisn'=>'dayinjisn='.$value['xx_sn'], //打印机ID号
                            'dingdan'=>'dingdan='.$content, //订单内容
                            'pages'=>'pages='.$pages, //联数
                            'replyURL'=>'replyURL=1'); //回复确认URL
                        $post_data = implode('&',$data);
                        $rst =Hcdyj::postData($url, $post_data);
                    }

                }
            }

        }

    }


//快服务
    public function doPagekfw(){
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/peisong/peisong.php';
        $order=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        $set=pdo_get('pintuan_kfwset',array('store_id'=>$order['store_id']));
        $storeInfo=pdo_get('pintuan_store',array('id'=>$order['store_id']));
        $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        $city=explode(',',$order['area']);
        $city=$city['1'];
        if(!$set['access_token']){//绑定商户
            $res=file_get_contents("http://api.kfw.net/quanta/d/bind?user_id=".$set['user_id']."&app_id=".$system['kfw_appid']);
            $res=json_decode($res);
            $data['access_token']=$res->data->access_token;
            $data['openid']=$res->data->openid;
            pdo_update('pintuan_kfwset',$data,array('store_id'=>$order['store_id']));
            $set=pdo_get('pintuan_kfwset',array('store_id'=>$order['store_id']));
        }
        $sender_zb=explode(',',$storeInfo['coordinates']);
        $sender_zb=peisong::coordinate_switchf($sender_zb[0],$sender_zb[1]);
        $zb=peisong::coordinate_switchf($order['lat'],$order['lng']);
        //下订单
        $data = array(
            'app_id'=>  $system['kfw_appid'],
            'access_token'=> $set['access_token'],
            'order_id'=> $order['order_num'],
            'business'=> '1',
            'openid'=> $set['openid'],
            'goods_info'=> '食物',
            'goods_price'=> '0',
            'sender_address'=>$storeInfo['address'],
            'sender_city'=>$city,
            'sender_tel'=>$storeInfo['tel'],
            'receiver_address'=>$order['address'],
            'receiver_city'=>$city,
            'receiver_tel'=>$order['tel'],
            'sender_lat'=>$sender_zb['Latitude'],
            'sender_lng'=>$sender_zb['Longitude'],
            'receiver_lat'=>$zb['Latitude'],
            'receiver_lng'=>$zb['Longitude'],
            'callback_url'=>$_W['siteroot']."addons/zh_jd/payment/peisong/notify2.php",

        );
        $obj= new KfwOpenapi();
        $sign=$obj->getSign($data,$system['kfw_appsecret']);
        $data['sign']=$sign;
        $url="http://openapi.kfw.net/openapi/v1/order/add";
        $result=$obj->requestWithPost($url,$data);
        // var_dump(json_decode($result));die;
        return json_decode($result)->ship_id;
    }




//呼叫服务员

    public function  doPageVoiceCall(){
        global $_W, $_GPC;
        $store_id=$_GPC['store_id'];
        $id=$_GPC['id'];
        $store=pdo_get('pintuan_call',array('store_id'=>$store_id));
        $table=pdo_get('pintuan_table',array('id'=>$id));
        $appid=$store['appid'];
        $appkey=$store['apikey'];
        //var_dump($store);die;
        //$number=$_GPC['number'];
        $number=$table['name'];
        $num=2;
        for($i=0;$i<$num;$i++){
            $content.=$number."呼叫服务员  ";
        }
        //echo $content;die;
        //$content=$number."呼叫服务员".$number."呼叫服务员";
        $src=file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=voiceTts&m=pintuan&content=".$content."&appid=".$appid."&appkey=".$appkey."&id=".$id);
        //var_dump($src);die;
        pdo_update('pintuan_call',array('src'=>$src),array('store_id'=>$_GPC['store_id']));
        //$src2=file_get_contents("{$_W['siteroot']}/app/index.php?i={$_W['uniacid']}&c=entry&do=newcall&m=pintuan&src={$src}&type=2&store_id={$store_id}");
        file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=SaveCallLog&m=pintuan&id={$id}&store_id={$store_id}");
        //var_dump($src2);die;
        //echo "<audio id='myaudio' src='{$src}' autoplay='autoplay' controls='controls'  hidden='true' ></audio>";
        return $src;
    }


//语音测试(文字转语音)
    public function doPagevoiceTts(){
        global $_W, $_GPC;
        $content=$_GPC['content'];
        $appid=$_GPC['appid'];
        $appkey=$_GPC['appkey'];
        $id=$_GPC['id'];
        $output_path="../addons/pintuan/call/test".$id.".wav";
        $param = [ 'engine_type' => 'intp65',
            'auf' => 'audio/L16;rate=16000',
            'aue' => 'raw',
            'voice_name' => 'xiaoyan',
            'speed' => '0'
        ];
        $cur_time = (string)time();
        $x_param = base64_encode(json_encode($param));
        $header_data = ['X-Appid:'.$appid,
            'X-CurTime:'.$cur_time,
            'X-Param:'.$x_param,
            'X-CheckSum:'.md5($appkey.$cur_time.$x_param),
            'Content-Type:application/x-www-form-urlencoded; charset=utf-8'
        ];
        $body_data = 'text='.urlencode($content);    //Request
        $url = "http://api.xfyun.cn/v1/service/v1/tts";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body_data);
        $result = curl_exec($ch);
        $res_header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $res_header = substr($result, 0, $res_header_size);
        curl_close($ch);
        if(stripos($res_header, 'Content-Type: audio/mpeg') === FALSE){ //合成错误
            return substr($result, $res_header_size);
        }else{
            file_put_contents($output_path, substr($result, $res_header_size));
            //echo   "<audio src='{$output_path}' autoplay='autoplay' controls='controls'  hidden='true' ></audio>";die;
            //return '语音合成成功，请查看文件！';
            return $output_path;
        }
    }


//保存呼叫记录
    public function doPageSaveCallLog(){
        global $_W, $_GPC;
        $data['store_id']=$_GPC['store_id'];
        $data['table_id']=$_GPC['id'];
        $data['user_id']=$_GPC['user_id'];
        $data['state']=1;
        $data['time']=time();
        $data['uniacid']=$_W['uniacid'];
        $res=pdo_insert('pintuan_calllog',$data);
    }


//排队获取桌位信息
    public function doPageGetTable(){
        global $_W, $_GPC;
        $time=strtotime(date("Y-m-d"));
        $del="delete from ".tablename('pintuan_number')." where uniacid={$_W['uniacid']} and unix_timestamp(time)< {$time}";
        pdo_query($del);
        $sql=" select * from ".tablename('pintuan_numbertype')." where store_id={$_GPC['store_id']} and uniacid={$_W['uniacid']} order by sort asc";
        $list=pdo_fetchall($sql);
        foreach($list as $key => $value){
            $num=$value['typename'];
            $newsql=" select count(id) as total from ".tablename('pintuan_number')." where uniacid={$_W['uniacid']} and store_id={$_GPC['store_id']}  and num='{$num}' and state=1  order by id asc";
            $res=pdo_fetch($newsql);
            $list[$key]['wait']=$res['total'];

        }

        echo json_encode($list);
    }

//取号
    public function doPageSaveNumber(){
        global $_W, $_GPC;
        //判断是否可以取号
        $mynumber=pdo_get('pintuan_number',array('user_id'=>$_GPC['user_id'],'store_id'=>$_GPC['store_id']));
        if($mynumber['state']!=1 or !$mynumber ){
            $time=date("Y-m-d",time());
            $data['store_id']=$_GPC['store_id'];
            $data['num']=$_GPC['typename'];
            //$data['people']=$_GPC['people'];
            $data['user_id']=$_GPC['user_id'];
            $data['state']=1;
            $data['time']=date('Y-m-d H:i:s');
            $data['uniacid']=$_W['uniacid'];
            $sql=" select id,code from".tablename('pintuan_number')." where  store_id={$_GPC['store_id']} and time LIKE '%{$time}%' order by id desc ";
            $rst=pdo_fetch($sql);
            if($rst){
                $str=substr($rst['code'],1);
                $preg = '/[0]*/';
                $result = preg_replace($preg, '', $str, 1);
                $num=$result+1;
                $code=substr("000".$num,-4);
                $data['code']='A'.$code;
            }else{
                $data['code']="A0001";
            }
            $res=pdo_insert('pintuan_number',$data);
            $num_id=pdo_insertid();
            if($res){
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=NumberMessage&m=pintuan&num_id=".$num_id);//模板消息
                echo $num_id;
            }else{
                echo '取号失败';
            }

        }else{
            echo '重复领号'.$mynumber['id'];
        }
    }


//判断是否取号
    public  function doPageIsReceive(){
        global $_W, $_GPC;
        $mynumber=pdo_getall('pintuan_number',array('user_id'=>$_GPC['user_id'],'store_id'=>$_GPC['store_id'],'state !='=>4),array(),'','id desc');
        echo json_encode($mynumber[0]);
    }


//号详情
    public function doPageNumberDetails(){
        global $_W, $_GPC;
        $sql=" select a.id,a.code,a.num,a.time,a.state,a.people,a.store_id,b.name as store_name  from".tablename('pintuan_number')." a left join".tablename('pintuan_store')."b on a.store_id=b.id  where  a.id={$_GPC['num_id']}  ";
        $details=pdo_fetch($sql);
        $newsql=" select count(id) as count from  ".tablename('pintuan_number')." where uniacid={$_W['uniacid']} and store_id={$details['store_id']}  and num='{$details['num']}' and state=1  and id<{$_GPC['num_id']}";
        $res=pdo_fetch($newsql);
        $details['wait']=$res['count'];
        echo json_encode($details);
    }


//删除号码
    public function doPageDelNumber(){
        global $_W, $_GPC;
        $res=pdo_update('pintuan_number',array('state'=>4),array('id'=>$_GPC['num_id']));
        echo json_encode($res);


    }


//下订单发短信
    public function doPageSms(){
        global $_W, $_GPC;
        $res = pdo_get('pintuan_sms', array('store_id' => $_GPC['store_id']));
        if($res['item']==1){
            if($_GPC['type']==1){//外卖
                $tpl_id = $res['wm_tid'];
                $kg = $res['is_wm'];
            }elseif($_GPC['type']==2){//店内
                $tpl_id = $res['dn_tid'];
                $kg = $res['is_dn'];
            }elseif($_GPC['type']==3){//预约
                $tpl_id = $res['yy_tid'];
                $kg = $res['is_yy'];
            }
            $tel = $res['tel'];
            $key = $res['appkey'];
            if($kg==1){
                $url = "http://v.juhe.cn/sms/send?mobile=".$tel."&tpl_id=".$tpl_id."&tpl_value=%23code%23%3D654654&key=".$key;
                $data = file_get_contents($url);
                print_r($data);
            }
        }
        if($res['item']==2){
            include IA_ROOT.'/addons/pintuan/txsms/SmsSingleSender.php';
            $appid = $res['appid'];; // 1400开头
            $appkey = $res['tx_appkey'];;
            $phoneNumbers = $res['tel'];;
            if($_GPC['type']==1){//外卖
                $templateId = $res['wm_tid'];
                $kg = $res['is_wm'];
            }elseif($_GPC['type']==2){//店内
                $templateId = $res['dn_tid'];
                $kg = $res['is_dn'];
            }elseif($_GPC['type']==3){//预约
                $templateId = $res['yy_tid'];
                $kg = $res['is_yy'];
            }
            $smsSign = "iMacau";
            if($kg==1){
                try {
                    $ssender = new SmsSingleSender($appid, $appkey);
                    $params = [];
                    $result = $ssender->sendWithParam($res['code'], $phoneNumbers, $templateId,
                        $params, $smsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
                    $rsp = json_decode($result);
                    echo $result;
                } catch(\Exception $e) {
                    echo var_dump($e);
                }
            }
        }


    }




//开启多人点菜
    public function doPageDrShop(){
        global $_W, $_GPC;
        $data['store_id']=$_GPC['store_id'];
        $data['user_id']=$_GPC['user_id'];
        $data['state']=1;//开启
        $data['time']=date('Y-m-d H:i:s');
        $data['uniacid']=$_W['uniacid'];
        $res=pdo_insert('pintuan_drorder',$data);
        $id=pdo_insertid();
        if($res){
            echo  $id;
        }else{
            echo  '请稍后重试';
        }
    }
//完成多人点菜
    public function doPageWcDrShop(){
        global $_W, $_GPC;
        $res=pdo_update('pintuan_drorder',array('state'=>3),array('id'=>$_GPC['id']));
        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }
//锁定多人点菜
    public function doPageSdDrShop(){
        global $_W, $_GPC;
        $res=pdo_update('pintuan_drorder',array('state'=>2),array('id'=>$_GPC['id']));
        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }
//解锁多人点菜
    public function doPageJsDrShop(){
        global $_W, $_GPC;
        $res=pdo_update('pintuan_drorder',array('state'=>1),array('id'=>$_GPC['id']));
        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }
//多人点菜列表
    public function doPageDrShopList(){
        global $_W, $_GPC;
        $user=pdo_get('pintuan_user',array('id'=>$_GPC['user_id']));
        $sql="select a.*,b.name,b.logo from".tablename('pintuan_shopcar')." a"  . " left join " . tablename("pintuan_goods") . " b on b.id=a.good_id where  a.store_id={$_GPC['store_id']} and a.user_id={$_GPC['user_id']} and a.son_id=0 and a.type=2";
        $res=pdo_fetchall($sql);
        $num2=0;
        $money2=0;
        for($j=0;$j<count($res);$j++){
            $money2=$res[$j]['num']*$res[$j]['money']+$money2;
            $num2=$res[$j]['num']+$num2;
        }
        $data=array(
            'user_name'=>$user['name'],
            'user_img'=>$user['img'],
            'good'=>$res
        );

        $sql2=" select distinct son_id from".tablename('pintuan_shopcar')." where  store_id={$_GPC['store_id']} and dr_id={$_GPC['dr_id']} and user_id={$_GPC['user_id']} and son_id !=0 and type=2";
        $res2=pdo_fetchall($sql2);
        $sql4=" select * from".tablename('pintuan_shopcar')." where  store_id={$_GPC['store_id']} and dr_id={$_GPC['dr_id']} and user_id={$_GPC['user_id']} and son_id !=0 and type=2";
        $res4=pdo_fetchall($sql4);
        $data2=array();
        $num=0;
        $money=0;
        for($k=0;$k<count($res4);$k++){
            $money=$res4[$k]['num']*$res4[$k]['money']+$money;
            $num=$res4[$k]['num']+$num;
        }
        for($i=0;$i<count($res2);$i++){
            $money=$res2[$i]['num']*$res2[$i]['money']+$money;
            $num=$res2[$i]['num']+$num;
            $user2=pdo_get('pintuan_user',array('id'=>$res2[$i]['son_id']));
            $sql3="select a.*,b.name,b.logo from".tablename('pintuan_shopcar')." a"  . " left join " . tablename("pintuan_goods") . " b on b.id=a.good_id where  a.store_id={$_GPC['store_id']} and a.user_id={$_GPC['user_id']} and a.son_id={$res2[$i]['son_id']} and a.dr_id={$_GPC['dr_id']} and a.type=2";
            $res3=pdo_fetchall($sql3);
            $data2[]=array(
                'user_name'=>$user2['name'],
                'user_img'=>$user2['img'],
                'son_id'=>$user2['id'],
                'good'=>$res3
            );
        }
        $dr=pdo_get('pintuan_drorder',array('user_id'=>$_GPC['user_id'],'store_id'=>$_GPC['store_id'],'state !='=>3));
        $data3=array(
            'user'=>$data,
            'son'=>$data2,
            'good_num'=>$num+$num2,
            'people'=>count($res2)+1,
            'money'=>$money+$money2,
            'drorder'=>$dr
        );
// print_R($data3);die;
        echo  json_encode($data3);
    }
//查看我的多人点菜
    public function doPageIsDr(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_drorder',array('user_id'=>$_GPC['user_id'],'store_id'=>$_GPC['store_id'],'state !='=>3));
        if($res){
            echo $res['id'];
        }else{
            echo '请重新开启拼单';
        }
    }

//入驻支付
    public function doPageRzPay(){
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/wxpay.php';
        $res=pdo_get('pintuan_pay',array('uniacid'=>$_W['uniacid']));
        $res2=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        if($res2['url_name']){
            $res2['url_name']=$res2['url_name'];
        }else{
            $res2['url_name']='餐饮小程序';
        }
        $appid=$res2['appid'];
        $openid=$_GPC['openid'];//oQKgL0ZKHwzAY-KhiyEEAsakW5Zg
        $mch_id=$res['mchid'];
        $key=$res['wxkey'];
        $out_trade_no = $mch_id. time();
        $root=$_W['siteroot'];
        pdo_update('pintuan_store',array('code'=>$out_trade_no),array('id'=>$_GPC['rz_id']));
        $total_fee =$_GPC['money'];
        if(empty($total_fee)) //押金
        {
            $body = $res2['url_name'];
            $total_fee = floatval(99*100);
        }else{
            $body = $res2['url_name'];
            $total_fee = floatval($total_fee*100);
        }
        $weixinpay = new WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$root);
        $return=$weixinpay->pay();
        echo json_encode($return);
    }

//入驻期限
    public function doPageGetRzqx(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_rzqx',array('uniacid'=>$_W['uniacid']),array(),'','num ASC');
        echo json_encode($res);
    }


//门店入驻
    public function doPageSaveRzsq(){
        global $_W, $_GPC;
        $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']),array('md_sh','md_sf'));
        $data['name'] = $_GPC['name'];
        $data['sq_id'] = $_GPC['user_id'];
        $data['user_id'] = $_GPC['user_id'];
        $data['admin_id'] = $_GPC['user_id'];
        $data['address'] = $_GPC['address'];
        $data['details'] =html_entity_decode($_GPC['details']);
        $data['rz_time'] = $_GPC['rz_time'];
        $data['state'] = 1;
        if(strlen($_GPC['logo'])<48){
            $data['logo']=$_W['attachurl'].$_GPC['logo'];
        }else{
            $data['logo']=$_GPC['logo'];
        }
        $data['yyzz']=$_GPC['yyzz'];
        $data['fm_img']=$_GPC['fm_img'];
        $data['zm_img']=$_GPC['zm_img'];
        $data['link_tel'] = $_GPC['link_tel'];
        $data['tel'] = $_GPC['link_tel'];
        $data['link_name'] = $_GPC['link_name'];
        $data['sq_time'] = date("Y-m-d H:i:s");
        $data['coordinates'] = $_GPC['coordinates'];
        $data['is_open']=1;
        $data['money']=$_GPC['money'];
        $data['uniacid'] = $_W['uniacid'];
        if($_GPC['id']==''){
            if($_GPC['money']>0){
                $data['zf_state'] = 1;
            }else{
                $data['zf_state'] = 2;
            }
            $res = pdo_insert('pintuan_store', $data);
            $rz_id = pdo_insertid();
        }else{
            $res = pdo_update('pintuan_store', $data,array('id'=>$_GPC['id']));
        }
        if ($res) {
            echo $rz_id;
        } else {
            echo '2';
        }
    }


//入驻记录
    public function doPageSaveRzLog(){
        global $_W, $_GPC;
        $data['store_id'] = $_GPC['store_id'];
        $data['money'] = $_GPC['money'];
        $data['time'] = time();
        $data['note']='入驻';
        $data['uniacid'] = $_W['uniacid'];
        $res = pdo_insert('pintuan_rzlog', $data);
        if($res){
            echo '1';
        }else{
            echo '2';
        }

    }
//短信验证码
    public function doPageSms2(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
        if($res['item']==1){
            $tpl_id=$res['tpl_id'];
            $tel=$_GPC['tel'];
            $code=$_GPC['code'];
            $key=$res['appkey'];
            $url = "http://v.juhe.cn/sms/send?mobile=".$tel."&tpl_id=".$tpl_id."&tpl_value=%23code%23%3D".$code."&key=".$key;
            $data=file_get_contents($url);
            print_r($data);
        }
        if($res['item']==2){
            include IA_ROOT.'/addons/pintuan/txsms/SmsSingleSender.php';
            $appid = $res['appid'];; // 1400开头
            $appkey = $res['tx_appkey'];;
            $phoneNumbers = $_GPC['tel'];;
            $templateId = $res['template_id'];
            $smsSign = "iMacau";
            try {
                $ssender = new SmsSingleSender($appid, $appkey);
                $params = [$_GPC['code']];
                $result = $ssender->sendWithParam($res['code'], $phoneNumbers, $templateId,
                    $params, $smsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
                $rsp = json_decode($result);
                echo $result;
            } catch(\Exception $e) {
                echo var_dump($e);
            }
        }
    }


    //是否入住
    public function doPageCheckRz(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_store',array('uniacid'=>$_W['uniacid'],'sq_id'=>$_GPC['user_id'],'zf_state'=>2));
        echo json_encode($res);
    }


    //是否开启短息
    public function doPageCheckSms(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']),'is_dxyz');
        echo json_encode($res);
    }



//入驻模板消息
    public function doPageRzMessage(){
        global $_W, $_GPC;
        function getaccess_token($_W){
            $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
            $appid=$res['appid'];
            $secret=$res['appsecret'];
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            $data = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($data,true);
            return $data['access_token'];
        }
        //设置与发送模板信息
        function set_msg($_W){
            $access_token = getaccess_token($_W);
            $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
            $res2=pdo_get('pintuan_store',array('id'=>$_GET['sh_id']));
            $user=pdo_get('pintuan_user',array('id'=>$res2['sq_id']));
            $state="等待审核";
            $note="1-3日完成审核";
            $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$res["rzsh_tid"].'",
           "page": "pintuan/pages/Liar/loginindex",
           "form_id":"'.$_GET['form_id'].'",
           "data": {
             "keyword1": {
               "value": "'.$state.'",
               "color": "#173177"
             },
             "keyword2": {
               "value":"'.$res2['sq_time'].'",
               "color": "#173177"
             },
             "keyword3": {
               "value": "'.$res2['name'].'",
               "color": "#173177"
             },
             "keyword4": {
               "value":  "'.$res2['link_tel'].'",
               "color": "#173177"
             },
             "keyword5": {
               "value": "'.$note.'",
               "color": "#173177"
             }
           }
         }';
            // $formwork=$data;
            $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$formwork);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }
        echo set_msg($_W);
    }




    public function doPageJcPrint(){ //前台打印
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/print/dyj.php';
        $res=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        $store=pdo_get('pintuan_store',array('id'=>$res['store_id']),'name');
        $res3=pdo_getall('pintuan_dyj',array('store_id'=>$res['store_id'],'state'=>1,'location'=>1));
        $ids=explode(',',$_GPC['good']);
        $res2=pdo_getall('pintuan_order_goods',array('id'=>$ids));
        $sql=" select a.name,b.name as type_name from ".tablename('pintuan_table')." a left join ".tablename('pintuan_table_type')." b on a.type_id=b.id where a.id={$res['table_id']}";
        $table=pdo_fetch($sql);
        foreach ($res3 as $key => $value) {
            $style="\n";
            if($value['type']==3){
                $style="<BR>";
            }
            $content = $style;
            if($value['type']==1&&$value['num']){
                $content = "^N".$value['num'];
            }
            $content .= "          ".$store['name'].$style.$style;
            $content .= "       订单编号  #".$res['id']." 加菜".$style.$style;
            $content .= "--------------------------------".$style;
            $content .= "桌号：".$table['type_name'].'('.$table['name'].')'.$style;
            $content .= "--------------------------------".$style;
            $content .='名称'.str_repeat(" ",15)."数量  价格".$style;
            $content .= "--------------------------------".$style;
            $name='';
            for($i=0;$i<count($res2);$i++){
                $name=$res2[$i]['name'];
                if($res2[$i]['spec']){
                    $name=$res2[$i]['name'].'('.$res2[$i]['spec'].')';
                }
                $content .= "".$name."$style";
                $content .=str_repeat(" ",20).$res2[$i]['number']."   ".number_format($res2[$i]['number']*$res2[$i]['money'],2).$style;
            }
            $content .= "--------------------------------".$style;
            $content .= "小计：　　　　　　　　　 ".$_GPC['money'].$style;
            $content .= "--------------------------------".$style;
            $content .= "流水号：".$res['order_num'].$style;

            if($value['type']==1){//365
                $rst=Dyj::dy($value['dyj_id'],$content,$value['dyj_key']);


            }
            if($value['type']==2){//易联云
                $rst=Dyj::ylydy($value['api'],$value['token'],$value['yy_id'],$value['mid'],$content);
            }
            if($value['type']==3){//飞蛾
                $rst=Dyj::fedy($value['fezh'],$value['fe_ukey'],$value['fe_dycode'],$content,$value['num']);
            }
            if($value['type']==4){//喜讯
                $url="115.28.15.113:60002";
                $pages=empty($value['num'])?1:$value['num'];
                $content .= "<0D0A><0D0A><0D0A><0D0A>";
                $data = array(
                    'dingdanID'=>'dingdanID='.$res['order_num'], //订单号
                    'dayinjisn'=>'dayinjisn='.$value['xx_sn'], //打印机ID号
                    'dingdan'=>'dingdan='.$content, //订单内容
                    'pages'=>'pages='.$pages, //联数
                    'replyURL'=>'replyURL=1'); //回复确认URL
                $post_data = implode('&',$data);
                $rst =Dyj::postData($url, $post_data);
                //var_dump($rst);die;

            }

        }

    }




    public function doPageJcPrint2(){ //后厨打印
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/print/hcdyj.php';
        $res=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        $ids=$_GPC['good'];
        //echo $ids;die;
        $sql="select a.*,b.label_id from".tablename('pintuan_order_goods')." a left join ".tablename('pintuan_goods')." b on a.dishes_id=b.id  where a.id in  ($ids)";
        $res2=pdo_fetchall($sql);
        $sql=" select a.name,b.name as type_name from ".tablename('pintuan_table')." a left join ".tablename('pintuan_table_type')." b on a.type_id=b.id where a.id={$res['table_id']}";
        $table=pdo_fetch($sql);

        $print=pdo_get('pintuan_storeset',array('store_id'=>$res['store_id']),array('is_jd','print_mode'));
        $result =   array();
        //判断打印类型
        $type=pdo_get('pintuan_storeset',array('store_id'=>$res['store_id']));
        if($type['print_type']==2){
            //按相同标签组成新的数组
            foreach($res2 as $k=>$v){
                $result[$v['label_id']][]    =   $v;
            }
            foreach ($result as $key => $value) {
                $res3=pdo_getall('pintuan_dyj',array('store_id'=>$res['store_id'],'state'=>1,'location'=>2,'tag_id'=>$key));
                $content1 .= "         订单编号  #".$_GPC['order_id']."\n\n";
                $content1 .= "          ".$res3[0]['dyj_title']."\n\n";
                $content1 .= "下单时间：".$res['time']."\n\n";
                $content1 .='名称'.str_repeat(" ",15)."数量\n\n";
                $content1 .= "--------------------------------"."\n";
                $content='';
                foreach ($value as $key2 => $value2) {
                    $content .= "".$value2['name']."\n";
                    $content .=str_repeat(" ",20).$value2['number']."\n";
                }
                if($res3){
                    foreach ($res3 as $key3 => $value3) {
                        $content=$content1.$content;
                        if($value3['type']==1){//365
                            $rst=Hcdyj::dy($value3['dyj_id'],$content,$value3['dyj_key']);


                        }
                        if($value['type']==2){//易联云
                            $rst=Hcdyj::ylydy($value3['api'],$value3['token'],$value3['yy_id'],$value3['mid'],$content);
                        }
                        if($value['type']==3){//飞蛾
                            $rst=Hcdyj::fedy($value3['fezh'],$value3['fe_ukey'],$value3['fe_dycode'],$content,$value3['num']);
                        }
                        if($value['type']==4){//喜讯
                            $url="115.28.15.113:60002";
                            $pages=empty($value3['num'])?1:$value3['num'];
                            $data = array(
                                'dingdanID'=>'dingdanID='.$res['order_num'], //订单号
                                'dayinjisn'=>'dayinjisn='.$value3['xx_sn'], //打印机ID号
                                'dingdan'=>'dingdan='.$content, //订单内容
                                'pages'=>'pages='.$pages, //联数
                                'replyURL'=>'replyURL=1'); //回复确认URL
                            $post_data = implode('&',$data);
                            $rst =Hcdyj::postData($url, $post_data);

                        }
                    }
                }
            }

        }else{
            $store=pdo_get('pintuan_store',array('id'=>$res['store_id']),'name');
            $res3=pdo_getall('pintuan_dyj',array('store_id'=>$res['store_id'],'state'=>1,'location'=>2));
            $content .= "         ".$store['name']."\n\n";
            $content .= "         订单编号  #".$res['id']."\n\n";
            $content .= "--------------------------------"."\n";
            $content .= "桌号：".$table['type_name'].'('.$table['name'].')'."\n";
            $content .= "--------------------------------"."\n";
            $content .='名称'.str_repeat(" ",15)."数量  价格"."\n";
            $content .= "--------------------------------"."\n";
            $name='';
            for($i=0;$i<count($res2);$i++){
                $name=$res2[$i]['name'];
                if($res2[$i]['spec']){
                    $name=$res2[$i]['name'].'('.$res2[$i]['spec'].')';
                }
                $content .= "".$name."\n";
                $content .=str_repeat(" ",20).$res2[$i]['number']."   ".number_format($res2[$i]['number']*$res2[$i]['money'],2)."\n";
            }
            $content .= "--------------------------------"."\n";
            $content .= "小计：　　　　　　　　　 ".$_GPC['money']."\n";
            $content .= "--------------------------------"."\n";
            $content .= "流水号：".$res['order_num']."\n";

            foreach ($res3 as $key => $value) {
                $content=$content1.$content;
                if($value['type']==1){//365
                    $rst=Hcdyj::dy($value['dyj_id'],$content,$value['dyj_key']);

                }
                if($value['type']==2){//易联云
                    $rst=Hcdyj::ylydy($value['api'],$value['token'],$value['yy_id'],$value['mid'],$content);
                }
                if($value['type']==3){//飞蛾
                    $rst=Hcdyj::fedy($value['fezh'],$value['fe_ukey'],$value['fe_dycode'],$content,$value['num']);
                }
                if($value['type']==4){//喜讯
                    $url="115.28.15.113:60002";
                    $pages=empty($value3['num'])?1:$value['num'];
                    $data = array(
                        'dingdanID'=>'dingdanID='.$res['order_num'], //订单号
                        'dayinjisn'=>'dayinjisn='.$value['xx_sn'], //打印机ID号
                        'dingdan'=>'dingdan='.$content, //订单内容
                        'pages'=>'pages='.$pages, //联数
                        'replyURL'=>'replyURL=1'); //回复确认URL
                    $post_data = implode('&',$data);
                    $rst =Hcdyj::postData($url, $post_data);
                }
            }
        }

    }






//商家登录
    public function doPageStoreLogin(){
        global $_GPC, $_W;
        load()->model('user');
        $member = array();
        $member['username'] =$_GPC['user'];
        $member['password'] = $_GPC['password'];
        $record = user_single($member);
        if(!empty($record)) {
            $account = pdo_fetch("SELECT * FROM " . tablename("pintuan_account") . " WHERE status=2 AND uid=:uid ORDER BY id DESC LIMIT 1", array(':uid' => $record['uid']));
            if (!empty($account)) {
                echo json_encode($account);
            } else {
                echo '您的账号正在审核或是已经被系统禁止，请联系网站管理员解决！';
            }
        }else{
            echo '账号或密码错误';
        }
    }
//商家微信登录
    public function doPageStoreWxLogin(){
        global $_GPC, $_W;
        $res=pdo_get('pintuan_store',array('admin_id'=>$_GPC['user_id'],'state'=>2));
        if($res){
            echo json_encode($res);
        }else{
            echo '您还不是管理员';
        }
    }

//商家订单
    public function doPageStoreWmOrder(){
        global $_GPC, $_W;
        if($_GPC['zt']==1){
            $where=" WHERE a.store_id=".$_GPC['store_id']." and a.state in (".$_GPC['state'].") and a.type=1 and a.order_type=2";
        }elseif($_GPC['zt']==2){
            $where=" WHERE a.store_id=".$_GPC['store_id']." and a.state in (".$_GPC['state'].") and a.type=1  and a.order_type=1";
        }else{
            $where=" WHERE a.store_id=".$_GPC['store_id']." and a.state in (".$_GPC['state'].") and a.type=1";
        }
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=empty($_GPC['pagesize'])?10:$_GPC['pagesize'];
        $sql="select  a.*,b.ps_mode  from " . tablename("pintuan_order") ." a left join ".tablename('pintuan_storeset')." b on a.store_id=b.store_id".$where." order by a.id DESC";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $list = pdo_fetchall($select_sql);
        $good=pdo_getall('pintuan_order_goods',array('uniacid'=>$_W['uniacid']));
        $data2=array();
        for($i=0;$i<count($list);$i++){
            $data=array();
            $num=0;
            for($k=0;$k<count($good);$k++){
                if($list[$i]['id']==$good[$k]['order_id']){
                    $data[]=array(
                        'good_id'=>$good[$k]['dishes_id'],
                        'img'=>$good[$k]['img'],
                        'number'=>$good[$k]['number'],
                        'name'=>$good[$k]['name'],
                        'money'=>$good[$k]['money'],
                        'spec'=>$good[$k]['spec']
                    );
                    $num=$num+$good[$k]['number'];
                }
            }
            $data2[]=array(
                'order'=>$list[$i],
                'good'=>$data,
                'num'=>$num
            );
        }
        echo  json_encode($data2);

    }
//商家店内订单
    public function doPageStoreDnOrder(){
        global $_GPC, $_W;
        $where=" WHERE a.store_id=".$_GPC['store_id']." and a.dn_state in (".$_GPC['dn_state'].") and a.type=2";
        if($_GPC['table_id']){
            $where .=" and a.table_id=".$_GPC['table_id'];
        }
        if($_GPC['time']=='today'){
            $time=date("Y-m-d",time());
            $where.="  and a.time LIKE '%{$time}%' ";
        }
        if($_GPC['time']=='yesterday'){
            $time=date("Y-m-d",strtotime("-1 day"));
            $where.="  and a.time LIKE '%{$time}%' ";
        }
        if($_GPC['time']=='week'){
            $time=strtotime(date("Y-m-d",strtotime("-7 day")));
            $where.=" and UNIX_TIMESTAMP(a.time) >".$time;
        }
        if($_GPC['time']=='month'){
            $time=date("Y-m");
            $where.="  and a.time LIKE '%{$time}%' ";
        }

        if($_GPC['start_time']){
            $start=strtotime($_GPC['start_time']);
            $end=strtotime($_GPC['end_time']);
            $where.=" and UNIX_TIMESTAMP(a.time) >='{$start}' and UNIX_TIMESTAMP(a.time) <='{$end}'";
        }
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        $sql="select  a.*,b.name as table_name,c.name as table_typename,b.status  from " . tablename("pintuan_order") ." a left join ".tablename('pintuan_table')." b on a.table_id=b.id left join ".tablename('pintuan_table_type')." c on b.type_id=c.id ".$where." order by a.id DESC";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $list = pdo_fetchall($select_sql);
        $good=pdo_getall('pintuan_order_goods',array('uniacid'=>$_W['uniacid']));
        $data2=array();
        for($i=0;$i<count($list);$i++){
            $data=array();
            $num=0;
            for($k=0;$k<count($good);$k++){
                if($list[$i]['id']==$good[$k]['order_id']){
                    $data[]=array(
                        'good_id'=>$good[$k]['dishes_id'],
                        'img'=>$good[$k]['img'],
                        'number'=>$good[$k]['number'],
                        'name'=>$good[$k]['name'],
                        'money'=>$good[$k]['money'],
                        'spec'=>$good[$k]['spec']
                    );
                    $num=$num+$good[$k]['number'];
                }
            }
            $data2[]=array(
                'order'=>$list[$i],
                'good'=>$data,
                'num'=>$num
            );
        }
        echo  json_encode($data2);
    }


//商家店内订单
    public function doPageStoreDmOrder(){
        global $_GPC, $_W;
        $where=" WHERE a.store_id=".$_GPC['store_id']." and  a.type=4 and dm_state=2";
        if($_GPC['time']=='today'){
            $time=date("Y-m-d",time());
            $where.="  and a.time LIKE '%{$time}%' ";
        }
        if($_GPC['time']=='yesterday'){
            $time=date("Y-m-d",strtotime("-1 day"));
            $where.="  and a.time LIKE '%{$time}%' ";
        }
        if($_GPC['time']=='week'){
            $time=strtotime(date("Y-m-d",strtotime("-7 day")));
            $where.=" and UNIX_TIMESTAMP(a.time) >".$time;
        }
        if($_GPC['time']=='month'){
            $time=date("Y-m");
            $where.="  and a.time LIKE '%{$time}%' ";
        }

        if($_GPC['start_time']){
            $start=strtotime($_GPC['start_time']);
            $end=strtotime($_GPC['end_time']);
            $where.=" and UNIX_TIMESTAMP(a.time) >='{$start}' and UNIX_TIMESTAMP(a.time) <='{$end}'";
        }
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        $sql="select  a.*,b.name as user_name,b.img as user_img from " . tablename("pintuan_order") ." a left join ".tablename('pintuan_user')." b on a.user_id=b.id ".$where." order by a.id DESC";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $list = pdo_fetchall($select_sql);
        echo  json_encode($list);
    }
//关闭订单
    public function doPageDnClose(){
        global $_GPC, $_W;
        $id=$_GPC['order_id'];
        $res= pdo_update('pintuan_order',array('dn_state'=>3), array('id'=>$id));
        if($res){
            pdo_update('pintuan_table',array('status'=>0), array('id'=>$res['table_id']));
            echo '1';
        }else{
            echo '2';
        }
    }
//确认订单
    public function doPageDnReceivables(){
        global $_GPC, $_W;
        $id=$_GPC['order_id'];
        $result = pdo_update('pintuan_order',array('dn_state'=>2,'pay_time'=>date("Y-m-d H:i:s")), array('id'=>$id));
        if($result){
            echo '1';
        }else{
            echo '2';
        }
    }
//重新开台
    public function doPageDnOpen(){
        global $_GPC, $_W;
        $table_id=$_GPC['table_id'];
        $data2['status']=$_GPC['status'];
        $result = pdo_update('pintuan_table',$data2, array('id'=>$table_id));
        if($result){
            echo '1';
        }else{
            echo '2';
        }
    }

//修改商家信息
    public function doPageUpdStoreInfo(){
        global $_GPC, $_W;
        $store=pdo_get('pintuan_store',array('id'=>$_GPC['id']));
        if($_GPC['logo']!=$store['logo']){
            $data['logo']=$_W['attachurl'].$_GPC['logo'];
        }

        $data['name']=$_GPC['name'];
        $data['tel']=$_GPC['tel'];
        $data['address']=$_GPC['address'];
        $data['coordinates']=$_GPC['coordinates'];
        $data['capita']=$_GPC['capita'];
        $data['start_at']=$_GPC['start_at'];
        $data['announcement']=$_GPC['announcement'];
        $data['environment']=$_GPC['environment'];
        $data2['xyh_open']=$_GPC['xyh_open'];
        $data2['xyh_money']=$_GPC['xyh_money'];
        $res=pdo_update('pintuan_store',$data,array('id'=>$_GPC['id']));
        $res2=pdo_update('pintuan_storeset',$data2,array('store_id'=>$_GPC['id']));
        if($res || $res2){
            echo '1';
        }else{
            echo '2';
        }
    }


//接单
    public function doPageJdOrder(){
        global $_GPC, $_W;
        $order_id=$_GPC['order_id'];
        $data2['state']=3;
        $data2['jd_time']=date('Y-m-d H:i:s');
        $sql=" select ps_mode,is_jd,print_mode from".tablename('pintuan_storeset')." where store_id=(select store_id from".tablename('pintuan_order')."where id={$_GPC['order_id']})";
        $store=pdo_fetch($sql);

        $orderInfo=pdo_get('pintuan_order',array('id'=>$order_id),'order_type');
        $sys=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']),'ps_name');
        $ps_name=empty($sys['ps_name'])?'超级跑腿':$sys['ps_name'];
        if($orderInfo['order_type']==1){
            if($store['ps_mode']=='商家配送'){
                $res=pdo_update('pintuan_order',$data2,array('id'=>$order_id));
            }
            if($store['ps_mode']=='达达配送'){
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=TestDada&m=pintuan&order_id=".$_GPC['order_id']);//达达
                //$result=$result['fee'];
                $res=pdo_update('pintuan_order',$data2,array('id'=>$order_id));
            }
            if($store['ps_mode']=='快服务配送'){
                $res=file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=kfw&m=pintuan&order_id=".$_GPC['order_id']);//快服务
                $data2['ship_id']=$res;
                $res=pdo_update('pintuan_order',$data2,array('id'=>$order_id));
            }
            if($store['ps_mode']==$ps_name){
                $result=file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=cjpt&m=pintuan&order_id=".$_GPC['order_id']);//跑腿
                if(json_decode($result)->code=='200'){
                    $res=pdo_update('pintuan_order',$data2,array('id'=>$order_id));
                }
            }
        }else{
            $res=pdo_update('pintuan_order',$data2,array('id'=>$order_id));
        }

        ///////////////模板消息///////////////////
        function getaccess_token($_W){
            $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
            $appid=$res['appid'];
            $secret=$res['appsecret'];
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            $data = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($data,true);
            return $data['access_token'];
        }
        //设置与发送模板信息
        function set_msg($_W){
            $access_token = getaccess_token($_W);
            $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
            $res2=pdo_get('pintuan_order',array('id'=>$_GET['order_id']));
            if($res2['order_type']==1){
                $yjsd="预计".$storeset['ps_time']."送达";
                $ddxx="外卖订单";
            }elseif($res2['order_type']==2){
                $yjsd="请按时去店内取货";
                $ddxx="自提订单";
            }
            $user=pdo_get('pintuan_user',array('id'=>$res2['user_id']));
            $store=pdo_get('pintuan_store',array('id'=>$res2['store_id']));
            $storeset=pdo_get('pintuan_storeset',array('store_id'=>$res2['store_id']));
            $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$res["jd_tid"].'",
           "page": "pintuan/pages/Liar/loginindex",
           "form_id":"'.$res2['form_id2'].'",
           "data": {
             "keyword1": {
               "value": "'.$res2['order_num'].'",
               "color": "#173177"
             },
             "keyword2": {
               "value":"已接单",
               "color": "#173177"
             },
             "keyword3": {
               "value": "'.$yjsd.'",
               "color": "#173177"
             },
             "keyword4": {
               "value":  "'.$store['name'].'   '.$ddxx.'",
               "color": "#173177"
             },
             "keyword5": {
               "value": "'.date("Y-m-d H:i:s").'",
               "color": "#173177"
             }
           }
         }';
            // $formwork=$data;
            $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$formwork);
            $data = curl_exec($ch);
            curl_close($ch);
            // return $data;
        }
        echo set_msg($_W);
        ///////////////模板消息///////////////////
        if($res){
            //判断商家打印方式
            if($store['print_mode']==2){
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=QtPrint&m=pintuan&order_id=".$order_id);//打印机
                file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=HcPrint&m=pintuan&order_id=".$order_id);//打印机
            }
            echo '1';
        }else{
            echo '2';
        }

    }


//拒绝订单
    public function doPageJjOrder(){
        global $_GPC, $_W;
        $data2['state']=7;
        $type=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        /////////////////////////////////////
        function wxrefund($order_id){
            global $_W, $_GPC;
            include_once IA_ROOT . '/addons/pintuan/cert/WxPay.Api.php';
            load()->model('account');
            load()->func('communication');
            $refund_order =pdo_get('pintuan_order',array('id'=>$order_id));
            $WxPayApi = new WxPayApi();
            $input = new WxPayRefund();
            $path_cert = IA_ROOT . "/addons/pintuan/cert/".'apiclient_cert_' .$_W['uniacid'] . '.pem';
            $path_key = IA_ROOT . "/addons/pintuan/cert/".'apiclient_key_' . $_W['uniacid'] . '.pem';
            $account_info = $_W['account'];
            $res=pdo_get('pintuan_pay',array('uniacid'=>$_W['uniacid']));
            $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));

            $appid=$system['appid'];
            $key=$res['wxkey'];
            $mchid=$res['mchid'];
            $out_trade_no=$refund_order['code'];
            $fee = $refund_order['money'] * 100;
            $input->SetAppid($appid);
            $input->SetMch_id($mchid);
            $input->SetOp_user_id($mchid);
            $input->SetRefund_fee($fee);
            $input->SetTotal_fee($fee);
            // $input->SetTransaction_id($refundid);
            $input->SetOut_refund_no($refund_order['order_num']);
            $input->SetOut_trade_no($out_trade_no);
            $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);
            return $result;
        }

        ////////////////////////////////////
        if( ($type['pay_type']==1 || $type['pay_type']==2) and  $type['money']>0){
            if($type['pay_type']==1){
                $result=wxrefund($_GPC['order_id']);
            }
            if($type['pay_type']==2){//余额退款
                $rst=pdo_get('pintuan_qbmx',array('user_id'=>$type['user_id'],'order_id'=>$type['id']));
                if(!$rst){
                    $tk['money'] = $type['money'];
                    $tk['order_id'] = $type['id'];
                    $tk['user_id'] = $type['user_id'];
                    $tk['type'] = 1;
                    $tk['note'] = '订单退款';
                    $tk['time'] = date('Y-m-d H:i:s');
                    $tkres = pdo_insert('pintuan_qbmx', $tk);
                    pdo_update('pintuan_user', array('wallet +=' => $type['money']), array('id' => $type['user_id']));
                }
            }
            if ($result['result_code'] == 'SUCCESS' || $tkres) {//退款成功
                //更改订单操作
                pdo_update('pintuan_order',array('state'=>7),array('id'=>$_GPC['order_id']));
                if($type['coupon_id']){
                    pdo_update('pintuan_usercoupons',array('state'=>2),array('id'=>$type['coupon_id']));
                }
                if($type['coupon_id2']){
                    pdo_update('pintuan_usercoupons',array('state'=>2),array('id'=>$type['coupon_id2']));
                }
                pdo_update('pintuan_earnings',array('state'=>3),array('order_id'=>$_GPC['order_id']));
                pdo_delete('pintuan_formid',array('time <='=>time()-60*60*24*7));
                ///////////////模板消息拒绝///////////////////
                function getaccess_token($_W){
                    $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
                    $appid=$res['appid'];
                    $secret=$res['appsecret'];
                    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,$url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                    $data = curl_exec($ch);
                    curl_close($ch);
                    $data = json_decode($data,true);
                    return $data['access_token'];
                }
                //设置与发送模板信息
                function set_msg($_W){
                    $access_token = getaccess_token($_W);
                    $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
                    $res2=pdo_get('pintuan_order',array('id'=>$_GET['order_id']));
                    $user=pdo_get('pintuan_user',array('id'=>$res2['user_id']));
                    $store=pdo_get('pintuan_store',array('id'=>$res2['store_id']));
                    $form=pdo_get('pintuan_formid',array('user_id'=>$res2['user_id'],'time >='=>time()-60*60*24*7));
                    $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$res["jj_tid"].'",
           "page": "pintuan/pages/Liar/loginindex",
           "form_id":"'.$form['form_id'].'",
           "data": {
             "keyword1": {
               "value": "'.$res2['order_num'].'",
               "color": "#173177"
             },
             "keyword2": {
               "value":"'.date("Y-m-d H:i:s").'",
               "color": "#173177"
             },
             "keyword3": {

               "value": "非常抱歉,商家暂时无法接单哦",
               "color": "#173177"
             },
             "keyword4": {
               "value":  "'.$store['name'].'",
               "color": "#173177"
             },
             "keyword5": {
               "value": "'.$store['tel'].'",
               "color": "#173177"
             },
             "keyword6": {
               "value": "'.$res2['money'].'",
               "color": "#173177"
             },
             "keyword7": {
               "value": "退款将尽快送达您的账户，请耐心等待...",
               "color": "#173177"
             }
           }
         }';
                    // $formwork=$data;
                    $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token."";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,$url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                    curl_setopt($ch, CURLOPT_POST,1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS,$formwork);
                    $data = curl_exec($ch);
                    curl_close($ch);
                    // return $data;
                    pdo_delete('pintuan_formid',array('id'=>$form['id']));
                }
                echo set_msg($_W);
                ///////////////模板消息///////////////////
                ///
                ///////////////模板消息退款///////////////////
                function getaccess_token2($_W){
                    $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
                    $appid=$res['appid'];
                    $secret=$res['appsecret'];
                    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,$url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                    $data = curl_exec($ch);
                    curl_close($ch);
                    $data = json_decode($data,true);
                    return $data['access_token'];
                }
                //设置与发送模板信息
                function set_msg2($_W){
                    $access_token = getaccess_token2($_W);
                    $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
                    $res2=pdo_get('pintuan_order',array('id'=>$_GET['order_id']));
                    if($res2['pay_type']==1){
                        $note='微信钱包';
                    }elseif($res2['pay_type']==2){
                        $note='余额钱包';
                    }
                    $user=pdo_get('pintuan_user',array('id'=>$res2['user_id']));
                    $store=pdo_get('pintuan_store',array('id'=>$res2['store_id']));
                    $form=pdo_get('pintuan_formid',array('user_id'=>$res2['user_id'],'time >='=>time()-60*60*24*7));
                    $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$res["tk_tid"].'",
           "page": "pintuan/pages/Liar/loginindex",
           "form_id":"'.$form['form_id'].'",
           "data": {
             "keyword1": {
               "value": "'.$res2['order_num'].'",
               "color": "#173177"
             },
             "keyword2": {
               "value":"'.$store['name'].'",
               "color": "#173177"
             },
             "keyword3": {

               "value": "'.$res2['money'].'",
               "color": "#173177"
             },
             "keyword4": {
               "value":  "'.$note.'",
               "color": "#173177"
             },
             "keyword5": {
               "value": "'.date("Y-m-d H:i:s").'",
               "color": "#173177"
             }
           }
         }';
                    // $formwork=$data;
                    $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token."";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,$url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                    curl_setopt($ch, CURLOPT_POST,1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS,$formwork);
                    $data = curl_exec($ch);
                    curl_close($ch);
                    // return $data;
                    pdo_delete('pintuan_formid',array('id'=>$form['id']));
                }
                echo set_msg2($_W);
                ///////////////模板消息///////////////////

                echo '1';
            }else{
                echo '2';
            }
        }else{
            $rst=pdo_update('pintuan_order',array('state'=>7),array('id'=>$_GPC['order_id']));
            if($rst){

                if($type['coupon_id']){
                    pdo_update('pintuan_usercoupons',array('state'=>2),array('id'=>$type['coupon_id']));
                }
                if($type['coupon_id2']){
                    pdo_update('pintuan_usercoupons',array('state'=>2),array('id'=>$type['coupon_id2']));
                }


                ///////////////模板消息拒绝///////////////////
                function getaccess_token($_W){
                    $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
                    $appid=$res['appid'];
                    $secret=$res['appsecret'];
                    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,$url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                    $data = curl_exec($ch);
                    curl_close($ch);
                    $data = json_decode($data,true);
                    return $data['access_token'];
                }
                //设置与发送模板信息
                function set_msg($_W){
                    $access_token = getaccess_token($_W);
                    $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
                    $res2=pdo_get('pintuan_order',array('id'=>$_GET['order_id']));
                    $user=pdo_get('pintuan_user',array('id'=>$res2['user_id']));
                    $store=pdo_get('pintuan_store',array('id'=>$res2['store_id']));
                    $form=pdo_get('pintuan_formid',array('user_id'=>$res2['user_id'],'time >='=>time()-60*60*24*7));
                    $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$res["jj_tid"].'",
           "page": "pintuan/pages/Liar/loginindex",
           "form_id":"'.$form['form_id'].'",
           "data": {
             "keyword1": {
               "value": "'.$res2['order_num'].'",
               "color": "#173177"
             },
             "keyword2": {
               "value":"'.date("Y-m-d H:i:s").'",
               "color": "#173177"
             },
             "keyword3": {

               "value": "非常抱歉,商家暂时无法接单哦",
               "color": "#173177"
             },
             "keyword4": {
               "value":  "'.$store['name'].'",
               "color": "#173177"
             },
             "keyword5": {
               "value": "'.$store['tel'].'",
               "color": "#173177"
             },
             "keyword6": {
               "value": "'.$res2['money'].'",
               "color": "#173177"
             },
             "keyword7": {
               "value": "退款将尽快送达您的账户，请耐心等待...",
               "color": "#173177"
             }
           }
         }';
                    // $formwork=$data;
                    $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token."";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,$url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                    curl_setopt($ch, CURLOPT_POST,1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS,$formwork);
                    $data = curl_exec($ch);
                    curl_close($ch);
                    pdo_delete('pintuan_formid',array('id'=>$form['id']));
                    //return $data;
                }
                echo set_msg($_W);
                ///////////////模板消息///////////////////

                echo '1';
            }else{
                echo '2';
            }
        }
    }

    public function doPageJjTk(){
        global $_GPC, $_W;
        $rst=pdo_update('pintuan_order',array('state'=>10),array('id'=>$_GPC['order_id']));
        if($rst){
            echo '1';
        }else{
            echo '2';
        }
    }
    public function doPageTkTg(){
        global $_GPC, $_W;
        $type=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        ////////////////////////////////////////
        function wxrefund($order_id){
            global $_W, $_GPC;
            include_once IA_ROOT . '/addons/pintuan/cert/WxPay.Api.php';
            load()->model('account');
            load()->func('communication');
            $refund_order =pdo_get('pintuan_order',array('id'=>$order_id));
            $WxPayApi = new WxPayApi();
            $input = new WxPayRefund();
            $path_cert = IA_ROOT . "/addons/pintuan/cert/".'apiclient_cert_' .$_W['uniacid'] . '.pem';
            $path_key = IA_ROOT . "/addons/pintuan/cert/".'apiclient_key_' . $_W['uniacid'] . '.pem';
            $account_info = $_W['account'];
            $res=pdo_get('pintuan_pay',array('uniacid'=>$_W['uniacid']));
            $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));

            $appid=$system['appid'];
            $key=$res['wxkey'];
            $mchid=$res['mchid'];
            $out_trade_no=$refund_order['code'];
            $fee = $refund_order['money'] * 100;
            $input->SetAppid($appid);
            $input->SetMch_id($mchid);
            $input->SetOp_user_id($mchid);
            $input->SetRefund_fee($fee);
            $input->SetTotal_fee($fee);
            // $input->SetTransaction_id($refundid);
            $input->SetOut_refund_no($refund_order['order_num']);
            $input->SetOut_trade_no($out_trade_no);
            $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);
            return $result;
        }



        function qxkfw($order_id){
            global $_W, $_GPC;
            include IA_ROOT.'/addons/pintuan/peisong/peisong.php';
            $order=pdo_get('pintuan_order',array('id'=>$order_id));
            $set=pdo_get('pintuan_kfwset',array('store_id'=>$order['store_id']));
            $storeInfo=pdo_get('pintuan_store',array('id'=>$order['store_id']));
            $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
            //下订单
            $data = array(
                'app_id'=>  $system['kfw_appid'],
                'access_token'=> $set['access_token'],
                'order_id'=> $order['order_num'],
                'reason'=> '客户取消订单',
                'ship_id'=> $order['ship_id'],
            );
            $obj= new KfwOpenapi();
            $sign=$obj->getSign($data,$system['kfw_appsecret']);
            $data['sign']=$sign;
            $url="http://openapi.kfw.net/openapi/v1/order/cancel";
            $result=$obj->requestWithPost($url,$data);
            // return json_decode($result)->ship_id;
            //var_dump(json_decode($result));die;


        }
        ////////////////////////////////////////////////////////

        if($type['pay_type']==1){
            $result=wxrefund($_GPC['order_id']);
        }
        if($type['pay_type']==2){//余额退款
            $rst=pdo_get('pintuan_qbmx',array('user_id'=>$type['user_id'],'order_id'=>$type['id']));
            if(!$rst){
                $tk['money'] = $type['money'];
                $tk['order_id'] = $type['id'];
                $tk['user_id'] = $type['user_id'];
                $tk['type'] = 1;
                $tk['note'] = '订单退款';
                $tk['time'] = date('Y-m-d H:i:s');
                $tkres = pdo_insert('pintuan_qbmx', $tk);
                pdo_update('pintuan_user', array('wallet +=' => $type['money']), array('id' => $type['user_id']));
            }
        }

        if ($result['result_code'] == 'SUCCESS' || $tkres) {//退款成功
            //更改订单操作
            pdo_update('pintuan_order',array('state'=>9),array('id'=>$_GPC['order_id']));
            $result=qxkfw($_GPC['order_id']);

            pdo_delete('pintuan_formid',array('time <='=>time()-60*60*24*7));
            ///////////////模板消息退款///////////////////
            function getaccess_token($_W){
                $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
                $appid=$res['appid'];
                $secret=$res['appsecret'];
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                $data = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($data,true);
                return $data['access_token'];
            }
            //设置与发送模板信息
            function set_msg($_W){
                $access_token = getaccess_token($_W);
                $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
                $res2=pdo_get('pintuan_order',array('id'=>$_GET['order_id']));
                if($res2['pay_type']==1){
                    $note='微信钱包';
                }elseif($res2['pay_type']==2){
                    $note='余额钱包';
                }
                $user=pdo_get('pintuan_user',array('id'=>$res2['user_id']));
                $store=pdo_get('pintuan_store',array('id'=>$res2['store_id']));
                $form=pdo_get('pintuan_formid',array('user_id'=>$res2['user_id'],'time >='=>time()-60*60*24*7));
                $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$res["tk_tid"].'",
           "page": "pintuan/pages/Liar/loginindex",
           "form_id":"'.$form['form_id'].'",
           "data": {
             "keyword1": {
               "value": "'.$res2['order_num'].'",
               "color": "#173177"
             },
             "keyword2": {
               "value":"'.$store['name'].'",
               "color": "#173177"
             },
             "keyword3": {

               "value": "'.$res2['money'].'",
               "color": "#173177"
             },
             "keyword4": {
               "value":  "'.$note.'",
               "color": "#173177"
             },
             "keyword5": {
               "value": "'.date("Y-m-d H:i:s").'",
               "color": "#173177"
             }
           }
         }';
                // $formwork=$data;
                $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                curl_setopt($ch, CURLOPT_POST,1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$formwork);
                $data = curl_exec($ch);
                curl_close($ch);
                // return $data;
                pdo_delete('pintuan_formid',array('id'=>$form['id']));
            }
            echo set_msg($_W);
            ///////////////模板消息///////////////////
            echo '1';
        }else{
            echo '2';
        }

    }



    public function doPageUpStore(){
        global $_W, $_GPC;
        $storeid=$_GPC['store_id'];
        if($_GPC['is_rest']){
            $data['is_rest']=$_GPC['is_rest'];
        }
        if($_GPC['time']){
            $data['time']=$_GPC['time'];
        }
        if($_GPC['time2']){
            $data['time2']=$_GPC['time2'];
        }
        if($_GPC['time3']){
            $data['time3']=$_GPC['time3'];
        }
        if($_GPC['time4']){
            $data['time4']=$_GPC['time4'];
        }
        if($_GPC['print_type']){
            $data2['print_type']=$_GPC['print_type'];
        }
        if($_GPC['print_mode']){
            $data2['print_mode']=$_GPC['print_mode'];
        }
        if($_GPC['is_jd']){
            $data2['is_jd']=$_GPC['is_jd'];
        }

        if($data){
            $res=pdo_update('pintuan_store', $data, array('id' => $storeid));
        }
        if($data2){
            $res2=pdo_update('pintuan_storeset', $data2, array('store_id' => $storeid));
        }

        if($res || $res2){
            echo '1';
        }else{
            echo '2';
        }
    }


//自提码
    public function doPageZtCode(){
        global $_W, $_GPC;
        function  getCoade($storeid,$order_id){
            function getaccess_token(){
                global $_W, $_GPC;
                $res=pdo_get('pintuan_system',array('uniacid' => $_W['uniacid']));
                $appid=$res['appid'];
                $secret=$res['appsecret'];
                // print_r($res);die;
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                $data = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($data,true);
                return $data['access_token'];
            }
            function set_msg($storeid,$order_id){
                $access_token = getaccess_token();
                $data2=array(
                    "scene"=>$storeid.",".$order_id,
                    "page"=>"pintuan/pages/sjzx/hx",
                    "width"=>400
                );
                $data2 = json_encode($data2);
                $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                curl_setopt($ch, CURLOPT_POST,1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$data2);
                $data = curl_exec($ch);
                curl_close($ch);
                return $data;
            }
            $img=set_msg($storeid,$order_id);
            $img=base64_encode($img);
            return $img;
        }
        echo getCoade($_GPC['store_id'],$_GPC['order_id']);
    }

//店铺统计
    public function doPageStoreStatistics(){
        global $_W, $_GPC;
        $time = date("Y-m-d");
        $time = "'%$time%'";
        $wm = "select sum(money) as total from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and state in (2,3,4,5,10) and type=1";
        $wm = pdo_fetch($wm);//今天的外卖销售额
        $wmnum = pdo_fetch("select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and state in (2,3,4,5,10) and type=1");//今天的外卖单数
        $wxwmnum = "select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and state in (2,3,4,5,10) and type=1 and pay_type=1";
        $wxwmnum = pdo_fetch($wxwmnum);//今天的微信外卖单数
        $ztwmnum = "select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and state in (2,3,4,5,10) and type=1 and order_type=2";
        $ztwmnum = pdo_fetch($ztwmnum);//今天的到店自提外卖单数
        $hdwmnum = "select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and state in (2,3,4,5,10) and type=1 and pay_type=4";
        $hdwmnum = pdo_fetch($hdwmnum);//今天的货到付款外卖单数
        $yuewmnum = "select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and state in (2,3,4,5,10) and type=1 and pay_type=2";
        $yuewmnum = pdo_fetch($yuewmnum);//今天的余额外卖单数
        $wm=empty($wm['total'])?'0.00':$wm['total'];


        $dn="select sum(money) as total from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and dn_state=2 and type=2";
        $dn = pdo_fetch($dn);//今天的店内销售额
        $dn=empty($dn['total'])?'0.00':$dn['total'];
        $dnnum = pdo_fetch("select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and dn_state=2 and type=2");//今天的店内单数
        $wxdnnum = "select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and dn_state=2 and type=2 and pay_type=1";
        $wxdnnum = pdo_fetch($wxdnnum);//今天的微信店内单数
        $yuednnum = "select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and dn_state=2 and type=2 and pay_type=2";
        $yuednnum = pdo_fetch($yuednnum);//今天的余额店内单数
        $chdnnum = "select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and dn_state=2 and type=2 and pay_type=5";
        $chdnnum = pdo_fetch($chdnnum);//今天的餐后店内单数



        $dm="select sum(money) as total from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and dm_state=2 and type=4";
        $dm = pdo_fetch($dm);//今天的当面付销售额
        $dm=empty($dm['total'])?'0.00':$dm['total'];
        $dmnum = pdo_fetch("select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and dm_state=2 and type=4");//今天的当面付单数
        $wxdmnum = "select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and dm_state=2 and type=4 and pay_type=1";
        $wxdmnum = pdo_fetch($wxdmnum);//今天的微信当面付单数
        $yuedmnum = "select count(id) as count from " . tablename("pintuan_order") . " WHERE time LIKE " . $time . " and store_id=" . $_GPC['store_id'] . " and dm_state=2 and type=4 and pay_type=2";
        $yuedmnum = pdo_fetch($yuedmnum);//今天的余额当面付单数
















        $data['dm']=$dm;
        $data['dmnum']=$dmnum['count'];
        $data['wxdmnum']=$wxdmnum['count'];
        $data['yuedmnum']=$yuedmnum['count'];



        $data['dn']=$dn;
        $data['dnnum']=$dnnum['count'];
        $data['wxdnnum']=$wxdnnum['count'];
        $data['yuednnum']=$yuednnum['count'];
        $data['chdnnum']=$chdnnum['count'];



        $data['wm']=$wm;
        $data['wmnum']=$wmnum['count'];
        $data['wxwmnum']=$wxwmnum['count'];
        $data['ztwmnum']=$ztwmnum['count'];
        $data['hdwmnum']=$hdwmnum['count'];
        $data['yuewmnum']=$yuewmnum['count'];
        echo json_encode($data);
    }


//////////////////////以下分销接口

//是否开启分销商
    public function doPageCheckRetail(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_fxset',array('uniacid'=>$_W['uniacid']));
        echo json_encode($res);
    }

    //查看我的申请
    public function doPageMyDistribution() {
        global $_W, $_GPC;
        $res = pdo_get('pintuan_retail', array('user_id' => $_GPC['user_id']));
        echo json_encode($res);
    }

//分销商申请
    public function doPageSaveRetail(){
        global $_W, $_GPC;
        $fx_set=pdo_get('pintuan_fxset',array('uniacid'=>$_W['uniacid']),'is_check');
        $data['user_id'] = $_GPC['user_id'];
        $data['user_name'] = $_GPC['user_name'];
        $data['user_tel'] = $_GPC['user_tel'];
        $data['time'] = time();
        if($fx_set['is_check']==1){
            $data['state'] = 1;
        }else{
            $data['state'] = 2;
            $data['sh_time'] = time();
        }
        $data['uniacid'] = $_W['uniacid'];
        $res = pdo_insert('pintuan_retail', $data);
        if ($res) {
            echo '1';
        } else {
            echo '2';
        }
    }

//分销数据
    public function doPageGetFxData(){
        global $_W, $_GPC;
        $sql2="select sum( case when state=1 then money else 0 end) as djyj, sum( case when state=2 then money else 0 end) as yxjy from  ".tablename('pintuan_earnings')." where user_id={$_GPC['user_id']}";
        $yj=pdo_fetch($sql2);
        $xjrs=pdo_get('pintuan_fxuser', array('user_id'=>$_GPC['user_id']), array('count(id) as count'));
        $data['djsyj']=0;
        $data['ljyj']=0;
        $data['rs']=0;
        if($yj['djyj']){
            $data['djsyj']=$yj['djyj'];
        }
        if($yj['yxjy']){
            $data['ljyj']=$yj['yxjy'];
        }
        if($xjrs['count']){
            $data['rs']=$xjrs['count'];
        }

        echo json_encode($data);

    }

//



    //查看我的上线
    public function doPageMySx() {
        global $_W, $_GPC;
        $sql = "select a.* ,b.name,b.img from " . tablename("pintuan_fxuser") . " a" . " left join " . tablename("pintuan_user") . " b on b.id=a.user_id   WHERE a.fx_user=:fx_user ";
        $res = pdo_fetch($sql, array(':fx_user' => $_GPC['user_id']));
        if($res['user_id']==0){
            $sys=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']),'link_logo');
            $res['name']='总店';
            $res['img']=$sys['link_logo'];
        }
        echo json_encode($res);
    }
    //查看我的佣金收益
    public function doPageEarnings() {
        global $_W, $_GPC;
        $sql = "select a.* ,b.name,b.img from " . tablename("pintuan_earnings") . " a" . " left join " . tablename("pintuan_user") . " b on b.id=a.son_id   WHERE a.user_id=:user_id  order by id DESC";
        $res = pdo_fetchall($sql, array(':user_id' => $_GPC['user_id']));
        echo json_encode($res);
    }
    //我的二维码
    public function doPageMyCode() {
        global $_W, $_GPC;
        function getCoade($user_id) {
            function getaccess_token() {
                global $_W, $_GPC;
                $res = pdo_get('pintuan_system', array('uniacid' => $_W['uniacid']));
                $appid = $res['appid'];
                $secret = $res['appsecret'];
                // print_r($res);die;
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $secret . "";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $data = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($data, true);
                return $data['access_token'];
            }
            function set_msg($user_id) {
                $access_token = getaccess_token();
                $data2 = array("scene" => $user_id,
                    "page"=>"pintuan/pages/Liar/loginindex",
                    "width" => 400);
                $data2 = json_encode($data2);
                $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token . "";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data2);
                $data = curl_exec($ch);
                curl_close($ch);
                return $data;
            }
            $img = set_msg($user_id);
            $img = base64_encode($img);
            return $img;
        }
        $base64_image_content="data:image/jpeg;base64,".getCoade($_GPC['user_id']);
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
            $type = $result[2];
            $new_file = IA_ROOT ."/addons/pintuan/img/";
            if(!file_exists($new_file))
            {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0777);
            }
            $wname="{$_GPC['user_id']}".".{$type}";
            //$wname="1511.jpeg";
            $new_file = $new_file.$wname;
            file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)));
        }
        echo  $_W['siteroot']."/addons/pintuan/img/".$wname;

    }
    //佣金提现
    public function doPageSaveYjtx() {
        global $_W, $_GPC;
        $data['user_id'] = $_GPC['user_id'];
        $data['user_name'] = $_GPC['user_name']; //姓名
        $data['account'] = $_GPC['account']; //账号
        $data['tx_cost'] = $_GPC['tx_cost']; //提现金额
        $data['sj_cost'] = $_GPC['sj_cost']; //实际到账金额
        $data['state'] = 1;
        $data['time'] = time();
        $data['uniacid'] = $_W['uniacid'];
        $res = pdo_insert('pintuan_commission_withdrawal', $data);
        if ($res) {
            //pdo_update('pintuan_user', array('commission -=' => $_GPC['tx_cost']), array('id' => $_GPC['user_id']));
            echo '1';
        } else {
            echo '2';
        }
    }
    //提现明细
    public function doPageYjtxList() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_commission_withdrawal', array('user_id' => $_GPC['user_id']), array(), '', 'id DESC');
        echo json_encode($res);
    }
    //绑定分销商
    public function doPageBinding() {
        global $_W, $_GPC;
        $set = pdo_get('pintuan_fxset', array('uniacid' => $_W['uniacid']));
        $res = pdo_get('pintuan_fxuser', array('fx_user' => $_GPC['fx_user']));
        $res2 = pdo_get('pintuan_fxuser', array('user_id' => $_GPC['fx_user'], 'fx_user' => $_GPC['user_id']));
        if ($set['is_open'] == 1) {
            if ($_GPC['user_id'] == $_GPC['fx_user']) {
                echo '自己不能绑定自己';
            } elseif ($res || $res2) {
                echo '不能重复绑定';
            } else {
                $res3 = pdo_insert('pintuan_fxuser', array('user_id' => $_GPC['user_id'], 'fx_user' => $_GPC['fx_user'], 'time' => date('Y-m-d H:i:s',time())));
                if ($res3) {
                    echo '1';
                } else {
                    echo '2';
                }
            }
        }
    }
    //查看我的团队
    public function doPageMyTeam() {
        global $_W, $_GPC;
        $sql = "select a.* ,b.name,b.img from " . tablename("pintuan_fxuser") . " a" . " left join " . tablename("pintuan_user") . " b on b.id=a.fx_user   WHERE a.user_id=:user_id order by id DESC";
        $res = pdo_fetchall($sql, array(':user_id' => $_GPC['user_id']));
        $res2 = array();
        for ($i = 0;$i < count($res);$i++) {
            $sql2 = "select a.* ,b.name,b.img from " . tablename("pintuan_fxuser") . " a" . " left join " . tablename("pintuan_user") . " b on b.id=a.fx_user   WHERE a.user_id=:user_id order by id DESC";
            $res3 = pdo_fetchall($sql2, array(':user_id' => $res[$i]['fx_user']));
            $res2[] = $res3;
        }
        $res4 = array();
        for ($k = 0;$k < count($res2);$k++) {
            for ($j = 0;$j < count($res2[$k]);$j++) {
                $res4[] = $res2[$k][$j];
            }
        }
        // foreach ($res as $key => $value) {
        // 	$sql11=" select count(id) as count from ".tablename('pintuan_earnings')." where son_id={$value['fx_user']} group by order_id";
        // 	$rst1=pdo_fetch($sql11);
        // 	$rst2=pdo_get('pintuan_earnings', array('son_id'=>$value['fx_user']), array('sum(money) as total_money'));
        // 	$res[$key]['dd']=empty($rst1['count'])?0:$rst1['count'];
        // 	$res[$key]['money']=empty($rst2['money'])?0:$rst2['money'];
        // }
        // foreach ($res4 as $key => $value2) {
        // 	$sql12=" select count(id) as count from ".tablename('pintuan_earnings')." where son_id={$value2['fx_user']} group by order_id";
        // 	$rst3=pdo_fetch($sql12);
        // 	$rst4=pdo_get('pintuan_earnings', array('son_id'=>$value2['fx_user']), array('sum(money) as total_money'));
        // 	$res4[$key]['dd']=empty($rst3['count'])?0:$rst3['count'];
        // 	$res4[$key]['money']=empty($rst4['money'])?0:$rst4['money'];
        // }
        $data['one'] = $res;
        $data['two'] = $res4;
        // print_r($data);die;
        echo json_encode($data);
    }
    //查看佣金
    public function doPageMyCommission() {
        global $_W, $_GPC;
        $sq = "select sum(tx_cost) as tx_cost from " . tablename("pintuan_commission_withdrawal") . " WHERE state=1 and user_id=" . $_GPC['user_id'];
        $sq = pdo_fetch($sq);
        $sq = empty($sq['tx_cost'])?0:$sq['tx_cost'];
        $cg = "select sum(tx_cost) as tx_cost from " . tablename("pintuan_commission_withdrawal") . " WHERE  state=2 and user_id=" . $_GPC['user_id'];
        $cg = pdo_fetch($cg);
        $cg = empty($cg['tx_cost'])?0:$cg['tx_cost'];
        $lei = "select sum(money) as tx_cost from " . tablename("pintuan_earnings") . " WHERE  state=2 and user_id=" . $_GPC['user_id'] ;
        $lei = pdo_fetch($lei);
        $lei = empty($lei['tx_cost'])?0:$lei['tx_cost'];
        $data['ktxyj'] = $lei-$sq-$cg;
        $data['ytxyj'] = $cg;
        $data['ddkyj'] = $sq;
        $data['ljyj'] = $lei;
        echo json_encode($data);
    }

//佣金明细
    public function doPageCommissionList(){
        global $_GPC, $_W;
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        $sql="select a.id,a.order_id,a.state,a.money,a.note,a.time,b.order_num,b.type  from " . tablename("pintuan_earnings") ."a left join".tablename('pintuan_order')."b on a.order_id=b.id where a.user_id={$_GPC['user_id']} and a.state={$_GPC['type']} order by a.id  desc ";
        $select_sql=$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $list=pdo_fetchall($select_sql,$data);
        echo json_encode($list);
    }

//佣金计算
    public function  doPageJsCommission(){
        global $_W, $_GPC;
        //订单类型
        $order=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']),array('type','money','user_id'));
        //获取佣金比例
        $commission=pdo_get('pintuan_fxset',array('uniacid'=>$_W['uniacid']));
        if ($commission['is_open'] == 1) { //开启分销
            if(($commission['type'] == 1&&$order['type']!=1) or ($commission['type']==2&&$order['type']==1) or($commission['type']==3)){
                if($order['type']==1 or $order['type']==3){
                    $state=1;
                }else{
                    $state=2;
                }
                $user = pdo_get('pintuan_fxuser', array('fx_user' => $order['user_id']));
                // var_dump($user);die;
                if ($user['user_id']) {
                    if($order['type']==1){
                        $money = $order['money'] * ($commission['wm_yj'] / 100); //一级佣金
                    }else{
                        $money = $order['money'] * ($commission['dn_yj'] / 100); //一级佣金
                    }
                    // pdo_update('pintuan_user', array('commission +=' => $money), array('id' => $userid));
                    $userid = $user['user_id']; //上线id
                    $data6['user_id'] = $userid; //上线id
                    $data6['son_id'] = $order['user_id']; //下线id
                    $data6['money'] = $money; //金额
                    $data6['time'] = time(); //时间
                    $data6['order_id'] = $_GPC['order_id'];
                    $data6['state'] = $state;
                    $data6['note'] = '一级佣金';
                    $data6['uniacid'] = $_W['uniacid'];
                    pdo_insert('pintuan_earnings', $data6);
                }
                if ($commission['is_ej'] == 2) { //开启二级分销
                    $user2 = pdo_get('pintuan_fxuser', array('fx_user' => $user['user_id'])); //上线的上线
                    if ($user2['user_id']) {
                        if($order['type']==1){
                            $money = $order['money'] * ($commission['wm_ej'] / 100); //一级佣金
                        }else{
                            $money = $order['money'] * ($commission['dn_ej'] / 100); //一级佣金
                        }
                        $userid2 = $user2['user_id']; //上线的上线id
                        $data7['user_id'] = $userid2; //上线id
                        $data7['son_id'] = $order['user_id']; //下线id
                        $data7['money'] = $money; //金额
                        $data7['time'] = time(); //时间
                        $data7['order_id'] = $_GPC['order_id'];
                        $data7['state'] = $state;
                        $data7['note'] = '二级佣金';
                        $data7['uniacid'] = $_W['uniacid'];
                        pdo_insert('pintuan_earnings', $data7);
                    }
                }
            }
        }

    }



//加积分
    public function doPageAddIntegral(){
        global $_W, $_GPC;
        $order=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
        $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        $score=ceil($order['money']*$system['integral2']/100);
        if($_GPC['type']==1){
            $note='外卖消费';
        }elseif($_GPC['type']==2){
            $note='店内消费';
        }elseif($_GPC['type']==3){
            $note='评价订单';
            $score=$system['integral'];
        }elseif($_GPC['type']==4){
            $note='预约消费';
        }elseif($_GPC['type']==5){
            $note='当面付消费';
        }
        if($system['jfgn']==1 and $system['is_jf']==1 and $score>0 and $order['pay_type']==1){
            $data['user_id']=$order['user_id'];
            $data['score']=$score;
            $data['type']=1;
            $data['order_id']=$order['id'];
            $data['cerated_time']=date("Y-m-d H:i:s");
            $data['uniacid']=$_W['uniacid'];
            $data['note']=$note;
            $res=pdo_insert('pintuan_integral',$data);
            if($res){
                pdo_update('pintuan_user',array('total_score +='=>$score),array('id'=>$order['user_id']));
            }
        }
    }
//商品分类
    public function doPageJftype() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_jftype', array('uniacid' => $_W['uniacid']), array(), '', 'num asc');
        echo json_encode($res);
    }
    //商品列表
    public function doPageJfGoods() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_jfgoods', array('uniacid' => $_W['uniacid'], 'is_open' => 1), array(), '', 'num asc');
        echo json_encode($res);
    }
    //商品详情
    public function doPageJfGoodsInfo() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_jfgoods', array('id' => $_GPC['id']));
        echo json_encode($res);
    }
    //分类下的商品
    public function doPageJftypeGoods() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_jfgoods', array('type_id' => $_GPC['type_id'], 'is_open' => 1), array(), '', 'num asc');
        echo json_encode($res);
    }


    //积分明细
    public function doPageJfmx() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_integral', array('user_id' => $_GPC['user_id']), array(), '', 'id DESC');
        echo json_encode($res);
    }
    //兑换商品
    public function doPageExchange() {
        global $_W, $_GPC;
        $data['user_id'] = $_GPC['user_id']; //用户id
        $data['good_id'] = $_GPC['good_id']; //商品id
        $data['user_name'] = $_GPC['user_name']; //用户名称
        $data['user_tel'] = $_GPC['user_tel']; //用户电话
        $data['address'] = $_GPC['address']; //地址
        $data['integral'] = $_GPC['integral']; //积分
        $data['good_name'] = $_GPC['good_name']; //商品名称
        $data['good_img'] = $_GPC['good_img']; //商品图片
        $data['time'] = date("Y-m-d H:i:s");
        $res = pdo_insert('pintuan_jfrecord', $data);
        if ($res) {
            pdo_update('pintuan_jfgoods', array('number -=' => 1), array('id' => $_GPC['good_id']));
            $data3['score'] = $_GPC['integral'];
            $data3['user_id'] = $_GPC['user_id'];
            $data3['note'] = '兑换商品';
            $data3['type'] = 2;
            $data3['cerated_time'] = date('Y-m-d H:i:s');
            $data3['uniacid'] = $_W['uniacid']; //小程序id
            pdo_insert('pintuan_integral', $data3);
            pdo_update('pintuan_user', array('total_score -=' => $_GPC['integral']), array('id' => $_GPC['user_id']));
            echo '1';
        } else {
            echo '2';
        }
    }
    //兑换明细
    public function doPageDhmx() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_jfrecord', array('user_id' => $_GPC['user_id']), array(), '', 'id DESC');
        echo json_encode($res);
    }



    public function doPagecjpt(){
        global $_W, $_GPC;
        $order_id=$_GPC['order_id'];
        include IA_ROOT.'/addons/pintuan/peisong/cjpt.php';
        $order=pdo_get('pintuan_order',array('id'=>$order_id));
        $store=pdo_get('pintuan_store',array('id'=>$order['store_id']),array('name','address','tel','coordinates','logo'));
        $zb= explode (",", $store['coordinates']);
        $goods=pdo_getall('pintuan_order_goods',array('order_id'=>$order_id));
        $bind=pdo_get('cjpt_bind',array('cy_uniacid'=>$_W['uniacid']));
        $goods_info='';
        foreach ($goods as $key => $value) {
            $goods_info.='#'.$value['name'];
            if($value['spec']){
                $goods_info.=$value['name']."(".$value['spec'].")";
            }
            $goods_info.="数量:".$value['number']."价格".$value['money'];
        }
        $goods_info=mb_substr($goods_info,1);

        //下订单
        $data = array(
            'order_id'=> $order['order_num'],
            'goods_info'=> $goods_info,
            'goods_price'=> $order['money'],
            'sender_name'=>$store['name'],
            'sender_address'=>$store['address'],
            'sender_tel'=>$store['tel'],
            'sender_lat'=>$zb[0],
            'sender_lng'=>$zb[1],
            'receiver_address'=>$order['address'],
            'receiver_name'=>$order['name'],
            'receiver_tel'=>$order['tel'],
            'receiver_lat'=>$order['lat'],
            'receiver_lng'=>$order['lng'],
            'note'=>$order['note'],
            'store_logo'=>$store['logo'],
            'yh_money'=>$order['discount'],
            'origin_id'=>$order_id,
            'uniacid'=>$_W['uniacid'],
        );
        $url=$_W['siteroot']."app/index.php?i=".$bind['pt_uniacid']."&c=entry&a=wxapp&do=addOrder&m=zh_cjpt";
        $result=cjpt::requestWithPost($url,$data);
        return $result;



    }



//会员期限
    public function doPageGetHyqx(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_hyqx',array('uniacid'=>$_W['uniacid']),array(),'','num ASC');
        echo json_encode($res);
    }


    //钱包明细
    public function doPageQbmx() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_qbmx', array('user_id' => $_GPC['user_id']), array(), '', 'id DESC');
        echo json_encode($res);
    }
    //充值活动
    public function doPageCzhd() {
        global $_W, $_GPC;
        $res = pdo_getall('pintuan_czhd', array('uniacid' => $_W['uniacid']), array(), '', 'full DESC');
        echo json_encode($res);
    }
    //充值
    public function doPageRecharge() {
        global $_W, $_GPC;
        $money=$_GPC['money']+$_GPC['money2'];
        $res = pdo_update('pintuan_user', array('wallet +=' => $money), array('id' => $_GPC['user_id']));
        if ($res) {
            $data['money'] = $_GPC['money'];
            $data['user_id'] = $_GPC['user_id'];
            $data['type'] = 1;
            $data['note'] = '在线充值';
            $data['time'] = date('Y-m-d H:i:s');
            $res2 = pdo_insert('pintuan_qbmx', $data);

            $data2['money'] = $_GPC['money2'];
            $data2['user_id'] = $_GPC['user_id'];
            $data2['type'] = 1;
            $data2['note'] = '充值赠送';
            $data2['time'] = date('Y-m-d H:i:s');
            $res3 = pdo_insert('pintuan_qbmx', $data2);
            if ($res2) {
                echo '1';
            } else {
                echo '2';
            }
        }
    }
    //充值下订单
    public function doPageAddCzorder() {
        global $_W, $_GPC;
        $data['user_id'] = $_GPC['user_id'];
        $data['money'] = $_GPC['money'];
        $data['money2'] = $_GPC['money2'];
        $data['form_id'] = $_GPC['form_id'];
        $data['state'] = 1;
        $data['uniacid'] = $_W['uniacid'];
        $data['time'] = date("Y-m-d H:i:s");
        $res = pdo_insert('pintuan_czorder', $data);
        $order_id = pdo_insertid();
        if ($res) {
            echo $order_id;
        } else {
            echo '下单失败!';
        }
    }

//开通会员
    public function doPageAddHyOrder(){
        global $_W, $_GPC;
        $data['user_id']=$_GPC['user_id'];
        $data['money']=$_GPC['money'];
        $data['month']=$_GPC['month'];
        $data['user_name']=$_GPC['user_name'];
        $data['user_tel']=$_GPC['user_tel'];
        $data['state']=1;
        if($_GPC['money']==0 || $_GPC['pay_type']==2){
            $data['state']=2;
            $data['day']=date('d');
            $data['time']=date('Y-m-d H:i:s');
        }
        $data['pay_type']=$_GPC['pay_type'];
        $res=pdo_insert('pintuan_hyorder',$data);
        $orderid=pdo_insertid();
        if($res){
            if($_GPC['pay_type']==2 and  $_GPC['money']>0){
                pdo_update('pintuan_user', array('wallet -=' => $_GPC['money']), array('id' => $_GPC['user_id']));
                $data4['money'] = $_GPC['money'];
                $data4['user_id'] = $_GPC['user_id'];
                $data4['type'] = 2;
                $data4['note'] = '购买会员';
                $data4['time'] = date('Y-m-d H:i:s');
                pdo_insert('pintuan_qbmx', $data4);
            }
            if($_GPC['pay_type']==2 || $_GPC['money']==0){

                pdo_update('pintuan_user',array('dq_time'=>date('Y-m-d',strtotime("+".$_GPC['month']." month")),'hy_day'=>date('d'),'user_name'=>$_GPC['user_name'],'user_tel'=>$_GPC['user_tel']),array('id'=>$_GPC['user_id']));
            }
            echo  $orderid;
        }else{
            echo '下单失败';
        }
    }


//菜品推荐
    public function doPageCptj(){
        global $_W, $_GPC;
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        $sql="select  *  from " . tablename("pintuan_cptj") ." where uniacid={$_W['uniacid']} order by num asc";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $list = pdo_fetchall($select_sql);
        echo json_encode($list);
    }
//菜品推荐
    public function doPageCptjInfo(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_cptj',array('id'=>$_GPC['id']));
        echo json_encode($res);
    }


//新订单提醒
    public function doPageNewOrder() {
        global $_W,$_GPC;
        $time=time();
        $time2=$time-10;
        $store_id=$_GPC['store_id'];
        $res=pdo_get('pintuan_order',array('state'=>2,'store_id'=>$store_id,'type'=>1));//外卖
        $sql=" select 1 from ".tablename('pintuan_order')." where type=2 and store_id={$store_id} and UNIX_TIMESTAMP(time)>={$time2}";
        $res2=pdo_fetch($sql);
        $res3=pdo_get('pintuan_order',array('yy_state'=>2,'store_id'=>$store_id,'type'=>3));//预约
        if($res){
            echo 1;
        }elseif($res2){
            echo 2;
        }elseif($res3){
            echo 3;
        }else{
            echo '暂无新订单!';
        }
    }
//添加formid
    public function doPageAddFormId(){
        global $_W, $_GPC;
        if($_GPC['form_id']!="the formId is a mock one" and $_GPC['form_id']){
            $data['user_id']=$_GPC['user_id'];
            $data['form_id']=$_GPC['form_id'];
            $data['uniacid']=$_W['uniacid'];
            $data['time']=time();
            $res=pdo_insert('pintuan_formid',$data);
        }
    }
//查看我的formid
    public function doPageMyFormId(){
        global $_W,$_GPC;
        $time=time()-60*60*24*7;
        $sql="select  count(*) as count  from " . tablename("pintuan_formid") ." where user_id={$_GPC['admin_id']} and time>={$time}";
        $res=pdo_fetch($sql);
        echo  $res['count'];
    }

//新订单模板消息
    public function doPageNewOrderMessage(){
        global $_W, $_GPC;
        pdo_delete('pintuan_formid',array('time <='=>time()-60*60*24*7));
        function getaccess_token($_W,$_GPC){
            $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
            $appid=$res['appid'];
            $secret=$res['appsecret'];
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            $data = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($data,true);
            return $data['access_token'];
        }
        //设置与发送模板信息
        function set_msg($_W,$_GPC){
            $access_token = getaccess_token($_W,$_GPC);
            $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
            $res2=pdo_get('pintuan_order',array('id'=>$_GPC['order_id']));
            //$store=pdo_get('pintuan_store',array('id'=>$res2['store_id']));
            $ordertype='天天拼团';
            $user=pdo_get('pintuan_user',array('u_id'=>$res2['user_id']));
            $form=pdo_get('pintuan_formid',array('user_id'=>$res2['user_id'],'time >='=>time()-60*60*24*7),array(),'','time desc');
            $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$res["xd_tid"].'",
           "page": "pages/nahuomain/main",
           "form_id":"'.$form['form_id'].'",
           "data": {
           	"keyword1": {
               "value": "'.$ordertype.'",
               "color": "#173177"
             },
             "keyword2": {
               "value":"'.$res2['time'].'",
               "color": "#173177"
             },
             "keyword3": {
               "value": "'.$res2['money'].'",
               "color": "#173177"
             },
             "keyword4": {
               "value":  "'.$res2['name'].'",
               "color": "#173177"
             },
             "keyword5": {
               "value": "'.$res2['tel'].'",
               "color": "#173177"
             },
             "keyword6": {
               "value": "'.$res2['address'].'",
               "color": "#173177"
             },
             "keyword7": {
               "value": "'.$res2['order_num'].'",
               "color": "#173177"
             }
           },
           "emphasis_keyword": ""
         }';
            // $formwork=$data;
            $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$formwork);
            $data = curl_exec($ch);
            curl_close($ch);
            // return $data;
            //pdo_delete('pintuan_formid',array('id'=>$form['id']));
        }
        echo set_msg($_W,$_GPC);
    }


//新订单模板消息
    public function doPageNewDmOrderMessage(){
        global $_W, $_GPC;
        pdo_delete('pintuan_formid',array('time <='=>time()-60*60*24*7));
        function getaccess_token($_W){
            $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
            $appid=$res['appid'];
            $secret=$res['appsecret'];
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            $data = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($data,true);
            return $data['access_token'];
        }
        //设置与发送模板信息
        function set_msg($_W){
            $access_token = getaccess_token($_W);
            $res=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
            $res2=pdo_get('pintuan_order',array('id'=>$_GET['order_id']));
            $store=pdo_get('pintuan_store',array('id'=>$res2['store_id']));
            $yh=pdo_get('pintuan_user',array('id'=>$res2['user_id']));
            if($res2['pay_type']==1){
                $pay_type='微信支付';
            }elseif($res2['pay_type']==2){
                $pay_type='余额支付';
            }
            $user=pdo_get('pintuan_user',array('id'=>$store['admin_id']));
            $form=pdo_get('pintuan_formid',array('user_id'=>$store['admin_id'],'time >='=>time()-60*60*24*7),array(),'','id asc');
            $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$res["xdd_tid2"].'",
           "page": "pintuan/pages/Liar/loginindex",
           "form_id":"'.$form['form_id'].'",
           "data": {
           	"keyword1": {
               "value": "'.$res2['money'].'",
               "color": "#173177"
             },
             "keyword2": {
               "value": "'.$pay_type.'",
               "color": "#173177"
             },
             "keyword3": {
               "value":"'.$res2['time'].'",
               "color": "#173177"
             },
             "keyword4": {
               "value": "'.$yh['name'].'",
               "color": "#173177"
             },
             "keyword5": {
               "value":  "'.$res2['order_num'].'",
               "color": "#173177"
             }
           }
         }';
            // $formwork=$data;
            $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$formwork);
            $data = curl_exec($ch);
            curl_close($ch);
            // return $data;
            pdo_delete('pintuan_formid',array('id'=>$form['id']));
        }
        echo set_msg($_W);
    }



//商品分类列表
    public function doPageGoodsType(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_type',array('store_id'=>$_GPC['store_id']),array(),'','order_by asc');
        echo  json_encode($res);
    }
//分类修改
    public function doPageUpdGoodsType(){
        global $_W, $_GPC;
        if($_GPC['type_name']){
            $data['type_name']=$_GPC['type_name'];
        }
        if($_GPC['order_by']>=0){
            $data['order_by']=$_GPC['order_by'];
        }
        if($_GPC['is_open']){
            $data['is_open']=$_GPC['is_open'];
        }
        if($_GPC['id']){
            $res=pdo_update('pintuan_type',$data,array('id'=>$_GPC['id']));
        }else{
            $data['store_id']=$_GPC['store_id'];
            $data['uniacid']=$_W['uniacid'];
            $res=pdo_insert('pintuan_type',$data);
        }

        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }
//分类删除
    public function doPageDelGoodsType(){
        global $_W, $_GPC;
        $res=pdo_delete('pintuan_type',array('id'=>$_GPC['id']));
        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }
    //菜品列表
    public function doPageAppDishes() {
        global $_W, $_GPC;
        $sql=" select * from".tablename('pintuan_type')." where  uniacid={$_W['uniacid']} and store_id={$_GPC['store_id']} and id in(select type_id from".tablename('pintuan_goods')." where uniacid={$_W['uniacid']} and store_id={$_GPC['store_id']})";
        $type=pdo_fetchall($sql);
        $list = pdo_getall('pintuan_goods', array('uniacid' => $_W['uniacid'], 'store_id' => $_GPC['store_id']), array(), '', 'num ASC');
        $data2 = array();
        for ($i = 0;$i < count($type);$i++) {
            $data = array();
            for ($k = 0;$k < count($list);$k++) {
                if ($type[$i]['id'] == $list[$k]['type_id']) {
                    $data[] = $list[$k];
                }
            }
            $data2[] = array('id' => $type[$i]['id'], 'type_name' => $type[$i]['type_name'], 'good' => $data);
        }
        echo json_encode($data2);
    }
//商家分类下菜品
    public function doPageStoreDishes(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_goods',array('type_id'=>$_GPC['type_id']));
        echo json_encode($res);
    }
//商品详情
    public function doPageStoreDishesInfo(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_goods',array('id'=>$_GPC['id']));
        echo json_encode($res);
    }
//添加/编辑商品
    public function doPageAddStoreDishes(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_goods',array('id'=>$_GPC['id']));
        if(isset($_GPC['name'])){
            $data['name']=$_GPC['name'];
        }
        if(isset($_GPC['type_id'])){
            $data['type_id']=$_GPC['type_id'];
        }
        if($_GPC['logo']!=$res['logo'] and isset($_GPC['logo'])){
            $data['logo']=$_W['attachurl'].$_GPC['logo'];
        }

        if(isset($_GPC['money'])){
            $data['money']=$_GPC['money'];
        }
        if(isset($_GPC['money2'])){
            $data['money2']=$_GPC['money2'];
        }
        if(isset($_GPC['dn_money'])){
            $data['dn_money']=$_GPC['dn_money'];
        }
        if(isset($_GPC['is_show'])){
            $data['is_show']=$_GPC['is_show'];
        }
        if(isset($_GPC['inventory'])){
            $data['inventory']=$_GPC['inventory'];
        }
        if(isset($_GPC['content'])){
            $data['content']=$_GPC['content'];
        }
        if(isset($_GPC['sales'])){
            $data['sales']=$_GPC['sales'];
        }
        if(isset($_GPC['num'])){
            $data['num']=$_GPC['num'];
        }
        if(isset($_GPC['is_hot'])){
            $data['is_hot']=$_GPC['is_hot'];
        }
        if(isset($_GPC['is_tj'])){
            $data['is_tj']=$_GPC['is_tj'];
        }
        if(isset($_GPC['is_new'])){
            $data['is_new']=$_GPC['is_new'];
        }
        if(isset($_GPC['is_zp'])){
            $data['is_zp']=$_GPC['is_zp'];
        }
        if(isset($_GPC['type'])){
            $data['type']=$_GPC['type'];
        }
        if(isset($_GPC['box_money'])){
            $data['box_money']=$_GPC['box_money'];
        }
        if(isset($_GPC['start_num'])){
            $data['start_num']=$_GPC['start_num'];
        }
        if(isset($_GPC['restrict_num'])){
            $data['restrict_num']=$_GPC['restrict_num'];
        }
        if($_GPC['id']){
            $res=pdo_update('pintuan_goods',$data,array('id'=>$_GPC['id']));
        }else{
            $data['is_gg']=1;
            $data['store_id']=$_GPC['store_id'];
            $data['uniacid']=$_W['uniacid'];
            $res=pdo_insert('pintuan_goods',$data);
        }
        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }
    public function doPageDelStoreGood(){
        global $_W, $_GPC;
        $res=pdo_delete('pintuan_goods',array('id'=>$_GPC['id']));
        echo json_encode($res);
    }


//抢购分类
    public function doPageQgType(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_qgtype',array('uniacid'=>$_W['uniacid'],'state'=>1));
        echo json_encode($res);
    }
//抢购商品
    public function doPageQgGoods(){
        global $_W, $_GPC;
        $time=time();
        if($_GPC['type_id']){
            $where=" and a.type_id=".$_GPC['type_id'];
        }
        if($_GPC['store_id']){
            $where=" and a.store_id=".$_GPC['store_id'];
        }

        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        if($_GPC['type']==1){
            $sql="select a.*,b.name as store_name,b.address,b.tel from " . tablename("pintuan_qggoods") . " a"  . " left join " . tablename("pintuan_store") . " b on b.id=a.store_id  where a.uniacid={$_W['uniacid']} and UNIX_TIMESTAMP(a.start_time)<={$time} and UNIX_TIMESTAMP(a.end_time)>{$time} and a.state=1 and a.state2=1 ".$where." order by a.num asc";
        }else{
            $sql="select a.*,b.name as store_name,b.address,b.tel from " . tablename("pintuan_qggoods") . " a"  . " left join " . tablename("pintuan_store") . " b on b.id=a.store_id  where a.uniacid={$_W['uniacid']} and UNIX_TIMESTAMP(a.start_time)<={$time} and UNIX_TIMESTAMP(a.end_time)>{$time} and a.state=1 ".$where." order by a.num asc";
        }

        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $res=pdo_fetchall($select_sql);
        echo json_encode($res);
    }

//商品详情
    public function doPageQgGoodInfo(){
        global $_W, $_GPC;
        pdo_update('pintuan_qggoods',array('hot +='=>1),array('id'=>$_GPC['id']));
        $sql="select a.*,b.name as store_name,b.address,b.tel from " . tablename("pintuan_qggoods") . " a"  . " left join " . tablename("pintuan_store") . " b on b.id=a.store_id  where a.id={$_GPC['id']} order by a.num asc";
        $res=pdo_fetch($sql);
        $res['start_time']=strtotime($res['start_time']);
        $res['end_time']=strtotime($res['end_time']);
        echo json_encode($res);
    }
//查看购买人数
    public function doPageQgPeople(){
        global $_W, $_GPC;
        $sql="select a.pay_time,b.name as user_name,b.img as user_img from " . tablename("pintuan_qgorder") . " a"  . " left join " . tablename("pintuan_user") . " b on b.id=a.user_id  where a.good_id={$_GPC['good_id']} and a.state in(2,3) order by a.id DESC";
        $res=pdo_fetchall($sql);
        echo json_encode($res);
    }
//抢购下单
    public function doPageQgOrder(){
        global $_W, $_GPC;
        $good=pdo_get('pintuan_qggoods',array('id'=>$_GPC['good_id']));
        if($good['surplus']>0){//还有剩余
            $data['order_num']=date('YmdHis',time()).rand(1111,9999);//订单号
            $data['user_id']=$_GPC['user_id'];
            $data['user_name']=$_GPC['user_name'];
            $data['user_tel']=$_GPC['user_tel'];
            $data['store_id']=$_GPC['store_id'];
            $data['money']=$_GPC['money'];
            $data['good_id']=$_GPC['good_id'];
            $data['good_name']=$_GPC['good_name'];
            $data['good_logo']=$_GPC['good_logo'];
            $data['pay_type']=$_GPC['pay_type'];
            $data['uniacid']=$_W['uniacid'];
            $data['state']=1;
            $data['note']=$_GPC['note'];
            $res=pdo_insert('pintuan_qgorder',$data);
            $id=pdo_insertid();
            if($res){
                if($_GPC['pay_type']==2){//余额支付
                    $time=time();
                    $dq_time=$time+$good['consumption_time']*60*60*24;
                    pdo_update('pintuan_qgorder',array('state'=>2,'dq_time'=>$dq_time,'pay_time'=>date('Y-m-d H:i:s',$time)),array('id'=>$id));
                    pdo_update('pintuan_user', array('wallet -=' => $_GPC['money']), array('id' => $_GPC['user_id']));
                    $data4['money'] = $_GPC['money'];
                    $data4['user_id'] = $_GPC['user_id'];
                    $data4['type'] = 2;
                    $data4['note'] = '抢购订单';
                    $data4['time'] = date('Y-m-d H:i:s');
                    pdo_insert('pintuan_qbmx', $data4);
                }
                pdo_update('pintuan_qggoods',array('surplus -='=>1),array('id'=>$_GPC['good_id']));
                echo $id;
            }else{
                echo '下单失败';
            }
        }else{//没有剩余
            echo '下手慢了';
        }
    }

//抢购支付
    public function doPageQgPay(){
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/wxpay.php';
        $res=pdo_get('pintuan_pay',array('uniacid'=>$_W['uniacid']));
        $res2=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        if($res2['url_name']){
            $res2['url_name']=$res2['url_name'];
        }else{
            $res2['url_name']='餐饮小程序';
        }
        $appid=$res2['appid'];
        $openid=$_GPC['openid'];//oQKgL0ZKHwzAY-KhiyEEAsakW5Zg
        $mch_id=$res['mchid'];
        $key=$res['wxkey'];
        $out_trade_no = $mch_id. time();
        $root=$_W['siteroot'];
        pdo_update('pintuan_qgorder',array('code'=>$out_trade_no),array('id'=>$_GPC['order_id']));
        $total_fee =$_GPC['money'];
        if(empty($total_fee)) //押金
        {
            $body = $res2['url_name'];
            $total_fee = floatval(99*100);
        }else{
            $body = $res2['url_name'];
            $total_fee = floatval($total_fee*100);
        }
        $weixinpay = new WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$root);
        $return=$weixinpay->pay();
        echo json_encode($return);
    }

//查看我的订单
    public function doPageMyQgOrder(){
        global $_W, $_GPC;
        if($_GPC['state']){
            $where=" and a.state=".$_GPC['state'];
        }
        if($_GPC['type']==1){
            $where=" and a.dq_time<".time();
        }
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        $sql="select a.*,b.name as store_name,b.address,b.tel,b.logo as store_logo from " . tablename("pintuan_qgorder") . " a"  . " left join " . tablename("pintuan_store") . " b on b.id=a.store_id  where a.uniacid={$_W['uniacid']} and a.user_id={$_GPC['user_id']} and a.state!=1 and a.del=2 ".$where." order by a.id DESC";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $res=pdo_fetchall($select_sql);
        echo json_encode($res);

    }
//查看是否购买
    public function doPageIsPay(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_qgorder',array('user_id'=>$_GPC['user_id'],'good_id'=>$_GPC['good_id']));
        $good=pdo_get('pintuan_qggoods',array('id'=>$_GPC['good_id']));
        //$ordergood=pdo_getall('pintuan_order_goods',array('user_id'=>$_GPC['user_id'],'good_id'=>$_GPC['good_id'],'is_qg'=>1));
        $sql="select a.id from " . tablename("pintuan_order_goods") . " a"  . " left join " . tablename("pintuan_order") . " b on b.id=a.order_id  where a.dishes_id={$_GPC['good_id']} and b.user_id={$_GPC['user_id']}";
        $ordergood=pdo_fetchall($sql);
        //echo count($ordergood);die;
        if($good['qg_num']==0){
            echo '2';
        }else{
            if($good['type']==1){//店内
                if($good['qg_num']==count($res)){
                    echo '1';
                }else{
                    echo '2';
                }
            }elseif($good['type']==2){//外卖
                if($good['qg_num']==count($ordergood)){
                    echo '1';
                }else{
                    echo '2';
                }
            }

        }

    }
//订单二维码
    public function doPageQgOrderCode(){
        global $_W, $_GPC;
        function  getCoade($order_id){
            function getaccess_token(){
                global $_W, $_GPC;
                $res=pdo_get('pintuan_system',array('uniacid' => $_W['uniacid']));
                $appid=$res['appid'];
                $secret=$res['appsecret'];
                // print_r($res);die;
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                $data = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($data,true);
                return $data['access_token'];
            }
            function set_msg($order_id){
                $access_token = getaccess_token();
                $data2=array(
                    "scene"=>$order_id,
                    "page"=>"pintuan/pages/sjzx/qghx",
                    "width"=>400
                );
                $data2 = json_encode($data2);
                $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                curl_setopt($ch, CURLOPT_POST,1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$data2);
                $data = curl_exec($ch);
                curl_close($ch);
                return $data;
            }
            $img=set_msg($order_id);
            $img=base64_encode($img);
            return $img;
        }
        echo getCoade($_GPC['order_id']);
    }



//核销订单
    public function doPageQgHx(){
        global $_W, $_GPC;
        $order=pdo_get('pintuan_qgorder',array('id'=>$_GPC['order_id']));
        $store=pdo_get('pintuan_store',array('id'=>$order['store_id']));
        if($order['store_id']==$_GPC['store_id'] || $store['admin_id']==$_GPC['user_id']){
            if($order['state']==3){
                echo '已经核销过了';
            }elseif($order['dq_time']<time()){
                echo '商品已失效';
            }else{
                $res=pdo_update('pintuan_qgorder',array('state'=>3,'hx_time'=>date('Y-m-d H:i:s')),array('id'=>$_GPC['order_id']));
                if($res){
                    echo '核销成功';
                }else{
                    echo '核销失败';
                }
            }
        }else{
            echo '暂无核销权限';
        }
    }



//删除
    public function doPageDelQgOrder(){
        global $_W, $_GPC;
        $res=pdo_update('pintuan_qgorder',array('del'=>1),array('id'=>$_GPC['order_id']));
        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }


//商家抢购订单
    public function doPageStoreQgOrder(){
        global $_W, $_GPC;
        $where=" where a.uniacid={$_W['uniacid']} and a.state!=1 and a.store_id=".$_GPC['store_id'];
        if($_GPC['state']){
            $where .=" and a.state=".$_GPC['state'];
        }
        if($_GPC['keywords']){
            $where.=" and (a.user_name LIKE  concat('%', :name,'%') || a.order_num LIKE  concat('%', :name,'%') || b.name LIKE  concat('%', :name,'%'))";
            $data[':name']=$_GPC['keywords'];
        }
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        $sql="select a.*,b.name as store_name,b.address,b.tel,b.logo as store_logo from " . tablename("pintuan_qgorder") . " a"  . " left join " . tablename("pintuan_store") . " b on b.id=a.store_id ".$where." order by a.id DESC";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $res=pdo_fetchall($select_sql,$data);
        echo json_encode($res);
    }




















/////////////////////////////////////////////////////////////////////
//以下拼团
//拼团分类
    public function doPageGroupType(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_grouptype',array('uniacid'=>$_W['uniacid']),array(),'','num ASC');
        echo json_encode($res);
    }


//拼团商品
    public function doPageGroupGoods(){
        global $_W, $_GPC;
        $time=time();
        $where= " where a.uniacid={$_W['uniacid']} and a.end_time >{$time} and a.is_shelves=1";
        if($_GPC['type_id']){
            $where.=" and a.type_id=".$_GPC['type_id'];
        }
        if($_GPC['store_id']){
            $where.=" and a.store_id=".$_GPC['store_id'];
        }
        if($_GPC['display']){
            $where.=" and a.display=1";
        }
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=empty($_GPC['pagesize'])?10:$_GPC['pagesize'];
        $sql="select a.*,b.name as store_name,b.address,b.tel,b.logo as store_logo from " . tablename("pintuan_groupgoods"). " a  left join " . tablename("pintuan_store") . " b on b.id=a.store_id" .$where." order by num asc";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $res=pdo_fetchall($select_sql);
        echo json_encode($res);
        file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=UpdateGroup&m=pintuan&store_id=".$_GPC['store_id']);//模板
    }


//商品详情
    public function  doPageGoodsInfo(){
        global $_W, $_GPC;
        $gsql=" select a.*,b.logo as store_logo from".tablename('pintuan_groupgoods')." a left join ".tablename('pintuan_store')." b on a.store_id=b.id where a.id=:id";
        $goods=pdo_fetch($gsql,array(':id'=>$_GPC['goods_id']));
        //$goods=pdo_get('pintuan_groupgoods',array('id'=>$_GPC['goods_id']));
        //拼团情况
        $sql=" select a.id,a.kt_num,a.yg_num,a.user_id,b.name,b.img  from".tablename('pintuan_group')." a left join ".tablename('pintuan_user')." b on a.user_id=b.id  where a.goods_id=:goods_id and a.state=1 and a.uniacid=:uniacid";
        $group=pdo_fetchall($sql,array(':uniacid'=>$_W['uniacid'],':goods_id'=>$_GPC['goods_id']));
        $goodsInfo['goods']=$goods;
        $goodsInfo['group']=$group;
        echo json_encode($goodsInfo);


    }


//下单
    public function doPageSaveGroupOrder(){
        global $_W, $_GPC;
        $good=pdo_get('pintuan_groupgoods',array('id'=>$_GPC['goods_id']));
        if($good['inventory']>=$_GPC['goods_num']){
            if($_GPC['type']==1){
                $data['order_num']=date('YmdHis',time()).rand(1111,9999);//订单号
                $data['user_id']=$_GPC['user_id'];
                $data['goods_id']=$_GPC['goods_id'];
                $data['group_id']=0;
                $data['logo']=$_GPC['logo'];
                $data['store_id']=$_GPC['store_id'];
                $data['goods_name']=$_GPC['goods_name'];
                $data['goods_type']=$_GPC['goods_type'];
                $data['goods_name']=$_GPC['goods_name'];
                $data['price']=$_GPC['price'];
                $data['goods_num']=$_GPC['goods_num'];
                $data['money']=$_GPC['money'];
                $data['receive_name']=$_GPC['receive_name'];
                $data['receive_tel']=$_GPC['receive_tel'];
                $data['receive_address']=$_GPC['receive_address'];
                $data['note']=$_GPC['note'];
                $data['time']=time();
                $data['xf_time']=$_GPC['xf_time'];
                $data['uniacid']=$_W['uniacid'];
                $data['pay_type']=$_GPC['pay_type'];
                $data['state']=1;
                $res=pdo_insert('pintuan_grouporder',$data);
                $id=pdo_insertid();
                if($res){
                    echo $id;
                }else{
                    echo '下单失败';
                }

            }
            if($_GPC['type']==2){
                //生产团
                if($_GPC['group_id']==''){
                    $data2['store_id']=$_GPC['store_id'];
                    $data2['goods_id']=$_GPC['goods_id'];
                    $data2['goods_logo']=$_GPC['logo'];
                    $data2['goods_name']=$_GPC['goods_name'];
                    $data2['kt_num']=$_GPC['kt_num'];
                    $data2['kt_time']=time();
                    $data2['dq_time']=$_GPC['dq_time'];
                    $data2['state']=0;
                    $data2['user_id']=$_GPC['user_id'];
                    $data2['uniacid']=$_W['uniacid'];
                    $rst=pdo_insert('pintuan_group',$data2);
                    $group_id=pdo_insertid();
                }else{
                    $group=pdo_get('pintuan_group',array('id'=>$_GPC['group_id']));
                }
                if($_GPC['group_id']==''&&$rst or $_GPC['group_id']&&$group['state']==1){
                    $data['order_num']=date('YmdHis',time()).rand(1111,9999);//订单号
                    $data['user_id']=$_GPC['user_id'];
                    $data['goods_id']=$_GPC['goods_id'];
                    $data['group_id']=empty($_GPC['group_id'])?$group_id:$_GPC['group_id'];
                    $data['logo']=$_GPC['logo'];
                    $data['store_id']=$_GPC['store_id'];
                    $data['goods_name']=$_GPC['goods_name'];
                    $data['goods_type']=$_GPC['goods_type'];
                    $data['goods_name']=$_GPC['goods_name'];
                    $data['price']=$_GPC['price'];
                    $data['goods_num']=$_GPC['goods_num'];
                    $data['money']=$_GPC['money'];
                    $data['receive_name']=$_GPC['receive_name'];
                    $data['receive_tel']=$_GPC['receive_tel'];
                    $data['receive_address']=$_GPC['receive_address'];
                    $data['note']=$_GPC['note'];
                    $data['time']=time();
                    $data['xf_time']=$_GPC['xf_time'];
                    $data['uniacid']=$_W['uniacid'];
                    $data['pay_type']=$_GPC['pay_type'];
                    $data['state']=1;
                    $res=pdo_insert('pintuan_grouporder',$data);
                    $id=pdo_insertid();
                    if($res){
                        echo $id;
                    }else{
                        echo '下单失败';
                    }
                }else{//没有剩余
                    echo '商品已销售完毕或拼团已失效';
                }
            }
        }

    }


//拼团支付
    public function doPageGroupPay(){
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/wxpay.php';
        // $grouporder=pdo_get('pintuan_grouporder',array('id'=>$_GPC['order_id']));
        // if($grouporder['group_id']>0){
        // 	$group=pdo_group('pintuan_group',array('id'=>$grouporder['group_id']));
        // }
        $res=pdo_get('pintuan_pay',array('uniacid'=>$_W['uniacid']));
        $res2=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        if($res2['url_name']){
            $res2['url_name']=$res2['url_name'];
        }else{
            $res2['url_name']='餐饮小程序';
        }
        $appid=$res2['appid'];
        $openid=$_GPC['openid'];//oQKgL0ZKHwzAY-KhiyEEAsakW5Zg
        $mch_id=$res['mchid'];
        $key=$res['wxkey'];
        $out_trade_no = $mch_id. time();
        $root=$_W['siteroot'];
        pdo_update('pintuan_grouporder',array('code'=>$out_trade_no),array('id'=>$_GPC['order_id']));
        $total_fee =$_GPC['money'];
        if(empty($total_fee)) //押金
        {
            $body = $res2['url_name'];
            $total_fee = floatval(99*100);
        }else{
            $body = $res2['url_name'];
            $total_fee = floatval($total_fee*100);
        }
        $weixinpay = new WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$root);
        $return=$weixinpay->pay();
        echo json_encode($return);
    }

//余额支付
    public function  doPageGroupYePay(){
        global $_W, $_GPC;
        $grouporder=pdo_get('pintuan_grouporder',array('id'=>$_GPC['order_id']));
        $rst=pdo_update('pintuan_user', array('wallet -=' => $grouporder['money']), array('id' => $grouporder['user_id']));
        if($rst){
            $data4['money'] = $grouporder['money'];
            $data4['user_id'] = $grouporder['user_id'];
            $data4['type'] = 2;
            $data4['note'] = '团购订单';
            $data4['time'] = date('Y-m-d H:i:s');
            pdo_insert('pintuan_qbmx', $data4);
            pdo_update('pintuan_grouporder',array('state'=>2,'pay_time'=>time()),array('id'=>$grouporder['id']));
            //改变商品
            $result=pdo_update('pintuan_groupgoods',array('ysc_num +='=>1,'inventory -='=>1),array('id'=>1));
            if($grouporder['group_id']>0){
                $count=pdo_get('pintuan_grouporder', array('group_id'=>$grouporder['group_id'],'state '=>2), array('count(user_id) as count'));
                $group=pdo_get('pintuan_group',array('id'=>$grouporder['group_id']));
                if($group['kt_num']==$count['count']){
                    $state=2;
                }else{
                    $state=1;
                }
                // //改变团状态
                pdo_update('pintuan_group',array('state'=>$state,'yg_num +='=>1),array('id'=>$grouporder['group_id']));
            }
            echo 1;
        }else{
            echo 2;
        }
    }

//查看团员信息
    public function doPageGetGroupUserInfo(){
        global $_W, $_GPC;
        $sql=" select a.id,b.name,b.img from".tablename('pintuan_grouporder')." a left join ".tablename('pintuan_user')."b on a.user_id=b.id where a.group_id=:group_id and a.state=2";
        $group=pdo_fetchall($sql,array(':group_id'=>$_GPC['group_id']));
        echo json_encode($group);
    }

//分销二维码
    public function doPageGoodsCode(){
        global $_W, $_GPC;
        function  getCoade($goods_id,$group_id){
            function getaccess_token(){
                global $_W, $_GPC;
                $res=pdo_get('pintuan_system',array('uniacid' => $_W['uniacid']));
                $appid=$res['appid'];
                $secret=$res['appsecret'];
                // print_r($res);die;
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                $data = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($data,true);
                return $data['access_token'];
            }
            function set_msg($goods_id,$group_id){
                $access_token = getaccess_token();
                $data2=array(
                    "scene"=>$goods_id.",".$group_id,
                    "page"=>"pintuan/pages/collage/index",
                    "width"=>400
                );
                $data2 = json_encode($data2);
                $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                curl_setopt($ch, CURLOPT_POST,1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$data2);
                $data = curl_exec($ch);
                curl_close($ch);
                return $data;
            }
            $img=set_msg($goods_id,$group_id);
            $img=base64_encode($img);
            return $img;
        }
        $base64_image_content="data:image/jpeg;base64,".getCoade($_GPC['goods_id'],$_GPC['group_id']='');
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
            $type = $result[2];
            $new_file = IA_ROOT ."/addons/pintuan/img/";
            if(!file_exists($new_file))
            {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0777);
            }
            $wname="{$_GPC['goods_id']}".".{$type}";
            //$wname="1511.jpeg";
            $new_file = $new_file.$wname;
            file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)));
        }
        echo "/addons/pintuan/img/".$wname;

    }

//团详情
    public function  doPageGroupInfo(){
        global $_W, $_GPC;
        //$group=pdo_get('pintuan_group',array('id'=>$_GPC['group_id']));
        $sql="select a.*,b.img,b.name from".tablename('pintuan_group')." a left join".tablename('pintuan_user')." b on a.user_id=b.id where a.id=:group_id";
        $group=pdo_fetch($sql,array(':group_id'=>$_GPC['group_id']));
        echo json_encode($group);
    }

//我的团购订单
    public function doPageMyGroupOrder(){
        global $_W, $_GPC;
        $where=" where a.uniacid={$_W['uniacid']} and a.user_id={$_GPC['user_id']} and a.state!=1 ";
        if($_GPC['state']){
            $where.=" and b.state=".$_GPC['state'];
        }
        if($_GPC['type']){//单独够订单
            $where.=" and a.group_id=0";
        }
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=empty($_GPC['pagesize'])?10:$_GPC['pagesize'];
        $sql="select a.*,b.state as g_state,c.name as store_name from " .tablename("pintuan_grouporder"). " a left join ".tablename('pintuan_group') ." b on a.group_id=b.id  left join ".tablename('pintuan_store') ." c on a.store_id=c.id" .$where." order by a.id DESC";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $res=pdo_fetchall($select_sql);
        echo json_encode($res);
        pdo_update('pintuan_grouporder',array('state'=>5,'cz_time'=>time()),array('xf_time <='=>time(),'uniacid'=>$_W['uniacid'],'state'=>2));

    }

//订单详情
    public function doPageGroupOrderInfo(){
        global $_W, $_GPC;
        $sql="select a.*,b.state as g_state from " . tablename("pintuan_grouporder")." a left join ".tablename('pintuan_group') ." b on a.group_id=b.id where a.id=:order_id  ";
        $res=pdo_fetchall($sql,array(':order_id'=>$_GPC['order_id']));
        echo json_encode($res);
    }



//订单二维码
    public function doPageOrderCode(){
        global $_W, $_GPC;
        function  getCoade($order_id){
            function getaccess_token(){
                global $_W, $_GPC;
                $res=pdo_get('pintuan_system',array('uniacid' => $_W['uniacid']));
                $appid=$res['appid'];
                $secret=$res['appsecret'];
                // print_r($res);die;
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                $data = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($data,true);
                return $data['access_token'];
            }
            function set_msg($order_id){
                $access_token = getaccess_token();
                $data2=array(
                    "scene"=>$order_id,
                    "page"=>"pintuan/pages/collage/yz_code",
                    "width"=>400
                );
                $data2 = json_encode($data2);
                $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                curl_setopt($ch, CURLOPT_POST,1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$data2);
                $data = curl_exec($ch);
                curl_close($ch);
                return $data;
            }
            $img=set_msg($order_id);
            $img=base64_encode($img);
            return $img;
        }
        echo getCoade($_GPC['order_id']);
    }

//抢购商品二维码
    public function doPageQgCode(){
        global $_W, $_GPC;
        function  getCoade($order_id){
            function getaccess_token(){
                global $_W, $_GPC;
                $res=pdo_get('pintuan_system',array('uniacid' => $_W['uniacid']));
                $appid=$res['appid'];
                $secret=$res['appsecret'];
                // print_r($res);die;
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                $data = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($data,true);
                return $data['access_token'];
            }
            function set_msg($order_id){
                $access_token = getaccess_token();
                $data2=array(
                    "scene"=>$order_id,
                    "page"=>"zh_tcwq/pages/xsqg/xsqgxq",
                    "width"=>400
                );
                $data2 = json_encode($data2);
                $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token."";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
                curl_setopt($ch, CURLOPT_POST,1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$data2);
                $data = curl_exec($ch);
                curl_close($ch);
                return $data;
            }
            $img=set_msg($order_id);
            $img=base64_encode($img);
            return $img;
        }
        echo getCoade($_GPC['id']);
    }

//核销订单
    public function doPageGroupVerification(){
        global $_W, $_GPC;
        $order=pdo_get('pintuan_grouporder',array('id'=>$_GPC['order_id']));
        $store=pdo_getall('pintuan_grouphx',array('store_id'=>$order['store_id']),'hx_id');
        $uids = array_map('array_shift', $store);
        if($order['store_id']==$_GPC['user_id'] || in_array($_GPC['user_id'],$uids)){
            if($order['state']==3 or $order['state']==5){
                echo '已经核销过了或订单已失效';
            }else{
                $res=pdo_update('pintuan_grouporder',array('state'=>3,'cz_time'=>time()),array('id'=>$_GPC['order_id']));
                if($res){
                    echo '核销成功';
                }else{
                    echo '核销失败';
                }
            }
        }else{
            echo '暂无核销权限';
        }
    }

    //拼团失败退款
    public function doPageUpdateGroup(){
        global $_W, $_GPC;
        $ids=pdo_getall('pintuan_group',array('dq_time <='=>time(),'state'=>1,'uniacid'=>$_W['uniacid'],'store_id'=>$_GPC['store_id']),'id');
        if($ids){
            $uids = array_map('array_shift', $ids);
            $orders=pdo_getall('pintuan_grouporder',array('group_id'=>$uids,'state'=>2,'pay_type'=>1),'id');
            //var_dump($orders);die;
            foreach ($orders as $key => $value) {
                include_once IA_ROOT . '/addons/pintuan/cert/WxPay.Api.php';
                load()->model('account');
                load()->func('communication');
                $refund_order =pdo_get('pintuan_grouporder',array('id'=>$value));
                $WxPayApi = new WxPayApi();
                $input = new WxPayRefund();
                $path_cert = IA_ROOT . "/addons/pintuan/cert/".'apiclient_cert_' .$_W['uniacid'] . '.pem';
                $path_key = IA_ROOT . "/addons/pintuan/cert/".'apiclient_key_' . $_W['uniacid'] . '.pem';
                $account_info = $_W['account'];
                $res=pdo_get('pintuan_pay',array('uniacid'=>$_W['uniacid']));
                $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
                $appid=$system['appid'];
                $key=$res['wxkey'];
                $mchid=$res['mchid'];
                $out_trade_no=$refund_order['code'];
                $fee = $refund_order['money'] * 100;
                $input->SetAppid($appid);
                $input->SetMch_id($mchid);
                $input->SetOp_user_id($mchid);
                $input->SetRefund_fee($fee);
                $input->SetTotal_fee($fee);
                // $input->SetTransaction_id($refundid);
                $input->SetOut_refund_no($refund_order['order_num']);
                $input->SetOut_trade_no($out_trade_no);
                $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);

                ////////////////////////////////////
                if ($result['result_code'] == 'SUCCESS' || $tkres) {//退款成功
                    //更改订单操作
                    pdo_update('pintuan_grouporder',array('state'=>4),array('id'=>$value));
                }

            }

            $yorders=pdo_getall('pintuan_grouporder',array('group_id'=>$uids,'state'=>2,'pay_type'=>2),'id');
            foreach ($yorders as $key => $value2) {
                $type=pdo_get('pintuan_grouporder',array('id'=>$value2));
                pdo_update('pintuan_user', array('wallet +=' => $type['money']), array('id' => $type['user_id']));
                $tk['money'] = $type['money'];
                $tk['user_id'] = $type['user_id'];
                $tk['type'] = 1;
                $tk['note'] = '拼团失败';
                $tk['time'] = date('Y-m-d H:i:s');
                $tkres = pdo_insert('pintuan_qbmx', $tk);
                pdo_update('pintuan_grouporder',array('state'=>4),array('id'=>$value2));

            }
            $group=pdo_update('pintuan_group',array('state'=>3),array('id'=>$uids));
        }
    }


    public function GETSiteroot(){
        global $_W, $_GPC;
        echo $_W['siteroot'];
    }

////流量主
    public function doPageLlz(){
        global $_W, $_GPC;
        $where=" where uniacid=:uniacid and status=1 and type in (".$_GPC['type'].")";
        $data[':uniacid']=$_W['uniacid'];
        $sql="select * from ".tablename('pintuan_llz').$where;
        $res=pdo_fetchall($sql,$data);
        echo json_encode($res);
    }

//判断今日预定
    public function doPageGetYdSet(){
        global $_W, $_GPC;
        $set=pdo_get('pintuan_storeset',array('store_id'=>$_GPC['store_id']),'is_ydtime');
        echo json_encode($set);
    }

//商户预定时间段
    public function doPageGetStoreTime(){
        global $_W, $_GPC;
        $list = pdo_getall('pintuan_reservation',array('uniacid' => $_W['uniacid'],'store_id'=>$_GPC['store_id']), array() , '' , 'num ASC');
        echo json_encode($list);
    }

//商户预定时间段
    public function doPageGetStoreService(){
        global $_W, $_GPC;
        $where=" WHERE uniacid={$_W['uniacid']} and store_id={$_GPC['store_id']} and pid=0";
        $sql=" select * from" . tablename("pintuan_service") .$where." order by num asc";
        $list = pdo_fetchall($sql);
        foreach($list as $key => $value){
            $data=pdo_getall('pintuan_service',array('pid'=>$value['id'],'uniacid'=>$_W['uniacid']),array(),'','order by num asc');
            if($data){
                $list[$key]['ej']=$data;
            }else{
                unset($list[$key]);
            }
        }
        echo json_encode($list);
    }



//提现申请
    public function doPageStoreTx(){
        global $_W, $_GPC;
        $data['name']=$_GPC['name'];
        $data['time']=date('Y-m-d H:i:s');
        $data['state']=1;
        $data['type']=$_GPC['is_brand'];
        $data['tx_cost']=$_GPC['tx_cost'];
        $data['sj_cost']=$_GPC['tx_cost'];
        $data['store_id']=$_GPC['store_id'];
        $data['uniacid']=$_W['uniacid'];
        $data['yhk_num']=$_GPC['yhk_num'];
        $data['tel']=$_GPC['tel'];
        $data['yh_info']=$_GPC['yh_info'];
        $res=pdo_insert('pintuan_withdrawal',$data);
        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }
//申请列表
    public function doPageStoreTxList(){
        global $_W, $_GPC;
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=empty($_GPC['pagesize'])?10:$_GPC['pagesize'];
        $where=" and state in (".$_GPC['state'].")";
        if($_GPC['start_time']){
            $start=strtotime($_GPC['start_time']);
            $end=strtotime($_GPC['end_time']);
            $where .=" and UNIX_TIMESTAMP(time) >='{$start}' and UNIX_TIMESTAMP(time)<='{$end}'";
        }
        $sql="select * from ".tablename('pintuan_withdrawal')." where uniacid={$_W['uniacid']} and store_id={$_GPC['store_id']} ".$where." order by id DESC";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $res=pdo_fetchall($select_sql);
        echo json_encode($res);
    }

//可提现金额
    public function doPageKtx(){
        global $_W, $_GPC;
        $storeid=$_GPC['store_id'];
        $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']),array('is_wx','is_yhk'));
        $data[':uniacid']=$_W['uniacid'];
        $data[':store_id']=$storeid;
        //获取商家手续费
        $sql="select b.poundage,b.dn_poundage,b.dm_poundage,b.yd_poundage from".tablename('pintuan_store')."a  left join ".tablename('pintuan_storetype')." b on a.md_type=b.id where a.id={$storeid}";
        $list4=pdo_fetch($sql);
        $where=" where a.uniacid=:uniacid and a.type=1 and a.store_id=:store_id and a.pay_type in (1,2) and a.state in (4,5,10)" ;
        //总数统计
        $sql2="select sum(money) as 'total_money',sum(ps_money) as ps_money from" . tablename("pintuan_order") ." as a".$where;
        $list2=pdo_fetch($sql2,$data);
        //店内订单金额统计
        $dnwmcost=pdo_get('pintuan_order', array('store_id'=>$storeid,'dn_state '=>2,'pay_type'=>array(1,2),'type'=>2), array('sum(money) as total_money'));
        //当面付订单金额统计
        $dmcost=pdo_get('pintuan_order', array('store_id'=>$storeid,'dm_state '=>2,'pay_type'=>array(1,2),'type'=>4), array('sum(money) as total_money'));
        //预约订单金额
        $yycost=pdo_get('pintuan_order', array('store_id'=>$storeid,'yy_state '=>3,'pay_type'=>array(1,2),'type'=>3), array('sum(money) as total_money'));
        //已申请金额
        $total=pdo_get('pintuan_withdrawal', array('store_id'=>$storeid,'state '=>1), array('sum(tx_cost) as tx_cost'));
        //已提现金额
        $total2=pdo_get('pintuan_withdrawal', array('store_id'=>$storeid,'state '=>2), array('sum(tx_cost) as tx_cost'));
        //运费服务费
        $sys=pdo_get('pintuan_store',array('id'=>$storeid),'ps_poundage');

        $ps_money=number_format($list2['ps_money']*$sys['ps_poundage']/100,1);
        //抢购金额
        $qg_money=pdo_get('pintuan_qgorder', array('store_id'=>$storeid,'state'=>array(2,3)), array('sum(money) as total_money'));
        //拼团金额
        $pt_money=pdo_get('pintuan_grouporder', array('store_id'=>$storeid,'state'=>array(3,5)), array('sum(money) as total_money'));
        $tuan=$qg_money['total_money']+$pt_money['total_money']-$list4['dn_poundage']*($qg_money['total_money']+$pt_money['total_money'])/100;

        //可提现金额
        $ktxcost=number_format(($list2['total_money']+$dnwmcost['total_money']+$dmcost['total_money']+$yycost['total_money'])-((($list2['total_money']-$list2['ps_money'])*$list4['poundage']+$dnwmcost['total_money']*$list4['dn_poundage']+$dmcost['total_money']*$list4['dm_poundage']+$yycost['total_money']*$list4['yd_poundage'])/100)-$total['tx_cost']-$total2['tx_cost']-$ps_money+$tuan,2);
        echo $ktxcost;
    }



//取号成功模板消息
    public function doPageNumberMessage(){
        global $_W, $_GPC;
        pdo_delete('pintuan_formid',array('time <='=>time()-60*60*24*7));
        function getaccess_token($_W){
            $res=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
            $appid=$res['appid'];
            $secret=$res['appsecret'];
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            $data = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($data,true);
            return $data['access_token'];
        }
        //设置与发送模板信息
        function set_msg($_W){
            $access_token = getaccess_token($_W);
            $rst=pdo_get('pintuan_message',array('uniacid'=>$_W['uniacid']));
            $res2=pdo_get('pintuan_number',array('id'=>$_GET['num_id']));
            $store=pdo_get('pintuan_store',array('id'=>$res2['store_id']));
            $user=pdo_get('pintuan_user',array('id'=>$res2['user_id']));
            $newsql=" select count(id) as count from  ".tablename('pintuan_number')." where uniacid={$_W['uniacid']} and store_id={$res2['store_id']}  and num='{$res2['num']}' and state=1  and id<{$_GET['num_id']}";
            $res=pdo_fetch($newsql);
            $form=pdo_get('pintuan_formid',array('user_id'=>$res2['user_id'],'time >='=>time()-60*60*24*7),array(),'','id asc');
            $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$rst["qh_tid"].'",
           "page": "pintuan/pages/seller/getnum?storeid='.$store['id'].'",
           "form_id":"'.$form['form_id'].'",
           "data": {
           	"keyword1": {
               "value": "排队中",
               "color": "#173177"
             },
             "keyword2": {
               "value": "'.$res2['code'].'",
               "color": "#173177"
             },
             "keyword3": {
               "value":"'.$res2['num'].'",
               "color": "#173177"
             },
             "keyword4": {
               "value": "'.$res['count'].'桌",
               "color": "#173177"
             },
             "keyword5": {
               "value":  "'.$res2['time'].'",
               "color": "#173177"
             },
               "keyword6": {
               "value":  "'.$store['name'].'",
               "color": "#173177"
             },
               "keyword7": {
               "value":  "取号成功,请随时关注取号状态",
               "color": "#173177"
             }
           }
         }';
            // $formwork=$data;
            $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token."";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$formwork);
            $data = curl_exec($ch);
            curl_close($ch);
            // return $data;
            pdo_delete('pintuan_formid',array('id'=>$form['id']));
        }
        echo set_msg($_W);
    }



///////////////////////////////////////取号手机端后台

//添加排队分类
    public function doPageEditNumberType(){
        global $_W, $_GPC;
        $data['typename']=$_GPC['typename'];
        $data['store_id']=$_GPC['store_id'];
        $data['sort']=$_GPC['sort'];
        $data['time']=time();
        $data['uniacid']=$_W['uniacid'];
        if($_GPC['id']==''){
            $res=pdo_insert('pintuan_numbertype',$data);
        }else{
            $res = pdo_update('pintuan_numbertype', $data, array('id' => $_GPC['id']));
        }
        if($res){
            echo '1';
        }else{
            echo '2';
        }

    }


//排队分类列表
    public function doPageNumberTypeList(){
        global $_W, $_GPC;
        $sql="select * from " . tablename("pintuan_numbertype") ." where uniacid={$_W['uniacid']} and store_id={$_GPC['store_id']} order by sort asc";
        $list = pdo_fetchall($sql);
        echo json_encode($list);
    }

//删除排队分类
    public function doPageDelNumberType(){
        global $_W, $_GPC;
        $res=pdo_delete('pintuan_numbertype',array('id'=>$_GPC['id']));
        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }

//取号排队
    public function doPageNumberList(){
        global $_W, $_GPC;
        $data[':uniacid']=$_W['uniacid'];
        $data[':store_id']=$_GPC['store_id'];
        $where=" WHERE uniacid=:uniacid and store_id=:store_id and state!=4";
        $sql=" select id,num,state,count(id) as count from" . tablename("pintuan_number") .$where." group by num ";
        $list = pdo_fetchall($sql,$data);
        foreach($list as $key => $value){
            $num=$value['num'];
            $newsql=" select id,num,code from ".tablename('pintuan_number')." where uniacid=:uniacid and store_id=:store_id  and num='{$num}' and state=1  order by id asc";
            $res=pdo_fetch($newsql,$data);
            if($res){
                $newsql2="select count(id) as count2 from ".tablename('pintuan_number')." where uniacid=:uniacid and store_id=:store_id and num='{$num}' and  id<={$res['id']}";
                $res2=pdo_fetch($newsql2,$data);
                $list[$key]['dq']=$res['code'];
                $list[$key]['pid']=$res['id'];
                $list[$key]['rs']=$res2['count2'];
            }else{
                $newsql2="select count(id) as count2 from ".tablename('pintuan_number')." where uniacid=:uniacid and store_id=:store_id and num='{$num}'";
                $res2=pdo_fetch($newsql2,$data);
                $list[$key]['dq']='暂无排队信息';
                $list[$key]['pid']='null';
                $list[$key]['rs']=$res2['count2'];;
            }
        }
        echo json_encode($list);
    }


//号领取列表
    public function doPagelqNumberList(){
        global $_W, $_GPC;
        $data[':uniacid']=$_W['uniacid'];
        $data[':store_id']=$_GPC['store_id'];
        $data[':num']=$_GPC['typename'];
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=empty($_GPC['pagesize'])?10:$_GPC['pagesize'];
        $where=" WHERE uniacid=:uniacid and store_id=:store_id and state!=4 and num=:num";
        $sql=" select * from" . tablename("pintuan_number") .$where."  order by id asc ";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $list = pdo_fetchall($select_sql,$data);
        foreach($list as $key => $value){
            if($value['state']==1){
                $newsql=" select count(id) as count from  ".tablename('pintuan_number')." where uniacid={$_W['uniacid']} and store_id={$_GPC['store_id']}  and num='{$value['num']}' and state=1  and id<{$value['id']}";
                $res=pdo_fetch($newsql);
            }
            if($res){
                $list[$key]['pdrs']=$res['count'];
            }else{
                $list[$key]['pdrs']='0';
            }
        }
        echo json_encode($list);
    }

//删除排队分号
    public function doPageDelNumberCode(){
        global $_W, $_GPC;
        $res=pdo_delete('pintuan_number',array('id'=>$_GPC['id']));
        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }

//叫号
    public function doPageQueryNumber(){
        global $_W, $_GPC;
        $number=pdo_get('pintuan_number',array('id'=>$_GPC['id']));
        $store=pdo_get('pintuan_call',array('store_id'=>$number['store_id']));
        $num=2;
        for($i=0;$i<$num;$i++){
            $content.="请".$number['code']."的顾客用餐,";
        }

        $appid=$store['appid'];
        $appkey=$store['apikey'];
        $output_path="../addons/pintuan/call/yc".$number['code'].$number['id'].".wav";
        $param = [ 'engine_type' => 'intp65',
            'auf' => 'audio/L16;rate=16000',
            'aue' => 'raw',
            'voice_name' => 'xiaoyan',
            'speed' => '0'
        ];
        $cur_time = (string)time();
        $x_param = base64_encode(json_encode($param));
        $header_data = ['X-Appid:'.$appid,
            'X-CurTime:'.$cur_time,
            'X-Param:'.$x_param,
            'X-CheckSum:'.md5($appkey.$cur_time.$x_param),
            'Content-Type:application/x-www-form-urlencoded; charset=utf-8'
        ];
        $body_data = 'text='.urlencode($content);    //Request
        $url = "http://api.xfyun.cn/v1/service/v1/tts";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body_data);
        $result = curl_exec($ch);
        $res_header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $res_header = substr($result, 0, $res_header_size);
        curl_close($ch);
        if(stripos($res_header, 'Content-Type: audio/mpeg') === FALSE){ //合成错误
            return substr($result, $res_header_size);
        }else{
            file_put_contents($output_path, substr($result, $res_header_size));
            //echo   "<audio src='{$output_path}' autoplay='autoplay' controls='controls'  hidden='true' ></audio>";die;
            //return '语音合成成功，请查看文件！';
            return  json_encode(substr($output_path,3));
        }


    }



//排队入座
    public function doPagePdrz(){
        global $_W,$_GPC;
        $rst=pdo_update('pintuan_number',array('state'=>2),array('id'=>$_GPC['id']));
        if($rst){
            echo '1';
        }else{
            echo '2';
        }

    }

//排队跳号
    public function doPagePdth(){
        global $_W,$_GPC;
        $rst=pdo_update('pintuan_number',array('state'=>3),array('id'=>$_GPC['id']));
        if($rst){
            echo '1';
        }else{
            echo '2';
        }

    }


    public function doPagePrintTest(){
        global $_W, $_GPC;
        include IA_ROOT.'/addons/pintuan/print/dyj.php';

        //$url="https://hl.zhycms.com/addons/zh_jd/payment/peisong/notify2.php";
        $url="115.28.15.113:60002";
        $dingdanID="20180816";
        $dayinjisn="17012425";
        $dingdan="123545";
        $pages="1";
        $replyURL="https://hl.zhycms.com/addons/zh_jd/payment/peisong/notify2.php";

        $data = array(
            'dingdanID'=>'dingdanID='.$dingdanID, //订单号
            'dayinjisn'=>'dayinjisn='.$dayinjisn, //打印机ID号
            'dingdan'=>'dingdan='.$dingdan, //订单内容
            'pages'=>'pages='.$pages, //联数
            'replyURL'=>'replyURL='.$replyURL); //回复确认URL

        $post_data = implode('&',$data);
        $result = Dyj::postData($url, $post_data);
        var_dump($result);die;




    }


//uu跑腿
    //获取订单价格
    public function doPageGetOrderPrice() {
        global $_W, $_GPC;
        include IA_ROOT . '/addons/pintuan/uupt/upt.php';
        //订单信息
        $orderinfo = pdo_get('pintuan_order', array('id' => $_GPC['order_id']));
        //获取商家信息
        $sellerinfo = pdo_get('pintuan_store', array('id' => $orderinfo['store_id']));
        $arr = explode(",", $sellerinfo['coordinates']);
        //获取uu配置
        //$uupt = pdo_get('wpdc_uuset', array('store_id' => $orderinfo['seller_id']));
        header("Content-type: text/html; charset=utf-8");
        $guid = str_replace('-', '', upt::guid());
        //var_dump($guid);die;
        // $appid = $uupt['appid'];
        // $appKey = $uupt['appkey'];
        // $openid = $uupt['OpenId'];
        $appid = '83326e55ffda4869aa812ca12fa40ae0';
        $appKey = '502d1978a2774d1cbde2769065b277ed';
        $openid = 'acbc538119d74eef904f383316e5e555';
        $city=explode(',',$orderinfo['area']);
        $city=$city['1'];
        $city=substr($city,0,strpos($city,'市'));
        $city_name = $city . '市';
        //var_dump($city_name);die;
        //$url = "http://openapi.uupaotui.com/v2_0/getorderprice.ashx";
        $url = "http://openapi.uupaotui.com/v2_0/getcitylist.ashx";
        /*$data = array('origin_id' => $_GPC['order_id'], 'from_address' => $sellerinfo['address'], 'to_address' => $orderinfo['address'], 'city_name' => $city_name, 'to_lng' => $orderinfo['lng'], //经度
        'to_lat' => $orderinfo['lat'], 'from_lng' => $arr[1], 'from_lat' => $arr[0], 'nonce_str' => strtolower($guid), 'timestamp' => time(), 'appid' => $appid, 'openid' => $openid,);*/
        $data = array(
            'nonce_str' => strtolower($guid),
            'timestamp' => time(),
            'appid' => $appid,
            'openid' => $openid,
        );
        ksort($data);
        $data['sign'] = upt::sign($data, $appKey);
        //var_dump($data);die;
        $res = upt::request_post($url, $data);
        var_dump($res);die;
        //$a=json_decode($res);
        //echo $a->total_money;die;
        echo json_encode($res);
    }




    //精选好店
    public function doPageSelectStoreList(){
        global $_W, $_GPC;
        $time=time();
        $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        // $lat="30.525980";
        //  $lng="114.353440";
        if($_GPC['lat']){
            $lat=$_GPC['lat'];
        }else{
            $lat='30.592760';
        }
        if($_GPC['lng']){
            $lng=$_GPC['lng'];
        }else{
            $lng='114.305250';
        }
        $where=" WHERE a.uniacid=:uniacid and a.is_open=1 and a.state=2 and a.is_select=1";
        if($_GPC['type_id']){
            $where .=" and a.md_type = :md_type";
            $data[':md_type']=$_GPC['type_id'];
        }
        if($_GPC['keywords']){
            $where.=" and a.name LIKE  concat('%', :name,'%') ";
            $data[':name']=$_GPC['keywords'];
        }
        if($_GPC['nopsf']==1){
            $where .=" and (b.money is null || b.money=0)";
        }
        if($_GPC['nostart']==1){
            $where .=" and a.start_at=0";
        }
        if($_GPC['yhhd']){
            $where .=$_GPC['yhhd'];
        }

        $data[':uniacid']=$_W['uniacid'];
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=empty($_GPC['pagesize'])?5:$_GPC['pagesize'];
        if(!$_GPC['by']){
            $_GPC['by']="number asc";
        }
        if($system['distance']!=0){
            $sql="select xx.* from (SELECT a.*, ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)/2),2)+COS($lat*PI()/180)*COS(SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)*POW(SIN(($lng*PI()/180-SUBSTRING_INDEX(coordinates, ',', -1)*PI()/180)/2),2)))*1000) AS juli ,b.money as ps_money,c.ps_mode,c.ps_time,c.xyh_open,c.xyh_money FROM ".tablename("pintuan_store") . " a left join (select min(money) as money,store_id from ".tablename("pintuan_distribution")." group by store_id) b on a.id=b.store_id " . " left join " . tablename("pintuan_storeset") . " c on c.store_id=a.id left join (select min(reduction) as money,store_id from ".tablename("pintuan_reduction")." ) d on a.id=d.store_id ".  $where ." ORDER BY ".$_GPC['by'].") xx where xx.juli<=".$system['distance'];
        }else{
            $sql="SELECT a.*, ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)/2),2)+COS($lat*PI()/180)*COS(SUBSTRING_INDEX(coordinates, ',', 1)*PI()/180)*POW(SIN(($lng*PI()/180-SUBSTRING_INDEX(coordinates, ',', -1)*PI()/180)/2),2)))*1000) AS juli ,b.money as ps_money,c.ps_mode,c.ps_time,c.xyh_open,c.xyh_money FROM ".tablename("pintuan_store") . " a left join (select min(money) as money,store_id from ".tablename("pintuan_distribution")." group by store_id) b on a.id=b.store_id " . " left join " . tablename("pintuan_storeset") . " c on c.store_id=a.id left join (select min(reduction) as money,store_id from ".tablename("pintuan_reduction")." ) d on a.id=d.store_id ".  $where ." ORDER BY ".$_GPC['by'];
        }
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $list = pdo_fetchall($select_sql,$data);
        for($i=0;$i<count($list);$i++){
            $mj=pdo_getall('pintuan_reduction',array('store_id'=>$list[$i]['id']));
            $hot=pdo_getslice('pintuan_goods', array('type' =>1,'store_id'=>$list[$i]['id']), array(1,10) , $total , array() , '' , array('sales desc'));
            $list[$i]['mj']=$mj;
            $list[$i]['hot']=$hot;
        }
        echo json_encode($list);
    }

//签到
    public function doPageSign(){
        global $_W, $_GPC;
        $sign=pdo_get('pintuan_signlist',array('user_id'=>$_GPC['user_id'],'time'=>$_GPC['time']));
        if(!$sign){
            $time2=explode(',',$_GPC['time']);
            $time2=$time2[0]."-".$time2[1]."-".$time2[2];
            $time2=strtotime($time2);
            $data['time2']=$time2;
            $data['time3']=time();
            $data['user_id']=$_GPC['user_id'];
            $data['time']=$_GPC['time'];
            $data['integral']=$_GPC['integral'];
            $data['uniacid']=$_W['uniacid'];
            $res=pdo_insert('pintuan_signlist',$data);
            if($res){
                if($_GPC['one']){
                    pdo_update('pintuan_user',array('total_score +='=>$_GPC['one'],'day +='=>1),array('id'=>$_GPC['user_id']));//签到增加积分/签到天数
                    $data2['score']=$_GPC['one'];
                    $data2['user_id']=$_GPC['user_id'];
                    $data2['note']='首次签到';
                    $data2['type']=1;
                    $data2['cerated_time']=date('Y-m-d H:i:s');
                    $data2['uniacid']=$_W['uniacid'];//小程序id
                    pdo_insert('pintuan_integral',$data2);//添加积分明细
                }else{
                    pdo_update('pintuan_user',array('total_score +='=>$_GPC['integral'],'day +='=>1),array('id'=>$_GPC['user_id']));//签到增加积分/签到天数
                    $data2['score']=$_GPC['integral'];
                    $data2['user_id']=$_GPC['user_id'];
                    $data2['note']='每日签到';
                    $data2['type']=1;
                    $data2['cerated_time']=date('Y-m-d H:i:s');
                    $data2['uniacid']=$_W['uniacid'];//小程序id
                    pdo_insert('pintuan_integral',$data2);//添加积分明细
                }
                $list=pdo_getall('pintuan_continuous',array('uniacid'=>$_W['uniacid']));//连续签到列表
                $my=pdo_getall('pintuan_signlist',array('user_id'=>$_GPC['user_id']),array(),'','time2 DESC');
                // print_r($list);die;
                $time=date('Y,n,j',time());//今天
                $jt=pdo_get('pintuan_signlist',array('user_id'=>$_GPC['user_id'],'time'=>$time));//查看今天有没有签到
                if($jt){//签到了
                    $num=0;
                    for($i=0;$i<count($my);$i++){
                        if(date('Y,n,j',time()-$i*60*60*24)==$my[$i]['time']){//从今天开始匹对
                            $num=$num+1;
                        }else{
                            break;
                        }
                    }
                }else{
                    $num=0;
                    for($i=0;$i<count($my);$i++){
                        if(date('Y,n,j',time()-($i+1)*60*60*24)==$my[$i]['time']){//从昨天开始匹对
                            $num=$num+1;
                        }else{
                            break;
                        }
                    }
                }
                for($k=0;$k<count($list);$k++){
                    if($num==$list[$k]['day']){
                        $data3['score']=$list[$k]['integral'];
                        $data3['user_id']=$_GPC['user_id'];
                        $data3['note']="连续签到".$list[$k]['day']."天";
                        $data3['type']=1;
                        $data3['cerated_time']=date('Y-m-d H:i:s');
                        $data3['uniacid']=$_W['uniacid'];//小程序id
                        $qd=pdo_get('pintuan_integral',array('uniacid'=>$_W['uniacid'],'note'=>$data3['note'],'user_id'=>$_GPC['user_id']));
                        if(!$qd){
                            pdo_insert('pintuan_integral',$data3);//添加积分明细
                            pdo_update('pintuan_user',array('total_score +='=>$list[$k]['integral']),array('id'=>$_GPC['user_id']));//连续签到增加积分
                        }
                        break;
                    }
                }
                echo '1';
            }else{
                echo '2';
            }
        }

    }

    //补签
    public function doPageSign2(){
        global $_W, $_GPC;
        $time2=explode(',',$_GPC['time']);
        $time2=$time2[0]."-".$time2[1]."-".$time2[2];
        $time2=strtotime($time2);
        $data['time2']=$time2;
        $data['time3']=time();
        $data['user_id']=$_GPC['user_id'];
        $data['time']=$_GPC['time'];
        $data['integral']=$_GPC['integral'];
        $data['uniacid']=$_W['uniacid'];
        $res=pdo_insert('pintuan_signlist',$data);
        $res2=pdo_get('pintuan_signset',array('uniacid'=>$_W['uniacid']));
        if($res){
            pdo_update('pintuan_user',array('total_score -='=>$res2['bq_integral']),array('id'=>$_GPC['user_id']));//签到增加积分/签到天数
            $data4['score']=$res2['bq_integral'];
            $data4['user_id']=$_GPC['user_id'];
            $data4['note']='补签';
            $data4['type']=2;
            $data4['cerated_time']=date('Y-m-d H:i:s');
            $data4['uniacid']=$_W['uniacid'];//小程序id
            pdo_insert('pintuan_integral',$data4);//添加积分明细
            if($_GPC['one']){
                pdo_update('pintuan_user',array('total_score +='=>$_GPC['one'],'day +='=>1),array('id'=>$_GPC['user_id']));//签到增加积分/签到天数
                $data2['score']=$_GPC['one'];
                $data2['user_id']=$_GPC['user_id'];
                $data2['note']='首次签到';
                $data2['type']=1;
                $data2['cerated_time']=date('Y-m-d H:i:s');
                $data2['uniacid']=$_W['uniacid'];//小程序id
                pdo_insert('pintuan_integral',$data2);//添加积分明细
            }else{
                pdo_update('pintuan_user',array('total_score +='=>$_GPC['integral'],'day +='=>1),array('id'=>$_GPC['user_id']));//签到增加积分/签到天数
                $data2['score']=$_GPC['integral'];
                $data2['user_id']=$_GPC['user_id'];
                $data2['note']='每日签到';
                $data2['type']=1;
                $data2['cerated_time']=date('Y-m-d H:i:s');
                $data2['uniacid']=$_W['uniacid'];//小程序id
                pdo_insert('pintuan_integral',$data2);//添加积分明细
            }


            $list=pdo_getall('pintuan_continuous',array('uniacid'=>$_W['uniacid']));//连续签到列表
            $my=pdo_getall('pintuan_signlist',array('user_id'=>$_GPC['user_id']),array(),'','time2 DESC');
            $time=date('Y,n,j',time());//今天
            $jt=pdo_get('pintuan_signlist',array('user_id'=>$_GPC['user_id'],'time'=>$time));//查看今天有没有签到
            if($jt){//签到了
                $num=0;
                for($i=0;$i<count($my);$i++){
                    if(date('Y,n,j',time()-$i*60*60*24)==$my[$i]['time']){//从今天开始匹对
                        $num=$num+1;
                    }else{
                        break;
                    }
                }
            }else{
                $num=0;
                for($i=0;$i<count($my);$i++){
                    if(date('Y,n,j',time()-($i+1)*60*60*24)==$my[$i]['time']){//从昨天开始匹对
                        $num=$num+1;
                    }else{
                        break;
                    }
                }
            }

            for($k=0;$k<count($list);$k++){
                if($num==$list[$k]['day']){
                    $data3['score']=$list[$k]['integral'];
                    $data3['user_id']=$_GPC['user_id'];
                    $data3['note']="连续签到".$list[$k]['day']."天";
                    $data3['type']=1;
                    $data3['cerated_time']=date('Y-m-d H:i:s');
                    $data3['uniacid']=$_W['uniacid'];//小程序id
                    $qd=pdo_get('pintuan_integral',array('uniacid'=>$_W['uniacid'],'note'=>$data3['note'],'user_id'=>$_GPC['user_id']));
                    if(!$qd){
                        pdo_insert('pintuan_integral',$data3);//添加积分明细
                        pdo_update('pintuan_user',array('total_score +='=>$list[$k]['integral']),array('id'=>$_GPC['user_id']));//连续签到增加积分
                    }
                    break;
                }
            }

            echo '1';
        }else{
            echo '2';
        }
    }
    //查看是否补签
    public function doPageIsbq(){
        global $_W, $_GPC;
        $time=date('Y-m-d');

        $time="'%$time%'";
        // echo $time;die;
        $sql="select *  from " . tablename("pintuan_integral") ." WHERE  cerated_time LIKE ".$time." and user_id=".$_GPC['user_id']." and note='补签'";
        //  echo $sql;die;
        $res=pdo_fetch($sql);
        if($res){
            echo '1';
        }else{
            echo '2';
        }
    }
    //查看我的签到
    public function doPageMySign(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_signlist',array('user_id'=>$_GPC['user_id']));
        echo json_encode($res);
    }
    //签到排行
    public function doPageRank(){
        global $_W, $_GPC;
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        $where="where uniacid=:uniacid and day!=''";
        $sql= "select * from".tablename('pintuan_user').$where." order by day DESC";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $res=pdo_fetchall($select_sql,array(':uniacid'=>$_W['uniacid']));
        echo json_encode($res);
    }
    //今日排行
    public function doPageJrRank(){
        global $_W, $_GPC;
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize=$_GPC['pagesize'];
        $sql="select a.*,b.name,b.img from " . tablename("pintuan_signlist") . " a"  . " left join " . tablename("pintuan_user") . " b on b.id=a.user_id  WHERE a.uniacid=:uniacid and a.time=:time order by time3 asc";
        $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
        $res=pdo_fetchall($select_sql,array(':uniacid'=>$_W['uniacid'],':time'=>date('Y,n,j')));
        echo json_encode($res);
    }
    //我的今日排行
    public function doPageMyJrRank(){
        global $_W, $_GPC;
        $sql="select a.*,b.name,b.img from " . tablename("pintuan_signlist") . " a"  . " left join " . tablename("pintuan_user") . " b on b.id=a.user_id  WHERE a.uniacid=:uniacid and a.time=:time order by time3 asc";
        $res=pdo_fetchall($sql,array(':uniacid'=>$_W['uniacid'],':time'=>date('Y,n,j')));
        for($i=0;$i<count($res);$i++){
            if($_GPC['user_id']==$res[$i]['user_id']){
                $res[$i]['num']=$i+1;
                $list=$res[$i];
            }
        }
        echo json_encode($list);
    }
    //查看今天是否签到
    public function doPageMyJrSign(){
        global $_W, $_GPC;
        $res=pdo_get('pintuan_signlist',array('user_id'=>$_GPC['user_id'],'time'=>date('Y,n,j')));
        if($res){
            echo '1';
        }else{
            echo  '2';
        }
    }
    //查看今日签到所得积分
    public function doPageMyJrJf(){
        global $_W, $_GPC;
        $time=date('Y-m-d');
        $time="'%$time%'";
        $sql="select sum(score) as total  from " . tablename("pintuan_integral") ." WHERE  cerated_time LIKE ".$time." and user_id=".$_GPC['user_id']." and (note='每日签到' || note='首次签到' || note LIKE '%连续签到%')";
        $res=pdo_fetch($sql);
        echo $res['total'];
    }
    //查看连签奖励
    public function doPageContinuousList(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_continuous',array('uniacid'=>$_W['uniacid']),array(),'','day asc');
        echo json_encode($res);
    }
    //查看特殊日期奖励
    public function doPageSpecial(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_special',array('uniacid'=>$_W['uniacid']));
        echo json_encode($res);
    }
    //查看签到规则
    public function doPageSignset(){
        global $_W, $_GPC;
        $res=pdo_getall('pintuan_signset',array('uniacid'=>$_W['uniacid']));
        echo json_encode($res);
    }
//查看连续签到天数
    public function doPageContinuous(){
        global $_W, $_GPC;
        $my=pdo_getall('pintuan_signlist',array('user_id'=>$_GPC['user_id']),array(),'','time2 desc');
        $time=date('Y,n,j',time());//今天
        $jt=pdo_get('pintuan_signlist',array('user_id'=>$_GPC['user_id'],'time'=>$time));//查看今天有没有签到
        if($jt){//签到了
            $num=0;
            for($i=0;$i<count($my);$i++){
                if(date('Y,n,j',time()-$i*60*60*24)==$my[$i]['time']){//从今天开始匹对
                    $num=$num+1;
                }else{
                    break;
                }
            }
        }else{
            $num=0;
            for($i=0;$i<count($my);$i++){
                if(date('Y,n,j',time()-($i+1)*60*60*24)==$my[$i]['time']){//从昨天开始匹对
                    $num=$num+1;
                }else{
                    break;
                }
            }
        }
        echo $num;
    }

//查看配送详情
    public  function doPageGetStorePsInfo(){
        global $_W, $_GPC;
        $order_id=$_GPC['order_id'];
        $store_id=$_GPC['store_id'];
        $sys=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']),'ps_name');
        $ps_name=empty($sys['ps_name'])?'超级跑腿':$sys['ps_name'];
        $rst=pdo_get('pintuan_storeset',array('store_id'=>$store_id),'ps_mode');
        if($rst['ps_mode']=='快服务配送'){
            $rst=file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=GetKfwInfo&m=pintuan&order_id=".$order_id);//配送详情
            return $rst;
        }
        if($rst['ps_mode']=='达达配送'){
            $rst=file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=GetDadaInfo&m=pintuan&order_id=".$order_id);//配送详情
            return $rst;
        }
        if($rst['ps_mode']==$ps_name){
            $rst=file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=GetPtInfo&m=pintuan&order_id=".$order_id);//配送详情
            return $rst;

        }

    }




    //跑腿详情
    public function doPageGetPtInfo(){
        global $_W, $_GPC;
        $order_id=$_GPC['order_id'];
        include IA_ROOT.'/addons/pintuan/peisong/cjpt.php';
        $order=pdo_get('pintuan_order',array('id'=>$order_id));
        $bind=pdo_get('cjpt_bind',array('cy_uniacid'=>$_W['uniacid']));
        $newstr = substr($news,0,strlen($news)-1);
        //下订单
        $data = array(
            'order_num'=> $order['order_num'],
            'uniacid'=>$_W['uniacid'],
        );
        $url=$_W['siteroot']."app/index.php?i=".$bind['pt_uniacid']."&c=entry&a=wxapp&do=GetOrderInfo&m=zh_cjpt";
        $result=cjpt::requestWithPost($url,$data);
        return $result;

    }


//达达详情
    public function doPageGetDadaInfo(){
        global $_W, $_GPC;
        $order_id=$_GPC['order_id'];
        include IA_ROOT.'/addons/pintuan/peisong/peisong.php';
        $order=pdo_get('pintuan_order',array('id'=>$order_id));
        $set=pdo_get('pintuan_psset',array('store_id'=>$order['store_id']));
        $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
//*********************配置项*************************
        $config = array();
        $config['app_key'] = $system['dada_key'];
        $config['app_secret'] = $system['dada_secret'];
        $config['source_id'] =$set['source_id'];
        // $config['app_key'] = 'dada69fa59eef841ee2';
        // $config['app_secret'] = '18e0b16c94f1dab5a920fadc6a6897d7';
        // $config['source_id'] ='73753';
        $config['url'] = 'http://newopen.imdada.cn/api/order/status/query';
        $data2 = array(
            'order_id'=> $order['order_num'],//订单id
            // 'order_id'=> '201807021442512909',
        );
        $result= Peisong::requestMethod($config,$data2);

        echo json_encode($result);
    }

    //快服务详情
    public  function  doPageGetKfwInfo(){
        global $_W, $_GPC;
        include IA_ROOT.'/addons/zh_jd/peisong/KfwOpenapi.php';
        $order_id=$_GPC['order_id'];
        $order=pdo_get('pintuan_order',array('id'=>$order_id));

        $set=pdo_get('pintuan_kfwset',array('store_id'=>$order['store_id']));
        $system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
        $app_secret=$system['kfw_appsecret'];
        $data = array(
            'app_id'=>  $system['kfw_appid'],
            'access_token'=> $set['access_token'],
            'ship_id'=> $order['ship_id'],
        );
        $obj= new KfwOpenapi();
        $sign=$obj->getSign($data,$app_secret);
        $data['sign']=$sign;
        $url="http://openapi.kfw.net/openapi/v1/order/status";
        // $url="http://openapi.kfw.net/openapi/v1/order/status";
        $result=$obj->requestWithPost($url,$data);
        //var_dump(json_decode($result));die;
        return $result;
    }
}