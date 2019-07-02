<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '/core/Onlyus.php';

class Users extends Onlyus {
  
  public $_table;
  public $_col_primary;
  public $_col_reguler;
  
  public function __construct()
  {
    parent::__construct();
    $this->_table = 'user_bankdata';
    $this->_col_primary = 'user_id';
    $this->_col_reguler = array(
      'user_fullname',
      'user_photo',
      'user_slug',
      'user_email',
      'user_password',
      'user_peneliti',
      'user_type',
      'ps_id',
      'pkr_id',
      'pkt_id',
    );
  }
  
  public function satker_available_get($id=0) {
    $id = intval($id);
    if ($id) {
      $sql = "SELECT A.`user_id`, A.`user_fullname`, A.`user_email` FROM ".$this->_table." A LEFT JOIN (SELECT Y.`user_id` FROM satuan_kerja X, satuan_kerja_anggota Y WHERE X.`sk_id` = Y.`sk_id` AND X.`sk_id` != $id AND X.`status` = 1) AS B ON A.`user_id` = B.`user_id` WHERE A.`user_type` = 1 AND A.`status` = 1 AND B.`user_id` IS NULL ORDER BY 2";
    }
    else {
      $sql = "SELECT A.`user_id`, A.`user_fullname`, A.`user_email` FROM ".$this->_table." A LEFT JOIN (SELECT Y.`user_id` FROM satuan_kerja X, satuan_kerja_anggota Y WHERE X.`sk_id` = Y.`sk_id` AND X.`status` = 1) AS B ON A.`user_id` = B.`user_id` WHERE A.`user_type` = 1 AND A.`status` = 1 AND B.`user_id` IS NULL ORDER BY 2";
    }
    $data = $this->db->query($sql)->result_array();
    
    $this->response(array('data' => $data, 'message' => 'successfully'), REST_Controller::HTTP_OK);
  }
  
  public function kegiatan_get($sk_id=0) 
  {
    $sk_id = intval($sk_id);
    if ($sk_id) {
      $sql = "SELECT A.`user_id`, A.`user_fullname`, B.`sk_name` AS `user_email` FROM ".$this->_table." A JOIN (SELECT Y.`user_id`, X.`sk_name` FROM satuan_kerja X, satuan_kerja_anggota Y WHERE X.`sk_id` = Y.`sk_id` AND X.`sk_id` = ? AND X.`status` = 1) AS B ON A.`user_id` = B.`user_id` WHERE A.`user_type` = 1 AND A.`status` = 1 ORDER BY 2";
      $data = $this->db->query($sql,array($sk_id))->result_array(); 
    }
    else {
      $sql = "SELECT A.`user_id`, A.`user_fullname`, IFNULL(B.`sk_name`,'-') AS `user_email` FROM ".$this->_table." A JOIN (SELECT Y.`user_id`, X.`sk_name` FROM satuan_kerja X, satuan_kerja_anggota Y WHERE X.`sk_id` = Y.`sk_id` AND X.`status` = 1) AS B ON A.`user_id` = B.`user_id` WHERE A.`user_type` = 1 AND A.`status` = 1 ORDER BY 2";
      $data = $this->db->query($sql)->result_array(); 
    }
    
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
      $sql = "SELECT A.*, IF(A.`status`=1,'Aktif','Tidak Aktif') AS `status_txt` FROM ".$this->_table." A WHERE 1 ORDER BY A.`user_id` DESC";
      $data = $this->db->query($sql)->result_array(); 
      
      $this->response(array('data' => $data, 'message' => 'successfully'), REST_Controller::HTTP_OK);
    }
    
