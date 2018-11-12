<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$list = pdo_getall('pintuan_bighome',array('uniacid' => $_W['uniacid']));
if($_GPC['op']=='del'){
	$rst=pdo_getall('pintuan_goodmy',array('StallsName'=>$_GPC['id']));
		if(!$rst){
		$result = pdo_delete('pintuan_bighome', array('bid'=>$_GPC['id']));
		if($result){
			message('删除成功',$this->createWebUrl('dishestype',array()),'success');
		}else{
		message('删除失败','','error');
		}
	}else{
		message('该大厦有商品无法删除','','error');
	}
}
include $this->template('web/dishestype');