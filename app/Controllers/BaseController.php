<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;
    protected $response;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = service('session');
        // $this->request = $request;
        $this->response = service('response');
        $this->request = service('request');
        
    }

    protected function getUserData()
    {
        return $this->request->getServer('jwtUser');
    }

    protected function authUserName()
    {
        return strtoupper($this->getUserData()['name']) ?? null;
    }

    protected function authUserId()
    {
        return $this->getUserData()['id'] ?? null;
    }

    protected function validateWithLabels(array $data, object $request): bool|array
    {
        $rules  = $request->rules($data);
        $labels = method_exists($request, 'labels') ? $request->labels($data) : [];

        $validation = \Config\Services::validation();
        foreach ($rules as $field => $rule) {
            $label = $labels[$field] ?? $field;
            $validation->setRule($field, $label, $rule);
        }

        if (!$validation->run($data)) {
            return $validation->getErrors();
        }

        return true;
    }
}
