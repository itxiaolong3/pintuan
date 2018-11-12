<?php
global $_GPC, $_W;
load()->func('tpl');
$GLOBALS['frames'] = $this->getMainMenu();
$list = pdo_getall('pintuan_areatype',array('uniacid' => $_W['uniacid']),array(),'','Cid ASC');
if($_GPC['id']){
  if ($_GPC['id']==1){
        message('不可删除该区域，请直接修改','','error');
    }
    $res=pdo_delete('pintuan_areatype',array('Cid'=>$_GPC['id']));
    if($res){
        message('删除成功',$this->createWebUrl('area',array()),'success');
    }else{
        message('删除失败','','error');
    }
}
include $this->template('web/area');