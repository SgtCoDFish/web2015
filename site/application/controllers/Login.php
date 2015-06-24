<?php
class Login extends CI_Controller {
	public function __construct() {
		parent::__construct ();
		
		$this->load->model ( 'user_model' );
	}
	
	public function index() {
		$this->load->view ( 'login.php' );
	}
	
	public function login_process() {
		$rules = array (
				array (
						'field' => 'email',
						'label' => 'E-Mail',
						'rules' => 'required|valid_email|trim' 
				),
				
				array (
						'field' => 'password',
						'label' => 'Password',
						'rules' => 'required|min_length[5]' 
				) 
		);
		
		$this->form_validation->set_rules ( $rules );
		
		if ($this->form_validation->run () === FALSE) {
			$this->load->view ( 'login.php' );
		} else {
			// verify
			$userdata = array(
				'email' => $this->input->post('email'),
				'password' => $this->input->post('password')
			);
			
			if($this->user_model->login_verify($userdata)) {
				// logged in successfully
				$this->set_login_session($userdata['email']);
				$this->load->view('welcome_message.php');
			} else {
				$this->load->view('login.php', array('message' => "Login failed; did you enter the correct e-mail and password?"));
			}
		}
	}
	
	public function register_process() {
		$rules = array (
				array (
						'field' => 'reg_email',
						'label' => 'e-mail',
						'rules' => 'required|valid_email|trim|is_unique[users.email]' 
				),
				array (
						'field' => 'reg_fname',
						'label' => 'first name',
						'rules' => 'required|trim'
				),
				array(
					'field' => 'reg_lname',
					'label' => 'last name',
					'rules' => 'required|trim'
				),
				array (
						'field' => 'reg_password1',
						'label' => 'password',
						'rules' => 'required|min_length[5]' 
				),
				array (
						'field' => 'reg_password2',
						'label' => 'password confirmation',
						'rules' => 'required|min_length[5]|matches[reg_password1]'
				)
		);
		
		$this->form_validation->set_rules ( $rules );
		
		if ($this->form_validation->run () === FALSE) {
			$this->load->view ( 'login.php' );
		} else {
			// verify
			$userdata = array(
					'email' => $this->input->post('reg_email'),
					'fname' => $this->input->post('reg_fname'),
					'lname' => $this->input->post('reg_lname'),
					'password' => $this->input->post('reg_password1')
			);
			
			$result = $this->user_model->insert($userdata);
			
			if($result === TRUE) {
				$this->load->view('welcome_message.php');
			} else {
				$userdata['message'] = "Account could not be created at this time. Please try again later.";
				$this->set_login_session($userdata['email']);
				$this->load->view('login.php', $userdata);
			}
		}
	}
	
	public function logout() {
		$this->session->unset_userdata('username');
		$this->session->unset_userdata('email');
		$this->session->unset_userdata('logged_in');
		$this->load->view('welcome_message.php', array('notification_message' => 'Logout successful.'));
	}
	
	private function set_login_session($email) {
		$sessdata = array(
				'username' => explode('@', $email)[0],
				'email' => $email,
				'logged_in' => TRUE
		);
		
		$this->session->set_userdata($sessdata);
	}
}