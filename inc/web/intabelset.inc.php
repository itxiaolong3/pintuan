<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$info=pdo_get('pintuan_storeset',array('store_id'=>$storeid));
if(checksubmit('submit')){
	$data['is_czztpd']=$_GPC['is_czztpd'];
		$data['is_chzf']=$_GPC['is_chzf'];
		$data['is_wxzf']=$_GPC['is_wxzf'];
		$data['is_ydtime']=$_GPC['is_ydtime'];
		$data['is_yyzw']=$_GPC['is_yyzw'];
		$res = pdo_update('pintuan_storeset', $data, array('store_id' => $storeid));
		if($res){
			message('编辑成功',$this->createWebUrl('intabelset',array()),'success');
		}else{
			message('编辑失败','','error');
		}
}
include $this->template('web/intabelset');