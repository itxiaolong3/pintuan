<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$info=pdo_get('pintuan_goodmy',array('gID'=>$_GPC['id']));
if(!empty($info)){
    $info['Images']=explode(',',$info['Images']);
}
//大厦
$bighome=pdo_getall('pintuan_bighome',array('uniacid'=>$_W['uniacid']));
//分类
$sql=" select * from".tablename('pintuan_typetwomy')." where  uniacid={$_W['uniacid']} and ID>0";
$type=pdo_fetchall($sql);

include $this->template('web/editallgood');