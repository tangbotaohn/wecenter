<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class user_manage extends AWS_CONTROLLER
{
	var $per_page = 20;

	public function setup()
	{
		$this->model('admin_session')->init();
	}

	public function index_action()
	{
		$this->list_action();
	}

	public function list_action()
	{
		$_GET['sort_key'] = isset($_GET['sort_key']) ? $_GET['sort_key'] : 'uid';
		$_GET['order'] = isset($_GET['order']) ? $_GET['order'] : 'DESC';
		
		$search_data = array(
			'action' => $_GET['action'],
			'user_name' => rawurldecode($_GET['user_name']),
			'email' => rawurldecode($_GET['email']),
			'reg_date' => base64_decode($_GET['reg_date']),
			'group_id' => $_GET['group_id'],
			'last_login_date' => base64_decode($_GET['last_login_date']),
			'ip' => $_GET['ip'],
			'integral_min' => $_GET['integral_min'],
			'integral_max' => $_GET['integral_max'],
			'reputation_min' => $_GET['reputation_min'],
			'reputation_max' => $_GET['reputation_max'],
			'answer_count_min' => $_GET['answer_count_min'],
			'answer_count_max' => $_GET['answer_count_max'],
			'job_id' => $_GET['job_id'],
			'province' => $_GET['province'],
			'city' => $_GET['city'],
			'birthday' => base64_decode($_GET['birthday']),
			'signature' => rawurldecode($_GET['signature']),
			'common_email' => $_GET['common_email'],
			'mobile' => $_GET['mobile'],
			'qq' => $_GET['qq'],
			'homepage' => $_GET['homepage'],
			'school_name' => rawurldecode($_GET['school_name']),
			'departments' => rawurldecode($_GET['departments']),
			'company_name' => rawurldecode($_GET['company_name']),
			'company_job_id' => $_GET['company_job_id'],
			'sort_key' => $_GET['sort_key'],
			'order' => $_GET['order'],
			'page' => $_GET['page'],
			'per_page' => $this->per_page,
		);
		
		if ($_POST['action'] == 'search')
		{
			foreach ($_POST as $key => $val)
			{
				if (in_array($key, array('reg_date', 'last_login_date', 'end_date', 'birthday')))
				{
					$val = base64_encode($val);
				}
				
				if (in_array($key, array('user_name', 'email', 'signature', 'school_name', 'departments', 'company_name')))
				{
					$val = rawurlencode($val);
				}
				
				$param[] = $key . '-' . $val;
			}
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_setting('base_url') . '/?/admin/user_manage/list/' . implode('__', $param)
			), 1, null));
		}
		
		$user_list = $this->model('account')->get_users_list_by_search(false, $search_data);
		
		$total_rows = $this->model('account')->get_users_list_by_search(true, $search_data);
		
		$url_param = array();
		
		foreach($_GET as $key => $val)
		{
			if (isset($search_data[$key]) AND !in_array($key, array('sort_key', 'order', 'page')))
			{
				$url_param[] = $key . '-' . $val;
			}
		}
		
		$search_url = 'admin/user_manage/list/' . implode('__', $url_param);
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/' . $search_url . '__sort_key-' . $_GET['sort_key'] . '__order-' . $_GET['order'], 
			'total_rows' => $total_rows, 
			'per_page' => $this->per_page, 
			'last_link' => '末页', 
			'first_link' => '首页', 
			'next_link' => '下一页 »', 
			'prev_link' => '« 上一页', 
			'anchor_class' => ' class="number"', 
			'cur_tag_open' => '<a class="number current">', 
			'cur_tag_close' => '</a>', 
			'direct_page' => TRUE
		))->create_links());
		
		$this->crumb(AWS_APP::lang()->_t('会员列表'), "admin/user_manage/list/");
		
		TPL::import_js('js/LocationSelect.js');
		
		TPL::assign('system_group', $this->model('account')->get_user_group_list(0));
		TPL::assign('job_list', $this->model('work')->get_jobs_list());
		TPL::assign('search_url', $search_url);
		TPL::assign('total_rows', $total_rows);
		TPL::assign('list', $user_list);
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 402));
		TPL::output('admin/user_manage/list');
	}
	
	public function group_list_action()
	{
		$this->crumb(AWS_APP::lang()->_t('用户组管理'), "admin/user_manage/group_list/");
		
		TPL::assign('mem_group', $this->model('account')->get_user_group_list(1));
		TPL::assign('system_group', $this->model('account')->get_user_group_list(0));
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 403));
		TPL::output('admin/user_manage/group_list');
	}

	public function group_save_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		if ($group_data = $_POST['group'])
		{
			foreach ($group_data as $key => $val)
			{
				if (empty($val['group_name']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入用户组名称')));
				}
				
				if ($val['reputation_factor'])
				{
					if (!is_numeric($val['reputation_factor']) || floatval($val['reputation_factor']) < 0)
					{
						H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('威望系数必须为大于或等于 0')));
					}
					
					if (!is_numeric($val['reputation_lower']) || floatval($val['reputation_lower']) < 0 || !is_numeric($val['reputation_higer']) || floatval($val['reputation_higer']) < 0)
					{
						H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('威望介于值必须为大于或等于 0')));
					}
					
					$val['reputation_factor'] = floatval($val['reputation_factor']);
				}
				
				$this->model('account')->update_group($key, $val);
			}
		}
		
		if ($group_new = $_POST['group_new'])
		{
			foreach ($group_new['group_name'] as $key => $val)
			{
				if (trim($group_new['group_name'][$key]))
				{
					$this->model('account')->add_group($group_new['group_name'][$key], $group_new['reputation_lower'][$key], $group_new['reputation_higer'][$key], $group_new['reputation_factor'][$key]);
				}
			}
		}
		
		if ($group_ids = $_POST['group_ids'])
		{
			foreach ($group_ids as $key => $id)
			{
				$group_info = $this->model('account')->get_group_by_id($id);
				
				if ($group_info['type'] == 1)
				{
					$this->model('account')->delete_group($id);
				}
				else
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('系统用户组不可删除')));
				}
			}
		}
		
		AWS_APP::cache()->cleanGroup('users_group');
		
		if ($group_new || $group_ids)
		{
			$rsm_array = array(
				'url' => get_js_url('/admin/user_manage/group_list/')
			);
		}
		
		H::ajax_json_output(AWS_APP::RSM($rsm_array, 1, null));
	}

	public function group_edit_action()
	{
		if (! $group = $this->model('account')->get_group_by_id(intval($_GET['group_id'])))
		{
			H::redirect_msg(AWS_APP::lang()->_t('用户组不存在'));
		}
		
		$this->crumb(AWS_APP::lang()->_t('用户组管理'), "admin/user_manage/group_list/");
		
		TPL::assign('group', $group);
		TPL::assign('group_pms', $group['permission']);
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 403));
		TPL::output('admin/user_manage/group_edit');
	}

	/**
	 * 保存用户组编辑
	 */
	public function group_edit_process_action()
	{
		$permission_array = array(
			'is_administortar',
			'is_moderator',
			'publish_question',
			'publish_approval',
			'publish_approval_time',
			'edit_question',
			'edit_topic',
			'manage_topic',
			'create_topic',
			'redirect_question',
			'upload_attach',
			'publish_url',
			'human_valid',
			'question_valid_hour',
			'answer_valid_hour',
			'visit_site',
			'visit_explore',
			'search_avail',
			'visit_question',
			'visit_topic',
			'visit_feature',
			'visit_people',
			'answer_show',
			'function_interval'
		);
		
		$group_setting = array();
		
		foreach ($permission_array as $permission)
		{
			if ($_POST[$permission])
			{
				$group_setting[$permission] = $_POST[$permission];
			}
		}
		
		$this->model('account')->update_group($_GET['group_id'], array(
			'permission' => serialize($group_setting)
		));
		
		AWS_APP::cache()->cleanGroup('users_group');
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	/**
	 * 修改用户资料
	 */
	public function edit_action()
	{
		$this->crumb(AWS_APP::lang()->_t('编辑用户资料'), "admin/user_manage/list/");
		
		TPL::assign('system_group', $this->model('account')->get_user_group_list(0));
		TPL::assign('user', $this->model('account')->get_user_info_by_uid($_GET['uid'], TRUE));
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 402));
		
		TPL::output("admin/user_manage/edit");
	}

	/**
	 * 用户修改处理
	 */
	public function user_save_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		$user_id = intval($_POST['uid']);
		
		if ($user_id)
		{
			unset($_POST['uid']);
			
			if (!$user_info = $this->model('account')->get_user_info_by_uid($user_id))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
			}
			
			if ($_POST['user_name'] != $user_info['user_name'] && $this->model('account')->get_user_info_by_username($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户名已存在')));
			}
			
			if ($_POST['email'])
			{
				$update_data['email'] = htmlspecialchars($_POST['email']);
			}
			
			if ($_POST['invitation_available'])
			{
				$update_data['invitation_available'] = intval($_POST['invitation_available']);
			}
			
			$update_data['verified'] = intval($_POST['verified']);
			$update_data['valid_email'] = intval($_POST['valid_email']);
			$update_data['forbidden'] = intval($_POST['forbidden']);
			
			if ($this->user_info['group_id'] == 1 AND $_POST['group_id'])
			{
				$update_data['group_id'] = intval($_POST['group_id']);
			}
			
			$this->model('account')->update_users_fields($update_data, $user_id);
			
			if ($_POST['delete_avatar'])
			{
				$this->model('account')->delete_avatar($user_id);
			}
			
			if ($_POST['password'])
			{
				$this->model('account')->update_user_password_ingore_oldpassword($_POST['password'], $user_id, fetch_salt(4));
			}
			
			$this->model('account')->update_users_attrib_fields(array(
				'signature' => htmlspecialchars($_POST['signature'])
			), $user_id);
			
			if ($_POST['user_name'] != $user_info['user_name'])
			{
				$this->model('account')->update_user_name($_POST['user_name'], $user_id);
			}
			
			H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('用户资料更新成功')));
		}
		else
		{
			if (trim($_POST['user_name']) == '')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户名')));
			}
			
			if ($this->model('account')->check_username($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已经存在')));
			}
			
			if ($this->model('account')->check_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('E-Mail 已经被使用, 或格式不正确')));
			}
			
			if (strlen($_POST['password']) < 6 or strlen($_POST['password']) > 16)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
			}
			
			$this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email'], true);
			
			H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('用户添加成功')));
		}
	}
	
	/**
	 * 设置会员状态
	 */
	public function forbidden_status_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		$this->model('account')->forbidden_user($_GET['user_id'], $_GET['status'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, "1", null));
	}

	public function user_add_action()
	{
		$this->crumb(AWS_APP::lang()->_t('添加用户'), "admin/user_manage/user_add/");
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 405));
		TPL::output('admin/user_manage/add');
	}

	public function invites_action()
	{
		$this->crumb(AWS_APP::lang()->_t('批量邀请'), "admin/user_manage/invites/");
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 406));
		TPL::output('admin/user_manage/invites');
	}

	public function invites_ajax_action()
	{
		if ($_POST['email_list'] && $emails = explode("\n", str_replace("\r", "\n", $_POST['email_list'])))
		{
			foreach($emails as $key => $email)
			{
				if (($email = trim($email)) == '')
				{
					continue;
				}
				
				if (!H::valid_email($email))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('邮箱地址无效')));
				}
				
				$email_list[] = strtolower($email);
			}
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入邮箱地址')));
		}
		
		$this->model('invitation')->send_batch_invitations(array_unique($email_list), $this->user_id, $this->user_info['user_name']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function job_list_action()
	{
		TPL::assign('job_list', $this->model('work')->get_jobs_list());
		
		$this->crumb(AWS_APP::lang()->_t('职位设置'), "admin/user_manage/job_list/");
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 407));
		TPL::output('admin/user_manage/job_list');
	}
	
	public function remove_job_action()
	{
		$this->model('work')->remove_job(intval($_GET['job_id']));
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function add_job_ajax_action()
	{
		if (!$_POST['jobs'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入职位名称')));
		}
		
		$job_list = array();
		
		if ($job_list_tmp = explode("\n", $_POST['jobs']))
		{
			foreach($job_list_tmp as $key => $job)
			{
				$job_name = trim(strtolower($job));
				
				if (!empty($job_name))
				{
					$job_list[] = $job_name;
				}
			}
		}
		else
		{
			$job_list[] = $_POST['jobs'];
		}
		
		foreach($job_list as $key => $val)
		{
			$this->model('work')->add_job($val);
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function save_job_ajax_action()
	{
		if ($_POST['job_list'])
		{
			foreach($_POST['job_list'] as $key => $val)
			{
				$this->model('work')->update_job($key, array(
					'job_name' => $val,
				));
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function integral_action()
	{
		$this->crumb(AWS_APP::lang()->_t('积分操作'), "admin/user_manage/tegral/");
		
		TPL::assign('user', $this->model('account')->get_user_info_by_uid($_GET['uid']));
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 407));
		TPL::output('admin/user_manage/integral');
	}
	
	public function integral_add_ajax_action()
	{
		if (!$_POST['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择用户进行操作')));
		}
		
		$integral = $_POST['integral'];
			
		if (!$_POST['note'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写理由')));
		}
		
		$this->model('integral')->process($_POST['uid'], 'AWARD', $integral, $_POST['note']);
		
		H::ajax_json_output(AWS_APP::RSM(array('url' => get_setting('base_url') . '/?/admin/user_manage/integral/uid-' . $_POST['uid']), 1, null));
	}
	
	public function forbidden_list_action()
	{
		$list = $this->model('account')->get_forbidden_user_list(false, 'uid DESC', calc_page_limit($_GET['page'], $this->per_page));
		
		$total_rows = $this->model('account')->get_forbidden_user_list(true);
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/admin/user_manage/forbidden_list/', 
			'total_rows' => $total_rows, 
			'per_page' => $this->per_page, 
			'last_link' => '末页', 
			'first_link' => '首页', 
			'next_link' => '下一页 »', 
			'prev_link' => '« 上一页', 
			'anchor_class' => ' class="number"', 
			'cur_tag_open' => '<a class="number current">', 
			'cur_tag_close' => '</a>', 
			'direct_page' => TRUE
		))->create_links());
		
		$this->crumb(AWS_APP::lang()->_t('禁封用户'), 'admin/user_manage/forbidden_list/');
		
		TPL::assign('total_rows', $total_rows);
		TPL::assign('list', $list);
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 408));
		TPL::output('admin/user_manage/forbidden_list');
	}
	
	public function verify_approval_list_action()
	{
		$approval_list = $this->model('verify')->approval_list($_GET['page'], $this->per_page);
		
		$total_rows = $this->model('verify')->found_rows();
		
		foreach ($approval_list AS $key => $val)
		{
			if (!$uids[$val['uid']])
			{
				$uids[$val['uid']] = $val['uid'];
			}
		}
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/admin/user_manage/verify_approval_list/', 
			'total_rows' => $total_rows, 
			'per_page' => $this->per_page, 
			'last_link' => '末页', 
			'first_link' => '首页', 
			'next_link' => '下一页 »', 
			'prev_link' => '« 上一页', 
			'anchor_class' => ' class="number"', 
			'cur_tag_open' => '<a class="number current">', 
			'cur_tag_close' => '</a>', 
			'direct_page' => TRUE
		))->create_links());
		
		$this->crumb(AWS_APP::lang()->_t('认证审核'), 'admin/user_manage/verify_approval_list/');
		
		TPL::assign('users_info', $this->model('account')->get_user_info_by_uids($uids, TRUE));
		TPL::assign('approval_list', $approval_list);
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 401));
		TPL::assign('job_list', $this->model('work')->get_jobs_list());
		TPL::output('admin/user_manage/verify_approval_list');
	}
	
	public function verify_approval_batch_action()
	{
		if (!is_array($_POST['approval_ids']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
		}
		
		switch ($_POST['batch_type'])
		{
			case 'approval':
			case 'decline':
				$func = $_POST['batch_type'] . '_verify';
				
				foreach ($_POST['approval_ids'] AS $approval_id)
				{
					$this->model('verify')->$func($approval_id);
				}
			break;
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function integral_log_action()
	{
		if ($log = $this->model('integral')->fetch_page('integral_log', 'uid = ' . intval($_GET['uid']), 'time DESC', $_GET['page'], 50))
		{
			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_setting('base_url') . '/?/user_manage/integral_log/uid-' . intval($_GET['uid']), 
				'total_rows' => $this->model('integral')->found_rows(), 
				'per_page' => 50, 
				'last_link' => '末页', 
				'first_link' => '首页', 
				'next_link' => '下一页 »', 
				'prev_link' => '« 上一页', 
				'anchor_class' => ' class="number"', 
				'cur_tag_open' => '<a class="number current">', 
				'cur_tag_close' => '</a>', 
				'direct_page' => TRUE
			))->create_links());
			
			foreach ($log AS $key => $val)
			{
				$parse_items[$val['id']] = array(
					'item_id' => $val['item_id'],
					'action' => $val['action']
				);
			}
		
			TPL::assign('integral_log', $log);
			TPL::assign('integral_log_detail', $this->model('integral')->parse_log_item($parse_items));
		}
		
		TPL::assign('user', $this->model('account')->get_user_info_by_uid($_GET['uid']));
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 402));
		
		TPL::output('admin/user_manage/integral_log');
	}
}