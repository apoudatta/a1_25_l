<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

class Adfs extends BaseController
{
  use ResponseTrait;

  protected $userModel;
  protected $db;

  public function __construct()
  {
      $this->userModel = new UserModel();
      $this->db        = \Config\Database::connect();
  }

  public function syncUsers()
  {
    try {
        // 1) Fetch from Graph
        $allApiUsers = $this->getAllApiUsers(); // returns array of arrays

        // 2) Load existing DB users keyed by azure_id
        $dbUsers      = $this->userModel
                            ->select('id, azure_id')
                            ->where('login_method', 'SSO')
                            ->findAll();
        $dbByAzure    = array_column($dbUsers, null, 'azure_id');

        $inserted = $updated = 0;

        // 3) Upsert
        $this->db->transStart();
        foreach ($allApiUsers as $api) {
            $azureId = $api['id'];

            $display_name = $api['displayName'];

            // 1) Split off the “(…)” part
            $parts      = explode('(', $display_name, 2);
            $name       = trim($parts[0]);

            // 2) If there was a parenthetical section, parse it
            $designation = $division = '';
            if (isset($parts[1])) {
                // remove the trailing “)”
                $inside = rtrim($parts[1], ')');
                // split by commas, trim each piece
                $pieces = array_map('trim', explode(',', $inside));
                // first piece is designation
                $designation = $pieces[0] ?? '';
                // last piece is division
                $division    = end($pieces) ?? '';
            }

            $data    = [
                'employee_id'     => $api['postalCode'] ?? null,
                'azure_id'        => $azureId,
                'name'            => $name ?? null,
                'email'           => $api['mail'] ?? null,
                'phone'           => ! empty($api['businessPhones'][0])
                                    ? $api['businessPhones'][0]
                                    : ($api['mobilePhone'] ?? null),
                'department'      => $api['officeLocation'] ?? null,
                'designation'     => $designation ?? null,
                'division'        => $division ?? null,
                'user_type'       => 'EMPLOYEE',
                'login_method'    => 'SSO',
                'status'          => 'ACTIVE',
            ];

            if (isset($dbByAzure[$azureId])) {
                //$this->userModel->update($dbByAzure[$azureId]['id'], $data);
                $updated++;
            } else {
                $this->userModel->insert($data);
                $inserted++;
            }
        }
        $this->db->transComplete();

        // 4) Deactivate missing users
        $apiAzureIds     = array_column($allApiUsers, 'id');
        $dbAzureIds      = array_keys($dbByAzure);
        $toDeactivate    = array_diff($dbAzureIds, $apiAzureIds);

        if (! empty($toDeactivate)) {
            $this->userModel
                ->whereIn('azure_id', $toDeactivate)
                ->set(['status' => 'INACTIVE'])
                ->update();
        }

        // 5) Log and respond
        $summary = "UserSync: {$inserted} new, {$updated} updated, ".count($toDeactivate)." deactivated";
        $this->writeAccessLog($summary, 'info');

        return $this->respond([
            'status'  => 'success',
            'inserted'=> $inserted,
            'updated' => $updated,
            'deactivated'=> count($toDeactivate),
        ], 200);
    }
    catch (\Throwable $e) {
        $summary = 'error: UserSync failed: '.$e->getMessage();
        $this->writeAccessLog($summary, 'info');
        return $this->failServerError('User sync error');
    }
  }

  // protected function getAllApiUsers(){
  //   $accessToken = $this->getAccessTokenForAdfsUsers();

  //   // here api response per page 100 items, for the next items we hit same api through nextLink url.
  //   $nextLink = $this->fetchAllUsers($accessToken, $nextLink = null);

  //   $allUsers = [];
  //   while (isset($nextLink['@odata.nextLink'])) {
  //       $allUsers = array_merge($allUsers, $nextLink['value']);
  //       $nextLink = $this->fetchAllUsers($accessToken, $nextLink['@odata.nextLink']);
  //   }
  //   if (isset($nextLink['value'])) {
  //       $allUsers = array_merge($allUsers, $nextLink['value']);
  //   }
  //      return $allUsers;
  // }




