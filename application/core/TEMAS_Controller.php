<?php
class TEMAS_Controller extends CI_Controller
{
  public $base_route;
  
  function __construct()
  {
    parent::__construct();
    
    $this->data = array();
    $this->active_menu = '';
    
    $sql = "SELECT menu_code, menu_name, menu_icon, menu_parent_code, menu_path, menu_type FROM adm_menu WHERE menu_is_mobile = FALSE AND menu_mobile_type='BROWSER' ORDER BY menu_code ASC";
    $nav = $this->db->query($sql)->result_array();
    $nav = $this->_build_menu($nav);
    
    // $nav = $this->buildTree($nav);
    //echo "<pre>";
    //print_r($this->buildTree($nav));
    //die;
    
    // $menu = '';
    // foreach ($nav as $row) {
    //   if ($row['menu_type'] === 'PARENT') {
    //     if ( $row['menu_icon'] && $row['menu_icon'] != '-' ) {
    //       $menu .= '<li><a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false"><i class="mdi '.$row['menu_icon'].'"></i><span class="hide-menu">'.$row['menu_name'].'</span></a><ul aria-expanded="false" class="collapse">';
    //     }
    //     else {
    //       $menu .= '<li><a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="false">'.$row['menu_name'].'</a><ul aria-expanded="false" class="collapse">';
    //     }
        
    //     // get parent's child
    //     foreach ($row['children'] as $row2) {
    //       if ($row2['menu_type'] === 'SUBMENU') {
    //         $menu .= '<li><a class="has-arrow" href="#" aria-expanded="false">'.$row2['menu_name'].'</a><ul aria-expanded="false" class="collapse">';
            
    //         // get sub menu child
    //         foreach ($row2['children'] as $row3) {  // MENU
    //           $menu .= '<li><a href="'.base_url($row3['menu_path']).'">'.$row3['menu_name'].'</a></li>';
    //         }
    //         $menu .= '</ul></li>';
    //       }
    //       else if ($row2['menu_type'] === 'MENU') {
    //         $menu .= '<li><a href="'.base_url($row2['menu_path']).'">'.$row2['menu_name'].'</a></li>';
    //       }
    //     }
    //     $menu .= '</ul></li>';
    //   }
    //   else if ($row['menu_type'] === 'MENU') {
    //     if ( $row['menu_icon'] && $row['menu_icon'] != '-' ) {
    //       $menu .= '<li><a class="waves-effect waves-dark" href="'.base_url($row['menu_path']).'"><i class="mdi '.$row['menu_icon'].'"></i><span class="hide-menu">'.$row['menu_name'].'</span></a></li>';
    //     }
    //     else {
    //       $menu .= '<li><a class="waves-effect waves-dark" href="'.base_url($row['menu_path']).'">'.$row['menu_name'].'</a></li>';
    //     }
    //   }
    // }
    
    $this->data['__page_title'] = 'TEMAS System 2018';
    // $this->data['__page_nav'] = $menu;
    $this->data['__page_nav'] = $nav;
  }
  
  private function buildTree(array $elements, $parentId = -1) {
    $result = array();

    foreach ($elements as $element) {
      if ($element['menu_parent_code'] == $parentId) {
        $children = $this->buildTree($elements, $element['menu_code']);
        $element['children'] = array();

        if ($children) {
          $element['children'] = $children;
        }

        $result[] = $element;
      }

      if (!$this->active_menu)
      {
        if ($this->_is_active_menu($element['menu_path']))
        {
          $this->active_menu = $element['menu_code'];
        }
      }
    }

    return $result;
  }

  private function _is_active_menu($path = '')
  {
    $_arr_uri = [];
    $route_class = $this->router->fetch_class();

    if ($path && $path != '-')
    {
      $_arr_uri = explode('/', $path);

      if ($_arr_uri[count($_arr_uri) - 1] == $route_class)
      {
        return true;
      }
    }

    return false;
  }

  private function _build_menu($data)
  {
    $_trees = $this->buildTree($data);
    $_component = $this->_build_component($_trees);

    return $_component;
  }

  private function _build_component($data)
  {
    $menu = '';
    $children = '';

    foreach ($data as $value) {
      $name_display = $value['menu_name'];

      if ($value['menu_icon'] && $value['menu_icon'] != '-')
      {
        $name_display = '<i class="mdi '.$value['menu_icon'].'"></i><span class="hide-menu">'.$name_display.'</span>';
      }

      $is_active = strpos($this->active_menu, $value['menu_code'] ) !== false;

      if ($value['children'])
      {
        $children = '<ul aria-expanded="false" class="collapse' . ($is_active ? ' in' : '') . '">'.$this->_build_component($value['children']).'</ul>';
      }

      if ($value['menu_type'] == 'PARENT')
      {
        $row = '<li><a class="has-arrow waves-effect waves-dark' . ($is_active ? ' active' : '') . '" href="#" aria-expanded="false">'.$name_display.'</a>'.$children.'</li>';
      }
      else if ($value['menu_type'] == 'SUBMENU')
      {
        $row = '<li><a class="has-arrow' . ($is_active ? ' active' : '') . '" href="#" aria-expanded="false">'.$value['menu_name'].'</a>'.$children.'</li>';
      }
      else if ($value['menu_type'] == 'MENU')
      {
        if ($value['menu_parent_code'] == -1)
        {
          $row = '<li><a class="waves-effect waves-dark' . ($is_active ? ' active' : '') . '" href="'.base_url($value['menu_path']).'">'.$name_display.'</a></li>';
        }
        else
        {
          $row = '<li><a class="' . ($is_active ? ' active' : '') . '" href="'.base_url($value['menu_path']).'">'.$value['menu_name'].'</a></li>';
        }
      }

      $menu .= $row;
    }

    return $menu;
  }

  public function render($template) {
    $loader = new Twig_Loader_Filesystem('application/views');
    $twig = new Twig_Environment($loader/* , array(
        'cache' => 'application/views/email/twig/cache',
    ) */);
    
    $_sess = $this->session->flashdata();
    if (isset($_sess['__flash_msg'])) {
      foreach ($_sess['__flash_msg'] as $var => $val) {
        $this->data[$var] = $val;
      }
    }

    $template_data = array(
      'base_url'  => base_url(),
      'base_route' => $this->base_route
    );

    $new_data = array_merge(
      $template_data,
      $this->data
    );

    echo $twig->render($template, $new_data);
  }
}
