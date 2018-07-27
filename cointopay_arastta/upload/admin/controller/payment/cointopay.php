<?php
/**
 * @package		Arastta eCommerce
 * @copyright	Copyright (C) 2015 Arastta Association. All rights reserved. (arastta.org)
 * @license		GNU General Public License version 3; see LICENSE.txt
 */

class ControllerPaymentCointopay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/cointopay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('cointopay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

            if (isset($this->request->post['button']) and $this->request->post['button'] == 'save') {
                $route = $this->request->get['route'];
                $module_id = '';
                if (isset($this->request->get['module_id'])) {
                    $module_id = '&module_id=' . $this->request->get['module_id'];
                }
	            elseif ($this->db->getLastId()) {
		            $module_id = '&module_id=' . $this->db->getLastId();
	            }
                $this->response->redirect($this->url->link($route, 'token=' . $this->session->data['token'] . $module_id, 'SSL'));
            }
            
			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['entry_account'] = $this->language->get('entry_account');
		$data['entry_secret'] = $this->language->get('entry_secret');
		

		$data['button_save'] = $this->language->get('button_save');
        $data['button_savenew'] = $this->language->get('button_savenew');
        $data['button_saveclose'] = $this->language->get('button_saveclose');		
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['account'])) {
			$data['error_account'] = $this->error['account'];
		} else {
			$data['error_account'] = '';
		}

		if (isset($this->error['secret'])) {
			$data['error_secret'] = $this->error['secret'];
		} else {
			$data['error_secret'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('payment/cointopay', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('payment/cointopay', 'token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['cointopay_account'])) {
			$data['cointopay_account'] = $this->request->post['cointopay_account'];
		} else {
			$data['cointopay_account'] = $this->config->get('cointopay_account');
		}

		if (isset($this->request->post['cointopay_secret'])) {
			$data['cointopay_secret'] = $this->request->post['cointopay_secret'];
		} else {
			$data['cointopay_secret'] = $this->config->get('cointopay_secret');
		}
		if (isset($this->request->post['cointopay_api_key'])) {
			$data['cointopay_api_key'] = $this->request->post['cointopay_api_key'];
		} else {
			$data['cointopay_api_key'] = $this->config->get('cointopay_api_key');
		}

		if (isset($this->request->post['cointopay_display'])) {
			$data['cointopay_display'] = $this->request->post['cointopay_display'];
		} else {
			$data['cointopay_display'] = $this->config->get('cointopay_display');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('payment/cointopay.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/cointopay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['cointopay_account']) {
			$this->error['account'] = $this->language->get('error_account');
		}

		if (!$this->request->post['cointopay_secret']) {
			$this->error['secret'] = $this->language->get('error_secret');
		}

		return !$this->error;
	}
}