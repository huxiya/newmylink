<?php
/**
 * 微酒店
 *
 * @url
 */

defined('IN_IA') or exit('Access Denied');

include "model.php";

class Yh_mailianModuleSite extends WeModuleSite {

	public $_img_url = '../addons/yh_mailian/template/style/img/';

	public $_css_url = '../addons/yh_mailian/template/style/css/';

	public $_script_url = '../addons/yh_mailian/template/style/js/';

	public $_search_key = '__gxmylink_search';

	public $_from_user = '';

	public $_weid = '';

	public $_version = 0;

	public $_hotel_level_config = array(1 => '1一线', 2 => '2二线', 3 => '3三线',);

	public $_set_info = array();

	public $_user_info = array();



	function __construct()
	{
		global $_W;
		$this->_from_user = $_W['fans']['from_user'];
		$this->_weid = $_W['uniacid'];
		$this->_set_info = get_hotel_set();
		$this->_version = $this->_set_info['version'];
	}

	public  function isMember() {
		global $_W;
		//判断公众号是否卡其会员卡功能
		$card_setting = pdo_fetch("SELECT * FROM ".tablename('mc_card')." WHERE uniacid = '{$_W['uniacid']}'");
		$card_status =  $card_setting['status'];
		//查看会员是否开启会员卡功能
		$membercard_setting  = pdo_get('mc_card_members', array('uniacid' => $_W['uniacid'], 'uid' => $_W['member']['uid']));
		$membercard_status = $membercard_setting['status'];
		$pricefield = !empty($membercard_status) && $card_status == 1?"mprice":"cprice";
		if (!empty($card_status) && !empty($membercard_status)) {
			return true;
		} else {
			return false;
		}
	}

	public function getItemTiles() {
		global $_W;
		$urls = array(
			array('title' => "首页", 'url' => $this->createMobileUrl('index')),
			array('title' => "我的", 'url' => $this->createMobileUrl('orderlist')),
		);
		return $urls;
	}

	function getSearchArray(){

		$search_array = get_cookie($this->_search_key);
		if (empty($search_array)) {
			//默认搜索参数
			$search_array['order_type'] = 1;
			$search_array['order_name'] = 2;
			$search_array['location_p'] = $this->_set_info['location_p'];
			$search_array['location_c'] = $this->_set_info['location_c'];
			if (strpos($search_array['location_p'], '市') > -1) {
				//直辖市
				$search_array['municipality'] = 1;
				$search_array['city_name'] = $search_array['location_p'];
			} else {
				$search_array['municipality'] = 0;
				$search_array['city_name'] = $search_array['location_c'];
			}
			$search_array['business_id'] = 0;
			$search_array['business_title'] = '';
			$search_array['brand_id'] = 0;
			$search_array['brand_title'] = '';

			$weekarray = array("日", "一", "二", "三", "四", "五", "六");

			$date = date('Y-m-d');
			$time = strtotime($date);
			$search_array['btime'] = $time;
			$search_array['etime'] = $time + 86400;
			$search_array['bdate'] = $date;
			$search_array['edate'] = date('Y-m-d', $search_array['etime']);
			$search_array['bweek'] = '星期' . $weekarray[date("w", $time)];
			$search_array['eweek'] = '星期' . $weekarray[date("w", $search_array['etime'])];
			$search_array['day'] = 1;
			insert_cookie($this->_search_key, $search_array);
		}
		//print_r($search_array);exit;
		return $search_array;
	}

	//入口文件
	public function doMobileIndex()
	{
		global $_GPC, $_W;
		$weid = $this->_weid;
		$from_user = $this->_from_user;
		$set = $this->_set_info;
		$hid = $_GPC['hid'];
		$user_info = pdo_fetch("SELECT * FROM " . tablename('gxmylinkus') . " WHERE from_user = :from_user AND weid = :weid limit 1", array(':from_user' => $from_user, ':weid' => $weid));
		
			if (empty($user_info['id'])) {
				//用户不存在
				$url = $this->createMobileUrl('login', array('from_user' => $from_user));
				header("Location: $url");
			} else {
				//用户已经存在
				$url = $this->createMobileUrl('menu', array('from_user' => $from_user));
				header("Location: $url");
			}
	}


	//巡检运维
	public function doWebHotel() {
		global $_GPC, $_W;

		$op = $_GPC['op'];
		$weid = $_W['uniacid'];
		$hotel_level_config = $this->_hotel_level_config;
		load()->func('tpl');

		if ($op == 'edit') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$insert = array(
					'weid' => $weid,
					'department' => $_GPC['department'],
					'maintain' => $_GPC['maintain'],
					'displayorder' => $_GPC['displayorder'],
					'company' => $_GPC['company'],
					'statime' => $_GPC['statime'],
					'description' => $_GPC['description'],
					'content' => $_GPC['content'],
					'traffic' => $_GPC['traffic'],
				);
				
				if ($_GPC['device']) {
					$devices = array();
					foreach ($_GPC['device'] as $key => $device) {
						if ($device != '') {
							$devices[] = $_GPC['show_device'];
						}
					}
					$devices = serialize($devices);
					$devices=strtok($devices, '|');
					if(!empty($devices))
					{
					$insert['device'] = empty($devices) ? '' :substr($devices, -1); 
					}else{
						;
					}
				}
				
			//	$devices=strtok($_GPC['device'], '|');
			//	$insert['device'] = empty($devices) ? '' :substr($devices, -1); 
				
				if (empty($id)) {
					pdo_insert('gxmylink', $insert);
				} else {
					pdo_update('gxmylink', $insert, array('id' => $id));
				}
				message("巡检运维信息保存成功!", $this->createWebUrl('hotel'), "success");
			}
			
