<?php
global $_GPC, $_W;
$system=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']));
$GLOBALS['frames'] = $this->getMainMenu();
$pageindex = max(1, intval($_GPC['page']));
$pagesize=8;
$type=isset($_GPC['type'])?$_GPC['type']:'now';
$type2=isset($_GPC['type2'])?$_GPC['type2']:'today';
$where=" where a.uniacid=:uniacid ";
$data[':uniacid']=$_W['uniacid'];
if(isset($_GPC['keywords'])){
    $where.=" and (a.name LIKE  concat('%', :name,'%') || a.order_num LIKE  concat('%', :name,'%'))";
    $data[':name']=$_GPC['keywords'];
    $type='all';
}
if($_GPC['time']){
    $start=$_GPC['time']['start'];
    $end=$_GPC['time']['end'];
    $where.=" and a.time >='{$start}' and a.time<='{$end}'";
    $type='all';
}else{
    if($type=='wait'){//待支付
        $where.=" and a.state=1";
    }
    if($type=='now'){//待发货
        $where.=" and a.state=2";
    }
    if($type=='cancel'){//取消
        $where.=" and a.state in (5,6,7,8,9,10)";
    }
    if($type=='complete'){//已完成
        $where.=" and a.state=4";
    }
    if($type=='delivery'){//待收货
        $where.=" and a.state=3";
    }
//    if($type=='zt'){
//        $where.=" and a.order_type=2";
//    }
//    if($type=='dd'){
//        $where.=" and a.order_type=3";
//    }

    if($type2=='today'){
        $time=date("Y-m-d",time());
        $where.="  and a.time LIKE '%{$time}%' ";
    }
    if($type2=='yesterday'){
        $time=date("Y-m-d",strtotime("-1 day"));
        $where.="  and a.time LIKE '%{$time}%' ";
    }
    if($type2=='week'){
        $time=strtotime(date("Y-m-d",strtotime("-7 day")));

        $where.=" and UNIX_TIMESTAMP(a.time) >".$time;
    }
    if($type2=='month'){
        $time=date("Y-m");
        $where.="  and a.time LIKE '%{$time}%' ";
    }
}
//$sql="SELECT a.*,b.name as md_name,c.poundage as md_poundage,d.poundage,d.ps_mode,b.ps_poundage FROM ".tablename('pintuan_order'). " a"  . " left join " . tablename("pintuan_store") . " b on a.store_id=b.id " . " left join " . tablename("pintuan_storetype") . " c on b.md_type=c.id ". " left join " . tablename("pintuan_storeset") . " d on b.id=d.store_id ".$where." ORDER BY a.id DESC";
$sql="SELECT a.* FROM ".tablename('pintuan_order'). " a" .$where." ORDER BY a.id DESC";
$total=pdo_fetchcolumn("SELECT count(*) FROM ".tablename('pintuan_order'). " a" .$where." ORDER BY a.id DESC",$data);
$select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
$list=pdo_fetchall($select_sql,$data);
$res2=pdo_getall('pintuan_order_goods');
$data3=array();
for($i=0;$i<count($list);$i++){
    $data4=array();
    for($k=0;$k<count($res2);$k++){
        if($list[$i]['id']==$res2[$k]['order_id']){
            $data4[]=array(
                'name'=>$res2[$k]['name'],
                'num'=>$res2[$k]['number'],
                'img'=>$res2[$k]['img'],
                'money'=>$res2[$k]['money'],
                'spec'=>$res2[$k]['spec'],
                'dishes_id'=>$res2[$k]['dishes_id']
            );
        }
    }
    $data3[]=array(
        'order'=> $list[$i],
        'goods'=>$data4
    );
}
////打印
//if($_GPC['op']=='dy'){
//    $result=$this->qtPrint($_GPC['order_id']);
//    //$result=$this->hcPrint($_GPC['order_id']);
//    message('打印成功！', $this->createWebUrl('order'), 'success');
//}

if($_GPC['op']=='cancel'){
    $res=pdo_update('pintuan_order',array('state'=>6),array('id'=>$_GPC['id']));
    $rst=pdo_get('pintuan_order',array('id'=>$_GPC['id']));
    $set=pdo_get('pintuan_storeset',array('store_id'=>$rst['store_id']),'ps_mode');
    $sys=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']),'ps_name');
    $ps_name=empty($sys['ps_name'])?'天天拼团':$sys['ps_name'];
    if($res){
        if($set['ps_mode']=='快服务配送'){
            $result=$this->qxkfw($_GPC['id']);
            message('取消成功！', $this->createWebUrl('order'), 'success');
        }
        if($set['ps_mode']=='达达配送'){
            $result=$this->QxDada($_GPC['id']);
            message('取消成功！', $this->createWebUrl('order'), 'success');
        }
        if($set['ps_mode']==$ps_name){
            $result=$this->qxpt($_GPC['id']);
            // var_dump($result);die;
            message('取消成功！', $this->createWebUrl('order'), 'success');
        }

    }

}

