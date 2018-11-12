<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$res3=pdo_get("pintuan_signset",array('uniacid'=>$_W['uniacid']));
$list=pdo_getall('pintuan_continuous',array('uniacid'=>$_W['uniacid']));
$res2=pdo_get('pintuan_special',array('uniacid'=>$_W['uniacid']));
include $this->template('web/integral');