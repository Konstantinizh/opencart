<?php
namespace Opencart\Catalog\Controller\Common;
class Search extends \Opencart\System\Engine\Controller {
	public function index(): string {
		$this->load->language('common/search');

		$data['text_search'] = $this->language->get('text_search');

		if (isset($this->request->get['search'])) {
			
			if ($this->config->get('config_seo_url')) {
				
				$req = $this->request->get;	
				unset($req['route'], $req['_route_'], $req['language']);
				$arg = '?' . http_build_query($req);
				
				if ($this->request->get['_route_'] != 'search' && $this->request->get['route'] != 'product/product' ) {
					$this->response->redirect($this->url->link('product/search', 'language=' . $this->config->get('config_language')) . $arg );
				}
			}

			$data['search'] = $this->request->get['search'];
			
		} else {
			$data['search'] = '';
		}

		$data['language'] = $this->config->get('config_language');

		return $this->load->view('common/search', $data);
	}
}