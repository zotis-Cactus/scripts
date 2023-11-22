<?php
/*
The function names are chosen carefully to ensure clarity and usability.
There is some comments to explain more if it needs.
Have a nice Day . :)
- Cactus 
*/
class ControllerApiProduct extends Controller
{
	public function index()
	{
		$request_method = $_SERVER["REQUEST_METHOD"];
		switch ($request_method) {
			case 'GET':
				echo "GET";
				break;
			case 'POST':

				$this->load->model('api/cactus');

				$product = json_decode(file_get_contents('php://input'), true);

				if (isset($product['token'])) {
					$this->load->model('api/cactus');
					$token_query = $this->model_api_cactus->check_token($product['token']);
					if (!empty($token_query)) {
						if (isset($product)) {
							$colorakia = $this->getColorToArray($product);

							foreach ($colorakia as $color) {
								$this->setProduct($color, $product);
							}
							$response['success'] = array(
								'status' => "success",
								'message' => "Products Successfully Updated or Created. " 
							);
						} else {
							$response['error'] = array(
								'status' => "error",
								'message' => 'Content you gave is empty'
							);
						}
					} else {
						$response = array(
							'status' => "error",
							'message' => 'token_invalid'
						);
					}
				} else {
					$response = array(
						'status' => "error",
						'message' => 'token_invalid'
					);
				}

				header('Content-Type: application/json');
				echo json_encode($response);
				break;
		}
	}

	// INSERT product
	// IF EXIST UPDATE PRODUCT AND DESCRIPTION
	function setProduct($color, $product)
	{
		$model = $product["code"] . "-" . $color;
		$query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE model = '" . $this->db->escape($model) . "'");

		if ($query->num_rows) {
			$productId = $query->row['product_id'];

			$this->db->query("UPDATE " . DB_PREFIX . "product SET 
				price = '" . (float) $product['retailPrice_gr'] . "',
				status = 1, 
				MTRL ='" . $product['mtrl'] . "',
				erp_color_id = '" . $product['code'] . "',
				date_added = NOW()
				WHERE product_id = '" . (int) $productId . "'");

			$productId = $this->db->getLastId();

			$this->updateProductDescription($productId, 1, $product['name_eu'], $product['composition'], $product['seoFriendlyUrl_eu'], $product['metaKeywords_eu'], $product['metaDescription_eu']);
			$this->updateProductDescription($productId, 2, $product['name_gr'], $product['composition'], $product['seoFriendlyUrl_gr'], $product['metaKeywords_gr'], $product['metaDescription_gr']);

			$result = $this->db->query("SELECT * FROM " . DB_PREFIX . "product
			WHERE product_id = '" . $productId . "'");
			
		} else {

			$result =$this->db->query("INSERT INTO " . DB_PREFIX . "product SET 
			model = '" . $product["code"] . "-" . $color . "', 
			price = '" . (float) $product['retailPrice_gr'] . "', 
			status = 1, 
			MTRL='" . $product['mtrl'] . "',
			erp_color_id ='" . $product['code'] . "',
			date_added = NOW()");

			$productId = $this->db->getLastId();

			$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET store_id = 0 , product_id = '" . $productId . "'");
			$this->insertProductDescription($productId, 1, $product['name_eu'], $product['composition'], $product['seoFriendlyUrl_eu'], $product['metaKeywords_eu'], $product['metaDescription_eu']);
			$this->insertProductDescription($productId, 2, $product['name_gr'], $product['composition'], $product['seoFriendlyUrl_gr'], $product['metaKeywords_gr'], $product['metaDescription_gr']);

			$sizeOptionId = $this->getOptionId('Size');

			foreach ($product['mtrSubstitutes'] as $variant) {
				if ($variant['colorCode'] == $color) {
					$this->insertProductOption($productId, $sizeOptionId, $variant, $productId);
				}
			}
		}
		
	

		return $result;
	}

	// MAKE the colors to array 
	function getColorToArray($product)
	{
		$colorakia = array();
		foreach ($product['mtrSubstitutes'] as $variant) {
			if (empty($colorakia)) {
				array_push($colorakia, $variant['colorCode']);
			} elseif (!in_array($variant['colorCode'], $colorakia)) {
				array_push($colorakia, $variant['colorCode']);
			}
		}
		return $colorakia;
	}

	//get the last id from options if exist else it create it
	function getOptionId($optionName)
	{
		$query = $this->db->query("SELECT option_id FROM " . DB_PREFIX . "option_description WHERE name = '" . $optionName . "' LIMIT 1");

		if ($query->num_rows) {
			return $query->row['option_id'];
		} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . "option SET type = 'select', sort_order = 0");
			$optionId = $this->db->getLastId();

			$languages = $this->getLanguages();
			foreach ($languages as $language) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET 
                option_id = '" . (int) $optionId . "',
                language_id = '" . (int) $language['language_id'] . "',
                name = '" . $optionName . "'");
			}

			return $optionId;
		}
	}