  protected function fetchAllUsers($accessToken, $nextLink = null)
  {
    if ($nextLink == null) {
      $groupObjectId = 'b96ae2b7-9c5c-46e2-9eb9-8638a1fe0197';
      $url = "https://graph.microsoft.com/v1.0/groups/{$groupObjectId}/members?\$select=id,businessPhones,displayName,givenName,jobTitle,mail,mobilePhone,officeLocation,preferredLanguage,surname,userPrincipalName,postalCode";
    } else {
      $url = $nextLink;
    }

    $headers = [
      "Authorization: Bearer {$accessToken}",
      "Content-Type: application/json"
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
  }

  protected function getAllApiUsers(): array {
    return [
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '157c25bb-9360-481d-826b-7f444cb97aeb',
            'businessPhones'   => ['01711829269'],
            'displayName'      => 'Syed Naim Ahmed (Deputy General Manager, Merchant Payments, Commercial)',
            'givenName'        => 'Syed Naim',
            'jobTitle'         => 'Deputy General Manager, Merchant Payments',
            'mail'             => 'syed.naimahmed@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Merchant Payments',
            'preferredLanguage'=> '',
            'surname'          => 'Ahmed',
            'userPrincipalName'=> 'syed.naimahmed@bKash.com',
            'postalCode'       => '0137',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '3a446263-d5ef-4bdf-b245-4b7b30003d5b',
            'businessPhones'   => ['01617790605'],
            'displayName'      => 'Shouvik Ghosh (VP, Banking Partnership, Financial Services, Commercial)',
            'givenName'        => 'Shouvik',
            'jobTitle'         => 'VP, Banking Partnership',
            'mail'             => 'shouvik.ghosh@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Financial Services',
            'preferredLanguage'=> '',
            'surname'          => 'Ghosh',
            'userPrincipalName'=> 'shouvik.ghosh@bKash.com',
            'postalCode'       => '0174',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '19d1d852-3bef-414d-b032-1687311318dd',
            'businessPhones'   => ['01815182528'],
            'displayName'      => 'Asadullah Amil (General Manager, Merchant Payments, Commercial)',
            'givenName'        => 'Asadullah',
            'jobTitle'         => 'General Manager, Merchant Payments',
            'mail'             => 'asadullah.amil@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Merchant Payments',
            'preferredLanguage'=> '',
            'surname'          => 'Amil',
            'userPrincipalName'=> 'asadullah.amil@bKash.com',
            'postalCode'       => '0046',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '63f7c364-5a4e-4296-a2b2-5116c265df6e',
            'businessPhones'   => ['01670291482'],
            'displayName'      => 'A.M. Sirajul Mowla (General Manager, Merchant Payments, Commercial)',
            'givenName'        => 'A.M. Sirajul',
            'jobTitle'         => 'General Manager, Merchant Payments',
            'mail'             => 'sirajul.mowla@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Merchant Payments',
            'preferredLanguage'=> '',
            'surname'          => 'Mowla',
            'userPrincipalName'=> 'sirajul.mowla@bKash.com',
            'postalCode'       => '0163',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '263583ee-d80b-4ae1-91c2-e7d4eb9b3ac3',
            'businessPhones'   => ['01914097728'],
            'displayName'      => 'Niaz Morshed Khan (Area Manager, Merchant Development, Commercial)',
            'givenName'        => 'Niaz Morshed',
            'jobTitle'         => 'Area Manager, Merchant Development',
            'mail'             => 'niaz.khan@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Merchant Development',
            'preferredLanguage'=> '',
            'surname'          => 'Khan',
            'userPrincipalName'=> 'niaz.khan@bKash.com',
            'postalCode'       => '0053',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '2033185b-2333-43b0-af54-99b37fdedd2b',
            'businessPhones'   => ['01759382505'],
            'displayName'      => 'Md Fuad Hasan (EVP & HoD, Management Reporting & Planning, Finance & Accounts)',
            'givenName'        => 'Md Fuad',
            'jobTitle'         => 'EVP & Head of Department, Management Reporting & Planning',
            'mail'             => 'fuad.hasan@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Management Reporting & Planning',
            'preferredLanguage'=> '',
            'surname'          => 'Hasan',
            'userPrincipalName'=> 'fuad.hasan@bKash.com',
            'postalCode'       => '0762',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '9704fe7d-ee4b-4cb0-b2b2-a93e3e0efa25',
            'businessPhones'   => ['01730709267'],
            'displayName'      => 'Md. Moniruzzaman (General Manager, Digital, Media & Commercial Procurement, Supply Chain Management, Finance & Accounts)',
            'givenName'        => 'Md.',
            'jobTitle'         => 'General Manager, Digital, Media & Commercial Procurement',
            'mail'             => 'md.moniruzzaman@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Supply Chain Management',
            'preferredLanguage'=> '',
            'surname'          => 'Moniruzzaman',
            'userPrincipalName'=> 'md.moniruzzaman@bKash.com',
            'postalCode'       => '0423',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '233a4b79-f7a4-4739-ad07-db6efd448b23',
            'businessPhones'   => ['01610009076'],
            'displayName'      => 'Md. Shiful Islam (General Manager, Digital, Media & Commercial Procurement, Supply Chain Management, Finance & Accounts)',
            'givenName'        => 'Md. Shiful',
            'jobTitle'         => 'General Manager, Digital, Media & Commercial Procurement',
            'mail'             => 'shiful.islam@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Supply Chain Management',
            'preferredLanguage'=> '',
            'surname'          => 'Islam',
            'userPrincipalName'=> 'shiful.islam@bKash.com',
            'postalCode'       => '0256',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '15cd2363-2cd8-47a2-b973-68a5fb94a47b',
            'businessPhones'   => ['01822211144'],
            'displayName'      => 'Faisal Mahmud Rony (General Manager, SC Governance & Contract Management, Supply Chain Management, Finance & Accounts)',
            'givenName'        => 'Faisal Mahmud',
            'jobTitle'         => 'General Manager, SC Governance & Contract Management',
            'mail'             => 'faisal.mahmud@bkash.com',
            'mobilePhone'      => '01822211144',
            'officeLocation'   => 'Supply Chain Management',
            'preferredLanguage'=> '',
            'surname'          => 'Rony',
            'userPrincipalName'=> 'faisal.mahmud@bKash.com',
            'postalCode'       => '0061',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '7e549413-b631-4c4b-ac3c-81ec5faaec9e',
            'businessPhones'   => ['01939900892'],
            'displayName'      => 'Minhazul Huq (Deputy General Manager, Windows Infrastructure Operations, IT Governance, Product & Technology)',
            'givenName'        => 'Minhazul',
            'jobTitle'         => 'Deputy General Manager, Windows Infrastructure Operations',
            'mail'             => 'minhazul.huq@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'IT Governance',
            'preferredLanguage'=> '',
            'surname'          => 'Huq',
            'userPrincipalName'=> 'minhazul.huq@bKash.com',
            'postalCode'       => '0509',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => 'a0476068-7254-4b3b-97f2-e051db150366',
            'businessPhones'   => ['01817182787'],
            'displayName'      => 'Meer Minhas Uddin Ahmed (SVP, Head of Technology & General Procurement, Supply Chain Management, Finance & Accounts)',
            'givenName'        => 'Meer Minhas Uddin',
            'jobTitle'         => 'SVP, Head of Technology & General Procurement',
            'mail'             => 'meer.ahmed@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Supply Chain Management',
            'preferredLanguage'=> '',
            'surname'          => 'Ahmed',
            'userPrincipalName'=> 'meer.ahmed@bKash.com',
            'postalCode'       => '0684',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '7c4a2b0e-e540-40b5-8bf2-e3649f681850',
            'businessPhones'   => ['01713005588'],
            'displayName'      => 'Moinuddin Mohammed Rahgir (Chief Financial Officer)',
            'givenName'        => 'Moinuddin Mohammed',
            'jobTitle'         => 'Chief Financial Officer',
            'mail'             => 'moinuddin.mohammed@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => '',
            'preferredLanguage'=> '',
            'surname'          => 'Rahgir',
            'userPrincipalName'=> 'moinuddin.mohammed@bKash.com',
            'postalCode'       => '0202',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => 'b0b9f23a-9d80-498e-beac-3e4c39cebf9d',
            'businessPhones'   => ['01715623552'],
            'displayName'      => 'Md. Mustakim Hasnine (VP, Head of Digital, Media & Commercial Procurement, Supply Chain Management, Finance & Accounts)',
            'givenName'        => 'Md. Mustakim',
            'jobTitle'         => 'VP, Head of Digital, Media & Commercial Procurement',
            'mail'             => 'mustakim.hasnine@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Supply Chain Management',
            'preferredLanguage'=> '',
            'surname'          => 'Hasnine',
            'userPrincipalName'=> 'mustakim.hasnine@bKash.com',
            'postalCode'       => '0135',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '5ab0758d-58b0-4682-b5cf-2848b897dc6c',
            'businessPhones'   => ['01720002023'],
            'displayName'      => 'A. S. M. Zakir Hossain (Deputy General Manager, Windows Infrastructure Operations, IT Governance, Product & Technology)',
            'givenName'        => 'Zakir',
            'jobTitle'         => 'Deputy General Manager, Windows Infrastructure Operations',
            'mail'             => 'hossain.zakir@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'IT Governance',
            'preferredLanguage'=> '',
            'surname'          => 'Hossain',
            'userPrincipalName'=> 'hossain.zakir@bKash.com',
            'postalCode'       => '1380',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '6052e5bf-8585-48cb-8f21-d0535a31ea6c',
            'businessPhones'   => ['01318214641'],
            'displayName'      => 'Saifur Rahman (Deputy General Manager, Windows Infrastructure Operations, IT Governance, Product & Technology)',
            'givenName'        => 'Saifur',
            'jobTitle'         => 'Deputy General Manager, Windows Infrastructure Operations',
            'mail'             => 'rahman.saifur@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'IT Governance',
            'preferredLanguage'=> '',
            'surname'          => 'Rahman',
            'userPrincipalName'=> 'rahman.saifur@bKash.com',
            'postalCode'       => '0927',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '78cb94ae-cdc0-45f3-9a30-cd6281476efc',
            'businessPhones'   => ['01911400080'],
            'displayName'      => 'Mohammad Shaedul Alam (General Manager, Merchant Payments, Commercial)',
            'givenName'        => 'Mohammad Shaedul',
            'jobTitle'         => 'General Manager, Merchant Payments',
            'mail'             => 'shaedul.alam@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Merchant Payments',
            'preferredLanguage'=> '',
            'surname'          => 'Alam',
            'userPrincipalName'=> 'shaedul.alam@bKash.com',
            'postalCode'       => '0162',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '341a2cf5-3b20-4056-8cf3-8cfb903a7614',
            'businessPhones'   => ['01610002120'],
            'displayName'      => 'Faisal Bin Raihan (General Manager, Technology & General Procurement, Supply Chain Management, Finance & Accounts)',
            'givenName'        => 'Faisal Bin',
            'jobTitle'         => 'General Manager, Technology & General Procurement',
            'mail'             => 'faisal.raihan@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Supply Chain Management',
            'preferredLanguage'=> '',
            'surname'          => 'Raihan',
            'userPrincipalName'=> 'faisal.raihan@bKash.com',
            'postalCode'       => '0252',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '02853841-9ea6-4e47-95b9-8d8d31cbd1ab',
            'businessPhones'   => ['01710274041'],
            'displayName'      => 'Halimuzzaman Mahmud (Senior Lead Engineer, Service Operations, Product & Technology)',
            'givenName'        => 'Halimuzzaman',
            'jobTitle'         => 'Senior Lead Engineer, Service Operations',
            'mail'             => 'h.mahmud@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Service Operations',
            'preferredLanguage'=> '',
            'surname'          => 'Mahmud',
            'userPrincipalName'=> 'h.mahmud@bKash.com',
            'postalCode'       => '0290',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '88a521bc-d79d-478c-b9ae-29208b666535',
            'businessPhones'   => ['01711500337'],
            'displayName'      => 'Nishat Rahman (Chief Customer Service Officer)',
            'givenName'        => 'Nishat',
            'jobTitle'         => 'Chief Customer Service Officer',
            'mail'             => 'nishat.rahman@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => '',
            'preferredLanguage'=> '',
            'surname'          => 'Rahman',
            'userPrincipalName'=> 'nishat.rahman@bKash.com',
            'postalCode'       => '0147',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => 'f47db5bb-1ba4-4d68-bf5d-cea9ae4258ae',
            'businessPhones'   => ['01719304613'],
            'displayName'      => 'Md. Mahabub Alam (Regional AML Compliance Officer, AML&CFT, External & Corporate Affairs)',
            'givenName'        => 'Md. Mahabub',
            'jobTitle'         => 'Regional AML Compliance Officer, AML&CFT',
            'mail'             => 'md.mahabubalam@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'AML&CFT',
            'preferredLanguage'=> '',
            'surname'          => 'Alam',
            'userPrincipalName'=> 'md.mahabubalam@bKash.com',
            'postalCode'       => '1107',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => 'bc8b52a8-3aed-4685-b3a9-90b2b500ec70',
            'businessPhones'   => [],
            'displayName'      => 'Major General Sheikh Md Monirul Islam (retd) (Chief External & Corporate Affairs Officer)',
            'givenName'        => 'Major General Sheikh Md Monirul',
            'jobTitle'         => 'Chief External & Corporate Affairs Officer',
            'mail'             => 'mdmonirul.islam@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => '',
            'preferredLanguage'=> '',
            'surname'          => 'Islam (retd)',
            'userPrincipalName'=> 'mdmonirul.islam@bKash.com',
            'postalCode'       => '0440',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => 'd25dc211-3e29-41c9-ac1f-588c3d36e79b',
            'businessPhones'   => [],
            'displayName'      => 'Mohammad Rashedul Alam (EVP & Head of Department, Supply Chain Management, Finance & Accounts)',
            'givenName'        => 'Mohammad Rashedul',
            'jobTitle'         => 'EVP & Head of Department, Supply Chain Management',
            'mail'             => 'rashedul.alam@bkash.com',
            'mobilePhone'      => '+8801819210330',
            'officeLocation'   => 'Supply Chain Management',
            'preferredLanguage'=> '',
            'surname'          => 'Alam',
            'userPrincipalName'=> 'rashedul.alam@bKash.com',
            'postalCode'       => '0524',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => 'c2575b17-df8e-4fe2-8f50-dde0d1e2617d',
            'businessPhones'   => ['01818857685'],
            'displayName'      => 'Md. Mashe Ur Rahman (EVP & HoD, Legal, Legal, Secretarial & Corporate Governance)',
            'givenName'        => 'Md. Mashe Ur',
            'jobTitle'         => 'EVP & Head of Department, Legal',
            'mail'             => 'masheur.rahman@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Legal',
            'preferredLanguage'=> '',
            'surname'          => 'Rahman',
            'userPrincipalName'=> 'masheur.rahman@bKash.com',
            'postalCode'       => '0589',
        ],    
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => 'b6ad65af-f434-4ea4-ad4c-8aa93df41504',
            'businessPhones'   => ['01670987001'],
            'displayName'      => 'Md. Raisul Islam (Field Compliance Assessment Officer, AML&CFT, External & Corporate Affairs)',
            'givenName'        => 'Md. Raisul',
            'jobTitle'         => 'Field Compliance Assessment Officer, AML&CFT',
            'mail'             => 'raisul.islam@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'AML&CFT',
            'preferredLanguage'=> '',
            'surname'          => 'Islam',
            'userPrincipalName'=> 'raisul.islam@bKash.com',
            'postalCode'       => '1049',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => 'ba5cf215-1932-4cea-8f51-f900aef64d99',
            'businessPhones'   => ['01711083668'],
            'displayName'      => 'Nargis Farhana (SVP, Head of Policy, Process & Procedure Risk Management, Enterprise Risk Management)',
            'givenName'        => 'Nargis',
            'jobTitle'         => 'SVP, Head of Policy, Process & Procedure Risk Management',
            'mail'             => 'nargis.farhana@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Policy, Process & Procedure Risk Management',
            'preferredLanguage'=> '',
            'surname'          => 'Farhana',
            'userPrincipalName'=> 'nargis.farhana@bKash.com',
            'postalCode'       => '1087',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '6c0d5f6a-ebc2-4087-8cc4-4a838742fa7a',
            'businessPhones'   => ['01713409321'],
            'displayName'      => 'Md Mozammel Haque (EVP & HoD, Research & Engineering, Product & Technology)',
            'givenName'        => 'Md Mozammel',
            'jobTitle'         => 'EVP & Head of Department, Research & Engineering',
            'mail'             => 'mozammel.haque@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Research & Engineering',
            'preferredLanguage'=> '',
            'surname'          => 'Haque',
            'userPrincipalName'=> 'mozammel.haque@bKash.com',
            'postalCode'       => '1061',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '22f74f03-9967-4531-84a7-55b5fac0d53e',
            'businessPhones'   => ['01777860860'],
            'displayName'      => 'Md. Shafiul Islam (Regional AML Compliance Officer, AML&CFT, External & Corporate Affairs)',
            'givenName'        => 'Md. Shafiul Islam',
            'jobTitle'         => 'Regional AML Compliance Officer, AML&CFT',
            'mail'             => 'shafiul.islam@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'AML&CFT',
            'preferredLanguage'=> '',
            'surname'          => 'Taposh',
            'userPrincipalName'=> 'shafiul.islam@bKash.com',
            'postalCode'       => '0285',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => 'ec397f24-129e-48b8-93c8-f9e6ba01e683',
            'businessPhones'   => ['01712116572'],
            'displayName'      => 'S M Saklainul Haque Rummon (General Manager, Technology & General Procurement, Supply Chain Management, Finance & Accounts)',
            'givenName'        => 'S M Saklainul Haque',
            'jobTitle'         => 'General Manager, Technology & General Procurement',
            'mail'             => 'saklainul.rummon@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Supply Chain Management',
            'preferredLanguage'=> '',
            'surname'          => 'Rummon',
            'userPrincipalName'=> 'saklainul.rummon@bKash.com',
            'postalCode'       => '0399',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '5a493209-bd5e-4598-844f-26d4ec039fad',
            'businessPhones'   => ['01798576744'],
            'displayName'      => 'Abdulla Mohammad Sakib (Regional AML Compliance Officer, AML&CFT, AML&CFT, External & Corporate Affairs)',
            'givenName'        => 'Abdulla Mohammad',
            'jobTitle'         => 'Regional AML Compliance Officer, AML&CFT',
            'mail'             => 'abdulla.sakib@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'AML&CFT',
            'preferredLanguage'=> '',
            'surname'          => 'Sakib',
            'userPrincipalName'=> 'abdulla.sakib@bKash.com',
            'postalCode'       => '0970',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '2c06b3be-3e69-48f9-bb0e-275173c8d157',
            'businessPhones'   => [],
            'displayName'      => 'A. K. M. Monirul Karim (EVP & HoD, External Affairs, External & Corporate Affairs)',
            'givenName'        => 'A K M Monirul',
            'jobTitle'         => 'EVP & Head of Department, External Affairs',
            'mail'             => 'monirul.karim@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'External Affairs',
            'preferredLanguage'=> '',
            'surname'          => 'Karim',
            'userPrincipalName'=> 'monirul.karim@bKash.com',
            'postalCode'       => '0427',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => 'd20ec910-9866-4c91-8827-58b1e30fecba',
            'businessPhones'   => ['01730325243'],
            'displayName'      => 'Jobaida Khanom (EVP & HoD, Lead HR Business Partner, Human Resources)',
            'givenName'        => 'Jobaida',
            'jobTitle'         => 'EVP & HoD, Lead HR Business Partner',
            'mail'             => 'jobaida.khanom@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'HRBP P&T and F&A',
            'preferredLanguage'=> '',
            'surname'          => 'Khanom',
            'userPrincipalName'=> 'jobaida.khanom@bKash.com',
            'postalCode'       => '0981',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '5350e695-722d-4483-8edb-a3a6db366050',
            'businessPhones'   => ['01840666669'],
            'displayName'      => 'Md. Jafar Iqubal (General Manager, BTL Media, Media & Digital Marketing, Marketing)',
            'givenName'        => 'Md. Jafar',
            'jobTitle'         => 'General Manager, BTL Media',
            'mail'             => 'jafar.iqbal@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Media & Digital Marketing',
            'preferredLanguage'=> '',
            'surname'          => 'Iqubal',
            'userPrincipalName'=> 'jafar.iqbal@bKash.com',
            'postalCode'       => '0253',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '536db07d-c23a-47c9-a591-7df480d43e20',
            'businessPhones'   => ['01674004401'],
            'displayName'      => 'Sibgatullah Mujaddid Alam (VP, Solution Architecture & Planning, Product & Technology)',
            'givenName'        => 'Sibgatullah Mujaddid',
            'jobTitle'         => 'VP, Solution Architecture & Planning',
            'mail'             => 'sibgatullah.alam@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Solution Architecture & Planning',
            'preferredLanguage'=> '',
            'surname'          => 'Alam',
            'userPrincipalName'=> 'sibgatullah.alam@bKash.com',
            'postalCode'       => '0229',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '60adcb50-0695-4441-8ca3-71b65839d637',
            'businessPhones'   => ['01711507427'],
            'displayName'      => 'A.T.M. Mahbub Alam (EVP & HoD, Payroll Business, Commercial)',
            'givenName'        => 'A.T.M. Mahbub',
            'jobTitle'         => 'EVP & Head of Department, Payroll Business',
            'mail'             => 'atm.mahbubalam@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Payroll Business',
            'preferredLanguage'=> '',
            'surname'          => 'Alam',
            'userPrincipalName'=> 'atm.mahbubalam@bKash.com',
            'postalCode'       => '0220',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '904642ff-037c-4a3d-9bce-11930f3ae544',
            'businessPhones'   => ['01716852946'],
            'displayName'      => 'Tanzir Hasan (General Manager, Cyber Defense Operations, IT Governance, Product & Technology)',
            'givenName'        => 'Tanzir',
            'jobTitle'         => 'General Manager, Cyber Defense Operations',
            'mail'             => 'tanzir.hasan@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'IT Governance',
            'preferredLanguage'=> '',
            'surname'          => 'Hasan',
            'userPrincipalName'=> 'tanzir.hasan@bKash.com',
            'postalCode'       => '1024',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '4abeb0d2-3628-45f3-b75d-ff98292678c8',
            'businessPhones'   => ['01711501345'],
            'displayName'      => 'Md. Shoriful Islam (Deputy General Manager, AML&CFT, External & Corporate Affairs)',
            'givenName'        => 'Md. Shoriful',
            'jobTitle'         => 'Deputy General Manager, AML&CFT',
            'mail'             => 'shoriful.islam@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'AML&CFT',
            'preferredLanguage'=> '',
            'surname'          => 'Islam',
            'userPrincipalName'=> 'shoriful.islam@bKash.com',
            'postalCode'       => '0258',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '8124b74f-da57-4b4b-9344-a26352ecf442',
            'businessPhones'   => ['01711500721'],
            'displayName'      => 'Md. Tanveer Zaman (EVP & HoD, Commercial Operations, Commercial)',
            'givenName'        => 'Md. Tanveer',
            'jobTitle'         => 'EVP & Head of Department, Commercial Operations',
            'mail'             => 'tanveer.zaman@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Commercial Operations',
            'preferredLanguage'=> '',
            'surname'          => 'Zaman',
            'userPrincipalName'=> 'tanveer.zaman@bKash.com',
            'postalCode'       => '0401',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '723eee85-8b1e-4c05-84e4-2ed3f57bf620',
            'businessPhones'   => ['01817182089'],
            'displayName'      => 'S. M. Belal Ahmed (EVP & HoD, Govt. & Utility Bill Payments, Commercial)',
            'givenName'        => 'S. M. Belal',
            'jobTitle'         => 'EVP & HoD, Govt. & Utility Bill Payments',
            'mail'             => 'belal.ahmed@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Govt. & Utility Bill Payments',
            'preferredLanguage'=> '',
            'surname'          => 'Ahmed',
            'userPrincipalName'=> 'belal.ahmed@bKash.com',
            'postalCode'       => '0255',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '68a99ef5-ae32-4e27-a416-bc61e316d4f6',
            'businessPhones'   => ['01715578979'],
            'displayName'      => 'Mahfouz Maleque (VP, Data Products, Data Science & Engineering, Product & Technology)',
            'givenName'        => 'Mahfouz',
            'jobTitle'         => 'VP, Data Products, Data Science & Engineering',
            'mail'             => 'mahfouz.maleque@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Data Science & Engineering',
            'preferredLanguage'=> '',
            'surname'          => 'Maleque',
            'userPrincipalName'=> 'mahfouz.maleque@bKash.com',
            'postalCode'       => '0274',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '63aed95a-63c8-4e1c-887c-99f7b6e81a54',
            'businessPhones'   => ['01847066039'],
            'displayName'      => 'Md. Salehin Bin Amin (Territory Manager, Distribution & Retail Business, Commercial)',
            'givenName'        => 'Md. Salehin Bin',
            'jobTitle'         => 'Territory Manager, Distribution & Retail Business',
            'mail'             => 'salehin.amin@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'Distribution & Retail Business',
            'preferredLanguage'=> '',
            'surname'          => 'Amin',
            'userPrincipalName'=> 'salehin.amin@bKash.com',
            'postalCode'       => '0404',
        ],
        [
            '@odata.type'      => '#microsoft.graph.user',
            'id'               => '4c5f8d6a-f60d-4b83-a5d8-38c8b8d3d522',
            'businessPhones'   => ['01837000042'],
            'displayName'      => 'Md. Golam Kabir Hossain (EVP & Head of Department, IT Governance, Product & Technology)',
            'givenName'        => 'Md. Golam Kabir',
            'jobTitle'         => 'EVP & Head of Department, IT Governance',
            'mail'             => 'kabir.hossain@bkash.com',
            'mobilePhone'      => '',
            'officeLocation'   => 'IT Governance',
            'preferredLanguage'=> '',
            'surname'          => 'Hossain',
            'userPrincipalName'=> 'kabir.hossain@bKash.com',
            'postalCode'       => '0063',
        ],
    ];

  }
}
