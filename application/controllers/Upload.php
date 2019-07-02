<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Upload extends REST_Controller {
  
  public function __construct()
  {
    parent::__construct();
  }
  
  public function instansi_post() 
  {
    $config['upload_path']          = './uploads/';
    $config['allowed_types']        = 'jpeg|jpg|png';
    $config['max_size']             = 2048;
    
    $this->load->library('upload', $config);
    
    if ( !$this->upload->do_upload('file') ) {
      $this->response(array('data' => null, 'message' => $this->upload->display_errors()), REST_Controller::HTTP_BAD_REQUEST);
    }
    else {
      $resp = $this->upload->data();
      $this->response(array('data' => $resp['file_name'], 'message' => 'Success'), REST_Controller::HTTP_OK);
    }
  }
  
  public function user_post() 
  {
    $config['upload_path']          = './uploads/';
    $config['allowed_types']        = 'jpeg|jpg|png';
    $config['max_size']             = 2048;
    
    $this->load->library('upload', $config);
    
    if ( !$this->upload->do_upload('file') ) {
      $this->response(array('data' => null, 'message' => $this->upload->display_errors()), REST_Controller::HTTP_BAD_REQUEST);
    }
    else {
      $resp = $this->upload->data();
      $this->response(array('data' => $resp['file_name'], 'message' => 'Success'), REST_Controller::HTTP_OK);
    }
  }
  
  public function product_post($type='foto') 
  {
    $config['upload_path']          = './uploads/';
    if ($type=='doc') {
      $config['upload_path']        = './uploads/dokumen/';
    }
    
    $config['allowed_types']        = '*';
    $config['max_size']             = 1048000;
    
    $this->load->library('upload', $config);
    
    if ( !$this->upload->do_upload('file') ) {
      $this->response(array('data' => null, 'message' => $this->upload->display_errors()), REST_Controller::HTTP_BAD_REQUEST);
    }
    else {
      $resp = $this->upload->data();
      $newResp = array(
        "file_name" => $resp["file_name"],
        "file_type" => $resp["file_type"],
        "raw_name" => $resp["raw_name"],
        "file_ext" => $resp["file_ext"],
        "file_size" => $resp["file_size"],
        "is_image" => $resp["is_image"],
        "image_width" => $resp["image_width"],
        "image_height" => $resp["image_height"],
        "image_type" => $resp["image_type"]
      );
      $this->response(array('data' => $newResp, 'message' => 'Success'), REST_Controller::HTTP_OK);
    }
  }
}
