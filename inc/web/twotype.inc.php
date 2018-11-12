<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$where="WHERE a.uniacid=:uniacid and a.ID>0";
$data[':uniacid']=$_W['uniacid'];
$sql="select a.*,b.ID as id,b.Name as onename from " . tablename("pintuan_typetwomy") . " a"
    . " left join " . tablename("pintuan_typeonemy")
    . " b on a.Pid=b.id " .$where." order by a.ID asc";
$list=pdo_fetchall($sql,$data);
if($_GPC['op']=='delete'){
	$res=pdo_delete('pintuan_typetwomy',array('ID'=>$_GPC['id']));
	if($res){
		 message('删除成功！', $this->createWebUrl('twotype'), 'success');
		}else{
			  message('删除失败！','','error');
		}
}
include $this->template('web/twotype');