	// retrive the language
	private function getLanguages()
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "language");
		return $query->rows;
	}

	// insert value at option
	function addOptionValue($optionId, $valueName)
	{
		$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET 
			option_id = '" . (int) $optionId . "', 
			image = '', 
			sort_order = 0");
		$optionValueId = $this->db->getLastId();

		$languages = $this->getLanguages();
		foreach ($languages as $language) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET 
				option_value_id = '" . (int) $optionValueId . "',
				language_id = '" . (int) $language['language_id'] . "',
				option_id = '" . (int) $optionId . "',
				name = '" . $this->db->escape($valueName) . "'");
		}
		return $optionValueId;
	}

	function insertProductOption($productId, $optionId, $variant, $product_id)
	{
		$query = $this->db->query("SELECT product_option_id FROM " . DB_PREFIX . "product_option 
        WHERE product_id = '" . (int) $productId . "' AND option_id = '" . (int) $optionId . "' LIMIT 1");

		if ($query->num_rows) {
			$productOptionId = $query->row['product_option_id'];
		} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET 
				product_id = '" . (int) $productId . "',
				option_id = '" . (int) $optionId . "',
				value = '" . "',
				required = 1");
			$productOptionId = $this->db->getLastId();
		}

		$optionValueId = $this->addOptionValue($optionId, $variant['size']);

		$query = $this->db->query("SELECT product_option_value_id FROM " . DB_PREFIX . "product_option_value 
			WHERE product_option_id = '" . (int) $productOptionId . "' AND option_value_id = '" . (int) $optionValueId . "' LIMIT 1");

		if (!$query->num_rows) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET 
				product_option_id = '" . (int) $productOptionId . "',
				product_id = '" . (int) $productId . "',
				option_id = '" . (int) $variant['mtrSubCode'] . "',
				barcode = '" . (int) $optionId . "',
				option_value_id = '" . (int) $optionValueId . "'");
		}
	}

	function linkProductToOption($productId, $optionId, $optionValueId)
	{
		$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET 
			product_id = '" . (int) $productId . "', 
			option_id = '" . (int) $optionId . "', 
			value = '', 
			required = 1");
	}

	function insertProductDescription($productId, $languageId, $name, $description, $seo, $metaword, $metadescription)
	{
		$name = $this->db->escape($name);
		$description = $this->db->escape($description);
		$seo = $this->db->escape($seo);
		$metaword = $this->db->escape($metaword);
		$metadescription = $this->db->escape($metadescription);

		$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET 
        product_id = '" . (int) $productId . "',
        language_id = '" . (int) $languageId . "',
        name = '" . $name . "',
        description = '" . $description . "',
        meta_title= '" . $seo . "',
        meta_keyword = '" . $metaword . "',
        meta_description = '" . $metadescription . "'");
	}

	function updateProductDescription($productId, $languageId, $name, $description, $seo, $metaword, $metadescription)
	{
		$name = $this->db->escape($name);
		$description = $this->db->escape($description);
		$seo = $this->db->escape($seo);
		$metaword = $this->db->escape($metaword);
		$metadescription = $this->db->escape($metadescription);

		$this->db->query("UPDATE " . DB_PREFIX . "product_description SET 
        name = '" . $name . "',
        description = '" . $description . "',
        meta_title = '" . $seo . "',
        meta_keyword = '" . $metaword . "',
        meta_description = '" . $metadescription . "'
        WHERE product_id = '" . (int) $productId . "' AND language_id = '" . (int) $languageId . "'");
	}

}
