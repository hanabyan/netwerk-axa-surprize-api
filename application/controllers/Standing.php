<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//require_once APPPATH . 'libraries/REST_Controller.php';

class Standing extends REST_Controller {
  
  public $_table_season;
  public $_table_season_detail;
  public $_table_player;
  public $_grade = array(
    '3' => array( 10, 6, 5 ),
    '4' => array( 15, 11, 9, 8 ),
    '5' => array( 20, 16, 13, 11, 10 ),
    '6' => array( 25, 20, 16, 13, 11, 10 ),
  );
  
  public function __construct()
  {
    parent::__construct();
    $this->_table_season = '`season`';
    $this->_table_season_detail = '`seasons`';
    $this->_table_player = '`player`';
  }
  
  public function index_get() 
  {
    $totalPlayer = 0;
    $standing = array();
    $sql = "SELECT `playerid`, `name` FROM ".$this->_table_player." WHERE `status` = 1"; 
    $res = $this->db->query($sql)->result_array(); 
    $urut=1;
    foreach ($res as $p) {
      $totalPlayer++;
      
      $standing[$p['playerid']] = array(
        'pid' => $p['playerid'],
        'seq' => 0,
        'name' => $p['name'],
        'game' => 0,
        'pointPerGame' => 0,
        'point' => 0,
        'pos1' => 0,
        'pos2' => 0,
        'pos3' => 0,
        'pos4' => 0,
        'pos5' => 0,
        'pos6' => 0,
        'looser' => 0,
        'close' => 0,
        'closed' => 0,
        'starred' => 0,
        //'comeback' => 0,
      );
    }
    
    if ($standing) {
      
      $sql = "SELECT A.`seasonid`, A.`playerid`, B.`player_count`, GROUP_CONCAT(A.`point` ORDER BY A.`seq` DESC) AS `point`, SUM(A.`closeit`) AS `closeit`, SUM(A.`closed`) AS `closed`, SUM(A.`starred`) AS `starred` FROM ".$this->_table_season_detail." A, ".$this->_table_season." B WHERE A.`seasonid` = B.`seasonid` AND B.`status` = 2 GROUP BY `seasonid`, `playerid` ORDER BY `seasonid`";
      $res = $this->db->query($sql)->result_array(); 
      
      $sess_tmp = $player_count = 0;
      $winner = array();
      
      foreach ($res as $r) {
        
        if ($sess_tmp && $sess_tmp != $r['seasonid']) {
          
          array_multisort(array_map(function($element) {
            return $element['point'];
          }, $winner), SORT_DESC, $winner);
          
          foreach ($winner as $kw => $vw) {
            if (isset($standing[$vw['pid']])) {
              if (isset($this->_grade[$player_count])) {
                $posIndex = $kw+1;
                $standing[$vw['pid']]['game']++;
                $standing[$vw['pid']]['point'] += $this->_grade[$player_count][$kw];
                $standing[$vw['pid']]['pos'.$posIndex]++;
                if (count($winner) == $posIndex) {
                  $standing[$vw['pid']]['looser']++;
                }
                $standing[$vw['pid']]['close'] += $vw['close'];
                $standing[$vw['pid']]['closed'] += $vw['closed'];
                $standing[$vw['pid']]['starred'] += $vw['starred'];
                
                $point_add = $vw['close'] * 1;
                $point_sub = $vw['closed'] * -1;
                
                $standing[$vw['pid']]['point'] += $point_add;
                $standing[$vw['pid']]['point'] += $point_sub;
              }
            }
          }
          
          $winner = array(); 
        }
        
        $player_count = $r['player_count'];
        $sess_tmp = $r['seasonid'];
        
        if (isset($standing[$r['playerid']])) { // check if player active
          
          $points = array_map('trim', explode(',',$r['point'],2));
          $point_high = $points[0];
          
          $winner[] = array(
            'pid' => $r['playerid'],
            'point' => $point_high,
            'close' => $r['closeit'],
            'closed' => $r['closed'],
            'starred' => $r['starred'],
            
          );
          
        }
      }
      
      if ($sess_tmp) {
        array_multisort(array_map(function($element) {
            return $element['point'];
          }, $winner), SORT_DESC, $winner);
          
        foreach ($winner as $kw => $vw) {
          if (isset($standing[$vw['pid']])) {
            if (isset($this->_grade[$player_count])) {
              $posIndex = $kw+1;
              $standing[$vw['pid']]['game']++;
              $standing[$vw['pid']]['point'] += $this->_grade[$player_count][$kw];
              $standing[$vw['pid']]['pos'.$posIndex]++;
              if (count($winner) == $posIndex) {
                $standing[$vw['pid']]['looser']++;
              }
              $standing[$vw['pid']]['close'] += $vw['close'];
              $standing[$vw['pid']]['closed'] += $vw['closed'];
              $standing[$vw['pid']]['starred'] += $vw['starred'];
              
              $point_add = $vw['close'] * 1;
              $point_sub = $vw['closed'] * -1;
              
              $standing[$vw['pid']]['point'] += $point_add;
              $standing[$vw['pid']]['point'] += $point_sub;
            }
          }
        }
        
        foreach($standing as $k => $p) {
          $divider = ($p['game']<$totalPlayer?$totalPlayer:$p['game']);
          $standing[$k]['pointPerGame'] = round( ($p['point']/$divider), 2 );
        }
        
        array_multisort(array_map(function($element) {
          return $element['pointPerGame'];
        }, $standing), SORT_DESC, $standing);
        
        $urutan = 0;
        foreach($standing as $k => $p) {
          $urutan++;
          $standing[$k]['seq'] = $urutan;
          //$standing[$k]['name'] = $urutan.'. '.$p['name'];
        }
      }
    }
    //$this->response(array('data' => $standing, 'count' => count($standing)), REST_Controller::HTTP_OK);
    $this->response(array('data' => $standing), REST_Controller::HTTP_OK);
  }
  }
