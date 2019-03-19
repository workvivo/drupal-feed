<?php
namespace Drupal\workvivo;

use Drupal\Component\Serialization\Json;

class WorkvivoClient {
  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;
  /**
   * WorkvivoClient constructor.
   *
   * @param $http_client_factory \Drupal\Core\Http\ClientFactory
   */
    public function __construct($http_client_factory) {
        $this->client = $http_client_factory->fromOptions([
            'base_uri' => 'https://www.workvivo.com/api/'
        ]);
    }
  /**
   * Fetch latest workvivo posts.
   *
   * @return array
   */
  public function fetch($url, $token) {
      try {
        $response = $this->client->get($url .'feed', ['headers' => 
            [
                'Authorization' => "Bearer $token"
            ]
        ]);
        
        $data = Json::decode($response->getBody());
        return $data['data'];

      } catch (\Exception $e) {
          return 'Unable to connect to workvivo, please check block config information';
      }
    
  }
}