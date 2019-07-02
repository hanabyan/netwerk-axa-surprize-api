<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_model extends CI_Model {
	
	public function __construct()
    {
        parent::__construct();
        $this->table = 'adm_role';
    }

    public function get($id = '')
    {
        if ($id != '')
        {
            return $this->db->get_where($this->table, array('role_id' => $id))->result_array();
        }

        return $this->db->get($this->table)->result();
    }

    public function update($id, $data)
    {
        $this->db->where('role_id', $id);
        $this->db->update($this->table, $data);

        if ($this->db->affected_rows() > 0)
        {
            return true;
        }

        return false;
    }

    public function delete($id)
    {
        $this->db->delete($this->table, array('role_id' => $id));

        if ($this->db->affected_rows() > 0)
        {
            return true;
        }

        return false;
    }
}