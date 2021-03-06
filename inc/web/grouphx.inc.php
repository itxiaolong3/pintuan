<?php
global $_GPC, $_W;
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);

$strwhere = '';
$pindex = max(1, intval($_GPC['page']));
$psize = 10;
$list = pdo_fetchall("SELECT a.*,b.name,b.openid,b.img FROM " . tablename('pintuan_grouphx') ." a left join" .tablename('pintuan_user')." b on a.hx_id=b.id WHERE a.store_id = :store_id  ORDER BY a.id DESC LIMIT
    " . ($pindex - 1) * $psize . ',' . $psize, array(':store_id' =>$storeid));

if (!empty($list)) {
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('pintuan_grouphx')." a left join" .tablename('pintuan_user')." b on a.hx_id=b.id WHERE a.store_id = :store_id", array(':store_id' => $storeid));
    $pager = pagination($total, $pindex, $psize);
}
if($_GPC['op']=='delete'){
		$res4=pdo_delete("pintuan_grouphx",array('id'=>$_GPC['id']));
		if($res4){
		 message('删除成功！', $this->createWebUrl('grouphx'), 'success');
		}else{
			  message('删除失败！','','error');
		}
	}
include $this->template('web/grouphx');