<?php defined('BASEPATH') || exit('Access Denied.');

class TldsModel extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->driver('cache', ['adapter' => 'file']);
        $this->load->database();
    }
    public function get($page=1, $limit=50, $query="", $type="") {
		$offset = ($page-1)*$limit;
		$tlds = array();
		if($type=="tld") {
			$tlds['tlds'] = $this->db->like('tld',$query)->limit($limit)->offset($offset)->order_by('tld_order', 'asc')->get('tlds')->result_array();
			$tlds['total_rows'] = $this->db->like('tld',$query)->get('tlds')->num_rows();
			$tlds['offset'] = $offset+1;
            $tlds['total_pages'] = ceil($tlds['total_rows']/$limit);
            $tlds['page'] = $page;
		} else if($type=="whois") {
			$tlds['tlds'] = $this->db->like('whois_server',$query)->limit($limit)->offset($offset)->order_by('tld_order', 'asc')->get('tlds')->result_array();
			$tlds['total_rows'] = $this->db->like('whois_server',$query)->get('tlds')->num_rows();
			$tlds['offset'] = $offset+1;
            $tlds['total_pages'] = ceil($tlds['total_rows']/$limit);
            $tlds['page'] = $page;
		} else if($type=="price") {
			$tlds['tlds'] = $this->db->like('price',$query)->limit($limit)->offset($offset)->order_by('tld_order', 'asc')->get('tlds')->result_array();
			$tlds['total_rows'] = $this->db->like('price',$query)->get('tlds')->num_rows();
			$tlds['offset'] = $offset+1;
            $tlds['total_pages'] = ceil($tlds['total_rows']/$limit);
            $tlds['page'] = $page;
		} else if($type=="all") {
			$tlds['tlds'] = $this->db->like('price',$query)->or_like('whois_server',$query)->or_like('tld',$query)->limit($limit)->offset($offset)->order_by('tld_order', 'asc')->get('tlds')->result_array();
			$tlds['total_rows'] = $this->db->like('price',$query)->or_like('whois_server',$query)->or_like('tld',$query)->get('tlds')->num_rows();
			$tlds['offset'] = $offset+1;
            $tlds['total_pages'] = ceil($tlds['total_rows']/$limit);
            $tlds['page'] = $page;
		} else {
			$tlds['tlds'] = $this->db->limit($limit)->offset($offset)->order_by('tld_order', 'asc')->get('tlds')->result_array();
			$tlds['total_rows'] = $this->db->get('tlds')->num_rows();
            $tlds['total_pages'] = ceil($tlds['total_rows']/$limit);
            $tlds['offset'] = $offset+1;
            $tlds['page'] = $page;
		}
        return $tlds;
    }
	
	public function getActive() {
		$tlds = $this->db->where('status', 1)->order_by('tld_order', 'asc')->get('tlds')->result_array();
        return $tlds;
    }

    public function getAll() {
		$tlds = $this->db->order_by('tld_order', 'asc')->get('tlds')->result_array();
        return $tlds;
    }

    public function getById($id) {
        if(!$tld = $this->cache->get('tlds-' . $id)) {
            $tld = $this->db->where('id', $id)->get('tlds')->row_array();
            if($tld) {
                $this->cache->save('tlds-' . $id, $tld, 86400 * 30);
            }
        }
        return $tld;
    }

    public function is_valid_tld($ext) {
        $ext = '.' .$ext;
        if($tld = $this->db->where('tld', $ext)->get('tlds')->row_array())
        return $tld;
        return false;
    }

    public function getByExtension($ext) {
        $ext = '.' . trim($ext, ". \t\n\r\0\x0B");
        $tlds = $this->getAll();
        foreach($tlds as $tld) {
            if($tld['tld'] == $ext)
                return $this->getById($tld['id']);
        }
        return null;
    }

    public function getMainTld() {
        if($main_tld = $this->db->where('is_main', 1)->get('tlds')->row_array())
        return $main_tld;
        return null;
    }

    public function split_url($url) {
		$finalized = false;
		$url = clean_url($url);
		$host = false;
		$tld = false;
		$url_array = array();
        $tld_data = array();
		while(!$finalized) {
		if($url_parts = explode(".",$url,2)) {
			if(count($url_parts)>1) {
			if(isset($url_parts[count($url_parts)-1])) {
				if($tld_data = $this->is_valid_tld($url_parts[count($url_parts)-1])) {
				$url_array['host'] = trim($url_parts[count($url_parts)-2], '-.');
				$url_array['tld'] = trim($url_parts[count($url_parts)-1], '-.');
                $url_array['tld_data'] = $tld_data;
                $url_array['domain'] = $url_array['host'] . "." . $url_array['tld'];
				$finalized = true;
				} else {
					$url = clean_url($url_parts[count($url_parts)-1]);
				}
			}
			} else {
				$url_array['host'] = trim($url_parts[0], '-.');
                $tld_data = $this->getMainTld();
				$url_array['tld'] = ltrim($tld_data['tld'],".");
                $url_array['tld_data'] = $tld_data;
                $url_array['domain'] = $url_array['host'] . "." . $url_array['tld'];
				$finalized = true;
			}
		}
	}
		return $url_array;
	}

    public function add($tld, $whois_server, $pattern, $is_main, $is_suggested, $price, $sale_price, $affiliate_link, $status = true) {
		$tld_data = array();
		if(!$tld_data = $this->db->where('tld',$tld)->get('tlds')->row_array()) {
			$this->db->insert('tlds', [
				'tld' => $tld,
				'whois_server' => $whois_server,
				'pattern' => $pattern,
				'is_suggested' => $is_suggested,
				'price' => $price,
				'sale_price' =>  $sale_price,
				'affiliate_link' => $affiliate_link,
				'status' => $status,
				'tld_order' => $this->get_new_tld_order()
			]);
			$id = $this->db->insert_id();
			if($is_main) {
				$this->set_main_tld($id);
			}
			$this->cache->delete('suggested_tlds');
			$this->cache->delete('tlds');
			return $id;
		} else {
			$this->edit($tld_data['id'], $tld, $whois_server, $pattern, $is_main, $is_suggested, $price, $sale_price, $affiliate_link, $status);
			return $tld_data['id'];
		}
    }

    public function edit($id, $tld, $whois_server, $pattern, $is_main, $is_suggested, $price, $sale_price, $affiliate_link, $status = true) {
        $this->db->where('id', $id)->set([
            'tld' => $tld,
            'whois_server' => $whois_server,
            'pattern' => $pattern,
            'is_suggested' => $is_suggested,
            'price' => $price,
            'sale_price' =>  $sale_price,
            'affiliate_link' => $affiliate_link,
            'status' => $status,
        ])->update('tlds');

        if($is_main) {
            $this->set_main_tld($id);
        }

        $this->cache->delete('suggested_tlds');
        $this->cache->delete('tlds-' . $id);
        $this->cache->delete('tlds');
        return true;
    }

    public function delete($id) {
        $this->db->where('id', $id)->delete('tlds');
        $this->cache->delete('suggested_tlds');
        $this->cache->delete('tlds-' . $id);
        $this->cache->delete('tlds');
        return true;
    }

    public function update_status($id, $status) {
        $this->db->where('id', $id)->set('status', $status)->update('tlds');
        $this->cache->delete('suggested_tlds');
        $this->cache->delete('tlds-' . $id);
        $this->cache->delete('tlds');
    }

    public function update_suggested($id, $status) {
        $this->db->where('id', $id)->set('is_suggested', $status)->update('tlds');
        $this->cache->delete('suggested_tlds');
        $this->cache->delete('tlds-' . $id);
        $this->cache->delete('tlds');
    }

    public function set_main_tld($id) {
        $this->db->set('is_main', 0)->update('tlds');
        $this->db->where('id', $id)->set('is_main', 1)->update('tlds');
        $this->delete_all_cache();
        return true;
    }

    public function delete_all_cache() {
        $this->cache->delete('tlds');
        $this->cache->delete('suggested_tlds');
        foreach($this->getAll() as $tld) {
            $this->cache->delete('tlds-' . $tld['id']);
        }
    }

    public function set_order($order_ids) {
        foreach($order_ids as $order => $id) {
            if(!$this->db
            ->where('id', $id)
            ->set('tld_order', $order)
            ->update('tlds'))
                return false;
        }
        $this->cache->delete('suggested_tlds');
        $this->cache->delete('tlds');
        return true;
    }

    public function get_new_tld_order() {
        $tlds = $this->getAll();
        if(count($tlds)) {
            $latest = array_pop($tlds);
            return ($latest['tld_order'] + 1);
        }
        return 0;
    }

    public function replace_affiliate_link($link) {
        $this->db->set('affiliate_link', $link)->update('tlds');
        $this->delete_all_cache();
        return true;
    }

    public function get_suggested_tlds() {
        return $this->db->where([
            'status' => TRUE,
            'is_suggested' => TRUE
        ])->order_by('tld_order', 'ASC')->get('tlds')->result_array();
    }

    public function get_suggested_tld_string() {
        if(!$str = $this->cache->get('suggested_tlds')) {
            $tlds = $this->get_suggested_tlds();
            $str = [];
            foreach($tlds as $tld) {
                $str[] = trim($tld['tld'], ". \t\n\r\0\x0B");
            }
            $str = join(',', $str);
            $this->cache->save('suggested_tlds', $str, 86400);
        }
        return $str;
    }

    public function get_generator_tlds() {
        return $this->db->order_by('order', 'ASC')->get('domain_generator')->result_array();
    }

    public function get_generator_tld($id) {
        return $this->db->where('id', $id)->get('domain_generator')->row_array();
    }

    public function add_generator_tld($extension) {
        $this->db->insert('domain_generator', [
            'id' => NULL,
            'tld' => '.' . trim($extension, ". \t\n\r\0\x0B"),
            'default' => TRUE,
            'order' => $this->get_new_generator_order()
        ]);
    }

    public function delete_generator_tld($id) {
        $this->db->where('id', $id)->delete('domain_generator');
    }

    public function get_new_generator_order() {
        if($row = $this->db->select('order')->order_by('order', 'DESC')->limit(1)->get('domain_generator')->row_array()) {
            return $row['order'] + 1;
        }

        return 0;
    }

    public function set_generator_order($order_ids) {
        foreach($order_ids as $order => $id) {
            if(!$this->db
            ->where('id', $id)
            ->set('order', $order)
            ->update('domain_generator'))
                return false;
        }
        return true;
    }

    public function update_generator_status($id, $status) {
        $this->db->where('id', $id)->set('default', $status)->update('domain_generator');
    }

    public function get_whois_tlds() {
        return $this->db->order_by('order', 'ASC')->get('domain_whois')->result_array();
    }

    public function get_whois_tld($id) {
        return $this->db->where('id', $id)->get('domain_whois')->row_array();
    }

    public function add_whois_tld($extension) {
        $this->db->insert('domain_whois', [
            'id' => NULL,
            'tld' => '.' . trim($extension, ". \t\n\r\0\x0B"),
            'order' => $this->get_new_whois_order()
        ]);
    }

    public function delete_whois_tld($id) {
        $this->db->where('id', $id)->delete('domain_whois');
    }

    public function get_new_whois_order() {
        if($row = $this->db->select('order')->order_by('order', 'DESC')->limit(1)->get('domain_whois')->row_array()) {
            return $row['order'] + 1;
        }
        return 0;
    }

    public function set_whois_order($order_ids) {
        foreach($order_ids as $order => $id) {
            if(!$this->db
            ->where('id', $id)
            ->set('order', $order)
            ->update('domain_whois'))
                return false;
        }
        return true;
    }
}