<?php

class ControllerSellerAccountTransaction extends ControllerSellerAccount {
	public function index() {
		$seller_id = $this->customer->getId();
		
		/*
		 * Payments
		 */
		$page = isset($this->request->get['page']) ? $this->request->get['page'] : 1;

		$sort = array(
			'order_by'  => 'mpay.date_created',
			'order_way' => 'DESC',
			'offset' => ($page - 1) * $this->config->get('config_admin_limit'),
			'limit' => 20
		);

		$results = array_merge(
			$this->MsLoader->MsPayment->getPayments(array(
				'seller_id' => $seller_id
			), $sort)
		);

		foreach ($results as $result) {
			if ($result['payment_status'] == MsPayment::STATUS_UNPAID && $result['payment_type'] == MsPayment::TYPE_SALE) {
				continue;
			}
			
			$this->data['payments'][] = array_merge(
				$result,
				array(
					'amount_text' => $this->currency->format(abs($result['amount']),$result['currency_code']),
					'description' => (mb_strlen($result['mpay.description']) > 80 ? mb_substr($result['mpay.description'], 0, 80) . '...' : $result['mpay.description']),
					'date_created' => date($this->language->get('date_format_short'), strtotime($result['mpay.date_created'])),
					'date_paid' => $result['mpay.date_paid'] ? date($this->language->get('date_format_short'), strtotime($result['mpay.date_paid'])) : ''
				)
			);
		}

		/*
		 * Balance transactions
		 */
		$page = isset($this->request->get['page']) ? $this->request->get['page'] : 1;

		$sort = array(
			'order_by'  => 'date_created',
			'order_way' => 'DESC',
			'page' => $page,
			'limit' => 5
		);

		$balance_entries = $this->MsLoader->MsBalance->getSellerBalanceEntries($seller_id, $sort);
		
		foreach ($balance_entries as $entry) {
			$this->data['transactions'][] = array_merge(
				$entry,
				array(
					'amount' => $this->currency->format($entry['amount'], $this->config->get('config_currency')),
					'date_created' => date($this->language->get('date_format_short'), strtotime($entry['date_created']))
				)
			);
		}
		
		$seller_balance = $this->MsLoader->MsBalance->getSellerBalance($seller_id);
		$pending_funds = $this->MsLoader->MsBalance->getReservedSellerFunds($seller_id);
		$waiting_funds = $this->MsLoader->MsBalance->getWaitingSellerFunds($seller_id, 14);
		$balance_formatted = $this->currency->format($seller_balance,$this->config->get('config_currency'));
		
		$balance_reserved_formatted = $pending_funds > 0 ? sprintf($this->language->get('ms_account_balance_reserved_formatted'), $this->currency->format($pending_funds)) . ', ' : '';
		$balance_reserved_formatted .= $waiting_funds > 0 ? sprintf($this->language->get('ms_account_balance_waiting_formatted'), $this->currency->format($waiting_funds)) . ', ' : ''; 
		$balance_reserved_formatted = ($balance_reserved_formatted == '' ? '' : '(' . substr($balance_reserved_formatted, 0, -2) . ')');

		$this->data['ms_balance_formatted'] = $balance_formatted;
		$this->data['ms_reserved_formatted'] = $balance_reserved_formatted;

		$earnings = $this->MsLoader->MsSeller->getTotalEarnings($seller_id);

		$this->data['earnings'] = $this->currency->format($earnings, $this->config->get('config_currency'));
		
		$pagination = new Pagination();
		$pagination->total = $this->MsLoader->MsBalance->getTotalSellerBalanceEntries($seller_id);
		$pagination->page = $sort['page'];
		$pagination->limit = $sort['limit']; 
		$pagination->text = $this->language->get('text_pagination');
		$pagination->url = $this->url->link('seller/account-transaction', 'page={page}', 'SSL');
		
		$this->data['pagination'] = $pagination->render();
		
		
		
		
		
		
		
		$this->data['link_back'] = $this->url->link('account/account', '', 'SSL');
		
		$this->document->setTitle($this->language->get('ms_account_transactions_heading'));
		
		$this->data['breadcrumbs'] = $this->MsLoader->MsHelper->setBreadcrumbs(array(
			array(
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', '', 'SSL'),
			),
			array(
				'text' => $this->language->get('ms_account_dashboard_breadcrumbs'),
				'href' => $this->url->link('seller/account-dashboard', '', 'SSL'),
			),
			array(
				'text' => $this->language->get('ms_account_transactions_breadcrumbs'),
				'href' => $this->url->link('seller/account-transaction', '', 'SSL'),
			)
		));
		
		list($this->template, $this->children) = $this->MsLoader->MsHelper->loadTemplate('account-transaction');
		$this->response->setOutput($this->render());
	}
}

?>
