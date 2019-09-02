<?php

require APPPATH . 'libraries/REST_Controller.php';

class Article extends REST_Controller
{

    /**
     * Get All Data from this method.
     *
     * @return Response
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('article_model', 'article');
        $this->load->helper(['jwt', 'authorization']);
        if (!$this->validateRequest()) {
            exit;
        }
    }

    /**
     * Get All Data from this method.
     *
     * @return Response
     */
    public function validateRequest()
    {
        $headers = $this->input->request_headers();
        $token = $headers['Authorization'];
        try {
            $data = AUTHORIZATION::validateToken($token);
            if ($data === false) {
                $response = array('message' => 'Unauthorized Access', 'type' => 'OAuthException', 'code' => REST_Controller::HTTP_UNAUTHORIZED, 'status' => false);
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            } else {
                return $data;
            }
        } catch (Exception $e) {
            $response = array('message' => 'Unauthorized Access', 'type' => 'OAuthException', 'code' => REST_Controller::HTTP_UNAUTHORIZED, 'status' => false);
            header('Content-Type: application/json');
            echo json_encode($response);
        }
    }

    public function index_get($id = 0)
    {
        if (!empty($id)) {
            $resp = $this->article->getArticles($id);
        } else {
            $resp = $this->article->getArticles();
        }
        $resp['code'] = REST_Controller::HTTP_OK;
        $this->response($resp, REST_Controller::HTTP_OK);
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
        $resp['code'] = REST_Controller::HTTP_OK;
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
        $resp['code'] = REST_Controller::HTTP_OK;
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
        $resp['code'] = REST_Controller::HTTP_OK;
        $this->response($resp, REST_Controller::HTTP_OK);
    }

}