$pager = pagination($total, $pageindex, $pagesize);
if($_GPC['op']=='jd'){
    $data2['state']=3;
    $data2['postfeename']=$_GPC['postfeename'];
    $data2['postfeenum']=$_GPC['postfeenum'];
    if(empty($_GPC['postfeenum'])||empty($_GPC['postfeename'])){
        message('物流信息不可为空！','','error');
    }
    $data2['jd_time']=date('Y-m-d H:i:s');
    $sql=" select ps_mode,is_jd,print_mode from".tablename('pintuan_storeset')." where store_id=(select store_id from".tablename('pintuan_order')."where id={$_GPC['id']})";
    $store=pdo_fetch($sql);
    $orderInfo=pdo_get('pintuan_order',array('id'=>$_GPC['id']),'order_type');
    $sys=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']),'ps_name');
    $ps_name=empty($sys['ps_name'])?'天天拼团':$sys['ps_name'];
    $res=pdo_update('pintuan_order',$data2,array('id'=>$_GPC['id']));
    ///////////////模板消息///////////////////
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
        $res2=pdo_get('pintuan_order',array('id'=>$_GPC['id']));
        $getname=pdo_get('pintuan_order_goods',array('order_id'=>$_GPC['id']));
        $user=pdo_get('pintuan_user',array('u_id'=>$res2['user_id']));
        $form=pdo_get('pintuan_formid',array('user_id'=>$res2['user_id'],'time >='=>time()-60*60*24*7),array(),'','time desc');
        $formwork ='{
           "touser": "'.$user["openid"].'",
           "template_id": "'.$res["jd_tid"].'",
           "page": "pages/nahuomain/main",
           "form_id":"'.$form['form_id'].'",
           "data": {
             "keyword1": {
               "value": "'.$res2['name'].'",
               "color": "#173177"
             },
             "keyword2": {
               "value":"天天拼团",
               "color": "#173177"
             },
             "keyword3": {

               "value": "'.$res2['order_num'].'",
               "color": "#173177"
             },
             "keyword4": {
               "value":  "'.$res2['postfeename'].'",
               "color": "#173177"
             },
             "keyword5": {
               "value": "'.$res2['postfeenum'].'",
               "color": "#173177"
             },
             "keyword6": {
               "value": "'.date('Y-m-d H:i:s',time()).'",
               "color": "#173177"
             },
             "keyword7": {
               "value": "'.$getname['name'].'等等",
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
       pdo_delete('pintuan_formid',array('id'=>$form['id']));
        // return $data;
    }
    echo set_msg($_W,$_GPC);
    ///////////////模板消息///////////////////
    if ($res){
        json_encode(array('code'=>1));
    }else{
        json_encode(array('code'=>0));
    }

}

if($_GPC['op']=='jjjd'){
    $data2['state']=7;
    $type=pdo_get('pintuan_order',array('id'=>$_GPC['id']));
    if( ($type['pay_type']==1 || $type['pay_type']==2) and  $type['money']>0){


        if($type['pay_type']==1){//微信退款
            $result=$this->wxrefund($_GPC['id']);
        }
        if($type['pay_type']==2){//余额退款
            pdo_update('pintuan_user', array('wallet +=' => $type['money']), array('id' => $type['user_id']));
            $tk['money'] = $type['money'];
            $tk['user_id'] = $type['user_id'];
            $tk['type'] = 1;
            $tk['note'] = '订单拒绝';
            $tk['time'] = date('Y-m-d H:i:s');
            $tkres = pdo_insert('pintuan_qbmx', $tk);
        }
        if ($result['result_code'] == 'SUCCESS' || $tkres) {//退款成功
            //更改订单操作
            pdo_update('pintuan_order',array('state'=>7),array('id'=>$_GPC['id']));

            if($type['coupon_id']){
                pdo_update('pintuan_usercoupons',array('state'=>2),array('id'=>$type['coupon_id']));
            }
            if($type['coupon_id2']){
                pdo_update('pintuan_usercoupons',array('state'=>2),array('id'=>$type['coupon_id2']));
            }
            $this->invalidcommission($_GPC['id']);

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
                $res2=pdo_get('pintuan_order',array('id'=>$_GET['id']));
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
                $res2=pdo_get('pintuan_order',array('id'=>$_GET['id']));
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

            message('拒绝成功',$this->createWebUrl('order',array()),'success');
        }else{//退款失败
            message($result['err_code_des'],'','error');
        }




    }else{
        $rst=pdo_update('pintuan_order',array('state'=>7),array('id'=>$_GPC['id']));
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
                $res2=pdo_get('pintuan_order',array('id'=>$_GET['id']));
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

            message('拒绝成功',$this->createWebUrl('order',array()),'success');
        }else{
            message('拒绝失败！','','error');
        }

    }




}








