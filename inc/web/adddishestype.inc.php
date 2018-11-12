<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$list = pdo_get('pintuan_bighome',array('bid'=>$_GPC['id']));
$data['hname']=$_GPC['hname'];
$data['uniacid']=$_W['uniacid'];
		if(checksubmit('submit')){
			if($_GPC['id']==''){
				$res=pdo_insert('pintuan_bighome',$data);
				if($res){
					message('添加成功',$this->createWebUrl('dishestype',array()),'success');
				}else{
					message('添加失败','','error');
				}
			}else{
				$res = pdo_update('pintuan_bighome', $data, array('bid' => $_GPC['id']));
				if($res){
					message('编辑成功',$this->createWebUrl('dishestype',array()),'success');
				}else{
					message('编辑失败','','error');
				}
			}
		}
include $this->template('web/adddishestype');