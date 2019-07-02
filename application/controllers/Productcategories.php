<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Productcategories extends REST_Controller {
  
  public $_table;
  public $_col_primary;
  public $_col_reguler;
  
  public function __construct()
  {
    parent::__construct();
    $this->_table = 'product_category';
    $this->_col_primary = 'pc_id';
    $this->_col_reguler = array(
      'pc_name',
      'pc_slug',
      'pc_desc',
      'meta_keyword',
      'meta_description',
    );
  }
  
  public function select_get() {
    $sql = "SELECT `pc_id` AS `value`, `pc_name` AS `label` FROM ".$this->_table." WHERE `status` = 1 ORDER BY 1";
    $data = $this->db->query($sql)->result_array(); 
    
    $this->response(array('data' => $data, 'message' => 'successfully'), REST_Controller::HTTP_OK);
  }
  
  public function index_get($id=0) 
  {
    $id = intval($id);
    if ($id) {
      $sql = "SELECT * FROM ".$this->_table." WHERE `".$this->_col_primary."` = $id AND `status` = 1 LIMIT 1";
      $data = $this->db->query($sql)->row(); 
      if ($data) {
        $this->response(array('data' => $data, 'message' => 'successfully'), REST_Controller::HTTP_OK);
      }
    }
    else {
      $sql = "SELECT A.*, IF(`status`=1,'Aktif','Tidak Aktif') AS `status_txt` FROM ".$this->_table." A WHERE 1 ORDER BY A.`pc_id` DESC";
      $data = $this->db->query($sql)->result_array(); 
      
      $this->response(array('data' => $data, 'message' => 'successfully'), REST_Controller::HTTP_OK);
    }
    
    $this->response(array('data' => null, 'message' => 'error'), REST_Controller::HTTP_BAD_REQUEST);
  }
  
  public function index_post()
  {
    $data = array();
    foreach ($this->_col_reguler as $col) {
      if ($col == 'pc_slug') {
        $data[$col] = $this->slugify($this->post('pc_name'));
      }
      else {
        $data[$col] = $this->post($col);
      }
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
        if ($col == 'pc_slug') {
          continue;
        }
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
  
  private function slugify($str) 
  {
    $search = array('Ș', 'Ț', 'ş', 'ţ', 'Ş', 'Ţ', 'ș', 'ț', 'î', 'â', 'ă', 'Î', 'Â', 'Ă', 'ë', 'Ë');
    $replace = array('s', 't', 's', 't', 's', 't', 's', 't', 'i', 'a', 'a', 'i', 'a', 'a', 'e', 'E');
    $str = str_ireplace($search, $replace, strtolower(trim($str)));
    $str = preg_replace('/[^\w\d\-\ ]/', '', $str);
    $str = str_replace(' ', '-', $str);
    
    return preg_replace('/\-{2,}/', '-', $str);
  }
}
