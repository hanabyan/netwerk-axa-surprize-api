<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . '/core/Onlyus.php';

class Product extends Onlyus {
  
  public $_table;
  public $_col_primary;
  public $_col_reguler;
  public $_col_child;
  
  public function __construct()
  {
    parent::__construct();
    $this->_table = 'product';
    $this->_col_primary = 'p_id';
    $this->_col_reguler = array(
      'p_title',
      'p_date',
      'p_highlight',
      'p_source_label',
      'p_photo',
      'p_desc',
      'p_content',
      'p_content_data',
      'p_status',
      'ps_id',
      'pc_id',
      'kg_id',
      'p_status_review',
    );
  }
  
  public function kegiatan_get($id=0) {
    $kg_id = intval($id);
    
    if ($kg_id<1) {
      $this->response(array('data' => null, 'message' => 'error'), REST_Controller::HTTP_BAD_REQUEST);
    }
    else {
      $fcats = array();
      $sql = "SELECT `pdc_id`, `pdc_name` FROm ".$this->_table."_doc_category WHERE `status` = 1";
      $res = $this->db->query($sql)->result_array(); 
      foreach ($res as $row) {
        $fcats[$row['pdc_id']] = $row['pdc_name'];
      }
    
      $sql = "SELECT A.`p_title`, C.`pd_id`, C.`pd_fname`, C.`pd_fext`, C.`pd_fsize`, C.`pd_url`, C.`is_file`, IFNULL(C.`pdc_id`,'') AS `category`, '' AS `category_name`, '0' AS `pd_fsize_txt`
      FROM ".$this->_table." A 
        JOIN ".$this->_table."_doc C ON A.p_id = C.p_id 
      WHERE A.`kg_id` = $kg_id AND A.status = 1 ORDER BY A.`p_id` ASC, C.`is_file` DESC, C.`pd_id` ASC";
      $res = $this->db->query($sql)->result_array(); 
      
      foreach ($res as $key => $val) {
        if ($val['is_file']) {
          $res[$key]['category_name'] = isset($fcats[$val['category']]) ? $fcats[$val['category']] : '';
          $res[$key]['pd_fsize_txt'] = $this->MakeReadable($val['pd_fsize']);
        }
      }
      
      $this->response(array('data' => $res, 'message' => 'successfully'), REST_Controller::HTTP_OK);
    }
  }
  
