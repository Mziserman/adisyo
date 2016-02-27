<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->redirect(
            "https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=77s899qhhimkbr&redirect_uri=http%3A%2F%2F127.0.0.1:8000%2Fauth%2Flinkedin&state=1&scope=r_basicprofile"
        );
    }

    /**
     * @Route("/auth/linkedin", name="tgbatar")
     */
    public function authAction(Request $request)
    {

        if (isset($_GET["code"])) {

            $params = array(
                'grant_type' => 'authorization_code',
                'client_id' => '77s899qhhimkbr',
                'client_secret' => 'GzVxFh5WUw0IQZtg',
                'code' => $_GET['code'],
                'redirect_uri' => 'http%3A%2F%2F127.0.0.1:8000%2Fauth%2Flinkedin',
            );
            // Access Token request
            $params =  http_build_query($params);

            $url = "https://www.linkedin.com/uas/oauth2/accessToken";

            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded',
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS,
                "grant_type=authorization_code&code=". $_GET["code"] ."&redirect_uri=http%3A%2F%2F127.0.0.1:8000%2Fauth%2Flinkedin&client_id=77s899qhhimkbr&client_secret=GzVxFh5WUw0IQZtg");
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
            $result = curl_exec($ch);
            curl_close($ch);

            $_result = json_decode($result, true);

            dump($_result);

            $url = "https://api.linkedin.com/v1/people/~:(firstName,lastName,public-profile-url,headline,industry,location,current-share,summary,specialties,positions,picture-url)";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer " .
                $_result['access_token'] . "\r\n" .
                "Content-Length: 0\r\n" .
                "x-li-format: json\r\n"

            ));
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
            $result = curl_exec($ch);
            curl_close($ch);

            dump($result);
        }

        /*if (isset($_GET['error'])) {
            echo $_GET['error'] . ': ' . $_GET['error_description'];
        } elseif (isset($_GET['code'])) {
            $this->getAccessToken();

            $user = $this->fetch('GET', '/v1/people/~:(firstName,lastName)');
            var_dump($user);
        }*/


        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ));
    }

    public function getAccessToken() {
        $params = array(
            'grant_type' => 'authorization_code',
            'client_id' => '77s899qhhimkbr',
            'client_secret' => 'GzVxFh5WUw0IQZtg',
            'code' => $_GET['code'],
            'redirect_uri' => 'http%3A%2F%2F127.0.0.1:8000%2Fauth%2Flinkedin',
        );
        // Access Token request
        $url = 'https://www.linkedin.com/uas/oauth2/accessToken?' . http_build_query($params);

        var_dump($url);

        // Tell streams to make a POST request
        $context = stream_context_create(
            array('http' =>
                array('method' => 'POST',
                )
            )
        );
        // Retrieve access token information
        $response = file_get_contents($url, false, $context);
        // Native PHP object, please
        $token = json_decode($response);
        // Store access token and expiration time
        $_SESSION['access_token'] = $token->access_token; // guard this!
        $_SESSION['expires_in'] = $token->expires_in; // relative time (in seconds)
        $_SESSION['expires_at'] = time() + $_SESSION['expires_in']; // absolute time
        return true;
    }

    public function fetch($method, $resource, $body = '') {
        $opts = array(
            'http' => array(
                'method' => $method,
                'header' => "Authorization: Bearer " .
                    $_SESSION['access_token'] . "\r\n" .
                    "x-li-format: json\r\n"
            )
        );
        $url = 'https://api.linkedin.com' . $resource;
        if (count($params)) {
            $url .= '?' . http_build_query($params);
        }
        $context = stream_context_create($opts);

        $response = file_get_contents($url, false, $context);
        return json_decode($response);
    }
}
