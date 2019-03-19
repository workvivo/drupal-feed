<?php
namespace Drupal\workvivo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Workvivo block
 *
 * @Block(
 *   id = "workvivo_block",
 *   admin_label = @Translation("Workvivo")
 * )
 */
class Workvivo extends BlockBase implements ContainerFactoryPluginInterface, BlockPluginInterface 
{
	/**
	 * @var \Drupal\workvivo\WorkvivoClient
	 */
	protected $workvivoClient;
	/**
	 * Workvivo constructor.
	 *
	 * @param array $configuration
	 * @param $plugin_id
	 * @param $plugin_definition
	 * @param $workvivo_client \Drupal\workvivo\WorkvivoClient
	 */
	public function __construct(array $configuration, $plugin_id, $plugin_definition, $workvivo_client) 
	{
		parent::__construct($configuration, $plugin_id, $plugin_definition);
		$this->WorkvivoClient = $workvivo_client;
	}
	/**
	 * {@inheritdoc}
	*/
	public function blockForm($form, FormStateInterface $form_state) 
	{
		$form = parent::blockForm($form, $form_state);

		$config = $this->getConfiguration();

		$form['workvivo_url'] = [
			'#type' => 'textfield',
			'#title' => $this->t('URL'),
			'#description' => $this->t('The public URL of your workvivo instance'),
			'#default_value' => isset($config['workvivo_url']) ? $config['workvivo_url'] : '',
		];

		$form['workvivo_api_key'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Api key'),
			'#description' => $this->t('Your workvivo API key'),
			'#default_value' => isset($config['workvivo_api_key']) ? $config['workvivo_api_key'] : '',
		];

		return $form;
	}
	public function getCacheMaxAge() {
		return 0;
	}
	/**
	 * {@inheritdoc}
	 */
	public function blockSubmit($form, FormStateInterface $form_state) 
	{
		parent::blockSubmit($form, $form_state);
		$values = $form_state->getValues();
		$this->configuration['workvivo_url'] = $values['workvivo_url'];
		$this->configuration['workvivo_api_key'] = $values['workvivo_api_key'];
	}
	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) 
	{
		return new static(
			$configuration,
			$plugin_id,
			$plugin_definition,
			$container->get('workvivo_client')
		);
	}

	private function format_item($item)
	{
		$images = '';

		if(count($item['item']['images'])) {
			foreach($item['item']['images'] as $image) {
			$images .= '<img src="' . $image['url'] . '" />';
			}
		}

		return("<div class='item'>
				<div class='user'>
					<a href='{$item['item']['permalink']}'><img src='{$item['user']['avatar_url']}' /></a>
					<div class='meta'>
						<div><a href='{$item['item']['permalink']}'>{$item['user']['first_name']} {$item['user']['last_name']}</a> posted an update</div>
						{$item['item']['relative_created_at']}
					</div>
				</div>
				<div class='update'>
					{$item['item']['html']}
				</div>
				<div class='images'>
					{$images}
				</div>
			</div>");
	}
	/**
	 * {@inheritdoc}
	 */
	public function build() {
		$config = $this->getConfiguration();

		$url = 'Not Set';

		if (empty($config['workvivo_url']) || empty($config['workvivo_api_key'])) {
			$items = "<div>Please set your url and api key</div>";
		} else {
			$workvivo = $this->WorkvivoClient->fetch($config['workvivo_url'], $config['workvivo_api_key']);

			if(is_array($workvivo)) {
				$items = '<ul id="workvivofeed">';
				foreach ($workvivo as $item) {
					$items .= '<li>' . $this->format_item($item) . '</li>';
				}
				$items .= '</ul>';
			} else {
				$items = "<div>$workvivo</div>";
			}
		}

		return [
			'#theme' => 'workvivo',
			'#attached'=> ['library' => ['workvivo/workvivo']],
			'#type' => 'markup',
			'#markup' => $items,
		];
	}
}