  public function index_get($id=0) 
  {
    $fcats = array();
    $sql = "SELECT `pdc_id`, `pdc_name` FROm ".$this->_table."_doc_category WHERE `status` = 1";
    $res = $this->db->query($sql)->result_array(); 
    foreach ($res as $row) {
      $fcats[$row['pdc_id']] = $row['pdc_name'];
    }
      
    $id = intval($id);
    if ($id) {
      $tagging = array();
      $sql = "SELECT `tg_id` FROm ".$this->_table."_tagging WHERE `p_id` = $id";
      $res = $this->db->query($sql)->result_array(); 
      foreach ($res as $row) {
        $tagging[] = $row['tg_id'];
      }
      
      $sources = array();
      $sql = "SELECT `ps_id`, `ps_type` FROm ".$this->_table."_source";
      $res = $this->db->query($sql)->result_array(); 
      foreach ($res as $row) {
        $sources[$row['ps_id']] = $row['ps_type'];
      }
      
      $sql = "SELECT A.*, C.`pd_id`, C.`pd_fname`, C.`pd_fext`, C.`pd_fsize`, C.`pd_url`, C.`is_file`, IFNULL(C.`pdc_id`,'') AS `pdc_id`, '' AS `docs` , '' AS `tags`, '3' AS `source`
      FROM ".$this->_table." A 
        LEFT JOIN ".$this->_table."_doc C ON A.p_id = C.p_id 
      WHERE A.`p_id` = $id AND A.status = 1 ORDER BY C.`is_file` DESC, C.`pd_id` ASC";
      $res = $this->db->query($sql)->result_array(); 
      if ($res) {
        $data = array();
        foreach ($res as $key => $val) {
          if (!isset($data[$val['p_id']])) {
            $data[$val['p_id']] = $val;
            $data[$val['p_id']]['tags'] = $tagging;
            $data[$val['p_id']]['docs'] =array();
          }
          
          if (isset($sources[$data[$val['p_id']]['ps_id']])) {
            $data[$val['p_id']]['source'] = $data[$val['p_id']]['ps_id'];
          }
          $hasDoc = intval($val['pd_id']);
          if ($hasDoc) {
            $data[$val['p_id']]['docs'][] = array(
              'pd_id' => $val['pd_id'],
              'pd_fname' => $val['pd_fname'],
              'pd_fext' => $val['pd_fext'],
              'pd_fsize' => $val['pd_fsize'],
              'pd_fsize_txt' => $val['is_file'] ? $this->MakeReadable($val['pd_fsize']) : 0,
              'download' => true,
              'category' => $val['pdc_id'],
              'category_name' => isset($fcats[$val['pdc_id']]) ? $fcats[$val['pdc_id']] : '',
              'pd_url' => $val['pd_url'],
              'is_file' => $val['is_file'],
            );
          }
        }
        
        if ($data) {
          $data = array_values($data);
          $data = (object) $data[0];
        }
        
        $this->response(array('data' => $data, 'message' => 'successfully'), REST_Controller::HTTP_OK);
      }
      else {
        $this->response(array('data' => array(), 'message' => 'successfully'), REST_Controller::HTTP_NO_CONTENT);
      }
    }
    else {
      if ($this->role === 'admin' || $this->role === 'satker') {
        $sql = "SELECT A.`p_id`, A.`p_title`, A.`p_year`, A.`p_source_label`, A.`p_status_review`, A.`p_status`, '' AS `docs`, B.pc_name, C.`pd_id`, C.`pd_fname`, C.`pd_fext`, C.`pd_fsize`, C.`pd_url`, C.`is_file`, IFNULL(C.`pdc_id`,'') AS `pdc_id`,
        IFNULL(D.user_fullname,'Unknown') AS `createdBy`, IFNULL(D.user_fullname,'') AS `updatedBy`
        FROM ".$this->_table." A 
          JOIN ".$this->_table."_category B ON A.pc_id = B.pc_id
          LEFT JOIN ".$this->_table."_doc C ON A.p_id = C.p_id 
          LEFT JOIN `user_bankdata` D ON A.created_by = D.user_id
          LEFT JOIN `user_bankdata` E ON A.updated_by = E.user_id
        WHERE A.status = 1 ORDER BY A.`p_id` DESC, C.`is_file` DESC, C.`pd_id` ASC";
        $res = $this->db->query($sql)->result_array(); 
      }
      else if ($this->kegiatan) {
        $ids = array();
        foreach ($this->kegiatan as $valx) {
          $ids[] = $valx['id'];
        }
         $sql = "SELECT A.`p_id`, A.`p_title`, A.`p_year`, A.`p_source_label`, A.`p_status_review`, A.`p_status`, '' AS `docs`, B.pc_name, C.`pd_id`, C.`pd_fname`, C.`pd_fext`, C.`pd_fsize`, C.`pd_url`, C.`is_file`, IFNULL(C.`pdc_id`,'') AS `pdc_id`,
         IFNULL(D.user_fullname,'Unknown') AS `createdBy`, IFNULL(D.user_fullname,'') AS `updatedBy`
        FROM ".$this->_table." A 
          JOIN ".$this->_table."_category B ON A.pc_id = B.pc_id
          LEFT JOIN ".$this->_table."_doc C ON A.p_id = C.p_id 
          LEFT JOIN `user_bankdata` D ON A.created_by = D.user_id
          LEFT JOIN `user_bankdata` E ON A.updated_by = E.user_id
        WHERE A.status = 1 AND ( A.kg_id IN ? OR A.created_by = ? OR A.updated_by = ?) ORDER BY A.`p_id` DESC, C.`is_file` DESC, C.`pd_id` ASC";
        $res = $this->db->query($sql, array($ids, $this->userId, $this->userId))->result_array(); 
      }
      else {
        $sql = "SELECT A.`p_id`, A.`p_title`, A.`p_year`, A.`p_source_label`, A.`p_status_review`, A.`p_status`, '' AS `docs`, B.pc_name, C.`pd_id`, C.`pd_fname`, C.`pd_fext`, C.`pd_fsize`, C.`pd_url`, C.`is_file`, IFNULL(C.`pdc_id`,'') AS `pdc_id`,
        IFNULL(D.user_fullname,'Unknown') AS `createdBy`, IFNULL(D.user_fullname,'') AS `updatedBy`
        FROM ".$this->_table." A 
          JOIN ".$this->_table."_category B ON A.pc_id = B.pc_id
          LEFT JOIN ".$this->_table."_doc C ON A.p_id = C.p_id 
          LEFT JOIN `user_bankdata` D ON A.created_by = D.user_id
          LEFT JOIN `user_bankdata` E ON A.updated_by = D.user_id
        WHERE A.status = 1 AND (A.created_by = ? OR A.updated_by = ?) ORDER BY A.`p_id` DESC, C.`is_file` DESC, C.`pd_id` ASC";
        $res = $this->db->query($sql, array($this->userId,$this->userId))->result_array(); 
      }
      
      
      $data = array();
      
      foreach ($res as $key => $val) {
        if (!isset($data[$val['p_id']])) {
          $data[$val['p_id']] = array(
            'p_id' => $val['p_id'],
            'p_title' => $val['p_title'],
            'p_year' => $val['p_year'],
            'p_source_label' => $val['p_source_label'],
            'pc_name' => $val['pc_name'],
            'p_status' => $val['p_status'],
            'p_status_review' => $val['p_status_review'],
            'createdBy' => $val['createdBy'],
            'updatedBy' => $val['updatedBy'],
            'docs' => array(),
          );
        }
        
        $hasDoc = intval($val['pd_id']);
        if ($hasDoc) {
          $data[$val['p_id']]['docs'][] = array(
            'pd_id' => $val['pd_id'],
            'pd_fname' => $val['pd_fname'],
            'pd_fext' => $val['pd_fext'],
            'pd_fsize' => $val['pd_fsize'],
            'category' => $val['pdc_id'],
            'category_name' => isset($fcats[$val['pdc_id']]) ? $fcats[$val['pdc_id']] : '',
            'pd_fsize_txt' => $val['is_file'] ? $this->MakeReadable($val['pd_fsize']) : 0,
            'pd_url' => $val['pd_url'],
            'is_file' => $val['is_file'],
          );
        }
      }
      
      if ($data) {
        $data = array_values($data);
      }
      
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
    
    $docs = $this->post('docs');
    if (!is_array($docs)) {
      $docs = array();
    }
    
    $links = $this->post('links');
    if (!is_array($links)) {
      $links = array();
    }
    
    $tags = $this->post('tags');
    if (!is_array($tags)) {
      $tags = array();
    }
    
    // jika karya ilmiah set empty source label
    if ($data['pc_id'] == '2') {
      $data['p_source_label'] = '';
    }
    
    $data['p_date'] = date('Y-m-d', strtotime($data['p_date']));
    $data['p_year'] = date('Y', strtotime($data['p_date']));
    
    $this->db->trans_begin();
    
    $data['created_by'] = $this->userId;
    if ($this->db->insert($this->_table, $data)) {
      $id = $this->db->insert_id();
      
      $dataChild = array();
      foreach ($docs as $va) {
        $dataChild[] = array(
          'p_id' => $id,
          'pd_fname' => $va['fname'],
          'pd_fext' => $va['fext'],
          'pd_fsize' => $va['fsize'],
          'pd_file_detail' => json_encode($va['fdetail']),
          'pd_url' => null,
          'pdc_id' => $va['fcat'],
          'is_file' => 1
        );
      }
      
      foreach ($links as $va) {
        $dataChild[] = array(
          'p_id' => $id,
          'pd_fname' => $va['fname'],
          'pd_fext' => null,
          'pd_fsize' => 0,
          'pd_file_detail' => null,
          'pd_url' => $va['furl'],
          'is_file' => 0
        );
      }
      
      if ($dataChild) {
        $this->db->insert_batch($this->_table . '_doc', $dataChild);
      }
      
      $dataChild = array();
      foreach ($tags as $tg) {
        $dataChild[] = array(
          'p_id' => $id,
          'tg_id' => $tg,
        );
      }
      
      if ($dataChild) {
        $this->db->insert_batch($this->_table . '_tagging', $dataChild);
      }
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
      
      $docs = $this->put('docs');
      if (!is_array($docs)) {
        $docs = array();
      }
      
      $docs_removed = $this->put('docs_removed');
      if (!is_array($docs_removed)) {
        $docs_removed = array();
      }
      else {
        $docs_removed = array_map('intval',$docs_removed);
      }
      
      $links = $this->put('links');
      if (!is_array($links)) {
        $links = array();
      }
      
      $tags = $this->put('tags');
      if (!is_array($tags)) {
        $tags = array();
      }
      
      $sql = "SELECT * FROM ".$this->_table." WHERE ".$this->_col_primary." = ?";
      $ori = $this->db->query($sql,array($id))->row(); 
      if (!$ori) {
        $this->response(array('data' => null, 'message' => 'Data invalid'), REST_Controller::HTTP_BAD_REQUEST);
      }
      $id = $ori->p_id;
      
      $data['p_date'] = date('Y-m-d', strtotime($data['p_date']));
      $data['p_year'] = date('Y', strtotime($data['p_date']));
      
      // jika karya ilmiah set empty source label
      if ($data['pc_id'] == '2') {
        $data['p_source_label'] = '';
      }
    
      $this->db->trans_begin();
      
      $data['updated_by'] = $this->userId;
      $this->db->where($this->_col_primary, $id);
      if ($this->db->update($this->_table, $data)) {
        if ($docs_removed) {
          $this->db->where_in('pd_id', $docs_removed);
          $this->db->delete($this->_table . '_doc');
        }
        
        $dataChild = array();
        foreach ($docs as $va) {
          $dataChild[] = array(
            'p_id' => $id,
            'pd_fname' => $va['fname'],
            'pd_fext' => $va['fext'],
            'pd_fsize' => $va['fsize'],
            'pd_file_detail' => json_encode($va['fdetail']),
            'pd_url' => null,
            'pdc_id' => $va['fcat'],
            'is_file' => 1
          );
        }
        
        foreach ($links as $va) {
          $dataChild[] = array(
            'p_id' => $id,
            'pd_fname' => $va['fname'],
            'pd_fext' => null,
            'pd_fsize' => 0,
            'pd_file_detail' => null,
            'pd_url' => $va['furl'],
            'is_file' => 0
          );
        }
        
        if ($dataChild) {
          $this->db->insert_batch($this->_table . '_doc', $dataChild);
        }
        
        $this->db->delete($this->_table . '_tagging', array('p_id' => $id));
        
        $dataChild = array();
        foreach ($tags as $tg) {
          $dataChild[] = array(
            'p_id' => $id,
            'tg_id' => $tg,
          );
        }
        
        if ($dataChild) {
          $this->db->insert_batch($this->_table . '_tagging', $dataChild);
        }
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
  
  public function MakeReadableXXX($bytes, $decimals = 1) {
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
  }

  public function MakeReadable($size,$precision=1) {
    static $units = array('kB','MB','GB','TB','PB','EB','ZB','YB');
    $step = 1024;
    $i = 0;
    while (($size / $step) > 0.9) {
        $size = $size / $step;
        $i++;
    }
    return round($size, $precision).' '.$units[$i];
  }
}
