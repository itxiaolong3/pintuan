<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$info = pdo_get('pintuan_areatype',array('uniacid' => $_W['uniacid'],'Cid'=>$_GPC['id']));
	if(checksubmit('submit')){
			$data['Name']=$_GPC['Name'];
			$data['uniacid']=$_W['uniacid'];
			if($_GPC['id']==''){				
				$res=pdo_insert('pintuan_areatype',$data);
				if($res){
					message('添加成功',$this->createWebUrl('area',array()),'success');
				}else{
					message('添加失败','','error');
				}
			}else{
				$res = pdo_update('pintuan_areatype', $data, array('Cid' => $_GPC['id']));
				if($res){
					message('编辑成功',$this->createWebUrl('area',array()),'success');
				}else{
					message('编辑失败','','error');
				}
			}
		}
include $this->template('web/addarea');