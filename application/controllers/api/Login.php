<?php

require APPPATH . 'libraries/REST_Controller.php';

class Login extends REST_Controller
{

    /**
     * Get All Data from this method.
     *
     * @return Response
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['jwt', 'authorization']);
    }

    /**
     * Get All Data from this method.
     *
     * @return Response
     */

    public function index_post()
    {
        $dummy_user = [
            'username' => 'ninjasanket',
            'password' => 'gravityworrier',
        ];
        $username = $this->post('username');
        $password = $this->post('password');
        if (trim($username) === $dummy_user['username'] && trim($password) === $dummy_user['password']) {
            $token = AUTHORIZATION::generateToken(['username' => $dummy_user['username']]);
            $status = parent::HTTP_OK;
            $response = ['status' => $status, 'token' => $token];
            $this->response($response, $status);
        } else {
            $this->response(['msg' => 'Invalid username or password!'], parent::HTTP_UNAUTHORIZED);
        }
    }
}

