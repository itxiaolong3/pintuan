<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$info = pdo_get('pintuan_qggoods',array('uniacid' => $_W['uniacid'],'id'=>$_GPC['id']));
	if($info['img']){
			if(strpos($info['img'],',')){
			$img= explode(',',$info['img']);
		}else{
			$img=array(
				0=>$info['img']
				);
		}
		}
	$type = pdo_getall('pintuan_qgtype',array('uniacid' => $_W['uniacid']));
		if(checksubmit('submit')){
			$data['name']=$_GPC['name'];
			if($info['logo']!=$_GPC['logo']){
				$data['logo']=$_W['attachurl'].$_GPC['logo'];
			}else{
				$data['logo']=$_GPC['logo'];
			}
			if($_GPC['img']){
			$data['img']=implode(",",$_GPC['img']);
			}else{
				$data['img']='';
			}
			$data['money']=$_GPC['money'];
			$data['price']=$_GPC['price'];
			$data['type_id']=$_GPC['type_id'];
			$data['num']=$_GPC['num'];
			$data['number']=$_GPC['number'];
			$data['start_time']=$_GPC['start_time'];
			$data['end_time']=$_GPC['end_time'];
			$data['content']=$_GPC['content'];
			$data['consumption_time']=$_GPC['consumption_time'];
			$data['details']=html_entity_decode($_GPC['details']);
			$data['uniacid']=$_W['uniacid'];
			if($_GPC['id']==''){	
			$data['surplus']=$_GPC['number'];			
				$res=pdo_insert('pintuan_qggoods',$data);
				if($res){
					message('添加成功',$this->createWebUrl('qggoodall',array()),'success');
				}else{
					message('添加失败','','error');
				}
			}else{
				$res = pdo_update('pintuan_qggoods', $data, array('id' => $_GPC['id']));
				if($res){
					message('编辑成功',$this->createWebUrl('qggoodall',array()),'success');
				}else{
					message('编辑失败','','error');
				}
			}
		}
include $this->template('web/addqggoodall');