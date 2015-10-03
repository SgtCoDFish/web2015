<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
class Clothing extends CI_Controller {

    public function __construct() {
        parent::__construct ();

    }

	public function index() {
        $data = array();
        $data['campaigns'] = $this->_getActiveCampaigns()->result();

        $this->load->view ( 'clothing/clothing' , $data);
	}

    public function add(){
        $data = array();
        $data['name'] = '';
        $data['desc'] = '';
        $data['date'] = '';

        if($this->input->server ( 'REQUEST_METHOD' ) == 'POST'){

            $data['name'] = $this->input->post('name');
            $data['desc'] = $this->input->post('desc');
            $data['date'] = $this->input->post('date');

            if($this->input->post('name') == ''){
                $data['errormessage'] = 'Please fill out the campaign name';
            } else if($this->input->post('desc') == ''){
                $data['errormessage'] = 'Please fill out the campaign description';
            } else if($this->input->post('date') == ''){
                $data['errormessage'] = 'Please fill out the campaign expiry date';
            } else {
                $insert = $this->campaign_model->insert ( $data );
                if ($insert !== FALSE) {
                    $data ['message'] = "Successfully Added new campaign";

                    echo $data['date'];

                    //Clear the form
                    $data['name'] = '';
                    $data['desc'] = '';
                    $data['date'] = '';
                } else {
                    $data ['errormessage'] = "Sorry couldn't add new campaign: " . $this->db->_error_message ();
                }
            }
        }

        $this->load->view( 'clothing/add' , $data);
    }

    public function edit($campaign_id = -1){
        $data = array();
        $data['campaign_id'] = '';
        $data['name'] = '';
        $data['desc'] = '';
        $data['date'] = '';

        if($this->input->server ( 'REQUEST_METHOD' ) == 'POST'){
            $data['campaign_id'] = $this->input->post('campaign_id');
            $data['name'] = $this->input->post('name');
            $data['desc'] = $this->input->post('desc');
            $data['date'] = $this->input->post('date');

            if($this->input->post('name') == ''){
                $data['errormessage'] = 'Please fill out the campaign name';
            } else if($this->input->post('desc') == ''){
                $data['errormessage'] = 'Please fill out the campaign description';
            } else if($this->input->post('date') == ''){
                $data['errormessage'] = 'Please fill out the campaign expiry date';
            } else {
                $update = $this->campaign_model->update ( $data );
                if ($update !== FALSE) {
                    $data ['message'] = "Successfully updated campaign";
                } else {
                    $data ['errormessage'] = "Sorry couldn't update campaign: " . $this->db->_error_message ();
                }
            }
        } else {
            if($this->_getCampaign($campaign_id)->first_row() == NULL){
                $this->listview();
                return;
            } else {
                $campaign = $this->_getCampaign($campaign_id)->first_row();

                $data['campaign_id'] = $campaign->id;
                $data['name'] = $campaign->name;
                $data['desc'] = $campaign->description;
                $data['date'] = $campaign->expiry_date;
                $data['date'] = str_replace(' ', 'T', $data['date']);
            }
        }

        $this->load->view( 'clothing/edit' , $data);
    }

    public function listview($campaign_id = -1){
        if($campaign_id == -1 || ($this->_getCampaign($campaign_id)->first_row() == NULL)){
            $data = array();
            $data['active'] = $this->_getActiveCampaigns()->result();
            $data['expired'] = $this->_getExpiredCampaigns()->result();

            $this->load->view('clothing/listview', $data);
            return;
        }

        $data = array();
        $data['campaign'] = $this->_getCampaign($campaign_id)->first_row();
        $data['aggregate'] = $this->_getAggregatedList($campaign_id)->result();
        $data['orders'] = $this->_getList($campaign_id)->result();
        $this->load->view('clothing/listdetails', $data);
    }

    function _getAggregatedList($campaign_id = -1){
        /**
         * SELECT clothing_sizes.name, count(orders.id) FROM orders
        JOIN clothing_sizes ON clothing_sizes.id = orders.size_id
        WHERE orders.campaign_id = 0
        GROUP BY clothing_sizes.name

         */

        $this->db->select('clothing_sizes.name, clothing_sizes.description, count(orders.id) as total');
        $this->db->from('orders');
        $this->db->join('clothing_sizes', 'clothing_sizes.id = orders.size_id');
        $this->db->group_by('clothing_sizes.name');
        $this->db->where('orders.campaign_id', $campaign_id);
        return $this->db->get();
    }

