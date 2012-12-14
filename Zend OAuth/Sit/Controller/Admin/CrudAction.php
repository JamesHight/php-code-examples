<?php

/**
 * Provides extensible CRUD functionality for the admin area 
 *
 **/

class Sit_Controller_Admin_CrudAction extends Sit_Controller_BaseAction {

	// Types that can be displayed in the index
	const TYPE_CLASS = 'class';
	const TYPE_FIELD = 'field';
	const TYPE_LABEL = 'label';
	const TYPE_PASSWORD = 'password';
	const TYPE_DISABLED = 'disabled';
	const TYPE_PUBLIC = 'public';
	const TYPE_DATE = 'date';
	const TYPE_EDIT = 'edit';
	const TYPE_DELETE = 'delete';
	const TYPE_URL = 'url';

	/**
	 * Name of Doctrine model class
	 * @var string
	 */
	protected $crudClass;

	protected $crudName;

	/**
	 * views/scripts directory used for rendering
	 * @var string
	 */
	protected $crudViewScriptDir = 'crud';

	/**
	 * Array of $field=>labels to display in the indexAction
	 * @var array
	 */
	protected $crudIndexFields;

	/**
	 * Array of $field to search
	 * @var array
	 */
	protected $crudSearchFields;

	protected $session;	

	public function init() {
		parent::init();

		$this->_helper->layout->setLayout('admin');
		
		$this->crudName = end(explode('_', $this->crudClass));
	}