			$listdm = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkdm'));
			$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));
			
			$sql = 'SELECT * FROM ' . tablename('gxmylink') . ' WHERE `id` = :id';
			$item = pdo_fetch($sql, array(':id' => $id));
			if (empty($item['device'])) {
				$devices = array(
					array('isdel' => 0, 'value' => '一线'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '二线'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '三线')
				);
			} else {
				$devices = array(
					array('isdel' => 0, 'value' => '一线'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '二线'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '三线')
				);
			}
			include $this->template('hotel_form');
		} else if ($op == 'delete') {

			$id = intval($_GPC['id']);
			pdo_delete("gxmylink", array("id" => $id));

			message("巡检运维信息删除成功!", referer(), "success");
		} else if ($op == 'deleteall') {
			foreach ($_GPC['idArr'] as $k => $id) {
				$id = intval($id);
				pdo_delete("gxmylink", array("id" => $id));
			}
			echo "0";
		} else {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);

			if (!empty($_GPC['title'])) {
				$where .= ' AND `title` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['title']}%";
			}
			if (!empty($_GPC['level'])) {
				$where .= ' AND level=:level';
				$params[':level'] = intval($_GPC['level']);
			}

			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylink') . $where;
			$total = pdo_fetchcolumn($sql, $params);

			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylink') . $where . ' ORDER BY `displayorder` DESC LIMIT ' .
						($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				foreach ($list as &$row) {
					$row['level'] = $this->_hotel_level_config[$row['level']];
				}

				$pager = pagination($total, $pindex, $psize);
			}

			include $this->template('hotel');
		}
	}
	
	
		public function doWebOrderxj() {
		global $_GPC, $_W;
		load()->func('tpl');
		if ($_GPC['export'] != '') {
			$weid = $_W['uniacid'];
			$psize = 20;
			$where = 'WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);
			if (!empty($_GPC['department'])) {
				$where .= ' AND `department` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['department']}%";
			}
			if (!empty($_GPC['company'])) {
				$where .= ' AND `company` LIKE :company';
				$params[':company'] = "%{$_GPC['company']}%";
			}
			if (!empty($_GPC['maintain'])) {
				$where .= ' AND `maintain` LIKE :maintain';
				$params[':maintain'] = "%{$_GPC['maintain']}%";
			}
			if (!empty($_GPC['statime'])) {
				$where .= ' AND statime<=:statime';
				$params[':statime'] = $_GPC['department'];
			}
			if (!empty($_GPC['endtime'])) {
				$where .= ' AND endtime>=:endtime';
				$params[':endtime'] = $_GPC['department'];
			}

			$psize = 20;
			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylink') . $where;
			$total = pdo_fetchcolumn($sql, $params);
			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylink') . $where . ' ORDER BY `displayorder` DESC';
				$list = pdo_fetchall($sql, $params);
				

				$pager = pagination($total, $pindex, $psize);
			}
			
				/* 输入到CSV文件 */
				$html = "\xEF\xBB\xBF";
				/* 输出表头 */
				$filter = array(
					'department' => '部门名称',
					'maintain' => '运维人员',
					'company' => '单位名称',
					'device' => '巡检类别',
					'statime' => '巡检时间',
					'description' => '巡检内容',
					'content' => '问题记录',
					'traffic' => '处理方法'
				);
				foreach ($filter as $key => $title) {
					$html .= $title . "\t,";
				}
				$html .= "\n";
				foreach ($list as $k => $v) {
					foreach ($filter as $key => $title) {
						if ($key == 'time') {
							$html .= date('Y-m-d H:i:s', $v[$key]) . "\t, ";
						} elseif ($key == 'btime') {
							$html .= date('Y-m-d', $v[$key]) . "\t, ";
						} elseif ($key == 'etime') {
							$html .= date('Y-m-d', $v[$key]) . "\t, ";
						} else {
							$html .= $v[$key] . "\t, ";
						}
					}
					$html .= "\n";
				}
				/* 输出CSV文件 */
				header("Content-type:text/csv");
				header("Content-Disposition:attachment; filename=巡检运维数据.csv");
				echo $html;
				exit();

			$pager = pagination($total, $pindex, $psize);
			include $this->template('orderxj');
		}else {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);

			if (!empty($_GPC['department'])) {
				$where .= ' AND `department` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['department']}%";
			}
			if (!empty($_GPC['company'])) {
				$where .= ' AND `company` LIKE :company';
				$params[':company'] = "%{$_GPC['company']}%";
			}
			if (!empty($_GPC['maintain'])) {
				$where .= ' AND `maintain` LIKE :maintain';
				$params[':maintain'] = "%{$_GPC['maintain']}%";
			}
			if (!empty($_GPC['statime'])) {
				$where .= ' AND statime<=:statime';
				$params[':statime'] = $_GPC['department'];
			}
			if (!empty($_GPC['endtime'])) {
				$where .= ' AND endtime>=:endtime';
				$params[':endtime'] = $_GPC['department'];
			}

			$listdm = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkdm'));
			$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));

			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylink') . $where;
			$total = pdo_fetchcolumn($sql, $params);

			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylink') . $where . ' ORDER BY `displayorder` DESC LIMIT ' .
						($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				

				$pager = pagination($total, $pindex, $psize);
			}

			include $this->template('orderxj');
		}
	}
	//<!------巡检运维------->
	
	
	//单位录入
	public function doWebCompanyinput() {
		global $_GPC, $_W;
		$op = $_GPC['op'];
		if ($op == 'edit') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$data = array(
					'weid' => $_W['uniacid'],
					'title' => $_GPC['title'],
					'style' => $_GPC['style'],
					'contact' => $_GPC['contact'],
					'mobile' => $_GPC['mobile'],
					'address' => $_GPC['address'],
					'contract' => $_GPC['contract'],
					'increasetime' => date('Y-m-d H:i:s',time()),
				);
				
				if (empty($id)) {
					pdo_insert('gxmylinkcp', $data);
					message('单位信息添加成功！', $this->createWebUrl('companyinput'), 'success');
				} else {
					pdo_update('gxmylinkcp', $data, array('id' => $id));
					message('单位信息更新成功！', $this->createWebUrl('companyinput'), 'success');
				}
				
			}
			if (!empty($id)) {
				$sql = 'SELECT * FROM ' . tablename('gxmylinkcp');
				$item = pdo_fetch($sql, array(':id' => $id));
			}
			include $this->template('companyinput_form');
		} else if ($op == 'delete') {
			$id = intval($_GPC['id']);
			pdo_delete('gxmylinkcp', array('id' => $id));
			message('删除成功！', referer(), 'success');
		} else if ($op == 'deleteall') {
			foreach ($_GPC['idArr'] as $k => $id) {

				$id = intval($id);
				pdo_delete('gxmylinkcp', array('id' => $id));
			}
			echo "0";
		}else {
			$sql = "";
			$params = array();
			if (!empty($_GPC['title'])) {
				$sql .= ' AND `title` LIKE :title';
				$params[':title'] = "%{$_GPC['title']}%";
			}
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$list = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp') . " WHERE weid = '{$_W['uniacid']}' $sql  LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('gxmylinkcp') . " WHERE weid = '{$_W['uniacid']}' $sql", $params);
			$pager = pagination($total, $pindex, $psize);
			include $this->template('companyinput');
		}
	}
	
	//用户录入
	public function doWebUserinput() {
		global $_GPC, $_W;
		$op = $_GPC['op'];
		if ($op == 'edit') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$data = array(
					'weid' => $_W['uniacid'],
					'user' => $_GPC['user'],
					'pwd' => md5($_GPC['pwd']),
					'username' => $_GPC['username'],
					'department' => $_GPC['department'],
					'increasetime' => date('Y-m-d H:i:s',time()),
				);
				
				if (empty($id)) {
					pdo_insert('gxmylinkus', $data);
					message('用户信息添加成功！', $this->createWebUrl('userinput'), 'success');
				} else {
					pdo_update('gxmylinkus', $data, array('id' => $id));
					message('用户信息更新成功！', $this->createWebUrl('userinput'), 'success');
				}
				
			}
			
			if (!empty($id)) {
				$sql = 'SELECT * FROM ' . tablename('gxmylinkus');
				$item = pdo_fetch($sql, array(':id' => $id));
			}
			
			include $this->template('userinput_form');
		} else if ($op == 'delete') {
			$id = intval($_GPC['id']);
			pdo_delete('gxmylinkus', array('id' => $id));
			message('删除成功！', referer(), 'success');
		} else if ($op == 'deleteall') {
			foreach ($_GPC['idArr'] as $k => $id) {

				$id = intval($id);
				pdo_delete('gxmylinkus', array('id' => $id));
			}
			echo "0";
		}else {
			$sql = "";
			$params = array();
			if (!empty($_GPC['title'])) {
				$sql .= ' AND `title` LIKE :title';
				$params[':title'] = "%{$_GPC['title']}%";
			}
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$list = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkus') . " WHERE weid = '{$_W['uniacid']}' $sql  LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('gxmylinkus') . " WHERE weid = '{$_W['uniacid']}' $sql", $params);
			$pager = pagination($total, $pindex, $psize);
			include $this->template('userinput');
		}
	}
	
	
	//项目录入
	public function doWebProjectinput() {
		global $_GPC, $_W;
		$op = $_GPC['op'];
		if ($op == 'edit') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$data = array(
					'weid' => $_W['uniacid'],
					'title' => $_GPC['title'],
					'department' => $_GPC['department'],
					'contractmoney' => $_GPC['contractmoney'],
					'contract' => $_GPC['contract'],
					'startime' => $_GPC['startime'],
					'endtime' => $_GPC['endtime'],
					'describe' => $_GPC['describe'],
					'firstpayment' => $_GPC['firstpayment'],
					'secondpayment' => $_GPC['secondpayment'],
					'thirdpayment' => $_GPC['thirdpayment'],
					'apirpayment' => $_GPC['apirpayment'],
					'firepayment' => $_GPC['firepayment'],
					'increasetime' => date('Y-m-d H:i:s',time()),
				);
				
				if (empty($id)) {
					pdo_insert('gxmylinkpj', $data);
					message('项目添加成功！', $this->createWebUrl('projectinput'), 'success');
				} else {
					pdo_update('gxmylinkpj', $data, array('id' => $id));
					message('项目更新成功！', $this->createWebUrl('projectinput'), 'success');
				}
				
			}
			
			$list = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));
			
			if (!empty($id)) {
				$sql = 'SELECT * FROM ' . tablename('gxmylinkpj');
				$item = pdo_fetch($sql, array(':id' => $id));
			}
			
			include $this->template('projectinput_form');
		} else if ($op == 'delete') {
			$id = intval($_GPC['id']);
			pdo_delete('gxmylinkpj', array('id' => $id));
			message('删除成功！', referer(), 'success');
		} else if ($op == 'deleteall') {
			foreach ($_GPC['idArr'] as $k => $id) {

				$id = intval($id);
				pdo_delete('gxmylinkpj', array('id' => $id));
			}
			echo "0";
		}else {
			$sql = "";
			$params = array();
			if (!empty($_GPC['title'])) {
				$sql .= ' AND `title` LIKE :title';
				$params[':title'] = "%{$_GPC['title']}%";
			}
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$list = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkpj') . " WHERE weid = '{$_W['uniacid']}' $sql  LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('gxmylinkpj') . " WHERE weid = '{$_W['uniacid']}' $sql", $params);
			$pager = pagination($total, $pindex, $psize);
			include $this->template('projectinput');
		}
	}
	
	//部门录入
	public function doWebDepartmentinput() {
		global $_GPC, $_W;
		$op = $_GPC['op'];
		if ($op == 'edit') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$data = array(
					'weid' => $_W['uniacid'],
					'title' => $_GPC['title'],
					'increasetime' => date('Y-m-d H:i:s',time()),
				);
				
				if (empty($id)) {
					pdo_insert('gxmylinkdm', $data);
					message('部门添加成功！', $this->createWebUrl('departmentinput'), 'success');
				} else {
					pdo_update('gxmylinkdm', $data, array('id' => $id));
					message('部门更新成功！', $this->createWebUrl('departmentinput'), 'success');
				}
				
			}
			
			if (!empty($id)) {
				$sql = 'SELECT * FROM ' . tablename('gxmylinkdm');
				$item = pdo_fetch($sql, array(':id' => $id));
			}
			
			include $this->template('departmentinput_form');
		} else if ($op == 'delete') {
			$id = intval($_GPC['id']);
			pdo_delete('gxmylinkdm', array('id' => $id));
			message('删除成功！', referer(), 'success');
		} else if ($op == 'deleteall') {
			foreach ($_GPC['idArr'] as $k => $id) {

				$id = intval($id);
				pdo_delete('gxmylinkdm', array('id' => $id));
			}
			echo "0";
		}else {
			$sql = "";
			$params = array();
			if (!empty($_GPC['title'])) {
				$sql .= ' AND `title` LIKE :title';
				$params[':title'] = "%{$_GPC['title']}%";
			}
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$list = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkdm') . " WHERE weid = '{$_W['uniacid']}' $sql  LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('gxmylinkdm') . " WHERE weid = '{$_W['uniacid']}' $sql", $params);
			$pager = pagination($total, $pindex, $psize);
			include $this->template('departmentinput');
		}
	}
	
	
	//维修运维
	public function doWebRepair() {
		global $_GPC, $_W;

		$op = $_GPC['op'];
		$weid = $_W['uniacid'];
		$hotel_level_config = $this->_hotel_level_config;
		load()->func('tpl');

		if ($op == 'edit') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$insert = array(
					'weid' => $weid,
					'department' => $_GPC['department'],
					'maintain' => $_GPC['maintain'],
					'displayorder' => $_GPC['displayorder'],
					'company' => $_GPC['company'],
					'statime' => $_GPC['statime'],
					'endtime' => $_GPC['endtime'],
					'address' => $_GPC['address'],
					'description' => $_GPC['description'],
					'content' => $_GPC['content'],
					'traffic' => $_GPC['traffic'],
				);
				
				if ($_GPC['device']) {
					$devices = array();
					foreach ($_GPC['device'] as $key => $device) {
						if ($device != '') {
							$devices[] = $_GPC['show_device'];
						}
					}
					$devices = serialize($devices);
					$devices=strtok($devices, '|');
					if(!empty($devices))
					{
					$insert['device'] = empty($devices) ? '' :substr($devices, -1); 
					}else{
						;
					}
				}
				
			//	$devices=strtok($_GPC['device'], '|');
			//	$insert['device'] = empty($devices) ? '' :substr($devices, -1); 
				
				if (empty($id)) {
					pdo_insert('gxmylinkrp', $insert);
				} else {
					pdo_update('gxmylinkrp', $insert, array('id' => $id));
				}
				message("维修运维信息保存成功!", $this->createWebUrl('repair'), "success");
			}
			
			$listdm = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkdm'));
			$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));
			
			$sql = 'SELECT * FROM ' . tablename('gxmylinkrp') . ' WHERE `id` = :id';
			$item = pdo_fetch($sql, array(':id' => $id));
			if (empty($item['device'])) {
				$devices = array(
					array('isdel' => 0, 'value' => '一线'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '二线'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '三线')
				);
			} else {
				$devices = array(
					array('isdel' => 0, 'value' => '一线'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '二线'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '三线')
				);
			}
			include $this->template('repair_form');
		} else if ($op == 'delete') {

			$id = intval($_GPC['id']);
			pdo_delete("gxmylinkrp", array("id" => $id));

			message("维修运维信息删除成功!", referer(), "success");
		} else if ($op == 'deleteall') {
			foreach ($_GPC['idArr'] as $k => $id) {
				$id = intval($id);
				pdo_delete("gxmylinkrp", array("id" => $id));
			}
			echo "0";
		} else {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);

			if (!empty($_GPC['title'])) {
				$where .= ' AND `title` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['title']}%";
			}
			if (!empty($_GPC['level'])) {
				$where .= ' AND level=:level';
				$params[':level'] = intval($_GPC['level']);
			}

			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylinkrp') . $where;
			$total = pdo_fetchcolumn($sql, $params);

			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylinkrp') . $where . ' ORDER BY `displayorder` DESC LIMIT ' .
						($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				foreach ($list as &$row) {
					$row['level'] = $this->_hotel_level_config[$row['level']];
				}

				$pager = pagination($total, $pindex, $psize);
			}

			include $this->template('repair');
		}
	}
	
		public function doWebOrderrp() {
		global $_GPC, $_W;
		load()->func('tpl');
		if ($_GPC['export'] != '') {
			$weid = $_W['uniacid'];
			$psize = 20;
			$where = 'WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);
			if (!empty($_GPC['department'])) {
				$where .= ' AND `department` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['department']}%";
			}
			if (!empty($_GPC['company'])) {
				$where .= ' AND `company` LIKE :company';
				$params[':company'] = "%{$_GPC['company']}%";
			}
			if (!empty($_GPC['maintain'])) {
				$where .= ' AND `maintain` LIKE :maintain';
				$params[':maintain'] = "%{$_GPC['maintain']}%";
			}
			if (!empty($_GPC['statime'])) {
				$where .= ' AND statime<=:statime';
				$params[':statime'] = $_GPC['department'];
			}
			if (!empty($_GPC['endtime'])) {
				$where .= ' AND endtime>=:endtime';
				$params[':endtime'] = $_GPC['department'];
			}

			$psize = 20;
			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylinkrp') . $where;
			$total = pdo_fetchcolumn($sql, $params);
			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylinkrp') . $where . ' ORDER BY `displayorder` DESC';
				$list = pdo_fetchall($sql, $params);
				

				$pager = pagination($total, $pindex, $psize);
			}
			
				/* 输入到CSV文件 */
				$html = "\xEF\xBB\xBF";
				/* 输出表头 */
				$filter = array(
					'department' => '部门名称',
					'maintain' => '运维人员',
					'company' => '单位名称',
					'device' => '维修类别',
					'statime' => '维修时间',
					'description' => '维修内容',
					'content' => '问题记录',
					'traffic' => '处理方法'
				);
				foreach ($filter as $key => $title) {
					$html .= $title . "\t,";
				}
				$html .= "\n";
				foreach ($list as $k => $v) {
					foreach ($filter as $key => $title) {
						if ($key == 'time') {
							$html .= date('Y-m-d H:i:s', $v[$key]) . "\t, ";
						} elseif ($key == 'btime') {
							$html .= date('Y-m-d', $v[$key]) . "\t, ";
						} elseif ($key == 'etime') {
							$html .= date('Y-m-d', $v[$key]) . "\t, ";
						} else {
							$html .= $v[$key] . "\t, ";
						}
					}
					$html .= "\n";
				}
				/* 输出CSV文件 */
				header("Content-type:text/csv");
				header("Content-Disposition:attachment; filename=维修运维数据.csv");
				echo $html;
				exit();

			$pager = pagination($total, $pindex, $psize);
			include $this->template('orderxj');
		}else {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = 'WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);

			if (!empty($_GPC['department'])) {
				$where .= ' AND `department` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['department']}%";
			}
			if (!empty($_GPC['company'])) {
				$where .= ' AND `company` LIKE :company';
				$params[':company'] = "%{$_GPC['company']}%";
			}
			if (!empty($_GPC['maintain'])) {
				$where .= ' AND `maintain` LIKE :maintain';
				$params[':maintain'] = "%{$_GPC['maintain']}%";
			}
			if (!empty($_GPC['statime'])) {
				$where .= ' AND statime<=:statime';
				$params[':statime'] = $_GPC['department'];
			}
			if (!empty($_GPC['endtime'])) {
				$where .= ' AND endtime>=:endtime';
				$params[':endtime'] = $_GPC['department'];
			}

			$listdm = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkdm'));
			$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));

			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylinkrp') . $where;
			$total = pdo_fetchcolumn($sql, $params);

			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylinkrp') . $where . ' ORDER BY `displayorder` DESC LIMIT ' .
						($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				

				$pager = pagination($total, $pindex, $psize);
			}

			include $this->template('orderrp');
		}
	}
	
	//新媒体运维
	public function doWebNewmedia() {
		global $_GPC, $_W;

		$op = $_GPC['op'];
		$weid = $_W['uniacid'];
		$hotel_level_config = $this->_hotel_level_config;
		load()->func('tpl');

		if ($op == 'edit') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$insert = array(
					'weid' => $weid,
					'department' => $_GPC['department'],
					'maintain' => $_GPC['maintain'],
					'displayorder' => $_GPC['displayorder'],
					'company' => $_GPC['company'],
					'statime' => $_GPC['statime'],
					'title' => $_GPC['endtime'],
					'description' => $_GPC['description'],
				);
				
				if ($_GPC['device']) {
					$devices = array();
					foreach ($_GPC['device'] as $key => $device) {
						if ($device != '') {
							$devices[] = $_GPC['show_device'];
						}
					}
					$devices = serialize($devices);
					$devices=strtok($devices, '|');
					if(!empty($devices))
					{
					$insert['device'] = empty($devices) ? '' :substr($devices, -1); 
					}else{
						;
					}
				}
				
			//	$devices=strtok($_GPC['device'], '|');
			//	$insert['device'] = empty($devices) ? '' :substr($devices, -1); 
				
				if (empty($id)) {
					pdo_insert('gxmylinkmedio', $insert);
				} else {
					pdo_update('gxmylinkmedio', $insert, array('id' => $id));
				}
				message("新媒体运维信息保存成功!", $this->createWebUrl('newmedia'), "success");
			}
			
			$listdm = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkdm'));
			$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));
			
			$sql = 'SELECT * FROM ' . tablename('gxmylinkmedio') . ' WHERE `id` = :id';
			$item = pdo_fetch($sql, array(':id' => $id));
			if (empty($item['device'])) {
				$devices = array(
					array('isdel' => 0, 'value' => '网站'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '微信'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '粉丝')
				);
			} else {
				$devices = array(
					array('isdel' => 0, 'value' => '网站'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '微信'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '粉丝')
				);
			}
			include $this->template('newmedia_form');
		} else if ($op == 'delete') {

			$id = intval($_GPC['id']);
			pdo_delete("gxmylinkmedio", array("id" => $id));

			message("新媒体运维信息删除成功!", referer(), "success");
		} else if ($op == 'deleteall') {
			foreach ($_GPC['idArr'] as $k => $id) {
				$id = intval($id);
				pdo_delete("gxmylinkmedio", array("id" => $id));
			}
			echo "0";
		} else {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);

			if (!empty($_GPC['title'])) {
				$where .= ' AND `title` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['title']}%";
			}
			if (!empty($_GPC['level'])) {
				$where .= ' AND level=:level';
				$params[':level'] = intval($_GPC['level']);
			}

			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylinkmedio') . $where;
			$total = pdo_fetchcolumn($sql, $params);

			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylinkmedio') . $where . ' ORDER BY `displayorder` DESC LIMIT ' .
						($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				foreach ($list as &$row) {
					$row['level'] = $this->_hotel_level_config[$row['level']];
				}

				$pager = pagination($total, $pindex, $psize);
			}

			include $this->template('newmedia');
		}
	}
	
	public function doWebOrdernd() {
		global $_GPC, $_W;
		load()->func('tpl');
		if ($_GPC['export'] != '') {
			$weid = $_W['uniacid'];
			$psize = 20;
			$where = 'WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);
			if (!empty($_GPC['department'])) {
				$where .= ' AND `department` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['department']}%";
			}
			if (!empty($_GPC['company'])) {
				$where .= ' AND `company` LIKE :company';
				$params[':company'] = "%{$_GPC['company']}%";
			}
			if (!empty($_GPC['maintain'])) {
				$where .= ' AND `maintain` LIKE :maintain';
				$params[':maintain'] = "%{$_GPC['maintain']}%";
			}
			if (!empty($_GPC['statime'])) {
				$where .= ' AND statime<=:statime';
				$params[':statime'] = $_GPC['department'];
			}
			if (!empty($_GPC['endtime'])) {
				$where .= ' AND endtime>=:endtime';
				$params[':endtime'] = $_GPC['department'];
			}

			$psize = 20;
			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylinkmedio') . $where;
			$total = pdo_fetchcolumn($sql, $params);
			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylinkmedio') . $where . ' ORDER BY `displayorder` DESC';
				$list = pdo_fetchall($sql, $params);
				

				$pager = pagination($total, $pindex, $psize);
			}
			
				/* 输入到CSV文件 */
				$html = "\xEF\xBB\xBF";
				/* 输出表头 */
				$filter = array(
					'department' => '部门名称',
					'maintain' => '编辑人员',
					'company' => '单位名称',
					'device' => '类别',
					'statime' => '发布时间',
					'title' => '内容',
					'description' => '其他'
				);
				foreach ($filter as $key => $title) {
					$html .= $title . "\t,";
				}
				$html .= "\n";
				foreach ($list as $k => $v) {
					foreach ($filter as $key => $title) {
						if ($key == 'time') {
							$html .= date('Y-m-d H:i:s', $v[$key]) . "\t, ";
						} elseif ($key == 'btime') {
							$html .= date('Y-m-d', $v[$key]) . "\t, ";
						} elseif ($key == 'etime') {
							$html .= date('Y-m-d', $v[$key]) . "\t, ";
						} else {
							$html .= $v[$key] . "\t, ";
						}
					}
					$html .= "\n";
				}
				/* 输出CSV文件 */
				header("Content-type:text/csv");
				header("Content-Disposition:attachment; filename=新媒体运维数据.csv");
				echo $html;
				exit();

			$pager = pagination($total, $pindex, $psize);
			include $this->template('ordernd');
		}else {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = 'WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);

			if (!empty($_GPC['department'])) {
				$where .= ' AND `department` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['department']}%";
			}
			if (!empty($_GPC['company'])) {
				$where .= ' AND `company` LIKE :company';
				$params[':company'] = "%{$_GPC['company']}%";
			}
			if (!empty($_GPC['maintain'])) {
				$where .= ' AND `maintain` LIKE :maintain';
				$params[':maintain'] = "%{$_GPC['maintain']}%";
			}
			if (!empty($_GPC['statime'])) {
				$where .= ' AND statime<=:statime';
				$params[':statime'] = $_GPC['statime'];
			}
			if (!empty($_GPC['endtime'])) {
				$where .= ' AND statime>=:endtime';
				$params[':endtime'] = $_GPC['endtime'];
			}

			$listdm = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkdm'));
			$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));

			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylinkmedio') . $where;
			$total = pdo_fetchcolumn($sql, $params);

			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylinkmedio') . $where . ' ORDER BY `displayorder` DESC LIMIT ' .
						($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				

				$pager = pagination($total, $pindex, $psize);
			}

			include $this->template('ordernd');
		}
	}
	
	//教育运维
	public function doWebEducation() {
		global $_GPC, $_W;

		$op = $_GPC['op'];
		$weid = $_W['uniacid'];
		$hotel_level_config = $this->_hotel_level_config;
		load()->func('tpl');

		if ($op == 'edit') {
			$id = intval($_GPC['id']);
			if (checksubmit('submit')) {
				$insert = array(
					'weid' => $weid,
					'department' => $_GPC['department'],
					'maintain' => $_GPC['maintain'],
					'displayorder' => $_GPC['displayorder'],
					'company' => $_GPC['company'],
					'statime' => $_GPC['statime'],
					'title' => $_GPC['endtime'],
					'description' => $_GPC['description'],
				);
				
				if ($_GPC['device']) {
					$devices = array();
					foreach ($_GPC['device'] as $key => $device) {
						if ($device != '') {
							$devices[] = $_GPC['show_device'];
						}
					}
					$devices = serialize($devices);
					$devices=strtok($devices, '|');
					if(!empty($devices))
					{
					$insert['device'] = empty($devices) ? '' :substr($devices, -1); 
					}else{
						;
					}
				}
				
			//	$devices=strtok($_GPC['device'], '|');
			//	$insert['device'] = empty($devices) ? '' :substr($devices, -1); 
				
				if (empty($id)) {
					pdo_insert('gxmylinked', $insert);
				} else {
					pdo_update('gxmylinked', $insert, array('id' => $id));
				}
				message("教育运维信息保存成功!", $this->createWebUrl('education'), "success");
			}
			
			$listdm = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkdm'));
			$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp')."where style='学校'");
			
			$sql = 'SELECT * FROM ' . tablename('gxmylinked') . ' WHERE `id` = :id';
			$item = pdo_fetch($sql, array(':id' => $id));
			if (empty($item['device'])) {
				$devices = array(
					array('isdel' => 0, 'value' => '培训'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '活动'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '户外')
				);
			} else {
				$devices = array(
					array('isdel' => 0, 'value' => '培训'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '活动'),
					array('isdel' => 0, 'isshow' => 0, 'value' => '户外')
				);
			}
			include $this->template('education_form');
		} else if ($op == 'delete') {

			$id = intval($_GPC['id']);
			pdo_delete("gxmylinked", array("id" => $id));

			message("教育运维信息删除成功!", referer(), "success");
		} else if ($op == 'deleteall') {
			foreach ($_GPC['idArr'] as $k => $id) {
				$id = intval($id);
				pdo_delete("gxmylinked", array("id" => $id));
			}
			echo "0";
		} else {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);

			if (!empty($_GPC['title'])) {
				$where .= ' AND `title` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['title']}%";
			}
			if (!empty($_GPC['level'])) {
				$where .= ' AND level=:level';
				$params[':level'] = intval($_GPC['level']);
			}

			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylinked') . $where;
			$total = pdo_fetchcolumn($sql, $params);

			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylinked') . $where . ' ORDER BY `displayorder` DESC LIMIT ' .
						($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				foreach ($list as &$row) {
					$row['level'] = $this->_hotel_level_config[$row['level']];
				}

				$pager = pagination($total, $pindex, $psize);
			}

			include $this->template('education');
		}
	}
	
	public function doWebOrdered() {
		global $_GPC, $_W;
		load()->func('tpl');
		if ($_GPC['export'] != '') {
			$weid = $_W['uniacid'];
			$psize = 20;
			$where = 'WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);
			if (!empty($_GPC['department'])) {
				$where .= ' AND `department` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['department']}%";
			}
			if (!empty($_GPC['company'])) {
				$where .= ' AND `company` LIKE :company';
				$params[':company'] = "%{$_GPC['company']}%";
			}
			if (!empty($_GPC['maintain'])) {
				$where .= ' AND `maintain` LIKE :maintain';
				$params[':maintain'] = "%{$_GPC['maintain']}%";
			}
			if (!empty($_GPC['statime'])) {
				$where .= ' AND statime<=:statime';
				$params[':statime'] = $_GPC['department'];
			}
			if (!empty($_GPC['endtime'])) {
				$where .= ' AND endtime>=:endtime';
				$params[':endtime'] = $_GPC['department'];
			}

			$psize = 20;
			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylinked') . $where;
			$total = pdo_fetchcolumn($sql, $params);
			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylinked') . $where . ' ORDER BY `displayorder` DESC';
				$list = pdo_fetchall($sql, $params);
				

				$pager = pagination($total, $pindex, $psize);
			}
			
				/* 输入到CSV文件 */
				$html = "\xEF\xBB\xBF";
				/* 输出表头 */
				$filter = array(
					'department' => '部门名称',
					'maintain' => '编辑人员',
					'company' => '单位名称',
					'device' => '类别',
					'statime' => '发布时间',
					'title' => '内容',
					'description' => '其他'
				);
				foreach ($filter as $key => $title) {
					$html .= $title . "\t,";
				}
				$html .= "\n";
				foreach ($list as $k => $v) {
					foreach ($filter as $key => $title) {
						if ($key == 'time') {
							$html .= date('Y-m-d H:i:s', $v[$key]) . "\t, ";
						} elseif ($key == 'btime') {
							$html .= date('Y-m-d', $v[$key]) . "\t, ";
						} elseif ($key == 'etime') {
							$html .= date('Y-m-d', $v[$key]) . "\t, ";
						} else {
							$html .= $v[$key] . "\t, ";
						}
					}
					$html .= "\n";
				}
				/* 输出CSV文件 */
				header("Content-type:text/csv");
				header("Content-Disposition:attachment; filename=教育运维数据.csv");
				echo $html;
				exit();

			$pager = pagination($total, $pindex, $psize);
			include $this->template('ordered');
		}else {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = 'WHERE `weid` = :weid';
			$params = array(':weid' => $_W['uniacid']);

			if (!empty($_GPC['department'])) {
				$where .= ' AND `department` LIKE :keywords';
				$params[':keywords'] = "%{$_GPC['department']}%";
			}
			if (!empty($_GPC['company'])) {
				$where .= ' AND `company` LIKE :company';
				$params[':company'] = "%{$_GPC['company']}%";
			}
			if (!empty($_GPC['maintain'])) {
				$where .= ' AND `maintain` LIKE :maintain';
				$params[':maintain'] = "%{$_GPC['maintain']}%";
			}
			if (!empty($_GPC['statime'])) {
				$where .= ' AND statime<=:statime';
				$params[':statime'] = $_GPC['statime'];
			}
			if (!empty($_GPC['endtime'])) {
				$where .= ' AND statime>=:endtime';
				$params[':endtime'] = $_GPC['endtime'];
			}

			$listdm = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkdm'));
			$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));

			$sql = 'SELECT COUNT(*) FROM ' . tablename('gxmylinked') . $where;
			$total = pdo_fetchcolumn($sql, $params);

			if ($total > 0) {
				$pindex = max(1, intval($_GPC['page']));
				$psize = 10;

				$sql = 'SELECT * FROM ' . tablename('gxmylinked') . $where . ' ORDER BY `displayorder` DESC LIMIT ' .
						($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				

				$pager = pagination($total, $pindex, $psize);
			}

			include $this->template('ordered');
		}
	}
	
	//手机端
	
	public function doMobilelogin()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false ) {	
		}else{
		global $_GPC, $_W;
		$weid = $this->_weid;
		$from_user = $_GPC['from_user'];
		$op = $_GPC['op'];
		$text1 = $_GPC['text1'];
		$text2 = $_GPC['text2'];
		$text2 = md5($text2);
		$register = $this->createMobileUrl('register', array('from_user' => $from_user));
		$login = $this->createMobileUrl('login', array('from_user' => $from_user));
		if ($op == 'edit') {
			
			$user_info = pdo_fetch("SELECT * FROM " . tablename('gxmylinkus') . " WHERE user = :text1", array(':text1' => $text1));
		
			if ($user_info['pwd']==$text2) {
				//正确
			//	$url = $this->createMobileUrl('menu', array('from_user' => $from_user));
			//	header("Location: $url");
				$id=$user_info['id'];
				$insert = array(
					'from_user' => $_GPC['from_user'],
				);
				pdo_update('gxmylinkus', $insert, array('id' => $id));
				echo "0";
			} else {
				//错误
				echo "1";
			}
		}
		
		include $this->template('login');
		}
	}
	
	public function doMobileregister()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false ) {	
		}else{
		global $_GPC, $_W;
		$weid = $this->_weid;
		$from_user = $_GPC['from_user'];
		$op = $_GPC['op'];
		$text1 = $_GPC['text1'];
		$text2 = $_GPC['text2'];
		$text2 = md5($text2);
		$register = $this->createMobileUrl('register', array('from_user' => $from_user));
		
		$listdm = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkdm'));
		$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));
		$register = $this->createMobileUrl('register', array('from_user' => $from_user));
		
		if ($op == 'edit') {
				
				$user_info = pdo_fetch("SELECT * FROM " . tablename('gxmylinkus') . " WHERE user = :text1", array(':text1' => $text1));
				if($user_info['id']=='')
				{
				$insert = array(
					'weid' => $weid,
					'user' => $_POST['text1'],
					'pwd' => md5($_POST['text2']),
					'department' => $_POST['text4'],
					'username' => $_POST['text3'],
					'from_user' => $from_user,
					'increasetime' => date('Y-m-d H:i:s',time()),
				);
				pdo_insert('gxmylinkus', $insert);
				echo "1";
				}else{
					echo "0";
				}
		}
		
		include $this->template('register');
		}
	}
	
	public function doMobilemenu()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false ) {	
		}else{
		global $_GPC, $_W;
		$weid = $this->_weid;
		$from_user = $_GPC['from_user'];
		$op = $_GPC['op'];
		
		$inspection = $this->createMobileUrl('inspection', array('from_user' => $from_user));
		$service = $this->createMobileUrl('service', array('from_user' => $from_user));
		$newmedia = $this->createMobileUrl('newmedia', array('from_user' => $from_user));
		$icloud = $this->createMobileUrl('icloud', array('from_user' => $from_user));
		$adminmenu = $this->createMobileUrl('adminmenu', array('from_user' => $from_user));
		
		include $this->template('menu');
		}
	}
	
	public function doMobileinspection()
	{
	//	if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false ) {	
	//	}else{
		global $_GPC, $_W;
		$weid = $this->_weid;
		$from_user = $_GPC['from_user'];
		$op = $_GPC['op'];
		
		$user_info = pdo_fetch("SELECT * FROM " . tablename('gxmylinkus') . " WHERE from_user = :from_user", array(':from_user' => $from_user));
		$listcpa = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp')."where style='机关'");
		$listcpb = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp')."where style='学校'");
		$listcpc = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp')."where style='企业'");
		$inspection = $this->createMobileUrl('inspection', array('from_user' => $from_user));
		
		if ($op == 'edit') {
				
				$insert = array(
					'weid' => $weid,
					'department' => $user_info['department'],
					'maintain' => $user_info['username'],
					'displayorder' => '-1',
					'company' => $_POST['text1'],
					'device' => $_GPC['text2'],
					'statime' => $_GPC['text3'],
					'description' => $_GPC['text4'],
					'content' => $_GPC['text5'],
					'traffic' => $_GPC['text6'],
				);
				pdo_insert('gxmylink', $insert);
				echo "1";
		}
		
		include $this->template('inspection');
	//	}
	}
	
	public function doMobileservice()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false ) {	
		}else{
		global $_GPC, $_W;
		$weid = $this->_weid;
		$from_user = $_GPC['from_user'];
		$op = $_GPC['op'];
		
		$user_info = pdo_fetch("SELECT * FROM " . tablename('gxmylinkus') . " WHERE from_user = :from_user", array(':from_user' => $from_user));
		$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));
		$service = $this->createMobileUrl('service', array('from_user' => $from_user));
		
		if ($op == 'edit') {
				
				$insert = array(
					'weid' => $weid,
					'department' => $user_info['department'],
					'maintain' => $user_info['username'],
					'displayorder' => '-1',
					'company' => $_GPC['text1'],
					'address' => $_GPC['text2'],
					'device' => $_GPC['text3'],
					'statime' => $_GPC['text4'],
					'endtime' => $_GPC['text5'],
					'description' => $_GPC['text6'],
					'content' => $_GPC['text7'],
					'traffic' => $_GPC['text8'],
				);
				pdo_insert('gxmylinkrp', $insert);
				echo "1";
		}
		
		include $this->template('service');
		}
	}
	
	public function doMobilenewmedia()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false ) {	
		}else{
		global $_GPC, $_W;
		$weid = $this->_weid;
		$from_user = $_GPC['from_user'];
		$op = $_GPC['op'];
		
		$user_info = pdo_fetch("SELECT * FROM " . tablename('gxmylinkus') . " WHERE from_user = :from_user", array(':from_user' => $from_user));
		$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));
		$newmedia = $this->createMobileUrl('newmedia', array('from_user' => $from_user));
		
		if ($op == 'edit') {
				
				$insert = array(
					'weid' => $weid,
					'department' => $user_info['department'],
					'maintain' => $user_info['username'],
					'displayorder' => '-1',
					'company' => $_GPC['text1'],
					'statime' => $_GPC['text3'],
					'device' => $_GPC['text2'],
					'title' => $_GPC['text4'],
					'description' => $_GPC['text5'],
				);
				pdo_insert('gxmylinkmedio', $insert);
				echo "1";
		}
		
		include $this->template('newmedia');
		}
	}
	
	public function doMobileicloud()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false ) {	
		}else{
		global $_GPC, $_W;
		$weid = $this->_weid;
		$from_user = $_GPC['from_user'];
		$op = $_GPC['op'];
		
		$user_info = pdo_fetch("SELECT * FROM " . tablename('gxmylinkus') . " WHERE from_user = :from_user", array(':from_user' => $from_user));
		$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));
		$icloud = $this->createMobileUrl('icloud', array('from_user' => $from_user));
		
		if ($op == 'edit') {
				
				$insert = array(
					'weid' => $weid,
					'department' => $user_info['department'],
					'maintain' => $user_info['username'],
					'displayorder' => '-1',
					'company' => $_GPC['text1'],
					'device' => $_GPC['text2'],
					'statime' => $_GPC['text3'],
					'title' => $_GPC['text4'],
					'description' => $_GPC['text5'],
				);
				pdo_insert('gxmylinked', $insert);
				echo "1";
		}
		
		include $this->template('icloud');
		}
	}
	
	public function doMobileadminmenu()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false ) {	
		}else{
		global $_GPC, $_W;
		$weid = $this->_weid;
		$from_user = $_GPC['from_user'];
		$op = $_GPC['op'];
		
		$addcompany = $this->createMobileUrl('addcompany', array('from_user' => $from_user));
		$addproject = $this->createMobileUrl('addproject', array('from_user' => $from_user));
		
		include $this->template('adminmenu');
		}
	}
	
	public function doMobileaddcompany()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false ) {	
		}else{
		global $_GPC, $_W;
		$weid = $this->_weid;
		$from_user = $_GPC['from_user'];
		$op = $_GPC['op'];
		
		if ($op == 'edit') {
				
				$insert = array(
					'weid' => $weid,
					'style' => $_GPC['text1'],
					'title' => $_GPC['text2'],
					'contact' => $_GPC['text3'],
					'mobile' => $_GPC['text4'],
					'address' => $_GPC['text5'],
					'contract' => $_GPC['text6'],
					'increasetime' => date('Y-m-d H:i:s',time())
				);
				pdo_insert('gxmylinkcp', $insert);
				
				echo "1";
		}
		
		include $this->template('addcompany');
		}
	}
	
	public function doMobileaddproject()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false ) {	
		}else{
		global $_GPC, $_W;
		$weid = $this->_weid;
		$from_user = $_GPC['from_user'];
		$op = $_GPC['op'];
		load()->func('tpl');
		
		$listcp = pdo_fetchall("SELECT * FROM " . tablename('gxmylinkcp'));
		
		if ($op == 'edit') {
				
				$insert = array(
					'weid' => $weid,
					'department' => $_GPC['text1'],
					'title' => $_GPC['text2'],
					'contractmoney' => $_GPC['text3'],
					'contract' => $_GPC['text4'],
					'startime' => $_GPC['text5'],
					'endtime' => $_GPC['text6'],
					'describe' => $_GPC['text7'],
					'firstpayment' => $_GPC['text8'],
					'secondpayment' => $_GPC['text9'],
					'thirdpayment' => $_GPC['text10'],
					'apirpayment' => $_GPC['text11'],
					'firepayment' => $_GPC['text12'],
					'increasetime' => date('Y-m-d H:i:s',time())
				);
				pdo_insert('gxmylinkpj', $insert);
				
				echo "1";
		}
		
		include $this->template('addproject');
		}
	}
	
}
