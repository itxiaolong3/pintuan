<?php
global $_GPC, $_W;
$action = 'start';
//$GLOBALS['frames'] = $this->getMainMenu2();
$uid=$_COOKIE["uid"];
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$GLOBALS['frames'] = $this->getNaveMenu($storeid, $action,$uid);
$list=pdo_getall('pintuan_dyj',array('store_id'=>$storeid,'uniacid'=>$_W['uniacid']));
if($_GPC['id']){
	$result = pdo_delete('pintuan_dyj', array('id'=>$_GPC['id']));
		if($result){
			message('删除成功',$this->createWebUrl2('dlprint',array()),'success');
		}else{
			message('删除失败','','error');
		}
}
include $this->template('web/dlprint');