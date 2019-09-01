<?php
class Article_model extends CI_Model {
    function __construct() {
        $this->load->database(); 
    }
  
    public function getArticles() {
      $this->db->select();
      $this->db->from('demo');
      $query = $this->db->get();
      return $query->result_array();
    }

    public function saveArticle($article_arr) {
        $this->db->insert('demo', $article_arr);
    }

    public function updateArticle($article_arr, $id) {
      echo "<pre>"; print_r($article_arr); exit;
     $this->db->update('demo', $article_arr, array('id'=>$id));
    }

    function deleteArticle($id){
       $this->db->delete('demo', array('id'=>$id));
    }
}

