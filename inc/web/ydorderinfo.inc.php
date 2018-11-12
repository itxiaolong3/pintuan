<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$item=pdo_get('pintuan_order',array('id'=>$_GPC['id']));

include $this->template('web/ydorderinfo');