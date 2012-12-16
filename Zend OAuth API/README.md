Example OAuth RPC API controller for retrieving items by id(s)
--------------------------------------------------------------

This is an OAuth protected API controller. See [Sit_Controller_UserAction](https://github.com/JamesHight/php-code-examples/blob/master/Zend%20OAuth/Sit/Controller/UserAction.php) and [Sit_Controller_BaseAction](https://github.com/JamesHight/php-code-examples/blob/master/Zend%20OAuth/Sit/Controller/BaseAction.php) for more detail.

	class ItemController extends Sit_Controller_UserAction {

		public function getAction() {
			$id = $this->_getParam('id');
			$ids = $this->_getParam('ids');
			// admin flag
			$admin = $this->_getBool('admin');
			
			// Get id
			if ($id) {
				$data = Core_Model_Mongo_Item::getDataById($id, $this->user, $admin);
			}
			// Get multiple ids
			else if ($ids) {
				$ids = Sit_Json::decode($ids);
				$data = Core_Model_Mongo_Item::getDataByIds($ids, $this->user, $admin);
			}
			else {
				// Missing parameter
				throw new Sit_Exception_Api('You must provide one of the following fields: id or ids', 
							Sit_Errors::PARAMETER);
			}

			// no data found return null
			if (!$data) {
				$data = null;
			}

			// Send JSON response
			$this->response($data);
		}
		
		public function saveAction() {
			$data = $this->_getRequiredParam('data');
			$data = Sit_Json::decode($data);
			// admin flag
			$admin = $this->_getBool('admin');
					
			// Item already exists in db?
			if (isset($data['id'])) {
				$model = false;

				// Check for third party prefix on id
				// Don't allow anyone to update third party items
				if (substr($data['id'], 0, 2) != 'fs')
					$model = Core_Model_Mongo_Item::getById($data['id'], $this->user);

				if (!$model) {
					throw new Sit_Exception_Api('You do not have permission to to save that item.', 
								Sit_Errors::PERMISSION_DENIED);
				}
			}
			else {
				// Create new item

				$model = new Core_Model_Mongo_Item();
				// set item ownership
				$model->user_id = $this->user->id;
				$model->site_id = $this->application->site_id;
				// Set data before save to trigger validation
				$model->setData($data, $admin);
				// save item to get id
				$model->save();
				// assign id to internal index
				$data['id'] = $model->_id;

				// code below will update and save id change
			}
			
			// set and validate data
			$model->setData($data, $admin);
			// save changes
			$model->save();
			
			// return id to client
			$this->response(array('id'=>$data['id']));
		}
	}

