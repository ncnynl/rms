<?php
App::uses('File', 'Utility');

/**
 * Site Settings Controller
 *
 * Site settings include options such as the site title, copyright message, and Google Analytics tracking. These
 * settings can only be edited by an admin.
 *
 * @author		Russell Toris - rctoris@wpi.edu
 * @copyright	2014 Worcester Polytechnic Institute
 * @link		https://github.com/WPI-RAIL/rms
 * @since		RMS v 2.0.0
 * @version		2.0.0
 * @package		app.Controller
 */
class SettingsController extends AppController {

	/**
	 * The used helpers for the controller.
	 *
	 * @var array
	 */
	public $helpers = array('Html', 'Form');

	/**
	 * The used components for the controller.
	 *
	 * @var array
	 */
	public $components = array('Session', 'Auth' => array('authorize' => 'Controller'));

	/**
	 * Define the actions which can be used by any user, authorized or not.
	 */
	public function beforeFilter() {
		// only allow unauthenticated viewing of a single page
		parent::beforeFilter();
		$this->Auth->allow('logo');
	}

	/**
	 * The admin index action lists all settings. This allows the admin to edit the settings.
	 */
	public function admin_index() {
		// grab the only entry
		$this->set('setting', $this->Setting->findById(Setting::$DEFAULT_ID));
	}

	/**
	 * The admin edit action. This allows the admin to edit the site settings.
	 */
	public function admin_edit() {
		// only work for PUT requests
		if ($this->request->is(array('page', 'put'))) {
			// set the ID
			$this->Page->id = Setting::$DEFAULT_ID;
			// set the current timestamp for modification
			$this->Page->data['Page']['modified'] = date('Y-m-d H:i:s');
			// attempt to save the entry
			if ($this->Page->save($this->request->data)) {
				$this->Session->setFlash('The page has been updated.');
				return $this->redirect(array('action' => 'index'));
			}
			$this->Session->setFlash('Unable to update the page.');
		}

		// store the entry data if it was not a PUT request
		if (!$this->request->data) {
			// grab the only entry
			$setting = $this->Setting->findById(Setting::$DEFAULT_ID);
			$this->request->data = $setting;
		}

		$this->set('title_for_layout', 'Edit Site Settings');
	}

	/**
	 * The admin edit action. This allows the admin to edit the site settings.
	 */
	public function admin_uploadLogo() {
		// only work for POST requests
		if ($this->request->is('post')) {
			// check for upload errors
			switch ($this->request->data['Setting']['logo']['error']) {
				case UPLOAD_ERR_OK:
					// load the file
					$file = new File($this->request->data['Setting']['logo']['tmp_name']);
					$png = $file->read();

					// check if we need to convert
					if (exif_imagetype($this->request->data['Setting']['logo']['tmp_name']) !== IMAGETYPE_PNG) {
						// convert to PNG
						$image = imagecreatefromstring($png);
						// read the image buffer
						ob_start();
						imagepng($image);
						$png = ob_get_contents();
						ob_end_clean();
					}

					// update the existing entry
					$this->Setting->read(null, Setting::$DEFAULT_ID);
					// set the current timestamp for modification
					$this->Setting->set('modified', date('Y-m-d H:i:s'));
					// store the raw image data
					$this->Setting->set('logo', $png);
					// attempt to save the entry
					if ($this->Setting->save()) {
						$this->Session->setFlash('The logo has been saved.');
						return $this->redirect(array('action' => 'index'));
					}
					$this->Session->setFlash('Unable to update the logo.');
					break;
				case UPLOAD_ERR_INI_SIZE:
					$this->Session->setFlash('File upload too large. Change server settings or select a new file.');
					break;
				case UPLOAD_ERR_NO_FILE:
					$this->Session->setFlash('No file uploaded.');
					break;
				default:
					$this->Session->setFlash('Could not upload image.');
					break;
			}
		}

		$this->set('title_for_layout', 'Upload Logo');
	}

	/**
	 * Display the site logo. This will display a PNG image, not an HTML page.
	 */
	public function logo() {
		// grab the only entry
		$setting = $this->set('setting', $this->Setting->findById(Setting::$DEFAULT_ID));

		// display a PNG image
		$this->layout = false;
		$this->response->type('png');
	}
}
