<?php 

// Ravilr for opencart-russia.ru === v.030323 ===
// Отличие от оригинала:
// 1. Не делает запросы в БД, если не включен в админке ЧПУ
// 2. Если выключен или не находит чпу, то перекидывает на not_found
// 3. Если находит ключ, но оно не входит в чпу, то not_found (например, прямой доступ к чпу товара, без /product/)
// 4. Удаляем лишний слеш при размещении в субдиректории.

namespace Opencart\Catalog\Controller\Startup;
use \Opencart\System\Helper as Helper;
class SeoUrl extends \Opencart\System\Engine\Controller {
	public function index(): void {

		if ($this->config->get('config_seo_url')) {
			
			// Add rewrite to URL class
			$this->url->addRewrite($this);

			$this->load->model('design/seo_url');

			// Decode URL
			if (isset($this->request->get['_route_'])) {
				$parts = explode('/', $this->request->get['_route_']);

				// remove any empty arrays from trailing
				if (Helper\Utf8\strlen(end($parts)) == 0) {
					array_pop($parts);
				}
				
				$count_path = count($parts);

				foreach ($parts as $part) {
					$seo_url_info = $this->model_design_seo_url->getSeoUrlByKeyword($part);

					if ($seo_url_info) {
						if ($seo_url_info['key'] != 'route') {
							if ($count_path > 1) {
								$this->request->get[$seo_url_info['key']] = html_entity_decode($seo_url_info['value'], ENT_QUOTES, 'UTF-8');		
							} else {
								$this->request->get['route'] = 'error/not_found';
							}
						} else {
							$this->request->get[$seo_url_info['key']] = html_entity_decode($seo_url_info['value'], ENT_QUOTES, 'UTF-8');	
						}
						
					} else {
						$this->request->get['route'] = 'error/not_found';
					}
				}
			}
		} else {
			if (isset($this->request->get['_route_'])) {
				$this->request->get['route'] = 'error/not_found';
			}
		}
	}

	public function rewrite(string $link): string {
		$url_info = parse_url(str_replace('&amp;', '&', $link));

		// Build the url
		$url = '';

		if ($url_info['scheme']) {
			$url .= $url_info['scheme'];
		}

		$url .= '://';

		if ($url_info['host']) {
			$url .= $url_info['host'];
		}

		if (isset($url_info['port'])) {
			$url .= ':' . $url_info['port'];
		}

		parse_str($url_info['query'], $query);

		// Start changing the URL query into a path
		$paths = [];

		// Parse the query into its separate parts
		$parts = explode('&', $url_info['query']);

		foreach ($parts as $part) {
			[$key, $value] = explode('=', $part);

			$result = $this->model_design_seo_url->getSeoUrlByKeyValue($key, $value);

			if ($result) {
				$paths[] = $result;

				unset($query[$key]);
			}
		}

		$sort_order = [];

		foreach ($paths as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}

		array_multisort($sort_order, SORT_ASC, $paths);

		// Build the path
		$url .= str_replace('/index.php', '', $url_info['path']);

		$keyword = '';

		foreach ($paths as $result) {
			$keyword .= '/' . $result['keyword'];
		}
		
		$url .= str_replace('//', '/', $keyword);
		

		// Rebuild the URL query
		if ($query) {
			$url .= '?' . str_replace(['%2F', '%7C'], ['/', '|'], http_build_query($query));
		}

		return $url;
	}
}