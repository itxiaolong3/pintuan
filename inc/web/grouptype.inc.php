<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$list = pdo_getall('pintuan_grouptype',array('uniacid' => $_W['uniacid']),array(),'','num ASC');
if($_GPC['id']){
    $res=pdo_delete('pintuan_grouptype',array('id'=>$_GPC['id']));
    if($res){
        message('删除成功',$this->createWebUrl('grouptype',array()),'success');
    }else{
        message('删除失败','','error');
    }
}
include $this->template('web/grouptype');