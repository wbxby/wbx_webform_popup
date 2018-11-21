<?php

namespace Drupal\wbx_webform_popup\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;

/**
 * Class WebformPopupController.
 */
class WebformPopupController extends ControllerBase {

  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $webformStorage;

  /**
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * WebformPopupController constructor.
   *
   * @param \Drupal\Core\Render\Renderer $renderer
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   */
  public function __construct(
    Renderer $renderer,
    EntityTypeManagerInterface $entityTypeManager,
    RequestStack $requestStack
  ) {
    $this->renderer = $renderer;
    $this->webformStorage = $entityTypeManager->getStorage('webform');
    $this->viewBuilder = $entityTypeManager->getViewBuilder('webform');
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * @param $id
   * @param string $ajax
   *
   * @return AjaxResponse|RedirectResponse
   */
  public function load($id, $ajax = 'nojs') {
    if ($ajax === 'nojs') {
      $url = Url::fromRoute('entity.webform.canonical', ['webform' => $id])->toString();
      return new RedirectResponse($url);
    }
    $form = $this->webformStorage->load($id);
    $view = $this->viewBuilder->view($form);
    $request = $this->requestStack->getCurrentRequest();
    $wrapper = 'body';
    $parameters = $request->query->all();
    foreach ($parameters as $name => $parameter) {
      if (strpos($name, 'fill_') !== FALSE) {
        $field = str_replace('fill_', '', $name);
        if (isset($view['elements'][$field])) {
          $view['elements'][$field]['#value'] = $parameter;
        }
      }
    }
    if ($wrapper_id = $request->query->get('insert')) {
      $wrapper = '#' . $wrapper_id;
    }
    $response = new AjaxResponse();
    $output = '<div class="popup-overlay"><div class="popup-wrapper"><div class="popup-close"><i class="icon-close"></i></div>';
    $output .= $this->renderer->renderRoot($view);
    $output .= '</div></div>';
    $settings = $view['#attached']['drupalSettings'];
    $response->addCommand(new SettingsCommand($settings, TRUE));
    $response->addCommand(new AppendCommand($wrapper, $output));
    return $response;
  }

}
