<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$where="WHERE a.uniacid=:uniacid";
$list = pdo_getall('pintuan_typeonemy',array('uniacid' => $_W['uniacid']),array(),'','ID ASC');
if($_GPC['op']=='delete'){
	$res=pdo_delete('pintuan_typeonemy',array('ID'=>$_GPC['id']));
	if($res){
	    //删除所属的二级分类
        pdo_delete('pintuan_typetwomy',array('Pid'=>$_GPC['id']));
		 message('删除成功！', $this->createWebUrl('onetype'), 'success');
		}else{
			  message('删除失败！','','error');
		}
}
include $this->template('web/onetype');