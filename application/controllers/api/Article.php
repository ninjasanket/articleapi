<?php
   
require APPPATH . 'libraries/REST_Controller.php';
     
class Article extends REST_Controller {
    
      /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function __construct() {
       parent::__construct();
       $this->load->model('article_model', 'article'); 
    }
       
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_get($id = 0)
    {
        if(!empty($id)){
            $data = $this->db->get_where("items", ['id' => $id])->row_array();
        }else{
            $data =  $this->article->getArticles();
            #$this->db->get("demo")->result();
        }
     
        $this->response($data, REST_Controller::HTTP_OK);
    }
      
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_post()
    {
        $input = $this->input->post();
        echo "<pre>"; print_r($input); 
        $this->article->saveArticle($input);    
        $this->response(['Item created successfully.'], REST_Controller::HTTP_OK);
    } 
     
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_put($id)
    {
        $input = $this->put();
        #echo "<pre>"; print_r($input);exit;
        $this->article->updateArticle($input, $id);
     
        $this->response(['Item updated successfully.'], REST_Controller::HTTP_OK);
    }
     
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_delete($id)
    {
        $this->article->deleteArticle($id); 
        $this->response(['Item deleted successfully.'], REST_Controller::HTTP_OK);
    }
        
}
