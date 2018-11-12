<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$where="WHERE a.uniacid=:uniacid";
$list = pdo_getall('pintuan_searchtag');
if($_GPC['op']=='delete'){
    $res=pdo_delete('pintuan_searchtag',array('ID'=>$_GPC['id']));
    if($res){
        message('删除成功！', $this->createWebUrl('seachtag'), 'success');
    }else{
        message('删除失败！','','error');
    }
}
include $this->template('web/seachtag');