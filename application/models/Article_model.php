<?php
class Article_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    /**
      #TODO: 1. limit offeset
             2. add dates
    */

    public function getArticles($id)
    {
        $this->db->select('a.*, aa.name as authorName, aa.url as authorUrl, ap.name as publisherName, ap.url as publisherUrl, ap.logo_url as logoUrl, ap.width, ap.height');
        $this->db->from('article a');
        $this->db->join('article_map am', 'am.article_id = a.id');
        $this->db->join('article_author aa', 'aa.id = am.author_id');
        $this->db->join('article_publisher ap', 'ap.id = am.publisher_id');
        $this->db->where('active', '1');
        $single = false; 
        if (intval($id) > 0) {
            $this->db->where('a.id', $id);
            $single = true; 
        }
        $article_data = $this->db->get()->result_array();
        if (empty($article_data)) {
            return array('status' => false, 'message' => 'Article does not exists.');  
        }
        $api_resp = $this->formatResponse($article_data, $single);
        return $api_resp;
    }

    public function formatResponse($article_result)
    {
        $response = $resp = array();
        foreach ($article_result as $article) {
            $response['image'] = $article['image'];
            $response['url'] = $article['url'];
            $response['headline'] = $article['headline'];
            $response['inLanguage'] = $article['language'];
            $response['contentLocation'] = array('name' => $article['name']);
            $response['author'] = array('name' => $article['authorName'], 'url' => $article['authorUrl']);
            $response['publisher'] = array('name' => $article['publisherName'], 'url' => $article['publisherUrl'], 'logo' => array('url' => $article['logoUrl'], 'width' => $article['width'], 'height' => $article['height']));
            $response['keywords'] = $this->getKeyswords($article['id']);
            $response['articleSection'] = $article['section'];
            $response['articleBody'] = $article['body'];
            $resp[] = $response;
        }
        return ($single)? $response : $resp;
    }

    public function getKeyswords($id){
        $keywords = $this->db->select('keyword')->from('article_keywords')->where('article_id', $id)->get()->result_array();
        if (!empty($keywords)) {
           $keywords = array_map(function($arr) {
                           return $arr['keyword'];  
                       }, $keywords);
        }
        return (!empty($keywords)) ? $keywords : array();
    }
    public function saveArticle($article_arr)
    {
        $validate_resp = $this->validatePostData($article_arr);
        if ($validate_resp['status'] == true) {
            $map['article_id'] = $this->saveArticleData($article_arr['article'], 'insert');
            $map['author_id'] = $this->saveAuthorData($article_arr['author'], 'insert');
            $map['publisher_id'] = $this->savePublisherData($article_arr['publisher'], 'insert');

            $this->saveArticleMap($map);
            if (count($article_arr['keywords']) > 0 && $map['article_id']) {
                $this->saveKeywords($article_arr['keywords'], $map['article_id']);
            }
            return array('status' => true, 'msg' => 'Article saved successfully.', 'articleId' => (int) $map['article_id']);
        } else {
            return $validate_resp;
        }
    }

    public function saveArticleMap($map)
    {
        $this->db->insert('article_map', $map);
    }

    public function saveArticleData($article)
    {
        $insert_arr = array(
            'name' => trim($article['name']),
            'image' => trim($article['image']),
            'url' => trim($article['url']),
            'headline' => trim($article['headline']),
            'language' => trim($article['language']),
            'section' => trim($article['section']),
            'body' => trim($article['body']),
        );
        $this->db->insert('article', $insert_arr);
        return $this->db->insert_id();
    }
    public function saveAuthorData($author, $action)
    {

        $result = $this->db->select('id')->from('article_author')->where('name', trim($author['name']))->get()->row_array();
        if ($action == 'insert' && $result['id']) {
            return $result['id'];
        }

        if (!$result['id']) {
            $insert_arr = array(
                'name' => addslashes(trim($author['name'])),
                'url' => trim($author['url']),
            );
            $this->db->insert('article_author', $insert_arr);
            return $this->db->insert_id();
        } else {
            $this->db->update('article_author', $author);
            $this->db->where('id', $result['id']);
        }
    }
    public function savePublisherData($publisher, $action)
    {
        $result = $this->db->select('id')->from('article_publisher')->where('name', trim($publisher['name']))->get()->row_array();

        if ($action == 'insert' && $result['id']) {
            return $result['id'];
        }
        if (!$result['id']) {
            $insert_arr = array(
                'name' => addslashes(trim($publisher['name'])),
                'logo_url' => trim($publisher['logo']),
                'width' => trim($publisher['width']),
                'height' => trim($publisher['height']),
            );
            $this->db->insert('article_publisher', $insert_arr);
            return $this->db->insert_id();
        } else {
            $this->db->update('article_publisher', $publisher);
            $this->db->where('id', $result['id']);
        }
    }

    public function saveKeywords($keywords, $id)
    {
        $this->db->where('article_id', $id);
        $this->db->delete('article_keywords');
        $insert_data = array();
        foreach ($keywords as $keyword) {
            $insert_data[] = array('article_id' => $id, 'keyword' => $keyword);
        }
        $this->db->insert_batch('article_keywords', $insert_data);
    }

    public function validatePostData($post_data)
    {

        $required = array(
            'article' => array('image', 'url', 'headline', 'name', 'language', 'section', 'body', 'publishedDate'),
            'author' => array('name', 'url'),
            'publisher' => array('name', 'url'),
        );
        $validate_resp = array('status' => true);
        foreach ($required as $k => $v) {
            $validate_resp = $this->validate($post_data[$k], $required[$k], $k);
            if ($validate_resp['status'] == false) {
                return $validate_resp;
            }
        }
        return $validate_resp;
    }

    public function validate($data, $validator, $k)
    {
        foreach ($validator as $v) {
            if (empty($data[$v])) {
                return array('status' => false, 'msg' => "$k $v is required field");
            }
        }
        return array('status' => true);
    }

    public function updateArticle($article_arr, $id)
    {
        echo "<pre>"; print_r($article_arr);exit;
        $this->db->update('demo', $article_arr, array('id' => $id));
    }

    public function deleteArticle($id)
    {
        if (intval($id) > 0) {
            $article = $this->db->select('id')->from('article')->where('id', $id)->get()->row_array();
            if ($article['id']) {
                $this->db->where('id', $id);
                $this->db->update('article', array('active' => '0'));
                return array('status' => false, 'msg' => 'Article deleted successfully.');
            } else {
                return array('status' => false, 'msg' => 'Article not exist in system.');
            }
        } else {
            return array('status' => false, 'msg' => 'Invalid article Id.');
        }
    }
}

