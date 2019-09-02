<?php
class Article_model extends CI_Model
{
    public function __construct()
    {
    }

    /**
    #TODO: 1. limit offeset
           2. add dates
     */

    public function getArticles($id)
    {
        if (($id && !intval($id) ) || $this->checkArticleExists($id) == false) {
            return array('status' => false, 'message' => 'Invalid article id provided.');
        }
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
        $api_resp = $this->formatResponse($article_data, $single);
        return $api_resp;
    }

    public function formatResponse($article_result, $single)
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
        return ($single) ? $response : $resp;
    }

    public function getKeyswords($id)
    {
        $keywords = $this->db->select('keyword')->from('article_keywords')->where('article_id', $id)->get()->result_array();
        if (!empty($keywords)) {
            $keywords = array_map(function ($arr) {
                return $arr['keyword'];
            }, $keywords);
        }
        return (!empty($keywords)) ? $keywords : array();
    }
    public function saveArticle($article_arr)
    {
        $validate_resp = $this->validatePostData($article_arr);
        if ($validate_resp['status'] == true) {
            $map['article_id'] = $this->saveArticleData('insert', $article_arr['article']);
            $map['author_id'] = $this->saveAuthorData('insert', $article_arr['author']);
            $map['publisher_id'] = $this->savePublisherData('insert', $article_arr['publisher']);

            $this->saveArticleMap($map);
            if (count($article_arr['keywords']) > 0 && $map['article_id']) {
                $this->saveKeywords('insert', $article_arr['keywords'], $map['article_id']);
            }
            return array('status' => true, 'msg' => 'Article saved successfully.', 'articleId' => (int) $map['article_id']);
        } else {
            return $validate_resp;
        }
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
    public function saveArticleData($action, $article, $id = 0)
    {
        if ($action == 'insert') {
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
        } else if ($action == 'update' && intval($id) > 0) {
            $this->db->update('article', $article, array('id' => $id));
        }
    }
    public function saveAuthorData($action, $author, $id = 0)
    {
        if ($action == 'insert') {
            $result = $this->db->select('id')->from('article_author')->where('name', trim($author['name']))->get()->row_array();
            if ($result['id']) {
                return $result['id'];
            }
            $insert_arr = array(
                'name' => addslashes(trim($author['name'])),
                'url' => trim($author['url']),
            );
            $this->db->insert('article_author', $insert_arr);
            return $this->db->insert_id();
        } else if ($action == 'update' && intval($id) > 0) {
            $this->db->update('article_author', $author, array('id' => $id));
        }
    }
    public function savePublisherData($action, $publisher, $id = 0)
    {
        if ($action == 'insert') {
            $result = $this->db->select('id')->from('article_publisher')->where('name', trim($publisher['name']))->get()->row_array();
            if ($result['id']) {
                return $result['id'];
            }
            $insert_arr = array(
                'name' => addslashes(trim($publisher['name'])),
                'logo_url' => trim($publisher['logo']),
                'url' => trim($publisher['url']),
                'width' => trim($publisher['width']),
                'height' => trim($publisher['height']),
            );
            $this->db->insert('article_publisher', $insert_arr);
            return $this->db->insert_id();
        } else if ($action == 'update' && intval($id) > 0) {
            $this->db->update('article_publisher', $publisher, array('id' => $id));
        }
    }
    public function saveArticleMap($map)
    {
        $this->db->insert('article_map', $map);
    }
    public function saveKeywords($action, $keywords, $id)
    {
        if ($action == 'update' && $id) {
            $this->db->where('article_id', $id);
            $this->db->delete('article_keywords');
        } 
        $insert_data = array();
        foreach ($keywords as $keyword) {
            $insert_data[] = array('article_id' => $id, 'keyword' => $keyword);
        }
        $this->db->insert_batch('article_keywords', $insert_data);
    }
    public function updateArticle($article_arr, $id)
    {
        if ($id && !intval($id)) {
            return array('status' => false, 'message' => 'Invalid articleId provided.');
        } 
        if ($this->checkArticleExists($id) == false) {
            return array('status' => false, 'message' => 'Invalid articleId provided.');
        }

            if (!empty($article_arr['article'])) {
                $article_resp = $this->validateFields(array('name', 'image', 'url', 'headline', 'language', 'section', 'body'), $article_arr['article'], 'article');
                if ($article_resp == false) {
                    return array('status' => false, 'message' => 'Invalid data provided for article\'s fields.');
                }
            }

            if (!empty($article_arr['author'])) {
                $author_resp = $this->validateFields(array('name', 'url'), $article_arr['author'], 'author');
                if ($author_resp == false) {
                    return array('status' => false, 'message' => 'Invalid data provided for author\'s fields.');
                }
            }

            if (!empty($article_arr['publisher'])) {
                $publisher_resp = $this->validateFields(array('name', 'logo_url', 'width', 'height', 'url'), $article_arr['publisher'], 'publisher');
                if ($publisher_resp == false) {
                    return array('status' => false, 'message' => 'Invalid data provided for publisher\'s fields.');
                }
            }

            $article_map = $this->getArticlemap($id);
            if (!empty($article_resp)) {
                $this->saveArticleData('update', $article_resp, $id);
            }

            if (!empty($author_resp) && $article_map['author_id']) {
                $this->saveAuthorData('update', $author_resp, $article_map['author_id']);
            }

            if (!empty($publisher_resp) && $article_map['publisher_id']) {
                $this->savePublisherData('update', $publisher_resp, $article_map['publisher_id']);
            }
            if (!empty($article_arr['keywords'])) {
                $this->saveKeywords('update', $article_arr['keywords'], $id);
            } 
            return array('status' => true, 'message' => 'Article updated successfully.');
    }
    public function checkArticleExists($id)
    {
        $id = intval($id);  
        $id = $this->db->select('id')->from('article')->where('id', $id)->get()->row_array();
        return ($id['id']) ? true : false;
    }
    public function validateFields($fields, $data, $key)
    {
        $update_fields = array();
        foreach ($fields as $field) {
            if (!empty($data[$field])) {
                $update_fields[$field] = trim($data[$field]);
            }
        }
        return (!empty($update_fields)) ? $update_fields : false;
    }
    public function getArticlemap($id)
    {
        return $this->db->select('author_id, publisher_id')->from('article_map')->where('article_id', $id)->get()->row_array();
    }
    public function deleteArticle($id)
    {
        if (intval($id) > 0) {
            if ($this->checkArticleExists($id) == true) {
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