	public function preDispatch() {
		parent::preDispatch();

		// enforce SSL
		if($_SERVER['SERVER_PORT'] != '443') {
			$this->_redirect('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			return;
		}

		// Divide session from regular user session
		$this->session = new Zend_Session_Namespace('admin');
		if (!$this->session->admin) {
			$this->_redirect('/admin/index/login');
			return;
		}
		else {
			$this->view->admin = $this->session->admin;
		}

		$this->view->moduleName = $this->getRequest()->getModuleName();
	}

	/** 
	 * Disable caching 
	 **/
	public function postDispatch() {
		$this->noCache();
	}

	/**
	 * Index scaffold, view based on $crudIndexFields
	 **/

	public function indexAction() {

        $currentPage = $this->_getInt('id', 1);
        $count = $this->_getInt('count', 20);

        // Start building query for index list
    	$query = Doctrine_Query::create()
            ->from( $this->crudClass . ' crud' );

		if ($this->crudSearchFields) {
			$this->view->searchEnabled = true;
		}

		$search = $this->_getParam('search');
		// Add search parameters
		if ($search) {
			// normalize white space
			$search = preg_replace('/\s+/', ' ', $search);
			$this->view->search = $search;
			// split string on spaces
			$terms = explode(' ', $search);

			// loop through terms and sandwich them between "%" for a LIKE query
			$whereQuery = '';
			$whereParams = array();
			foreach ($terms as $term) {
				if ($whereQuery != '') {
					$whereQuery .= ' AND ';
				}
				$whereQuery .= '( ';
				$first = true;
				foreach ($this->crudSearchFields as $field) {
					if (!$first) {
						$whereQuery .= ' OR ';
					}
					else {
						$first = false;
					}
					$whereQuery .= 'crud.' . $field . ' LIKE ?';
					$whereParams[] = '%' . $term . '%';
				}
				$whereQuery .= ' )';
			}

			$query->where($whereQuery, $whereParams);
		}
        $query->orderby( 'crud.id' );

        // pass query to pager
		$this->view->doctrinePager = new Doctrine_Pager($query, $currentPage, $count);
		// execute query and pass items to view
		$this->view->doctrineItems = $this->view->doctrinePager->execute();
		$this->view->crudName = $this->crudName;
		$this->view->crudIndexFields = $this->crudIndexFields;

		$this->renderScript($this->getScriptDir() . '/index.phtml');
    }

    /**
     * Renders empty Zend form for item
     **/

	public function newAction() {
		// Get form for item
		$form = $this->getCrudForm($this->crudName);

		// validate post
    	if ($this->getRequest()->isPost()) {   		
    		if ($form->isValid($this->getRequest()->getParams())) {
    			// form is valid so save form data to database
    			$class = new $this->crudClass;
    			$form->updateClass($class);
				$this->_redirect('/' . $this->_getParam('module') . '/' . $this->_getParam('controller') . '/edit/id/' . $class->id);
    			//$this->_forward('edit', null, null, array('id'=>$class->id));
    			return;
    		}
    	}

    	$this->view->crudName = $this->crudName;
    	$this->view->form = $form;
    	$this->renderScript($this->getScriptDir() . '/new.phtml');
    }

    /**
     * Works similar to newAction except it populates the form with 
     * the item data
     **/
    public function editAction() {
    	$id = $this->_getInt('id');

    	if ($id) {
    		// Find item
    		$class = Doctrine::getTable($this->crudClass)->find($id);
    		if ($class) {
    			$form = $this->getCrudForm($this->crudName);
				
				// Validate and update database if post
		    	if ($this->getRequest()->isPost()) {
			    	if ($form->isValid($this->getRequest()->getParams())) {
			    		$form->updateClass($class);
			    	}
		    	}
		    	else {
		    		// first time, load data into form
		    		$form->loadClass($class);
		    	}

		    	// change form submit label from Create
		    	$form->getElement('submit')->setLabel('Update');
		    	$this->view->crudName = $this->crudName;
		    	$this->view->form = $form;
    		}
    	}

    	$this->renderScript($this->getScriptDir() . '/edit.phtml');
    }

    /**
     * Simple delete action, delete prompt handled client side
     **/

    public function deleteAction() {
    	$id = $this->_getInt('id');

    	if ($id) {
    		$class = Doctrine::getTable($this->crudClass)->find($id);
    		if ($class) {
    			$class->delete();				
				if (isset($_SERVER["HTTP_REFERER"]) && strlen($_SERVER["HTTP_REFERER"])) {
					$this->_redirect($_SERVER["HTTP_REFERER"]);
					return;
				}
    		}
    	}

		$this->_redirect('/' . $this->_getParam('module') . '/' . $this->_getParam('controller'));
    }

    /**
     * Update user password using bcrypt
     * Password encryption handled by form when saving to database
     **/

	public function passwordAction() {

        $form = $this->getCrudForm('Password');

		if ($this->getRequest()->isPost()) {
    		if ($form->isValid($this->getRequest()->getParams())) {
        		$id = $this->_getParam('id');
        		if ($id) {
        			$class = Doctrine::getTable($this->crudClass)->find($id);
        			if ($class) {
        				$form->updateClass($class);
        				$this->_forward('index');
        				return;
        			}
        		}
    		}
    	}

        $this->view->form = $form;
        $this->renderScript($this->getScriptDir() . '/password.phtml');
    }

    /**
     * Toggle the disabled flag on a user
     **/

	public function disabledAction() {
        $this->disableLayout();

		if (array_key_exists('disabled', $this->crudIndexFields)) {

			$id = $this->_getParam('id');
	        if ($id) {
				if (array_key_exists('disabled', $this->crudIndexFields)) {
					$user = Doctrine::getTable($this->crudClass)->find($id);
					$user->disabled = !$user->disabled;
					$user->save();

					$this->view->user = $user;
				}
	        }
		}

		$this->renderScript($this->getScriptDir() . '/disabled.phtml');
    }

    /**
     * Helper function, get the default script directory 
     * or the override value set in the controller
     **/

    protected function getScriptDir() {
    	if ($this->crudViewScriptDir == 'crud') {
    		$this->view->setScriptPath(realpath(APPLICATION_PATH . '/../library/Sit/View/Scripts'));
    	}
    	return $this->crudViewScriptDir;
    }

    /**
     * Get a new instance of the item form
     **/

    protected function getCrudForm($type) {
    	$formClass = ucfirst($this->getRequest()->getModuleName()) . '_Form_' . $type;
    	$form = new $formClass;
    	return $form;
    }
	
}
