<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '/libraries/JWT.php';
use \Firebase\JWT\JWT;

class Authentication extends REST_Controller {

  public function __construct()
  {
    parent::__construct();
    
  }
  
  public function index_post()
  {
    
    foreach (array('email','password','strategy','keep') as $col) {
      $$col = $this->post($col);
    }
    
    if (empty($email) || empty($password) || $strategy !== 'local' ) {
      $this->response(array('message' => 'Invalid parameters'), REST_Controller::HTTP_BAD_REQUEST);
    }
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false ) {
      $this->response(array('message' => 'Invalid parameters'), REST_Controller::HTTP_BAD_REQUEST);
    }
    
    $key = "dhSHtk9VcDJj0FHDfwdiputnZBAx6b8K0ZReymZhm5GJnPrELxL9kSiFKswHYmYYISubLRYO34j+NEjMTOR8UgYEKoqUYgl2IPohG/DiOySslgmSnsRk+YxY9X7SgSWb0slpGdCjt90Pkm1B/3LFmYO6mzT3klmgKviv/p6VEbx9X9w6gl044ajM2J3b36hDpogzn/NMgolCn+m6l9DvnoSmdwanQ+m/ao+wOXQTw9h6J0k9tE9aAal/tAMRAAcDF2M6mETgjLiRPDBZhOUSa+4b+H1G4Z1bVZjEciz11m0BhBTm/MaWUMh/aGaWMgZR47uOk58r8beM1JN54BmvlQ==";
    
    $userCanLogin = false ;
    
    $dataUser = array(
      'role' => 'anggota',
      'satker' => 0,
      'kegiatan' => 0,
      'peneliti' => 0,
      'eksternal' => 0,
      'internal' => 0,
    );
    
    $sql = "SELECT `user_id`, `user_password`, `user_fullname`, `user_type`, `user_superadmin`, `user_peneliti`, `status`, IFNULL(`ps_id`,0) AS `ps_id` FROM `user_bankdata` WHERE `user_email` = ? LIMIT 1";
    $row = $this->db->query($sql,array($email))->row();
    if ($row) {
      $row->status = intval($row->status);
      $row->user_id = intval($row->user_id);
      $row->user_type = intval($row->user_type);
      $row->user_peneliti = intval($row->user_peneliti);
      $row->user_superadmin = intval($row->user_superadmin);
      $row->ps_id = intval($row->ps_id);
      
      if ($row->user_id > 0 && $row->status === 1 && $row->user_type !== 3) {
        if ( hash_equals($row->user_password, md5($password)) ) {
          
          if ($row->user_superadmin) {
            $userCanLogin = true;
            $dataUser['role'] = 'admin';
          }
          else {
            $dataUser['peneliti'] = $row->user_peneliti;
          
            if ($row->user_type === 1) {
              // get satker, mandatory
              // satker user always 1
              $sql2 = "SELECT A.`sk_id`, A.`ps_id`, B.`role` FROM `satuan_kerja` A, `satuan_kerja_anggota` B WHERE A.`sk_id` = B.`sk_id` AND A.`status` = 1 AND B.`user_id` = ? LIMIT 1";
              $row2 = $this->db->query($sql2,array($row->user_id))->row();
              if ($row2 && isset($row2->sk_id)) {
                $userCanLogin = true;
                
                $row2->role = intval($row2->role);
                $row2->sk_id = intval($row2->sk_id);
                $row2->ps_id = intval($row2->ps_id);
                
                if ($row2->role === 1) {
                  $dataUser['role'] = 'satker';
                }
                
                $dataUser['satker'] = $row2->sk_id;
                $dataUser['internal'] = $row2->ps_id;
                
                // get data kegiatan
                $sql3 = "SELECT A.`kg_id`, B.`role` FROM `kegiatan` A, `kegiatan_anggota` B WHERE A.`kg_id` = B.`kg_id` AND A.`status` = 1 AND B.`user_id` = ?";
                $row3 = $this->db->query($sql3,array($row->user_id))->result_array();
                if ($row3) {
                  $kegiatans = array();
                  foreach ($row3 as $val) {
                    $kegiatans[] = array(
                      'id' => intval($val['kg_id']),
                      'role' => intval($val['role'])
                    );
                  }
                  if ($kegiatans) {
                    $dataUser['kegiatan'] = $kegiatans;
                  }
                }
              }
            }
            else if ($row->user_type === 2) {
              $dataUser['role'] = 'eksternal';
              $dataUser['eksternal'] = $row->ps_id;
              $dataUser['peneliti'] = 0;
              $userCanLogin = true;
            }
            
          }
          if ($userCanLogin) {
            $times = time();
            $token = array();
            
            $token['username'] = $row->user_fullname;
            $token['userId'] = $row->user_id;
            $token['iat'] = $times;
            $token['exp'] = $times + 120*120*1;
            $token['role'] = $dataUser['role'];
            $token['payload'] = array(
              'satker' => $dataUser['satker'],
              'kegiatan' => $dataUser['kegiatan'],
              'peneliti' => $dataUser['peneliti'],
              'eksternal' => $dataUser['eksternal'],
              'internal' => $dataUser['internal'],
            );
            
            $this->db->where('user_id', $row->user_id);
            if ($this->db->update('user_session', array('status' => 0))) {
              $logs = array(
                'user_id' => $row->user_id,
                'payload' => json_encode($token),
                'status' => 1
              );
              if ($this->db->insert('user_session', $logs)) {
                $sessId = $this->db->insert_id();
                $token['sid'] = $sessId;
                
                $jwt = JWT::encode($token, $key);
                $this->response(array('accessToken' => $jwt, 'message' => 'login successfully'), REST_Controller::HTTP_OK);
              }
            }
          }
        }
      }
    }
   
   $this->response(array('message' => 'Invalid parameters'), REST_Controller::HTTP_BAD_REQUEST); 
  }
}
