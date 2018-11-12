<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$item = pdo_fetch("SELECT a.*,b.name as table_name,c.name as type_name FROM ".tablename('pintuan_order'). " a"  . " left join " . tablename("pintuan_table") . " b on a.table_id=b.id  left join " . tablename("pintuan_table_type") ." c on b.type_id=c.id WHERE  a.id=:id", array(':id'=>$_GPC['id']));
$goods=pdo_getall('pintuan_order_goods',array('order_id'=>$_GPC['id']));

include $this->template('web/dnorderinfo');