    $this->response(array('data' => null, 'message' => 'error'), REST_Controller::HTTP_BAD_REQUEST);
  }
  
  public function myprofile_get() 
  {
    $id = $this->userId;
    if ($id) {
      $sql = "SELECT `user_id`, `user_fullname`, `user_email`, `user_photo`, `user_desc` FROM ".$this->_table." WHERE `".$this->_col_primary."` = $id AND `status` = 1 LIMIT 1";
      $data = $this->db->query($sql)->row(); 
      if ($data) {
        $this->response(array('data' => $data, 'message' => 'successfully'), REST_Controller::HTTP_OK);
      }
    }
    
    $this->response(array('data' => null, 'message' => 'error'), REST_Controller::HTTP_BAD_REQUEST);
  }
  
  public function index_post()
  {
    $data = array();
    foreach (array_merge($this->_col_reguler,array('created_by')) as $col) {
      if ($col == 'user_slug') {
        $data[$col] = $this->slugify($this->post('user_fullname'));
      }
      else if ($col == 'user_password') {
        $data[$col] = md5(trim($this->post($col)));
      }
      else {
        $data[$col] = $this->post($col);
      }
    }
    
    if ($data['user_type'] == '1') {
      $data['ps_id'] = null;
    }
    else if ($data['user_type'] == '2') {
      $data['user_peneliti'] = '0';
    }
    else {
      $data['user_peneliti'] = '0';
      $data['ps_id'] = null;
    }
    
    if ($data['user_peneliti'] != '1') {
      $data['pkt_id'] = null;
      $data['pkr_id'] = null;
    }

    if ($this->db->insert($this->_table, $data)) {
      $id = $this->db->insert_id();
      $this->response(array('data' => $id, 'message' => 'successfully'), REST_Controller::HTTP_OK);
    }
    
    $this->response(array('data' => null, 'message' => 'error'), REST_Controller::HTTP_BAD_REQUEST);
  }
  
  public function myprofile_put()
  {
    $id = $this->userId;
    if ($id) {
      $col_reguler = array(
        'user_fullname',
        'user_photo',
        'user_email',
      );
      
      $data = array();
      foreach (array_merge($col_reguler,array('updated_by')) as $col) {
        $data[$col] = $this->put($col);
      }
      
      $oldPass = $this->put('user_oldpassword') ? trim($this->put('user_oldpassword')) : '';
      if ($oldPass) {
        $sql = "SELECT `user_password` FROM ".$this->_table." WHERE `user_id` = ? LIMIT 1";
        $row = $this->db->query($sql,array($id))->row(); 
        if ($row) {
          if (hash_equals($row->user_password, md5($oldPass)) ) {
            $data['user_password'] = md5($this->put('user_password'));
          }
          else {
            $this->response(array('data' => null, 'message' => 'Password lama salah'), REST_Controller::HTTP_BAD_REQUEST);
          }
        }
      }
      
      $this->db->where($this->_col_primary, $id);
      if ($this->db->update($this->_table, $data)) {
        $this->response(array('data' => $id, 'message' => 'successfully'), REST_Controller::HTTP_OK);
      }
    }
    
    $this->response(array('data' => null, 'message' => 'Internal server error'), REST_Controller::HTTP_BAD_REQUEST);
  }
  
  public function index_put($id=0)
  {
    if ($this->role==='admin') {
      $id = intval($id);
    }
    if ($id) {
      $data = array();
      foreach (array_merge($this->_col_reguler,array('updated_by')) as $col) {
        if ( in_array($col, array('user_password','user_slug')) ) {
          continue;
        }
        $data[$col] = $this->put($col);
      }
      
      $user_password = $this->put('user_password') ? trim($this->put('user_password')) : '';
      if ($user_password) {
        $user_password = md5($user_password);
        $data['user_password'] = $user_password;
      }
      
      if ($data['user_type'] == '1') {
      $data['ps_id'] = null;
      }
      else if ($data['user_type'] == '2') {
        $data['user_peneliti'] = '0';
      }
      else {
        $data['user_peneliti'] = '0';
        $data['ps_id'] = null;
      }
      
      if ($data['user_peneliti'] != '1') {
        $data['pkt_id'] = null;
        $data['pkr_id'] = null;
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
