<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Socialmedia extends REST_Controller {
  
  public $_table;
  public $_col_primary;
  public $_col_reguler;
  
  public function __construct()
  {
    parent::__construct();
    $this->_table = 'social_media';
    $this->_col_primary = 'sc_id';
    $this->_col_reguler = array(
      'sc_name',
      'sc_url',
      'status'
    );
  }
  
  public function index_get($id=0) 
  {
    $id = intval($id);
    if ($id) {
      $sql = "SELECT * FROM ".$this->_table." WHERE `".$this->_col_primary."` = $id LIMIT 1";
      $data = $this->db->query($sql)->row(); 
      if ($data) {
        $this->response(array('data' => $data, 'message' => 'successfully'), REST_Controller::HTTP_OK);
      }
    }
    else {
      $sql = "SELECT *, IF(`status`=1,'Aktif','Tidak Aktif') AS `status_txt` FROM ".$this->_table." WHERE 1 ORDER BY `".$this->_col_primary."` DESC";
      $data = $this->db->query($sql)->result_array(); 
      
      $this->response(array('data' => $data, 'message' => 'successfully'), REST_Controller::HTTP_OK);
    }
    
    $this->response(array('data' => null, 'message' => 'error'), REST_Controller::HTTP_BAD_REQUEST);
  }
  
  public function index_post()
  {
    $data = array();
    foreach ($this->_col_reguler as $col) {
      $data[$col] = $this->post($col);
    }

    if ($this->db->insert($this->_table, $data)) {
      $id = $this->db->insert_id();
      $this->response(array('data' => $id, 'message' => 'successfully'), REST_Controller::HTTP_OK);
    }
    
    $this->response(array('data' => null, 'message' => 'error'), REST_Controller::HTTP_BAD_REQUEST);
  }
  
  public function index_put($id=0)
  {
    $id = intval($id);
    if ($id) {
      $data = array();
      foreach ($this->_col_reguler as $col) {
        $data[$col] = $this->put($col);
      }
      $this->db->where($this->_col_primary, $id);
      if ($this->db->update($this->_table, $data)) {
        $this->response(array('data' => $id, 'message' => 'successfully'), REST_Controller::HTTP_OK);
      }
    }
    
    $this->response(array('data' => null, 'message' => 'error'), REST_Controller::HTTP_BAD_REQUEST);
  }
  
  public function index_delete($id=0)
  { 
    $id = intval($id);
    if ($id) {
      $data = array(
        'status' => 0
      );
      $this->db->where($this->_col_primary, $id);
      @$this->db->update($this->_table, $data);
      
      $this->response(array('data' => $id, 'message' => 'successfully'), REST_Controller::HTTP_OK);
    }
    
    $this->response(array('data' => null, 'message' => 'error'), REST_Controller::HTTP_BAD_REQUEST);
  }
}
