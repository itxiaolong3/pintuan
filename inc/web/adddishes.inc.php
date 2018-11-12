<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"]; 
$cur_store = $this->getStoreById($storeid);
$info=pdo_get('pintuan_goodmy',array('gID'=>$_GPC['id']));
if(!empty($info)){
    $info['Images']=explode(',',$info['Images']); 
}
$type=pdo_getall('pintuan_bighome',array('uniacid'=>$_W['uniacid']));
if(!$type){
	message('请先添加大厦',$this->createWebUrl('adddishestype',array()),'error');
}
//大厦
$bighome=pdo_getall('pintuan_bighome',array('uniacid'=>$_W['uniacid']));
//分类
$sql=" select * from".tablename('pintuan_typetwomy')." where  uniacid={$_W['uniacid']} and ID>0";
$type=pdo_fetchall($sql);
//$type=pdo_getall('pintuan_typetwomy',array('uniacid'=>$_W['uniacid']));

include $this->template('web/adddishes');