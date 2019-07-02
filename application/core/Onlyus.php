<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '/libraries/JWT.php';
use \Firebase\JWT\JWT;

class Onlyus extends REST_Controller {
  
  private $headers = array();
  public $role = 0;
  public $eksternal = 0;
  public $internal = 0;
  public $kegiatan = 0;
  public $peneliti = 0;
  public $userId = 0;
  public $satker = 0;
  
  public function __construct()
  {
    parent::__construct();
    
    $this->headers = array();
    foreach (getallheaders() as $name => $value) { //get all parameter in header
      $this->headers[$name] = $value;
    }
    
    $key = "dhSHtk9VcDJj0FHDfwdiputnZBAx6b8K0ZReymZhm5GJnPrELxL9kSiFKswHYmYYISubLRYO34j+NEjMTOR8UgYEKoqUYgl2IPohG/DiOySslgmSnsRk+YxY9X7SgSWb0slpGdCjt90Pkm1B/3LFmYO6mzT3klmgKviv/p6VEbx9X9w6gl044ajM2J3b36hDpogzn/NMgolCn+m6l9DvnoSmdwanQ+m/ao+wOXQTw9h6J0k9tE9aAal/tAMRAAcDF2M6mETgjLiRPDBZhOUSa+4b+H1G4Z1bVZjEciz11m0BhBTm/MaWUMh/aGaWMgZR47uOk58r8beM1JN54BmvlQ==";
    
    $allowed = false;
    if (isset($this->headers['Authorization'])) {
      $jwt = $this->headers['Authorization'];
      try {
        $payload = JWT::decode($jwt, $key, array('HS256'));
        if ($payload) {
          $payload = json_decode(json_encode($payload),true);
          $payload['userId'] = intval($payload['userId']);
          $payload['sid'] = intval($payload['sid']);
          if ($payload['userId'] > 0 && $payload['sid'] > 0) {
            $sql = "SELECT `sid`, `user_id`, `payload`, `log_time`, `status` FROM `user_session` WHERE `sid` = ? LIMIT 1";
            $row = $this->db->query($sql,array($payload['sid']))->row();
            if ($row) {
              $row->status = intval($row->status);
              if ($row->status === 1) {
                $secret = json_decode($row->payload,true);
                $now = time();
                if ($secret['exp'] > $now) {
                  $this->userId = $secret['userId'];
                  $this->role = $secret['role'];
                  $this->internal = $secret['payload']['internal'];
                  $this->eksternal = $secret['payload']['eksternal'];
                  $this->satker = $secret['payload']['satker'];
                  $this->kegiatan = $secret['payload']['kegiatan'];
                  $allowed = true;
                }
              }
            }
          }
        }       
      } 
      catch (Exception $e) {
        // seharunsya di response invalid signature, dan app-nya di logout
      }
    }
    
    if (!$allowed) {
      $this->response(array('data' => null, 'message' => 'Invalid session'), REST_Controller::HTTP_UNAUTHORIZED);
    }
  }
}
