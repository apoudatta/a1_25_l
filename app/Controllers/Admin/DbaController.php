<?php
/*
|---------------------------------------
| Module Name     : DBA
|---------------------------------------
| Copyright       : Arena Phone BD Ltd.  
| Created on      : 2025                 
| Developed By    : apoudatto6@gmail.com
|---------------------------------------
*/

namespace App\Controllers\Admin;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Controllers\BaseController;
use CodeIgniter\Validation\Validation;
use CodeIgniter\Session\Session;
use CodeIgniter\Controller;
use CodeIgniter\Database\BaseConnection;

class DbaController extends BaseController
{
	protected $helpers = ['form'];
	protected $session;
	private $email;
	public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
	{
		parent::initController($request, $response, $logger);
		$request	= \Config\Services::request();
		$this->session = service('session');
		//dd($session->get('employeeno'));
		if ($this->session->get('user_id') !== env('DBA_USER')) {
			exit('Access Denied!');
		}
		$this->db = \Config\Database::connect(); // Load database
	}

	public function index()
	{
		//$this->dd($this->session->get());
		return view('admin/Dba/index', ['data' => null]);
	}

	public function execute()
	{
		//echo "ddddddddddd";exit;
		$request = service('request');
		$queryString = trim($request->getPost('query_string'));
		//dd($queryString);
		if (empty($queryString)) {
			return view('admin/Dba/index', ['message' => ['error' => 'Query cannot be empty!']]);
		}
		$return_query = $queryString;
		if (preg_match('/^(SELECT)/i', $queryString)) {
			$queryString .= " LIMIT 5000";
		}

		try {
			$query = $this->db->query($queryString);
			if ($this->db->affectedRows() > 0) {
				if (preg_match('/^(SELECT|SHOW)/i', $queryString)) {
					$data['query_result'] = $query->getResultArray();
				}else{
					$data['message'] = 'Query executed successfully. Rows affected: ' . $this->db->affectedRows();
				}
			} else {
				$data['message'] = 'Query execution successfully! but result not found!';
			}
		} catch (\Throwable $e) {
			//dd($e);
			$data['message'] = 'Query execution failed! ' . $e->getMessage();
		}
		$data['query_string'] = $return_query;
		return view('admin/Dba/index', $data);
	}

	public function phpinfo() {
		phpinfo();
	}

	public function testdata() {
		$this->dd("testdata");
	}
}