    function _getList($campaign_id = -1){
        $this->db->select('users.fullname, clothing_sizes.name, orders.paid');
        $this->db->from('orders');
        $this->db->join('users', 'users.userid = orders.userid');
        $this->db->join('clothing_sizes', 'clothing_sizes.id = orders.size_id');
        $this->db->where('orders.campaign_id', $campaign_id);
        return $this->db->get();
    }

    public function details($campaign_id = -1){
        if($campaign_id == -1 && ($this->input->server ( 'REQUEST_METHOD' ) != 'POST')){
            //First visit to the page
            $data['campaigns'] = $this->_getActiveCampaigns()->result();
            $this->load->view ( 'clothing/clothing' , $data);
            return;
        }

        if (($this->_getCampaign($campaign_id)->first_row() == NULL) && ($this->input->server ( 'REQUEST_METHOD' ) != 'POST')) {
            //Catch a campaign id that doesn't exist
            $data['campaigns'] = $this->_getActiveCampaigns()->result();
            $this->load->view ( 'clothing/clothing' , $data);
            return;
        }

        $CI = & get_instance ();
        $email = $CI->session->userdata ( 'email' );
        $user_id = $this->_getUserID($email)->first_row()->userid;

        if($campaign_id == -1){
            $campaign_id = $this->input->post('campaign_id');
        }

        $data = array();
        $data['campaign'] = $this->_getCampaign($campaign_id)->first_row();
        $data['clothing_sizes'] = $this->_getSizes()->result();

        if ($this->input->server ( 'REQUEST_METHOD' ) == 'POST' && $this->input->post('size') != 0) {
            //Page loaded by a POST request
            $orderdata = array (
                'userid' => $user_id,
                'campaign_id' => $this->input->post('campaign_id'),
                'size_id' => $this->input->post('size')
            );

            if($this->_getUserChoice($campaign_id, $user_id)->first_row() == NULL){
                $insert = $this->order_model->insert ( $orderdata );
                if ($insert !== FALSE) {
                    $data ['message'] = "Successfully Added your selection";
                } else {
                    $data ['errormessage'] = "Sorry couldn't add your selection: " . $this->db->_error_message ();
                }
            } else {
                $updated = $this->order_model->update($orderdata);
                if ($updated !== FALSE) {
                    $data ['message'] = "Update Successful";
                } else {
                    $data ['errormessage'] = "Update Failed: " . $this->db->_error_message ();
                }
            }
        }

        $data['user_choice'] = $this->_getUserChoice($campaign_id, $user_id)->first_row();

        if($data['user_choice'] == NULL){
            $data['user_choice'] = new stdClass();
            $data['user_choice']->size_id = 0;
        }

        $this->load->view('clothing/details', $data);
    }

    private function _getCampaign($campaign_id){
        $this->db->select ( 'id, name, expiry_date, description' );
        $this->db->from ( 'campaigns' );
        $this->db->where( 'id' , $campaign_id);
        return $this->db->get ();
    }

    private function _getActiveCampaigns() {
        $this->db->select ( 'id, name, expiry_date, description' );
        $this->db->from ( 'campaigns' );
        $this->db->where( 'expiry_date >' , date('Y-m-d H:i:s'));
        return $this->db->get ();
    }

    private function _getExpiredCampaigns() {
        $this->db->select ( 'id, name, expiry_date, description' );
        $this->db->from ( 'campaigns' );
        $this->db->where( 'expiry_date <' , date('Y-m-d H:i:s'));
        return $this->db->get ();
    }

    private function _getUserID($email){
        $this->db->select('userid');
        $this->db->from('users');
        $this->db->where('email', $email);
        return $this->db->get();
    }

    private function _getSizes(){
        $this->db->select('id, name, description');
        $this->db->from('clothing_sizes');
        return $this->db->get();
    }

    private function _getUserChoice($campaign_id, $userid){
        $this->db->select('id, userid, campaign_id, size_id, paid');
        $this->db->from('orders');
        $this->db->where('campaign_id', $campaign_id);
        $this->db->where('userid', $userid);
        return $this->db->get();
    }
}