if($_GPC['op']=='wc'){
    $data2['state']=4;
    $data2['complete_time']=date("Y-m-d H:i:s");
    $res=pdo_update('pintuan_order',$data2,array('id'=>$_GPC['id']));
    if($res){
        //有效分销佣金
        $this->updcommission($_GPC['id']);
        file_get_contents("".$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&a=wxapp&do=addintegral&m=pintuan&type=1&order_id=".$_GPC['id']);
        message('完成成功！', $this->createWebUrl('order'), 'success');
    }else{
        message('完成失败！','','error');
    }
}
if($_GPC['op']=='refund'){
    $type=pdo_get('pintuan_order',array('id'=>$_GPC['id']));
    $store=pdo_get('pintuan_storeset',array('store_id'=>$type['store_id']),'ps_mode');
    $sys=pdo_get('pintuan_system',array('uniacid'=>$_W['uniacid']),'ps_name');
    $ps_name=empty($sys['ps_name'])?'超级跑腿':$sys['ps_name'];
    if($type['pay_type']==1){//微信退款
        $result=$this->wxrefund($_GPC['id']);
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
        pdo_update('pintuan_order',array('state'=>9),array('id'=>$_GPC['id']));
        if($store['ps_mode']=='快服务配送'){
            $result=$this->qxkfw($_GPC['id']);
        }
        if($store['ps_mode']==$ps_name){
            $this->qxpt($_GPC['id']);
        }
        $this->invalidcommission($_GPC['id']);

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
            $res2=pdo_get('pintuan_order',array('id'=>$_GET['id']));
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
        message('退款成功',$this->createWebUrl('order',array()),'success');
    }else{
        message($result['err_code_des'],'','error');
    }

}
if($_GPC['op']=='reject'){
    //更改订单操作
    $rst=pdo_update('pintuan_order',array('state'=>10),array('id'=>$_GPC['id']));
    if($rst){
        $this->updcommission($_GPC['id']);
        message('操作成功',$this->createWebUrl('order',array()),'success');
    }else{
        message('操作失败！','','error');
    }
}

if(checksubmit('export_submit', true)) {
    $time=date("Y-m-d");
    $time="'%$time%'";
    $start=$_GPC['time']['start'];
    $end=$_GPC['time']['end'];
    $count = pdo_fetchcolumn("SELECT COUNT(*) FROM". tablename("pintuan_order")." WHERE uniacid={$_W['uniacid']} and time >='{$start}' and time<='{$end}'");
    $pagesize = ceil($count/5000);
    //array_unshift( $names,  '活动名称');

    $header = array(
        'ids'=>'序号',
        'order_num' => '订单号',
        'name' => '联系人',
        'tel' => '联系电话',
        'address' => '联系地址',
        'time' => '下单时间',
        'money' => '金额',
        'state' => '订单状态',
        'pay_type' => '支付方式',
        'goods' => '商品'

    );

    $keys = array_keys($header);
    $html = "\xEF\xBB\xBF";
    foreach ($header as $li) {
        $html .= $li . "\t ,";
    }
    $html .= "\n";
    for ($j = 1; $j <= $pagesize; $j++) {
        $sql = "select a.* from " . tablename("pintuan_order")."  a"  . "   WHERE a.uniacid={$_W['uniacid']}  and a.time >='{$start}' and a.time<='{$end}' limit " . ($j - 1) * 5000 . ",5000 ";
        $list = pdo_fetchall($sql);
    }
    if (!empty($list)) {
        $size = ceil(count($list) / 500);
        for ($i = 0; $i < $size; $i++) {
            $buffer = array_slice($list, $i * 500, 500);
            $user = array();
            foreach ($buffer as $k =>$row) {
                $row['ids']= $k+1;
                if($row['state']==1){
                    $row['state']='待付款';
                }elseif($row['state']==2){
                    $row['state']='等待接单';
                }elseif($row['state']==3){
                    $row['state']='等待送达';
                }elseif($row['state']==4){
                    $row['state']='完成';
                }elseif($row['state']==5){
                    $row['state']='已评价';
                }elseif($row['state']==6){
                    $row['state']='已取消';
                }elseif($row['state']==7){
                    $row['state']='已拒绝';
                }elseif($row['state']==8){
                    $row['state']='退款中';
                }elseif($row['state']==9){
                    $row['state']='退款成功';
                }elseif($row['state']==10){
                    $row['state']='退款失败';
                }
                if($row['pay_type']==0){
                    $row['pay_type']='微信支付';
                }

                $good=pdo_getall('pintuan_order_goods',array('order_id'=>$row['id']));
               $date6='';
                for($i=0;$i<count($good);$i++){
                   
                    if($good[$i]['spec']){
                        $date6 .=$good[$i]['name'].'('.$good[$i]['spec'].')*一共'.$good[$i]['number']."  件";
                    }else{
                        $date6 .=$good[$i]['name'].'*一共'.$good[$i]['number']."  件";
                    }

                }
                $row['goods']=$date6;
                foreach ($keys as $key) {
                    $data5[] = $row[$key];
                }
                $user[] = implode("\t ,", $data5) . "\t ,";
                unset($data5);
            }
            $html .= implode("\n", $user) . "\n";
        }
    }

    header("Content-type:text/csv");
    header("Content-Disposition:attachment; filename=商品订单数据表.csv");
    echo $html;
    exit();
}


if($_GPC['op']=='delete'){
    $res=pdo_delete('pintuan_order',array('id'=>$_GPC['id']));
    if($res){
        message('删除成功！', $this->createWebUrl('order'), 'success');
    }else{
        message('删除失败！','','error');
    }
}
include $this->template('web/order');