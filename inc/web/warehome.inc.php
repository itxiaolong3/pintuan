<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$list = pdo_getall('pintuan_warehome',array('uniacid'=>$_W['uniacid']));
if($_GPC['op']=='delete'){
    $res=pdo_delete('pintuan_warehome',array('fid'=>$_GPC['id']));
    if($res){
        message('删除成功！', $this->createWebUrl('warehome'), 'success');
    }else{
        message('删除失败！','','error');
    }
}
include $this->template('web/warehome');