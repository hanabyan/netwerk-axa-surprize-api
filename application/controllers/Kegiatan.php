<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//require_once APPPATH . 'libraries/REST_Controller.php';

class Kegiatan extends REST_Controller {
  
  public $_table;
  public $_col_primary;
  public $_col_reguler;
  public $_col_child;
  
  public function __construct()
  {
    parent::__construct();
    $this->_table = 'kegiatan';
    $this->_col_primary = 'kg_id';
    $this->_col_reguler = array(
      'kg_name',
      'kg_year',
      'kg_desc',
      'sk_id',
      'status'
    );
  }
  
  public function select_get() {
    $sql = "SELECT A.`kg_id` AS `value`, A.`kg_name` AS `label`, A.`kg_year`, C.`ps_name` AS `instansi` FROM ".$this->_table." A, `satuan_kerja` B, `product_source` C  WHERE A.`sk_id` = B.`sk_id` AND B.ps_id = C.ps_id AND A.`status` = 1 ORDER BY A.`kg_name`";
    $data = $this->db->query($sql)->result_array(); 
    
    $this->response(array('data' => $data, 'message' => 'successfully'), REST_Controller::HTTP_OK);
  }
  
  public function index_get($id=0) 
  {
    $id = intval($id);
    if ($id) {
      $sql = "SELECT * FROM ".$this->_table." WHERE `kg_id` = $id LIMIT 1";
      $data = $this->db->query($sql)->row(); 
      if ($data) {
        $sql = "SELECT B.`user_id`, B.`user_fullname`, '-' AS `user_email`, A.`role` FROM ".$this->_table."_anggota A, user_bankdata B WHERE A.`user_id` = B.`user_id` AND A.`kg_id` = ? ORDER BY B.`user_fullname`";
        $anggota = $this->db->query($sql,array($data->kg_id))->result_array(); 
        $tmp = array();
        foreach ($anggota as $va) {
          $tmp[] = $va['user_id'];
        }
        
        if ($tmp) {
          $sql = "SELECT Y.`user_id`, X.`sk_name` FROM satuan_kerja X, satuan_kerja_anggota Y WHERE X.`sk_id` = Y.`sk_id` AND Y.`user_id` IN ? AND X.`status` = 1";
          $userSatker = $this->db->query($sql, array($tmp))->result_array(); 
          $tmp = array();
          foreach ($userSatker as $va) {
            $tmp[ $va['user_id']] = $va;
          }
          foreach ($anggota as $ka => $va) {
            if (isset($tmp[$va['user_id']])) {
              $anggota[$ka]['user_email'] = $tmp[$va['user_id']]['sk_name'];
            }
          }
        }
        
        $data->anggota = $anggota;
        $this->response(array('data' => $data, 'message' => 'successfully'), REST_Controller::HTTP_OK);
      }
      else {
        $this->response(array('data' => array(), 'message' => 'successfully'), REST_Controller::HTTP_NO_CONTENT);
      }
    }
    else {
      $sql = "SELECT A.*, IF(A.`status`=1,'Aktif','Tidak Aktif') AS `status_txt`, COUNT(B.`kg_id`) AS `anggota`, C.`sk_name` FROM ".$this->_table." A, ".$this->_table."_anggota B, satuan_kerja C WHERE A.kg_id = B.kg_id AND A.sk_id = C.sk_id GROUP BY A.`kg_id` ORDER BY `kg_id` DESC";
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
    
    $anggota = $this->post('anggota');
    if (!is_array($anggota)) {
      $anggota = array();
    }
    if (empty($anggota)) {
      $this->response(array('data' => null, 'message' => 'Anggota harus diisi'), REST_Controller::HTTP_BAD_REQUEST);
    }
    
    $this->db->trans_begin();
    
    $data['created_by'] = 1;
    if ($this->db->insert($this->_table, $data)) {
      $id = $this->db->insert_id();
      
      $dataChild = array();
      foreach ($anggota as $va) {
        $dataChild[] = array(
          'kg_id' => $id,
          'user_id' => intval($va['user_id']),
          'role' => intval($va['role']),
        );
      }
      $this->db->insert_batch($this->_table . '_anggota', $dataChild);
    }
    
    if ($this->db->trans_status() === FALSE) {
      $this->db->trans_rollback();
      
      $this->response(array('data' => null, 'msg' => 'Gagal menyimpan data'), REST_Controller::HTTP_BAD_REQUEST);
    }
    else {
      $this->db->trans_commit();
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
      
      $anggota = $this->put('anggota');
      if (!is_array($anggota)) {
        $anggota = array();
      }
      if (empty($anggota)) {
        $this->response(array('data' => null, 'message' => 'Anggota harus diisi'), REST_Controller::HTTP_BAD_REQUEST);
      }
      
      $sql = "SELECT * FROM ".$this->_table." WHERE ".$this->_col_primary." = ?";
      $ori = $this->db->query($sql,array($id))->row(); 
      if (!$ori) {
        $this->response(array('data' => null, 'message' => 'Data invalid'), REST_Controller::HTTP_BAD_REQUEST);
      }
      $id = $ori->kg_id;
      
      $this->db->trans_begin();
      
      $data['updated_by'] = 1;
      $this->db->where($this->_col_primary, $id);
      if ($this->db->update($this->_table, $data)) {
        if ($this->db->delete($this->_table . '_anggota', array('kg_id' => $id))) {
        }
        $dataChild = array();
        foreach ($anggota as $va) {
          $dataChild[] = array(
            'kg_id' => $id,
            'user_id' => intval($va['user_id']),
            'role' => intval($va['role']),
          );
        }
        $this->db->insert_batch($this->_table . '_anggota', $dataChild);
      }
      
      if ($this->db->trans_status() === FALSE) {
        $this->db->trans_rollback();
        
        $this->response(array('data' => null, 'msg' => 'Gagal menyimpan data'), REST_Controller::HTTP_BAD_REQUEST);
      }
      else {
        $this->db->trans_commit();
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
