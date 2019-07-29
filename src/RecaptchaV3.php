<?php
/**
 * Created by Josias Montag
 * Date: 10/30/18 11:04 AM
 * Mail: josias@montag.info
 */

namespace Lunaweb\RecaptchaV3;


use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository;

class RecaptchaV3
{

    const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * @var string
     */
    protected $secret;
    /**
     * @var string
     */
    protected $sitekey;
    /**
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var bool
     */
    protected $hidden;

    /**
     * RecaptchaV3 constructor.
     *
     * @param $secret
     * @param $sitekey
     * @param $hidden
     */
    public function __construct(Repository $config, Client $client, Request $request)
    {
        $this->secret = $config['recaptchav3']['secret'];
        $this->sitekey = $config['recaptchav3']['sitekey'];
        $this->hidden = $config['recaptchav3']['hidden'];
        $this->http = $client;
        $this->request = $request;
    }


    /*
     * Verify the given token and retutn the score.
     * Returns false if token is invalid.
     * Returns the score if the token is valid.
     *
     * @param $token
     */
    public function verify($token, $action = null)
    {

        $response = $this->http->request('POST', static::VERIFY_URL, [
            'form_params' => [
                'secret'   => $this->secret,
                'response' => $token,
                'remoteip' => $this->request->getClientIp(),
            ],
        ]);


        $body = json_decode($response->getBody(), true);

        if (!isset($body['success']) || $body['success'] !== true) {
            return false;
        }

        if ($action && (!isset($body['action']) || $action != $body['action'])) {
            return false;
        }


        return isset($body['score']) ? $body['score'] : false;

    }


    /**
     * @return string
     */
    public function sitekey()
    {
        return $this->sitekey;
    }

    /**
     * @return string
     */
    public function initJs()
    {
        $html = '<script src="https://www.google.com/recaptcha/api.js?render=' . $this->sitekey . '"></script>';
        if($this->hidden) $html .='<style>.grecaptcha-badge { visibility:hidden; }</style>';
        return $html;
    }


    /**
     * @param $action
     */
    public function field($action, $name = 'g-recaptcha-response')
    {
        $fieldId = uniqid($name . '-', false);
        $html = '<input type="hidden" name="' . $name . '" id="' . $fieldId . '">';
        if($this->hidden) $html .= "<style>.grecaptcha-badge { visibility: visible !important; }</style>";
        $html .= "<script>
  grecaptcha.ready(function() {
      grecaptcha.execute('" . $this->sitekey . "', {action: '" . $action . "'}).then(function(token) {
         document.getElementById('" . $fieldId . "').value = token;
      });
  });
  </script>";
        return $html;
    }


}
