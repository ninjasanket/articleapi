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
            $data = $this->article->getArticles($id);
        }else{
            $data = $this->article->getArticles();
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
        $resp = $this->article->saveArticle($input);    
        $this->response($resp, REST_Controller::HTTP_OK);
    } 
     
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_put($id)
    {
        $input = $this->put();
        $resp = $this->article->updateArticle($input, $id);
     
        $this->response($resp, REST_Controller::HTTP_OK);
    }
     
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_delete($id)
    {
        $resp = $this->article->deleteArticle($id); 
        $this->response($resp, REST_Controller::HTTP_OK);
    }
        
}
